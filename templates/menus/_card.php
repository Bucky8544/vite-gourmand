<article class="card menu-card h-100">
    <img class="card-img-top" src="<?= $this->e($this->menuImage($menu['image_url'])) ?>"
         alt="<?= $this->e($menu['image_alt'] ?? '') ?>" width="640" height="400" loading="lazy">
    <div class="card-body d-flex flex-column">
        <p class="mb-2">
            <span class="pastille"><?= $this->e($menu['theme']) ?></span>
            <?php if ($menu['regime'] !== 'Classique'): ?>
                <span class="pastille"><?= $this->e($menu['regime']) ?></span>
            <?php endif; ?>
            <?php if ((int) $menu['stock'] <= 0): ?>
                <span class="pastille pastille-alerte">Indisponible pour le moment</span>
            <?php endif; ?>
        </p>
        <h3 class="card-title h4"><?= $this->e($menu['titre']) ?></h3>
        <p class="menu-desc"><?= $this->e($menu['description']) ?></p>
        <p class="menu-prix mt-auto">
            <span class="prix"><?= $this->price($menu['prix_minimum']) ?></span>
            pour <?= (int) $menu['nombre_personne_minimum'] ?> personnes minimum
        </p>
        <a class="btn btn-outline-primary w-100" href="/menus/<?= (int) $menu['id'] ?>">
            Voir le détail<span class="visually-hidden"> : <?= $this->e($menu['titre']) ?></span>
        </a>
    </div>
</article>
