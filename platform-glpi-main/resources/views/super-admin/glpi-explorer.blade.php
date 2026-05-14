@extends('layouts.dashboard')
@section('title','GLPI Explorer')
@section('page-title','GLPI Explorer')

@section('content')

{{-- HEADER --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-lg border-radius-lg p-3"
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center">
          <div class="bg-white border-radius-lg me-3 shadow d-flex align-items-center justify-content-center"
               style="width:52px;height:52px;">
            <i class="material-symbols-rounded" style="font-size:30px;color:var(--color-primary);">api</i>
          </div>
          <div>
            <h5 class="text-white font-weight-bolder mb-0">GLPI Explorer</h5>
            <p class="text-white text-sm mb-0 opacity-8">Toutes les APIs GLPI en temps réel</p>
          </div>
        </div>
        <div id="glpiStatus" class="badge px-3 py-2" style="background:rgba(255,255,255,0.2);color:white;font-size:12px;">
          <span class="spinner-border spinner-border-sm me-1"></span> Connexion...
        </div>
      </div>
    </div>
  </div>
</div>

{{-- TABS NAVIGATION --}}
<div class="card mb-4">
  <div class="card-body py-2 px-4">
    <ul class="nav nav-pills gap-2 flex-wrap" id="glpiTabs">
      <li class="nav-item">
        <a class="nav-link active" href="#" onclick="showTab('session')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">info</i>
          Session & Config
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('tickets')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">confirmation_number</i>
          Tickets GLPI
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('users')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">group</i>
          Utilisateurs
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('categories')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">category</i>
          Catégories
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('profiles')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">manage_accounts</i>
          Profils & Entités
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('search')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">search</i>
          Recherche
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showTab('documents')">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">attach_file</i>
          Documents
        </a>
      </li>
    </ul>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: SESSION & CONFIG --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-session" class="tab-content">
  <div class="row">
    {{-- Session Info --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 font-weight-bold">Session GLPI</h6>
          <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                  onclick="loadSection('session-data', '/glpi/session/info')">
            <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
            Actualiser
          </button>
        </div>
        <div class="card-body px-4 pb-4">
          <div id="session-data">
            <p class="text-xs text-secondary">Cliquez sur Actualiser pour charger</p>
          </div>
        </div>
      </div>
    </div>

    {{-- GLPI Config --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 font-weight-bold">Configuration GLPI</h6>
          <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                  onclick="loadSection('config-data', '/glpi/config')">
            <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
            Charger
          </button>
        </div>
        <div class="card-body px-4 pb-4">
          <div id="config-data">
            <p class="text-xs text-secondary">Cliquez sur Charger</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: TICKETS GLPI --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-tickets" class="tab-content" style="display:none;">

  {{-- Stats par statut --}}
  <div class="row mb-4" id="ticket-stats-cards">
    <div class="col-12 text-center py-3">
      <button class="btn text-white" style="background:var(--color-primary);"
              onclick="loadTicketStats()">
        <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">bar_chart</i>
        Charger les statistiques GLPI
      </button>
    </div>
  </div>

  {{-- Liste tickets --}}
  <div class="card mb-4">
    <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 font-weight-bold">Tickets GLPI</h6>
      <div class="d-flex gap-2">
        <input type="text" id="ticketSearch" class="form-control form-control-sm"
               placeholder="Rechercher..." style="width:200px;"
               oninput="searchGlpiTickets(this.value)">
        <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                onclick="loadSection('tickets-list', '/glpi/tickets')">
          <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
          Tous les tickets
        </button>
      </div>
    </div>
    <div class="card-body px-4 pb-4">
      <div id="tickets-list">
        <p class="text-xs text-secondary">Cliquez sur "Tous les tickets" pour charger</p>
      </div>
    </div>
  </div>

  {{-- Détail ticket --}}
  <div class="card" id="ticket-detail-card" style="display:none;">
    <div class="card-header pb-0 pt-3 px-4">
      <h6 class="mb-0 font-weight-bold">Détail ticket GLPI <span id="ticket-detail-id"></span></h6>
    </div>
    <div class="card-body px-4 pb-4">
      <div id="ticket-detail-data"></div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: UTILISATEURS GLPI --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-users" class="tab-content" style="display:none;">
  <div class="card">
    <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 font-weight-bold">Utilisateurs GLPI</h6>
      <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
              onclick="loadUsers()">
        <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
        Charger
      </button>
    </div>
    <div class="card-body px-4 pb-4">
      <div id="users-list">
        <p class="text-xs text-secondary">Cliquez sur Charger</p>
      </div>

      {{-- User picture --}}
      <div id="user-picture-section" style="display:none;" class="mt-3 p-3 border-radius-md" style="background:#f8f9ff;">
        <p class="text-xs font-weight-bold mb-2">Photo utilisateur GLPI</p>
        <img id="user-picture-img" src="" alt="Photo"
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--color-primary);">
        <p class="text-xs text-secondary mt-2" id="user-picture-name"></p>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: CATÉGORIES --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-categories" class="tab-content" style="display:none;">
  <div class="card">
    <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 font-weight-bold">Catégories GLPI</h6>
      <div class="d-flex gap-2">
        <button class="btn btn-sm mb-0 text-white" style="background:#28a745;"
                onclick="syncCategories()">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">sync</i>
          Sync vers local
        </button>
        <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                onclick="loadSection('categories-list', '/glpi/categories')">
          <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
          Charger
        </button>
      </div>
    </div>
    <div class="card-body px-4 pb-4">
      <div id="categories-list">
        <p class="text-xs text-secondary">Cliquez sur Charger</p>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: PROFILS & ENTITÉS --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-profiles" class="tab-content" style="display:none;">
  <div class="row">
    {{-- Profils --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 font-weight-bold">Profils GLPI</h6>
          <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                  onclick="loadSection('profiles-list', '/glpi/profiles')">
            <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
            Charger
          </button>
        </div>
        <div class="card-body px-4 pb-4">
          <div id="profiles-list">
            <p class="text-xs text-secondary">Cliquez sur Charger</p>
          </div>

          {{-- Profil actif --}}
          <div class="mt-3">
            <button class="btn btn-sm mb-0 btn-outline-secondary w-100"
                    onclick="loadSection('active-profile', '/glpi/profiles/active')">
              Voir profil actif
            </button>
            <div id="active-profile" class="mt-2"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- Entités --}}
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 font-weight-bold">Entités GLPI</h6>
          <button class="btn btn-sm mb-0 text-white" style="background:var(--color-primary);"
                  onclick="loadSection('entities-list', '/glpi/entities')">
            <i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">refresh</i>
            Charger
          </button>
        </div>
        <div class="card-body px-4 pb-4">
          <div id="entities-list">
            <p class="text-xs text-secondary">Cliquez sur Charger</p>
          </div>

          {{-- Entité active --}}
          <div class="mt-3">
            <button class="btn btn-sm mb-0 btn-outline-secondary w-100"
                    onclick="loadSection('active-entity', '/glpi/entities/active')">
              Voir entité active
            </button>
            <div id="active-entity" class="mt-2"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: RECHERCHE --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-search" class="tab-content" style="display:none;">
  <div class="card">
    <div class="card-header pb-0 pt-3 px-4">
      <h6 class="mb-0 font-weight-bold">Recherche GLPI</h6>
    </div>
    <div class="card-body px-4 pb-4">

      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Type</label>
          <select id="searchItemtype" class="form-control form-select" style="height:40px;">
            <option value="Ticket">Ticket</option>
            <option value="User">User</option>
            <option value="ITILCategory">Catégorie</option>
            <option value="Computer">Computer</option>
            <option value="Software">Software</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Champ</label>
          <select id="searchField" class="form-control form-select" style="height:40px;">
            <option value="1">Titre/Nom (field 1)</option>
            <option value="12">Statut (field 12)</option>
            <option value="3">Priorité (field 3)</option>
            <option value="2">ID (field 2)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">Valeur</label>
          <input type="text" id="searchValue" class="form-control" placeholder="Terme de recherche..."
                 style="height:40px;">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn w-100 mb-0 text-white" style="background:var(--color-primary);"
                  onclick="runSearch()">
            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">search</i>
            Chercher
          </button>
        </div>
      </div>

      {{-- Search options --}}
      <div class="mb-3">
        <button class="btn btn-sm btn-outline-secondary mb-0"
                onclick="loadSearchOptions()">
          <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">list</i>
          Voir les champs disponibles
        </button>
      </div>

      <div id="search-results">
        <p class="text-xs text-secondary">Résultats de recherche apparaîtront ici</p>
      </div>
      <div id="search-options" style="display:none;" class="mt-3"></div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- TAB: DOCUMENTS --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div id="tab-documents" class="tab-content" style="display:none;">
  <div class="row">
    {{-- Upload --}}
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header pb-0 pt-3 px-4">
          <h6 class="mb-0 font-weight-bold">Upload document vers GLPI</h6>
        </div>
        <div class="card-body px-4 pb-4">
          <div class="mb-3">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Fichier
            </label>
            <input type="file" id="uploadFile" class="form-control" style="height:40px;">
          </div>
          <button class="btn w-100 mb-0 text-white" style="background:var(--color-primary);"
                  onclick="uploadDoc()">
            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">upload</i>
            Uploader vers GLPI
          </button>
          <div id="upload-result" class="mt-3"></div>
        </div>
      </div>
    </div>

    {{-- Download --}}
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header pb-0 pt-3 px-4">
          <h6 class="mb-0 font-weight-bold">Télécharger document GLPI</h6>
        </div>
        <div class="card-body px-4 pb-4">
          <div class="mb-3">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              ID Document GLPI
            </label>
            <input type="number" id="downloadDocId" class="form-control"
                   placeholder="Ex: 42" style="height:40px;">
          </div>
          <a id="downloadLink" href="#" target="_blank"
             class="btn w-100 mb-0 text-white" style="background:#28a745;"
             onclick="buildDownloadLink(event)">
            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">download</i>
            Télécharger
          </a>
          <p class="text-xs text-secondary mt-2">
            Téléchargement via: <code>/glpi/documents/{id}/download</code>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- JAVASCRIPT --}}
{{-- ══════════════════════════════════════════════════════ --}}
<script>
// ── CSRF token — accessible après DOMContentLoaded ──────
let CSRF = '';
document.addEventListener('DOMContentLoaded', function() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    CSRF = meta ? meta.getAttribute('content') : '';
    checkGlpiStatus();
});

// ── Tab navigation ──────────────────────────────────────
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + name).style.display = 'block';
    document.querySelectorAll('#glpiTabs .nav-link').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');

    // ✅ Auto-charger les données au clic de l'onglet
    const autoLoad = {
        'tickets':    () => loadTicketStats(),
        'users':      () => loadUsers(),
        'categories': () => loadSection('categories-list', '/glpi/categories'),
        'profiles':   () => {
            loadSection('profiles-list', '/glpi/profiles');
            loadSection('entities-list', '/glpi/entities');
        },
        'search':     () => {},  // manuel
        'documents':  () => {},  // manuel
        'session':    () => {},  // manuel
    };
    if (autoLoad[name]) autoLoad[name]();
}

