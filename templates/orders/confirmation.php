<?php
$c = $commande;
$centimes = static fn (string $euros): int => \App\Service\Pricing::toCents($euros);
$date = (new DateTimeImmutable($c['date_prestation']))->format('d/m/Y');
$heure = substr((string) $c['heure_livraison'], 0, 5);
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <h1>Merci, votre commande est enregistrée</h1>
        <p class="lead">Commande n° <strong><?= $this->e($c['numero_commande']) ?></strong>. Elle est <strong>en attente de validation</strong> par notre équipe.</p>
        <p>Un e-mail de confirmation vous a été envoyé. Vous pourrez modifier ou annuler votre commande depuis votre espace tant qu'elle n'a pas été acceptée.</p>

        <h2 class="h4 mt-4">Votre prestation</h2>
        <dl class="row">
            <dt class="col-sm-4">Menu</dt><dd class="col-sm-8"><?= $this->e($c['menu_titre']) ?></dd>
            <dt class="col-sm-4">Nombre de personnes</dt><dd class="col-sm-8"><?= (int) $c['nombre_personne'] ?></dd>
            <dt class="col-sm-4">Date et heure</dt><dd class="col-sm-8"><?= $this->e($date) ?> à <?= $this->e($heure) ?></dd>
            <dt class="col-sm-4">Lieu</dt>
            <dd class="col-sm-8"><?= $this->e($c['adresse_prestation']) ?>, <?= $this->e($c['code_postal_prestation']) ?> <?= $this->e($c['ville_prestation']) ?></dd>
        </dl>

        <table class="table devis">
            <caption class="visually-hidden">Prix de la commande</caption>
            <tbody>
                <tr><th scope="row">Menu</th><td class="text-end"><?= \App\Core\Money::format($centimes($c['prix_menu']) + $centimes($c['remise'])) ?></td></tr>
                <?php if ($centimes($c['remise']) > 0): ?>
                    <tr><th scope="row">Remise</th><td class="text-end">&minus;<?= \App\Core\Money::format($centimes($c['remise'])) ?></td></tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Livraison</th>
                    <td class="text-end"><?= $centimes($c['prix_livraison']) === 0 ? 'Offerte' : \App\Core\Money::format($centimes($c['prix_livraison'])) ?></td>
                </tr>
            </tbody>
            <tfoot><tr class="devis-total"><th scope="row">Total</th><td class="text-end"><?= \App\Core\Money::format($centimes($c['prix_total'])) ?></td></tr></tfoot>
        </table>

        <p><a class="btn btn-primary" href="/mon-espace">Aller à mon espace</a></p>
    </div>
</div>
