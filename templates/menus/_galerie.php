<?php
$n = count($images);
$src = $this->menuImage($images[0]['url'] ?? null);
$alt = $images[0]['texte_alternatif'] ?? '';
?>
<div class="galerie">
    <figure class="galerie-principale m-0">
        <img id="galerie-image" src="<?= $this->e($src) ?>" alt="<?= $this->e($alt) ?>" width="960" height="600">
    </figure>

    <?php if ($n > 1): ?>
        <p id="galerie-statut" class="visually-hidden" role="status"></p>
        <ul id="galerie-vignettes" class="galerie-vignettes list-unstyled" aria-label="Photos du menu">
            <?php foreach ($images as $i => $image): ?>
                <?php $url = $this->menuImage($image['url']); ?>
                <li>
                    <a class="vignette" href="<?= $this->e($url) ?>" data-galerie
                       data-alt="<?= $this->e($image['texte_alternatif']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                        <img src="<?= $this->e($url) ?>" alt="" width="320" height="200" loading="lazy">
                        <span class="visually-hidden">Afficher la photo <?= $i + 1 ?> sur <?= $n ?> : <?= $this->e($image['texte_alternatif']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
