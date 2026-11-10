<?php
$errors = $errors ?? [];
$errMdp = $errors['mot_de_passe'] ?? null;
$errConf = $errors['mot_de_passe_confirmation'] ?? null;
?>
<div class="container py-5">
    <div class="col-md-8 col-lg-6 mx-auto">
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un nouveau mot de passe pour votre compte.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le formulaire contient des erreurs :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/reinitialisation/<?= $this->e($token) ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Nouveau mot de passe</label>
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

            <div class="mb-4">
                <label for="mot_de_passe_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input class="form-control<?= $errConf ? ' is-invalid' : '' ?>" type="password" id="mot_de_passe_confirmation"
                       name="mot_de_passe_confirmation" autocomplete="new-password" required
                       <?= $errConf ? 'aria-invalid="true" aria-describedby="mot_de_passe_confirmation-erreur"' : '' ?>>
                <?php if ($errConf): ?><div id="mot_de_passe_confirmation-erreur" class="invalid-feedback d-block"><?= $this->e($errConf) ?></div><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Enregistrer le nouveau mot de passe</button>
        </form>
    </div>
</div>
<script src="/assets/js/register.js" defer></script>
