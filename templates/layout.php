<?php
$nav = [['/', 'Accueil'], ['/menus', 'Nos menus'], ['/contact', 'Contact']];
$current = $current ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e(isset($title) ? $title . ' - Vite & Gourmand' : 'Vite & Gourmand') ?></title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#contenu">Aller au contenu</a>

<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Navigation principale">
        <div class="container">
            <a class="navbar-brand" href="/">Vite &amp;<br>Gourmand</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navPrincipale" aria-controls="navPrincipale"
                    aria-expanded="false" aria-label="Ouvrir le menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navPrincipale">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <?php foreach ($nav as [$href, $label]): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $current === $href ? ' active' : '' ?>"
                               href="<?= $this->e($href) ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= $this->e($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-item"><a class="btn btn-primary" href="/connexion">Connexion</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="contenu" tabindex="-1">
    <?php foreach (\App\Core\Session::pullFlashes() as $flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $this->e($flash['type']) ?>" role="status"><?= $this->e($flash['message']) ?></div>
        </div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container py-4">
        <div class="row gy-4">
            <div class="col-md-4">
                <p class="footer-brand">Vite &amp; Gourmand</p>
                <p>Traiteur à Bordeaux depuis 25 ans. Repas de fête et événements.</p>
            </div>
            <div class="col-md-4">
                <h2 class="footer-title">Horaires</h2>
                <ul class="list-unstyled mb-0">
                    <li>Lundi - Vendredi : 9h - 19h</li>
                    <li>Samedi : 9h - 18h</li>
                    <li>Dimanche : 10h - 14h</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h2 class="footer-title">Informations</h2>
                <ul class="list-unstyled mb-0">
                    <li><a href="/mentions-legales">Mentions légales</a></li>
                    <li><a href="/cgv">Conditions générales de vente</a></li>
                    <li><a href="/confidentialite">Politique de confidentialité</a></li>
                    <li><a href="/contact">Contact</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="mb-0 small">&copy; 2026 Vite &amp; Gourmand</p>
    </div>
</footer>

<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
