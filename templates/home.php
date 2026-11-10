<?php
$moyenne = number_format($statsAvis['moyenne'], 1, ',', '');
?>
<section class="hero" aria-labelledby="titre-accueil">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1 id="titre-accueil">Vite &amp; Gourmand, le traiteur qui met de la fête dans vos repas</h1>
                <p class="lead">Depuis 25 ans à Bordeaux, Julie et José préparent vos repas de Noël, de Pâques et de tous vos événements.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary btn-lg" href="/menus">Découvrir nos menus</a>
                    <a class="btn btn-outline-primary btn-lg" href="/contact">Nous contacter</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img class="img-fluid image-arrondie" src="/assets/img/accueil-banniere.jpg" alt="Table en bois dressée pour les fêtes : bûche, volaille aux marrons, brioche de Pâques, chou-fleur rôti et petits fours, devant un pont sur le fleuve." width="1200" height="655" fetchpriority="high" decoding="async">
            </div>
        </div>
    </div>
</section>

<section aria-labelledby="titre-presentation">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <img class="img-fluid image-arrondie" src="/assets/img/accueil-presentation.jpg" alt="Événement en plein air devant des vignes et un château : table garnie de fromages, huîtres, magret et verrines, serveurs et invités, ardoise « Nos saveurs, votre événement Bordeaux »." width="1200" height="655" loading="lazy" decoding="async">
            </div>
            <div class="col-lg-7">
                <p class="text-muted fw-bold mb-1">Présentation</p>
                <h2 id="titre-presentation">Notre engagement, votre plaisir</h2>
                <p>Julie et José cuisinent pour vos repas de famille comme pour vos grands événements. Les menus évoluent au fil des saisons et des fêtes.</p>
                <ul class="liste-coches list-unstyled">
                    <li>Des menus qui changent à chaque saison</li>
                    <li>Livraison à Bordeaux et au-delà</li>
                    <li>Prestations pour tous vos événements</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="bande-sable" aria-labelledby="titre-equipe">
    <div class="container py-5">
        <h2 id="titre-equipe" class="text-center">Une équipe de professionnels à votre service</h2>
        <p class="text-center lead mb-4">Le même soin, de la cuisine à la livraison.</p>
        <div class="row g-4">
            <?php foreach ([
                ['Savoir-faire', "Julie et José préparent chaque prestation avec le soin de 25 ans d'expérience.", '/assets/img/savoir-faire.jpg'],
                ['Menus de saison', "Une carte en constante évolution, pensée pour chaque fête de l'année.", '/assets/img/placeholder-personne.svg'],
                ['Service soigné', "Livraison à l'heure choisie, au lieu de votre événement.", '/assets/img/placeholder-personne.svg'],
            ] as [$titre, $texte, $image]): ?>
                <div class="col-md-4">
                    <article class="carte-espace h-100">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img class="avatar" src="<?= $this->e($image) ?>" alt="" width="96" height="96" loading="lazy">
                            <h3 class="h5 mb-0"><?= $this->e($titre) ?></h3>
                        </div>
                        <p class="mb-0"><?= $this->e($texte) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section aria-labelledby="titre-avis">
    <div class="container py-5">
        <h2 id="titre-avis" class="text-center">Ce que disent nos clients</h2>

        <?php if ($statsAvis['count'] > 0): ?>
            <p class="text-center lead">Note moyenne : <strong><?= $this->e($moyenne) ?> sur 5</strong> (<?= (int) $statsAvis['count'] ?> avis)</p>
        <?php endif; ?>

        <?php if ($avis === []): ?>
            <p class="text-center">Les premiers avis de nos clients seront publiés ici.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($avis as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <figure class="avis-carte h-100">
                            <p class="mb-2">
                                <span class="etoiles" aria-hidden="true"><?= str_repeat('★', $a['note']) . str_repeat('☆', 5 - $a['note']) ?></span>
                                <span class="ms-2 small text-muted">Note : <?= (int) $a['note'] ?> sur 5</span>
                            </p>
                            <blockquote class="mb-3"><p class="mb-0">« <?= nl2br($this->e($a['commentaire'])) ?> »</p></blockquote>
                            <figcaption class="fw-bold"><?= $this->e($a['auteur']) ?></figcaption>
                        </figure>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
