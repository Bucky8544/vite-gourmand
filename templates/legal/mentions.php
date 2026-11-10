<?php $e = fn (string $cle): string => $this->e(\App\Core\Entreprise::get($cle)); ?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto legal">
        <h1>Mentions légales</h1>
        <?php $this->partial('legal/_fictif'); ?>

        <h2 class="h4">Éditeur du site</h2>
        <p><?= $e('nom') ?>, <?= $e('forme') ?> au capital de <?= $e('capital') ?>, immatriculée sous le numéro SIREN <?= $e('siren') ?>.<br>
           Siège social : <?= $e('adresse') ?>.<br>
           Téléphone : <?= $e('telephone') ?>.<br>
           E-mail : <a href="mailto:<?= $e('email') ?>"><?= $e('email') ?></a>.</p>

        <h2 class="h4">Directeur de la publication</h2>
        <p><?= $e('directeur') ?>.</p>

        <h2 class="h4">Conception et réalisation</h2>
        <p>Site conçu et développé par FastDev pour <?= $e('nom') ?>.</p>

        <h2 class="h4">Hébergement</h2>
        <p><?= $e('hebergeur_nom') ?><br>
           Adresse : <?= $e('hebergeur_adresse') ?><br>
           Pays d'hébergement des données : <?= $e('hebergeur_pays') ?></p>

        <h2 class="h4">Propriété intellectuelle</h2>
        <p>L'ensemble des contenus de ce site (textes, photographies, illustrations, logo, graphisme) est protégé par le droit d'auteur. Toute reproduction, représentation ou diffusion, totale ou partielle, sans autorisation préalable de l'éditeur est interdite.</p>

        <h2 class="h4">Données personnelles et cookies</h2>
        <p>Les données personnelles collectées sur ce site sont traitées conformément au règlement général sur la protection des données (RGPD) et à la loi Informatique et Libertés. Le détail des traitements, des durées de conservation et de vos droits figure dans la <a href="/confidentialite">politique de confidentialité</a>. Ce site n'utilise qu'un cookie technique de session, indispensable à son fonctionnement.</p>

        <h2 class="h4">Accessibilité</h2>
        <p>Ce site est conçu pour être utilisable par le plus grand nombre. Voir la <a href="/accessibilite">page d'accessibilité</a>.</p>

        <h2 class="h4">Médiation de la consommation</h2>
        <p>En cas de litige avec l'entreprise non résolu après une réclamation écrite, le consommateur peut recourir gratuitement à un médiateur de la consommation : <?= $e('mediateur') ?>.</p>

        <h2 class="h4">Contact</h2>
        <p>Pour toute question, utilisez le <a href="/contact">formulaire de contact</a>.</p>
    </div>
</div>
