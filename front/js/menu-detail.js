
  import { API_BASE } from "./config.js";
  const API = API_BASE;

(() => {

  const params = new URLSearchParams(window.location.search);
  const menuId = params.get('id');

  const alertBox = document.getElementById('menu-alert');

  const nameEl = document.getElementById('menu-name');
  const imgEl = document.getElementById('menu-image');
  const descEl = document.getElementById('menu-description');
  const priceEl = document.getElementById('menu-price');
  const condEl = document.getElementById('menu-conditions');
  const minEl = document.getElementById('menu-min');
  const btn = document.getElementById('btn-commander');

  const themeEl = document.getElementById('menu-theme');
  const regimeEl = document.getElementById('menu-regime');
  const stockEl = document.getElementById('menu-stock');

  
  const listEntrees = document.getElementById('list-entrees');
  const listPlats = document.getElementById('list-plats');
  const listDesserts = document.getElementById('list-desserts');

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  function resolveImg(src) {
    const fallback = '/vite_gourmand/front/images/menu-fallback.jpg';
    if (!src) return fallback;

    const s = String(src).trim();
    if (!s) return fallback;

    if (s.startsWith('http://') || s.startsWith('https://')) return s;
    if (s.startsWith('/')) return s;

    return '/vite_gourmand/front/' + s.replace(/^\.?\//, '');
  }

  function applyStock(stockValue) {
    const stock = Number(stockValue);

    if (stockEl) stockEl.textContent = Number.isFinite(stock) ? String(stock) : '—';

    if (Number.isFinite(stock) && stock <= 0) {
      btn.disabled = true;
      btn.textContent = 'Menu indisponible (stock épuisé)';
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-secondary');
    } else {
      btn.disabled = false;
      btn.textContent = 'Commander ce menu';
      btn.classList.add('btn-primary');
      btn.classList.remove('btn-secondary');
    }
  }

  function renderDishList(ul, dishes) {
    if (!ul) return;

    ul.innerHTML = '';

    if (!dishes || dishes.length === 0) {
      const li = document.createElement('li');
      li.className = 'text-muted';
      li.textContent = 'Aucun élément';
      ul.appendChild(li);
      return;
    }

    for (const d of dishes) {
      const li = document.createElement('li');
      li.className = 'mb-2';

      const allergens = Array.isArray(d.allergens) && d.allergens.length
        ? ` • Allergènes : ${d.allergens.join(', ')}`
        : '';

      li.innerHTML = `
        <strong>${d.name ?? 'Plat'}</strong>
        ${d.description ? `<div class="text-muted small">${d.description}</div>` : ''}
        ${allergens ? `<div class="small text-danger">${allergens}</div>` : ''}
      `;
      ul.appendChild(li);
    }
  }

  async function loadMenu() {
    try {
      if (!menuId) {
        setAlert('danger', 'ID du menu manquant');
        return;
      }

      const res = await fetch(API + `/api/menus/${menuId}`, { cache: 'no-store' });
      const data = await res.json();

      if (!res.ok) {
        setAlert('danger', data.error || 'Menu introuvable');
        return;
      }

      nameEl.textContent = data.title ?? 'Menu';
      descEl.textContent = data.description ?? '';
      priceEl.textContent = Number(data.base_price ?? 0).toFixed(2);
      minEl.textContent = data.min_people ?? '—';

      condEl.textContent = data.conditions_text ? data.conditions_text : 'Aucune condition particulière';

      if (themeEl) themeEl.textContent = data.theme ?? '—';
      if (regimeEl) regimeEl.textContent = data.regime ?? '—';

      imgEl.src = resolveImg(data.image);
      imgEl.classList.remove('d-none');

      applyStock(data.stock_available);

      // ✅ plats
      const dishes = Array.isArray(data.dishes) ? data.dishes : [];

      const entrees = dishes.filter(d => d.type === 'entrée');
      const plats = dishes.filter(d => d.type === 'plat');
      const desserts = dishes.filter(d => d.type === 'dessert');

      renderDishList(listEntrees, entrees);
      renderDishList(listPlats, plats);
      renderDishList(listDesserts, desserts);

    } catch (e) {
      console.error(e);
      setAlert('danger', 'Erreur de chargement du menu');
    }
  }

  btn.addEventListener('click', () => {
    const token = localStorage.getItem('authToken');

    if (!token) {
      alert('Vous devez vous connecter pour commander');
      window.location.href = 'login.html';
      return;
    }

    if (btn.disabled) return;

    window.location.href = `commande.html?menu=${menuId}`;
  });

  loadMenu();
})();