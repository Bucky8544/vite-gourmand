<?php
$jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
?>
<div class="container py-5">
    <h1>Horaires</h1>
    <?php $this->partial('employe/_nav', ['actif' => 'horaires']); ?>
    <p class="lead">Ces horaires s'affichent en bas de chaque page du site et limitent l'heure de livraison choisie par les clients. Cochez « Fermé » pour un jour sans activité.</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <p class="fw-bold mb-2">Les horaires n'ont pas été enregistrés :</p>
            <ul class="mb-0">
                <?php foreach ($errors as $champ => $message): ?>
                    <li><a href="#<?= $this->e($champ) ?>"><?= $this->e($message) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/employe/horaires" novalidate>
        <?= \App\Core\Csrf::field() ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Horaires d'ouverture, du lundi au dimanche</caption>
                <thead><tr><th scope="col">Jour</th><th scope="col">Ouverture</th><th scope="col">Fermeture</th><th scope="col">Fermé</th></tr></thead>
                <tbody>
                    <?php foreach ($jours as $j => $nom): ?>
                        <tr>
                            <th scope="row"><?= $this->e($nom) ?></th>
                            <td>
                                <label for="ouverture_<?= $j ?>" class="visually-hidden">Heure d'ouverture du <?= $this->e(mb_strtolower($nom)) ?></label>
                                <input class="form-control<?= isset($errors["ouverture_$j"]) ? ' is-invalid' : '' ?>" type="time" id="ouverture_<?= $j ?>" name="ouverture_<?= $j ?>" value="<?= $this->e($valeurs["ouverture_$j"] ?? '') ?>"
                                       <?= isset($errors["ouverture_$j"]) ? 'aria-invalid="true" aria-describedby="ouverture_' . $j . '-erreur"' : '' ?>>
                                <?php if (isset($errors["ouverture_$j"])): ?><div id="ouverture_<?= $j ?>-erreur" class="invalid-feedback d-block"><?= $this->e($errors["ouverture_$j"]) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <label for="fermeture_<?= $j ?>" class="visually-hidden">Heure de fermeture du <?= $this->e(mb_strtolower($nom)) ?></label>
                                <input class="form-control<?= isset($errors["fermeture_$j"]) ? ' is-invalid' : '' ?>" type="time" id="fermeture_<?= $j ?>" name="fermeture_<?= $j ?>" value="<?= $this->e($valeurs["fermeture_$j"] ?? '') ?>"
                                       <?= isset($errors["fermeture_$j"]) ? 'aria-invalid="true" aria-describedby="fermeture_' . $j . '-erreur"' : '' ?>>
                                <?php if (isset($errors["fermeture_$j"])): ?><div id="fermeture_<?= $j ?>-erreur" class="invalid-feedback d-block"><?= $this->e($errors["fermeture_$j"]) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ferme_<?= $j ?>" name="ferme_<?= $j ?>" value="1"<?= ($valeurs["ferme_$j"] ?? '') === '1' ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="ferme_<?= $j ?>">Fermé<span class="visually-hidden"> le <?= $this->e(mb_strtolower($nom)) ?></span></label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Enregistrer les horaires</button>
    </form>
</div>
