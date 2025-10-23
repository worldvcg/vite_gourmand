const qs = new URLSearchParams(location.search);
const id = qs.get('id');
const $detail = document.getElementById('menu-detail');

async function loadOne() {
  const res = await fetch('./data/menus.json', { cache: 'no-store' });
  const menus = await res.json();
  const m = menus.find(x => x.id === id);
  if (!m) {
    $detail.innerHTML = `<div class="col-12"><div class="alert alert-danger">Menu introuvable.</div></div>`;
    return;
  }
  // Exemple d’images : adapte selon tes données réelles plus tard
  $detail.innerHTML = `
    <div class="col-12 col-lg-6">
      <img class="img-fluid rounded border" src="./images/${m.id || 'placeholder'}.jpg" onerror="this.src='./images/placeholder.jpg';" alt="${m.titre}">
    </div>
    <div class="col-12 col-lg-6">
      <h1 class="h3">${m.titre}</h1>
      <p class="text-muted">${m.description}</p>
      <div class="mb-2"><strong>Thème :</strong> ${m.theme} • <strong>Régime :</strong> ${m.regime}</div>
      <div class="mb-2"><strong>Personnes min. :</strong> ${m.minPersonnes} • <strong>Prix (min) :</strong> ${m.prixBase}€</div>
      <div class="alert alert-warning small">
        <strong>Conditions :</strong> commander au moins ${m.minPersonnes} personnes. 
        Réduction <strong>10%</strong> si +5 personnes au-dessus du minimum.
      </div>
      <a class="btn btn-primary" href="./order.html?id=${m.id}">Commander</a>
    </div>
  `;
}
loadOne();
