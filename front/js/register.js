  import { API_BASE } from "./config.js";
  const API = API_BASE;
const USERS_KEY = 'vg_users';

// UI
const $form = document.getElementById('register-form');
const $first = document.getElementById('firstName');
const $last  = document.getElementById('lastName');
const $email = document.getElementById('email');
const $phone = document.getElementById('phone');
const $city  = document.getElementById('city');
const $addr  = document.getElementById('address');
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

// Regex mot de passe (10+ avec maj/min/chiffre/spécial)
const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/;

// Téléphone FR simple (tolérant)
function isValidPhone(v){
  const s = String(v || '').replace(/\s+/g,'').trim();
  return /^(\+33|0)[1-9]\d{8}$/.test(s);
}

[$first,$last,$email,$phone,$city,$addr,$pwd,$pwd2].forEach(el=>{
  if (!el) return;
  el.addEventListener('input',()=>{
    el.classList.remove('is-invalid');
    hideAlert();
  });
});

$form.addEventListener('submit', async (e) => {
  e.preventDefault();
  hideAlert();

  let ok = true;

  if (!$first.value.trim()) { $first.classList.add('is-invalid'); ok = false; }
  if (!$last.value.trim())  { $last.classList.add('is-invalid');  ok = false; }

  const emailVal = $email.value.trim().toLowerCase();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
    $email.classList.add('is-invalid'); ok = false;
  }

  const phoneVal = $phone.value.trim();
  if (!isValidPhone(phoneVal)) { $phone.classList.add('is-invalid'); ok = false; }

  if (!$city.value.trim()) { $city.classList.add('is-invalid'); ok = false; }
  if (!$addr.value.trim()) { $addr.classList.add('is-invalid'); ok = false; }

  if (!pwdRegex.test($pwd.value)) { $pwd.classList.add('is-invalid'); ok = false; }
  if ($pwd2.value !== $pwd.value || !$pwd2.value) { $pwd2.classList.add('is-invalid'); ok = false; }

  if (!ok) return;

  $btn.disabled = true;
  showAlert('Création du compte…', 'info');

  try {
    const res = await fetch(API + '/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: emailVal,
        password: $pwd.value,
        first_name: $first.value.trim(),
        last_name:  $last.value.trim(),
        phone: phoneVal,
        city: $city.value.trim(),
        address: $addr.value.trim()
      })
    });

    const ct = (res.headers.get('content-type') || '').toLowerCase();
    const data = ct.includes('application/json') ? await res.json() : { error: await res.text() };

    if (!res.ok) {
      if (res.status === 409) { showAlert('Cet email est déjà utilisé.', 'warning'); $btn.disabled = false; return; }
      if (res.status === 400) { showAlert(data.error || 'Champs manquants.', 'warning'); $btn.disabled = false; return; }
      showAlert(data.error || 'Erreur serveur.', 'danger');
      $btn.disabled = false;
      return;
    }

    showAlert('Compte créé ✅ Vous allez être redirigé vers la connexion…', 'success');
    setTimeout(() => { window.location.href = './login.html'; }, 900);

  } catch (err) {
    console.error(err);
    showAlert('Impossible de contacter le serveur (MAMP/URL ?).', 'danger');
    $btn.disabled = false;
  }
});