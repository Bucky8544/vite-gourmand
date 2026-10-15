<?php
$tel = $profil ? trim(chunk_split((string) $profil['telephone'], 2, ' ')) : '';
?>
<div class="container py-5">
    <h1>Mon espace</h1>
    <p class="lead">Bonjour <?= $this->e($profil['prenom'] ?? '') ?>. Retrouvez ici vos commandes et vos informations personnelles.</p>

    <div class="row g-4">
        <div class="col-lg-4">
            <section class="carte-espace h-100" aria-labelledby="titre-infos">
                <h2 id="titre-infos" class="h4">Mes informations</h2>
                <?php if ($profil): ?>
                    <dl class="mb-3">
                        <dt>Nom</dt><dd><?= $this->e($profil['prenom'] . ' ' . $profil['nom']) ?></dd>
                        <dt>E-mail</dt><dd><?= $this->e($profil['email']) ?></dd>
                        <dt>GSM</dt><dd><?= $this->e($tel) ?></dd>
                        <dt>Adresse</dt>
                        <dd><?= $this->e($profil['adresse_postale']) ?>, <?= $this->e($profil['code_postal']) ?> <?= $this->e($profil['ville']) ?></dd>
                    </dl>
                <?php endif; ?>
                <a class="btn btn-outline-primary" href="/mon-espace/profil">Modifier mes informations</a>
            </section>
        </div>

        <section class="col-lg-8" aria-labelledby="titre-commandes">
            <h2 id="titre-commandes" class="h4">Mes commandes</h2>
            <?php if ($commandes === []): ?>
                <p>Vous n'avez pas encore passé de commande. <a href="/menus">Découvrir nos menus</a></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <caption class="visually-hidden">Liste de vos commandes, de la plus récente à la plus ancienne</caption>
                        <thead>
                            <tr>
                                <th scope="col">Commande</th>
                                <th scope="col">Menu</th>
                                <th scope="col">Prestation</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $c): ?>
                                <tr>
                                    <th scope="row"><a href="/mon-espace/commandes/<?= $this->e($c['numero_commande']) ?>"><?= $this->e($c['numero_commande']) ?></a></th>
                                    <td><?= $this->e($c['menu_titre']) ?><span class="d-block small text-muted"><?= (int) $c['nombre_personne'] ?> personnes</span></td>
                                    <td><?= $this->e((new DateTimeImmutable($c['date_prestation']))->format('d/m/Y')) ?></td>
                                    <td class="text-end"><?= \App\Core\Money::format(\App\Service\Pricing::toCents($c['prix_total'])) ?></td>
                                    <td><span class="statut statut-<?= $this->e($c['statut']) ?>"><?= $this->e(\App\Service\OrderStatus::label($c['statut'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
