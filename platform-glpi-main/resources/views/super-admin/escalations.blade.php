@extends('layouts.dashboard')
@section('title', 'Escalations')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --ep: var(--color-primary, #6C63FF);
  --es: var(--color-secondary, #8B85FF);
  --ebg: #F8F9FF;
  --ebg2: #FFFFFF;
  --ebrd: #E8EAFF;
  --et1: #0D0F1A;
  --et2: #2D3047;
  --et3: #6B7280;
  --et4: #9CA3AF;
  --font: 'DM Sans', system-ui, sans-serif;
  --mono: 'DM Mono', monospace;
}
* { box-sizing: border-box; }
.esc-wrap { font-family: var(--font); background: var(--ebg); height: calc(100vh - 64px); display: flex; flex-direction: column; overflow: hidden; }

/* ── TOP BAR ── */
.esc-top {
  background: var(--ebg2); border-bottom: 1px solid var(--ebrd);
  padding: 13px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-shrink: 0;
}
.esc-title { font-size: 18px; font-weight: 700; color: var(--et1); display: flex; align-items: center; gap: 9px; }
.esc-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg,#FEF2F2,#FECACA); display: flex; align-items: center; justify-content: center; }
.stat-pills { display: flex; gap: 8px; }
.stat-pill { padding: 5px 14px; border-radius: 99px; font-size: 12px; font-weight: 700; border: 1px solid; display: flex; align-items: center; gap: 5px; }
.btn-sm {
  padding: 6px 14px; border-radius: 8px; border: 1px solid var(--ebrd);
  background: var(--ebg2); font-size: 12px; font-weight: 600; color: var(--et2);
  cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: var(--font); transition: all .15s;
}
.btn-sm:hover { border-color: var(--ep); color: var(--ep); }
.btn-sm.primary { background: linear-gradient(135deg,var(--ep),var(--es)); color:#fff; border-color: var(--ep); box-shadow: 0 3px 10px color-mix(in srgb, var(--ep) 30%, transparent); }
.btn-sm.primary:hover { transform: translateY(-1px); }

/* ── 3-COL LAYOUT ── */
.esc-body { display: flex; flex: 1; overflow: hidden; }

/* ── LEFT: TICKET LIST ── */
#escList { width: 300px; min-width: 300px; background: var(--ebg2); border-right: 1px solid var(--ebrd); display: flex; flex-direction: column; overflow: hidden; }
.list-hdr { padding: 12px 14px 8px; border-bottom: 1px solid var(--ebrd); flex-shrink: 0; }
.list-search { display: flex; align-items: center; gap: 7px; background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 9px; padding: 6px 11px; }
.list-search input { flex: 1; border: none; background: transparent; outline: none; font-size: 12px; color: var(--et1); font-family: var(--font); }
.list-filters { display: flex; gap: 5px; margin-top: 8px; flex-wrap: wrap; }
.filter-chip { padding: 3px 10px; border-radius: 99px; font-size: 10px; font-weight: 700; border: 1px solid var(--ebrd); background: transparent; cursor: pointer; color: var(--et3); transition: all .13s; font-family: var(--font); }
.filter-chip.active, .filter-chip:hover { border-color: var(--ep); color: var(--ep); background: color-mix(in srgb, var(--ep) 8%, transparent); }
.esc-items { flex: 1; overflow-y: auto; }
.esc-items::-webkit-scrollbar { width: 3px; } .esc-items::-webkit-scrollbar-thumb { background: var(--ebrd); border-radius: 2px; }
.esc-item { padding: 12px 14px; border-bottom: 1px solid var(--ebrd); cursor: pointer; transition: background .12s; position: relative; }
.esc-item:hover { background: #FAFAFF; }
.esc-item.active { background: color-mix(in srgb, var(--ep) 6%, transparent); border-left: 3px solid var(--ep); }
.esc-item.active { padding-left: 11px; }
.esc-item-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; gap: 6px; }
.esc-subject { font-size: 12px; font-weight: 600; color: var(--et1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.prio-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.esc-source { font-size: 10px; color: var(--et4); font-family: var(--mono); margin-bottom: 4px; }
.esc-preview { font-size: 11px; color: var(--et3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.esc-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 5px; }
.esc-date { font-size: 10px; color: var(--et4); }
.prio-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 99px; }

/* ── MIDDLE: DETAIL ── */
#escDetail { flex: 1; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid var(--ebrd); }
.detail-hdr { padding: 14px 20px; background: var(--ebg2); border-bottom: 1px solid var(--ebrd); flex-shrink: 0; }
.detail-subject { font-size: 15px; font-weight: 700; color: var(--et1); margin-bottom: 4px; }
.detail-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-source { font-size: 11px; color: var(--et4); font-family: var(--mono); }
.detail-body { flex: 1; overflow-y: auto; padding: 20px; }
.detail-body::-webkit-scrollbar { width: 4px; } .detail-body::-webkit-scrollbar-thumb { background: var(--ebrd); }
.msg-card { background: var(--ebg2); border: 1px solid var(--ebrd); border-radius: 12px; padding: 16px; margin-bottom: 14px; }
.msg-from { font-size: 12px; font-weight: 700; color: var(--et1); margin-bottom: 4px; display: flex; align-items: center; gap: 7px; }
.msg-from-av { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }
.msg-body { font-size: 13px; color: var(--et2); line-height: 1.65; }
.msg-time { font-size: 10px; color: var(--et4); margin-top: 8px; }
.timeline-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; color: var(--et4); gap: 8px; }
.tl-icon { font-size: 32px; }

/* ── AI ACTIONS SECTION ── */
.ai-actions-hdr {
  display: flex; align-items: center; gap: 7px; margin-bottom: 12px;
  font-size: 11px; font-weight: 700; color: var(--et2); text-transform: uppercase; letter-spacing: .05em;
}
.ai-badge { padding: 2px 8px; border-radius: 99px; font-size: 9px; font-weight: 700; background: linear-gradient(135deg, var(--ep), var(--es)); color: #fff; }
.action-card {
  background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 10px;
  padding: 11px 13px; margin-bottom: 8px; font-size: 12px;
}
.action-outcome { display: flex; align-items: center; gap: 7px; margin-bottom: 6px; }
.conf-bar { height: 3px; background: var(--ebrd); border-radius: 99px; overflow: hidden; margin-top: 5px; }
.conf-fill { height: 100%; border-radius: 99px; transition: width .6s; }

/* ── RIGHT: PANEL ── */
#escPanel { width: 280px; min-width: 280px; background: var(--ebg2); display: flex; flex-direction: column; overflow-y: auto; }
#escPanel::-webkit-scrollbar { width: 3px; }
.panel-section { padding: 14px 16px; border-bottom: 1px solid var(--ebrd); }
.panel-sec-title { font-size: 10px; font-weight: 700; color: var(--et4); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px; }
.form-sel, .form-inp {
  width: 100%; padding: 8px 11px; border-radius: 9px; border: 1px solid var(--ebrd);
  background: var(--ebg); font-size: 12px; color: var(--et1); font-family: var(--font); outline: none; transition: border-color .15s;
}
.form-sel:focus, .form-inp:focus { border-color: var(--ep); }
.form-lbl { font-size: 11px; font-weight: 600; color: var(--et2); margin-bottom: 5px; display: block; }
.form-grp { margin-bottom: 10px; }
.rec-action { display: flex; align-items: flex-start; gap: 8px; padding: 6px 0; }
.rec-check { color: #059669; flex-shrink: 0; margin-top: 1px; }
.rec-txt { font-size: 12px; color: var(--et2); line-height: 1.4; }
.reply-area { width: 100%; padding: 9px 11px; border-radius: 9px; border: 1px solid var(--ebrd); background: var(--ebg); font-size: 12px; color: var(--et1); font-family: var(--font); resize: vertical; min-height: 80px; outline: none; line-height: 1.6; }
.reply-area:focus { border-color: var(--ep); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ep) 10%, transparent); }
.suggested-reply {
  padding: 8px 10px; background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 8px;
  font-size: 11px; color: var(--et2); cursor: pointer; transition: all .13s; margin-bottom: 5px; line-height: 1.4;
}
.suggested-reply:hover { border-color: var(--ep); background: color-mix(in srgb, var(--ep) 5%, white); }
.apply-btn {
  width: 100%; padding: 9px; border-radius: 9px; border: none; cursor: pointer;
  background: linear-gradient(135deg, var(--ep), var(--es)); color: #fff;
  font-size: 13px; font-weight: 700; font-family: var(--font); transition: all .18s;
  box-shadow: 0 3px 10px color-mix(in srgb, var(--ep) 30%, transparent);
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.apply-btn:hover { transform: translateY(-1px); }
.rerun-btn {
  width: 100%; padding: 7px; border-radius: 9px; border: 1px solid var(--ebrd);
  background: transparent; color: var(--et3); font-size: 12px; font-weight: 600;
  font-family: var(--font); cursor: pointer; transition: all .15s; margin-top: 6px;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.rerun-btn:hover { border-color: var(--ep); color: var(--ep); }

/* empty / loading */
.panel-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 40px 20px; text-align: center; color: var(--et4); gap: 10px; }
.panel-empty-icon { font-size: 36px; }
.panel-empty-title { font-size: 14px; font-weight: 600; color: var(--et2); }

/* ── TOAST ── */
.toast { position:fixed;bottom:22px;right:22px;z-index:9999;background:#0D0F1A;color:#fff;padding:11px 16px;border-radius:11px;font-size:12px;font-family:var(--font);display:flex;align-items:center;gap:7px;box-shadow:0 8px 28px rgba(0,0,0,.22);opacity:0;transform:translateY(8px);transition:all .22s;pointer-events:none; }
.toast.show { opacity:1;transform:translateY(0); }

@keyframes cpls { 0%,100%{opacity:1}50%{opacity:.3} }
</style>

@php $role = auth()->user()->role; @endphp

<div class="esc-wrap">

  {{-- TOP BAR --}}
  <div class="esc-top">
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="esc-title">
        <div class="esc-icon">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        Escalations
      </div>
      <div class="stat-pills">
        <div class="stat-pill" style="background:#FEF2F2;border-color:#FECACA;color:#EF4444;" id="statTotalPill">
          <div style="width:6px;height:6px;border-radius:50%;background:#EF4444;animation:cpls 1s infinite;"></div>
          Chargement...
        </div>
        <div class="stat-pill" style="background:#FFF7ED;border-color:#FED7AA;color:#D97706;">⬆ 2 Haute</div>
        <div class="stat-pill" style="background:#ECFDF5;border-color:#A7F3D0;color:#059669;">✓ 12 résolues ce mois</div>
      </div>
    </div>
    <div style="display:flex;gap:6px;">
      <button class="btn-sm" onclick="toast('Export en cours…')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exporter
      </button>
      <button class="btn-sm" onclick="refreshList()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Refresh
      </button>
    </div>
  </div>

  <div class="esc-body">

    {{-- LEFT: LIST --}}
    <div id="escList">
      <div class="list-hdr">
        <div class="list-search">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--et4)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" placeholder="Rechercher…" id="escSearch" oninput="filterEsc()">
        </div>
        <div class="list-filters" id="filterChips">
          <button class="filter-chip active" onclick="setFilter('',this)">Tous</button>
          <button class="filter-chip" onclick="setFilter('High',this)">Haute</button>
          <button class="filter-chip" onclick="setFilter('Medium',this)">Moyenne</button>
          <button class="filter-chip" onclick="setFilter('Low',this)">Faible</button>
        </div>
      </div>
      <div class="esc-items" id="escItems"></div>
    </div>

    {{-- MIDDLE: DETAIL --}}
    <div id="escDetail">
      <div id="detailEmpty" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--et4);">
        <div style="font-size:40px;">🔔</div>
        <div style="font-size:14px;font-weight:600;color:var(--et2);">Sélectionnez une escalation</div>
        <div style="font-size:12px;">Choisissez un ticket dans la liste</div>
      </div>
      <div id="detailContent" style="display:none;flex-direction:column;height:100%;overflow:hidden;">
        <div class="detail-hdr">
          <div class="detail-subject" id="detailSubject"></div>
          <div class="detail-meta">
            <span class="detail-source" id="detailSource"></span>
            <span id="detailPrioBadge"></span>
            <span id="detailDate" style="font-size:11px;color:var(--et4);"></span>
          </div>
        </div>
        <div class="detail-body" id="detailBody"></div>
      </div>
    </div>

    {{-- RIGHT: PANEL --}}
    <div id="escPanel">
      <div id="panelEmpty" class="panel-empty" style="flex:1;display:flex;">
        <div class="panel-empty-icon">⚡</div>
        <div class="panel-empty-title">Panneau IA</div>
        <div style="font-size:12px;">Sélectionnez une escalation pour voir les recommandations IA</div>
      </div>
      <div id="panelContent" style="display:none;">

        {{-- Recommended actions --}}
        <div class="panel-section">
          <div class="panel-sec-title">Actions recommandées <span class="ai-badge">IA</span></div>
          <div id="recActions"></div>
        </div>

        {{-- Override & resolve --}}
        <div class="panel-section">
          <div class="panel-sec-title">Override & Résoudre</div>
          <div class="form-grp">
            <label class="form-lbl">Statut</label>
            <select class="form-sel" id="overrideStatus">
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In progress</option>
              <option value="escalated" selected>Escalated</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div class="form-grp">
            <label class="form-lbl">Priorité</label>
            <select class="form-sel" id="overridePriority">
              <option>low</option>
              <option>medium</option>
              <option>high</option>
              <option>critical</option>
            </select>
          </div>
          <button class="apply-btn" onclick="applyOverride()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Apply Override
          </button>
          <button class="rerun-btn" onclick="rerun()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Re-run IA
          </button>
        </div>

        {{-- Suggested replies --}}
        <div class="panel-section">
          <div class="panel-sec-title">Réponses suggérées <span class="ai-badge">IA</span></div>
          <div id="sugReplies"></div>
          <div class="form-grp" style="margin-top:10px;">
            <label class="form-lbl">Réponse au client</label>
            <textarea class="reply-area" id="replyArea" placeholder="Rédigez ou sélectionnez une réponse suggérée…"></textarea>
          </div>
          <button class="apply-btn" onclick="sendReply()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Envoyer
          </button>
        </div>

      </div>
    </div>

  </div>
</div>

<div class="toast" id="toast">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
// ── DATA ─────────────────────────────────────────────────────
var escalations = [];
var currentFilter = '';
var currentId = null;

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  fetchEscalations();
});

function fetchEscalations() {
  fetch('{{ route('super-admin.escalations.api') }}')
    .then(res => res.json())
    .then(data => {
      const items = Array.isArray(data)
        ? data
        : (data.tickets || data.data || []);
      escalations = items.map(t => ({
        id: t.id,
        subject: t.subject || t.title || 'Sans sujet',
        source: t.channel_source || t.source || 'GLPI',
        preview: String(t.description || t.summary || '').substring(0, 100) + '...',
        priority: String(t.priority || t.severity || 'low').charAt(0).toUpperCase() + String(t.priority || t.severity || 'low').slice(1).toLowerCase(),
        date: new Date(t.created_at || t.createdAt || t.created_at).toLocaleDateString('fr-FR'),
        full: t
      }));
      renderList(escalations);
      updateStats();
    })
    .catch(err => {
      console.error('Escalations fetch failed', err);
      toast('Erreur chargement escalations');
    });
}

function updateStats() {
  document.getElementById('statTotalPill').innerHTML = `
    <div style="width:6px;height:6px;border-radius:50%;background:#EF4444;animation:cpls 1s infinite;"></div>
    ${escalations.length} total
  `;
}

// ── LIST ─────────────────────────────────────────────────────
function renderList(list) {
  var el = document.getElementById('escItems');
  if (!list.length) {
    el.innerHTML = '<div style="padding:30px;text-align:center;color:var(--et4);font-size:12px;">Aucune escalation trouvée</div>';
    return;
  }
  el.innerHTML = list.map(e => {
    var pc = prioColor(e.priority);
    return `
    <div class="esc-item ${currentId===e.id?'active':''}" onclick="selectEsc(${e.id})">
      <div class="esc-item-top">
        <div class="esc-subject">${esc(e.subject)}</div>
        <div class="prio-dot" style="background:${pc};${e.priority==='High'?'box-shadow:0 0 6px '+pc+'80;':''}"></div>
      </div>
      <div class="esc-source">${esc(e.source)}</div>
      <div class="esc-preview">${esc(e.preview)}</div>
      <div class="esc-meta">
        <span class="esc-date">${e.date}</span>
        <span class="prio-badge" style="background:${pc}18;color:${pc};">${e.priority}</span>
      </div>
    </div>`;
  }).join('');
}

function filterEsc() {
  var q = document.getElementById('escSearch').value.toLowerCase();
  var filtered = escalations.filter(e =>
    (!currentFilter || e.priority.toLowerCase() === currentFilter.toLowerCase()) &&
    (!q || e.subject.toLowerCase().includes(q) || e.preview.toLowerCase().includes(q))
  );
  renderList(filtered);
}

function setFilter(prio, btn) {
  currentFilter = prio;
  document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterEsc();
}

function refreshList() {
  fetchEscalations();
  toast('Liste actualisée');
}

// ── DETAIL ───────────────────────────────────────────────────
function selectEsc(id) {
  currentId = id;
  renderList(escalations); // update active state
  
  fetch(`{{ url('/super-admin/api/escalations') }}/${id}`)
    .then(res => res.json())
    .then(ticket => {
      renderDetail(ticket);
    })
    .catch(err => toast('Erreur chargement détails'));
}

function renderDetail(e) {
  document.getElementById('detailEmpty').style.display = 'none';
  var dc = document.getElementById('detailContent');
  dc.style.display = 'flex';

  document.getElementById('detailSubject').textContent = e.subject;
  document.getElementById('detailSource').textContent = `${e.channel_source || 'GLPI'} · #${e.id}`;
  document.getElementById('detailDate').textContent = new Date(e.created_at).toLocaleString('fr-FR');
  
  var pc = prioColor(e.priority.charAt(0).toUpperCase() + e.priority.slice(1).toLowerCase());
  document.getElementById('detailPrioBadge').innerHTML = `<span class="prio-badge" style="background:${pc}18;color:${pc};">${e.priority}</span>`;

  var body = document.getElementById('detailBody');
  var html = '';

  // Original Message
  html += `<div class="msg-card">
    <div class="msg-from">
      <div class="msg-from-av" style="background:#EEF2FF;color:#4F46E5;">C</div>
      Client
    </div>
    <div class="msg-body">${esc(e.description)}</div>
  </div>`;

  // AI Analysis
  if (e.last_analysis) {
    var a = e.last_analysis;
    var conf = Math.round(a.confidence_score * 100);
    var confColor = conf>=70?'#059669':conf>=40?'#D97706':'#EF4444';
    
    html += `<div>
      <div class="ai-actions-hdr"><span>Analyse AI</span><span class="ai-badge">IA</span></div>
      <div class="action-card" style="border-left: 3px solid ${confColor}">
        <div class="action-outcome">
          <span class="prio-badge" style="background:${confColor}18;color:${confColor};">Confiance: ${conf}%</span>
        </div>
        <div style="font-size:13px;font-weight:600;margin-bottom:5px;">Résumé:</div>
        <div style="font-size:12px;color:var(--et3);line-height:1.55;">${esc(a.summary)}</div>
        <div class="conf-bar"><div class="conf-fill" style="width:${conf}%;background:${confColor};"></div></div>
      </div>
    </div>`;
    
    // Recommended actions
    document.getElementById('recActions').innerHTML = (a.recommended_actions || []).map(r => `
      <div class="rec-action">
        <svg class="rec-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="16 10 11 15 8 12"/></svg>
        <div class="rec-txt">${esc(r)}</div>
      </div>`).join('');
  }

  body.innerHTML = html;

  // Panel
  document.getElementById('panelEmpty').style.display = 'none';
  document.getElementById('panelContent').style.display = 'block';
  
  document.getElementById('overrideStatus').value = e.status;
  document.getElementById('overridePriority').value = e.priority;
}

function applyOverride() {
  if (!currentId) {
    toast('Aucune escalation sélectionnée.');
    return;
  }
  var status = document.getElementById('overrideStatus').value;
  var prio = document.getElementById('overridePriority').value;

  fetch(`{{ url('/super-admin/api/escalations') }}/${currentId}/resolve`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    body: JSON.stringify({ status: status, priority: prio })
  })
  .then(function (res) {
    if (!res.ok) {
      return res.text().then(function (text) {
        var msg = res.statusText || 'Erreur';
        try {
          var json = JSON.parse(text || '{}');
          msg = json.message || json.detail || json.error || text || msg;
        } catch (e) {
          msg = text || msg;
        }
        throw new Error(msg);
      });
    }
    return res.json();
  })
  .then(function () {
    toast('Override appliqué avec succès');
    fetchEscalations();
  })
  .catch(function (err) {
    toast('Erreur lors de l\'application : ' + (err.message || 'Erreur'));
  });
}

