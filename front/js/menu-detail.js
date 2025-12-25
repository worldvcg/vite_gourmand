(() => {
  const API = 'http://localhost:9000/index.php?route=';

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

  async function loadMenu() {
    try {
      const res = await fetch(API + `/api/menus/${menuId}`, { cache: 'no-store' });
      const data = await res.json();

      if (!res.ok) {
        setAlert('danger', data.error || 'Menu introuvable');
        return;
      }

      // ✅ Champs DB
      nameEl.textContent = data.title ?? 'Menu';
      descEl.textContent = data.description ?? '';
      priceEl.textContent = Number(data.base_price ?? 0).toFixed(2);
      condEl.textContent = data.conditions_text ?? '';
      minEl.textContent = data.min_people ?? '—';

      imgEl.src = resolveImg(data.image);
      imgEl.classList.remove('d-none');

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

    window.location.href = `commande.html?menu=${menuId}`;
  });

  loadMenu();
})();