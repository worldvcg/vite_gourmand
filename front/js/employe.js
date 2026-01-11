console.log("✅ employe.js chargé");

  import { API_BASE } from "./config.js";
  const API = API_BASE;

(() => {

  const token = (localStorage.getItem("authToken") || "").trim();

  function authHeaders(extra = {}) {
    return {
      ...extra,
      Authorization: "Bearer " + token
    };
  }

  if (!document.getElementById("menus-list")) {
  console.log("⏭️ employe.js ignoré (pas la page gestion)");
  return;
}
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

      const url = `${API}/api/orders${qs.toString() ? "&" + qs.toString() : ""}`;

      const res = await fetch(url, { cache: "no-store", headers: authHeaders() });
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

    const currentStatus = o.status || 'accepte';

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
          <strong>Commande #${o.id}</strong><br>
          <span class="text-muted small">${o.menu_title || 'Menu #' + o.menu_id}</span>
          <div class="small mt-2">
            <div><strong>Client :</strong> ${o.fullname || ''} (${o.email || ''})</div>
            <div><strong>Tél :</strong> ${o.phone || ''}</div>
            <div><strong>Adresse :</strong> ${o.address || ''}</div>
            <div><strong>Date :</strong> ${o.prestation_date || ''} à ${o.prestation_time || ''}</div>
          </div>
        </div>

        <div class="text-end">
          <div><strong>${euro(o.total_price)}</strong></div>
          <div class="small text-muted">${o.guests} pers.</div>

          <div class="mt-2">
            <label class="form-label small mb-1">Statut</label>
            <select class="form-select form-select-sm js-status" data-id="${o.id}">
              <option value="attente" ${currentStatus === 'attente' ? 'selected' : ''}>attente</option>
              <option value="accepte" ${currentStatus === 'accepte' ? 'selected' : ''}>accepté</option>
              <option value="en_preparation" ${currentStatus === 'en_preparation' ? 'selected' : ''}>en préparation</option>
              <option value="en_livraison" ${currentStatus === 'en_livraison' ? 'selected' : ''}>en cours de livraison</option>
              <option value="livre" ${currentStatus === 'livre' ? 'selected' : ''}>livré</option>
              <option value="attente_retour_materiel" ${currentStatus === 'attente_retour_materiel' ? 'selected' : ''}>attente retour matériel</option>
              <option value="terminee" ${currentStatus === 'terminee' ? 'selected' : ''}>terminée</option>
              <option value="annulee" ${currentStatus === 'annulee' ? 'selected' : ''}>annulée</option>
            </select>

            <button class="btn btn-sm btn-primary w-100 mt-2 js-save-status" data-id="${o.id}">
              Mettre à jour
            </button>

            <button class="btn btn-sm btn-danger w-100 mt-2 js-cancel"
              data-id="${o.id}"
              ${currentStatus === 'annulee' || currentStatus === 'terminee' ? 'disabled' : ''}>
              Annuler (motif obligatoire)
            </button>
          </div>
        </div>
      </div>
    `;

    ordersBox.appendChild(div);
  });

  // événements après rendu
  ordersBox.querySelectorAll('.js-save-status').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const select = ordersBox.querySelector(`.js-status[data-id="${id}"]`);
      const status = select.value;
      await updateOrderStatus(id, status);
    });
  });

  ordersBox.querySelectorAll('.js-cancel').forEach(btn => {
    btn.addEventListener('click', () => openCancelModal(btn.dataset.id));
  });
}

async function updateOrderStatus(id, status) {
  try {
    const res = await fetch(`${API}/api/orders/${id}/status`, {
      method: "PUT",
      headers: authHeaders({ "Content-Type": "application/json" }),
      body: JSON.stringify({ status })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert("danger", data.error || "Erreur mise à jour statut");
      return;
    }

    setAlert("success", `Statut de la commande #${id} mis à jour ✅`);
    loadOrders();
  } catch (e) {
    console.error(e);
    setAlert("danger", "Erreur réseau (update status)");
  }
}

// ===== MODAL ANNULATION =====
const cancelModalEl = document.getElementById('cancelModal');
const cancelModal = cancelModalEl ? new bootstrap.Modal(cancelModalEl) : null;

