<?php /* Navigation de l'espace employé : $actif ('commandes' ou 'avis'), $avisEnAttente */ ?>
<nav aria-label="Sections de l'espace employé" class="mb-4">
    <ul class="list-inline mb-0">
        <li class="list-inline-item">
            <a class="btn <?= $actif === 'commandes' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/employe"<?= $actif === 'commandes' ? ' aria-current="page"' : '' ?>>Commandes</a>
        </li>
        <li class="list-inline-item">
            <a class="btn <?= $actif === 'avis' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/employe/avis"<?= $actif === 'avis' ? ' aria-current="page"' : '' ?>>Avis clients<?= (int) $avisEnAttente > 0 ? ' (' . (int) $avisEnAttente . ' en attente)' : '' ?></a>
        </li>
    </ul>
</nav>
