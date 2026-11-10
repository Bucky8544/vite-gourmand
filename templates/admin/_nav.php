<?php /* $actif : statistiques ou employes */ ?>
<nav aria-label="Sections de l'espace administrateur" class="mb-4">
    <ul class="list-inline mb-0">
        <li class="list-inline-item mb-2">
            <a class="btn <?= $actif === 'statistiques' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/admin"<?= $actif === 'statistiques' ? ' aria-current="page"' : '' ?>>Statistiques</a>
        </li>
        <li class="list-inline-item mb-2">
            <a class="btn <?= $actif === 'employes' ? 'btn-primary' : 'btn-outline-primary' ?>" href="/admin/employes"<?= $actif === 'employes' ? ' aria-current="page"' : '' ?>>Comptes employés</a>
        </li>
        <li class="list-inline-item mb-2">
            <a class="btn btn-outline-primary" href="/employe">Espace employé : commandes, avis, menus, horaires</a>
        </li>
    </ul>
</nav>
