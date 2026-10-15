<?php
$old = $old ?? [];
$errors = $errors ?? [];
$c = $commande;
$minimum = (int) $c['nombre_personne_minimum'];
$delai = (int) $c['delai_commande_jours'];
$numero = $this->e($c['numero_commande']);
$aujourdhui = (new DateTimeImmutable('today'))->format('Y-m-d');

$classe = static fn (string $nom): string => isset($errors[$nom]) ? ' is-invalid' : '';
$decrire = static function (string $nom, string $aide = '') use ($errors): string {
    $ids = array_filter([$aide !== '' ? $aide : null, isset($errors[$nom]) ? $nom . '-erreur' : null]);

    return (isset($errors[$nom]) ? ' aria-invalid="true"' : '') . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$retour = function (string $nom) use ($errors): void {
    if (isset($errors[$nom])) {
        echo '<div id="' . $nom . '-erreur" class="invalid-feedback d-block">' . $this->e($errors[$nom]) . '</div>';
    }
};
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <nav aria-label="Fil d'Ariane" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/mon-espace">Mon espace</a></li>
                <li class="breadcrumb-item"><a href="/mon-espace/commandes/<?= $numero ?>">Commande <?= $numero ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>

        <h1>Modifier la commande <?= $numero ?></h1>
        <p>Menu : <strong><?= $this->e($c['menu_titre']) ?></strong>. Le menu ne peut pas être changé : pour un autre menu, annulez cette commande et passez-en une nouvelle.</p>

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

        <form method="post" action="/mon-espace/commandes/<?= $numero ?>/modifier" novalidate>
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" id="menu_id" name="menu_id" value="<?= (int) $c['menu_id'] ?>">

            <div class="mb-3">
                <label for="personnes" class="form-label">Nombre de personnes</label>
                <input class="form-control<?= $classe('personnes') ?>" type="number" id="personnes" name="personnes" step="1" min="<?= $minimum ?>"
                       value="<?= $this->e($old['personnes'] ?? '') ?>" required<?= $decrire('personnes', 'aide-personnes') ?>>
                <div id="aide-personnes" class="form-text">Minimum pour ce menu : <?= $minimum ?> personnes. Remise de 10 % à partir de <?= $minimum + 5 ?> personnes.</div>
                <?php $retour('personnes'); ?>
            </div>

            <div id="estimation" class="estimation" aria-live="polite">
                <p class="mb-0">Le prix détaillé s'affiche à l'étape suivante.</p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_prestation" class="form-label">Date de la prestation</label>
                    <input class="form-control<?= $classe('date_prestation') ?>" type="date" id="date_prestation" name="date_prestation"
                           min="<?= $this->e($aujourdhui) ?>" value="<?= $this->e($old['date_prestation'] ?? '') ?>" required<?= $decrire('date_prestation', 'aide-delai') ?>>
                    <div id="aide-delai" class="form-text">Si vous changez la date, ce menu doit être commandé au moins <?= $delai ?> jours avant la prestation.</div>
                    <?php $retour('date_prestation'); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="heure_livraison" class="form-label">Heure de livraison souhaitée</label>
                    <input class="form-control<?= $classe('heure_livraison') ?>" type="time" id="heure_livraison" name="heure_livraison"
                           value="<?= $this->e($old['heure_livraison'] ?? '') ?>" required<?= $decrire('heure_livraison') ?>>
                    <?php $retour('heure_livraison'); ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="adresse_prestation" class="form-label">Adresse de la prestation</label>
                <input class="form-control<?= $classe('adresse_prestation') ?>" type="text" id="adresse_prestation" name="adresse_prestation"
                       value="<?= $this->e($old['adresse_prestation'] ?? '') ?>" required<?= $decrire('adresse_prestation') ?>>
                <?php $retour('adresse_prestation'); ?>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code_postal_prestation" class="form-label">Code postal</label>
                    <input class="form-control<?= $classe('code_postal_prestation') ?>" type="text" id="code_postal_prestation" name="code_postal_prestation"
                           inputmode="numeric" value="<?= $this->e($old['code_postal_prestation'] ?? '') ?>" required<?= $decrire('code_postal_prestation') ?>>
                    <?php $retour('code_postal_prestation'); ?>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="ville_prestation" class="form-label">Ville</label>
                    <input class="form-control<?= $classe('ville_prestation') ?>" type="text" id="ville_prestation" name="ville_prestation"
                           value="<?= $this->e($old['ville_prestation'] ?? '') ?>" required<?= $decrire('ville_prestation') ?>>
                    <?php $retour('ville_prestation'); ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary btn-lg">Voir le récapitulatif</button>
                <a class="btn btn-outline-primary btn-lg" href="/mon-espace/commandes/<?= $numero ?>">Annuler les modifications</a>
            </div>
        </form>
    </div>
</div>
<script src="/assets/js/commande.js" defer></script>
