<?php
/* Navigation de l'espace employé. $actif : commandes, avis, menus, plats, categories ou horaires. $avisEnAttente est facultatif. */
$enAttente = $avisEnAttente ?? ((new \App\Repository\ReviewRepository())->countByStatus()['en_attente'] ?? 0);
$onglets = [
    'commandes' => ['/employe', 'Commandes'],
    'avis' => ['/employe/avis', 'Avis clients' . ((int) $enAttente > 0 ? ' (' . (int) $enAttente . ' en attente)' : '')],
    'menus' => ['/employe/menus', 'Menus'],
    'plats' => ['/employe/plats', 'Plats'],
    'categories' => ['/employe/categories', 'Thèmes et régimes'],
    'horaires' => ['/employe/horaires', 'Horaires'],
];
?>
<nav aria-label="Sections de l'espace employé" class="mb-4">
    <ul class="list-inline mb-0">
        <?php foreach ($onglets as $code => [$url, $libelle]): ?>
            <li class="list-inline-item mb-2">
                <a class="btn <?= $actif === $code ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= $this->e($url) ?>"<?= $actif === $code ? ' aria-current="page"' : '' ?>><?= $this->e($libelle) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