function openCancelModal(orderId) {
  if (!cancelModal) {
    setAlert('danger', 'Modal annulation introuvable (#cancelModal)');
    return;
  }
  document.getElementById('cancel-order-id').value = orderId;
  document.getElementById('cancel-reason').value = '';
  document.getElementById('cancel-contact').value = 'gsm';
  cancelModal.show();
}

async function submitCancel() {
  const id = document.getElementById('cancel-order-id').value;
  const cancel_reason = document.getElementById('cancel-reason').value.trim();
  const cancel_contact_mode = document.getElementById('cancel-contact').value;

  if (cancel_reason.length < 5) {
    setAlert('danger', 'Motif trop court (min 5 caractères)');
    return;
  }

  try {
    const res = await fetch(`${API}/api/orders/${id}/cancel`, {
    method: "POST",
    headers: authHeaders({ "Content-Type": "application/json" }),
    body: JSON.stringify({ cancel_reason, cancel_contact_mode })
  });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur annulation');
      return;
    }

    cancelModal.hide();
    setAlert('success', `Commande #${id} annulée`);
    loadOrders();
  } catch (e) {
    console.error(e);
    setAlert('danger', 'Erreur réseau (annulation)');
  }
}

// =====================
// REVIEWS (MODERATION)
// =====================
const reviewsBox = document.getElementById('reviews-list');
const fReviewStatus = document.getElementById('f-review-status');
const btnRefreshReviews = document.getElementById('btn-refresh-reviews');

async function loadReviews() {
  try {
    const status = (fReviewStatus?.value || '').trim();
    const qs = new URLSearchParams();
    if (status) qs.set('status', status);

    const url = `http://localhost:9000/index.php?route=/api/reviews${qs.toString() ? '&' + qs.toString() : ''}`;

    const res = await fetch(url, { cache: 'no-store' });
    const data = await res.json();

    if (!res.ok) {
      reviewsBox.innerHTML = `<div class="alert alert-danger">Erreur chargement avis</div>`;
      return;
    }

    renderReviews(Array.isArray(data) ? data : []);
  } catch (e) {
    console.error(e);
    reviewsBox.innerHTML = `<div class="alert alert-danger">Impossible de charger les avis</div>`;
  }
}

function renderReviews(items) {
  reviewsBox.innerHTML = '';

  if (!items.length) {
    reviewsBox.inners = `<div class="alert alert-info">Aucun avis</div>`;
    reviewsBox.innerHTML = `<div class="alert alert-info">Aucun avis</div>`;
    return;
  }

  items.forEach(r => {
    const div = document.createElement('div');
    div.className = 'card p-3 mb-3';

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
          <strong>${r.menu_title || 'Menu'}</strong>
          <div class="small text-muted">${r.user_email || ''} — ${r.created_at || ''}</div>
          <div class="mt-2">
            <div><strong>Note :</strong> ${r.rating}/5</div>
            <div><strong>Commentaire :</strong> ${r.comment || ''}</div>
            <div class="mt-1"><strong>Statut :</strong> ${r.status}</div>
          </div>
        </div>

        <div class="text-end review-actions">
          <textarea class="form-control form-control-sm mb-2 js-reason" data-id="${r.id}"
            placeholder="Motif (obligatoire si refus)"></textarea>

          <button class="btn btn-sm btn-primary w-100 mb-2 js-approve" data-id="${r.id}">
            Valider
          </button>
          <button class="btn btn-sm btn-danger w-100 js-reject" data-id="${r.id}">
            Refuser
          </button>
        </div>
      </div>
    `;

    reviewsBox.appendChild(div);
  });

  reviewsBox.querySelectorAll('.js-approve').forEach(btn => {
    btn.addEventListener('click', () => moderateReview(btn.dataset.id, 'approved'));
  });

  reviewsBox.querySelectorAll('.js-reject').forEach(btn => {
    btn.addEventListener('click', () => moderateReview(btn.dataset.id, 'rejected'));
  });
}

async function moderateReview(id, status) {
  const reason = (reviewsBox.querySelector(`.js-reason[data-id="${id}"]`)?.value || '').trim();

  if (status === 'rejected' && reason.length < 5) {
    setAlert('danger', 'Motif obligatoire (min 5 caractères) pour refuser');
    return;
  }

  try {
    const res = await fetch(`http://localhost:9000/index.php?route=/api/reviews/${id}/moderate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status, moderation_reason: reason })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur modération');
      return;
    }

    setAlert('success', status === 'approved' ? 'Avis validé ✅' : 'Avis refusé ✅');
    loadReviews();
  } catch (e) {
    console.error(e);
    setAlert('danger', 'Erreur réseau (modération)');
  }
}

