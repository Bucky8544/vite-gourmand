<?php
use App\Core\Money;

$errors = $f['errors'];
$nbCommandes = (int) $total['nombre_commandes'];
$ca = (int) $total['chiffre_affaires_centimes'];
$panier = $nbCommandes > 0 ? (int) round($ca / $nbCommandes) : 0;
$filtreActif = $f['menu'] !== null || $f['du'] !== '' || $f['au'] !== '';

$titreMenu = 'Tous les menus';
if ($f['menu'] !== null) {
    $titreMenu = 'Menu n° ' . $f['menu'];
    foreach ($menus as $m) {
        if ((int) $m['id'] === $f['menu']) {
            $titreMenu = '« ' . $m['titre'] . ' »';
        }
    }
}
$totalCommandesTableau = array_sum(array_column($lignes, 'commandes'));
$totalCaTableau = array_sum(array_column($lignes, 'ca_centimes'));
?>
<div class="container py-5">
    <h1>Espace administrateur</h1>
    <p class="lead">Connecté en tant que <?= $this->e($user['email']) ?>.</p>
    <?php $this->partial('admin/_nav', ['actif' => 'statistiques']); ?>

    <section aria-labelledby="titre-filtres" class="mb-4">
        <h2 id="titre-filtres" class="h4">Filtres</h2>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="get" action="/admin" class="row g-3 align-items-end" novalidate>
            <div class="col-md-4">
                <label for="menu" class="form-label">Menu (chiffre d'affaires)</label>
                <select class="form-select<?= isset($errors['menu']) ? ' is-invalid' : '' ?>" id="menu" name="menu">
                    <option value="">Tous les menus</option>
                    <?php foreach ($menus as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"<?= $f['menu'] === (int) $m['id'] ? ' selected' : '' ?>><?= $this->e($m['titre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="du" class="form-label">Du</label>
                <input class="form-control<?= isset($errors['du']) ? ' is-invalid' : '' ?>" type="date" id="du" name="du" value="<?= $this->e($f['du']) ?>">
            </div>
            <div class="col-md-3">
                <label for="au" class="form-label">Au</label>
                <input class="form-control<?= isset($errors['au']) ? ' is-invalid' : '' ?>" type="date" id="au" name="au" value="<?= $this->e($f['au']) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Appliquer</button>
                <a class="btn btn-outline-primary" href="/admin">Réinitialiser</a>
            </div>
        </form>
        <p class="form-text mt-2">La période porte sur la date de la prestation et s'applique aux graphiques et au chiffre d'affaires. Le menu ne filtre que le chiffre d'affaires : les graphiques comparent toujours tous les menus. Seules les commandes acceptées et non annulées sont comptabilisées.</p>
    </section>

    <?php if ($indisponible): ?>
        <div class="alert alert-danger" role="alert">
            Les statistiques sont momentanément indisponibles : la base MongoDB ne répond pas. Les autres fonctions du site ne sont pas touchées. Réessayez dans un instant.
        </div>
    <?php elseif ($errors === []): ?>
        <section aria-labelledby="titre-ca" class="mb-5">
            <h2 id="titre-ca" class="h4">Chiffre d'affaires</h2>
            <p class="text-muted"><?= $this->e($titreMenu) ?> · <?= $this->e($periode) ?> · livraison comprise</p>
            <div class="row g-3">
                <div class="col-md-4"><div class="carte-espace h-100"><p class="mb-1 text-muted">Commandes comptabilisées</p><p class="kpi-valeur mb-0"><?= $nbCommandes ?></p></div></div>
                <div class="col-md-4"><div class="carte-espace h-100"><p class="mb-1 text-muted">Chiffre d'affaires</p><p class="kpi-valeur mb-0"><?= Money::format($ca) ?></p></div></div>
                <div class="col-md-4"><div class="carte-espace h-100"><p class="mb-1 text-muted">Panier moyen</p><p class="kpi-valeur mb-0"><?= Money::format($panier) ?></p></div></div>
            </div>
        </section>

        <?php if ($totalCommandesTableau === 0): ?>
            <div class="alert alert-info" role="status">
                <?= $filtreActif
                    ? 'Aucune commande comptabilisée sur cette période.'
                    : "Aucune commande n'est encore comptabilisée. Si des commandes ont déjà été acceptées, utilisez « Reconstruire les statistiques » en bas de page." ?>
            </div>
        <?php endif; ?>

        <section aria-labelledby="titre-comparaison" class="mb-5">
            <h2 id="titre-comparaison" class="h4">Comparaison des menus</h2>
            <h3 class="h5 mt-4">Nombre de commandes par menu</h3>
            <?= $graphCommandes ?>
            <h3 class="h5 mt-4">Chiffre d'affaires par menu</h3>
            <?= $graphCa ?>

            <h3 class="h5 mt-4">Les mêmes données en tableau</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden">Commandes et chiffre d'affaires par menu, <?= $this->e($periode) ?></caption>
                    <thead>
                        <tr><th scope="col">Menu</th><th scope="col" class="text-end">Commandes</th><th scope="col" class="text-end">Chiffre d'affaires</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <th scope="row"><?= $this->e($l['titre']) ?></th>
                                <td class="text-end"><?= (int) $l['commandes'] ?></td>
                                <td class="text-end"><?= Money::format((int) $l['ca_centimes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="devis-total"><th scope="row">Total</th><td class="text-end"><?= (int) $totalCommandesTableau ?></td><td class="text-end"><?= Money::format((int) $totalCaTableau) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section aria-labelledby="titre-maintenance" class="mt-5">
        <h2 id="titre-maintenance" class="h4">Maintenance des statistiques</h2>
        <p>Les statistiques sont stockées dans MongoDB et mises à jour à chaque acceptation ou annulation de commande. Après une installation neuve ou une panne de MongoDB, vous pouvez les reconstruire à partir des commandes.</p>
        <form method="post" action="/admin/statistiques/resynchroniser">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-outline-primary">Reconstruire les statistiques</button>
        </form>
    </section>
</div>
