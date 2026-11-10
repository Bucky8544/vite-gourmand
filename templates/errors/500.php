<div class="container py-5">
    <h1>Une erreur est survenue</h1>
    <p>Nous rencontrons un problème technique. Veuillez réessayer dans quelques instants.</p>
    <p><a class="btn btn-primary" href="/">Retour à l'accueil</a></p>
    <?php if (!empty($debug)): ?>
        <pre class="small"><?= $this->e($debug) ?></pre>
    <?php endif; ?>
</div>