// =====================
// OPENING HOURS
// =====================
const hoursBox = document.getElementById('hours-list');
const btnRefreshHours = document.getElementById('btn-refresh-hours');

const hoursModalEl = document.getElementById('hoursModal');
const hoursModal = hoursModalEl ? new bootstrap.Modal(hoursModalEl) : null;

async function loadHours() {
  if (!hoursBox) return;

  try {
    const res = await fetch('http://localhost:9000/index.php?route=/api/opening-hours', { cache: 'no-store' });
    const data = await res.json();

    if (!res.ok) {
      hoursBox.innerHTML = `<div class="alert alert-danger">Erreur chargement horaires</div>`;
      return;
    }

    renderHours(Array.isArray(data) ? data : []);
  } catch (e) {
    console.error(e);
    hoursBox.innerHTML = `<div class="alert alert-danger">Impossible de charger les horaires</div>`;
  }
}

function renderHours(items) {
  hoursBox.innerHTML = '';

  if (!items.length) {
    hoursBox.innerHTML = `<div class="alert alert-info">Aucun horaire</div>`;
    return;
  }

  items.forEach(h => {
    const div = document.createElement('div');
    div.className = 'card p-3 mb-3';

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-center gap-3">
        <div>
          <strong>${h.day_name || h.day || 'Jour'}</strong>
          <div class="text-muted small">
            ${(h.open_time ?? h.open ?? '--:--')} — ${(h.close_time ?? h.close ?? '--:--')}
          </div>
        </div>

        <button class="btn btn-sm btn-primary js-edit-hours"
          data-id="${h.id}"
          data-day="${h.day_name || h.day || ''}"
          data-open="${h.open_time ?? h.open ?? ''}"
          data-close="${h.close_time ?? h.close ?? ''}">
          Modifier
        </button>
      </div>
    `;

    hoursBox.appendChild(div);
  });

  hoursBox.querySelectorAll('.js-edit-hours').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!hoursModal) return setAlert('danger', 'Modal horaires introuvable (#hoursModal)');

      document.getElementById('hours-id').value = btn.dataset.id;
      document.getElementById('hours-day').value = btn.dataset.day;
      document.getElementById('hours-open').value = btn.dataset.open;
      document.getElementById('hours-close').value = btn.dataset.close;

      hoursModal.show();
    });
  });
}

document.getElementById('hoursForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = document.getElementById('hours-id').value;
  const open_time = document.getElementById('hours-open').value;
  const close_time = document.getElementById('hours-close').value;

  try {
    const res = await fetch(`http://localhost:9000/index.php?route=/api/opening-hours/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
      open: open_time,
      close: close_time
       })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur mise à jour horaires');
      return;
    }

    hoursModal?.hide();
    setAlert('success', 'Horaires mis à jour ✅');
    loadHours();
  } catch (e) {
    console.error(e);
    setAlert('danger', 'Erreur réseau (horaires)');
  }
});

  btnRefreshHours?.addEventListener('click', loadHours);
  btnRefreshReviews?.addEventListener('click', loadReviews);
  fReviewStatus?.addEventListener('change', loadReviews);

// =====================
// DISHES
// =====================
const dishesBox = document.getElementById('dishes-list');
const btnAddDish = document.getElementById('btn-add-dish');

const dishModalEl = document.getElementById('dishModal');
const dishModal = dishModalEl ? new bootstrap.Modal(dishModalEl) : null;

const dishForm = document.getElementById('dishForm');
const in_dish_id = document.getElementById('dish-id');
const in_dish_name = document.getElementById('dish-name');
const in_dish_type = document.getElementById('dish-type');
const in_dish_description = document.getElementById('dish-description');

let DISHES = [];

async function loadDishes() {
  if (!dishesBox) return;

  try {
    const res = await fetch('http://localhost:9000/index.php?route=/api/dishes', { cache: 'no-store' });
    const data = await res.json();

    if (!res.ok) {
      dishesBox.innerHTML = `<div class="alert alert-danger">Erreur chargement plats</div>`;
      return;
    }

    DISHES = Array.isArray(data) ? data : [];
    renderDishes(DISHES);
  } catch (e) {
    console.error(e);
    dishesBox.innerHTML = `<div class="alert alert-danger">Impossible de charger les plats</div>`;
  }
}

