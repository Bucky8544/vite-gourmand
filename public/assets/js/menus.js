// Filtres dynamiques de la page des menus.
// Amélioration progressive : sans JavaScript, le formulaire fonctionne par rechargement de page.
(function () {
  'use strict';

  const CHAMPS = ['prix_max', 'prix_de', 'prix_a', 'theme', 'regime', 'personnes'];
  const DELAI = 350; // attente (ms) après la dernière frappe
  const IMAGE_SURE = /^\/assets\/[A-Za-z0-9/_.-]+$/;
  const IMAGE_DEFAUT = '/assets/img/placeholder-menu.svg';

  const form = document.getElementById('filtres');
  const zone = document.getElementById('zone-resultats');
  const compteur = document.getElementById('compteur');
  const erreurs = document.getElementById('erreurs-filtres');
  const bouton = document.getElementById('bouton-filtres');
  const aide = document.getElementById('aide-filtres');

  if (!form || !zone || !compteur || !erreurs || !window.fetch || !window.AbortController || !window.URLSearchParams) {
    return; // navigateur trop ancien : le formulaire classique continue de fonctionner
  }

  let minuteur = null;
  let controleur = null;
  let sequence = 0;
  let repli = false; // vrai après une panne : le prochain envoi du formulaire recharge la page

  // ---------- Outils ----------
  function el(balise, classe, texte) {
    const n = document.createElement(balise);
    if (classe) { n.className = classe; }
    if (texte !== undefined && texte !== null) { n.textContent = texte; } // jamais innerHTML : pas de XSS
    return n;
  }

  function prix(valeur) {
    const v = Number(valeur);
    const entier = Math.abs(v - Math.round(v)) < 0.005;
    const decimales = entier ? 0 : 2;
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: decimales, maximumFractionDigits: decimales })
      .format(v) + '\u00A0€';
  }

  function urlImage(url) {
    return typeof url === 'string' && IMAGE_SURE.test(url) && url.indexOf('..') === -1 ? url : IMAGE_DEFAUT;
  }

  function lireFiltres() {
    const params = new URLSearchParams();
    CHAMPS.forEach((nom) => {
      const champ = form.elements[nom];
      if (champ && champ.value.trim() !== '') { params.set(nom, champ.value.trim()); }
    });
    return params;
  }

  function ajouterDescription(champ, id) {
    const ids = (champ.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
    if (ids.indexOf(id) === -1) { ids.push(id); }
    champ.setAttribute('aria-describedby', ids.join(' '));
  }

  function retirerDescription(champ, id) {
    const ids = (champ.getAttribute('aria-describedby') || '').split(/\s+/).filter((x) => x && x !== id);
    if (ids.length) { champ.setAttribute('aria-describedby', ids.join(' ')); } else { champ.removeAttribute('aria-describedby'); }
  }

  // ---------- Erreurs ----------
  function effacerErreurs() {
    erreurs.textContent = '';
    CHAMPS.forEach((nom) => {
      const champ = form.elements[nom];
      if (!champ) { return; }
      champ.classList.remove('is-invalid');
      champ.removeAttribute('aria-invalid');
      retirerDescription(champ, nom + '-erreur');
      const message = document.getElementById(nom + '-erreur');
      if (message) { message.remove(); }
    });
  }

  function afficherErreurs(liste) {
    const alerte = el('div', 'alert alert-danger');
    alerte.setAttribute('role', 'alert');
    alerte.appendChild(el('p', 'fw-bold mb-2', "Certains filtres n'ont pas été appliqués :"));
    const ul = el('ul', 'mb-0');
    Object.keys(liste).forEach((nom) => {
      if (CHAMPS.indexOf(nom) === -1) { return; }
      const message = String(liste[nom]);
      const lien = el('a', null, message);
      lien.href = '#' + nom;
      const li = el('li');
      li.appendChild(lien);
      ul.appendChild(li);

      const champ = form.elements[nom];
      if (champ) {
        champ.classList.add('is-invalid');
        champ.setAttribute('aria-invalid', 'true');
        ajouterDescription(champ, nom + '-erreur');
        const retour = el('div', 'invalid-feedback d-block', message);
        retour.id = nom + '-erreur';
        champ.parentNode.appendChild(retour);
      }
    });
    alerte.appendChild(ul);
    erreurs.appendChild(alerte);
  }

  function afficherPanne() {
    effacerErreurs();
    const alerte = el('div', 'alert alert-danger',
      'La mise à jour des menus a échoué. Cliquez sur « Appliquer les filtres » pour recharger la page.');
    alerte.setAttribute('role', 'alert');
    erreurs.appendChild(alerte);
  }

  // ---------- Affichage des menus ----------
  function carte(m) {
    const li = el('li', 'col-md-6 col-xl-4');
    const article = el('article', 'card menu-card h-100');

    const img = el('img', 'card-img-top');
    img.src = urlImage(m.image && m.image.url);
    img.alt = String((m.image && m.image.alt) || '');
    img.width = 640;
    img.height = 400;
    img.loading = 'lazy';
    article.appendChild(img);

    const corps = el('div', 'card-body d-flex flex-column');
    const pastilles = el('p', 'mb-2');
    pastilles.appendChild(el('span', 'pastille', String(m.theme)));
    if (m.regime !== 'Classique') {
      pastilles.appendChild(document.createTextNode(' '));
      pastilles.appendChild(el('span', 'pastille', String(m.regime)));
    }
    if (Number(m.stock) <= 0) {
      pastilles.appendChild(document.createTextNode(' '));
      pastilles.appendChild(el('span', 'pastille pastille-alerte', 'Indisponible pour le moment'));
    }
    corps.appendChild(pastilles);
    corps.appendChild(el('h3', 'card-title h4', String(m.titre)));
    corps.appendChild(el('p', 'menu-desc', String(m.description)));

    const ligne = el('p', 'menu-prix mt-auto');
    ligne.appendChild(el('span', 'prix', prix(m.prix_minimum)));
    ligne.appendChild(document.createTextNode(' pour ' + Number(m.nombre_personne_minimum) + ' personnes minimum'));
    corps.appendChild(ligne);

    const lien = el('a', 'btn btn-outline-primary w-100', 'Voir le détail');
    lien.href = '/menus/' + Number(m.id);
    lien.appendChild(el('span', 'visually-hidden', ' : ' + String(m.titre)));
    corps.appendChild(lien);

    article.appendChild(corps);
    li.appendChild(article);
    return li;
  }

  function afficher(menus) {
    const n = menus.length;
    compteur.textContent = n === 0 ? 'Aucun menu trouvé' : (n === 1 ? '1 menu trouvé' : n + ' menus trouvés');
    zone.textContent = '';

    if (n === 0) {
      const p = el('p');
      p.appendChild(document.createTextNode('Aucun menu ne correspond à vos critères. '));
      const lien = el('a', null, 'Réinitialiser les filtres');
      lien.href = '/menus';
      lien.setAttribute('data-reinitialiser', '');
      p.appendChild(lien);
      zone.appendChild(p);
      return;
    }

    const ul = el('ul', 'row g-4 list-unstyled p-0 m-0');
    ul.id = 'liste-menus';
    menus.forEach((m) => { ul.appendChild(carte(m)); });
    zone.appendChild(ul);
  }

  function majBouton(params) {
    if (!bouton) { return; }
    let nb = 0;
    params.forEach(() => { nb += 1; });
    bouton.textContent = 'Filtrer les menus' + (nb > 0 ? ' (' + nb + ')' : '');
  }

  function majUrl(qs) {
    try { history.replaceState(null, '', '/menus' + (qs ? '?' + qs : '')); } catch (e) { /* sans importance */ }
  }

  // ---------- Requête ----------
  function mettreAJour() {
    const params = lireFiltres();
    const qs = params.toString();

    if (controleur) { controleur.abort(); } // la requête précédente est abandonnée
    controleur = new AbortController();
    const numero = ++sequence;

    zone.setAttribute('aria-busy', 'true');
    zone.classList.add('chargement');

    fetch('/api/menus' + (qs ? '?' + qs : ''), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal: controleur.signal
    })
      .then((reponse) => reponse.json().then((donnees) => ({ statut: reponse.status, ok: reponse.ok, donnees })))
      .then((res) => {
        if (numero !== sequence) { return; } // une requête plus récente a pris le relais
        repli = false;
        effacerErreurs();
        if (res.statut === 422 && res.donnees && res.donnees.errors) {
          afficherErreurs(res.donnees.errors);
        } else if (res.ok && res.donnees && Array.isArray(res.donnees.menus)) {
          afficher(res.donnees.menus);
          majBouton(params);
          majUrl(qs);
        } else {
          throw new Error('Réponse inattendue');
        }
      })
      .catch((erreur) => {
        if (erreur && erreur.name === 'AbortError') { return; }
        if (numero !== sequence) { return; }
        repli = true;
        afficherPanne();
      })
      .then(() => {
        if (numero === sequence) {
          zone.removeAttribute('aria-busy');
          zone.classList.remove('chargement');
        }
      });
  }

  function planifier() {
    clearTimeout(minuteur);
    minuteur = setTimeout(mettreAJour, DELAI);
  }

  function vider() {
    CHAMPS.forEach((nom) => {
      const champ = form.elements[nom];
      if (champ) { champ.value = ''; }
    });
  }

  // ---------- Événements ----------
  form.addEventListener('input', (e) => {
    if (e.target.tagName === 'INPUT') { planifier(); } // champs numériques : on attend la fin de la saisie
  });

  form.addEventListener('change', (e) => {
    if (e.target.tagName === 'SELECT') { clearTimeout(minuteur); mettreAJour(); }
  });

  form.addEventListener('submit', (e) => {
    if (repli) { return; } // après une panne : envoi classique, la page se recharge
    e.preventDefault();
    clearTimeout(minuteur);
    mettreAJour();
  });

  document.addEventListener('click', (e) => {
    const cible = e.target.closest ? e.target.closest('[data-reinitialiser]') : null;
    if (!cible) { return; }
    e.preventDefault();
    const dansLesResultats = zone.contains(cible); // ce lien va disparaître : on replace le focus
    vider();
    clearTimeout(minuteur);
    mettreAJour();
    if (dansLesResultats && form.elements.prix_max) { form.elements.prix_max.focus(); }
  });

  if (aide) { aide.classList.remove('d-none'); } // l'information n'a de sens que si le JavaScript est actif
})();
