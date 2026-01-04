(() => {
  const API = 'http://localhost:9000/index.php?route=';

  const token = (localStorage.getItem('authToken') || '').trim();
  if (!token) {
    window.location.href = 'index.html';
    return;
  }

  const alertBox = document.getElementById('alert-box');
  const firstEl  = document.getElementById('me-first');
  const lastEl   = document.getElementById('me-last');
  const emailEl  = document.getElementById('me-email');

  const phoneEl   = document.getElementById('me-phone');
  const addressEl = document.getElementById('me-address');
  const cityEl    = document.getElementById('me-city');

  const btnEdit   = document.getElementById('btn-edit');
  const btnSave   = document.getElementById('btn-save');
  const btnCancel = document.getElementById('btn-cancel');

  const ordersList = document.getElementById('orders-list');

  let profileCache = null;

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }
  function clearAlert() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }

  function setEditMode(isEdit) {
    phoneEl.disabled = !isEdit;
    addressEl.disabled = !isEdit;
    cityEl.disabled = !isEdit;

    btnSave.classList.toggle('d-none', !isEdit);
    btnCancel.classList.toggle('d-none', !isEdit);
    btnEdit.classList.toggle('d-none', isEdit);
  }

  function setField(el, value) {
  if (!el) return;
  if ('value' in el) el.value = value ?? '';
  else el.textContent = value ?? '—';
}

  function fillProfile(data) {
  setField(firstEl, data.first_name || '—');
  setField(lastEl,  data.last_name  || '—');
  setField(emailEl, data.email      || '—');

  setField(phoneEl, data.phone || '');
  setField(addressEl, data.address || '');
  setField(cityEl, data.city || '');
  }

  async function loadProfile() {
    clearAlert();
    const res = await fetch(API + '/api/auth/me', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      setAlert('danger', data.error || 'Session invalide. Reconnectez-vous.');
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      window.location.href = 'login.html';
      return;
    }

    profileCache = data;
    fillProfile(data);
  }

  async function saveProfile() {
    clearAlert();

    const payload = {
      phone: phoneEl.value.trim(),
      address: addressEl.value.trim(),
      city: cityEl.value.trim(),
    };

    // mini validation front
    if (!payload.phone || !payload.address || !payload.city) {
      setAlert('warning', 'Téléphone, adresse et ville sont obligatoires.');
      return;
    }

    btnSave.disabled = true;

    const res = await fetch(API + '/api/auth/me', {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));
    btnSave.disabled = false;

    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur mise à jour');
      return;
    }

    setAlert('success', '✅ Informations mises à jour');
    profileCache = { ...(profileCache || {}), ...data.user };
    fillProfile(profileCache);
    setEditMode(false);
  }

  // -------------------------
  // Orders (inchangé, mais propre)
  // -------------------------
  async function loadOrders() {

    try {
      const res = await fetch(API + '/api/orders/my', {
        headers: { Authorization: `Bearer ${token}` }
      });

      const json = await res.json().catch(() => []);
      const data = Array.isArray(json) ? json : (json.orders || []);

      ordersList.innerHTML = '';

      if (data.length === 0) {
        ordersList.innerHTML = `<p class="text-center text-muted">Aucune commande.</p>`;
        return;
      }

      data.forEach(order => {
  const div = document.createElement('div');
  div.className = 'commande mb-3 p-3 border rounded';

  const status = (order.status || '').toLowerCase();

  const canEditCancel = (status === 'attente');
  const canTrack = (
    status !== 'attente' &&
    status !== 'annulee'
  );

  div.innerHTML = `
    <h3>Commande #${order.id}</h3>
    <p><strong>Date :</strong> ${order.created_at}</p>
    <p><strong>Menu :</strong> ${order.menu_name || order.menu_title || '—'}</p>
    <p><strong>Statut :</strong> ${order.status}</p>

    <div class="cmd-actions mt-2">
      ${canEditCancel ? `
        <button class="btn btn-outline-secondary btn-sm btn-modifier me-2" data-id="${order.id}">
          Modifier
        </button>
        <button class="btn btn-outline-danger btn-sm btn-annuler" data-id="${order.id}">
          Annuler
        </button>
      ` : ''}

      ${order.status === 'terminee' ? `
        <button class="btn btn-success btn-sm btn-review" data-id="${order.id}">
          Donner un avis
        </button>
      ` : ''}

      ${canTrack ? `
        <button class="btn btn-primary btn-sm btn-suivi" data-id="${order.id}">
          Suivi
        </button>
      ` : ''}
    </div>
  `;

  ordersList.appendChild(div);
});

document.querySelectorAll('.btn-modifier').forEach(btn => {
      btn.addEventListener('click', () => {
      const orderId = btn.dataset.id;
      window.location.href = `commande.html?edit=${orderId}`;
      });
    });

    document.querySelectorAll('.btn-review').forEach(btn => {
      btn.addEventListener('click', () => {
      window.location.href = `avis.html?order=${btn.dataset.id}`;
      });
    });

      // events
      document.querySelectorAll('.btn-annuler').forEach(btn => {
        btn.addEventListener('click', () => cancelOrder(btn.dataset.id));
      });
      document.querySelectorAll('.btn-suivi').forEach(btn => {
        btn.addEventListener('click', () => showTracking(btn.dataset.id));
      });

    } catch (err) {
      console.error(err);
      setAlert('danger', 'Impossible de charger les commandes');
    }
  }

  async function cancelOrder(id) {
    setAlert('info', 'Fonction annulation: à brancher avec ton endpoint + payload (motif + contact).');
  }

  async function showTracking(id) {
  const zone = document.querySelector('.suivi-commande');
  zone.classList.remove('d-none');

  const res = await fetch(API + `/api/orders/${id}/status`, {
    headers: { Authorization: `Bearer ${token}` }
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    setAlert('danger', data.error || 'Impossible de récupérer le suivi');
    return;
  }

  const order = data.order;
  const history = Array.isArray(data.history) ? data.history : [];

  let historyHtml = '';

  if (history.length === 0) {
    historyHtml = `<p class="text-muted">Aucun historique</p>`;
  } else {
    historyHtml = `
      <ul class="list-group list-group-flush">
        ${history.map(h => `
          <li class="list-group-item">
            <strong>${h.status}</strong><br>
            <small class="text-muted">
              ${h.changed_at}${h.note ? ' — ' + h.note : ''}
            </small>
          </li>
        `).join('')}
      </ul>
    `;
  }

  zone.innerHTML = `
    <h2>Suivi de la commande</h2>

    <p><strong>Commande :</strong> #${order.id}</p>
    <p><strong>Statut actuel :</strong> ${order.status}</p>
    <p>
      <strong>Prestation :</strong>
      ${order.prestation_date || '—'} à ${order.prestation_time || '—'}
    </p>
    <p class="text-muted small">
      Dernière mise à jour : ${order.updated_at || '—'}
    </p>

    <hr>

    <h5>Historique des statuts</h5>
    ${historyHtml}
  `;
}


  // -------------------------
  // UI events
  // -------------------------
  btnEdit.addEventListener('click', () => {
    setEditMode(true);
    clearAlert();
  });

  btnCancel.addEventListener('click', () => {
    if (profileCache) fillProfile(profileCache);
    setEditMode(false);
    clearAlert();
  });

  btnSave.addEventListener('click', saveProfile);

  // start
  setEditMode(false);
  loadProfile();
  loadOrders();
})();