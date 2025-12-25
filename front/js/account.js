(() => {
if (!localStorage.getItem('authToken')) {
  window.location.href = 'http://localhost:8888/vite_gourmand/front/index.html';
}
  const API = 'http://localhost:9000/index.php?route=';

  const alertBox = document.getElementById('alert-box');
  const first = document.getElementById('me-first');
  const last  = document.getElementById('me-last');
  const email = document.getElementById('me-email');

  const ordersList = document.querySelector('.liste-commandes');

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  // ==========================
  // Charger profil utilisateur
  // ==========================
  async function load() {
    let token = localStorage.getItem('authToken');
    if (!token) {
      setAlert('danger', 'Token manquant. Veuillez vous reconnecter.');
      return;
    }
    token = token.trim();

    try {
      const res = await fetch(API + '/api/auth/me', {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      const data = await res.json();

      if (!res.ok) {
        return setAlert('danger', data.error || 'Session invalide.');
      }

      first.textContent = data.first_name || '—';
      last.textContent  = data.last_name  || '—';
      email.textContent = data.email      || '—';

      // Charger les commandes après le profil
      loadOrders(token);

    } catch (err) {
      console.error(err);
      setAlert('danger', 'Erreur réseau. Réessayez.');
    }
  }

  // ==========================
  // Charger les commandes
  // ==========================
  async function loadOrders(token) {
  try {
    const res = await fetch(API + '/api/orders/my', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    const json = await res.json();
    console.log('📦 orders data =', json);

    // Vérifie si c'est un tableau ou un objet { orders: [...] }
    const data = Array.isArray(json) ? json : (json.orders || []);

    ordersList.innerHTML = '';

    if (data.length === 0) {
      const p = document.createElement('p');
      p.className = 'text-center text-muted';
      p.textContent = 'Aucune commande n’est en cours.';
      ordersList.appendChild(p);
      return;
    }

    data.forEach(order => {
      const div = document.createElement('div');
      div.className = 'commande mb-3 p-3 border rounded';

      div.innerHTML = `
        <h3>Commande #${order.id}</h3>
        <p><strong>Date :</strong> ${order.created_at}</p>
        <p><strong>Menu :</strong> ${order.menu_name}</p>
        <p><strong>Statut :</strong> ${order.status}</p>

        <div class="cmd-actions mt-2">
          ${order.status === 'attente' ? `
              <button class="btn-modifier me-2 mb-1" data-id="${order.id}">Modifier</button>
              <button class="btn-annuler me-2 mb-1" data-id="${order.id}">Annuler</button>
          ` : ''}
          ${order.status === 'accepte' ? `
              <button class="btn-suivi me-2 mb-1" data-id="${order.id}">Suivi</button>
          ` : ''}
          ${order.status === 'terminee' ? `
              <button class="btn-avis me-2 mb-1" data-id="${order.id}">Donner un avis</button>
          ` : ''}
        </div>
      `;

      ordersList.appendChild(div);
    });

    addOrderButtonsEvents();

  } catch (err) {
    console.error(err);
    setAlert('danger', 'Impossible de charger les commandes');
  }
}

  // ==========================
  // Événements boutons
  // ==========================
  function addOrderButtonsEvents() {
    document.querySelectorAll('.btn-annuler').forEach(btn => {
      btn.addEventListener('click', () => cancelOrder(btn.dataset.id));
    });

    document.querySelectorAll('.btn-modifier').forEach(btn => {
      btn.addEventListener('click', () => editOrder(btn.dataset.id));
    });

    document.querySelectorAll('.btn-suivi').forEach(btn => {
      btn.addEventListener('click', () => showTracking(btn.dataset.id));
    });

    document.querySelectorAll('.btn-avis').forEach(btn => {
      btn.addEventListener('click', () => showReviewForm(btn.dataset.id));
    });
  }

  async function cancelOrder(id) {
    const token = localStorage.getItem('authToken');
    try {
      const res = await fetch(API + `/api/orders/${id}/cancel`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json();
      if (!res.ok) return setAlert('danger', data.error || 'Erreur annulation');
      setAlert('success', 'Commande annulée');
      loadOrders(token);
    } catch (err) {
      console.error(err);
      setAlert('danger', 'Erreur réseau lors de l\'annulation');
    }
  }

  function editOrder(id) {
    console.log('📝 Modifier commande', id);
    // Ici tu peux ouvrir un formulaire pré-rempli pour modification
  }

  async function showTracking(id) {
    const token = localStorage.getItem('authToken');
    try {
      const res = await fetch(API + `/api/orders/${id}/tracking`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json();
      const zone = document.querySelector('.suivi-commande');
      zone.style.display = 'block';
      zone.innerHTML = `
        <h2>Suivi de la commande</h2>
        <ul>
          ${data.map(step => `<li>${step.status} – ${step.date}</li>`).join('')}
        </ul>
      `;
    } catch (err) {
      console.error(err);
      setAlert('danger', 'Impossible de récupérer le suivi');
    }
  }

  function showReviewForm(id) {
    const zone = document.querySelector('.donner-avis');
    zone.style.display = 'block';
    zone.dataset.id = id;
  }

  async function sendReview() {
    const zone = document.querySelector('.donner-avis');
    const id = zone.dataset.id;
    const token = localStorage.getItem('authToken');

    const note = zone.querySelector('input').value;
    const comment = zone.querySelector('textarea').value;

    try {
      const res = await fetch(API + `/api/orders/${id}/review`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ note, comment })
      });
      const data = await res.json();
      if (!res.ok) return setAlert('danger', data.error || 'Erreur envoi avis');
      setAlert('success', 'Avis envoyé !');
      zone.style.display = 'none';
    } catch (err) {
      console.error(err);
      setAlert('danger', 'Erreur réseau lors de l\'avis');
    }
  }

  window.sendReview = sendReview; // rendre accessible depuis onclick bouton

  // ==========================
  // Lancer
  // ==========================
  load();
})();