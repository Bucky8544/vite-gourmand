<?php
$old = $old ?? [];
$errors = $errors ?? [];
$n = count($menus);
$compteur = $n === 0 ? 'Aucun menu trouvé' : ($n === 1 ? '1 menu trouvé' : "$n menus trouvés");
$nbActifs = count(array_filter($filters, static fn ($v): bool => $v !== null));
$panneauOuvert = $nbActifs > 0 || $errors !== [];

$nombre = function (string $name, string $label, string $min, string $step, string $classeLabel = '') use ($old, $errors): void {
    $err = $errors[$name] ?? null; ?>
    <div class="mb-3">
        <label for="<?= $name ?>" class="form-label<?= $classeLabel ?>"><?= $this->e($label) ?></label>
        <input class="form-control<?= $err ? ' is-invalid' : '' ?>" type="number" id="<?= $name ?>" name="<?= $name ?>"
               min="<?= $min ?>" step="<?= $step ?>" value="<?= $this->e($old[$name] ?? '') ?>"
               <?= $err ? 'aria-invalid="true" aria-describedby="' . $name . '-erreur"' : '' ?>>
        <?php if ($err): ?><div id="<?= $name ?>-erreur" class="invalid-feedback d-block"><?= $this->e($err) ?></div><?php endif; ?>
    </div>
<?php };

$liste = function (string $name, string $label, array $options, string $tous) use ($old, $errors): void {
    $err = $errors[$name] ?? null; ?>
    <div class="mb-3">
        <label for="<?= $name ?>" class="form-label fw-bold"><?= $this->e($label) ?></label>
        <select class="form-select<?= $err ? ' is-invalid' : '' ?>" id="<?= $name ?>" name="<?= $name ?>"
                <?= $err ? 'aria-invalid="true" aria-describedby="' . $name . '-erreur"' : '' ?>>
            <option value=""><?= $this->e($tous) ?></option>
            <?php foreach ($options as $o): ?>
                <option value="<?= (int) $o['id'] ?>"<?= ($old[$name] ?? '') === (string) $o['id'] ? ' selected' : '' ?>><?= $this->e($o['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($err): ?><div id="<?= $name ?>-erreur" class="invalid-feedback d-block"><?= $this->e($err) ?></div><?php endif; ?>
    </div>
<?php };
?>
<div class="container py-5">
    <h1>Nos menus</h1>
    <p class="lead">Des menus pour Noël, Pâques et tous vos événements. Filtrez pour trouver le vôtre.</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <p class="fw-bold mb-2">Certains filtres n'ont pas été appliqués :</p>
            <ul class="mb-0">
                <?php foreach ($errors as $champ => $message): ?>
                    <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-3">
            <button class="btn btn-outline-primary w-100 d-lg-none mb-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#panneau-filtres" aria-controls="panneau-filtres"
                    aria-expanded="<?= $panneauOuvert ? 'true' : 'false' ?>">
                Filtrer les menus<?= $nbActifs > 0 ? ' (' . $nbActifs . ')' : '' ?>
            </button>

            <div class="collapse d-lg-block<?= $panneauOuvert ? ' show' : '' ?>" id="panneau-filtres">
                <form method="get" action="/menus" class="filtres" id="filtres" aria-labelledby="titre-filtres">
                    <h2 id="titre-filtres" class="filtres-titre">Filtrer les menus</h2>
                    <div class="filtres-corps">
                        <?php $nombre('prix_max', 'Prix maximum (€)', '0', 'any', ' fw-bold'); ?>

                        <fieldset class="mb-3">
                            <legend class="fs-6 fw-bold">Fourchette de prix (€)</legend>
                            <div class="row g-2">
                                <div class="col-6"><?php $nombre('prix_de', 'De', '0', 'any', ' small'); ?></div>
                                <div class="col-6"><?php $nombre('prix_a', 'À', '0', 'any', ' small'); ?></div>
                            </div>
                        </fieldset>

                        <?php $liste('theme', 'Thème', $themes, 'Tous les thèmes'); ?>
                        <?php $liste('regime', 'Régime', $regimes, 'Tous les régimes'); ?>

                        <div class="mb-3">
                            <label for="personnes" class="form-label fw-bold">Nombre de personnes minimum</label>
                            <input class="form-control<?= isset($errors['personnes']) ? ' is-invalid' : '' ?>" type="number" id="personnes"
                                   name="personnes" min="1" step="1" value="<?= $this->e($old['personnes'] ?? '') ?>"
                                   aria-describedby="personnes-aide<?= isset($errors['personnes']) ? ' personnes-erreur' : '' ?>"
                                   <?= isset($errors['personnes']) ? 'aria-invalid="true"' : '' ?>>
                            <div id="personnes-aide" class="form-text">Affiche les menus commandables pour ce nombre d'invités.</div>
                            <?php if (isset($errors['personnes'])): ?>
                                <div id="personnes-erreur" class="invalid-feedback d-block"><?= $this->e($errors['personnes']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                            <a class="btn btn-outline-primary" href="/menus">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <section class="col-lg-9" aria-labelledby="titre-resultats">
            <h2 id="titre-resultats" class="visually-hidden">Résultats</h2>
            <p id="compteur" class="fw-bold" role="status" aria-live="polite"><?= $this->e($compteur) ?></p>

            <div id="zone-resultats">
                <?php if ($n === 0): ?>
                    <p>Aucun menu ne correspond à vos critères. <a href="/menus">Réinitialiser les filtres</a></p>
                <?php else: ?>
                    <ul id="liste-menus" class="row g-4 list-unstyled p-0 m-0">
                        <?php foreach ($menus as $menu): ?>
                            <li class="col-md-6 col-xl-4"><?php $this->partial('menus/_card', ['menu' => $menu]); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
