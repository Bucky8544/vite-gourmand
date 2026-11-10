<?php
$old = $old ?? [];
$errors = $errors ?? [];

$champ = function (string $name, string $label, string $type = 'text', string $autocomplete = '', string $aide = '') use ($old, $errors): void {
    $err = $errors[$name] ?? null;
    $val = $type === 'password' ? '' : ($old[$name] ?? '');
    $descr = array_filter([$aide !== '' ? "$name-aide" : null, $err ? "$name-erreur" : null]);
    ?>
    <div class="mb-3">
        <label for="<?= $this->e($name) ?>" class="form-label"><?= $this->e($label) ?></label>
        <input class="form-control<?= $err ? ' is-invalid' : '' ?>" type="<?= $this->e($type) ?>"
               id="<?= $this->e($name) ?>" name="<?= $this->e($name) ?>" value="<?= $this->e($val) ?>"
               <?= $autocomplete !== '' ? 'autocomplete="' . $this->e($autocomplete) . '"' : '' ?>
               <?= $err ? 'aria-invalid="true"' : '' ?>
               <?= $descr ? 'aria-describedby="' . $this->e(implode(' ', $descr)) . '"' : '' ?> required>
        <?php if ($aide !== ''): ?><div id="<?= $this->e($name) ?>-aide" class="form-text"><?= $this->e($aide) ?></div><?php endif; ?>
        <?php if ($err): ?><div id="<?= $this->e($name) ?>-erreur" class="invalid-feedback d-block"><?= $this->e($err) ?></div><?php endif; ?>
    </div>
    <?php
};
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <h1>Créer un compte</h1>
        <p>Tous les champs sont obligatoires. Un compte vous permet de commander et de suivre vos commandes.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le formulaire contient des erreurs :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $champErreur => $message): ?>
                        <li><?= $champErreur === '_general'
                            ? $this->e($message)
                            : '<a href="#' . $this->e($champErreur) . '">' . $this->e($message) . '</a>' ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/inscription">
            <?= \App\Core\Csrf::field() ?>

            <div class="row">
                <div class="col-md-6"><?php $champ('prenom', 'Prénom', 'text', 'given-name'); ?></div>
                <div class="col-md-6"><?php $champ('nom', 'Nom', 'text', 'family-name'); ?></div>
            </div>
            <?php $champ('email', 'Adresse e-mail (votre identifiant)', 'email', 'email'); ?>
            <?php $champ('telephone', 'Numéro de GSM', 'tel', 'tel', 'Numéro mobile français, par exemple 06 12 34 56 78.'); ?>
            <?php $champ('adresse_postale', 'Adresse postale', 'text', 'street-address'); ?>
            <div class="row">
                <div class="col-md-4"><?php $champ('code_postal', 'Code postal', 'text', 'postal-code'); ?></div>
                <div class="col-md-8"><?php $champ('ville', 'Ville', 'text', 'address-level2'); ?></div>
            </div>

            <?php $errMdp = $errors['mot_de_passe'] ?? null; ?>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input class="form-control<?= $errMdp ? ' is-invalid' : '' ?>" type="password" id="mot_de_passe"
                       name="mot_de_passe" autocomplete="new-password" required
                       <?= $errMdp ? 'aria-invalid="true"' : '' ?> aria-describedby="mot_de_passe-aide<?= $errMdp ? ' mot_de_passe-erreur' : '' ?>">
                <div id="mot_de_passe-aide" class="form-text">
                    Votre mot de passe doit contenir :
                    <ul class="regles list-unstyled mb-0">
                        <?php foreach (['longueur' => '10 caractères minimum', 'majuscule' => 'une majuscule', 'minuscule' => 'une minuscule',
                                        'chiffre' => 'un chiffre', 'special' => 'un caractère spécial (par exemple ! ? # @ ou un espace)'] as $cle => $texte): ?>
                            <li data-regle="<?= $this->e($cle) ?>"><span class="etat" aria-hidden="true">•</span>
                                <span class="visually-hidden statut">à respecter :</span> <?= $this->e($texte) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($errMdp): ?><div id="mot_de_passe-erreur" class="invalid-feedback d-block"><?= $this->e($errMdp) ?></div><?php endif; ?>
            </div>
            <?php $champ('mot_de_passe_confirmation', 'Confirmer le mot de passe', 'password', 'new-password'); ?>

            <?php $errCons = $errors['consentement'] ?? null; ?>
            <div class="form-check mb-4">
                <input class="form-check-input<?= $errCons ? ' is-invalid' : '' ?>" type="checkbox" id="consentement"
                       name="consentement" value="1" required <?= $errCons ? 'aria-invalid="true" aria-describedby="consentement-erreur"' : '' ?>>
                <label class="form-check-label" for="consentement">
                    J'ai lu et j'accepte la <a href="/confidentialite">politique de confidentialité</a>.
                    Mes données servent uniquement à gérer mon compte et mes commandes.
                </label>
                <?php if ($errCons): ?><div id="consentement-erreur" class="invalid-feedback d-block"><?= $this->e($errCons) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">Créer mon compte</button>
        </form>
    </div>
</div>
<script src="/assets/js/register.js" defer></script>
