<?php
$c = $commande;
$numero = $this->e($c['numero_commande']);
?>
<div class="container py-5">
    <div class="col-lg-7 mx-auto">
        <h1>Annuler la commande <?= $numero ?> ?</h1>
        <p>Vous êtes sur le point d'annuler votre commande du menu <strong><?= $this->e($c['menu_titre']) ?></strong>
           pour le <?= $this->e((new DateTimeImmutable($c['date_prestation']))->format('d/m/Y')) ?>
           (<?= (int) $c['nombre_personne'] ?> personnes, <?= \App\Core\Money::format(\App\Service\Pricing::toCents($c['prix_total'])) ?>).</p>
        <p>Cette action est définitive : si vous souhaitez de nouveau ce menu, il faudra passer une nouvelle commande.</p>

        <form method="post" action="/mon-espace/commandes/<?= $numero ?>/annuler" class="d-flex flex-wrap gap-3">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-primary btn-lg">Oui, annuler ma commande</button>
            <a class="btn btn-outline-primary btn-lg" href="/mon-espace/commandes/<?= $numero ?>">Non, conserver ma commande</a>
        </form>
    </div>
</div>
