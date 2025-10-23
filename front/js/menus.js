const $list = document.getElementById('menus-list');
const $priceMax = document.getElementById('f-price-max');
const $priceMin = document.getElementById('f-price-min');
const $priceTo  = document.getElementById('f-price-to');
const $theme    = document.getElementById('f-theme');
const $regime   = document.getElementById('f-regime');
const $minPers  = document.getElementById('f-min-pers');

let MENUS = [];

async function loadMenus() {
  const res = await fetch('./data/menus.json', { cache: 'no-store' });
  MENUS = await res.json();
  render(MENUS);
}

function render(items) {
  $list.innerHTML = '';
  if (!items.length) {
    $list.innerHTML = `<div class="col-12"><div class="alert alert-warning">Aucun menu ne correspond aux filtres.</div></div>`;
    return;
  }
  items.forEach(m => {
    const col = document.createElement('div');
    col.className = 'col-12 col-md-6 col-lg-4';
    col.innerHTML = `
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">${m.titre}</h5>
          <p class="card-text text-muted mb-2">${m.description}</p>
          <div class="small mb-2">Thème : <strong>${m.theme}</strong> • Régime : <strong>${m.regime}</strong></div>
          <div class="small mb-3">Min. personnes : <strong>${m.minPersonnes}</strong> • Prix (min) : <strong>${m.prixBase}€</strong></div>
          <a href="#" class="btn btn-sm btn-outline-primary">Détails</a>
        </div>
      </div>
    `;
    $list.appendChild(col);
  });
}

function applyFilters() {
  const max = Number($priceMax.value) || Infinity;
  const min = Number($priceMin.value) || 0;
  const to  = Number($priceTo.value)  || Infinity;
  const th  = $theme.value;
  const rg  = $regime.value;
  const mp  = Number($minPers.value) || 0;

  const filtered = MENUS.filter(m => {
    const okPriceMax = m.prixBase <= max;
    const okRange = m.prixBase >= min && m.prixBase <= to;
    const okTheme = !th || m.theme === th;
    const okRegime = !rg || m.regime === rg;
    const okMinPers = m.minPersonnes >= mp;
    // Si aucun champ de fourchette rempli, on ignore okRange
    const useRange = $priceMin.value || $priceTo.value;
    const priceOK = useRange ? okRange : okPriceMax;
    return priceOK && okTheme && okRegime && okMinPers;
  });

  render(filtered);
}

[$priceMax, $priceMin, $priceTo, $theme, $regime, $minPers].forEach(el => {
  el.addEventListener('input', applyFilters);
  el.addEventListener('change', applyFilters);
});

loadMenus();
