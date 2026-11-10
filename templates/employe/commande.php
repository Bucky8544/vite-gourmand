<?php
use App\Core\Money;
use App\Service\OrderStatus;
use App\Service\Pricing;

$c = $commande;
$errors = $errors ?? [];
$old = $old ?? [];
$numero = $this->e($c['numero_commande']);
$cents = static fn (string $euros): int => Pricing::toCents($euros);
$date = (new DateTimeImmutable($c['date_prestation']))->format('d/m/Y');
$heure = substr((string) $c['heure_livraison'], 0, 5);
$tel = trim(chunk_split((string) $c['telephone'], 2, ' '));
$nomComplet = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
$formatDate = static function (string $dateHeure): array {
    $d = new DateTimeImmutable($dateHeure);

    return [$d->format('c'), $d->format('d/m/Y') . ' à ' . $d->format('H:i')];
};
?>
<div class="container py-5">
    <div class="col-lg-10 mx-auto">
        <nav aria-label="Fil d'Ariane" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/employe">Espace employé</a></li>
                <li class="breadcrumb-item active" aria-current="page">Commande <?= $numero ?></li>
            </ol>
        </nav>

        <h1>Commande <?= $numero ?></h1>
        <p>Statut : <span class="statut statut-<?= $this->e($c['statut']) ?>"><?= $this->e(OrderStatus::label($c['statut'])) ?></span></p>

        <div class="row g-4">
            <div class="col-lg-6">
                <section class="carte-espace h-100" aria-labelledby="titre-client">
                    <h2 id="titre-client" class="h4">Client</h2>
                    <dl class="mb-0">
                        <dt>Nom</dt><dd><?= $this->e($nomComplet) ?></dd>
                        <dt>E-mail</dt><dd><a href="mailto:<?= $this->e($c['email']) ?>"><?= $this->e($c['email']) ?></a></dd>
                        <dt>GSM</dt><dd><a href="tel:<?= $this->e((string) $c['telephone']) ?>"><?= $this->e($tel) ?></a></dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="carte-espace h-100" aria-labelledby="titre-prestation">
                    <h2 id="titre-prestation" class="h4">Prestation</h2>
                    <dl class="mb-0">
                        <dt>Menu</dt><dd><?= $this->e($c['menu_titre']) ?> (<?= (int) $c['nombre_personne'] ?> personnes)</dd>
                        <dt>Date et heure</dt><dd><?= $this->e($date) ?> à <?= $this->e($heure) ?></dd>
                        <dt>Lieu</dt><dd><?= $this->e($c['adresse_prestation']) ?>, <?= $this->e($c['code_postal_prestation']) ?> <?= $this->e($c['ville_prestation']) ?></dd>
                    </dl>
                </section>
            </div>
        </div>

        <h2 class="h4 mt-4">Prix</h2>
        <table class="table devis">
            <caption class="visually-hidden">Prix de la commande</caption>
            <tbody>
                <tr><th scope="row">Menu</th><td class="text-end"><?= Money::format($cents($c['prix_menu']) + $cents($c['remise'])) ?></td></tr>
                <?php if ($cents($c['remise']) > 0): ?>
                    <tr><th scope="row">Remise</th><td class="text-end">&minus;<?= Money::format($cents($c['remise'])) ?></td></tr>
                <?php endif; ?>
                <tr><th scope="row">Livraison</th><td class="text-end"><?= $cents($c['prix_livraison']) === 0 ? 'Offerte' : Money::format($cents($c['prix_livraison'])) ?></td></tr>
            </tbody>
            <tfoot><tr class="devis-total"><th scope="row">Total</th><td class="text-end"><?= Money::format($cents($c['prix_total'])) ?></td></tr></tfoot>
        </table>

        <?php if ((int) $c['pret_materiel'] === 1): ?>
            <p><strong>Matériel prêté :</strong> <?= (int) $c['materiel_restitue'] === 1 ? 'restitué.' : 'pas encore restitué.' ?></p>
        <?php endif; ?>

        <?php if ($c['statut'] === 'annulee'): ?>
            <section aria-labelledby="titre-annulation" class="mb-4">
                <h2 id="titre-annulation" class="h4">Annulation</h2>
                <p class="mb-1">Motif : <?= $this->e($c['motif_annulation'] ?? '') ?></p>
                <p class="mb-0">Client contacté <?= ($c['mode_contact_annulation'] ?? '') === 'gsm' ? 'par téléphone (GSM)' : (($c['mode_contact_annulation'] ?? '') === 'mail' ? 'par e-mail' : '(non précisé : annulation faite par le client)') ?>.</p>
            </section>
        <?php endif; ?>

        <section aria-labelledby="titre-actions" class="mt-4">
            <h2 id="titre-actions" class="h4">Actions</h2>

            <?php if ($suivants === [] && !$annulable): ?>
                <p>Cette commande est <?= $c['statut'] === 'annulee' ? 'annulée' : 'terminée' ?> : aucune action n'est possible.</p>
            <?php endif; ?>

            <?php foreach ($suivants as $cible): ?>
                <form method="post" action="/employe/commandes/<?= $numero ?>/statut" class="mb-3">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="statut" value="<?= $this->e($cible) ?>">
                    <?php if ($cible === 'livre'): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="pret_materiel" name="pret_materiel" value="1">
                            <label class="form-check-label" for="pret_materiel">Du matériel a été prêté au client (il devra le restituer)</label>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $this->e(OrderStatus::ACTIONS[$cible] ?? $cible) ?></button>
                    <?php if ($cible === 'attente_retour_materiel'): ?>
                        <p class="form-text">Le client recevra un e-mail lui rappelant les 600 € de frais s'il ne rend pas le matériel sous 10 jours ouvrés.</p>
                    <?php elseif ($cible === 'terminee'): ?>
                        <p class="form-text">Le client recevra un e-mail l'invitant à donner son avis.</p>
                    <?php elseif ($cible === 'accepte'): ?>
                        <p class="form-text">La commande sera comptée dans les statistiques.</p>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>

            <?php if ($annulable): ?>
                <h3 class="h5 mt-4">Annuler la commande</h3>
                <p class="form-text">Contactez d'abord le client par téléphone ou par e-mail : l'annulation exige d'indiquer le mode de contact et le motif.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <p class="fw-bold mb-2">L'annulation n'a pas été enregistrée :</p>
                        <ul class="mb-0">
                            <?php foreach ($errors as $champ => $message): ?>
                                <li><a href="#<?= $this->e($champ === 'mode_contact' ? 'contact-gsm' : $champ) ?>"><?= $this->e($message) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/employe/commandes/<?= $numero ?>/annuler" novalidate>
                    <?= \App\Core\Csrf::field() ?>
                    <fieldset class="mb-3"<?= isset($errors['mode_contact']) ? ' aria-describedby="mode_contact-erreur"' : '' ?>>
                        <legend class="fs-6 fw-bold">Comment avez-vous contacté le client ?</legend>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input<?= isset($errors['mode_contact']) ? ' is-invalid' : '' ?>" type="radio" name="mode_contact" id="contact-gsm" value="gsm"<?= ($old['mode_contact'] ?? '') === 'gsm' ? ' checked' : '' ?>>
                            <label class="form-check-label" for="contact-gsm">Par téléphone (GSM)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input<?= isset($errors['mode_contact']) ? ' is-invalid' : '' ?>" type="radio" name="mode_contact" id="contact-mail" value="mail"<?= ($old['mode_contact'] ?? '') === 'mail' ? ' checked' : '' ?>>
                            <label class="form-check-label" for="contact-mail">Par e-mail</label>
                        </div>
                        <?php if (isset($errors['mode_contact'])): ?><div id="mode_contact-erreur" class="invalid-feedback d-block"><?= $this->e($errors['mode_contact']) ?></div><?php endif; ?>
                    </fieldset>

                    <div class="mb-3">
                        <label for="motif" class="form-label">Motif de l'annulation</label>
                        <textarea class="form-control<?= isset($errors['motif']) ? ' is-invalid' : '' ?>" id="motif" name="motif" rows="3" maxlength="500" required
                                  <?= isset($errors['motif']) ? 'aria-invalid="true" aria-describedby="motif-erreur"' : '' ?>><?= $this->e($old['motif'] ?? '') ?></textarea>
                        <?php if (isset($errors['motif'])): ?><div id="motif-erreur" class="invalid-feedback d-block"><?= $this->e($errors['motif']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input<?= isset($errors['contacte']) ? ' is-invalid' : '' ?>" type="checkbox" id="contacte" name="contacte" value="1"
                               <?= isset($errors['contacte']) ? 'aria-invalid="true" aria-describedby="contacte-erreur"' : '' ?>>
                        <label class="form-check-label" for="contacte">J'ai bien contacté le client avant d'annuler sa commande.</label>
                        <?php if (isset($errors['contacte'])): ?><div id="contacte-erreur" class="invalid-feedback d-block"><?= $this->e($errors['contacte']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Annuler la commande</button>
                </form>
            <?php endif; ?>
        </section>

        <section aria-labelledby="titre-historique" class="mt-5">
            <h2 id="titre-historique" class="h4">Historique des statuts</h2>
            <ol class="suivi list-unstyled">
                <?php foreach ($historique as $h): ?>
                    <?php [$iso, $texte] = $formatDate($h['date_modification']); ?>
                    <li>
                        <span class="suivi-statut"><?= $this->e(OrderStatus::label($h['statut'])) ?></span>
                        <time datetime="<?= $this->e($iso) ?>"><?= $this->e($texte) ?></time>
                        <span class="text-muted"><?= $h['email'] !== null ? 'par ' . $this->e(trim(($h['prenom'] ?? '') . ' ' . ($h['nom'] ?? '')) ?: $h['email']) : '' ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php if ($avis): ?>
            <section aria-labelledby="titre-avis" class="mt-4">
                <h2 id="titre-avis" class="h4">Avis du client</h2>
                <p class="mb-1"><strong>Note : <?= (int) $avis['note'] ?> sur 5</strong></p>
                <p class="mb-0"><?= nl2br($this->e($avis['commentaire'])) ?></p>
            </section>
        <?php endif; ?>
    </div>
</div>
