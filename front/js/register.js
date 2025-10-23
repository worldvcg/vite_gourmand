const USERS_KEY = 'vg_users';

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

$form.addEventListener('submit', (e)=>{
  e.preventDefault();

  let ok = true;
  if(!$first.value.trim()){ $first.classList.add('is-invalid'); ok=false; }
  if(!$last.value.trim()){  $last.classList.add('is-invalid');  ok=false; }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.value.trim())){
    $email.classList.add('is-invalid'); ok=false;
  }
  if(!pwdRegex.test($pwd.value)){ $pwd.classList.add('is-invalid'); ok=false; }
  if($pwd2.value !== $pwd.value || !$pwd2.value){ $pwd2.classList.add('is-invalid'); ok=false; }

  if(!ok) return;

  const users = loadUsers();
  const exists = users.some(u => u.email.toLowerCase() === $email.value.trim().toLowerCase());
  if(exists){
    showAlert("Cet email est déjà utilisé.");
    return;
  }

  const user = {
    id: uid(),
    firstName: $first.value.trim(),
    lastName:  $last.value.trim(),
    email:     $email.value.trim().toLowerCase(),
    // ⚠️ Démo front : en clair. En prod: hash côté back (password_hash).
    password:  $pwd.value,
    role:      'user'
  };
  users.push(user);
  saveUsers(users);

  showAlert("Compte créé avec succès 🎉 Vous pouvez vous connecter.", "success");
  $btn.disabled = true;
  setTimeout(()=>{ window.location.href = './login.html'; }, 1200);
});