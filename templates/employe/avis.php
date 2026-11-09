<?php
use App\Repository\ReviewRepository;

$formatDate = static function (?string $dateHeure): string {
    if ($dateHeure === null) {
        return '';
    }
    $d = new DateTimeImmutable($dateHeure);

    return $d->format('d/m/Y') . ' à ' . $d->format('H:i');
};
$libellesStatut = ['en_attente' => 'En attente de validation', 'valide' => 'Validé (publié sur la page d\'accueil)', 'refuse' => 'Refusé (non publié)'];
?>
<div class="container py-5">
    <h1>Avis clients</h1>
    <p class="lead">Les avis validés apparaissent sur la page d'accueil, avec le prénom du client et l'initiale de son nom.</p>

    <?php $this->partial('employe/_nav', ['actif' => 'avis', 'avisEnAttente' => $compte['en_attente'] ?? 0]); ?>

    <nav aria-label="Statut des avis" class="mb-4">
        <ul class="list-inline mb-0">
            <?php foreach (ReviewRepository::STATUTS as $code => $libelle): ?>
                <li class="list-inline-item">
                    <a class="btn btn-sm <?= $statut === $code ? 'btn-primary' : 'btn-outline-primary' ?>" href="/employe/avis?statut=<?= $this->e($code) ?>"<?= $statut === $code ? ' aria-current="page"' : '' ?>>
                        <?= $this->e($libelle) ?> (<?= (int) ($compte[$code] ?? 0) ?>)
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <section aria-labelledby="titre-liste">
        <h2 id="titre-liste" class="h4"><?= $this->e(ReviewRepository::STATUTS[$statut]) ?></h2>
        <p class="fw-bold" role="status"><?= $total === 0 ? 'Aucun avis dans cette catégorie' : ($total === 1 ? '1 avis' : $total . ' avis') ?></p>

        <?php foreach ($lignes as $a): ?>
            <?php $auteur = ReviewRepository::auteur($a['prenom'], $a['nom']); ?>
            <article class="carte-espace mb-3" aria-labelledby="avis-<?= (int) $a['id'] ?>">
                <h3 id="avis-<?= (int) $a['id'] ?>" class="h5">
                    Note : <?= (int) $a['note'] ?> sur 5
                    <span class="statut statut-<?= $a['statut'] === 'valide' ? 'accepte' : ($a['statut'] === 'refuse' ? 'annulee' : 'en_attente') ?> ms-2"><?= $this->e($libellesStatut[$a['statut']] ?? $a['statut']) ?></span>
                </h3>
                <p class="mb-2"><?= nl2br($this->e($a['commentaire'])) ?></p>
                <p class="small text-muted mb-3">
                    <?= $this->e(trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''))) ?> (<?= $this->e($a['email']) ?>),
                    commande <a href="/employe/commandes/<?= $this->e($a['numero_commande']) ?>"><?= $this->e($a['numero_commande']) ?></a>
                    (<?= $this->e($a['menu_titre']) ?>), avis déposé le <?= $this->e($formatDate($a['created_at'])) ?>.
                    <span class="d-block">Affiché sur le site sous le nom : <strong><?= $this->e($auteur) ?></strong></span>
                    <?php if ($a['modere_at'] !== null): ?>
                        <span class="d-block">Dernière décision le <?= $this->e($formatDate($a['modere_at'])) ?><?= $a['moderateur_email'] !== null ? ' par ' . $this->e(trim(($a['moderateur_prenom'] ?? '') . ' ' . ($a['moderateur_nom'] ?? '')) ?: $a['moderateur_email']) : '' ?>.</span>
                    <?php endif; ?>
                </p>

                <form method="post" action="/employe/avis/<?= (int) $a['id'] ?>/decision" class="d-flex flex-wrap gap-2">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="onglet" value="<?= $this->e($statut) ?>">
                    <?php if ($a['statut'] !== 'valide'): ?>
                        <button type="submit" class="btn btn-primary" name="decision" value="valide">
                            <?= $a['statut'] === 'refuse' ? 'Publier' : 'Valider' ?><span class="visually-hidden"> l'avis de <?= $this->e($auteur) ?></span>
                        </button>
                    <?php endif; ?>
                    <?php if ($a['statut'] !== 'refuse'): ?>
                        <button type="submit" class="btn btn-outline-primary" name="decision" value="refuse">
                            <?= $a['statut'] === 'valide' ? 'Retirer' : 'Refuser' ?><span class="visually-hidden"> l'avis de <?= $this->e($auteur) ?></span>
                        </button>
                    <?php endif; ?>
                </form>
            </article>
        <?php endforeach; ?>

        <?php if ($pages > 1): ?>
            <nav aria-label="Pagination des avis">
                <ul class="pagination">
                    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                        <?php if ($page > 1): ?><a class="page-link" href="/employe/avis?statut=<?= $this->e($statut) ?>&amp;page=<?= $page - 1 ?>">Précédent</a>
                        <?php else: ?><span class="page-link">Précédent</span><?php endif; ?>
                    </li>
                    <li class="page-item active" aria-current="page"><span class="page-link">Page <?= $page ?> sur <?= $pages ?></span></li>
                    <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
                        <?php if ($page < $pages): ?><a class="page-link" href="/employe/avis?statut=<?= $this->e($statut) ?>&amp;page=<?= $page + 1 ?>">Suivant</a>
                        <?php else: ?><span class="page-link">Suivant</span><?php endif; ?>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </section>
</div>