// ── Generic loader ──────────────────────────────────────
async function loadSection(targetId, url) {
    const el = document.getElementById(targetId);
    el.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span> Chargement...</div>';
    try {
        const res  = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        renderData(el, data.data ?? data);
    } catch(e) {
        el.innerHTML = `<div class="alert alert-danger text-xs py-2">Erreur: ${e.message}</div>`;
    }
}

// ── Render JSON data as table or key-value ──────────────
function renderData(el, data) {
    if (!data || (Array.isArray(data) && data.length === 0)) {
        el.innerHTML = '<p class="text-xs text-secondary">Aucune donnée</p>';
        return;
    }

    if (Array.isArray(data)) {
        // Table
        const keys = Object.keys(data[0]).slice(0, 8); // max 8 cols
        let html = `<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:11px;">
            <thead><tr>${keys.map(k => `<th class="text-uppercase text-secondary" style="font-size:10px;">${k}</th>`).join('')}</tr></thead>
            <tbody>`;
        data.slice(0, 50).forEach(row => {
            html += `<tr>${keys.map(k => `<td>${row[k] ?? '-'}</td>`).join('')}</tr>`;
        });
        html += `</tbody></table></div>`;
        if (data.length > 50) html += `<p class="text-xs text-secondary mt-1">Affichage 50/${data.length} éléments</p>`;
        el.innerHTML = html;
    } else if (typeof data === 'object') {
        // Key-value
        const entries = Object.entries(data).slice(0, 20);
        let html = '<div class="row">';
        entries.forEach(([k, v]) => {
            html += `<div class="col-6 mb-1">
                <span class="text-xs text-secondary">${k}:</span>
                <span class="text-xs font-weight-bold ms-1">${typeof v === 'object' ? JSON.stringify(v).slice(0,50) : v}</span>
            </div>`;
        });
        html += '</div>';
        el.innerHTML = html;
    }
}

