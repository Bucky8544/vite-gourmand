<?php /* Détail du prix : $devis, $distance, $personnes, $minimum */ ?>
<table class="table devis">
    <caption class="visually-hidden">Détail du prix de la commande</caption>
    <tbody>
        <tr>
            <th scope="row">Menu (<?= (int) $personnes ?> personnes)</th>
            <td class="text-end"><?= \App\Core\Money::format($devis['brut']) ?></td>
        </tr>
        <?php if ($devis['remise'] > 0): ?>
            <tr>
                <th scope="row">Remise de <?= \App\Service\Pricing::REMISE_POURCENT ?> % (à partir de <?= \App\Service\Pricing::seuilRemise((int) $minimum) ?> personnes)</th>
                <td class="text-end">&minus;<?= \App\Core\Money::format($devis['remise']) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <?php if ($distance['bordeaux']): ?>
                <th scope="row">Livraison à Bordeaux</th>
                <td class="text-end">Offerte</td>
            <?php else: ?>
                <th scope="row">
                    Livraison hors Bordeaux : 5,00&nbsp;€ + 0,59&nbsp;€ × <?= $this->e(number_format($distance['km10'] / 10, 1, ',', "\u{202F}")) ?>&nbsp;km
                    <?= $distance['estime'] ? '<span class="d-block fw-normal">(distance estimée)</span>' : '' ?>
                </th>
                <td class="text-end"><?= \App\Core\Money::format($devis['livraison']) ?></td>
            <?php endif; ?>
        </tr>
    </tbody>
    <tfoot>
        <tr class="devis-total"><th scope="row">Total</th><td class="text-end"><?= \App\Core\Money::format($devis['total']) ?></td></tr>
    </tfoot>
</table>
