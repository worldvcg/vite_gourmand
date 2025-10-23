const SESSION_KEY = 'vg_session';

function getSession() {
  return JSON.parse(localStorage.getItem(SESSION_KEY) || sessionStorage.getItem(SESSION_KEY) || 'null');
}

const $info = document.getElementById('user-info');
const $logout = document.getElementById('btn-logout');

const session = getSession();
if (!session) {
  // Pas connecté → retour login
  window.location.href = './login.html';
} else {
  $info.classList.remove('d-none');
  $info.textContent = `Connecté en tant que ${session.firstName} ${session.lastName} (${session.email}) — rôle : ${session.role}`;
}

$logout.addEventListener('click', () => {
  localStorage.removeItem(SESSION_KEY);
  sessionStorage.removeItem(SESSION_KEY);
  window.location.href = './login.html';
});