<?php
$errors = $errors ?? [];
$old = $old ?? [];
?>
<div class="container py-5">
    <h1>Comptes employés</h1>
    <?php $this->partial('admin/_nav', ['actif' => 'employes']); ?>

    <section aria-labelledby="titre-liste" class="mb-5">
        <h2 id="titre-liste" class="h4">Employés</h2>
        <?php if ($employes === []): ?>
            <p>Aucun compte employé pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden">Comptes employés</caption>
                    <thead><tr><th scope="col">Identifiant (e-mail)</th><th scope="col">Créé le</th><th scope="col">État</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($employes as $e): ?>
                            <?php $actif = (int) $e['actif'] === 1; ?>
                            <tr>
                                <th scope="row"><?= $this->e($e['email']) ?></th>
                                <td><?= $this->e((new DateTimeImmutable($e['created_at']))->format('d/m/Y')) ?></td>
                                <td><span class="statut statut-<?= $actif ? 'accepte' : 'annulee' ?>"><?= $actif ? 'Compte actif' : 'Compte désactivé' ?></span></td>
                                <td>
                                    <form method="post" action="/admin/employes/<?= (int) $e['id'] ?>/actif">
                                        <?= \App\Core\Csrf::field() ?>
                                        <input type="hidden" name="actif" value="<?= $actif ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?= $actif ? 'Désactiver' : 'Réactiver' ?><span class="visually-hidden"> le compte <?= $this->e($e['email']) ?></span></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="form-text">Un compte désactivé ne peut plus se connecter, et une session déjà ouverte est fermée à la requête suivante. Il peut être réactivé à tout moment.</p>
        <?php endif; ?>
    </section>

    <section aria-labelledby="titre-creation">
        <h2 id="titre-creation" class="h4">Créer un compte employé</h2>
        <p>L'employé reçoit un e-mail l'informant de la création de son compte, <strong>sans le mot de passe</strong> : vous devez le lui communiquer vous-même. Il pourra ensuite en choisir un autre avec « Mot de passe oublié ». Il n'est pas possible de créer un compte administrateur depuis l'application.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le compte n'a pas été créé :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/employes" class="col-lg-6" novalidate>
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Adresse e-mail (identifiant)</label>
                <input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" type="email" id="email" name="email" autocomplete="off" required
                       value="<?= $this->e($old['email'] ?? '') ?>" <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-erreur"' : '' ?>>
                <?php if (isset($errors['email'])): ?><div id="email-erreur" class="invalid-feedback d-block"><?= $this->e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input class="form-control<?= isset($errors['mot_de_passe']) ? ' is-invalid' : '' ?>" type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password" required
                       aria-describedby="aide-mdp<?= isset($errors['mot_de_passe']) ? ' mot_de_passe-erreur' : '' ?>" <?= isset($errors['mot_de_passe']) ? 'aria-invalid="true"' : '' ?>>
                <div id="aide-mdp" class="form-text">10 caractères minimum, avec une majuscule, une minuscule, un chiffre et un caractère spécial.</div>
                <?php if (isset($errors['mot_de_passe'])): ?><div id="mot_de_passe-erreur" class="invalid-feedback d-block"><?= $this->e($errors['mot_de_passe']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="mot_de_passe_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input class="form-control<?= isset($errors['mot_de_passe_confirmation']) ? ' is-invalid' : '' ?>" type="password" id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" autocomplete="new-password" required
                       <?= isset($errors['mot_de_passe_confirmation']) ? 'aria-invalid="true" aria-describedby="mot_de_passe_confirmation-erreur"' : '' ?>>
                <?php if (isset($errors['mot_de_passe_confirmation'])): ?><div id="mot_de_passe_confirmation-erreur" class="invalid-feedback d-block"><?= $this->e($errors['mot_de_passe_confirmation']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Créer le compte</button>
        </form>
    </section>
</div>
