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

    'hebergeur_nom' => 'À renseigner au moment du déploiement',
    'hebergeur_adresse' => 'À renseigner au moment du déploiement',
    'hebergeur_pays' => 'À renseigner au moment du déploiement',

    'mediateur' => 'À renseigner : médiateur de la consommation choisi par l\'entreprise (nom, adresse postale et site internet)',
    'paiement' => 'Les modalités de paiement sont communiquées par l\'entreprise lors de la confirmation de la commande.',
    'smtp_nom' => 'un prestataire d\'envoi d\'e-mails',

    'cgv_date' => '10 novembre 2026',
    'politique_date' => '10 novembre 2026',
];
