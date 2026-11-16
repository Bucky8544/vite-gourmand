<?php $e = fn (string $cle): string => $this->e(\App\Core\Entreprise::get($cle)); ?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto legal">
        <h1>Accessibilité</h1>
        <?php $this->partial('legal/_fictif'); ?>

        <p><?= $e('nom') ?> s'engage à rendre son site accessible, conformément à l'article 47 de la loi n° 2005-102 du 11 février 2005 et au référentiel général d'amélioration de l'accessibilité (RGAA), version 4.1.</p>

        <h2 class="h4">État de conformité</h2>
        <p>Ce site n'a pas fait l'objet d'un audit de conformité complet : son niveau de conformité au RGAA n'est donc pas chiffré. Un contrôle partiel a été réalisé le 16 novembre 2026, avec les résultats ci-dessous.</p>

        <h2 class="h4">Méthode du contrôle partiel</h2>
        <ul>
            <li>Relecture du code des gabarits de pages, des styles et des scripts du site.</li>
            <li>Calcul des rapports de contraste de la palette de couleurs, selon la méthode WCAG (4,5 pour 1 pour le texte, 3 pour 1 pour les composants d'interface).</li>
            <li>Vérification sur le site en ligne du comportement du focus au clavier.</li>
            <li>Échantillon contrôlé : toutes les pages publiques et les formulaires, par relecture du code. Les pages sont contrôlées en tant que code ; elles n'ont pas toutes été parcourues une par une.</li>
        </ul>
        <p>Les tests avec un lecteur d'écran, le contrôle de la restitution à 200 % de zoom et celui de chaque page n'ont pas encore été réalisés.</p>

        <h2 class="h4">Points conformes constatés</h2>
        <ul>
            <li>Langue de la page déclarée, un seul titre de niveau 1 par page et hiérarchie de titres cohérente.</li>
            <li>Structure sémantique (en-tête, navigation, contenu principal, pied de page) et lien d'évitement « Aller au contenu » en début de page.</li>
            <li>Chaque champ de formulaire a un libellé visible ; les messages d'erreur sont reliés au champ concerné et listés en haut du formulaire.</li>
            <li>Tableaux de données avec légende et en-têtes de lignes et de colonnes ; les graphiques de l'espace d'administration sont doublés d'un tableau reprenant les mêmes données.</li>
            <li>Textes alternatifs obligatoires pour les images des menus, saisis par l'équipe ; images décoratives sans alternative.</li>
            <li>Mises à jour dynamiques (compteur de résultats, estimation du prix, galerie) annoncées aux technologies d'assistance, et fonctions essentielles utilisables sans JavaScript.</li>
            <li>Aucune information donnée par la couleur seule : les statuts sont écrits en toutes lettres.</li>
            <li>Rapports de contraste du texte supérieurs à 4,5 pour 1 pour la palette du site (texte courant, liens, boutons, pied de page, étiquettes de statut).</li>
        </ul>

        <h2 class="h4">Défauts relevés et corrigés lors du contrôle</h2>
        <ul>
            <li>Indicateur de focus au clavier trop discret sur les champs de formulaire, les liens de navigation et le pied de page : remplacé par un contour épais à fort contraste.</li>
            <li>Messages d'erreur de formulaire en rouge insuffisamment contrasté : couleur foncée adoptée.</li>
            <li>Bordure des cases à cocher et boutons radio insuffisamment contrastée : bordure renforcée.</li>
            <li>Champ de protection anti-spam du formulaire de contact affiché par erreur : masqué de nouveau pour les utilisateurs.</li>
        </ul>

        <h2 class="h4">Limites connues</h2>
        <ul>
            <li>Les textes alternatifs des images de menus sont rédigés par l'équipe et leur pertinence dépend de cette saisie.</li>
            <li>Le contenu saisi par l'équipe (descriptions, avis) n'est pas relu automatiquement.</li>
        </ul>

        <h2 class="h4">Signaler un problème</h2>
        <p>Si vous rencontrez un défaut d'accessibilité vous empêchant d'accéder à un contenu ou à une fonctionnalité, vous pouvez nous écrire par le <a href="/contact">formulaire de contact</a> ou par téléphone au <?= $e('telephone') ?>. Nous nous efforcerons de vous répondre rapidement et de vous fournir l'information sous une autre forme.</p>

        <h2 class="h4">Voies de recours</h2>
        <p>Si vous constatez un défaut d'accessibilité, que vous nous l'avez signalé et que vous n'obtenez pas de réponse satisfaisante, vous pouvez adresser une réclamation ou une demande de saisine au Défenseur des droits (defenseurdesdroits.fr).</p>
    </div>
</div>
