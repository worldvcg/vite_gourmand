const qs = new URLSearchParams(location.search);
const id = qs.get('id');

// Eléments UI
const $title = document.getElementById('menu-title');
const $desc  = document.getElementById('menu-desc');
const $min   = document.getElementById('menu-min');
const $price = document.getElementById('menu-price');
const $minPlus5 = document.getElementById('min-plus5');

const $nb   = document.getElementById('c-nb');
const $bdx  = document.getElementById('c-bdx');
const $km   = document.getElementById('c-km');

const $pMenu = document.getElementById('p-menu');
const $pRem  = document.getElementById('p-remise');
const $pLiv  = document.getElementById('p-livraison');
const $pTot  = document.getElementById('p-total');

const $form = document.getElementById('order-form');

let MENU = null;

async function init() {
  const res = await fetch('./data/menus.json', { cache: 'no-store' });
  const menus = await res.json();
  MENU = menus.find(x => x.id === id);
  if (!MENU) {
    alert("Menu introuvable.");
    location.href = './menus.html';
    return;
  }
  $title.textContent = MENU.titre;
  $desc.textContent  = MENU.description;
  $min.textContent   = MENU.minPersonnes;
  $price.textContent = MENU.prixBase;
  $minPlus5.textContent = MENU.minPersonnes + 5;

  $nb.value = MENU.minPersonnes; // valeur par défaut
  compute();
}

function compute() {
  const nb = Math.max(Number($nb.value || 0), 1);
  const min = MENU.minPersonnes;
  const base = MENU.prixBase;
  const unit = base / min;

  const prixMenu = nb * unit;
  const remise = nb >= (min + 5) ? prixMenu * 0.10 : 0;

  let livraison = 0;
  if ($bdx.value === 'non') {
    const km = Math.max(Number($km.value || 0), 0);
    livraison = 5 + 0.59 * km;
  }

  const total = Math.max(0, prixMenu - remise + livraison);

  // UI
  $pMenu.textContent = prixMenu.toFixed(2) + ' €';
  $pRem.textContent  = '-' + remise.toFixed(2) + ' €';
  $pLiv.textContent  = livraison.toFixed(2) + ' €';
  $pTot.textContent  = total.toFixed(2) + ' €';
}

[$nb, $bdx, $km].forEach(el => {
  el.addEventListener('input', compute);
  el.addEventListener('change', compute);
});

$form.addEventListener('submit', (e) => {
  e.preventDefault();
  // Ici tu enverras au back (PHP) plus tard.
  alert('Commande enregistrée (simulation). Un email de confirmation sera envoyé.');
  location.href = './menus.html';
});

init();
