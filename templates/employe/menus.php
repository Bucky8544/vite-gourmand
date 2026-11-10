<div class="container py-5">
    <h1>Menus</h1>
    <?php $this->partial('employe/_nav', ['actif' => 'menus']); ?>

    <p><a class="btn btn-primary" href="/employe/menus/nouveau">Créer un menu</a></p>

    <?php if ($menus === []): ?>
        <p>Aucun menu pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Tous les menus, actifs et désactivés</caption>
                <thead>
                    <tr>
                        <th scope="col">Menu</th>
                        <th scope="col">Thème et régime</th>
                        <th scope="col">Minimum et prix</th>
                        <th scope="col" class="text-end">Stock</th>
                        <th scope="col">Commandes</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menus as $m): ?>
                        <?php $actif = (int) $m['actif'] === 1; ?>
                        <tr>
                            <th scope="row">
                                <a href="/employe/menus/<?= (int) $m['id'] ?>"><?= $this->e($m['titre']) ?></a>
                                <span class="d-block"><span class="statut statut-<?= $actif ? 'accepte' : 'annulee' ?>"><?= $actif ? 'Visible sur le site' : 'Désactivé' ?></span></span>
                            </th>
                            <td><?= $this->e($m['theme']) ?><span class="d-block small text-muted"><?= $this->e($m['regime']) ?></span></td>
                            <td><?= $this->price($m['prix_minimum']) ?><span class="d-block small text-muted">pour <?= (int) $m['nombre_personne_minimum'] ?> personnes minimum</span></td>
                            <td class="text-end"><?= (int) $m['stock'] ?></td>
                            <td><?= (int) $m['nb_en_cours'] ?> en cours<span class="d-block small text-muted"><?= (int) $m['nb_commandes'] ?> au total</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="/employe/menus/<?= (int) $m['id'] ?>">Modifier<span class="visually-hidden"> <?= $this->e($m['titre']) ?></span></a>
                                    <?php if ($actif): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="/menus/<?= (int) $m['id'] ?>">Voir sur le site<span class="visually-hidden"> : <?= $this->e($m['titre']) ?></span></a>
                                    <?php endif; ?>
                                    <form method="post" action="/employe/menus/<?= (int) $m['id'] ?>/actif">
                                        <?= \App\Core\Csrf::field() ?>
                                        <input type="hidden" name="actif" value="<?= $actif ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?= $actif ? 'Désactiver' : 'Réactiver' ?><span class="visually-hidden"> <?= $this->e($m['titre']) ?></span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
