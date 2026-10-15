<?php
$errors = $errors ?? [];
$minimum = (int) $menu['nombre_personne_minimum'];
$date = (new DateTimeImmutable($in['date_prestation']))->format('d/m/Y');
$km = number_format($distance['km10'] / 10, 1, ',', "\u{202F}");
$champsCaches = ['menu_id', 'personnes', 'date_prestation', 'heure_livraison', 'adresse_prestation', 'code_postal_prestation', 'ville_prestation'];
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <h1>Récapitulatif de votre commande</h1>
        <p>Étape 2 sur 2 : vérifiez le détail du prix et les conditions avant de confirmer.</p>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-info" role="alert"><?= $this->e($notice) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h2 class="h4">Votre prestation</h2>
        <dl class="row">
            <dt class="col-sm-4">Menu</dt><dd class="col-sm-8"><?= $this->e($menu['titre']) ?></dd>
            <dt class="col-sm-4">Nombre de personnes</dt><dd class="col-sm-8"><?= (int) $in['personnes'] ?></dd>
            <dt class="col-sm-4">Date et heure</dt><dd class="col-sm-8"><?= $this->e($date) ?> à <?= $this->e($in['heure_livraison']) ?></dd>
            <dt class="col-sm-4">Lieu</dt>
            <dd class="col-sm-8"><?= $this->e($in['adresse_prestation']) ?>, <?= $this->e($in['code_postal_prestation']) ?> <?= $this->e($in['ville_prestation']) ?></dd>
        </dl>

        <h2 class="h4">Détail du prix</h2>
        <table class="table devis">
            <caption class="visually-hidden">Détail du prix de la commande</caption>
            <tbody>
                <tr>
                    <th scope="row">Menu (<?= (int) $in['personnes'] ?> personnes)</th>
                    <td class="text-end"><?= \App\Core\Money::format($devis['brut']) ?></td>
                </tr>
                <?php if ($devis['remise'] > 0): ?>
                    <tr>
                        <th scope="row">Remise de <?= \App\Service\Pricing::REMISE_POURCENT ?> % (à partir de <?= \App\Service\Pricing::seuilRemise($minimum) ?> personnes)</th>
                        <td class="text-end">&minus;<?= \App\Core\Money::format($devis['remise']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <?php if ($distance['bordeaux']): ?>
                        <th scope="row">Livraison à Bordeaux</th>
                        <td class="text-end">Offerte</td>
                    <?php else: ?>
                        <th scope="row">
                            Livraison hors Bordeaux : 5,00&nbsp;€ + 0,59&nbsp;€ × <?= $this->e($km) ?>&nbsp;km
                            <?= $distance['estime'] ? '<span class="d-block fw-normal">(distance estimée)</span>' : '' ?>
                        </th>
                        <td class="text-end"><?= \App\Core\Money::format($devis['livraison']) ?></td>
                    <?php endif; ?>
                </tr>
            </tbody>
            <tfoot>
                <tr class="devis-total">
                    <th scope="row">Total</th>
                    <td class="text-end"><?= \App\Core\Money::format($devis['total']) ?></td>
                </tr>
            </tfoot>
        </table>

        <section class="conditions mb-4" aria-labelledby="titre-conditions">
            <h2 id="titre-conditions" class="conditions-titre">
                <span class="conditions-icone" aria-hidden="true">!</span>
                Conditions de ce menu
            </h2>
            <p><?= $this->e($menu['conditions']) ?></p>
        </section>

        <form method="post" action="/commande">
            <?= \App\Core\Csrf::field() ?>
            <?php foreach ($champsCaches as $champ): ?>
                <input type="hidden" name="<?= $champ ?>" value="<?= $this->e((string) $in[$champ]) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="total_centimes" value="<?= (int) $devis['total'] ?>">

            <div class="form-check mb-4">
                <input class="form-check-input<?= isset($errors['conditions']) ? ' is-invalid' : '' ?>" type="checkbox" id="conditions" name="conditions" value="1" required
                       <?= isset($errors['conditions']) ? 'aria-invalid="true" aria-describedby="conditions-erreur"' : '' ?>>
                <label class="form-check-label" for="conditions">
                    J'ai lu et j'accepte les conditions de ce menu ci-dessus et les <a href="/cgv">conditions générales de vente</a>.
                </label>
                <?php if (isset($errors['conditions'])): ?>
                    <div id="conditions-erreur" class="invalid-feedback d-block"><?= $this->e($errors['conditions']) ?></div>
                <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary btn-lg">Confirmer ma commande</button>
                <button type="submit" class="btn btn-outline-primary btn-lg" formaction="/commande/modifier" formnovalidate>Modifier ma commande</button>
            </div>
        </form>
    </div>
</div>
