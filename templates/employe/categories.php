<?php
$sections = [
    'theme' => ['Thèmes', $themes, 'thème', 'Noël, Pâques, classique, événement…'],
    'regime' => ['Régimes', $regimes, 'régime', 'Classique, végétarien, vegan, sans gluten…'],
];
?>
<div class="container py-5">
    <h1>Thèmes et régimes</h1>
    <?php $this->partial('employe/_nav', ['actif' => 'categories']); ?>
    <p class="lead">Ces catégories servent à classer les menus et à les filtrer sur le site. Un élément utilisé par des menus ne peut pas être supprimé.</p>

    <?php foreach ($sections as $type => [$titre, $lignes, $singulier, $exemples]): ?>
        <section aria-labelledby="titre-<?= $this->e($type) ?>" class="mb-5">
            <h2 id="titre-<?= $this->e($type) ?>" class="h4"><?= $this->e($titre) ?></h2>

            <form method="post" action="/employe/categories/<?= $this->e($type) ?>/ajouter" class="row g-2 align-items-end mb-3">
                <?= \App\Core\Csrf::field() ?>
                <div class="col-md-6">
                    <label for="nouveau-<?= $this->e($type) ?>" class="form-label">Ajouter un <?= $this->e($singulier) ?></label>
                    <input class="form-control" type="text" id="nouveau-<?= $this->e($type) ?>" name="libelle" maxlength="50" required aria-describedby="aide-<?= $this->e($type) ?>">
                    <div id="aide-<?= $this->e($type) ?>" class="form-text"><?= $this->e($exemples) ?></div>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">Ajouter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden"><?= $this->e($titre) ?></caption>
                    <thead><tr><th scope="col">Nom</th><th scope="col">Menus</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td>
                                    <form method="post" action="/employe/categories/<?= $this->e($type) ?>/<?= (int) $l['id'] ?>/renommer" class="d-flex gap-2">
                                        <?= \App\Core\Csrf::field() ?>
                                        <label for="nom-<?= $this->e($type) ?>-<?= (int) $l['id'] ?>" class="visually-hidden">Nom du <?= $this->e($singulier) ?> « <?= $this->e($l['libelle']) ?> »</label>
                                        <input class="form-control" type="text" id="nom-<?= $this->e($type) ?>-<?= (int) $l['id'] ?>" name="libelle" maxlength="50" required value="<?= $this->e($l['libelle']) ?>">
                                        <button type="submit" class="btn btn-outline-primary">Renommer<span class="visually-hidden"> <?= $this->e($l['libelle']) ?></span></button>
                                    </form>
                                </td>
                                <td><?= (int) $l['nb_menus'] === 0 ? 'Aucun' : (int) $l['nb_menus'] ?></td>
                                <td>
                                    <?php if ((int) $l['nb_menus'] === 0): ?>
                                        <form method="post" action="/employe/categories/<?= $this->e($type) ?>/<?= (int) $l['id'] ?>/supprimer">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-outline-primary">Supprimer<span class="visually-hidden"> <?= $this->e($l['libelle']) ?></span></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Utilisé : suppression impossible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>
