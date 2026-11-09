<div class="container py-5">
    <h1>Espace administrateur</h1>
    <p>Connecté en tant que <?= $this->e($user['email']) ?>.</p>
    <p>L'administrateur peut faire tout ce que fait un employé :</p>
    <ul>
        <li><a href="/employe">Gérer les commandes (filtres, statuts, annulations)</a></li>
        <li><a href="/employe/avis">Modérer les avis clients</a></li>
    </ul>
    <p class="text-muted">Page provisoire : la gestion des employés et les statistiques arrivent avec les cartes « Espace administrateur ».</p>
</div>