function labelType(t) {
  const v = String(t || '').toLowerCase();
  if (v === 'entree' || v === 'entrée') return 'Entrée';
  if (v === 'plat') return 'Plat';
  if (v === 'dessert') return 'Dessert';
  return t || '—';
}

function renderDishes(items) {
  dishesBox.innerHTML = '';

  if (!items.length) {
    dishesBox.innerHTML = `<div class="alert alert-info">Aucun plat</div>`;
    return;
  }

  items.forEach(d => {
    const div = document.createElement('div');
    div.className = 'card p-3 mb-3';

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
          <strong>${d.name || 'Sans nom'}</strong>
          <div class="text-muted small">${labelType(d.type)}</div>
          <div class="small mt-2">${d.description || ''}</div>
        </div>

        <div class="text-end" style="min-width:160px;">
          <button class="btn btn-sm btn-warning w-100 mb-2 js-edit-dish" data-id="${d.id}">
            Modifier
          </button>
          <button class="btn btn-sm btn-danger w-100 js-delete-dish" data-id="${d.id}">
            Supprimer
          </button>
        </div>
      </div>
    `;

    dishesBox.appendChild(div);
  });

  dishesBox.querySelectorAll('.js-edit-dish').forEach(btn => {
    btn.addEventListener('click', () => openDishModal(btn.dataset.id));
  });

  dishesBox.querySelectorAll('.js-delete-dish').forEach(btn => {
    btn.addEventListener('click', () => deleteDish(btn.dataset.id));
  });
}

function clearDishForm() {
  in_dish_id.value = '';
  in_dish_name.value = '';
  in_dish_type.value = 'entree';
  in_dish_description.value = '';
}

function openDishModal(id = null) {
  if (!dishModal) return setAlert('danger', 'Modal plat introuvable (#dishModal)');

  if (!id) {
    document.getElementById('dishModalTitle').textContent = 'Ajouter un plat';
    clearDishForm();
    dishModal.show();
    return;
  }

  const d = DISHES.find(x => String(x.id) === String(id));
  if (!d) return setAlert('danger', 'Plat introuvable');

  document.getElementById('dishModalTitle').textContent = 'Modifier un plat';
  in_dish_id.value = d.id;
  in_dish_name.value = d.name || '';
  in_dish_type.value = (d.type || 'entree').toLowerCase();
  in_dish_description.value = d.description || '';
  dishModal.show();
}

btnAddDish?.addEventListener('click', () => openDishModal(null));

dishForm?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const id = in_dish_id.value ? Number(in_dish_id.value) : null;
  const payload = {
    name: in_dish_name.value.trim(),
    type: in_dish_type.value,
    description: in_dish_description.value.trim()
  };

  if (!payload.name) return setAlert('danger', 'Nom obligatoire');

  try {
    const url = id
      ? `http://localhost:9000/index.php?route=/api/dishes/${id}`
      : `http://localhost:9000/index.php?route=/api/dishes`;

    const method = id ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur enregistrement plat');
      return;
    }

    dishModal?.hide();
    setAlert('success', id ? 'Plat modifié ✅' : 'Plat ajouté ✅');
    loadDishes();
  } catch (e) {
    console.error(e);
    setAlert('danger', 'Erreur réseau (plats)');
  }
});

async function deleteDish(id) {
  if (!confirm('Supprimer ce plat ?')) return;

  try {
    const res = await fetch(`http://localhost:9000/index.php?route=/api/dishes/${id}`, {
      method: 'DELETE'
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur suppression plat');
      return;
    }

    setAlert('success', 'Plat supprimé ✅');
    loadDishes();
  } catch (e) {
    console.error(e);
    setAlert('danger', 'Erreur réseau (suppression plat)');
  }
}

document.getElementById('btn-confirm-cancel')?.addEventListener('click', submitCancel);

  btnRefreshOrders?.addEventListener('click', loadOrders);
  fEmail?.addEventListener('input', loadOrders);
  fStatus?.addEventListener('change', loadOrders);

  // Chargement initial
  loadOrders();
  loadMenus();
  loadReviews();
  loadHours();
  loadDishes();
})();