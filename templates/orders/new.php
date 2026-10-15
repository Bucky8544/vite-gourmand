<?php
$old = $old ?? [];
$errors = $errors ?? [];
$menuChoisi = null;
foreach ($menus as $m) {
    if ((string) $m['id'] === ($old['menu_id'] ?? '')) {
        $menuChoisi = $m;
    }
}
$min = $menuChoisi ? (int) $menuChoisi['nombre_personne_minimum'] : null;
$delai = $menuChoisi ? (int) $menuChoisi['delai_commande_jours'] : null;
$dateMin = (new DateTimeImmutable('today'))->modify('+' . ($delai ?? 1) . ' days')->format('Y-m-d');

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
$tel = $profil ? trim(chunk_split((string) $profil['telephone'], 2, ' ')) : '';
?>
<div class="container py-5">
    <div class="col-lg-8 mx-auto">
        <h1>Commander</h1>
        <p>Étape 1 sur 2 : les informations de la prestation. Le prix détaillé s'affiche avant la validation.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-2">Le formulaire contient des erreurs :</p>
                <ul class="mb-0">
                    <?php foreach ($errors as $champ => $message): ?>
                        <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/commande/recapitulatif" novalidate>
            <?= \App\Core\Csrf::field() ?>

            <fieldset class="mb-4">
                <legend class="h4">Vos coordonnées</legend>
                <?php if ($profil): ?>
                    <dl class="row mb-1">
                        <dt class="col-sm-3">Nom</dt><dd class="col-sm-9"><?= $this->e($profil['prenom'] . ' ' . $profil['nom']) ?></dd>
                        <dt class="col-sm-3">E-mail</dt><dd class="col-sm-9"><?= $this->e($profil['email']) ?></dd>
                        <dt class="col-sm-3">GSM</dt><dd class="col-sm-9"><?= $this->e($tel) ?></dd>
                    </dl>
                    <p class="form-text">Ces informations viennent de votre compte. Vous pouvez les modifier dans <a href="/mon-espace">votre espace</a>.</p>
                <?php endif; ?>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="h4">Le menu</legend>
                <?php if ($menuChoisi && (int) $menuChoisi['stock'] <= 0): ?>
                    <div class="alert alert-info" role="status">Le menu « <?= $this->e($menuChoisi['titre']) ?> » n'est plus disponible pour le moment. Choisissez-en un autre.</div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="menu_id" class="form-label">Menu</label>
                    <select class="form-select<?= $classe('menu_id') ?>" id="menu_id" name="menu_id" required<?= $decrire('menu_id') ?>>
                        <option value="">Choisissez un menu</option>
                        <?php foreach ($menus as $m): ?>
                            <?php $dispo = (int) $m['stock'] > 0; ?>
                            <option value="<?= (int) $m['id'] ?>" data-min="<?= (int) $m['nombre_personne_minimum'] ?>"
                                    data-delai="<?= (int) $m['delai_commande_jours'] ?>"<?= ($old['menu_id'] ?? '') === (string) $m['id'] ? ' selected' : '' ?><?= $dispo ? '' : ' disabled' ?>>
                                <?= $this->e($m['titre']) ?> (<?= $this->price($m['prix_minimum']) ?> pour <?= (int) $m['nombre_personne_minimum'] ?> pers. min.)<?= $dispo ? '' : ' : indisponible' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $retour('menu_id'); ?>
                    <?php if ($menuChoisi): ?>
                        <p class="form-text mb-0"><a href="/menus/<?= (int) $menuChoisi['id'] ?>">Voir le détail et les conditions de ce menu</a></p>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="personnes" class="form-label">Nombre de personnes</label>
                    <input class="form-control<?= $classe('personnes') ?>" type="number" id="personnes" name="personnes" step="1"
                           min="<?= $min ?? 1 ?>" value="<?= $this->e($old['personnes'] ?? '') ?>" required<?= $decrire('personnes', 'aide-personnes') ?>>
                    <div id="aide-personnes" class="form-text">
                        <?= $min !== null
                            ? 'Minimum pour ce menu : ' . $min . ' personnes. Remise de 10 % à partir de ' . ($min + 5) . ' personnes.'
                            : 'Choisissez un menu pour connaître le minimum de personnes.' ?>
                    </div>
                    <?php $retour('personnes'); ?>
                </div>

                <div id="estimation" class="estimation" aria-live="polite">
                    <p class="mb-0">Le prix détaillé s'affiche à l'étape suivante.</p>
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="h4">La prestation</legend>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_prestation" class="form-label">Date de la prestation</label>
                        <input class="form-control<?= $classe('date_prestation') ?>" type="date" id="date_prestation" name="date_prestation"
                               min="<?= $this->e($dateMin) ?>" value="<?= $this->e($old['date_prestation'] ?? '') ?>" required<?= $decrire('date_prestation', 'aide-delai') ?>>
                        <div id="aide-delai" class="form-text"><?= $delai !== null ? 'Ce menu se commande au moins ' . $delai . ' jours avant la prestation.' : '' ?></div>
                        <?php $retour('date_prestation'); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="heure_livraison" class="form-label">Heure de livraison souhaitée</label>
                        <input class="form-control<?= $classe('heure_livraison') ?>" type="time" id="heure_livraison" name="heure_livraison"
                               value="<?= $this->e($old['heure_livraison'] ?? '') ?>" required<?= $decrire('heure_livraison') ?>>
                        <?php $retour('heure_livraison'); ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adresse_prestation" class="form-label">Adresse de la prestation</label>
                    <input class="form-control<?= $classe('adresse_prestation') ?>" type="text" id="adresse_prestation" name="adresse_prestation"
                           autocomplete="street-address" value="<?= $this->e($old['adresse_prestation'] ?? '') ?>" required<?= $decrire('adresse_prestation') ?>>
                    <?php $retour('adresse_prestation'); ?>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="code_postal_prestation" class="form-label">Code postal</label>
                        <input class="form-control<?= $classe('code_postal_prestation') ?>" type="text" id="code_postal_prestation" name="code_postal_prestation"
                               inputmode="numeric" autocomplete="postal-code" value="<?= $this->e($old['code_postal_prestation'] ?? '') ?>" required<?= $decrire('code_postal_prestation') ?>>
                        <?php $retour('code_postal_prestation'); ?>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="ville_prestation" class="form-label">Ville</label>
                        <input class="form-control<?= $classe('ville_prestation') ?>" type="text" id="ville_prestation" name="ville_prestation"
                               autocomplete="address-level2" value="<?= $this->e($old['ville_prestation'] ?? '') ?>" required<?= $decrire('ville_prestation') ?>>
                        <?php $retour('ville_prestation'); ?>
                    </div>
                </div>
                <p class="form-text">Livraison offerte à Bordeaux. Hors Bordeaux : 5 € + 0,59 € par kilomètre, calculés à l'étape suivante.</p>
            </fieldset>

            <button type="submit" class="btn btn-primary btn-lg">Voir le récapitulatif</button>
        </form>
    </div>
</div>
<script src="/assets/js/commande.js" defer></script>
