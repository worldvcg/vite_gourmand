const USERS_KEY = 'vg_users';
const API = 'http://localhost:9000/index.php?route=';

function loadUsers() {
  try { return JSON.parse(localStorage.getItem(USERS_KEY)) || []; }
  catch { return []; }
}
function saveUsers(list) { localStorage.setItem(USERS_KEY, JSON.stringify(list)); }
const uid = () => 'u_' + Math.random().toString(36).slice(2,10);

// UI
const $form = document.getElementById('register-form');
const $first = document.getElementById('firstName');
const $last  = document.getElementById('lastName');
const $email = document.getElementById('email');
const $pwd   = document.getElementById('password');
const $pwd2  = document.getElementById('password2');
const $alert = document.getElementById('alert-box');
const $btn   = document.getElementById('btnRegister');

function showAlert(msg, type='danger'){
  $alert.className = `alert alert-${type}`;
  $alert.textContent = msg;
  $alert.classList.remove('d-none');
}
function hideAlert(){ $alert.classList.add('d-none'); }

[$first,$last,$email,$pwd,$pwd2].forEach(el=>{
  el.addEventListener('input',()=>{
    el.classList.remove('is-invalid');
    hideAlert();
  });
});

// Regex mot de passe (exigence sujet)
const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/;

$form.addEventListener('submit', async (e) => {
  e.preventDefault();
  hideAlert();

  let ok = true;
  if (!$first.value.trim()) { $first.classList.add('is-invalid'); ok = false; }
  if (!$last.value.trim())  { $last.classList.add('is-invalid');  ok = false; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.value.trim())) {
    $email.classList.add('is-invalid'); ok = false;
  }
  if (!pwdRegex.test($pwd.value)) { $pwd.classList.add('is-invalid'); ok = false; }
  if ($pwd2.value !== $pwd.value || !$pwd2.value) { $pwd2.classList.add('is-invalid'); ok = false; }
  if (!ok) return;

  // Désactive le bouton pendant l’envoi
  $btn.disabled = true;
  showAlert('Création du compte…', 'info');

  try {
    const res = await fetch(API + '/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: $email.value.trim().toLowerCase(),
        password: $pwd.value,
        first_name: $first.value.trim(),
        last_name:  $last.value.trim()
      })
    });

    // Lis proprement la réponse (JSON ou texte)
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    const data = ct.includes('application/json') ? await res.json() : { error: await res.text() };

    if (!res.ok) {
      // cas fréquents côté API
      if (res.status === 409) { showAlert('Cet email est déjà utilisé.', 'warning'); $btn.disabled = false; return; }
      if (res.status === 400) { showAlert('Champs manquants.', 'warning'); $btn.disabled = false; return; }
      showAlert(data.error || 'Erreur serveur.', 'danger');
      $btn.disabled = false;
      return;
    }

    // Succès
    showAlert('Compte créé ✅ Vous allez être redirigé vers la connexion…', 'success');
    setTimeout(() => { window.location.href = './login.html'; }, 900);

  } catch (err) {
    console.error(err);
    showAlert('Impossible de contacter le serveur (MAMP/URL ?).', 'danger');
    $btn.disabled = false;
  }
});