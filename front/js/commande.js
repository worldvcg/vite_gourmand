(() => {
  const API = 'http://localhost:9000/index.php?route=';

  const CITY_DISTANCES = {
    bordeaux: 0,
    pessac: 6,
    merignac: 8,
    talence: 5,
    begles: 4,
    cenon: 6,
    floirac: 7,
    libourne: 34,
    langon: 48,
    arcachon: 67
  };

  const params = new URLSearchParams(window.location.search);
  const editOrderId = params.get('edit'); // ✅ mode édition
  let menuId = params.get('menu');        // ✅ mode création
  const token = (localStorage.getItem('authToken') || '').trim();

  const alertBox = document.getElementById('order-alert');
  const form = document.getElementById('order-form');

  const first = document.getElementById('ord-first');
  const last  = document.getElementById('ord-last');
  const email = document.getElementById('ord-email');
  const phone = document.getElementById('ord-phone');

  const address = document.getElementById('ord-address');
  const city = document.getElementById('ord-city');
  const date = document.getElementById('ord-date');
  const time = document.getElementById('ord-time');
  const distanceKm = document.getElementById('ord-distance'); // optionnel

  const menuInput = document.getElementById('ord-menu');      // champ texte (affichage)
  const personsInp = document.getElementById('ord-persons');

  const priceMenu = document.getElementById('price-menu');
  const priceDelivery = document.getElementById('price-delivery');
  const priceTotal = document.getElementById('price-total');

  let menuData = null;
  let minPeople = 1;

  function setAlert(type, msg) {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  // ✅ min date = aujourd'hui
  (function setMinDate() {
    if (!date) return;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');

    date.setAttribute('min', `${yyyy}-${mm}-${dd}`);
  })();

  // ✅ si date = aujourd'hui => min time = maintenant
  date?.addEventListener('change', () => {
    if (!time) return;

    const selected = new Date(date.value);
    const today = new Date();
    selected.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    if (selected.getTime() === today.getTime()) {
      const now = new Date();
      const hh = String(now.getHours()).padStart(2, '0');
      const mm = String(now.getMinutes()).padStart(2, '0');
      time.setAttribute('min', `${hh}:${mm}`);
    } else {
      time.removeAttribute('min');
    }
  });

  function clampPersons() {
    let val = parseInt(personsInp.value, 10);
    if (!Number.isFinite(val) || val < minPeople) val = minPeople;
    personsInp.value = String(val);
    return val;
  }

  async function loadUser() {
    const res = await fetch(API + 'api/auth/me', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      setAlert('danger', data.error || 'Session invalide, reconnectez-vous.');
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      window.location.href = 'login.html';
      return;
    }

    first.value = data.first_name || '';
    last.value  = data.last_name || '';
    email.value = data.email || '';
    phone.value = data.phone || '';
    address.value = data.address || '';
    city.value = data.city || '';

    // update distance auto après remplissage ville
    updateDistanceFromCity();
  }

  async function loadMenuById(id) {
    if (!id) {
      setAlert('danger', 'Menu introuvable (ID manquant).');
      menuInput.value = '—';
      return false;
    }

    const res = await fetch(API + `api/menus/${id}`);
    menuData = await res.json().catch(() => ({}));

    if (!res.ok) {
      setAlert('danger', menuData.error || 'Menu introuvable.');
      menuInput.value = '—';
      return false;
    }

    menuInput.value = menuData.title || `Menu #${menuData.id}`;

    minPeople = Number(menuData.min_people || 1);
    personsInp.min = String(minPeople);
    personsInp.step = '1';
    personsInp.required = true;

    // si vide => mets au min
    if (!personsInp.value) personsInp.value = String(minPeople);

    calculate();
    return true;
  }

  function calculate() {
    if (!menuData) return;

    const persons = clampPersons();
    const unitPrice = Number(menuData.base_price || 0);

    let base = unitPrice * persons;

    let delivery = 0;
    const c = (city.value || '').trim().toLowerCase();
    if (c && c !== 'bordeaux') {
      const km = Number(distanceKm?.value || 0);
      delivery = 5 + (0.59 * km);
    }

    let discount = 0;
    if (persons >= (minPeople + 5)) discount = base * 0.10;

    const total = base + delivery - discount;

    priceMenu.textContent = base.toFixed(2) + ' €';
    priceDelivery.textContent = delivery.toFixed(2) + ' €';
    priceTotal.textContent = total.toFixed(2) + ' €';
  }

  function updateDistanceFromCity() {
    if (!distanceKm) return;

    const cityName = (city.value || '').trim().toLowerCase();

    if (!cityName) {
      distanceKm.value = '';
      calculate();
      return;
    }

    if (Object.prototype.hasOwnProperty.call(CITY_DISTANCES, cityName)) {
      distanceKm.value = CITY_DISTANCES[cityName];
    } else {
      distanceKm.value = 30;
    }

    calculate();
  }

  // ✅ MODE ÉDITION : on charge la commande, puis le menu de la commande
  async function loadOrderForEdit(orderId) {
    const res = await fetch(API + `api/orders/${orderId}/status`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || 'Impossible de charger la commande');
      return false;
    }

    const o = data.order;

    // ⚠️ IMPORTANT : il faut que ton backend renvoie menu_id ET guests
    // sinon tu ne peux pas recharger le menu en édition
    if (!o || !o.menu_id) {
      setAlert('danger', "Ton endpoint /status doit renvoyer order.menu_id (et guests).");
      return false;
    }

    // ✅ on récupère le menuId depuis la commande
    menuId = String(o.menu_id);

    // ✅ remplir champs modifiables
    date.value = o.prestation_date || '';
    time.value = o.prestation_time || '';
    personsInp.value = String(o.guests || '');

    // (optionnel) tu peux aussi re-remplir address/city si tu les stockes
    if (o.address) address.value = o.address;
    if (o.city) city.value = o.city;

    // 🔒 menu non modifiable
    menuInput.setAttribute('disabled', 'disabled');

    setAlert('info', '✏️ Modification de la commande');

    // ✅ charge le menu de la commande
    const ok = await loadMenuById(menuId);
    if (!ok) return false;

    // distance auto si ville chargée
    updateDistanceFromCity();

    // si date = today => recalcul min time
    date.dispatchEvent(new Event('change'));

    return true;
  }

  // listeners recalcul
  personsInp?.addEventListener('input', calculate);
  personsInp?.addEventListener('change', calculate);
  city?.addEventListener('input', updateDistanceFromCity);
  city?.addEventListener('change', updateDistanceFromCity);
  distanceKm?.addEventListener('input', calculate);
  distanceKm?.addEventListener('change', calculate);

  // ✅ SUBMIT : POST en création, PUT en édition
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!menuData) {
      setAlert('danger', 'Menu non chargé.');
      return;
    }

    const persons = clampPersons();
    if (persons < minPeople) {
      setAlert('danger', `Minimum ${minPeople} personnes requises.`);
      return;
    }

    calculate();

    const payload = {
      // menu_id : obligatoire en création, en édition tu peux le renvoyer aussi (mais ton back doit l’ignorer)
      menu_id: Number(menuId),
      fullname: `${first.value.trim()} ${last.value.trim()}`.trim(),
      email: email.value.trim(),
      phone: phone.value.trim(),
      address: address.value.trim(),
      city: city.value.trim(),
      prestation_date: date.value,
      prestation_time: time.value,
      guests: persons,
      distance_km: Number(distanceKm?.value || 0)
    };

    const isEdit = !!editOrderId;

    // ⚠️ ici il te faut un endpoint UPDATE côté back
    // - création : POST api/orders
    // - édition  : PUT  api/orders/{id}
    const url = isEdit
      ? API + `api/orders/${editOrderId}`
      : API + 'api/orders';

    const method = isEdit ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      setAlert('danger', data.error || (isEdit ? 'Erreur modification' : 'Erreur commande'));
      return;
    }

    setAlert('success', isEdit ? '✅ Commande modifiée !' : '✅ Commande envoyée !');

    // si édition => retourne au compte
    if (isEdit) {
      setTimeout(() => (window.location.href = 'account.html'), 600);
      return;
    }

    // reset création
    form.reset();
    personsInp.min = String(minPeople);
    personsInp.value = String(minPeople);
    calculate();
  });

  // -------- INIT --------
  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  (async () => {
    await loadUser();

    if (editOrderId) {
      // ✅ mode édition
      await loadOrderForEdit(editOrderId);
    } else {
      // ✅ mode création
      await loadMenuById(menuId);
    }
  })();
})();