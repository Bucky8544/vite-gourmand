<?php
$types = ['entree' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts'];
$parType = ['entree' => [], 'plat' => [], 'dessert' => []];
foreach ($plats as $p) {
    $parType[$p['type']][] = $p;
}
?>
<div class="container py-5">
    <h1>Plats</h1>
    <?php $this->partial('employe/_nav', ['actif' => 'plats']); ?>

    <p><a class="btn btn-primary" href="/employe/plats/nouveau">Créer un plat</a></p>

    <?php if ($plats === []): ?>
        <p>Aucun plat pour le moment.</p>
    <?php endif; ?>

    <?php foreach ($types as $type => $libelle): ?>
        <?php if ($parType[$type] === []) { continue; } ?>
        <section aria-labelledby="titre-<?= $this->e($type) ?>" class="mb-4">
            <h2 id="titre-<?= $this->e($type) ?>" class="h4"><?= $this->e($libelle) ?></h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden"><?= $this->e($libelle) ?></caption>
                    <thead>
                        <tr><th scope="col">Plat</th><th scope="col">Allergènes</th><th scope="col">Utilisé dans</th><th scope="col">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parType[$type] as $p): ?>
                            <tr>
                                <th scope="row"><?= $this->e($p['titre']) ?></th>
                                <td><?= $p['allergenes'] === [] ? 'Aucun déclaré' : $this->e(implode(', ', $p['allergenes'])) ?></td>
                                <td><?= $p['menus'] === [] ? 'Aucun menu' : $this->e(implode(', ', array_column($p['menus'], 'titre'))) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="/employe/plats/<?= (int) $p['id'] ?>">Modifier<span class="visually-hidden"> <?= $this->e($p['titre']) ?></span></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>
