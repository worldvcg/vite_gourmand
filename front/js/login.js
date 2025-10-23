// Clés de stockage local
const USERS_KEY = 'vg_users';
const SESSION_KEY = 'vg_session';

// Eléments UI
const form = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const pwdInput = document.getElementById('password');
const rememberInput = document.getElementById('remember');
const togglePwdBtn = document.getElementById('togglePwd');
const alertBox = document.getElementById('alert-box');
const forgotLink = document.getElementById('forgot-link');

// --- Seed de comptes de démo (si rien en localStorage)
function seedUsersIfNeeded() {
  const data = localStorage.getItem(USERS_KEY);
  if (data) return;

  const users = [
    { id: 'u1', role: 'user',    email: 'client@demo.fr',  password: 'Passw0rd!', firstName: 'Claire', lastName: 'Client' },
    { id: 'u2', role: 'employe', email: 'employe@demo.fr', password: 'Passw0rd!', firstName: 'Emma',   lastName: 'Employe' },
    { id: 'u3', role: 'admin',   email: 'admin@demo.fr',   password: 'Passw0rd!', firstName: 'Alex',   lastName: 'Admin'  }
  ];
  localStorage.setItem(USERS_KEY, JSON.stringify(users));
}
seedUsersIfNeeded();

function getUsers() {
  try { return JSON.parse(localStorage.getItem(USERS_KEY)) || []; }
  catch { return []; }
}

function setSession(user, remember) {
  const session = {
    id: user.id,
    email: user.email,
    role: user.role,
    firstName: user.firstName,
    lastName: user.lastName,
    createdAt: Date.now()
  };
  // Si "se souvenir", on garde en localStorage ; sinon, en sessionStorage
  if (remember) {
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
  } else {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
    // Nettoyage éventuel côté localStorage pour éviter collisions
    localStorage.removeItem(SESSION_KEY);
  }
}

function showAlert(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
  alertBox.classList.remove('d-none');
}

function clearAlert() {
  alertBox.classList.add('d-none');
  alertBox.textContent = '';
}

// Afficher / masquer mot de passe
togglePwdBtn.addEventListener('click', () => {
  const isPwd = pwdInput.type === 'password';
  pwdInput.type = isPwd ? 'text' : 'password';
  togglePwdBtn.textContent = isPwd ? 'Masquer' : 'Afficher';
});

// “Mot de passe oublié ?” (simulation)
forgotLink.addEventListener('click', (e) => {
  e.preventDefault();
  const mail = emailInput.value.trim();
  if (!mail) return showAlert('Entrez d’abord votre e-mail dans le champ ci-dessus.', 'warning');
  showAlert(`Si un compte ${mail} existe, un lien de réinitialisation a été envoyé (simulation).`, 'info');
});

// Validation simple
function validate() {
  let ok = true;

  const email = emailInput.value.trim();
  const pwd = pwdInput.value;

  // Email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const emailOK = emailRegex.test(email);
  emailInput.classList.toggle('is-invalid', !emailOK);
  emailInput.classList.toggle('is-valid', emailOK);
  ok = ok && emailOK;

  // Password
  const pwdOK = pwd.length > 0;
  pwdInput.classList.toggle('is-invalid', !pwdOK);
  pwdInput.classList.toggle('is-valid', pwdOK);
  ok = ok && pwdOK;

  return ok;
}

// Soumission du formulaire
form.addEventListener('submit', (e) => {
  e.preventDefault();
  clearAlert();
  if (!validate()) return;

  const email = emailInput.value.trim().toLowerCase();
  const pwd = pwdInput.value;

  const users = getUsers();
  const user = users.find(u => u.email.toLowerCase() === email && u.password === pwd);

  if (!user) {
    showAlert('Identifiants incorrects. Vérifiez votre e-mail et votre mot de passe.');
    return;
  }

  setSession(user, !!rememberInput.checked);

  // Redirection selon rôle (tu peux ajuster les pages ensuite)
  switch (user.role) {
    case 'admin':   window.location.href = './espace.html#admin';   break;
    case 'employe': window.location.href = './espace.html#employe'; break;
    default:        window.location.href = './espace.html#user';    break;
  }
});