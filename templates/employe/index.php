<?php
use App\Core\Money;
use App\Service\OrderStatus;
use App\Service\Pricing;

$requete = static fn (array $extra): string => '/employe?' . http_build_query(array_filter(
    array_merge(['statut' => $statut, 'q' => $q], $extra), static fn ($v): bool => $v !== '' && $v !== null && $v !== 1
));
?>
<div class="container py-5">
    <h1>Espace employé</h1>
    <p class="lead">Bonjour <?= $this->e($user['prenom'] ?? $user['email']) ?>. Retrouvez ici les commandes à traiter.</p>

    <?php $this->partial('employe/_nav', ['actif' => 'commandes', 'avisEnAttente' => $avisEnAttente ?? 0]); ?>

    <section aria-labelledby="titre-synthese" class="mb-4">
        <h2 id="titre-synthese" class="h4">Synthèse</h2>
        <ul class="list-inline synthese">
            <?php foreach (OrderStatus::LABELS as $code => $libelle): ?>
                <li class="list-inline-item">
                    <a href="/employe?statut=<?= $this->e($code) ?>"><span class="statut statut-<?= $this->e($code) ?>"><?= $this->e($libelle) ?></span>
                        <strong><?= (int) ($compte[$code] ?? 0) ?></strong></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section aria-labelledby="titre-filtres" class="mb-4">
        <h2 id="titre-filtres" class="h4">Filtrer les commandes</h2>
        <form method="get" action="/employe" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="statut" class="form-label">Statut</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">Tous les statuts</option>
                    <?php foreach (OrderStatus::LABELS as $code => $libelle): ?>
                        <option value="<?= $this->e($code) ?>"<?= $statut === $code ? ' selected' : '' ?>><?= $this->e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="q" class="form-label">Client ou numéro de commande</label>
                <input class="form-control" type="search" id="q" name="q" maxlength="100" value="<?= $this->e($q) ?>"
                       aria-describedby="aide-q">
                <div id="aide-q" class="form-text">Nom, prénom, e-mail ou numéro (par exemple VG-2026-000012).</div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a class="btn btn-outline-primary" href="/employe">Réinitialiser</a>
            </div>
        </form>
    </section>

    <section aria-labelledby="titre-commandes">
        <h2 id="titre-commandes" class="h4">Commandes</h2>
        <p class="fw-bold" role="status"><?= $total === 0 ? 'Aucune commande trouvée' : ($total === 1 ? '1 commande trouvée' : $total . ' commandes trouvées') ?></p>

        <?php if ($lignes !== []): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden">Commandes : à traiter en premier, triées par date de prestation</caption>
                    <thead>
                        <tr>
                            <th scope="col">Commande</th>
                            <th scope="col">Client</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Prestation</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <th scope="row"><a href="/employe/commandes/<?= $this->e($l['numero_commande']) ?>"><?= $this->e($l['numero_commande']) ?></a></th>
                                <td><?= $this->e(trim(($l['prenom'] ?? '') . ' ' . ($l['nom'] ?? ''))) ?><span class="d-block small text-muted"><?= $this->e($l['email']) ?></span></td>
                                <td><?= $this->e($l['menu_titre']) ?><span class="d-block small text-muted"><?= (int) $l['nombre_personne'] ?> personnes</span></td>
                                <td><?= $this->e((new DateTimeImmutable($l['date_prestation']))->format('d/m/Y')) ?> à <?= $this->e(substr((string) $l['heure_livraison'], 0, 5)) ?></td>
                                <td class="text-end"><?= Money::format(Pricing::toCents($l['prix_total'])) ?></td>
                                <td><span class="statut statut-<?= $this->e($l['statut']) ?>"><?= $this->e(OrderStatus::label($l['statut'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav aria-label="Pagination des commandes">
                    <ul class="pagination">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                            <?php if ($page > 1): ?><a class="page-link" href="<?= $this->e($requete(['page' => $page - 1])) ?>">Précédent</a>
                            <?php else: ?><span class="page-link">Précédent</span><?php endif; ?>
                        </li>
                        <li class="page-item active" aria-current="page"><span class="page-link">Page <?= $page ?> sur <?= $pages ?></span></li>
                        <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
                            <?php if ($page < $pages): ?><a class="page-link" href="<?= $this->e($requete(['page' => $page + 1])) ?>">Suivant</a>
                            <?php else: ?><span class="page-link">Suivant</span><?php endif; ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
