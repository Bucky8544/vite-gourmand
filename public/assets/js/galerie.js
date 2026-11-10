// Galerie d'images de la vue détaillée d'un menu.
// Amélioration progressive : sans JavaScript, chaque vignette est un lien vers l'image.
(function () {
  'use strict';

  const image = document.getElementById('galerie-image');
  const liste = document.getElementById('galerie-vignettes');
  const statut = document.getElementById('galerie-statut');

  if (!image || !liste) { return; }

  const IMAGE_SURE = /^\/assets\/[A-Za-z0-9/_.-]+$/;
  const vignettes = Array.prototype.slice.call(liste.querySelectorAll('a[data-galerie]'));

  function afficher(lien) {
    const url = lien.getAttribute('href') || '';
    if (!IMAGE_SURE.test(url) || url.indexOf('..') !== -1) { return; } // défense en profondeur

    image.src = url;
    image.alt = lien.getAttribute('data-alt') || '';

    vignettes.forEach((v) => {
      if (v === lien) { v.setAttribute('aria-current', 'true'); } else { v.removeAttribute('aria-current'); }
    });

    if (statut) {
      statut.textContent = 'Photo ' + (vignettes.indexOf(lien) + 1) + ' sur ' + vignettes.length + ' affichée.';
    }
  }

  liste.addEventListener('click', (e) => {
    const lien = e.target.closest ? e.target.closest('a[data-galerie]') : null;
    if (!lien || !liste.contains(lien)) { return; }
    e.preventDefault();
    afficher(lien);
  });
})();
