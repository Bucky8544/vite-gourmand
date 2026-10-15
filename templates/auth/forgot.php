<div class="container py-5">
    <div class="col-md-8 col-lg-5 mx-auto">
        <h1>Mot de passe oublié</h1>
        <p>Saisissez l'adresse e-mail de votre compte. Si elle correspond à un compte, nous vous enverrons un lien
           pour choisir un nouveau mot de passe.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/mot-de-passe-oublie">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Adresse e-mail</label>
                <input class="form-control" type="email" id="email" name="email" autocomplete="email"
                       value="<?= $this->e($old['email'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Envoyer le lien</button>
        </form>

        <p class="mt-4"><a href="/connexion">Retour à la connexion</a></p>
    </div>
</div>
