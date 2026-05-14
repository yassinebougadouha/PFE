@extends('layouts.dashboard')
@section('title', 'Moteur de Decisions - L2T Support')

@section('content')
<style>
.de-page{max-width:1180px;margin:0 auto;padding:22px;display:flex;flex-direction:column;gap:16px}
.de-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.de-title{font-size:28px;font-weight:800;color:#0f172a;margin:0}
[data-bs-theme="dark"] .de-title{color:#e5e7eb}
.de-sub{font-size:13px;color:#64748b}
[data-bs-theme="dark"] .de-sub{color:#94a3b8}

.de-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
@media(max-width:980px){.de-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:540px){.de-kpis{grid-template-columns:1fr}}
.de-kpi{border:1px solid #cbd5e1;background:#fff;border-radius:12px;padding:12px 14px}
[data-bs-theme="dark"] .de-kpi{border-color:#334155;background:#0f172a}
.de-kpi span{display:block;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
[data-bs-theme="dark"] .de-kpi span{color:#9ca3af}
.de-kpi strong{display:block;margin-top:4px;font-size:25px;color:#0f172a;font-family:Consolas,monospace}
[data-bs-theme="dark"] .de-kpi strong{color:#f8fafc}

.de-tabs{display:inline-flex;gap:4px;padding:5px;background:#e2e8f0;border-radius:12px}
[data-bs-theme="dark"] .de-tabs{background:#111827}
.de-tab{border:1px solid transparent;background:transparent;color:#334155;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:all .14s}
[data-bs-theme="dark"] .de-tab{color:#94a3b8}
.de-tab.active{background:#fff;border-color:#cbd5e1;color:#0f172a;box-shadow:0 5px 16px rgba(0,0,0,.08)}
[data-bs-theme="dark"] .de-tab.active{background:#1f2937;border-color:#374151;color:#f3f4f6}

.de-pane{display:none;flex-direction:column;gap:12px}
.de-pane.active{display:flex}
.de-card{border:1px solid #cbd5e1;background:#fff;border-radius:12px;padding:14px}
[data-bs-theme="dark"] .de-card{border-color:#334155;background:#0f172a}

.de-grid{display:grid;gap:12px}
.de-grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}
@media(max-width:820px){.de-grid.two{grid-template-columns:1fr}}

.de-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:6px}
[data-bs-theme="dark"] .de-label{color:#9ca3af}
.de-input,.de-text{width:100%;border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:10px;padding:9px 10px;font-size:13px;outline:none}
[data-bs-theme="dark"] .de-input,[data-bs-theme="dark"] .de-text{border-color:#374151;background:#111827;color:#e5e7eb}
.de-input:focus,.de-text:focus{border-color:var(--color-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 24%,transparent)}
.de-text{min-height:95px;resize:vertical}

.de-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.de-btn{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer;transition:.14s}
[data-bs-theme="dark"] .de-btn{border-color:#374151;background:#111827;color:#e5e7eb}
.de-btn:hover{border-color:var(--color-primary);transform:translateY(-1px)}
.de-btn.primary{border-color:transparent;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff}

.de-badges{display:flex;flex-wrap:wrap;gap:6px}
.de-badge{padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid #cbd5e1;background:#f8fafc;color:#334155}
[data-bs-theme="dark"] .de-badge{border-color:#334155;background:#111827;color:#cbd5e1}

.de-result-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
@media(max-width:800px){.de-result-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:520px){.de-result-grid{grid-template-columns:1fr}}
.de-cell{border:1px solid #cbd5e1;background:#f8fafc;border-radius:10px;padding:8px}
[data-bs-theme="dark"] .de-cell{border-color:#334155;background:#111827}
.de-cell span{display:block;font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em}
.de-cell strong{display:block;margin-top:4px;font-size:13px;color:#0f172a}
[data-bs-theme="dark"] .de-cell strong{color:#f8fafc}

.de-table-wrap{overflow:auto;border:1px solid #cbd5e1;border-radius:12px}
[data-bs-theme="dark"] .de-table-wrap{border-color:#334155}
.de-table{width:100%;border-collapse:collapse;font-size:12px}
.de-table th{padding:9px 10px;text-align:left;background:#f1f5f9;color:#475569;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
[data-bs-theme="dark"] .de-table th{background:#111827;color:#94a3b8}
.de-table td{padding:9px 10px;border-top:1px solid #e2e8f0;color:#334155;vertical-align:top}
[data-bs-theme="dark"] .de-table td{border-top-color:#1f2937;color:#cbd5e1}

.de-bars{display:flex;align-items:flex-end;gap:8px;min-height:180px}
.de-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:6px}
.de-bar{width:100%;max-width:48px;border-radius:6px 6px 0 0;background:linear-gradient(180deg,var(--color-primary),var(--color-secondary));min-height:4px}
.de-bar-l{font-size:10px;color:#64748b;text-align:center}
[data-bs-theme="dark"] .de-bar-l{color:#94a3b8}

.de-pie-legend{display:flex;flex-direction:column;gap:6px}
.de-legend-row{display:flex;align-items:center;gap:8px;font-size:12px;color:#334155}
[data-bs-theme="dark"] .de-legend-row{color:#cbd5e1}
.de-dot{width:10px;height:10px;border-radius:999px;flex-shrink:0}

.de-empty{font-size:13px;color:#64748b}
[data-bs-theme="dark"] .de-empty{color:#94a3b8}
.de-alert{display:none;border-radius:10px;padding:10px 12px;font-size:13px}
.de-alert.show{display:block}
.de-alert.err{border:1px solid rgba(239,68,68,.4);background:rgba(239,68,68,.1);color:#991b1b}
[data-bs-theme="dark"] .de-alert.err{color:#fecaca}
.de-alert.ok{border:1px solid rgba(16,185,129,.4);background:rgba(16,185,129,.1);color:#065f46}
[data-bs-theme="dark"] .de-alert.ok{color:#bbf7d0}

/* DECISION CONFIG STYLES */
.dec-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.dec-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
[data-bs-theme="dark"] .dec-subtitle{color:#94a3b8}
.dec-badge{display:inline-block;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:700;background:rgba(124,58,237,.12);color:#6d28d9}
[data-bs-theme="dark"] .dec-badge{background:rgba(124,58,237,.24);color:#c4b5fd}

.dec-alert{border-radius:10px;padding:12px 14px;font-size:13px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start}
.dec-alert-icon{width:20px;height:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center}
.dec-alert.info{border:1px solid rgba(59,130,246,.3);background:rgba(59,130,246,.1);color:#1e40af}
[data-bs-theme="dark"] .dec-alert.info{border-color:rgba(59,130,246,.4);background:rgba(59,130,246,.15);color:#bfdbfe}
.dec-alert.warn{border:1px solid rgba(245,158,11,.3);background:rgba(245,158,11,.1);color:#92400e}
[data-bs-theme="dark"] .dec-alert.warn{border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.15);color:#fcd34d}
.dec-alert.err{border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.1);color:#991b1b}
[data-bs-theme="dark"] .dec-alert.err{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.15);color:#fecaca}

.dec-card{border:1px solid #cbd5e1;background:#ffffff;border-radius:12px;padding:16px}
[data-bs-theme="dark"] .dec-card{border-color:#334155;background:#0f172a}

.dec-section-title{font-size:14px;font-weight:700;color:#0f172a;margin:0 0 12px}
[data-bs-theme="dark"] .dec-section-title{color:#f3f4f6}

.dec-grid{display:grid;gap:14px}
.dec-grid.cols2{grid-template-columns:repeat(2,minmax(0,1fr))}
.dec-grid.cols3{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1024px){.dec-grid.cols3{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:768px){.dec-grid.cols2,.dec-grid.cols3{grid-template-columns:1fr}}

.dec-field{display:flex;flex-direction:column;gap:6px}
.dec-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
[data-bs-theme="dark"] .dec-label{color:#9ca3af}
.dec-label-full{display:flex;justify-content:space-between;align-items:center;gap:8px}
.dec-value{font-family:Consolas,Monaco,monospace;font-size:12px;font-weight:600;color:#0f172a;background:#f1f5f9;padding:4px 8px;border-radius:6px}
[data-bs-theme="dark"] .dec-value{color:#f8fafc;background:#111827}

.dec-slider-wrap{display:flex;flex-direction:column;gap:8px}
.dec-slider{width:100%;height:6px;border-radius:3px;background:#cbd5e1;outline:none;-webkit-appearance:none;appearance:none}
[data-bs-theme="dark"] .dec-slider{background:#374151}
.dec-slider::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:16px;height:16px;border-radius:999px;background:var(--color-primary);cursor:pointer;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.2)}
[data-bs-theme="dark"] .dec-slider::-webkit-slider-thumb{border-color:#0f172a}
.dec-slider::-moz-range-thumb{width:16px;height:16px;border-radius:999px;background:var(--color-primary);cursor:pointer;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.2)}
[data-bs-theme="dark"] .dec-slider::-moz-range-thumb{border-color:#0f172a}
.dec-range-labels{display:flex;justify-content:space-between;font-size:10px;color:#94a3b8}

.dec-toggle{display:flex;align-items:center;gap:8px}
.dec-toggle input[type="checkbox"]{width:40px;height:24px;appearance:none;-webkit-appearance:none;background:#cbd5e1;border-radius:12px;cursor:pointer;position:relative;transition:background .2s}
[data-bs-theme="dark"] .dec-toggle input[type="checkbox"]{background:#374151}
.dec-toggle input[type="checkbox"]:checked{background:var(--color-primary)}
.dec-toggle input[type="checkbox"]::before{content:'';position:absolute;width:20px;height:20px;border-radius:999px;background:white;top:2px;left:2px;transition:left .2s}
.dec-toggle input[type="checkbox"]:checked::before{left:18px}
.dec-toggle-label{font-size:13px;color:#0f172a;flex:1}
[data-bs-theme="dark"] .dec-toggle-label{color:#e5e7eb}
.dec-toggle-desc{font-size:11px;color:#94a3b8;margin-top:2px}

.dec-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid #cbd5e1}
[data-bs-theme="dark"] .dec-actions{border-top-color:#334155}
.dec-btn{border:1px solid #cbd5e1;background:#ffffff;color:#0f172a;border-radius:10px;padding:9px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all .14s}
[data-bs-theme="dark"] .dec-btn{border-color:#374151;background:#111827;color:#e5e7eb}
.dec-btn:hover:not(:disabled){border-color:var(--color-primary);transform:translateY(-1px)}
.dec-btn.primary{border-color:transparent;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:#fff}
.dec-btn.secondary{border-color:transparent;background:#e2e8f0;color:#0f172a}
[data-bs-theme="dark"] .dec-btn.secondary{background:#1f2937;color:#e5e7eb}
.dec-btn:disabled{opacity:.5;cursor:not-allowed}

.dec-loading{display:flex;justify-content:center;align-items:center;min-height:200px}
.dec-spinner{width:40px;height:40px;border:3px solid #e2e8f0;border-top-color:var(--color-primary);border-radius:999px;animation:spin .6s linear infinite}
[data-bs-theme="dark"] .dec-spinner{border-color:#1f2937}
@keyframes spin{to{transform:rotate(360deg)}}

.dec-error{border-radius:10px;padding:16px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#991b1b;font-size:13px}
[data-bs-theme="dark"] .dec-error{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.4);color:#fecaca}
</style>

<div class="de-page" id="dePage">
  <div class="de-head">
    <div>
      <h1 class="de-title">Moteur de Decisions</h1>
      <div class="de-sub">Analyze, history, stats, and playbook wired to backend Decision Engine endpoints.</div>
    </div>
  </div>

  <div class="de-kpis">
    <div class="de-kpi"><span>Total Decisions</span><strong id="kTotal">-</strong></div>
    <div class="de-kpi"><span>Auto Resolved</span><strong id="kAuto">-</strong></div>
    <div class="de-kpi"><span>Escalated</span><strong id="kEsc">-</strong></div>
    <div class="de-kpi"><span>Escalation Rate</span><strong id="kRate">-</strong></div>
  </div>

  <div class="de-tabs" id="deTabs">
    <button class="de-tab active" data-tab="analyze">Analyze</button>
    <button class="de-tab" data-tab="history">History</button>
    <button class="de-tab" data-tab="stats">Stats</button>
    <button class="de-tab" data-tab="playbook">Playbook</button>
    @if(auth()->user()->role === 'super_admin')
      <button class="de-tab" data-tab="config">Configuration</button>
    @endif
  </div>

  <div id="deAlert" class="de-alert"></div>

  <section id="pane-analyze" class="de-pane active">
    <div class="de-grid two">
      <div class="de-card">
        <label class="de-label">Analyze by ticket</label>
        <input id="aTicket" class="de-input" placeholder="Ticket id" />
        <div style="margin-top:10px;display:grid;gap:8px">
          <label style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#64748b">Auto assign agent <input id="aAssign" type="checkbox" /></label>
          <label style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#64748b">Auto update priority <input id="aPrio" type="checkbox" checked /></label>
        </div>
        <div class="de-actions" style="margin-top:10px"><button class="de-btn primary" id="aRunTicket">Analyze ticket</button></div>
      </div>

      <div class="de-card">
        <label class="de-label">Analyze free text</label>
        <input id="aSubject" class="de-input" placeholder="Subject (optional)" />
        <textarea id="aText" class="de-text" placeholder="Issue description" style="margin-top:8px"></textarea>
        <div class="de-actions" style="margin-top:10px"><button class="de-btn primary" id="aRunText">Analyze preview</button></div>
      </div>
    </div>

    <div id="aResult" class="de-card" style="display:none">
      <div class="de-badges" id="aBadges"></div>
      <div class="de-result-grid" id="aGrid"></div>
      <p id="aReason" style="margin:10px 0 0;color:#64748b;font-size:13px"></p>
      <div id="aSug" style="margin-top:10px;display:grid;gap:6px"></div>
      <div id="aEsc" style="margin-top:10px;display:none;border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.08);border-radius:10px;padding:10px"></div>
    </div>
  </section>

  <section id="pane-history" class="de-pane">
    <div class="de-card">
      <div class="de-actions">
        <input id="hFilter" class="de-input" placeholder="Filter by ticket id" style="max-width:260px" />
        <button class="de-btn" id="hRefresh">Refresh</button>
        <button class="de-btn" id="hClear">Clear filter</button>
      </div>
    </div>
    <div class="de-table-wrap">
      <table class="de-table">
        <thead><tr><th>Ticket</th><th>Outcome</th><th>Intent</th><th>Confidence</th><th>Risk</th><th>Rule</th><th>Date</th></tr></thead>
        <tbody id="hBody"><tr><td colspan="7" class="de-empty">Loading...</td></tr></tbody>
      </table>
    </div>
  </section>

  <section id="pane-stats" class="de-pane">
    <div class="de-grid two">
      <div class="de-card">
        <label class="de-label">Decisions by category</label>
        <div id="sCat" class="de-bars"></div>
      </div>
      <div class="de-card">
        <label class="de-label">Decisions by outcome</label>
        <div id="sOut"></div>
      </div>
    </div>
  </section>

  <section id="pane-playbook" class="de-pane">
    <div class="de-card">
      <label class="de-label">All outcomes</label>
      <div class="de-table-wrap">
        <table class="de-table">
          <thead><tr><th>Outcome</th><th>Description</th><th>Operator guidance</th></tr></thead>
          <tbody id="pOutcomes"><tr><td colspan="3" class="de-empty">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
    <div class="de-card">
      <label class="de-label">Decision matrix</label>
      <div class="de-table-wrap">
        <table class="de-table">
          <thead><tr><th>Category</th><th>Confidence</th><th>Risk</th><th>Outcome</th><th>Rule</th></tr></thead>
          <tbody id="pMatrix"><tr><td colspan="5" class="de-empty">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </section>

  @if(auth()->user()->role === 'super_admin')
    <section id="pane-config" class="de-pane">
      <div class="dec-card">
        <h2 class="dec-section-title">Decision Engine Configuration</h2>
        <div class="dec-subtitle">Fine-tune confidence and risk thresholds, configure escalation rules, and adjust boost parameters</div>
        <div class="dec-alert info" style="margin-top:12px">
          <div class="dec-alert-icon">ℹ️</div>
          <div>
            <strong>Live runtime tuning:</strong> Changes here affect ticket analysis endpoints immediately. Save only after validating impact.
          </div>
        </div>
        <div id="decContent"></div>
      </div>
    </section>
  @endif
</div>
@endsection

@push('page-scripts')
<script>
(function(){
  const $ = (id) => document.getElementById(id);
  const COLORS = ['#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6'];

  function showAlert(message, ok) {
    const el = $('deAlert');
    el.textContent = message;
    el.className = 'de-alert show ' + (ok ? 'ok' : 'err');
    clearTimeout(showAlert._t);
    showAlert._t = setTimeout(() => { el.className = 'de-alert'; }, 4200);
  }

  async function api(path, options) {
    const opts = options || {};
    if (window.supportBackendFetch) return window.supportBackendFetch(path, opts);
    const headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});
    if (opts.body && !(opts.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const res = await fetch('/api/v1/' + String(path || '').replace(/^\//, ''), Object.assign({}, opts, { headers }));
    const text = await res.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (_) { data = { message: text }; }
    if (!res.ok) throw new Error(data.detail || data.message || res.statusText || 'Request failed');
    return data;
  }

  function humanize(v) {
    if (!v) return '-';
    return String(v).replace(/_/g, ' ');
  }

  function pct(v) {
    const n = Number(v || 0);
    return Math.round(n * 100) + '%';
  }

  function badge(text) {
    const el = document.createElement('span');
    el.className = 'de-badge';
    el.textContent = humanize(text);
    return el;
  }

  function switchTab(tab) {
    document.querySelectorAll('.de-tab').forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.de-pane').forEach((p) => p.classList.toggle('active', p.id === 'pane-' + tab));
    if (tab === 'config' && !window.decConfigUI) {
      window.decConfigUI = new DecisionConfigUI();
    }
  }

  // ═════════════════════════════════════════════════════════════════════════
  // Decision Engine Config UI Class
  // ═════════════════════════════════════════════════════════════════════════
  class DecisionConfigUI {
    constructor() {
      this.contentEl = document.getElementById('decContent');
      this.config = null;
      this.draft = null;
      this.isLoading = true;
      this.isSaving = false;
      this.init();
    }
    async init() {
      try {
        await this.loadConfig();
        this.render();
      } catch (error) {
        console.error('Failed to load config:', error);
        this.showError(error.message || 'Failed to load configuration');
      }
    }
    async loadConfig() {
      const response = await fetch('/api/v1/decision-engine/config', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      this.config = await response.json();
      this.draft = JSON.parse(JSON.stringify(this.config));
      this.isLoading = false;
    }
    async saveConfig() {
      this.isSaving = true;
      this.render();
      try {
        const response = await fetch('/api/v1/decision-engine/config', {
          method: 'PUT',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(this.draft)
        });
        if (!response.ok) {
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.detail || `HTTP ${response.status}`);
        }
        this.config = await response.json();
        this.draft = JSON.parse(JSON.stringify(this.config));
        this.showSuccess('Configuration saved successfully');
      } catch (error) {
        this.showError('Failed to save: ' + error.message);
      } finally {
        this.isSaving = false;
        this.render();
      }
    }
    resetDraft() {
      this.draft = JSON.parse(JSON.stringify(this.config));
      this.render();
    }
    hasOrderingIssue() {
      return (
        this.draft.confidence_medium_threshold >= this.draft.confidence_high_threshold ||
        this.draft.risk_medium_threshold >= this.draft.risk_high_threshold ||
        this.draft.risk_high_threshold >= this.draft.risk_critical_threshold
      );
    }
    updateSlider(key, value) {
      this.draft[key] = parseFloat(value);
      this.render();
    }
    toggleCheckbox(key) {
      this.draft[key] = !this.draft[key];
      this.render();
    }
    showError(msg) {
      const alert = document.createElement('div');
      alert.className = 'dec-alert err';
      alert.innerHTML = `<div class="dec-alert-icon">✕</div><div>${msg}</div>`;
      this.contentEl.insertBefore(alert, this.contentEl.firstChild);
      setTimeout(() => alert.remove(), 5000);
    }
    showSuccess(msg) {
      const alert = document.createElement('div');
      alert.className = 'dec-alert info';
      alert.style.borderColor = 'rgba(16,185,129,.3)';
      alert.style.background = 'rgba(16,185,129,.1)';
      alert.style.color = '#065f46';
      alert.innerHTML = `<div class="dec-alert-icon">✓</div><div>${msg}</div>`;
      this.contentEl.insertBefore(alert, this.contentEl.firstChild);
      setTimeout(() => alert.remove(), 4000);
    }
    formatValue(value, step) {
      const decimals = (step.toString()).split('.')[1]?.length ?? 0;
      return value.toFixed(decimals);
    }
    renderSlider(key, label, min, max, step) {
      const value = this.draft[key];
      const formatted = this.formatValue(value, step);
      return `
        <div class="dec-field">
          <div class="dec-label-full">
            <label class="dec-label">${label}</label>
            <span class="dec-value">${formatted}</span>
          </div>
          <div class="dec-slider-wrap">
            <input type="range" min="${min}" max="${max}" step="${step}" value="${value}"
                   class="dec-slider" data-key="${key}" />
            <div class="dec-range-labels">
              <span>${this.formatValue(min, step)}</span>
              <span>${this.formatValue(max, step)}</span>
            </div>
          </div>
        </div>
      `;
    }
    renderToggle(key, label, desc) {
      const checked = this.draft[key] ? 'checked' : '';
      return `
        <div class="dec-field">
          <div class="dec-toggle">
            <input type="checkbox" ${checked} class="dec-toggle-input" data-key="${key}" />
            <div>
              <div class="dec-toggle-label">${label}</div>
              <div class="dec-toggle-desc">${desc}</div>
            </div>
          </div>
        </div>
      `;
    }
    render() {
      if (!this.contentEl) return;
      if (this.isLoading) {
        this.contentEl.innerHTML = '<div class="dec-loading"><div class="dec-spinner"></div></div>';
        return;
      }
      const orderingIssue = this.hasOrderingIssue();
      this.contentEl.innerHTML = `
        ${orderingIssue ? `
          <div class="dec-alert warn">
            <div class="dec-alert-icon">⚠️</div>
            <div>
              <strong>Threshold ordering issue:</strong> Medium thresholds must remain lower than high.
            </div>
          </div>
        ` : ''}
        <div class="dec-grid cols2" style="margin-top:16px">
          ${this.renderSlider('confidence_high_threshold', 'Confidence High', 0.45, 0.98, 0.01)}
          ${this.renderSlider('confidence_medium_threshold', 'Confidence Medium', 0.05, 0.94, 0.01)}
        </div>
        <div class="dec-grid cols3" style="margin-top:16px">
          ${this.renderSlider('risk_critical_threshold', 'Risk Critical', 0.45, 1, 0.01)}
          ${this.renderSlider('risk_high_threshold', 'Risk High', 0.2, 0.98, 0.01)}
          ${this.renderSlider('risk_medium_threshold', 'Risk Medium', 0.05, 0.9, 0.01)}
        </div>
        <div class="dec-grid cols2" style="margin-top:16px">
          ${this.renderSlider('low_confidence_risk_boost', 'Low Conf Boost', 0, 0.4, 0.01)}
          ${this.renderSlider('medium_confidence_risk_boost', 'Medium Conf Boost', 0, 0.25, 0.01)}
        </div>
        <div class="dec-grid" style="margin-top:16px;gap:10px">
          ${this.renderToggle('enforce_security_escalation', 'Security Escalation', 'Auto-escalate security tickets')}
          ${this.renderToggle('enforce_critical_escalation', 'Critical Escalation', 'Auto-escalate critical risk')}
          ${this.renderToggle('low_confidence_general_suggest', 'General Suggest', 'Suggest guidance for low confidence')}
        </div>
        <div class="dec-actions">
          <button class="dec-btn primary" id="decSaveBtn" ${this.isSaving ? 'disabled' : ''}>
            ${this.isSaving ? 'Saving...' : 'Save Configuration'}
          </button>
          <button class="dec-btn secondary" id="decResetBtn" ${this.isSaving ? 'disabled' : ''}>
            Reset
          </button>
        </div>
      `;
      this.setupEventListeners();
    }
    setupEventListeners() {
      this.contentEl.querySelectorAll('.dec-slider').forEach(s => {
        s.addEventListener('input', (e) => this.updateSlider(e.target.dataset.key, e.target.value));
      });
      this.contentEl.querySelectorAll('.dec-toggle-input').forEach(c => {
        c.addEventListener('change', (e) => this.toggleCheckbox(e.target.dataset.key));
      });
      const sBtn = document.getElementById('decSaveBtn');
      if (sBtn) sBtn.addEventListener('click', () => this.saveConfig());
      const rBtn = document.getElementById('decResetBtn');
      if (rBtn) rBtn.addEventListener('click', () => this.resetDraft());
    }
  }

  document.querySelectorAll('.de-tab').forEach((b) => b.addEventListener('click', () => switchTab(b.dataset.tab)));

  function renderResult(res) {
    const card = $('aResult');
    card.style.display = '';

    const badges = $('aBadges');
    badges.innerHTML = '';
    badges.appendChild(badge(res.outcome || res.decision_outcome));
    badges.appendChild(badge(res.risk_level));
    if (res.suggested_priority) badges.appendChild(badge(res.suggested_priority));
    const conf = document.createElement('span');
    conf.className = 'de-badge';
    conf.textContent = 'Confidence ' + pct(res.confidence ?? res.confidence_score);
    badges.appendChild(conf);

    const grid = $('aGrid');
    const riskScore = res.risk_score !== undefined ? pct(res.risk_score) : '-';
    const vals = [
      ['Intent', humanize(res.intent_category)],
      ['Confidence level', humanize(res.confidence_level)],
      ['Risk score', riskScore],
      ['Suggested agent', res.suggested_agent_name || '-'],
    ];
    grid.innerHTML = vals.map(([k, v]) => '<div class="de-cell"><span>' + k + '</span><strong>' + String(v).replace(/</g, '&lt;') + '</strong></div>').join('');

    $('aReason').textContent = res.reasoning || '';

    const sug = $('aSug');
    sug.innerHTML = '';
    const suggestions = Array.isArray(res.response_suggestions) ? res.response_suggestions.slice(0, 4) : [];
    suggestions.forEach((s) => {
      const row = document.createElement('div');
      row.className = 'de-cell';
      row.textContent = s;
      sug.appendChild(row);
    });

    const esc = $('aEsc');
    if (res.escalation_summary) {
      esc.style.display = '';
      esc.textContent = res.escalation_summary;
    } else {
      esc.style.display = 'none';
      esc.textContent = '';
    }
  }

  async function runAnalyzeTicket() {
    try {
      const ticket = ($('aTicket').value || '').trim();
      if (!ticket) throw new Error('Ticket id is required.');
      const payload = {
        ticket_id: ticket,
        auto_assign: $('aAssign').checked,
        auto_update_priority: $('aPrio').checked,
      };
      const res = await api('/decision-engine/analyze', { method: 'POST', body: JSON.stringify(payload) });
      renderResult(res);
      showAlert('Ticket analyzed.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  }

  async function runAnalyzeText() {
    try {
      const text = ($('aText').value || '').trim();
      if (!text) throw new Error('Text is required.');
      const payload = { text: text, subject: (($('aSubject').value || '').trim() || undefined) };
      const res = await api('/decision-engine/analyze-text', { method: 'POST', body: JSON.stringify(payload) });
      renderResult(res);
      showAlert('Text analyzed.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  }

  async function loadStats() {
    const s = await api('/decision-engine/stats');
    $('kTotal').textContent = String(s.total_decisions ?? '-');
    $('kAuto').textContent = String(s.auto_resolved ?? '-');
    $('kEsc').textContent = String(s.escalated ?? '-');
    $('kRate').textContent = s.escalation_rate !== undefined ? pct(s.escalation_rate) : '-';

    const cat = s.decisions_by_category || {};
    const catEntries = Object.entries(cat);
    const catMax = Math.max(1, ...catEntries.map((x) => Number(x[1] || 0)));
    $('sCat').innerHTML = catEntries.length
      ? catEntries.map(([k,v],i) => {
          const h = Math.max(4, Math.round((Number(v || 0) / catMax) * 150));
          return '<div class="de-bar-col"><div class="de-bar" style="height:' + h + 'px;background:' + COLORS[i % COLORS.length] + '"></div><div class="de-bar-l">' + humanize(k) + ' (' + v + ')</div></div>';
        }).join('')
      : '<div class="de-empty">No category data.</div>';

    const out = s.decisions_by_outcome || {};
    const outEntries = Object.entries(out);
    const outTotal = Math.max(1, outEntries.reduce((acc, cur) => acc + Number(cur[1] || 0), 0));
    $('sOut').innerHTML = outEntries.length
      ? '<div class="de-pie-legend">' + outEntries.map(([k,v], i) => {
          const p = Math.round((Number(v || 0) / outTotal) * 100);
          return '<div class="de-legend-row"><span class="de-dot" style="background:' + COLORS[i % COLORS.length] + '"></span><span>' + humanize(k) + '</span><strong style="margin-left:auto">' + v + ' (' + p + '%)</strong></div>';
        }).join('') + '</div>'
      : '<div class="de-empty">No outcome data.</div>';
  }

  async function loadHistory() {
    const filter = ($('hFilter').value || '').trim();
    const endpoint = filter ? '/decision-engine/decisions/' + encodeURIComponent(filter) : '/decision-engine/decisions';
    const res = await api(endpoint);
    const rows = Array.isArray(res?.decisions) ? res.decisions : [];
    const body = $('hBody');
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="de-empty">No decisions found.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((d) => {
      const conf = pct(d.confidence_score ?? d.confidence);
      const dt = d.created_at ? new Date(d.created_at).toLocaleString() : '-';
      const rule = Array.isArray(d?.matched_rules?.rules) ? d.matched_rules.rules[0] : (Array.isArray(d.matched_rules) ? d.matched_rules[0] : '-');
      return '<tr>'
        + '<td>' + String(d.ticket_id || '-').replace(/</g, '&lt;') + '</td>'
        + '<td>' + humanize(d.decision_outcome || d.outcome) + '</td>'
        + '<td>' + humanize(d.intent_category) + '</td>'
        + '<td>' + conf + '</td>'
        + '<td>' + humanize(d.risk_level) + '</td>'
        + '<td>' + String(rule || '-').replace(/</g, '&lt;') + '</td>'
        + '<td>' + dt + '</td>'
        + '</tr>';
    }).join('');
  }

  async function loadPlaybook() {
    const res = await api('/decision-engine/outcomes-docs');
    const outcomes = Array.isArray(res?.outcomes) ? res.outcomes : [];
    const matrix = Array.isArray(res?.matrix) ? res.matrix : [];

    $('pOutcomes').innerHTML = outcomes.length
      ? outcomes.map((o) => '<tr><td>' + humanize(o.outcome) + '</td><td>' + String(o.description || '').replace(/</g, '&lt;') + '</td><td>' + String(o.operator_guidance || '').replace(/</g, '&lt;') + '</td></tr>').join('')
      : '<tr><td colspan="3" class="de-empty">No outcome documentation.</td></tr>';

    $('pMatrix').innerHTML = matrix.length
      ? matrix.map((r) => '<tr><td>' + humanize(r.category) + '</td><td>' + humanize(r.confidence_level) + '</td><td>' + humanize(r.risk_level) + '</td><td>' + humanize(r.outcome) + '</td><td>' + String(r.matched_rule || '').replace(/</g, '&lt;') + '</td></tr>').join('')
      : '<tr><td colspan="5" class="de-empty">No matrix rows.</td></tr>';
  }

  $('aRunTicket').addEventListener('click', runAnalyzeTicket);
  $('aRunText').addEventListener('click', runAnalyzeText);
  $('hRefresh').addEventListener('click', () => loadHistory().catch((e) => showAlert(e.message || String(e), false)));
  $('hClear').addEventListener('click', () => { $('hFilter').value = ''; loadHistory().catch((e) => showAlert(e.message || String(e), false)); });

  Promise.all([loadStats(), loadHistory(), loadPlaybook()]).catch((err) => {
    showAlert(err.message || String(err), false);
  });
})();
</script>
@endpush
