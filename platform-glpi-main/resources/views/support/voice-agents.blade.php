@extends('layouts.dashboard')
@section('title', 'Voice Agents Runtime - L2T Support')

@section('content')
@php
  $apiBase = rtrim((string) config('services.support_api.public_url'), '/');
@endphp
<style>
.va-page{
  --va-bg:#f8fafc;
  --va-panel:#ffffff;
  --va-panel-2:#f1f5f9;
  --va-border:#e2e8f0;
  --va-text:#1e293b;
  --va-muted:#64748b;
  --va-good:#10b981;
  --va-bad:#ef4444;
  --va-warn:#f59e0b;
  min-height:calc(100vh - 120px);
  background:var(--va-bg);
  border-radius:16px;
  color:var(--va-text);
  font-family:Inter,system-ui,sans-serif;
}

[data-bs-theme="dark"] .va-page {
  --va-bg: #0f172a;
  --va-panel: #1e293b;
  --va-panel-2: #334155;
  --va-border: #334155;
  --va-text: #f1f5f9;
  --va-muted: #94a3b8;
}

/* ── Header ── */
.va-head-card{
  background: var(--va-panel);
  border-radius: 20px;
  padding: 32px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 24px;
  border: 1px solid var(--va-border);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.va-head-left{display:flex;align-items:center;gap:20px;}
.va-head-icon{
  width:60px;height:60px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  border-radius:16px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.va-title{color:var(--va-text);font-weight:800;margin:0;font-size:26px;letter-spacing:-0.03em;}
.va-sub{color:var(--va-muted);margin:0;font-size:14px;font-weight:500;}

.va-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:20px}

.va-card{
  background: var(--va-panel);
  border: 1px solid var(--va-border);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.va-card h3{
  font-size:16px;
  font-weight:800;
  color:var(--va-text);
  margin:0 0 20px;
  display:flex;
  align-items:center;
  gap:10px;
  letter-spacing:-0.01em;
}

.va-actions{display:flex;flex-wrap:wrap;gap:10px}
.va-btn{
  border:2px solid var(--va-border);
  border-radius:12px;
  background:var(--va-panel);
  color:var(--va-muted);
  padding:10px 18px;
  font-size:13px;
  font-weight:700;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:8px;
  transition: all 0.2s ease;
}
.va-btn:hover{
  border-color:var(--color-primary);
  color:var(--color-primary);
  transform:translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.va-btn.primary{
  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
  border-color:transparent;
  color:#fff;
}
.va-btn.primary:hover{
  color:#fff;
  opacity:0.95;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
.va-btn.danger{
  background:rgba(225, 29, 72, 0.1);
  border-color:rgba(225, 29, 72, 0.2);
  color:#e11d48;
}
.va-btn.danger:hover{
  background:#e11d48;
  color:#fff;
  border-color:#e11d48;
}

.va-status{display:flex;gap:14px;align-items:center;margin-bottom:20px;padding:16px;background:var(--va-panel-2);border-radius:14px;border:1px solid var(--va-border);}
.va-dot{width:12px;height:12px;border-radius:999px;background:var(--va-bad);box-shadow:0 0 0 5px rgba(239,68,68,.1)}
.va-dot.on{background:var(--va-good);box-shadow:0 0 0 5px rgba(16,185,129,.1)}

.va-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}
.va-kpi{border:1px solid var(--va-border);border-radius:14px;background:var(--va-panel);padding:14px;text-align:center;}
.va-kpi span{display:block;font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px}
.va-kpi strong{font-size:18px;color:var(--color-primary);font-family:JetBrains Mono,Consolas,monospace;font-weight:800;}

.va-form{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.va-field{display:flex;flex-direction:column;gap:6px}
.va-field.full{grid-column:1/-1}
.va-field label{font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:.05em}
.va-field input,.va-field select{
  height:44px;
  border-radius:12px;
  border:2px solid var(--va-border);
  background:var(--va-panel);
  color:var(--va-text);
  padding:0 14px;
  font-size:13px;
  font-weight:600;
  outline:none;
  transition: all 0.2s ease;
}
.va-field input:focus,.va-field select:focus{
  border-color:var(--color-primary);
  background: var(--va-panel) !important;
  color: var(--va-text) !important;
  box-shadow: 0 0 0 4px color-mix(in srgb,var(--color-primary) 10%,transparent)
}

.va-log{
  height:400px;
  overflow:auto;
  background:#0f172a;
  border:1px solid #1e293b;
  border-radius:14px;
  padding:16px;
  font:12px/1.6 JetBrains Mono,Consolas,monospace;
  color:#e2e8f0;
  white-space:pre-wrap;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}
.va-alert{display:none;margin-bottom:16px;border-radius:12px;padding:12px 16px;font-size:14px;font-weight:600;border:1px solid}
.va-alert.show{display:block}
.va-alert.ok{background:rgba(22, 163, 74, 0.1);border-color:rgba(22, 163, 74, 0.2);color:#22c55e}
.va-alert.err{background:rgba(239, 68, 68, 0.1);border-color:rgba(239, 68, 68, 0.2);color:#ef4444}

.va-test{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end}
.va-token{margin-top:16px;word-break:break-all;border:2px dashed var(--va-border);border-radius:12px;background:var(--va-panel-2);padding:16px;color:var(--va-muted);font-size:13px;font-family:monospace;}

@media(max-width:1100px){.va-grid{grid-template-columns:1fr}.va-form{grid-template-columns:1fr}.va-kpis{grid-template-columns:1fr}}
</style>

<div class="va-page">
  <div class="va-head-card">
    <div class="va-head-left">
      <div class="va-head-icon">
        <i class="material-symbols-rounded text-white" style="font-size:32px;">graphic_eq</i>
      </div>
      <div>
        <h1 class="va-title">Voice Agents Runtime</h1>
        <div class="va-sub">Gestion et supervision en temps réel des agents vocaux IA</div>
      </div>
    </div>
    <div class="va-actions">
      <button class="va-btn" id="refreshBtn">
        <i class="material-symbols-rounded" style="font-size:18px;">refresh</i> Actualiser
      </button>
      <button class="va-btn primary" id="startDevBtn">
        <i class="material-symbols-rounded" style="font-size:18px;">code</i> Start Dev
      </button>
      <button class="va-btn primary" id="startProdBtn">
        <i class="material-symbols-rounded" style="font-size:18px;">play_arrow</i> Lancer
      </button>
      <button class="va-btn danger" id="stopBtn">
        <i class="material-symbols-rounded" style="font-size:18px;">stop</i> Arrêter
      </button>
    </div>
  </div>

  <div id="alertBox" class="va-alert"></div>

  <div class="va-grid">
    <section class="va-card">
      <h3><i class="material-symbols-rounded text-primary">monitoring</i> État du service</h3>
      <div class="va-status">
        <span class="va-dot" id="statusDot"></span>
        <div>
          <div id="statusText" style="font-weight:800;color:#1e293b;font-size:16px;">Vérification...</div>
          <div id="statusSub" style="font-size:12px;color:var(--va-muted);">En attente de réponse de l'API FastAPI.</div>
        </div>
      </div>
      <div class="va-kpis">
        <div class="va-kpi"><span>Mode</span><strong id="kpiMode">-</strong></div>
        <div class="va-kpi"><span>PID</span><strong id="kpiPid">-</strong></div>
        <div class="va-kpi"><span>Uptime</span><strong id="kpiUptime">-</strong></div>
      </div>
    </section>

    <section class="va-card">
      <h3><i class="material-symbols-rounded text-primary">vibration</i> LiveKit Smoke Test</h3>
      <div class="va-test">
        <div class="va-field">
          <label>Test room token</label>
          <input id="testRoomLabel" value="support-room" disabled>
        </div>
        <button class="va-btn primary" id="testTokenBtn">Générer un token</button>
      </div>
      <div class="va-token" id="tokenBox">Aucun token généré pour le moment.</div>
    </section>

    <section class="va-card">
      <h3><i class="material-symbols-rounded text-primary">settings</i> Configuration</h3>
      <form id="configForm" class="va-form">
        <div class="va-field"><label>LiveKit URL</label><input name="livekit_url"></div>
        <div class="va-field"><label>AI provider</label><select name="ai_response_provider"><option value="gemini">gemini</option><option value="openai">openai</option><option value="claude">claude</option><option value="local">local</option></select></div>
        <div class="va-field"><label>LiveKit API key</label><input name="livekit_api_key"></div>
        <div class="va-field"><label>LiveKit API secret</label><input name="livekit_api_secret" type="password"></div>
        <div class="va-field"><label>Gemini model</label><input name="gemini_model"></div>
        <div class="va-field"><label>OpenAI model</label><input name="openai_model"></div>
        <div class="va-field"><label>Backend API URL</label><input name="backend_api_url"></div>
        <div class="va-field"><label>Recordings dir</label><input name="voice_recordings_dir"></div>
        <div class="va-field full"><label>Gemini API key(s)</label><input name="gemini_api_key" type="password"></div>
        <div class="va-field"><label>Google API key</label><input name="google_api_key" type="password"></div>
        <div class="va-field"><label>OpenAI API key</label><input name="openai_api_key" type="password"></div>
        <div class="va-field"><label>Anthropic API key</label><input name="anthropic_api_key" type="password"></div>
        <div class="va-field"><label>Internal service key</label><input name="internal_service_key" type="password"></div>
        <div class="va-field full"><label>Database URL</label><input name="database_url"></div>
        <div class="va-field"><label>Use realtime</label><select name="use_realtime"><option value="true">true</option><option value="false">false</option></select></div>
        <div class="va-field" style="justify-content:end; grid-column: 2;"><button class="va-btn primary w-100" type="submit" style="justify-content:center;">Enregistrer la configuration</button></div>
      </form>
    </section>

    <section class="va-card">
      <h3><i class="material-symbols-rounded text-primary">terminal</i> Journaux d'exécution (Logs)</h3>
      <div class="va-log" id="logBox">Chargement des logs...</div>
    </section>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
(function(){
  const $ = (id) => document.getElementById(id);
  let currentConfig = null;

  async function api(path, options) {
    options = options || {};
    if (window.supportBackendFetch) {
      return window.supportBackendFetch(path, options);
    }

    const headers = Object.assign({'Accept':'application/json'}, options.headers || {});
    if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const base = @json($apiBase);
    const url = base.replace(/\/$/, '') + '/' + String(path || '').replace(/^\//, '');
    const res = await fetch(url, Object.assign({}, options, {headers}));
    const text = await res.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (_) { data = {message:text}; }
    if (!res.ok) throw new Error(data.detail || data.message || res.statusText || 'Request failed');
    return data;
  }

  function alert(message, ok) {
    const box = $('alertBox');
    box.textContent = message;
    box.className = 'va-alert show ' + (ok ? 'ok' : 'err');
    window.clearTimeout(alert._t);
    alert._t = window.setTimeout(() => box.className = 'va-alert', 4500);
  }

  function fmtUptime(sec) {
    if (!sec && sec !== 0) return '-';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = Math.floor(sec % 60);
    return h ? `${h}h ${m}m` : `${m}m ${s}s`;
  }

  function fillConfig(config) {
    currentConfig = config;
    const form = $('configForm');
    Object.keys(config).forEach((key) => {
      const input = form.elements[key];
      if (!input) return;
      if (key === 'use_realtime') input.value = config[key] ? 'true' : 'false';
      else input.value = config[key] ?? '';
    });
  }

  function readConfig() {
    const form = $('configForm');
    const out = Object.assign({}, currentConfig || {});
    Array.from(form.elements).forEach((el) => {
      if (!el.name) return;
      out[el.name] = el.name === 'use_realtime' ? el.value === 'true' : el.value;
    });
    return out;
  }

  async function refreshStatus() {
    const status = await api('/voice-agents/status');
    $('statusDot').classList.toggle('on', Boolean(status.running));
    $('statusText').textContent = status.running ? 'Running' : 'Stopped';
    $('statusSub').textContent = status.started_at ? `Started ${new Date(status.started_at).toLocaleString()}` : 'No active runtime process.';
    $('kpiMode').textContent = status.mode || '-';
    $('kpiPid').textContent = status.pid || '-';
    $('kpiUptime').textContent = fmtUptime(status.uptime_seconds);
  }

  async function refreshLogs() {
    const logs = await api('/voice-agents/logs?lines=220');
    $('logBox').textContent = (logs.lines || []).join('\n') || 'No logs yet.';
  }

  async function refreshConfig() {
    const data = await api('/voice-agents/config');
    fillConfig(data.config || {});
  }

  async function refreshAll() {
    try {
      await Promise.all([refreshStatus(), refreshLogs(), refreshConfig()]);
    } catch (err) {
      alert(err.message, false);
    }
  }

  async function start(mode) {
    try {
      await api('/voice-agents/start', {method:'POST', body: JSON.stringify({mode})});
      alert(`Voice agents started in ${mode} mode.`, true);
      await refreshAll();
    } catch (err) {
      alert(err.message, false);
    }
  }

  $('refreshBtn').addEventListener('click', refreshAll);
  $('startDevBtn').addEventListener('click', () => start('dev'));
  $('startProdBtn').addEventListener('click', () => start('start'));
  $('stopBtn').addEventListener('click', async () => {
    try {
      await api('/voice-agents/stop', {method:'POST'});
      alert('Voice agents stopped.', true);
      await refreshAll();
    } catch (err) {
      alert(err.message, false);
    }
  });
  $('testTokenBtn').addEventListener('click', async () => {
    try {
      const data = await api('/voice-agents/test-token');
      $('tokenBox').textContent = `URL: ${data.url}\n\nTOKEN: ${data.token}`;
      alert('LiveKit test token generated.', true);
    } catch (err) {
      alert(err.message, false);
    }
  });
  $('configForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const data = await api('/voice-agents/config', {method:'PUT', body: JSON.stringify(readConfig())});
      fillConfig(data.config || readConfig());
      alert('Configuration saved.', true);
    } catch (err) {
      alert(err.message, false);
    }
  });

  refreshAll();
  window.setInterval(() => { refreshStatus().catch(() => {}); }, 10000);
})();
</script>
@endpush
