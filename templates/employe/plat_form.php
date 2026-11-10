<?php
$old = $old ?? [];
$errors = $errors ?? [];
$id = $id ?? null;
$coches = array_map('intval', $old['allergenes'] ?? []);
?>
<div class="container py-5">
    <div class="col-lg-8">
        <h1><?= $id === null ? 'Nouveau plat' : 'Modifier le plat' ?></h1>
        <?php $this->partial('employe/_nav', ['actif' => 'plats']); ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le plat n'a pas été enregistré :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $id === null ? '/employe/plats/nouveau' : '/employe/plats/' . (int) $id ?>" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <div class="mb-3">
                <label for="titre" class="form-label">Nom du plat</label>
                <input class="form-control<?= isset($errors['titre']) ? ' is-invalid' : '' ?>" type="text" id="titre" name="titre" maxlength="150" required value="<?= $this->e($old['titre'] ?? '') ?>"
                       <?= isset($errors['titre']) ? 'aria-invalid="true" aria-describedby="titre-erreur"' : '' ?>>
                <?php if (isset($errors['titre'])): ?><div id="titre-erreur" class="invalid-feedback d-block"><?= $this->e($errors['titre']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select<?= isset($errors['type']) ? ' is-invalid' : '' ?>" id="type" name="type" required
                        <?= isset($errors['type']) ? 'aria-invalid="true" aria-describedby="type-erreur"' : '' ?>>
                    <option value="">Choisissez un type</option>
                    <?php foreach (\App\Validation\PlatValidator::TYPES as $code => $libelle): ?>
                        <option value="<?= $this->e($code) ?>"<?= ($old['type'] ?? '') === $code ? ' selected' : '' ?>><?= $this->e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['type'])): ?><div id="type-erreur" class="invalid-feedback d-block"><?= $this->e($errors['type']) ?></div><?php endif; ?>
            </div>

            <fieldset class="mb-4">
                <legend class="fs-6 fw-bold">Allergènes présents dans ce plat</legend>
                <p class="form-text">Ces informations sont affichées aux clients avant la commande : cochez tout ce qui est présent. Rien de coché signifie « aucun allergène déclaré ».</p>
                <div class="row">
                    <?php foreach ($allergenes as $a): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allergenes[]" id="allergene-<?= (int) $a['id'] ?>" value="<?= (int) $a['id'] ?>"<?= in_array((int) $a['id'], $coches, true) ? ' checked' : '' ?>>
                                <label class="form-check-label" for="allergene-<?= (int) $a['id'] ?>"><?= $this->e($a['libelle']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['allergenes'])): ?><div id="allergenes" class="invalid-feedback d-block"><?= $this->e($errors['allergenes']) ?></div><?php endif; ?>
            </fieldset>

            <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary btn-lg"><?= $id === null ? 'Créer le plat' : 'Enregistrer' ?></button>
                <a class="btn btn-outline-primary btn-lg" href="/employe/plats">Retour à la liste</a>
            </div>
        </form>

        <?php if ($id !== null && $plat !== null): ?>
            <section class="mt-5" aria-labelledby="titre-suppression">
                <h2 id="titre-suppression" class="h4">Supprimer le plat</h2>
                <?php if ($plat['menus'] !== []): ?>
                    <p>Ce plat est utilisé par : <?= $this->e(implode(', ', array_column($plat['menus'], 'titre'))) ?>. Retirez-le d'abord de ces menus pour pouvoir le supprimer.</p>
                <?php else: ?>
                    <form method="post" action="/employe/plats/<?= (int) $id ?>/supprimer">
                        <?= \App\Core\Csrf::field() ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmer-suppression" name="confirmer" value="1">
                            <label class="form-check-label" for="confirmer-suppression">Je confirme la suppression définitive de ce plat.</label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Supprimer le plat</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
