console.log("✅ employe.js chargé");
(() => {
  const API_PROXY = './admin/menu_proxy.php';
  const alertBox = document.getElementById('alert-box');
  const menusBox = document.getElementById('menus-list');
  const menuModalEl = document.getElementById('menuModal');
  const menuModal = new bootstrap.Modal(menuModalEl);
  const menuForm = document.getElementById('menuForm');

  const in_id = document.getElementById('menu-id');
  const in_titre = document.getElementById('menu-titre');
  const in_description = document.getElementById('menu-description');
  const in_prix = document.getElementById('menu-prix');
  const in_min = document.getElementById('menu-min');
  const in_theme = document.getElementById('menu-theme');
  const in_regime = document.getElementById('menu-regime');
  const in_image = document.getElementById('menu-image');

  let MENUS = [];

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  // ✅ NORMALISE toutes tes images, quel que soit l'écran (employe, menus, menu-detail)
  function resolveImg(src) {
    const fallback = '/vite_gourmand/front/images/menu-fallback.jpg';
    if (!src) return fallback;

    const s = String(src).trim();
    if (!s) return fallback;

    // URL complète
    if (s.startsWith('http://') || s.startsWith('https://')) return s;

    // Chemin absolu web
    if (s.startsWith('/')) return s;

    // Sinon: on force vers le /front/
    return '/vite_gourmand/front/' + s.replace(/^\.?\//, '');
  }

  function clearMenuForm() {
    in_id.value = '';
    in_titre.value = '';
    in_description.value = '';
    in_prix.value = '';
    in_min.value = '';
    in_theme.value = '';
    in_regime.value = '';
    in_image.value = '';
  }

  function fillMenuForm(m) {
    in_id.value = m.id;
    in_titre.value = m.title;
    in_description.value = m.description;
    in_prix.value = m.base_price;
    in_min.value = m.min_people;
    in_theme.value = m.theme;
    in_regime.value = m.regime;
    in_image.value = m.image;
  }

  window.showAddMenu = () => {
    document.getElementById('menuModalTitle').textContent = 'Ajouter un menu';
    clearMenuForm();
    menuModal.show();
  };

  window.showEditMenu = (id) => {
    const m = MENUS.find(x => x.id == id);
    if (!m) return setAlert('danger', 'Menu introuvable');
    document.getElementById('menuModalTitle').textContent = 'Modifier le menu';
    fillMenuForm(m);
    menuModal.show();
  };

  menuForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const token = localStorage.getItem('authToken');

    const payload = {
      title: in_titre.value.trim(),
      description: in_description.value.trim(),
      base_price: Number(in_prix.value),
      min_people: Number(in_min.value),
      theme: in_theme.value.trim(),
      regime: in_regime.value.trim(),
      image: in_image.value.trim()
    };

    const id = in_id.value;
    const action = id ? 'update' : 'create';
    const body = { action, id: id || null, payload, authToken: token };

    try {
      const res = await fetch(API_PROXY, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });

      const data = await res.json();
      if (!res.ok) return setAlert('danger', data.error || 'Erreur proxy');

      menuModal.hide();
      setAlert('success', id ? 'Menu modifié' : 'Menu ajouté');
      loadMenus();

    } catch (err) {
      console.error(err);
      setAlert('danger', 'Erreur réseau (proxy)');
    }
  });

  window.deleteMenu = async (id) => {
    if (!confirm('Supprimer ce menu ?')) return;

    const token = localStorage.getItem('authToken');
    const body = { action: 'delete', id, authToken: token };

    try {
      const res = await fetch(API_PROXY, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });

      const data = await res.json();
      if (!res.ok) return setAlert('danger', data.error || 'Erreur proxy delete');

      setAlert('success', 'Menu supprimé');
      loadMenus();

    } catch (err) {
      console.error(err);
      setAlert('danger', 'Erreur réseau (proxy)');
    }
  };

  async function loadMenus() {
    try {
      const res = await fetch(API_PROXY, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'list' })
      });

      const data = await res.json();
      MENUS = Array.isArray(data) ? data : [];
      renderMenus();

    } catch (err) {
      console.error(err);
      menusBox.innerHTML = '<div class="alert alert-danger">Impossible de charger les menus.</div>';
    }
  }

  function renderMenus() {
    menusBox.innerHTML = '';

    if (!MENUS.length) {
      menusBox.innerHTML = '<div class="alert alert-info">Aucun menu</div>';
      return;
    }

    MENUS.forEach(m => {
      const div = document.createElement('div');
      div.className = 'card p-3 mb-3';

      const img = resolveImg(m.image);

      div.innerHTML = `
        <div class="d-flex gap-3 align-items-center">
          <img src="${img}"
               style="width:80px;height:80px;object-fit:cover;border-radius:10px"
               onerror="this.src='/vite_gourmand/front/images/menu-fallback.jpg'">

          <div class="flex-grow-1">
            <h5 class="mb-1">${m.title || 'Sans titre'}</h5>
            <div class="text-muted small mb-2">${m.description || ''}</div>

            <button class="btn btn-sm btn-warning me-2" onclick="showEditMenu(${m.id})">
              Modifier
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteMenu(${m.id})">
              Supprimer
            </button>
          </div>

          <div class="text-end small">
            <strong>${Number(m.base_price || 0).toFixed(2)} €</strong><br>
            <span class="text-muted">min ${m.min_people || 0} pers.</span>
          </div>
        </div>
      `;

      menusBox.appendChild(div);
    });
  }

  const ordersBox = document.getElementById('orders-list');
  const fStatus = document.getElementById('f-order-status');
  const fEmail = document.getElementById('f-order-email');
  const btnRefreshOrders = document.getElementById('btn-refresh-orders');

  function euro(val) {
    return Number(val || 0).toLocaleString('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    });
  }

  async function loadOrders() {
    try {
      const status = (fStatus?.value || '').trim();
      const email = (fEmail?.value || '').trim();

      const qs = new URLSearchParams();
      if (status) qs.set('status', status);
      if (email) qs.set('email', email);

      const url = `http://localhost:9000/index.php?route=/api/orders${qs.toString() ? '&' + qs.toString() : ''}`;

      const res = await fetch(url, { cache: 'no-store' });
      const data = await res.json();

      if (!res.ok) {
        ordersBox.innerHTML = `<div class="alert alert-danger">Erreur chargement commandes</div>`;
        return;
      }

      renderOrders(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error(e);
      ordersBox.innerHTML = `<div class="alert alert-danger">Impossible de charger les commandes</div>`;
    }
  }

  function renderOrders(items) {
    ordersBox.innerHTML = '';

    if (!items.length) {
      ordersBox.innerHTML = `<div class="alert alert-info">Aucune commande</div>`;
      return;
    }

    items.forEach(o => {
      const div = document.createElement('div');
      div.className = 'card p-3 mb-3';

      div.innerHTML = `
        <div class="d-flex justify-content-between">
          <div>
            <strong>Commande #${o.id}</strong><br>
            <span class="text-muted small">${o.menu_title || 'Menu #' + o.menu_id}</span>
          </div>
          <div class="text-end">
            <strong>${euro(o.total_price)}</strong><br>
            <span class="small text-muted">${o.guests} pers.</span>
          </div>
        </div>

        <hr class="my-2">

        <div class="small">
          <div><strong>Client :</strong> ${o.fullname || ''} (${o.email || ''})</div>
          <div><strong>Adresse :</strong> ${o.address || ''}</div>
          <div><strong>Date :</strong> ${o.prestation_date || ''} à ${o.prestation_time || ''}</div>
          <div><strong>Statut :</strong> ${o.status || ''}</div>
        </div>
      `;

      ordersBox.appendChild(div);
    });
  }

  btnRefreshOrders?.addEventListener('click', loadOrders);
  fEmail?.addEventListener('input', loadOrders);
  fStatus?.addEventListener('change', loadOrders);

  // Chargement initial
  loadOrders();

  loadMenus();
})();