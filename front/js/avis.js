
  import { API_BASE } from "./config.js";
  const API = API_BASE;

(() => {
  const token = localStorage.getItem('authToken');

  if (!token) {
    window.location.href = 'login.html';
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('order');

  if (!orderId) {
    alert('Commande manquante');
    window.location.href = 'account.html';
    return;
  }

  const alertBox = document.getElementById('review-alert');
  const form = document.getElementById('review-form');
  const ratingEl = document.getElementById('review-rating');
  const commentEl = document.getElementById('review-comment');
  const orderIdEl = document.getElementById('order-id');

  orderIdEl.value = orderId;

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove('d-none');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const rating = Number(ratingEl.value);
    const comment = commentEl.value.trim();

    if (!rating || rating < 1 || rating > 5) {
      setAlert('warning', 'Veuillez sélectionner une note valide.');
      return;
    }

    try {
      const res = await fetch(API + '/api/reviews', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          order_id: Number(orderId),
          rating,
          comment
        })
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        setAlert('danger', data.error || 'Erreur lors de l’envoi de l’avis');
        return;
      }

      setAlert('success', '✅ Merci ! Votre avis a été envoyé et sera modéré.');

      setTimeout(() => {
        window.location.href = 'account.html';
      }, 1500);

    } catch (e) {
      console.error(e);
      setAlert('danger', 'Erreur réseau');
    }
  });
})();