// ── Check GLPI connection on load ───────────────────────
async function checkGlpiStatus() {
    try {
        const res  = await fetch('/glpi/config', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        const statusEl = document.getElementById('glpiStatus');
        if (data.success) {
            const ver = data.data?.glpi_version ?? '';
            statusEl.innerHTML = `<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">check_circle</i> GLPI ${ver} Connecté`;
            statusEl.style.background = 'rgba(76,175,80,0.3)';
        } else {
            statusEl.innerHTML = `<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">error</i> Déconnecté`;
            statusEl.style.background = 'rgba(244,67,54,0.3)';
        }
    } catch(e) {
        document.getElementById('glpiStatus').innerHTML = '❌ GLPI inaccessible';
    }
}

// ── Ticket stats ────────────────────────────────────────
async function loadTicketStats() {
    const el = document.getElementById('ticket-stats-cards');
    el.innerHTML = '<div class="col-12 text-center"><span class="spinner-border spinner-border-sm"></span></div>';
    try {
        const res  = await fetch('/glpi/tickets/stats', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        const stats = data.data ?? {};
        const labels = {
            new:'Nouveaux', assigned:'Assignés', planned:'Planifiés',
            waiting:'En attente', solved:'Résolus', closed:'Fermés'
        };
        const colors = {
            new:'#f57c00', assigned:'#1565c0', planned:'#6a1b9a',
            waiting:'#f9a825', solved:'#2e7d32', closed:'#455a64'
        };
        let html = '';
        Object.entries(labels).forEach(([k, label]) => {
            html += `<div class="col-xl-2 col-sm-4 col-6 mb-3">
                <div class="text-center p-3 border-radius-md" style="background:#f8f9ff;border:1px solid #e0e7ff;">
                    <h4 class="font-weight-bolder mb-0" style="color:${colors[k]}">${stats[k] ?? 0}</h4>
                    <p class="text-xs mb-0 text-secondary">${label}</p>
                </div>
            </div>`;
        });
        el.innerHTML = `<div class="col-12"><div class="row">${html}</div></div>`;
    } catch(e) {
        el.innerHTML = '<div class="col-12"><p class="text-danger text-xs">Erreur chargement stats</p></div>';
    }
}

// ── Search GLPI tickets ─────────────────────────────────
let searchTimeout;
function searchGlpiTickets(q) {
    clearTimeout(searchTimeout);
    if (q.length < 3) return;
    searchTimeout = setTimeout(async () => {
        await loadSection('tickets-list', `/glpi/search/tickets?q=${encodeURIComponent(q)}`);
    }, 500);
}

// ── Ticket detail ───────────────────────────────────────
async function loadTicketDetail(glpiId) {
    document.getElementById('ticket-detail-card').style.display = 'block';
    document.getElementById('ticket-detail-id').textContent = `#${glpiId}`;
    await loadSection('ticket-detail-data', `/glpi/tickets/${glpiId}/detail`);
    document.getElementById('ticket-detail-card').scrollIntoView({ behavior: 'smooth' });
}

// ── Users with picture ──────────────────────────────────
async function loadUsers() {
    const el = document.getElementById('users-list');
    el.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';
    try {
        const res  = await fetch('/glpi/users', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        const users = data.data ?? [];

        let html = `<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:11px;">
            <thead><tr>
                <th class="text-secondary text-uppercase" style="font-size:10px;">ID</th>
                <th class="text-secondary text-uppercase" style="font-size:10px;">Nom</th>
                <th class="text-secondary text-uppercase" style="font-size:10px;">Email</th>
                <th class="text-secondary text-uppercase" style="font-size:10px;">Photo</th>
            </tr></thead><tbody>`;

        users.slice(0, 30).forEach(u => {
            html += `<tr>
                <td>${u.id}</td>
                <td>${u.name ?? '-'}</td>
                <td>${u.email ?? '-'}</td>
                <td>
                    <button class="btn btn-sm mb-0 btn-outline-secondary py-0 px-1"
                            style="font-size:10px;"
                            onclick="loadUserPicture(${u.id}, '${u.name}')">
                        <i class="material-symbols-rounded" style="font-size:12px;vertical-align:middle;">photo</i>
                    </button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        if (users.length > 30) html += `<p class="text-xs text-secondary mt-1">30/${users.length} affichés</p>`;
        el.innerHTML = html;
    } catch(e) {
        el.innerHTML = `<div class="alert alert-danger text-xs py-2">Erreur: ${e.message}</div>`;
    }
}

// ── User picture ────────────────────────────────────────
async function loadUserPicture(userId, userName) {
    // ✅ API: GET /glpi/users/{id}/picture
    const section = document.getElementById('user-picture-section');
    const img     = document.getElementById('user-picture-img');
    const name    = document.getElementById('user-picture-name');
    section.style.display = 'block';
    img.src = `/glpi/users/${userId}/picture`;
    name.textContent = userName;
}

// ── Sync categories ─────────────────────────────────────
async function syncCategories() {
    const btn = event.target;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sync...';
    btn.disabled  = true;
    try {
        const res  = await fetch('/super-admin/glpi/sync-categories', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        btn.innerHTML = `✅ ${data.synced ?? 0} catégories synchronisées`;
        setTimeout(() => loadSection('categories-list', '/glpi/categories'), 500);
    } catch(e) {
        btn.innerHTML = '❌ Erreur sync';
    } finally {
        btn.disabled = false;
        setTimeout(() => {
            btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">sync</i> Sync vers local';
        }, 3000);
    }
}

// ── Search ──────────────────────────────────────────────
async function runSearch() {
    const itemtype = document.getElementById('searchItemtype').value;
    const field    = document.getElementById('searchField').value;
    const value    = document.getElementById('searchValue').value;
    const el       = document.getElementById('search-results');

    el.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Recherche...';

    const url = `/glpi/search/${itemtype}?criteria[0][field]=${field}&criteria[0][searchtype]=contains&criteria[0][value]=${encodeURIComponent(value)}`;
    await loadSection('search-results', url);
}

async function loadSearchOptions() {
    const itemtype = document.getElementById('searchItemtype').value;
    const el = document.getElementById('search-options');
    el.style.display = 'block';
    await loadSection('search-options', `/glpi/search/${itemtype}/options`);
}

// ── Upload document ─────────────────────────────────────
async function uploadDoc() {
    const file = document.getElementById('uploadFile').files[0];
    if (!file) { alert('Choisissez un fichier'); return; }

    const formData = new FormData();
    formData.append('file', file);

    const el = document.getElementById('upload-result');
    el.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Upload...';

    try {
        const res  = await fetch('/glpi/documents/upload', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            el.innerHTML = `<div class="alert alert-success py-2 text-xs">
                ✅ Document uploadé — ID GLPI: <strong>${data.data?.id ?? 'N/A'}</strong>
            </div>`;
        } else {
            el.innerHTML = `<div class="alert alert-danger py-2 text-xs">❌ ${data.error ?? 'Erreur upload'}</div>`;
        }
    } catch(e) {
        el.innerHTML = `<div class="alert alert-danger py-2 text-xs">❌ ${e.message}</div>`;
    }
}

// ── Download document ───────────────────────────────────
function buildDownloadLink(e) {
    const id = document.getElementById('downloadDocId').value;
    if (!id) { e.preventDefault(); alert('Entrez un ID document'); return; }
    // ✅ API: GET /glpi/documents/{id}/download
    document.getElementById('downloadLink').href = `/glpi/documents/${id}/download`;
}

// ── Init handled by DOMContentLoaded above ──────────────
</script>

@endsection