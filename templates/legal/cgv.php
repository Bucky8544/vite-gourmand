<?php $e = fn (string $cle): string => $this->e(\App\Core\Entreprise::get($cle)); ?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto legal">
        <h1>Conditions générales de vente</h1>
        <p class="text-muted">Version du <?= $e('cgv_date') ?></p>
        <?php $this->partial('legal/_fictif'); ?>

        <h2 class="h4">1. Objet</h2>
        <p>Les présentes conditions générales de vente régissent les commandes de menus et de prestations de traiteur passées sur ce site auprès de <?= $e('nom') ?> (« l'entreprise »), par toute personne agissant en tant que consommateur (« le client »). Passer commande implique l'acceptation sans réserve de ces conditions.</p>

        <h2 class="h4">2. Identification de l'entreprise</h2>
        <p><?= $e('nom') ?>, <?= $e('forme') ?> au capital de <?= $e('capital') ?>, SIREN <?= $e('siren') ?>, <?= $e('adresse') ?>. Téléphone : <?= $e('telephone') ?>. E-mail : <?= $e('email') ?>. Voir aussi les <a href="/mentions-legales">mentions légales</a>.</p>

        <h2 class="h4">3. Menus et prix</h2>
        <p>Chaque menu est présenté avec sa description, sa composition, un nombre minimum de personnes et le prix correspondant à ce minimum. Les prix sont indiqués en euros toutes taxes comprises.</p>
        <ul>
            <li>Le prix pour un nombre de personnes supérieur au minimum est calculé proportionnellement au prix du menu pour le minimum.</li>
            <li>Une remise de 10 % sur le prix du menu est accordée pour toute commande d'au moins cinq personnes de plus que le minimum indiqué pour le menu.</li>
            <li>Une commande ne peut pas être passée pour un nombre de personnes inférieur au minimum du menu.</li>
            <li>La livraison est offerte dans la ville de Bordeaux. Hors de Bordeaux, elle est facturée 5 euros, majorés de 0,59 euro par kilomètre parcouru entre l'entreprise et le lieu de la prestation. Le détail du prix, livraison comprise, est affiché avant la validation de la commande.</li>
        </ul>

        <h2 class="h4">4. Conditions propres à chaque menu</h2>
        <p>Chaque menu a ses propres conditions (délai de commande avant la prestation, précautions de stockage, etc.). Elles sont affichées en évidence sur la page du menu et rappelées avant la validation de la commande : le client déclare les avoir lues en cochant la case prévue à cet effet.</p>

        <h2 class="h4">5. Allergènes</h2>
        <p>Les allergènes présents dans chaque plat sont indiqués sur la page du menu. Le client doit informer l'entreprise de toute allergie ou intolérance avant la commande. L'entreprise ne peut pas garantir l'absence totale de traces d'allergènes dans ses préparations.</p>

        <h2 class="h4">6. Commande</h2>
        <p>La commande nécessite un compte client. Le client choisit son menu, le nombre de personnes, la date, l'heure souhaitée et le lieu de la prestation, vérifie le récapitulatif et le prix, puis confirme. Un e-mail de confirmation lui est envoyé. La commande est prise en compte lorsque l'équipe de l'entreprise l'accepte : le client suit son état depuis son espace, avec la date et l'heure de chaque changement.</p>
        <p>L'entreprise peut refuser une commande lorsque le menu n'est plus disponible ou lorsque la prestation demandée est impossible.</p>

        <h2 class="h4">7. Modification et annulation par le client</h2>
        <p>Tant que la commande n'a pas été acceptée par l'équipe, le client peut la modifier (sauf le choix du menu) ou l'annuler depuis son espace. Après son acceptation, ces opérations ne sont plus possibles en ligne : le client doit contacter l'entreprise, qui étudie sa demande.</p>

        <h2 class="h4">8. Annulation ou modification par l'entreprise</h2>
        <p>L'entreprise ne modifie ni n'annule une commande sans avoir préalablement contacté le client, par téléphone ou par e-mail, et sans lui en indiquer le motif.</p>

        <h2 class="h4">9. Livraison</h2>
        <p>La livraison est réalisée à l'adresse et à l'heure indiquées par le client, dans les horaires d'ouverture de l'entreprise affichés sur le site. Le client s'assure qu'une personne est présente pour réceptionner la commande, et respecte les précautions de conservation indiquées pour le menu.</p>

        <h2 class="h4">10. Prêt de matériel</h2>
        <p>Du matériel peut être prêté au client pour la prestation. Le client le restitue dans un délai de <strong>10 jours ouvrés</strong> après la livraison, en prenant contact avec l'entreprise. À défaut de restitution dans ce délai, des frais de <strong>600 euros</strong> sont facturés au client. Un e-mail de rappel lui est envoyé dès que la commande passe à l'état « en attente du retour de matériel ».</p>

        <h2 class="h4">11. Paiement</h2>
        <p><?= $e('paiement') ?></p>

        <h2 class="h4">12. Droit de rétractation</h2>
        <p>Conformément à l'article L221-28 du Code de la consommation, le droit de rétractation ne s'applique pas aux contrats de fourniture de services de restauration devant être fournis à une date déterminée, ni à la fourniture de biens susceptibles de se détériorer ou de se périmer rapidement. Le client dispose néanmoins des possibilités de modification et d'annulation décrites à l'article 7.</p>

        <h2 class="h4">13. Réclamations et médiation</h2>
        <p>Toute réclamation peut être adressée à l'entreprise par le <a href="/contact">formulaire de contact</a> ou à l'adresse indiquée à l'article 2. En cas de litige non résolu, le client peut recourir gratuitement à un médiateur de la consommation : <?= $e('mediateur') ?>.</p>

        <h2 class="h4">14. Données personnelles</h2>
        <p>Les données personnelles du client sont traitées selon les modalités décrites dans la <a href="/confidentialite">politique de confidentialité</a>.</p>

        <h2 class="h4">15. Responsabilité et force majeure</h2>
        <p>L'entreprise n'est pas responsable de l'inexécution de ses obligations en cas de force majeure (événement imprévisible, irrésistible et extérieur). Elle en informe le client dans les meilleurs délais.</p>

        <h2 class="h4">16. Droit applicable et litiges</h2>
        <p>Les présentes conditions sont soumises au droit français. En cas de litige, le client peut saisir la juridiction compétente, conformément aux règles applicables aux consommateurs.</p>
    </div>
</div>
