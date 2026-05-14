@extends('layouts.dashboard')
@section('title', 'Visual AI - L2T Support')

@section('content')
@php
  $me = auth()->user();
  $isAdminLike = in_array($me->role ?? '', ['admin', 'super_admin'], true);
@endphp

<style>
.vai-page{
  --vai-bg:#f8fafc;
  --va-panel:#ffffff;
  --va-panel-2:#f1f5f9;
  --va-border:#e2e8f0;
  --va-text:#1e293b;
  --va-muted:#64748b;
  --va-good:#10b981;
  --va-bad:#ef4444;
  --va-warn:#f59e0b;
  min-height:calc(100vh - 120px);
  background:var(--vai-bg);
  border-radius:16px;
  color:var(--va-text);
  font-family:Inter,system-ui,sans-serif;
}

[data-bs-theme="dark"] .vai-page {
  --vai-bg: #0f172a;
  --va-panel: #1e293b;
  --va-panel-2: #334155;
  --va-border: #334155;
  --va-text: #f1f5f9;
  --va-muted: #94a3b8;
}

/* ── Header ── */
.vai-head-card{
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
.vai-head-left{display:flex;align-items:center;gap:20px;}
.vai-head-icon{
  width:60px;height:60px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  border-radius:16px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.vai-title{color:var(--va-text);font-weight:800;margin:0;font-size:26px;letter-spacing:-0.03em;}
.vai-sub{color:var(--va-muted);margin:0;font-size:14px;font-weight:500;}

.vai-tabs-container{
  margin-bottom: 24px;
}
.vai-tabs{
  display: inline-flex;
  gap: 8px;
  padding: 6px;
  border-radius: 16px;
  background: var(--va-panel-2);
  border: 1px solid var(--va-border);
}
.vai-tab{
  border: 1px solid transparent;
  background: transparent;
  color: var(--va-muted);
  padding: 10px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.vai-tab:hover{ color: var(--va-text); }
.vai-tab.active{
  background: var(--va-panel);
  border-color: var(--va-border);
  color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.vai-pane{display:none;flex-direction:column;gap:20px}
.vai-pane.active{display:flex}

.vai-card{
  background: var(--va-panel);
  border: 1px solid var(--va-border);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.vai-card h3{
  font-size:16px;
  font-weight:800;
  color:var(--va-text);
  margin:0 0 20px;
  display:flex;
  align-items:center;
  gap:10px;
  letter-spacing:-0.01em;
}

.vai-grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:980px){.vai-grid-2{grid-template-columns:1fr 1fr}}

.vai-label{display:block;font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px}

.vai-input, .vai-select, .vai-text{
  width:100%;
  border:2px solid var(--va-border);
  background:var(--vai-bg);
  color:var(--va-text);
  border-radius:12px;
  padding:12px 14px;
  font-size:14px;
  font-weight:600;
  outline:none;
  transition: all 0.2s ease;
}
.vai-input:focus, .vai-select:focus, .vai-text:focus{
  border-color:var(--color-primary);
  background: var(--vai-bg) !important;
  color: var(--va-text) !important;
  box-shadow: 0 0 0 4px color-mix(in srgb,var(--color-primary) 10%,transparent)
}
.vai-text{resize:vertical;min-height:100px}

.vai-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.vai-btn{
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
.vai-btn:hover{
  border-color:var(--color-primary);
  color:var(--color-primary);
  transform:translateY(-1px);
}
.vai-btn.primary{
  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
  border-color:transparent;
  color:#fff;
}
.vai-btn.primary:hover{
  color:#fff;
  opacity:0.95;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.vai-drop{
  border:2px dashed var(--va-border);
  background:var(--vai-bg);
  border-radius:16px;
  padding:32px;
  text-align:center;
  font-size:14px;
  font-weight:600;
  color:var(--va-muted);
  transition: all 0.2s ease;
}
.vai-drop:hover{
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 5%, var(--vai-bg));
  color: var(--color-primary);
}
.vai-drop input{display:block;margin:12px auto 0;font-size:13px}

/* Result Components Styles */
.res-container{display:flex;flex-direction:column;gap:20px}
.res-section{padding:20px;border-radius:16px;background:var(--va-panel);border:1px solid var(--va-border);box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
.res-section.highlight{border-left:4px solid var(--color-primary);background:var(--va-panel);}
[data-bs-theme="dark"] .res-section.highlight { background: rgba(108, 99, 255, 0.05); }

.res-section-title{font-size:11px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.res-section-title i{font-size:18px;color:var(--color-primary);}

.res-caption{font-size:16px;line-height:1.6;color:var(--va-text);font-weight:700;letter-spacing:-0.01em;}
.res-summary{font-size:14px;line-height:1.6;color:var(--va-text);font-weight:500;}
.res-summary.diag{padding:12px; background:rgba(37, 99, 235, 0.1); border-radius:10px; border:1px solid rgba(37, 99, 235, 0.2); color:var(--color-primary);}

.res-tags{display:flex;flex-wrap:wrap;gap:8px;}
.res-tag{padding:5px 12px;border-radius:10px;background:var(--va-panel-2);border:1px solid var(--va-border);font-size:12px;font-weight:700;color:var(--va-text);}

.res-steps{display:flex;flex-direction:column;gap:12px;}
.res-step{display:flex;gap:16px;align-items:flex-start;padding:20px;border-radius:16px;background:var(--va-panel);border:1px solid var(--va-border);box-shadow: 0 2px 8px rgba(0,0,0,0.02);}
.res-step-content{flex-grow:1;}
.res-step-title{font-size:15px;font-weight:800;color:var(--va-text);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;}
.res-step-why{font-size:12px;color:var(--va-muted);font-style:italic;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--va-border);}
.res-step-instructions{list-style:none;padding:0;margin:0 0 16px;display:flex;flex-direction:column;gap:8px;}
.res-step-instructions li{font-size:13px;color:var(--va-text);display:flex;gap:8px;align-items:flex-start;}
.res-step-instructions li::before{content:'→';color:var(--color-primary);font-weight:bold;}
.res-step-logic{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;padding-top:12px;border-top:1px dashed var(--va-border);}
.res-logic-box{padding:10px;border-radius:10px;font-size:11px;}
.res-logic-box.expected{background:rgba(22, 163, 74, 0.1);border:1px solid rgba(22, 163, 74, 0.2);color:#22c55e;}
.res-logic-box.fallback{background:rgba(239, 68, 68, 0.1);border:1px solid rgba(239, 68, 68, 0.2);color:#ef4444;}
.res-logic-label{font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;}

.res-meta-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-top:8px;}
.res-meta-card{padding:16px;border-radius:14px;background:var(--va-panel);border:1.5px solid var(--va-border);display:flex;align-items:center;gap:12px;}
.res-meta-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.res-meta-label{font-size:10px;font-weight:800;color:var(--va-muted);text-transform:uppercase;letter-spacing:0.05em;display:block;}
.res-meta-value{font-size:13px;font-weight:700;color:var(--va-text);}

.res-ocr-box{
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 13px;
  line-height: 1.6;
  background: #0f172a;
  color: #e2e8f0;
  padding: 20px;
  border-radius: 14px;
  border: 1px solid #1e293b;
  white-space: pre-wrap;
  max-height: 300px;
  overflow-y: auto;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
}

.vai-alert{display:none;margin-bottom:20px;border-radius:12px;padding:12px 16px;font-size:14px;font-weight:600;border:1px solid}
.vai-alert.show{display:block}
.vai-alert.err{background:rgba(239, 68, 68, 0.1);border-color:rgba(239, 68, 68, 0.2);color:#ef4444}
.vai-alert.ok{background:rgba(22, 163, 74, 0.1);border-color:rgba(22, 163, 74, 0.2);color:#22c55e}

.vai-list{display:flex;flex-direction:column;gap:12px;max-height:400px;overflow:auto}
.vai-item{display:flex;align-items:center;justify-content:space-between;gap:16px;border:1px solid var(--va-border);border-radius:14px;padding:16px;background:var(--va-panel);transition:all 0.2s ease;}
.vai-item:hover{border-color:var(--color-primary);box-shadow: 0 4px 12px rgba(0,0,0,0.05);}
.vai-item h4{margin:0;font-size:14px;font-weight:800;color:var(--va-text)}
.vai-item p{margin:4px 0 0;font-size:13px;color:var(--va-muted)}

.vai-preview{width:100%;aspect-ratio:16/10;object-fit:contain;border-radius:16px;border:2px solid var(--va-border);background:#0f172a}

.vai-badge{display:inline-block;border-radius:999px;padding:4px 12px;font-size:11px;font-weight:800;background:rgba(124,58,237,.1);color:#6d28d9;text-transform:uppercase;letter-spacing:0.05em;}
</style>

<div class="vai-page" id="vaiPage" data-is-admin="{{ $isAdminLike ? '1' : '0' }}">
  <div class="vai-head-card">
    <div class="vai-head-left">
      <div class="vai-head-icon">
        <i class="material-symbols-rounded text-white" style="font-size:32px;">visibility</i>
      </div>
      <div>
        <h1 class="vai-title">Visual AI</h1>
        <div class="vai-sub">Analyse visuelle et assistance intelligente basée sur l'image et la vidéo</div>
      </div>
    </div>
    @if($isAdminLike)
      <span class="vai-badge">Super Admin Access</span>
    @endif
  </div>

  <div class="vai-tabs-container">
    <div class="vai-tabs" id="vaiTabs">
      <button class="vai-tab active" data-tab="analyze">Screenshot Assistant</button>
      <button class="vai-tab" data-tab="screenshare">Live Screenshare</button>
      <button class="vai-tab" data-tab="wizard">Troubleshooting Wizard</button>
      <button class="vai-tab" data-tab="references">References</button>
    </div>
  </div>

  <div class="vai-alert" id="vaiAlert"></div>

  <section class="vai-pane active" id="pane-analyze">
    <div class="vai-card">
      <h3><i class="material-symbols-rounded text-primary">add_photo_alternate</i> Analyse de capture d'écran</h3>
      <p style="margin-bottom:20px">Téléchargez une capture d'écran pour obtenir l'OCR, l'extraction d'éléments d'interface et des conseils de support.</p>
      
      <div class="vai-drop">
        Faites glisser une image ou cliquez pour parcourir
        <input id="anFile" type="file" accept="image/png,image/jpeg,image/webp,image/bmp" />
      </div>
      
      <div class="vai-actions" style="margin-top:20px; justify-content:space-between">
        <div class="d-flex align-items-center gap-3">
          <button class="vai-btn primary" id="anRun">
            <i class="material-symbols-rounded" style="font-size:18px;">analytics</i> Lancer l'analyse
          </button>
          <span id="anName" style="font-size:13px;font-weight:600;color:#64748b"></span>
        </div>
      </div>
    </div>

    <div class="vai-card" id="anResultCard" style="display:none">
      <h3><i class="material-symbols-rounded text-primary">insights</i> Résultats de l'analyse</h3>
      <div id="anOut" class="res-container"></div>
    </div>
  </section>

  <section class="vai-pane" id="pane-screenshare">
    <div class="vai-card">
      <h3><i class="material-symbols-rounded text-primary">video_call</i> Assistance en direct</h3>
      <p style="margin-bottom:20px">Utilisez le partage d'écran en direct, l'envoi de séquences d'images ou le téléchargement d'une courte vidéo.</p>
      
      <div class="vai-grid vai-grid-2">
        <div class="vai-field">
          <label class="vai-label">Mode d'assistance</label>
          <select id="scMode" class="vai-select">
            <option value="live">Partage en direct (Live)</option>
            <option value="frames">Séquence d'images (Frames)</option>
            <option value="video">Vidéo courte (Upload)</option>
          </select>
        </div>
        <div class="vai-field">
          <label class="vai-label">Images par seconde (FPS)</label>
          <input id="scFps" class="vai-input" type="number" min="0.1" step="0.1" value="2" />
        </div>
        <div class="vai-field">
          <label class="vai-label">Clé de référence (Optionnel)</label>
          <input id="scRef" class="vai-input" placeholder="ex: checkout_confirmation" />
        </div>
        <div class="vai-field">
          <label class="vai-label">Embeddings Gemini</label>
          <select id="scGem" class="vai-select">
            <option value="1">Activé</option>
            <option value="0">Désactivé</option>
          </select>
        </div>
      </div>
      <div class="vai-actions" style="margin-top:20px">
        <label style="display:flex;gap:10px;align-items:center;font-size:14px;font-weight:600;color:#1e293b;cursor:pointer">
          <input type="checkbox" id="scConsent" style="width:18px;height:18px" /> J'autorise l'analyse de mon écran
        </label>
      </div>
    </div>

    <div class="vai-card" id="scLiveBlock">
      <div class="vai-actions">
        <button class="vai-btn primary" id="scStartLive">
          <i class="material-symbols-rounded" style="font-size:18px;">videocam</i> Lancer le partage
        </button>
        <button class="vai-btn danger" id="scStopLive" disabled>
          <i class="material-symbols-rounded" style="font-size:18px;">videocam_off</i> Arrêter
        </button>
        <div class="ms-auto d-flex align-items-center gap-2">
          <div id="scLiveIndicator" style="width:10px;height:10px;border-radius:50%;background:#cbd5e1"></div>
          <span id="scLiveState" style="font-size:13px;font-weight:700;color:#64748b">Inactif</span>
        </div>
      </div>
      <video id="scPreview" class="vai-preview" autoplay muted playsinline style="margin-top:20px"></video>
      <div class="vai-history" id="scHistory" style="margin-top:20px"></div>
    </div>

    <div class="vai-card" id="scFramesBlock" style="display:none">
      <h3>Séquence d'images</h3>
      <div class="vai-drop">
        Glissez les images ou cliquez pour sélectionner
        <input id="scFrames" type="file" accept="image/png,image/jpeg,image/webp,image/bmp" multiple />
      </div>
      <div class="vai-actions" style="margin-top:20px">
        <button class="vai-btn primary" id="scRunFrames">Analyser les images</button>
        <span id="scFramesName" style="font-size:13px;font-weight:600;color:#64748b"></span>
      </div>
    </div>

    <div class="vai-card" id="scVideoBlock" style="display:none">
      <h3>Upload Vidéo</h3>
      <div class="vai-drop">
        Sélectionnez une courte vidéo (MP4, WebM)
        <input id="scVideo" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-matroska" />
      </div>
      <div class="vai-actions" style="margin-top:20px">
        <button class="vai-btn primary" id="scRunVideo">Analyser la vidéo</button>
        <span id="scVideoName" style="font-size:13px;font-weight:600;color:#64748b"></span>
      </div>
    </div>

    <div class="vai-card" id="scResultCard" style="display:none">
      <h3><i class="material-symbols-rounded text-primary">insights</i> Résultats de l'analyse Live</h3>
      <div id="scOut" class="res-container"></div>
    </div>
  </section>

  <section class="vai-pane" id="pane-wizard">
    <div class="vai-card">
      <h3><i class="material-symbols-rounded text-primary">auto_fix_high</i> Assistant de dépannage</h3>
      <p style="margin-bottom:20px">Générez un flux de dépannage guidé à partir du contexte du problème et de l'état observé de l'interface.</p>
      
      <div class="vai-grid vai-grid-2">
        <div class="vai-field">
          <label class="vai-label">Objectif du support</label>
          <input id="wGoal" class="vai-input" value="Aider l'utilisateur à finaliser le paiement" />
        </div>
        <div class="vai-field">
          <label class="vai-label">Résumé du problème</label>
          <input id="wIssue" class="vai-input" value="Bouton de validation inactif après saisie de carte" />
        </div>
      </div>
      
      <div class="vai-grid vai-grid-2" style="margin-top:20px">
        <div class="vai-field">
          <label class="vai-label">Description de l'écran</label>
          <textarea id="wCap" class="vai-text">Écran de paiement avec formulaire rempli et bouton désactivé</textarea>
        </div>
        <div class="vai-field">
          <label class="vai-label">Texte visible ou erreur</label>
          <textarea id="wText" class="vai-text">Message d'erreur : "Impossible de traiter la demande".</textarea>
        </div>
      </div>
      
      <div class="vai-grid vai-grid-2" style="margin-top:20px">
        <div class="vai-field">
          <label class="vai-label">Actions déjà tentées (une par ligne)</label>
          <textarea id="wAct" class="vai-text">Rafraîchissement de la page
Essai avec une autre carte</textarea>
        </div>
        <div class="vai-field">
          <label class="vai-label">Indices de contexte (une par ligne)</label>
          <textarea id="wCtx" class="vai-text">Problème apparu aujourd'hui
Concerne un seul compte client</textarea>
        </div>
      </div>
      
      <div class="vai-actions" style="margin-top:24px; justify-content:space-between">
        <div class="d-flex align-items-center gap-2">
          <label class="vai-label mb-0">Nombre d'étapes max</label>
          <input id="wMax" class="vai-input" type="number" min="3" max="8" value="5" style="width:80px" />
        </div>
        <button class="vai-btn primary" id="wRun">
          <i class="material-symbols-rounded" style="font-size:18px;">magic_button</i> Générer l'assistant
        </button>
      </div>
    </div>
    
    <div class="vai-card" id="wResultCard" style="display:none">
      <h3><i class="material-symbols-rounded text-primary">list_alt</i> Flux de résolution généré</h3>
      <div id="wOut" class="res-container"></div>
    </div>
  </section>

  @if($isAdminLike)
  <section class="vai-pane" id="pane-references">
    <div class="vai-grid vai-grid-2">
      <div class="vai-card">
        <h3><i class="material-symbols-rounded text-primary">add_box</i> Créer une référence</h3>
        <div class="vai-grid">
          <div class="vai-field"><label class="vai-label">Nom</label><input id="rName" class="vai-input" placeholder="ex: Confirmation de commande" /></div>
          <div class="vai-field"><label class="vai-label">Clé de l'écran</label><input id="rKey" class="vai-input" placeholder="ex: checkout_confirmation" /></div>
          <div class="vai-field"><label class="vai-label">Description</label><input id="rDesc" class="vai-input" placeholder="Page attendue après succès" /></div>
          <div class="vai-field"><label class="vai-label">Texte OCR attendu</label><input id="rOcr" class="vai-input" placeholder="ex: Commande confirmée" /></div>
          <div class="vai-field full"><label class="vai-label">Éléments attendus (JSON)</label><textarea id="rElems" class="vai-text" placeholder='[{"type":"BUTTON","label":"Continuer"}]'></textarea></div>
          <div class="vai-field full"><label class="vai-label">Image de référence</label><input id="rFile" class="vai-input" type="file" accept="image/png,image/jpeg,image/webp,image/bmp" /></div>
          <div class="vai-actions mt-2"><button class="vai-btn primary w-100" id="rCreate" style="justify-content:center">Créer la référence</button></div>
        </div>
      </div>
      <div class="vai-card">
        <div class="vai-actions mb-3" style="justify-content:space-between">
          <h3 class="mb-0"><i class="material-symbols-rounded text-primary">bookmarks</i> Références enregistrées</h3>
          <button class="vai-btn" id="rRefresh"><i class="material-symbols-rounded" style="font-size:18px;">refresh</i></button>
        </div>
        <div class="vai-list" id="rList"></div>
      </div>
    </div>
  </section>
  @endif
</div>
@endsection

@push('page-scripts')
<script>
(function(){
  const PAGE = document.getElementById('vaiPage');
  const IS_ADMIN = PAGE?.dataset?.isAdmin === '1';
  const $ = (id) => document.getElementById(id);

  const state = {
    analyzeFile: null,
    frames: [],
    video: null,
    liveStream: null,
    liveTimer: null,
    liveActive: false,
    liveFrameNo: 0,
  };

  function showAlert(message, ok) {
    const box = $('vaiAlert');
    box.textContent = message;
    box.className = 'vai-alert show ' + (ok ? 'ok' : 'err');
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(() => box.className = 'vai-alert', 4200);
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

  function renderResult(containerId, data) {
    const container = $(containerId);
    if (!container) return;
    container.innerHTML = '';
    
    const res = document.createElement('div');
    res.className = 'res-container';

    // 1. CAPTION / SUMMARY (High Impact)
    const caption = data.ui_analysis?.caption || data.caption || data.summary || data.explanation;
    if (caption) {
      const section = document.createElement('div');
      section.className = 'res-section highlight';
      section.innerHTML = `
        <div class="res-section-title"><i class="material-symbols-rounded">auto_awesome</i> Description & Analyse</div>
        <div class="res-caption">${caption}</div>
      `;
      res.appendChild(section);
    }

    // 2. DIAGNOSIS & KEY METADATA
    if (data.diagnosis || data.risk_level || data.estimated_time_minutes) {
      const section = document.createElement('div');
      section.className = 'res-section';
      
      let html = `<div class="res-section-title"><i class="material-symbols-rounded">analytics</i> Diagnostic & Risques</div>`;
      
      if (data.diagnosis) {
        html += `<div class="res-summary mb-3 diag">${data.diagnosis}</div>`;
      }
      
      html += `<div class="res-meta-grid">`;
      
      if (data.risk_level) {
        const r = String(data.risk_level).toLowerCase();
        const color = r === 'high' || r === 'critical' ? '#ef4444' : (r === 'medium' ? '#f59e0b' : '#10b981');
        html += `
          <div class="res-meta-card">
            <div class="res-meta-icon" style="background:${color}15; color:${color}"><i class="material-symbols-rounded">warning</i></div>
            <div><span class="res-meta-label">Niveau de risque</span><span class="res-meta-value" style="color:${color}">${data.risk_level.toUpperCase()}</span></div>
          </div>
        `;
      }
      
      if (data.estimated_time_minutes) {
        html += `
          <div class="res-meta-card">
            <div class="res-meta-icon" style="background:rgba(37, 99, 235, 0.1); color:#3b82f6"><i class="material-symbols-rounded">schedule</i></div>
            <div><span class="res-meta-label">Temps estimé</span><span class="res-meta-value">${data.estimated_time_minutes} minutes</span></div>
          </div>
        `;
      }

      if (data.provider || data.model) {
        html += `
          <div class="res-meta-card">
            <div class="res-meta-icon" style="background:rgba(124, 58, 237, 0.1); color:#8b5cf6"><i class="material-symbols-rounded">smart_toy</i></div>
            <div><span class="res-meta-label">Moteur IA</span><span class="res-meta-value">${data.model || data.provider}</span></div>
          </div>
        `;
      }
      
      html += `</div>`;
      section.innerHTML = html;
      res.appendChild(section);
    }

    // 3. LABELS / TAGS
    const labels = data.ui_analysis?.labels || data.labels || [];
    if (Array.isArray(labels) && labels.length > 0) {
      const section = document.createElement('div');
      section.className = 'res-section';
      let html = `<div class="res-section-title"><i class="material-symbols-rounded">label</i> Tags de détection</div><div class="res-tags">`;
      labels.forEach(label => {
        html += `<span class="res-tag">${label}</span>`;
      });
      html += `</div>`;
      section.innerHTML = html;
      res.appendChild(section);
    }

    // 4. DETECTED UI ELEMENTS (Grid)
    const elements = data.ui_analysis?.elements || data.elements || data.objects || data.detected_items || [];
    if (Array.isArray(elements) && elements.length > 0) {
      const section = document.createElement('div');
      section.className = 'res-section';
      let html = `<div class="res-section-title"><i class="material-symbols-rounded">widgets</i> Éléments d'interface détectés</div><div class="res-elements">`;
      elements.forEach(el => {
        const type = el.element_type || el.type || el.class || 'Element';
        const label = el.label || el.text || el.name || 'N/A';
        const confidence = el.confidence ? ` (${Math.round(el.confidence * 100)}%)` : '';
        html += `
          <div class="res-element">
            <div class="res-element-type">${type}</div>
            <div class="res-element-label">${label}${confidence}</div>
          </div>
        `;
      });
      html += `</div>`;
      section.innerHTML = html;
      res.appendChild(section);
    }

    // 5. DETECTED OCR TEXT (Monospace Box)
    const ocrText = data.ocr?.text || data.ocr_text || data.text_content;
    if (ocrText) {
      const section = document.createElement('div');
      section.className = 'res-section';
      const ocrConf = data.ocr?.confidence ? ` <span style="font-size:10px; color:#94a3b8; float:right;">Confiance: ${Math.round(data.ocr.confidence * 100)}%</span>` : '';
      section.innerHTML = `
        <div class="res-section-title"><i class="material-symbols-rounded">spellcheck</i> Texte extrait (OCR)${ocrConf}</div>
        <div class="res-ocr-box">${ocrText}</div>
      `;
      res.appendChild(section);
    }

    // 6. ASSISTANCE / RECOMMENDATIONS (Steps)
    const hints = data.assistance_hints || data.recommendations || data.steps || [];
    if (Array.isArray(hints) && hints.length > 0) {
      const section = document.createElement('div');
      section.className = 'res-section';
      let html = `<div class="res-section-title"><i class="material-symbols-rounded">lightbulb</i> Recommandations & Actions</div><div class="res-steps">`;
      hints.forEach((hint, idx) => {
        let step = hint;
        // Auto-detect JSON strings for wizard steps
        if (typeof hint === 'string' && hint.trim().startsWith('{')) {
          try { step = JSON.parse(hint); } catch(_) {}
        }

        if (typeof step === 'object' && step.title) {
          // Rich Wizard Step
          html += `
            <div class="res-step">
              <div class="res-step-num">${step.step_number || (idx + 1)}</div>
              <div class="res-step-content">
                <div class="res-step-title">
                  <span>${step.title}</span>
                  ${step.estimated_time ? `<span class="badge bg-light text-dark border text-xxs">${step.estimated_time}</span>` : ''}
                </div>
                ${step.why ? `<div class="res-step-why">${step.why}</div>` : ''}
                <ul class="res-step-instructions">
                  ${(Array.isArray(step.instructions) ? step.instructions : [step.text || '']).map(i => `<li>${i}</li>`).join('')}
                </ul>
                <div class="res-step-logic">
                  <div class="res-logic-box expected">
                    <span class="res-logic-label">Résultat attendu</span>
                    ${step.expected_signal || 'Validation de l\'étape'}
                  </div>
                  <div class="res-logic-box fallback">
                    <span class="res-logic-label">Si échec</span>
                    ${step.if_not_seen || 'Contacter le support'}
                  </div>
                </div>
              </div>
            </div>
          `;
        } else {
          // Simple Text Step
          const text = typeof hint === 'string' ? hint : (hint.text || hint.description || JSON.stringify(hint));
          html += `
            <div class="res-step">
              <div class="res-step-num">${idx + 1}</div>
              <div class="res-step-content"><div class="res-step-text">${text}</div></div>
            </div>
          `;
        }
      });
      html += `</div>`;
      section.innerHTML = html;
      res.appendChild(section);
    }

    // 7. OTHER METADATA
    const knownKeys = ['ocr', 'ui_analysis', 'caption', 'summary', 'explanation', 'assistance_hints', 'recommendations', 'steps', 'elements', 'objects', 'detected_items', 'ocr_text', 'text_content', 'labels', 'diagnosis', 'risk_level', 'estimated_time_minutes', 'provider', 'model', 'generated_at', 'escalation_hint', 'issue_summary'];
    const otherData = {};
    Object.keys(data).forEach(k => {
      if (!knownKeys.includes(k)) otherData[k] = data[k];
    });

    if (Object.keys(otherData).length > 0) {
      const section = document.createElement('div');
      section.className = 'res-section';
      section.innerHTML = `
        <div class="res-section-title"><i class="material-symbols-rounded">data_object</i> Données techniques</div>
        <pre style="margin:0; font-size:11px; background:var(--vai-bg); padding:12px; border-radius:10px; border:1px solid var(--va-border); color:var(--va-text); overflow:auto;">${JSON.stringify(otherData, null, 2)}</pre>
      `;
      res.appendChild(section);
    }

    container.appendChild(res);
    
    // Show result card
    const card = container.closest('.vai-card');
    if (card) card.style.display = 'block';
  }

  function pretty(v) {
    try { return JSON.stringify(v, null, 2); } catch (_) { return String(v); }
  }

  function linesToList(value) {
    return String(value || '').split('\n').map((s) => s.trim()).filter(Boolean);
  }

  function switchTab(tab) {
    document.querySelectorAll('.vai-tab').forEach((el) => el.classList.toggle('active', el.dataset.tab === tab));
    document.querySelectorAll('.vai-pane').forEach((el) => el.classList.toggle('active', el.id === 'pane-' + tab));
  }

  document.querySelectorAll('.vai-tab').forEach((btn) => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));

  // Analyze
  $('anFile').addEventListener('change', (e) => {
    state.analyzeFile = e.target.files?.[0] || null;
    $('anName').textContent = state.analyzeFile ? state.analyzeFile.name : '';
  });
  $('anRun').addEventListener('click', async () => {
    try {
      if (!state.analyzeFile) throw new Error('Select an image first.');
      const fd = new FormData();
      fd.append('file', state.analyzeFile);
      const out = await api('/visual-ai/analyze-raw', { method: 'POST', body: fd });
      renderResult('anOut', out);
      showAlert('Screenshot analyzed.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });

  // Screenshare mode switch
  function syncScreenshareMode() {
    const mode = $('scMode').value;
    $('scLiveBlock').style.display = mode === 'live' ? '' : 'none';
    $('scFramesBlock').style.display = mode === 'frames' ? '' : 'none';
    $('scVideoBlock').style.display = mode === 'video' ? '' : 'none';
  }
  $('scMode').addEventListener('change', syncScreenshareMode);
  syncScreenshareMode();

  function currentScreenOpts() {
    return {
      consent: $('scConsent').checked,
      source_fps: Number($('scFps').value || 2),
      target_fps: Number($('scFps').value || 2),
      reference_key: ($('scRef').value || '').trim() || undefined,
      use_gemini_embeddings: $('scGem').value === '1' ? true : undefined,
    };
  }

  $('scFrames').addEventListener('change', (e) => {
    state.frames = Array.from(e.target.files || []);
    $('scFramesName').textContent = state.frames.length ? state.frames.length + ' frame(s) selected' : '';
  });
  $('scVideo').addEventListener('change', (e) => {
    state.video = e.target.files?.[0] || null;
    $('scVideoName').textContent = state.video ? state.video.name : '';
  });

  $('scRunFrames').addEventListener('click', async () => {
    try {
      const opts = currentScreenOpts();
      if (!opts.consent) throw new Error('Consent is required.');
      if (!state.frames.length) throw new Error('Please select at least one frame.');
      const fd = new FormData();
      state.frames.forEach((f) => fd.append('frames', f));
      Object.entries(opts).forEach(([k,v]) => { if (v !== undefined) fd.append(k, String(v)); });
      const out = await api('/visual-ai/screenshare/assist', { method: 'POST', body: fd });
      renderResult('scOut', out);
      showAlert('Frames analyzed.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });

  $('scRunVideo').addEventListener('click', async () => {
    try {
      const opts = currentScreenOpts();
      if (!opts.consent) throw new Error('Consent is required.');
      if (!state.video) throw new Error('Please select a video file.');
      const fd = new FormData();
      fd.append('file', state.video);
      fd.append('video', state.video);
      Object.entries(opts).forEach(([k,v]) => { if (v !== undefined) fd.append(k, String(v)); });
      const out = await api('/visual-ai/screenshare/assist-video', { method: 'POST', body: fd });
      renderResult('scOut', out);
      showAlert('Video analyzed.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });

  async function captureLiveFrame(stream) {
    const video = $('scPreview');
    if (!video.videoWidth || !video.videoHeight) return null;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) return null;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) return null;
    return new File([blob], 'live-frame-' + Date.now() + '.png', { type: 'image/png' });
  }

  async function runLiveIteration() {
    if (!state.liveActive || !state.liveStream) return;
    const opts = currentScreenOpts();
    const fps = Math.max(0.2, Math.min(Number(opts.target_fps || 2), 5));
    const nextMs = Math.max(450, Math.round(1000 / fps));

    try {
      state.liveFrameNo += 1;
      $('scLiveState').textContent = 'Capturing frame #' + state.liveFrameNo + '...';
      const frame = await captureLiveFrame(state.liveStream);
      if (!frame) throw new Error('Could not capture a frame yet.');

      const fd = new FormData();
      fd.append('frames', frame);
      fd.append('consent', 'true');
      fd.append('source_fps', String(opts.source_fps || 2));
      fd.append('target_fps', String(opts.target_fps || 2));
      if (opts.reference_key) fd.append('reference_key', String(opts.reference_key));
      if (opts.use_gemini_embeddings) fd.append('use_gemini_embeddings', 'true');
      fd.append('frame_number', String(state.liveFrameNo));
      fd.append('chunk_index', String(state.liveFrameNo));

      const out = await api('/visual-ai/screenshare/assist', { method: 'POST', body: fd });
      renderResult('scOut', out);
      const hint = out?.caption || (Array.isArray(out?.assistance_hints) ? out.assistance_hints[0] : '') || 'Frame analyzed.';
      const row = document.createElement('div');
      row.className = 'vai-item';
      row.innerHTML = '<div><span class="vai-chip" style="background:var(--color-primary); color:#fff;">Frame ' + state.liveFrameNo + '</span><p style="margin-top:8px; font-weight:600; color:#1e293b;">' + String(hint).replace(/</g, '&lt;') + '</p></div>';
      const list = $('scHistory');
      list.prepend(row);
      while (list.children.length > 24) list.removeChild(list.lastChild);
      $('scLiveState').textContent = 'Analyse en cours...';
      $('scLiveIndicator').style.background = 'var(--va-good)';
    } catch (err) {
      $('scLiveState').textContent = 'Erreur: ' + (err.message || String(err));
      $('scLiveIndicator').style.background = 'var(--va-bad)';
    }

    if (state.liveActive) {
      state.liveTimer = window.setTimeout(runLiveIteration, nextMs);
    }
  }

  $('scStartLive').addEventListener('click', async () => {
    try {
      const opts = currentScreenOpts();
      if (!opts.consent) throw new Error('Consent is required.');
      if (!navigator.mediaDevices?.getDisplayMedia) throw new Error('Screen sharing is not supported in this browser.');

      state.liveStream = await navigator.mediaDevices.getDisplayMedia({ video: { frameRate: Math.min(Math.max(Number(opts.target_fps || 2), 1), 5) }, audio: false });
      $('scPreview').srcObject = state.liveStream;
      await $('scPreview').play().catch(() => {});
      state.liveFrameNo = 0;
      state.liveActive = true;
      $('scHistory').innerHTML = '';
      $('scStartLive').disabled = true;
      $('scStopLive').disabled = false;
      $('scLiveState').textContent = 'Partage actif';
      $('scLiveIndicator').style.background = 'var(--va-good)';

      state.liveStream.getVideoTracks()[0]?.addEventListener('ended', () => {
        $('scStopLive').click();
      });

      runLiveIteration();
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });

  $('scStopLive').addEventListener('click', () => {
    state.liveActive = false;
    if (state.liveTimer) window.clearTimeout(state.liveTimer);
    state.liveTimer = null;
    if (state.liveStream) {
      state.liveStream.getTracks().forEach((t) => t.stop());
      state.liveStream = null;
    }
    $('scPreview').srcObject = null;
    $('scStartLive').disabled = false;
    $('scStopLive').disabled = true;
    $('scLiveState').textContent = 'Partage arrêté';
    $('scLiveIndicator').style.background = '#cbd5e1';
  });

  // Wizard
  $('wRun').addEventListener('click', async () => {
    try {
      const payload = {
        goal: $('wGoal').value,
        issue_summary: $('wIssue').value || undefined,
        observed_screen_caption: $('wCap').value || undefined,
        observed_text: $('wText').value || undefined,
        user_actions_attempted: linesToList($('wAct').value),
        context_hints: linesToList($('wCtx').value),
        max_steps: Math.max(3, Math.min(8, Number($('wMax').value || 5))),
      };
      const out = await api('/visual-ai/troubleshooting/wizard', { method: 'POST', body: JSON.stringify(payload) });
      renderResult('wOut', out);
      showAlert('Wizard generated.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });

  // References
  async function loadRefs() {
    if (!IS_ADMIN) return;
    const out = await api('/visual-ai/references?limit=200');
    const list = $('rList');
    list.innerHTML = '';
    const items = Array.isArray(out?.items) ? out.items : [];
    if (!items.length) {
      list.innerHTML = '<p style="color:#64748b;font-size:13px">No references yet.</p>';
      return;
    }
    items.forEach((r) => {
      const el = document.createElement('div');
      el.className = 'vai-item';
      const key = r.screen_key || r.key || '';
      const desc = r.description || '';
      el.innerHTML = '<div><h4>' + String(r.name || key || 'Reference').replace(/</g, '&lt;') + '</h4><p><span class="vai-chip">' + String(key).replace(/</g, '&lt;') + '</span></p>' + (desc ? '<p>' + String(desc).replace(/</g, '&lt;') + '</p>' : '') + '</div>';
      const btn = document.createElement('button');
      btn.className = 'vai-btn bad';
      btn.textContent = 'Delete';
      btn.addEventListener('click', async () => {
        try {
          await api('/visual-ai/references/' + r.id, { method: 'DELETE' });
          showAlert('Reference deleted.', true);
          loadRefs();
        } catch (err) {
          showAlert(err.message || String(err), false);
        }
      });
      el.appendChild(btn);
      list.appendChild(el);
    });
  }

  if (IS_ADMIN) {
    $('rRefresh').addEventListener('click', () => loadRefs().catch((err) => showAlert(err.message || String(err), false)));
    $('rCreate').addEventListener('click', async () => {
      try {
        const name = ($('rName').value || '').trim();
        const key = ($('rKey').value || '').trim();
        const file = $('rFile').files?.[0] || null;
        if (!name) throw new Error('Reference name is required.');
        if (!key) throw new Error('Screen key is required.');
        if (!file) throw new Error('Reference image is required.');

        const fd = new FormData();
        fd.append('file', file);
        fd.append('name', name);
        fd.append('screen_key', key);
        const desc = ($('rDesc').value || '').trim();
        const ocr = ($('rOcr').value || '').trim();
        const elemsRaw = ($('rElems').value || '').trim();
        if (desc) fd.append('description', desc);
        if (ocr) fd.append('expected_ocr_text', ocr);
        if (elemsRaw) {
          const parsed = JSON.parse(elemsRaw);
          if (!Array.isArray(parsed)) throw new Error('Expected elements must be a JSON array.');
          fd.append('expected_elements', JSON.stringify(parsed));
        }

        await api('/visual-ai/references', { method: 'POST', body: fd });
        $('rName').value = '';
        $('rKey').value = '';
        $('rDesc').value = '';
        $('rOcr').value = '';
        $('rElems').value = '';
        $('rFile').value = '';
        showAlert('Reference created.', true);
        loadRefs();
      } catch (err) {
        showAlert(err.message || String(err), false);
      }
    });
    loadRefs().catch((err) => showAlert(err.message || String(err), false));
  }
})();
</script>
@endpush
