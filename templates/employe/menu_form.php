<?php
$old = $old ?? [];
$errors = $errors ?? [];
$id = $id ?? null;
$types = ['entree' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts'];
$coches = array_map('intval', $old['plats'] ?? []);
$nbPlats = array_sum(array_map('count', $platsParType));

$classe = static fn (string $nom): string => isset($errors[$nom]) ? ' is-invalid' : '';
$decrire = static function (string $nom, string $aide = '') use ($errors): string {
    $ids = array_filter([$aide !== '' ? $aide : null, isset($errors[$nom]) ? $nom . '-erreur' : null]);

    return (isset($errors[$nom]) ? ' aria-invalid="true"' : '') . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
$retour = function (string $nom) use ($errors): void {
    if (isset($errors[$nom])) {
        echo '<div id="' . $nom . '-erreur" class="invalid-feedback d-block">' . $this->e($errors[$nom]) . '</div>';
    }
};
?>
<div class="container py-5">
    <h1><?= $id === null ? 'Nouveau menu' : 'Modifier le menu' ?></h1>
    <?php $this->partial('employe/_nav', ['actif' => 'menus']); ?>

    <?php if ($id !== null && $actifEnBase): ?>
        <p><a href="/menus/<?= (int) $id ?>">Voir ce menu sur le site</a></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <p class="fw-bold mb-2">Le menu n'a pas été enregistré :</p>
            <ul class="mb-0">
                <?php foreach ($errors as $champ => $message): ?>
                    <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $id === null ? '/employe/menus/nouveau' : '/employe/menus/' . (int) $id ?>" enctype="multipart/form-data" novalidate>
        <?= \App\Core\Csrf::field() ?>

        <fieldset class="mb-4">
            <legend class="h4">Présentation</legend>
            <div class="mb-3">
                <label for="titre" class="form-label">Titre du menu</label>
                <input class="form-control<?= $classe('titre') ?>" type="text" id="titre" name="titre" maxlength="150" required value="<?= $this->e($old['titre'] ?? '') ?>"<?= $decrire('titre') ?>>
                <?php $retour('titre'); ?>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (présentation du menu)</label>
                <textarea class="form-control<?= $classe('description') ?>" id="description" name="description" rows="3" maxlength="2000" required<?= $decrire('description') ?>><?= $this->e($old['description'] ?? '') ?></textarea>
                <?php $retour('description'); ?>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="theme_id" class="form-label">Thème</label>
                    <select class="form-select<?= $classe('theme_id') ?>" id="theme_id" name="theme_id" required<?= $decrire('theme_id') ?>>
                        <option value="">Choisissez un thème</option>
                        <?php foreach ($themes as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"<?= ($old['theme_id'] ?? '') === (string) $t['id'] ? ' selected' : '' ?>><?= $this->e($t['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php $retour('theme_id'); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="regime_id" class="form-label">Régime</label>
                    <select class="form-select<?= $classe('regime_id') ?>" id="regime_id" name="regime_id" required<?= $decrire('regime_id') ?>>
                        <option value="">Choisissez un régime</option>
                        <?php foreach ($regimes as $r): ?>
                            <option value="<?= (int) $r['id'] ?>"<?= ($old['regime_id'] ?? '') === (string) $r['id'] ? ' selected' : '' ?>><?= $this->e($r['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php $retour('regime_id'); ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h4">Prix, stock et conditions</legend>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="nombre_personne_minimum" class="form-label">Personnes minimum</label>
                    <input class="form-control<?= $classe('nombre_personne_minimum') ?>" type="number" min="1" max="999" step="1" id="nombre_personne_minimum" name="nombre_personne_minimum" required value="<?= $this->e($old['nombre_personne_minimum'] ?? '') ?>"<?= $decrire('nombre_personne_minimum') ?>>
                    <?php $retour('nombre_personne_minimum'); ?>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="prix_minimum" class="form-label">Prix pour ce minimum (€)</label>
                    <input class="form-control<?= $classe('prix_minimum') ?>" type="text" inputmode="decimal" id="prix_minimum" name="prix_minimum" required value="<?= $this->e($old['prix_minimum'] ?? '') ?>"<?= $decrire('prix_minimum', 'aide-prix') ?>>
                    <div id="aide-prix" class="form-text">Le prix par personne s'en déduit.</div>
                    <?php $retour('prix_minimum'); ?>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="stock" class="form-label">Stock (commandes possibles)</label>
                    <input class="form-control<?= $classe('stock') ?>" type="number" min="0" max="9999" step="1" id="stock" name="stock" required value="<?= $this->e($old['stock'] ?? '') ?>"<?= $decrire('stock') ?>>
                    <?php $retour('stock'); ?>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="delai_commande_jours" class="form-label">Délai de commande (jours)</label>
                    <input class="form-control<?= $classe('delai_commande_jours') ?>" type="number" min="0" max="365" step="1" id="delai_commande_jours" name="delai_commande_jours" required value="<?= $this->e($old['delai_commande_jours'] ?? '') ?>"<?= $decrire('delai_commande_jours', 'aide-delai') ?>>
                    <div id="aide-delai" class="form-text">Appliqué aux commandes.</div>
                    <?php $retour('delai_commande_jours'); ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="conditions" class="form-label">Conditions du menu</label>
                <textarea class="form-control<?= $classe('conditions') ?>" id="conditions" name="conditions" rows="3" maxlength="2000" required<?= $decrire('conditions', 'aide-conditions') ?>><?= $this->e($old['conditions'] ?? '') ?></textarea>
                <div id="aide-conditions" class="form-text">Affichées en évidence avant la commande : délai à respecter, précautions de stockage…</div>
                <?php $retour('conditions'); ?>
            </div>
        </fieldset>

        <fieldset class="mb-4"<?= isset($errors['plats']) ? ' aria-describedby="plats-erreur"' : '' ?>>
            <legend class="h4">Composition</legend>
            <?php if ($nbPlats === 0): ?>
                <p>Aucun plat n'existe encore. <a href="/employe/plats/nouveau">Créez d'abord des plats</a>.</p>
            <?php else: ?>
                <p class="form-text">Un plat peut figurer dans plusieurs menus. <a href="/employe/plats/nouveau">Créer un nouveau plat</a></p>
                <div class="row g-3">
                    <?php foreach ($types as $type => $libelle): ?>
                        <?php if (($platsParType[$type] ?? []) === []) { continue; } ?>
                        <div class="col-md-4">
                            <p class="fw-bold mb-2"><?= $this->e($libelle) ?></p>
                            <?php foreach ($platsParType[$type] as $p): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input<?= $classe('plats') ?>" type="checkbox" name="plats[]" id="plat-<?= (int) $p['id'] ?>" value="<?= (int) $p['id'] ?>"<?= in_array((int) $p['id'], $coches, true) ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="plat-<?= (int) $p['id'] ?>">
                                        <?= $this->e($p['titre']) ?>
                                        <span class="d-block small text-muted">Allergènes : <?= $p['allergenes'] === [] ? 'aucun déclaré' : $this->e(implode(', ', $p['allergenes'])) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php $retour('plats'); ?>
            <span id="plats" tabindex="-1"></span>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h4">Galerie d'images</legend>
            <p class="form-text">JPEG, PNG ou WebP, 2 Mo maximum par image, 8 images par menu. La première image est celle qui illustre le menu dans la liste. Chaque image doit avoir un texte alternatif qui la décrit.</p>
            <?php $retour('images'); ?>

            <?php foreach ($images as $img): ?>
                <?php
                $iid = (int) $img['id'];
                $o = $old['images'][$iid] ?? ['alt' => $img['texte_alternatif'], 'position' => (string) $img['position'], 'supprimer' => false];
                ?>
                <div class="carte-espace mb-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-2">
                            <img class="img-fluid image-arrondie" src="<?= $this->e($this->menuImage($img['url'])) ?>" alt="" width="160" height="100">
                        </div>
                        <div class="col-md-6">
                            <label for="image_alt_<?= $iid ?>" class="form-label">Texte alternatif</label>
                            <input class="form-control<?= $classe('image_alt_' . $iid) ?>" type="text" maxlength="255" id="image_alt_<?= $iid ?>" name="image[<?= $iid ?>][alt]" value="<?= $this->e($o['alt']) ?>"<?= $decrire('image_alt_' . $iid) ?>>
                            <?php $retour('image_alt_' . $iid); ?>
                        </div>
                        <div class="col-md-2">
                            <label for="image_pos_<?= $iid ?>" class="form-label">Position</label>
                            <input class="form-control" type="number" min="0" max="99" id="image_pos_<?= $iid ?>" name="image[<?= $iid ?>][position]" value="<?= $this->e($o['position']) ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-md-4">
                                <input class="form-check-input" type="checkbox" id="image_sup_<?= $iid ?>" name="image[<?= $iid ?>][supprimer]" value="1"<?= !empty($o['supprimer']) ? ' checked' : '' ?>>
                                <label class="form-check-label" for="image_sup_<?= $iid ?>">Supprimer</label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <p class="fw-bold mb-2">Ajouter des images</p>
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="row g-3 mb-2">
                    <div class="col-md-5">
                        <label for="nouvelle_image_<?= $i ?>" class="form-label">Fichier <?= $i + 1 ?></label>
                        <input class="form-control<?= $classe('nouvelle_image_' . $i) ?>" type="file" accept="image/jpeg,image/png,image/webp" id="nouvelle_image_<?= $i ?>" name="nouvelle_image[]"<?= $decrire('nouvelle_image_' . $i) ?>>
                        <?php $retour('nouvelle_image_' . $i); ?>
                    </div>
                    <div class="col-md-7">
                        <label for="nouvelle_alt_<?= $i ?>" class="form-label">Texte alternatif du fichier <?= $i + 1 ?></label>
                        <input class="form-control<?= $classe('nouvelle_alt_' . $i) ?>" type="text" maxlength="255" id="nouvelle_alt_<?= $i ?>" name="nouvelle_alt[]"<?= $decrire('nouvelle_alt_' . $i) ?>>
                        <?php $retour('nouvelle_alt_' . $i); ?>
                    </div>
                </div>
            <?php endfor; ?>
            <p class="form-text">Si le formulaire contient une erreur, il faudra choisir de nouveau les fichiers.</p>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h4">Publication</legend>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1"<?= ($old['actif'] ?? '') === '1' ? ' checked' : '' ?>>
                <label class="form-check-label" for="actif">Menu visible sur le site</label>
            </div>
        </fieldset>

        <div class="d-flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary btn-lg"><?= $id === null ? 'Créer le menu' : 'Enregistrer les modifications' ?></button>
            <a class="btn btn-outline-primary btn-lg" href="/employe/menus">Retour à la liste</a>
        </div>
    </form>

    <?php if ($id !== null): ?>
        <section class="mt-5" aria-labelledby="titre-suppression">
            <h2 id="titre-suppression" class="h4">Supprimer le menu</h2>
            <?php if ($nbCommandes > 0): ?>
                <p>Ce menu a déjà été commandé (<?= (int) $nbCommandes ?> commande<?= $nbCommandes > 1 ? 's' : '' ?>) : il ne peut pas être supprimé, car l'historique des commandes doit rester intact. Décochez « Menu visible sur le site » ci-dessus pour le retirer de la vente.</p>
            <?php else: ?>
                <p>Ce menu n'a jamais été commandé : il peut être supprimé définitivement, avec sa composition et ses images.</p>
                <form method="post" action="/employe/menus/<?= (int) $id ?>/supprimer">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmer-suppression" name="confirmer" value="1">
                        <label class="form-check-label" for="confirmer-suppression">Je confirme la suppression définitive de ce menu.</label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Supprimer le menu</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
