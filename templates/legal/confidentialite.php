<?php $e = fn (string $cle): string => $this->e(\App\Core\Entreprise::get($cle)); ?>
<div class="container py-5">
    <div class="col-lg-9 mx-auto legal">
        <h1>Politique de confidentialité</h1>
        <p class="text-muted">Version du <?= $e('politique_date') ?></p>
        <?php $this->partial('legal/_fictif'); ?>

        <p>Cette page explique quelles données personnelles <?= $e('nom') ?> collecte sur ce site, pourquoi, combien de temps elles sont conservées, et comment exercer vos droits.</p>

        <h2 class="h4">Responsable du traitement</h2>
        <p><?= $e('nom') ?>, <?= $e('adresse') ?>. Contact pour toute question sur vos données : <a href="mailto:<?= $e('email') ?>"><?= $e('email') ?></a>.</p>

        <h2 class="h4">Données collectées et finalités</h2>
        <div class="table-responsive">
            <table class="table align-top">
                <caption class="visually-hidden">Données collectées, finalités et bases légales</caption>
                <thead><tr><th scope="col">Données</th><th scope="col">Pourquoi</th><th scope="col">Base légale</th></tr></thead>
                <tbody>
                    <tr>
                        <th scope="row">Compte : nom, prénom, e-mail, numéro de GSM, adresse postale, mot de passe (conservé uniquement sous forme chiffrée), date d'acceptation de cette politique</th>
                        <td>Créer et gérer votre compte, vous identifier, vous envoyer les e-mails liés à votre compte</td>
                        <td>Exécution du contrat</td>
                    </tr>
                    <tr>
                        <th scope="row">Commandes : menu, nombre de personnes, adresse, date et heure de la prestation, montants, historique des statuts, contact et motif en cas d'annulation par l'équipe</th>
                        <td>Préparer, livrer et facturer votre commande, vous tenir informé de son avancement</td>
                        <td>Exécution du contrat ; obligation légale (comptabilité)</td>
                    </tr>
                    <tr>
                        <th scope="row">Avis : note et commentaire</th>
                        <td>Publier votre avis sur le site, après validation par l'équipe, signé de votre prénom et de l'initiale de votre nom</td>
                        <td>Votre démarche volontaire de déposer un avis, après information</td>
                    </tr>
                    <tr>
                        <th scope="row">Message de contact : e-mail, objet, message</th>
                        <td>Répondre à votre demande</td>
                        <td>Intérêt légitime à répondre aux demandes reçues</td>
                    </tr>
                    <tr>
                        <th scope="row">Données techniques : cookie de session, journaux du serveur</th>
                        <td>Assurer le fonctionnement et la sécurité du site</td>
                        <td>Intérêt légitime (sécurité)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>Les données facultatives sont signalées comme telles. Les statistiques de commandes consultées par l'administrateur sont agrégées par menu et ne contiennent aucune donnée personnelle.</p>

        <h2 class="h4">Destinataires</h2>
        <p>Vos données sont accessibles aux seules personnes habilitées de l'entreprise (équipe et administrateur), pour traiter vos commandes. Elles ne sont ni vendues ni cédées. Nous faisons appel à des prestataires, uniquement pour fournir le service :</p>
        <ul>
            <li><strong>L'hébergeur du site</strong> (<?= $e('hebergeur_nom') ?>) : hébergement des données. Pays d'hébergement : <?= $e('hebergeur_pays') ?>.</li>
            <li><strong><?= $e('smtp_nom') ?></strong> : envoi des e-mails (confirmation de compte, de commande, changements d'état).</li>
            <li><strong>Le service public de géolocalisation de l'IGN (Géoplateforme)</strong> : l'adresse de la prestation lui est transmise pour calculer la distance de livraison et les frais correspondants. Ce service n'exige ni compte ni identifiant.</li>
        </ul>

        <h2 class="h4">Durées de conservation</h2>
        <ul>
            <li><strong>Compte</strong> : tant que vous ne demandez pas sa suppression.</li>
            <li><strong>Commandes et factures</strong> : 10 ans, conformément aux obligations comptables.</li>
            <li><strong>Avis</strong> : tant qu'ils sont publiés, ou jusqu'à votre demande de retrait.</li>
            <li><strong>Messages de contact</strong> : ils ne sont pas enregistrés sur le site, seulement reçus par e-mail ; ils sont supprimés de la messagerie au plus tard 12 mois après le dernier échange.</li>
            <li><strong>Cookie de session</strong> : supprimé à la fermeture du navigateur.</li>
        </ul>

        <h2 class="h4">Vos droits</h2>
        <p>Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation du traitement, d'opposition et de portabilité de vos données. Vous pouvez modifier vos informations personnelles directement depuis <a href="/mon-espace/profil">votre espace</a>. Pour toute autre demande (suppression du compte, copie de vos données, retrait d'un avis), écrivez à <a href="mailto:<?= $e('email') ?>"><?= $e('email') ?></a> : nous vous répondrons dans un délai d'un mois. Certaines données (commandes) doivent être conservées pour respecter nos obligations légales.</p>
        <p>Si vous estimez, après nous avoir contactés, que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la Commission nationale de l'informatique et des libertés (CNIL), cnil.fr.</p>

        <h2 class="h4">Cookies</h2>
        <p>Ce site n'utilise qu'<strong>un seul cookie, technique</strong> : le cookie de session (<code>VGSESSID</code>). Il permet de rester connecté, de protéger les formulaires contre les envois frauduleux et de conserver votre panier de commande en cours. Il est indispensable au fonctionnement du site : il ne nécessite donc pas votre consentement. Le site n'utilise aucun cookie publicitaire, aucun outil de mesure d'audience et aucun contenu hébergé chez un tiers (polices et bibliothèques sont servies par le site lui-même).</p>

        <h2 class="h4">Sécurité</h2>
        <p>Les mots de passe sont conservés sous forme chiffrée irréversible. L'accès aux données est limité par rôle (client, employé, administrateur), les échanges avec le site sont chiffrés (HTTPS) et les formulaires sont protégés contre les envois frauduleux.</p>

        <h2 class="h4">Évolution de cette politique</h2>
        <p>Cette politique peut être mise à jour ; la date de version figure en haut de la page.</p>
    </div>
</div>
