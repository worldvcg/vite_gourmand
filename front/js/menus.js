
  import { API_BASE } from "./config.js";
  const API = API_BASE;

const $list     = document.getElementById('menus-list');
const $priceMax = document.getElementById('f-price-max');
const $priceMin = document.getElementById('f-price-min');
const $priceTo  = document.getElementById('f-price-to');
const $theme    = document.getElementById('f-theme');
const $regime   = document.getElementById('f-regime');
const $minPers  = document.getElementById('f-min-pers');


let MENUS = [];

// ✅ Images fiables sur toutes les pages
function resolveImg(src) {
  const fallback = '/vite_gourmand/front/images/menu-fallback.jpg';
  if (!src) return fallback;

  const s = String(src).trim();
  if (!s) return fallback;

  if (s.startsWith('http://') || s.startsWith('https://')) return s;
  if (s.startsWith('/')) return s;

  return '/vite_gourmand/front/' + s.replace(/^\.?\//, '');
}

// ✅ Format Euro
function euro(val) {
  const n = Number(val || 0);
  return n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
}

async function loadMenus() {
  try {
    const res = await fetch(API_BASE + '/api/menus', { cache: 'no-store' });
    if (!res.ok) throw new Error('API menus indisponible');

    const data = await res.json();
    MENUS = Array.isArray(data) ? data : [];
    render(MENUS);

  } catch (e) {
    console.error(e);
    $list.innerHTML = `
      <div class="col-12">
        <div class="alert alert-danger">Impossible de charger les menus.</div>
      </div>`;
  }
}

function render(items) {
  $list.innerHTML = '';

  if (!items || !items.length) {
    $list.innerHTML = `
      <div class="col-12">
        <div class="alert alert-warning">
          Aucun menu ne correspond aux filtres.
        </div>
      </div>`;
    return;
  }

  items.forEach(m => {
    const col = document.createElement('div');
    col.className = 'col-12 col-md-6 col-lg-4';

    const img = resolveImg(m.image);

    // ✅ Champs DB
    const title = m.title ?? 'Menu';
    const desc = m.description ?? '';
    const prix = Number(m.base_price ?? 0);
    const theme = m.theme ?? '—';
    const regime = m.regime ?? '—';
    const min = m.min_people ?? '—';
    const id = m.id;

    col.innerHTML = `
      <div class="card h-100 shadow-sm border-0 menu-card">
        <div class="ratio ratio-16x9">
          <img src="${img}"
               class="card-img-top object-fit-cover"
               alt="${title}"
               onerror="this.src='/vite_gourmand/front/images/menu-fallback.jpg'">
        </div>

        <div class="card-body d-flex flex-column">
          <h5 class="card-title mb-2">${title}</h5>

          <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge bg-secondary"><i class="bi bi-calendar3 me-1"></i>${theme}</span>
            <span class="badge bg-secondary"><i class="bi bi-egg-fried me-1"></i>${regime}</span>
            <span class="badge bg-secondary"><i class="bi bi-people me-1"></i>min. ${min} pers.</span>
          </div>

          <p class="card-text text-muted small mb-3">${desc}</p>

          <div class="mt-auto">
            <p class="menu-price h6 mb-3">
              À partir de <strong>${euro(prix)}</strong>
            </p>

            <button class="btn btn-primary w-100 btn-cta" type="button">
              Voir le menu
            </button>
          </div>
        </div>
      </div>
    `;

    col.querySelector('button').addEventListener('click', () => {
      window.location.href = `menu-detail.html?id=${id}`;
    });

    $list.appendChild(col);
  });
}

function applyFilters() {
  const max = $priceMax.value ? Number($priceMax.value) : Infinity;
  const min = $priceMin.value ? Number($priceMin.value) : 0;
  const to  = $priceTo.value  ? Number($priceTo.value)  : Infinity;
  const th  = ($theme.value || '').toLowerCase();
  const rg  = ($regime.value || '').toLowerCase();
  const mp  = $minPers.value ? Number($minPers.value) : 0;

  const useRange = $priceMin.value || $priceTo.value;

  const filtered = MENUS.filter(m => {
    const prix = Number(m.base_price || 0);

    const okMax = prix <= max;
    const okRange = prix >= min && prix <= to;
    const priceOK = useRange ? okRange : okMax;

    const okTheme = !th || String(m.theme || '').toLowerCase() === th;
    const okRegime = !rg || String(m.regime || '').toLowerCase() === rg;
    const okMinPers = Number(m.min_people || 0) >= mp;

    return priceOK && okTheme && okRegime && okMinPers;
  });

  render(filtered);
}

[$priceMax, $priceMin, $priceTo, $theme, $regime, $minPers].forEach(el => {
  el.addEventListener('input', applyFilters);
  el.addEventListener('change', applyFilters);
});

loadMenus();