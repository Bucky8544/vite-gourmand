<?php
declare(strict_types=1);

namespace App\Service;

use finfo;
use RuntimeException;

/**
 * Images des menus téléversées par l'équipe. Le contenu est vérifié (type réel, dimensions), le nom est
 * toujours généré par le serveur (jamais celui du client) et le dossier n'exécute aucun script.
 */
final class ImageStorage
{
    public const URL = '/assets/uploads/menus/';
    public const TAILLE_MAX = 2097152; // 2 Mo
    public const DIMENSION_MAX = 6000;

    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private string $dossier;

    /** $televerse : false uniquement dans les tests (fichiers qui ne viennent pas d'un envoi HTTP). */
    public function __construct(?string $dossier = null, private bool $televerse = true)
    {
        $this->dossier = rtrim($dossier ?? dirname(__DIR__, 2) . '/public/assets/uploads/menus', '/');
    }

    /**
     * @param array<string,mixed> $fichier un fichier de $_FILES (name, tmp_name, error, size)
     * @return array{ok:bool,vide?:bool,ext?:string,erreur?:string}
     */
    public function inspecter(array $fichier): array
    {
        $erreur = $fichier['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erreur === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'vide' => true];
        }

        $tmp = $fichier['tmp_name'] ?? null;
        if (!is_int($erreur) || !is_string($tmp) || $tmp === '') {
            return $this->refus("Le fichier n'a pas pu être lu.");
        }
        if ($erreur === UPLOAD_ERR_INI_SIZE || $erreur === UPLOAD_ERR_FORM_SIZE) {
            return $this->refus('Le fichier est trop lourd (2 Mo maximum).');
        }
        if ($erreur !== UPLOAD_ERR_OK) {
            return $this->refus("Le fichier n'a pas pu être téléversé. Réessayez.");
        }
        if ($this->televerse && !is_uploaded_file($tmp)) {
            return $this->refus("Le fichier n'a pas pu être lu.");
        }

        $taille = @filesize($tmp);
        if ($taille === false || $taille <= 0) {
            return $this->refus('Le fichier est vide.');
        }
        if ($taille > self::TAILLE_MAX) {
            return $this->refus('Le fichier est trop lourd (2 Mo maximum).');
        }

        // Le type est lu dans le contenu : l'extension et le type annoncés par le navigateur ne comptent pas
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!is_string($mime) || !isset(self::TYPES[$mime])) {
            return $this->refus('Format non accepté : choisissez une image JPEG, PNG ou WebP.');
        }

        $dimensions = @getimagesize($tmp);
        if ($dimensions === false || ($dimensions['mime'] ?? '') !== $mime) {
            return $this->refus("Ce fichier n'est pas une image valide.");
        }
        if ($dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > self::DIMENSION_MAX || $dimensions[1] > self::DIMENSION_MAX) {
            return $this->refus('Image trop grande (' . self::DIMENSION_MAX . ' pixels maximum par côté).');
        }

        return ['ok' => true, 'ext' => self::TYPES[$mime]];
    }

    /** Enregistre un fichier déjà inspecté et renvoie son adresse publique. */
    public function enregistrer(string $tmp, string $ext): string
    {
        if (!in_array($ext, self::TYPES, true)) {
            throw new RuntimeException('Extension non autorisée.');
        }
        if (!is_dir($this->dossier) && !@mkdir($this->dossier, 0755, true) && !is_dir($this->dossier)) {
            throw new RuntimeException('Dossier des images inaccessible.');
        }

        $nom = bin2hex(random_bytes(16)) . '.' . $ext;
        $cible = $this->dossier . '/' . $nom;
        $ok = $this->televerse ? move_uploaded_file($tmp, $cible) : copy($tmp, $cible);
        if (!$ok) {
            throw new RuntimeException("Enregistrement de l'image impossible (droits du dossier ?).");
        }
        @chmod($cible, 0644);

        return self::URL . $nom;
    }

    /** Ne supprime que les fichiers créés par cette classe : les images du seed et tout autre chemin sont ignorés. */
    public function supprimer(string $url): void
    {
        if (preg_match('#^' . preg_quote(self::URL, '#') . '([a-f0-9]{32}\.(?:jpg|png|webp))$#', $url, $m)) {
            @unlink($this->dossier . '/' . $m[1]);
        }
    }

    /** @return array{ok:false,erreur:string} */
    private function refus(string $message): array
    {
        return ['ok' => false, 'erreur' => $message];
    }
}
