// Affiche en direct les règles du mot de passe respectées (la validation réelle reste côté serveur).
(function () {
  var champ = document.getElementById('mot_de_passe');
  var regles = document.querySelectorAll('[data-regle]');
  if (!champ || !regles.length) { return; }

  var tests = {
    longueur: function (v) { return v.length >= 10; },
    majuscule: function (v) { return /\p{Lu}/u.test(v); },
    minuscule: function (v) { return /\p{Ll}/u.test(v); },
    chiffre: function (v) { return /[0-9]/.test(v); },
    special: function (v) { return /[^\p{L}\p{N}]/u.test(v); }
  };

  function maj() {
    regles.forEach(function (li) {
      var ok = tests[li.getAttribute('data-regle')](champ.value);
      li.classList.toggle('ok', ok);
      li.querySelector('.etat').textContent = ok ? '✓' : '✗';
      li.querySelector('.statut').textContent = ok ? 'respecté :' : 'à respecter :';
    });
  }

  champ.addEventListener('input', maj);
})();
