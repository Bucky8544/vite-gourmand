<?php
$old = $old ?? [];
$errors = $errors ?? [];

$champ = function (string $name, string $label, string $type = 'text', string $autocomplete = '', string $aide = '') use ($old, $errors): void {
    $err = $errors[$name] ?? null;
    $ids = array_filter([$aide !== '' ? "$name-aide" : null, $err ? "$name-erreur" : null]); ?>
    <div class="mb-3">
        <label for="<?= $this->e($name) ?>" class="form-label"><?= $this->e($label) ?></label>
        <input class="form-control<?= $err ? ' is-invalid' : '' ?>" type="<?= $this->e($type) ?>" id="<?= $this->e($name) ?>" name="<?= $this->e($name) ?>"
               value="<?= $this->e($old[$name] ?? '') ?>"<?= $autocomplete !== '' ? ' autocomplete="' . $this->e($autocomplete) . '"' : '' ?>
               <?= $err ? 'aria-invalid="true"' : '' ?><?= $ids ? ' aria-describedby="' . $this->e(implode(' ', $ids)) . '"' : '' ?> required>
        <?php if ($aide !== ''): ?><div id="<?= $this->e($name) ?>-aide" class="form-text"><?= $this->e($aide) ?></div><?php endif; ?>
        <?php if ($err): ?><div id="<?= $this->e($name) ?>-erreur" class="invalid-feedback d-block"><?= $this->e($err) ?></div><?php endif; ?>
    </div>
<?php };
?>
<div class="container py-5">
    <div class="col-lg-7 mx-auto">
        <nav aria-label="Fil d'Ariane" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/mon-espace">Mon espace</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mes informations</li>
            </ol>
        </nav>

        <h1>Mes informations</h1>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le formulaire contient des erreurs :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $nom => $message): ?>
                        <li><a href="#<?= $this->e($nom) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/mon-espace/profil" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <p class="form-label mb-1">Adresse e-mail (votre identifiant)</p>
                <p class="mb-0"><strong><?= $this->e($profil['email'] ?? '') ?></strong></p>
                <p class="form-text">Votre adresse e-mail est votre identifiant : elle ne peut pas être modifiée ici. Pour en changer, contactez-nous.</p>
            </div>

            <div class="row">
                <div class="col-md-6"><?php $champ('prenom', 'Prénom', 'text', 'given-name'); ?></div>
                <div class="col-md-6"><?php $champ('nom', 'Nom', 'text', 'family-name'); ?></div>
            </div>
            <?php $champ('telephone', 'Numéro de GSM', 'tel', 'tel', 'Numéro mobile français, par exemple 06 12 34 56 78.'); ?>
            <?php $champ('adresse_postale', 'Adresse postale', 'text', 'street-address'); ?>
            <div class="row">
                <div class="col-md-4"><?php $champ('code_postal', 'Code postal', 'text', 'postal-code'); ?></div>
                <div class="col-md-8"><?php $champ('ville', 'Ville', 'text', 'address-level2'); ?></div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer</button>
                <a class="btn btn-outline-primary btn-lg" href="/mon-espace">Annuler</a>
            </div>
        </form>
    </div>
</div>
