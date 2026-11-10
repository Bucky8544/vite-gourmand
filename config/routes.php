<?php
declare(strict_types=1);

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\MenuController;
use App\Controller\OrderController;
use App\Controller\PasswordController;
use App\Controller\SpaceController;
use App\Controller\StaffCatalogController;
use App\Controller\StaffController;
use App\Controller\StaffMenuController;
use App\Controller\StaffReviewController;
use App\Controller\UserSpaceController;
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/sante', [HealthController::class, 'index']);

    $r->get('/menus', [MenuController::class, 'index']);
    $r->get('/menus/{id}', [MenuController::class, 'show']);
    $r->get('/api/menus', [MenuController::class, 'api']);

    $r->get('/commande', [OrderController::class, 'create']);
    $r->post('/commande/recapitulatif', [OrderController::class, 'recap']);
    $r->post('/commande/modifier', [OrderController::class, 'modifier']);
    $r->post('/commande', [OrderController::class, 'store']);
    $r->get('/commande/confirmation/{numero}', [OrderController::class, 'confirmation']);
    $r->get('/api/commande/prix', [OrderController::class, 'price']);

    $r->get('/inscription', [AuthController::class, 'showRegister']);
    $r->post('/inscription', [AuthController::class, 'register']);
    $r->get('/connexion', [AuthController::class, 'showLogin']);
    $r->post('/connexion', [AuthController::class, 'login']);
    $r->post('/deconnexion', [AuthController::class, 'logout']);

    $r->get('/mot-de-passe-oublie', [PasswordController::class, 'showForgot']);
    $r->post('/mot-de-passe-oublie', [PasswordController::class, 'forgot']);
    $r->get('/reinitialisation/{token}', [PasswordController::class, 'showReset']);
    $r->post('/reinitialisation/{token}', [PasswordController::class, 'reset']);

    // Espace utilisateur
    $r->get('/mon-espace', [UserSpaceController::class, 'index']);
    $r->get('/mon-espace/profil', [UserSpaceController::class, 'profil']);
    $r->post('/mon-espace/profil', [UserSpaceController::class, 'profilEnregistrer']);
    $r->get('/mon-espace/commandes/{numero}', [UserSpaceController::class, 'show']);
    $r->get('/mon-espace/commandes/{numero}/modifier', [UserSpaceController::class, 'modifierForm']);
    $r->post('/mon-espace/commandes/{numero}/modifier', [UserSpaceController::class, 'modifierRecap']);
    $r->post('/mon-espace/commandes/{numero}/modifier/retour', [UserSpaceController::class, 'modifierRetour']);
    $r->post('/mon-espace/commandes/{numero}/modifier/confirmer', [UserSpaceController::class, 'modifierConfirmer']);
    $r->get('/mon-espace/commandes/{numero}/annuler', [UserSpaceController::class, 'annulerForm']);
    $r->post('/mon-espace/commandes/{numero}/annuler', [UserSpaceController::class, 'annuler']);
    $r->post('/mon-espace/commandes/{numero}/avis', [UserSpaceController::class, 'avis']);

    // Espace employé (et administrateur) : commandes
    $r->get('/employe', [StaffController::class, 'index']);
    $r->get('/employe/commandes/{numero}', [StaffController::class, 'show']);
    $r->post('/employe/commandes/{numero}/statut', [StaffController::class, 'changerStatut']);
    $r->post('/employe/commandes/{numero}/annuler', [StaffController::class, 'annuler']);

    // Avis
    $r->get('/employe/avis', [StaffReviewController::class, 'index']);
    $r->post('/employe/avis/{id}/decision', [StaffReviewController::class, 'decider']);

    // Menus ('nouveau' avant '{id}' : la première route qui correspond gagne)
    $r->get('/employe/menus', [StaffMenuController::class, 'index']);
    $r->get('/employe/menus/nouveau', [StaffMenuController::class, 'nouveau']);
    $r->post('/employe/menus/nouveau', [StaffMenuController::class, 'creer']);
    $r->get('/employe/menus/{id}', [StaffMenuController::class, 'modifierForm']);
    $r->post('/employe/menus/{id}', [StaffMenuController::class, 'modifier']);
    $r->post('/employe/menus/{id}/actif', [StaffMenuController::class, 'basculer']);
    $r->post('/employe/menus/{id}/supprimer', [StaffMenuController::class, 'supprimer']);

    // Plats
    $r->get('/employe/plats', [StaffCatalogController::class, 'plats']);
    $r->get('/employe/plats/nouveau', [StaffCatalogController::class, 'platNouveau']);
    $r->post('/employe/plats/nouveau', [StaffCatalogController::class, 'platCreer']);
    $r->get('/employe/plats/{id}', [StaffCatalogController::class, 'platModifierForm']);
    $r->post('/employe/plats/{id}', [StaffCatalogController::class, 'platModifier']);
    $r->post('/employe/plats/{id}/supprimer', [StaffCatalogController::class, 'platSupprimer']);

    // Thèmes et régimes
    $r->get('/employe/categories', [StaffCatalogController::class, 'categories']);
    $r->post('/employe/categories/{type}/ajouter', [StaffCatalogController::class, 'categorieAjouter']);
    $r->post('/employe/categories/{type}/{id}/renommer', [StaffCatalogController::class, 'categorieRenommer']);
    $r->post('/employe/categories/{type}/{id}/supprimer', [StaffCatalogController::class, 'categorieSupprimer']);

    // Horaires
    $r->get('/employe/horaires', [StaffCatalogController::class, 'horaires']);
    $r->post('/employe/horaires', [StaffCatalogController::class, 'horairesEnregistrer']);

    // Espace administrateur
    $r->get('/admin', [AdminController::class, 'index']);
    $r->post('/admin/statistiques/resynchroniser', [AdminController::class, 'resynchroniser']);
    $r->get('/admin/employes', [AdminController::class, 'employes']);
    $r->post('/admin/employes', [AdminController::class, 'employeCreer']);
    $r->post('/admin/employes/{id}/actif', [AdminController::class, 'employeBasculer']);
};
