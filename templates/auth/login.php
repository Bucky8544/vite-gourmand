<div class="container py-5">
    <div class="col-md-8 col-lg-5 mx-auto">
        <h1>Connexion</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/connexion">
            <?= \App\Core\Csrf::field() ?>
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= $this->e($redirect) ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="email" class="form-label">Adresse e-mail</label>
                <input class="form-control" type="email" id="email" name="email" autocomplete="username"
                       value="<?= $this->e($old['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input class="form-control" type="password" id="mot_de_passe" name="mot_de_passe"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Se connecter</button>
        </form>

        <p class="mt-4 mb-1"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
        <p>Pas encore de compte ? <a href="/inscription">Créer un compte</a></p>
    </div>
</div>
