// Formulaire de commande : mise à jour du prix du menu selon le nombre de personnes.
// Amélioration progressive : sans JavaScript, le prix détaillé s'affiche à l'étape suivante.
(function () {
  'use strict';

  const menu = document.getElementById('menu_id');
  const personnes = document.getElementById('personnes');
  const estimation = document.getElementById('estimation');
  const aidePersonnes = document.getElementById('aide-personnes');
  const aideDelai = document.getElementById('aide-delai');
  const date = document.getElementById('date_prestation');

  if (!menu || !personnes || !estimation || !window.fetch || !window.AbortController) { return; }

  const DELAI = 300;
  const format = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  let minuteur = null;
  let controleur = null;
  let sequence = 0;

  function euros(centimes) { return format.format(centimes / 100) + '\u00A0€'; }

  function ligne(texte, classe) {
    const p = document.createElement('p');
    p.className = classe || 'mb-1';
    p.textContent = texte; // jamais innerHTML
    return p;
  }

  function jourIso(d) {
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const j = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + j;
  }

  function optionChoisie() { return menu.options ? menu.options[menu.selectedIndex] : null; } // champ caché : pas d'options

  function majContraintes() {
    const option = optionChoisie();
    const min = option ? parseInt(option.getAttribute('data-min'), 10) : NaN;
    const delai = option ? parseInt(option.getAttribute('data-delai'), 10) : NaN;

    if (!isNaN(min)) {
      personnes.min = String(min);
      if (aidePersonnes) {
        aidePersonnes.textContent = 'Minimum pour ce menu : ' + min + ' personnes. Remise de 10 % à partir de ' + (min + 5) + ' personnes.';
      }
    }
    if (!isNaN(delai)) {
      const limite = new Date();
      limite.setDate(limite.getDate() + delai);
      if (date) { date.min = jourIso(limite); }
      if (aideDelai) { aideDelai.textContent = 'Ce menu se commande au moins ' + delai + ' jours avant la prestation.'; }
    }
  }

  function afficher(noeuds) {
    estimation.textContent = '';
    noeuds.forEach((n) => { estimation.appendChild(n); });
  }

  function estimer() {
    const idMenu = menu.value.trim();
    const n = personnes.value.trim();

    if (controleur) { controleur.abort(); }
    if (idMenu === '' || n === '') {
      afficher([ligne('Choisissez un menu et un nombre de personnes pour voir le prix du menu.', 'mb-0')]);
      return;
    }

    controleur = new AbortController();
    const numero = ++sequence;
    estimation.setAttribute('aria-busy', 'true');

    const qs = new URLSearchParams({ menu: idMenu, personnes: n }).toString();
    fetch('/api/commande/prix?' + qs, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controleur.signal })
      .then((r) => r.json().then((d) => ({ ok: r.ok, statut: r.status, donnees: d })))
      .then((res) => {
        if (numero !== sequence) { return; }
        if (res.statut === 422 && res.donnees && res.donnees.errors) {
          const message = res.donnees.errors.personnes || res.donnees.errors.menu || 'Vérifiez le menu et le nombre de personnes.';
          afficher([ligne(String(message), 'estimation-erreur mb-0')]);
          return;
        }
        if (!res.ok || !res.donnees || typeof res.donnees.menu_centimes !== 'number') { throw new Error('Réponse inattendue'); }

        const d = res.donnees;
        const lignes = [ligne('Menu pour ' + d.personnes + ' personnes : ' + euros(d.brut_centimes))];
        if (d.remise_centimes > 0) {
          lignes.push(ligne('Remise de 10 % : \u2212' + euros(d.remise_centimes)));
          lignes.push(ligne('Prix du menu après remise : ' + euros(d.menu_centimes), 'fw-bold mb-1'));
        } else {
          lignes.push(ligne('Remise de 10 % à partir de ' + d.seuil_remise + ' personnes.'));
        }
        lignes.push(ligne("Les frais de livraison sont calculés à l'étape suivante, selon l'adresse.", 'text-muted mb-0'));
        afficher(lignes);
      })
      .catch((e) => {
        if (e && e.name === 'AbortError') { return; }
        if (numero !== sequence) { return; }
        afficher([ligne("Le prix n'a pas pu être calculé pour le moment. Il sera affiché à l'étape suivante.", 'mb-0')]);
      })
      .then(() => { if (numero === sequence) { estimation.removeAttribute('aria-busy'); } });
  }

  menu.addEventListener('change', () => { majContraintes(); estimer(); });
  personnes.addEventListener('input', () => { clearTimeout(minuteur); minuteur = setTimeout(estimer, DELAI); });

  majContraintes();
  if (menu.value !== '' && personnes.value !== '') { estimer(); }
})();
