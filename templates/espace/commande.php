<?php
use App\Core\Money;
use App\Service\OrderStatus;
use App\Service\Pricing;

$c = $commande;
$errors = $errors ?? [];
$old = $old ?? [];
$cents = static fn (string $euros): int => Pricing::toCents($euros);
$date = (new DateTimeImmutable($c['date_prestation']))->format('d/m/Y');
$heure = substr((string) $c['heure_livraison'], 0, 5);
$numero = $this->e($c['numero_commande']);
$libellesContact = ['gsm' => 'par téléphone (GSM)', 'mail' => 'par e-mail'];
$formatDate = static function (string $dateHeure): array {
    $d = new DateTimeImmutable($dateHeure);

    return [$d->format('c'), $d->format('d/m/Y') . ' à ' . $d->format('H:i')];
};
?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto">
        <nav aria-label="Fil d'Ariane" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/mon-espace">Mon espace</a></li>
                <li class="breadcrumb-item active" aria-current="page">Commande <?= $numero ?></li>
            </ol>
        </nav>

        <h1>Commande <?= $numero ?></h1>
        <p>
            Statut : <span class="statut statut-<?= $this->e($c['statut']) ?>"><?= $this->e(OrderStatus::label($c['statut'])) ?></span>
        </p>

        <?php if ($modifiable): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a class="btn btn-primary" href="/mon-espace/commandes/<?= $numero ?>/modifier">Modifier la commande</a>
                <a class="btn btn-outline-primary" href="/mon-espace/commandes/<?= $numero ?>/annuler">Annuler la commande</a>
            </div>
            <p class="form-text">Vous pouvez modifier ou annuler votre commande tant qu'elle n'a pas été acceptée par notre équipe. Le menu ne peut pas être changé.</p>
        <?php endif; ?>

        <h2 class="h4 mt-4">Votre prestation</h2>
        <dl class="row">
            <dt class="col-sm-4">Menu</dt><dd class="col-sm-8"><a href="/menus/<?= (int) $c['menu_id'] ?>"><?= $this->e($c['menu_titre']) ?></a></dd>
            <dt class="col-sm-4">Nombre de personnes</dt><dd class="col-sm-8"><?= (int) $c['nombre_personne'] ?></dd>
            <dt class="col-sm-4">Date et heure</dt><dd class="col-sm-8"><?= $this->e($date) ?> à <?= $this->e($heure) ?></dd>
            <dt class="col-sm-4">Lieu</dt>
            <dd class="col-sm-8"><?= $this->e($c['adresse_prestation']) ?>, <?= $this->e($c['code_postal_prestation']) ?> <?= $this->e($c['ville_prestation']) ?></dd>
            <dt class="col-sm-4">Commandée le</dt><dd class="col-sm-8"><?= $this->e((new DateTimeImmutable($c['date_commande']))->format('d/m/Y')) ?></dd>
        </dl>

        <h2 class="h4">Prix</h2>
        <table class="table devis">
            <caption class="visually-hidden">Prix de la commande</caption>
            <tbody>
                <tr><th scope="row">Menu</th><td class="text-end"><?= Money::format($cents($c['prix_menu']) + $cents($c['remise'])) ?></td></tr>
                <?php if ($cents($c['remise']) > 0): ?>
                    <tr><th scope="row">Remise</th><td class="text-end">&minus;<?= Money::format($cents($c['remise'])) ?></td></tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Livraison</th>
                    <td class="text-end"><?= $cents($c['prix_livraison']) === 0 ? 'Offerte' : Money::format($cents($c['prix_livraison'])) ?></td>
                </tr>
            </tbody>
            <tfoot><tr class="devis-total"><th scope="row">Total</th><td class="text-end"><?= Money::format($cents($c['prix_total'])) ?></td></tr></tfoot>
        </table>

        <?php if ($c['statut'] === 'annulee'): ?>
            <section class="mt-4" aria-labelledby="titre-annulation">
                <h2 id="titre-annulation" class="h4">Annulation</h2>
                <p class="mb-1"><?= $this->e($c['motif_annulation'] ?? 'Cette commande a été annulée.') ?></p>
                <?php if (!empty($c['mode_contact_annulation'])): ?>
                    <p class="mb-0">Notre équipe vous a contacté <?= $this->e($libellesContact[$c['mode_contact_annulation']] ?? '') ?>.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="mt-4" aria-labelledby="titre-suivi">
            <h2 id="titre-suivi" class="h4">Suivi de votre commande</h2>
            <?php if ($suiviVisible): ?>
                <ol class="suivi list-unstyled">
                    <?php foreach ($historique as $ligne): ?>
                        <?php [$iso, $texte] = $formatDate($ligne['date_modification']); ?>
                        <li>
                            <span class="suivi-statut"><?= $this->e(OrderStatus::label($ligne['statut'])) ?></span>
                            <time datetime="<?= $this->e($iso) ?>"><?= $this->e($texte) ?></time>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php if ($c['statut'] === 'attente_retour_materiel'): ?>
                    <div class="alert alert-info" role="status">
                        Du matériel vous a été prêté. Si le matériel n'est pas restitué sous 10 jours ouvrés, des frais de 600&nbsp;€ s'appliqueront
                        (voir les <a href="/cgv">conditions générales de vente</a>). Pour le rendre, prenez contact avec nous.
                    </div>
                <?php endif; ?>
            <?php elseif ($c['statut'] === 'en_attente'): ?>
                <p>Votre commande est en attente de validation par notre équipe. Le suivi détaillé sera disponible dès qu'elle sera acceptée.</p>
            <?php else: ?>
                <p>Aucun suivi disponible pour cette commande.</p>
            <?php endif; ?>
        </section>

        <section class="mt-4" aria-labelledby="titre-avis">
            <h2 id="titre-avis" class="h4">Votre avis</h2>

            <?php if ($avis): ?>
                <p class="mb-1"><strong>Note : <?= (int) $avis['note'] ?> sur 5</strong></p>
                <p class="mb-1"><?= nl2br($this->e($avis['commentaire'])) ?></p>
                <p class="form-text">
                    <?= [
                        'en_attente' => 'Votre avis est en attente de validation par notre équipe.',
                        'valide' => 'Votre avis est publié sur notre site. Merci !',
                        'refuse' => "Votre avis n'a pas été retenu pour publication.",
                    ][$avis['statut']] ?? '' ?>
                </p>
            <?php elseif ($peutDonnerAvis): ?>
                <p>Votre commande est terminée. Dites-nous ce que vous en avez pensé.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $champ => $message): ?>
                                <li><a href="#<?= $this->e($champ === 'note' ? 'note-1' : $champ) ?>"><?= $this->e($message) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/mon-espace/commandes/<?= $numero ?>/avis" novalidate>
                    <?= \App\Core\Csrf::field() ?>
                    <fieldset class="mb-3"<?= isset($errors['note']) ? ' aria-describedby="note-erreur"' : '' ?>>
                        <legend class="fs-6 fw-bold">Votre note</legend>
                        <div class="d-flex flex-wrap gap-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="form-check">
                                    <input class="form-check-input<?= isset($errors['note']) ? ' is-invalid' : '' ?>" type="radio" name="note" id="note-<?= $i ?>" value="<?= $i ?>"<?= ($old['note'] ?? '') === (string) $i ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="note-<?= $i ?>"><?= $i ?> sur 5</label>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <?php if (isset($errors['note'])): ?><div id="note-erreur" class="invalid-feedback d-block"><?= $this->e($errors['note']) ?></div><?php endif; ?>
                    </fieldset>

                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Votre commentaire</label>
                        <textarea class="form-control<?= isset($errors['commentaire']) ? ' is-invalid' : '' ?>" id="commentaire" name="commentaire"
                                  rows="4" maxlength="1000" required<?= isset($errors['commentaire']) ? ' aria-invalid="true" aria-describedby="commentaire-erreur"' : '' ?>><?= $this->e($old['commentaire'] ?? '') ?></textarea>
                        <?php if (isset($errors['commentaire'])): ?><div id="commentaire-erreur" class="invalid-feedback d-block"><?= $this->e($errors['commentaire']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer mon avis</button>
                    <p class="form-text mt-2">Votre avis sera publié sur notre site après validation par notre équipe.</p>
                </form>
            <?php else: ?>
                <p class="mb-0">Vous pourrez donner votre avis une fois la commande terminée.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
