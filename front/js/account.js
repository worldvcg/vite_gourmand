(() => {
  const API = 'http://localhost:8888/vite_gourmand/back/public/index.php?route=';

  const alertBox = document.getElementById('alert-box');
  const first = document.getElementById('me-first');
  const last  = document.getElementById('me-last');
  const email = document.getElementById('me-email');

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  async function load() {
    // 1️⃣ Récupère et nettoie le token
    let token = localStorage.getItem('authToken');
    console.log('🔹 Token brut =', token);

    if (!token) {
      setAlert('danger', 'Token manquant. Veuillez vous reconnecter.');
      return;
    }
    token = token.trim(); // <- enlève espaces éventuels
    console.log('🔹 Token après trim =', token);
    console.log('🔹 Longueur token =', token.length);

    // 2️⃣ Appel /me avec le header Authorization
    const url = API + '/api/auth/me';
    console.log('📡 Requête vers :', url);

    try {
      const res = await fetch(url, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      console.log('📥 Statut /me =', res.status);

      // On lit la réponse brute pour debug
      const text = await res.text();
      console.log('📦 Réponse brute /me =', text);

      const data = JSON.parse(text || '{}');

      if (!res.ok) {
        setAlert('danger', data.error || 'Session invalide. Veuillez vous reconnecter.');
        return;
      }

      // 3️⃣ Affiche les infos utilisateur
      first.textContent = data.first_name || '—';
      last.textContent  = data.last_name  || '—';
      email.textContent = data.email      || '—';
      alertBox.classList.add('d-none');

      console.log('✅ Données utilisateur chargées', data);

    } catch (err) {
      console.error('❌ Erreur /me :', err);
      setAlert('danger', 'Erreur réseau. Réessayez.');
    }
  }

  load();
})();