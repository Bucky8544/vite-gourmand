<?php
use App\Core\Money;

$c = $commande;
$numero = $this->e($c['numero_commande']);
$date = (new DateTimeImmutable($in['date_prestation']))->format('d/m/Y');
$champsCaches = ['personnes', 'date_prestation', 'heure_livraison', 'adresse_prestation', 'code_postal_prestation', 'ville_prestation'];
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <h1>Récapitulatif de la modification</h1>
        <p>Commande <strong><?= $numero ?></strong> : vérifiez le nouveau prix avant d'enregistrer.</p>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-info" role="alert"><?= $this->e($notice) ?></div>
        <?php endif; ?>

        <h2 class="h4">Votre prestation</h2>
        <dl class="row">
            <dt class="col-sm-4">Menu</dt><dd class="col-sm-8"><?= $this->e($c['menu_titre']) ?></dd>
            <dt class="col-sm-4">Nombre de personnes</dt><dd class="col-sm-8"><?= (int) $in['personnes'] ?></dd>
            <dt class="col-sm-4">Date et heure</dt><dd class="col-sm-8"><?= $this->e($date) ?> à <?= $this->e($in['heure_livraison']) ?></dd>
            <dt class="col-sm-4">Lieu</dt>
            <dd class="col-sm-8"><?= $this->e($in['adresse_prestation']) ?>, <?= $this->e($in['code_postal_prestation']) ?> <?= $this->e($in['ville_prestation']) ?></dd>
        </dl>

        <h2 class="h4">Nouveau prix</h2>
        <?php $this->partial('orders/_devis', [
            'devis' => $devis, 'distance' => $distance, 'personnes' => $in['personnes'],
            'minimum' => (int) $c['nombre_personne_minimum'],
        ]); ?>
        <p>Total actuel de la commande : <?= Money::format($ancienTotal) ?>.
            <?= $devis['total'] === $ancienTotal ? 'Le total ne change pas.' : 'Nouveau total : <strong>' . Money::format($devis['total']) . '</strong>.' ?></p>

        <form method="post" action="/mon-espace/commandes/<?= $numero ?>/modifier/confirmer">
            <?= \App\Core\Csrf::field() ?>
            <?php foreach ($champsCaches as $champ): ?>
                <input type="hidden" name="<?= $champ ?>" value="<?= $this->e((string) $in[$champ]) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="total_centimes" value="<?= (int) $devis['total'] ?>">

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary btn-lg">Enregistrer les modifications</button>
                <button type="submit" class="btn btn-outline-primary btn-lg" formaction="/mon-espace/commandes/<?= $numero ?>/modifier/retour" formnovalidate>Modifier encore</button>
            </div>
        </form>
    </div>
</div>