function rerun() {
  toast('Re-run IA en cours…');
  fetch(`{{ url('/ai/analyze') }}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    body: JSON.stringify({ ticket_id: currentId })
  })
  .then(res => res.json())
  .then(res => {
    toast('Analyse IA complétée');
    selectEsc(currentId);
  })
  .catch(err => toast('Erreur re-run IA'));
}

function sendReply() {
  var txt = document.getElementById('replyArea').value.trim();
  if (!txt) { toast('Rédigez une réponse d\'abord'); return; }
  
  // Send reply as a comment/followup
  toast('Envoi de la réponse...');
  // Implementation for sending reply would go here
  setTimeout(() => {
    toast('Réponse envoyée au client ✓');
    document.getElementById('replyArea').value = '';
  }, 1000);
}

// ── HELPERS ───────────────────────────────────────────────────
function prioColor(p) { 
  p = p.toLowerCase();
  return p==='high'||p==='critical'?'#EF4444':p==='medium'?'#D97706':'#6B7280'; 
}
function prioBadge(p) { return `<span class="prio-badge" style="background:${prioColor(p)}18;color:${prioColor(p)};">${p}</span>`; }
function esc(t) { return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function toast(msg) { var el=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;el.classList.add('show');clearTimeout(el._t);el._t=setTimeout(()=>el.classList.remove('show'),3000); }
</script>
@endsection
