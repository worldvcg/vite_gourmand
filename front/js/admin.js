console.log("✅ admin.js chargé");
(() => {
  const API = "http://localhost:9000/index.php?route=";
  const token = (localStorage.getItem("authToken") || "").trim();

function authHeaders(extra = {}) {
  return {
    ...extra,
    Authorization: "Bearer " + token
  };
}

  const alertBox = document.getElementById("alert-box");
  const employeesList = document.getElementById("employees-list");

  function setAlert(type, msg) {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = msg;
    alertBox.classList.remove("d-none");
  }

  // ==========================
  // EMPLOYEES (CRUD light)
  // ==========================
  const employeeModalEl = document.getElementById("employeeModal");
  const employeeModal = employeeModalEl ? new bootstrap.Modal(employeeModalEl) : null;
  const employeeForm = document.getElementById("employeeForm");
  const empEmail = document.getElementById("emp-email");
  const empPass = document.getElementById("emp-pass");
  const empError = document.getElementById("emp-error");

  document.getElementById("btn-open-create-employee")?.addEventListener("click", () => {
    empEmail.value = "";
    empPass.value = "";
    empError.classList.add("d-none");
    employeeModal?.show();
  });

  async function loadEmployees() {
    try {
      const res = await fetch(`${API}/api/admin/employees`, {
       cache: "no-store",
       headers: authHeaders()
    });
      const data = await res.json();

      if (!res.ok) {
        employeesList.innerHTML = `<div class="alert alert-danger">Erreur chargement employés</div>`;
        return;
      }

      renderEmployees(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error(e);
      employeesList.innerHTML = `<div class="alert alert-danger">Impossible de charger les employés</div>`;
    }
  }

  function renderEmployees(items) {
    employeesList.innerHTML = "";

    if (!items.length) {
      employeesList.innerHTML = `<div class="alert alert-info">Aucun employé</div>`;
      return;
    }

    items.forEach(u => {
      const div = document.createElement("div");
      div.className = "employee-card mb-3";

      const isActive = (u.is_active ?? 1) == 1;

      div.innerHTML = `
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="fw-semibold">${u.email}</div>
            <div class="small text-muted">Rôle : ${u.role}</div>
            <div class="small ${isActive ? "text-success" : "text-danger"}">
              Statut : ${isActive ? "actif" : "désactivé"}
            </div>
          </div>

          <div class="text-end">
            <button class="btn btn-sm ${isActive ? "btn-danger" : "btn-primary"} btn-thin js-toggle"
              data-id="${u.id}" data-active="${isActive ? 1 : 0}">
              ${isActive ? "Désactiver" : "Réactiver"}
            </button>
          </div>
        </div>
      `;

      employeesList.appendChild(div);
    });

    employeesList.querySelectorAll(".js-toggle").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        const active = btn.dataset.active === "1";
        await toggleEmployee(id, !active);
      });
    });
  }

  async function toggleEmployee(id, newActive) {
    try {
      const res = await fetch(`${API}/api/admin/employees/${id}/active`, {
        method: "PUT",
        headers: authHeaders({ "Content-Type": "application/json" }),
        body: JSON.stringify({ is_active: newActive ? 1 : 0 })
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        setAlert("danger", data.error || "Erreur update employé");
        return;
      }

      setAlert("success", "Employé mis à jour ✅");
      loadEmployees();
    } catch (e) {
      console.error(e);
      setAlert("danger", "Erreur réseau (update employé)");
    }
  }

  employeeForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    empError.classList.add("d-none");

    try {
      const res = await fetch(`${API}/api/admin/employees`, {
        method: "POST",
        headers: authHeaders({ "Content-Type": "application/json" }),
        body: JSON.stringify({
          email: empEmail.value.trim(),
          password: empPass.value.trim()
        })
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        empError.textContent = data.error || "Erreur création";
        empError.classList.remove("d-none");
        return;
      }

      employeeModal?.hide();
      setAlert("success", "Employé créé ✅ (mail à ajouter côté back)");
      loadEmployees();
    } catch (e2) {
      console.error(e2);
      empError.textContent = "Erreur réseau";
      empError.classList.remove("d-none");
    }
  });

  let chartOrders = null;

  async function loadStats() {
    const menuId = document.getElementById("stats-menu")?.value || "";
    const from = document.getElementById("stats-from")?.value || "";
    const to = document.getElementById("stats-to")?.value || "";

    const qs = new URLSearchParams();
    if (menuId) qs.set("menu_id", menuId);
    if (from) qs.set("from", from);
    if (to) qs.set("to", to);

    const q = qs.toString() ? "&" + qs.toString() : "";

// 1) commandes par menu
try {
  const res = await fetch(`${API}/api/admin/stats/orders-per-menu${q}`, {
    cache: "no-store",
    headers: authHeaders()
  });
  const data = await res.json();
  if (res.ok) renderOrdersChart(data);
} catch (e) {
  console.warn("stats orders-per-menu non dispo");
}

// 2) CA
try {
  const res = await fetch(`${API}/api/admin/stats/revenue-per-menu${q}`, {
    cache: "no-store",
    headers: authHeaders()
  });
  const data = await res.json();
  if (res.ok) renderRevenue(data);
} catch (e) {
  console.warn("stats revenue non dispo");
}
  }

  function renderOrdersChart(payload) {
    // attendu: [{label:"Menu X", value:12}, ...]
    const items = Array.isArray(payload) ? payload : [];
    const labels = items.map(i => i.label);
    const values = items.map(i => Number(i.value || 0));

    const ctx = document.getElementById("chart-orders");
    if (!ctx) return;

    if (chartOrders) chartOrders.destroy();
    chartOrders = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [{ label: "Commandes", data: values }]
      }
    });
  }

  function renderRevenue(payload) {
    // attendu: { total: 1234.56, by_menu:[{label,value}] }
    const box = document.getElementById("ca-box");
    if (!box) return;

    const total = Number(payload?.total || 0).toLocaleString("fr-FR", { style: "currency", currency: "EUR" });
    box.textContent = `Total CA : ${total}`;
  }

  document.getElementById("btn-refresh-stats")?.addEventListener("click", loadStats);

  // ==========================
  // INIT
  // ==========================
  loadEmployees();
})();