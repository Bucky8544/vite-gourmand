<?php
use App\Core\Entreprise;

$old = $old ?? [];
$errors = $errors ?? [];
$general = $errors['_general'] ?? null;
$champs = array_diff_key($errors, ['_general' => 1]);

$classe = static fn (string $nom): string => isset($errors[$nom]) ? ' is-invalid' : '';
$decrire = static fn (string $nom): string => isset($errors[$nom]) ? ' aria-invalid="true" aria-describedby="' . $nom . '-erreur"' : '';
$retour = function (string $nom) use ($errors): void {
    if (isset($errors[$nom])) {
        echo '<div id="' . $nom . '-erreur" class="invalid-feedback d-block">' . $this->e($errors[$nom]) . '</div>';
    }
};
?>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <h1>Nous contacter</h1>
            <p class="lead">Une question, une demande particulière, un événement à organiser ? Écrivez-nous : nous vous répondrons à l'adresse e-mail que vous indiquez.</p>

            <?php if ($general): ?>
                <div class="alert alert-danger" role="alert"><?= $this->e($general) ?></div>
            <?php endif; ?>
            <?php if ($champs): ?>
                <div class="alert alert-danger" role="alert">
                    <p class="fw-bold mb-2">Le message n'a pas été envoyé :</p>
                    <ul class="mb-0">
                        <?php foreach ($champs as $nom => $message): ?>
                            <li><a href="#<?= $this->e($nom) ?>"><?= $this->e($message) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="/contact" novalidate>
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Votre adresse e-mail</label>
                    <input class="form-control<?= $classe('email') ?>" type="email" id="email" name="email" autocomplete="email" required
                           value="<?= $this->e($old['email'] ?? '') ?>"<?= $decrire('email') ?>>
                    <?php $retour('email'); ?>
                </div>
                <div class="mb-3">
                    <label for="titre" class="form-label">Objet</label>
                    <input class="form-control<?= $classe('titre') ?>" type="text" id="titre" name="titre" maxlength="150" required
                           value="<?= $this->e($old['titre'] ?? '') ?>"<?= $decrire('titre') ?>>
                    <?php $retour('titre'); ?>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Votre message</label>
                    <textarea class="form-control<?= $classe('description') ?>" id="description" name="description" rows="7" maxlength="3000" required<?= $decrire('description') ?>><?= $this->e($old['description'] ?? '') ?></textarea>
                    <?php $retour('description'); ?>
                </div>

                <div class="champ-piege" aria-hidden="true">
                    <input type="text" name="site_web" tabindex="-1" autocomplete="off" value="">
                </div>

                <p class="form-text">Votre adresse e-mail et votre message servent uniquement à vous répondre. Ils ne sont pas conservés sur le site. Pour en savoir plus, consultez notre <a href="/confidentialite">politique de confidentialité</a>.</p>
                <button type="submit" class="btn btn-primary btn-lg">Envoyer le message</button>
            </form>
        </div>

        <aside class="col-lg-5" aria-labelledby="titre-coordonnees">
            <div class="carte-espace">
                <h2 id="titre-coordonnees" class="h4"><?= $this->e(Entreprise::get('nom')) ?></h2>
                <p class="mb-2"><?= $this->e(Entreprise::get('adresse')) ?></p>
                <p class="mb-2">Téléphone : <a href="tel:<?= $this->e(preg_replace('/\s+/', '', Entreprise::get('telephone'))) ?>"><?= $this->e(Entreprise::get('telephone')) ?></a></p>
                <p class="mb-0">E-mail : <a href="mailto:<?= $this->e(Entreprise::get('email')) ?>"><?= $this->e(Entreprise::get('email')) ?></a></p>
            </div>
        </aside>
    </div>
</div>
