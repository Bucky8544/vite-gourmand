<?php
$nb = (int) $menu['nombre_personne_minimum'];
$stock = (int) $menu['stock'];
$libelles = [
    'entree' => ['Entrée', 'Entrées'],
    'plat' => ['Plat', 'Plats'],
    'dessert' => ['Dessert', 'Desserts'],
];
$aDesPlats = ($composition['entree'] ?? []) !== [] || ($composition['plat'] ?? []) !== [] || ($composition['dessert'] ?? []) !== [];
?>
<div class="container py-4">
    <nav aria-label="Fil d'Ariane" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
            <li class="breadcrumb-item"><a href="/menus">Nos menus</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $this->e($menu['titre']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-lg-6">
            <?php $this->partial('menus/_galerie', ['images' => $images]); ?>
        </div>

        <div class="col-lg-6">
            <p class="mb-2">
                <span class="pastille">Thème : <?= $this->e($menu['theme']) ?></span>
                <span class="pastille">Régime : <?= $this->e($menu['regime']) ?></span>
            </p>
            <h1><?= $this->e($menu['titre']) ?></h1>
            <p class="lead"><?= $this->e($menu['description']) ?></p>

            <p class="menu-prix menu-prix-detail mb-1">
                <span class="prix"><?= $this->price($menu['prix_minimum']) ?></span>
                pour <?= $nb ?> personnes minimum
            </p>
            <p class="remise-info">Remise de 10 % pour toute commande d'au moins <?= $nb + 5 ?> personnes.</p>

            <p class="stock">
                <?php if ($stock > 0): ?>
                    <span class="stock-point" aria-hidden="true"></span>
                    <?= $stock === 1 ? "Plus qu'une commande possible" : 'Plus que ' . $stock . ' commandes possibles' ?>
                <?php else: ?>
                    Menu momentanément indisponible
                <?php endif; ?>
            </p>

            <section class="conditions" aria-labelledby="titre-conditions">
                <h2 id="titre-conditions" class="conditions-titre">
                    <span class="conditions-icone" aria-hidden="true">!</span>
                    Conditions de ce menu
                </h2>
                <p><?= $this->e($menu['conditions']) ?></p>
            </section>

            <div class="mt-4">
                <?php if (!$peutCommander): ?>
                    <p class="form-text mb-0">Les comptes employé et administrateur ne passent pas de commande.</p>
                <?php elseif ($stock > 0): ?>
                    <a class="btn btn-primary btn-lg w-100" href="/commande?menu=<?= (int) $menu['id'] ?>">Commander ce menu</a>
                    <?php if ($user === null): ?>
                        <p class="form-text mt-2 mb-0">Visiteur : vous serez invité à vous connecter ou à créer un compte avant de commander.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button" class="btn btn-primary btn-lg w-100" disabled aria-describedby="stock-epuise">Commander ce menu</button>
                    <p id="stock-epuise" class="form-text mt-2 mb-0">Ce menu n'est plus disponible pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="mt-5" aria-labelledby="titre-composition">
        <h2 id="titre-composition" class="mb-4">Composition du menu</h2>

        <?php if (!$aDesPlats): ?>
            <p>La composition de ce menu sera bientôt disponible.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($libelles as $type => $noms): ?>
                    <?php $plats = $composition[$type] ?? []; ?>
                    <?php if ($plats === []) { continue; } ?>
                    <div class="col-md-4">
                        <section class="composition-carte h-100" aria-labelledby="composition-<?= $this->e($type) ?>">
                            <h3 id="composition-<?= $this->e($type) ?>"><?= count($plats) > 1 ? $noms[1] : $noms[0] ?></h3>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($plats as $plat): ?>
                                    <li class="plat">
                                        <span class="plat-nom"><?= $this->e($plat['titre']) ?></span>
                                        <span class="plat-allergenes">Allergènes :
                                            <?= $plat['allergenes'] === [] ? 'aucun allergène déclaré' : $this->e(implode(', ', $plat['allergenes'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="mt-4 mb-1">
                <strong>Allergènes présents dans l'ensemble du menu :</strong>
                <?= $allergenes === [] ? 'aucun allergène déclaré' : $this->e(implode(', ', $allergenes)) ?>
            </p>
            <p class="form-text">En cas d'allergie, <a href="/contact">contactez-nous</a> avant de commander.</p>
        <?php endif; ?>
    </section>
</div>
<script src="/assets/js/galerie.js" defer></script>
