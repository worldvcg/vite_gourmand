(() => {
  const API = 'http://localhost:9000/index.php?route=';

  const params = new URLSearchParams(window.location.search);
  const menuId = params.get('menu');
  const token = localStorage.getItem('authToken');

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
  const place = document.getElementById('ord-place'); // (pas envoyé au back, mais on le garde)

  const menuInput = document.getElementById('ord-menu');
  const personsInp = document.getElementById('ord-persons');

  const priceMenu = document.getElementById('price-menu');
  const priceDelivery = document.getElementById('price-delivery');
  const priceTotal = document.getElementById('price-total');

  let menuData = null;
  let minPeople = 1; // ✅ stocké globalement pour éviter les soucis après reset

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  function clampPersons() {
    // ✅ empêche toute valeur < minPeople (clavier, scroll, copier/coller, vide)
    let val = parseInt(personsInp.value, 10);

    if (!Number.isFinite(val) || val < minPeople) {
      val = minPeople;
    }

    personsInp.value = String(val);
    return val;
  }

  async function loadUser() {
    const res = await fetch(API + '/api/auth/me', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    const data = await res.json();

    if (!res.ok) {
      setAlert('danger', data.error || 'Session invalide, reconnectez-vous.');
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      window.location.href = 'login.html';
      return;
    }

    first.value = data.first_name || '';
    last.value = data.last_name || '';
    email.value = data.email || '';
    phone.value = data.phone || '';
  }

  async function loadMenu() {
    if (!menuId) {
      setAlert('danger', 'Menu manquant dans l’URL (?menu=ID).');
      menuInput.value = '—';
      return;
    }

    const res = await fetch(API + `/api/menus/${menuId}`);
    menuData = await res.json();

    if (!res.ok) {
      setAlert('danger', menuData.error || 'Menu introuvable.');
      menuInput.value = '—';
      return;
    }

    // ✅ Champs DB : title, min_people, base_price
    menuInput.value = menuData.title || `Menu #${menuData.id}`;

    minPeople = Number(menuData.min_people || 1);

    personsInp.min = String(minPeople);
    personsInp.step = '1';
    personsInp.required = true;
    personsInp.value = String(minPeople);

    calculate();
  }

  function calculate() {
    if (!menuData) return;

    const persons = clampPersons();
    const unitPrice = Number(menuData.base_price || 0);

    // Total menu
    let base = unitPrice * persons;

    // Livraison : +5 si ville != Bordeaux (comme ton backend)
    let delivery = 0;
    const c = (city.value || '').trim().toLowerCase();
    if (c && c !== 'bordeaux') delivery = 5;

    // Réduction -10% si persons >= min_people + 5
    let discount = 0;
    if (persons >= (minPeople + 5)) {
      discount = base * 0.10;
    }

    const total = base + delivery - discount;

    priceMenu.textContent = base.toFixed(2) + ' €';
    priceDelivery.textContent = delivery.toFixed(2) + ' €';
    priceTotal.textContent = total.toFixed(2) + ' €';
  }

  // ✅ recalculs + blocage min
  personsInp.addEventListener('input', calculate);
  personsInp.addEventListener('change', calculate);
  city.addEventListener('input', calculate);
  city.addEventListener('change', calculate);

  form.addEventListener('submit', async (e) => {
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

    // ✅ recalculer juste avant envoi (pour être sûr)
    calculate();

    // ✅ récupérer le total proprement (évite NaN)
    const total = Number(
      (priceTotal.textContent || '0')
        .replace('€', '')
        .replace(',', '.')
        .trim()
    ) || 0;

    const payload = {
     menu_id: Number(menuId),
    fullname: `${first.value.trim()} ${last.value.trim()}`.trim(),
    email: email.value.trim(),
    phone: phone.value.trim(),
    address: address.value.trim(),
    city: city.value.trim(), // optionnel
    prestation_date: date.value,
    prestation_time: time.value,
    guests: persons
    };

    const res = await fetch(API + '/api/orders', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      setAlert('danger', data.error || 'Erreur commande');
      return;
    }

    setAlert('success', '✅ Commande envoyée ! Un email de confirmation vous sera envoyé.');

    // ✅ reset propre : on garde le min + recalcul
    form.reset();
    personsInp.min = String(minPeople);
    personsInp.value = String(minPeople);
    calculate();
  });

  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  loadUser();
  loadMenu();
})();