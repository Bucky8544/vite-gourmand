<?php $e = fn (string $cle): string => $this->e(\App\Core\Entreprise::get($cle)); ?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto legal">
        <h1>Accessibilité</h1>
        <?php $this->partial('legal/_fictif'); ?>

        <p><?= $e('nom') ?> s'engage à rendre son site accessible, conformément au référentiel général d'amélioration de l'accessibilité (RGAA).</p>

        <h2 class="h4">État de conformité</h2>
        <p>Ce site a été conçu en visant la conformité au RGAA. Un audit de conformité complet n'a pas encore été réalisé : le niveau de conformité n'est donc pas déclaré à ce jour. Les contrôles effectués jusqu'ici sont des vérifications automatiques (Lighthouse et WAVE) et des tests de navigation au clavier.</p>

        <h2 class="h4">Mesures mises en place</h2>
        <ul>
            <li>Structure de page sémantique (en-tête, navigation, contenu principal, pied de page) et hiérarchie de titres cohérente.</li>
            <li>Lien d'évitement « Aller au contenu » en début de page.</li>
            <li>Navigation et formulaires utilisables entièrement au clavier, avec un contour de focus toujours visible.</li>
            <li>Chaque champ de formulaire a un libellé visible ; les messages d'erreur sont reliés au champ concerné et listés en haut du formulaire.</li>
            <li>Textes alternatifs obligatoires pour les images des menus, saisis par l'équipe.</li>
            <li>Couleurs choisies pour respecter les rapports de contraste (4,5 pour 1 pour le texte courant). Aucune information n'est donnée par la couleur seule : les statuts, les notes et les états sont écrits en toutes lettres.</li>
            <li>Mises à jour dynamiques (filtres des menus, estimation du prix) annoncées aux lecteurs d'écran, et site utilisable sans JavaScript pour ses fonctions essentielles.</li>
            <li>Affichage adapté aux petits écrans, texte redimensionnable sans perte de contenu.</li>
            <li>Les graphiques de l'espace d'administration sont doublés d'un tableau reprenant les mêmes données.</li>
        </ul>

        <h2 class="h4">Signaler un problème</h2>
        <p>Si vous rencontrez un défaut d'accessibilité vous empêchant d'accéder à un contenu ou à une fonctionnalité, vous pouvez nous écrire par le <a href="/contact">formulaire de contact</a> ou par téléphone au <?= $e('telephone') ?>. Nous nous efforcerons de vous répondre rapidement et de vous fournir l'information sous une autre forme.</p>

        <h2 class="h4">Voies de recours</h2>
        <p>Si vous constatez un défaut d'accessibilité, que vous nous l'avez signalé et que vous n'obtenez pas de réponse satisfaisante, vous pouvez adresser une réclamation ou une demande de saisine au Défenseur des droits (defenseurdesdroits.fr).</p>
    </div>
</div>
