<?php
declare(strict_types=1);

/**
 * Informations de l'entreprise, utilisées par les mentions légales, les CGV, la politique de confidentialité et le contact.
 * Les valeurs ci-dessous sont des EXEMPLES FICTIFS : remplacez-les par les vraies informations,
 * puis mettez 'fictif' à false pour retirer le bandeau d'avertissement des pages légales.
 */
return [
    'fictif' => true,

    'nom' => 'Vite & Gourmand',
    'forme' => 'Société à responsabilité limitée',
    'capital' => '10 000 euros',
    'siren' => '000 000 000',
    'adresse' => "12 rue de l'Exemple, 33000 Bordeaux",
    'telephone' => '05 36 49 00 00',
    'email' => 'contact@vite-gourmand.test',
    'directeur' => 'Julie et José, cogérants',

    'hebergeur_nom' => 'Heroku (Salesforce, Inc.) pour l\'application et la base MySQL (JawsDB) ; MongoDB Atlas (MongoDB, Inc.) pour la base des statistiques',
    'hebergeur_adresse' => 'Salesforce, Inc., 415 Mission Street, 3rd Floor, San Francisco, CA 94105, États-Unis ; MongoDB, Inc., 1633 Broadway, 38th Floor, New York, NY 10019, États-Unis',
    'hebergeur_pays' => 'application et base MySQL : git branch --show-current ; base MongoDB : git status --short',

    'mediateur' => 'À renseigner : médiateur de la consommation choisi par l\'entreprise (nom, adresse postale et site internet)',
    'paiement' => 'Les modalités de paiement sont communiquées par l\'entreprise lors de la confirmation de la commande.',
    'smtp_nom' => 'Brevo (service d\'envoi d\'e-mails)',
    'concepteur' => 'un projet de formation (titre professionnel Développeur Web et Web Mobile)',

    'cgv_date' => '10 novembre 2026',
    'politique_date' => '16 novembre 2026',
];
