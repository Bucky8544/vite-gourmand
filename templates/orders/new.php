<div class="container py-5">
    <h1>Commander</h1>
    <p>Vous commandez : <strong><?= $this->e($menu['titre']) ?></strong>
       (<?= $this->price($menu['prix_minimum']) ?> pour <?= (int) $menu['nombre_personne_minimum'] ?> personnes minimum).</p>
    <p class="text-muted">Page provisoire : le formulaire de commande arrive avec la carte « Commande d'un menu ».</p>
    <p><a href="/menus/<?= (int) $menu['id'] ?>">Retour au détail du menu</a></p>
</div>
