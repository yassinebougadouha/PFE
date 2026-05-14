@extends('layouts.dashboard')
@section('title', 'Troubleshooting Wizard - L2T Support')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
/* ── Hide floating widget on this full-page wizard ── */
#cwBtn,#cwWrap,#cwWrap #cwPanel{display:none!important;visibility:hidden!important;pointer-events:none!important;}
.main-content{padding:0!important;overflow:hidden;}
*{box-sizing:border-box;}

/* ══ VARIABLES ══ */
#wizardWrap{
  --p:#6C63FF;
  --p-soft:#EEEDFE;
  --p-mid:#AFA9EC;
  --p-dark:#3C3489;
  --bg:#f8fafc;
  --bg2:#ffffff;
  --brd:#e2e8f0;
  --t1:#1e293b;
  --t2:#334155;
  --t3:#64748b;
  --font:'Inter',system-ui,sans-serif;
  --radius-md:12px;
  --radius-lg:20px;
}

[data-bs-theme="dark"] #wizardWrap {
  --bg: #0f172a;
  --bg2: #1e293b;
  --brd: #334155;
  --t1: #f1f5f9;
  --t2: #e2e8f0;
  --t3: #94a3b8;
}

/* ══ LAYOUT ══ */
#wizardWrap{
  display:flex;
  min-height:calc(100vh - 120px);
  background:var(--bg);
  font-family:var(--font);
  flex-direction:column;
  border-radius:16px;
  color:var(--t1);
}

/* ── Header ── */
.wiz-head-card{
  background: var(--bg2);
  border-radius: 20px;
  padding: 32px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 24px;
  border: 1px solid var(--brd);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.wiz-head-left{display:flex;align-items:center;gap:20px;}
.wiz-head-icon{
  width:60px;height:60px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
  border-radius:16px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.wiz-title{color:var(--t1);font-weight:800;margin:0;font-size:26px;letter-spacing:-0.03em;}
.wiz-sub{color:var(--t3);margin:0;font-size:14px;font-weight:500;}

/* ══ CONTENT ══ */
#wizardContent{
  flex:1;
  padding:0 0 24px;
}
.wizard-card{
  background: var(--bg2);
  border: 1px solid var(--brd);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
}
.wizard-card h3{
  font-size:16px;
  font-weight:800;
  color:var(--t1);
  margin:0 0 20px;
  display:flex;
  align-items:center;
  gap:10px;
  letter-spacing:-0.01em;
}

.wizard-label{
  display:block;
  font-size:11px;
  font-weight:800;
  color:var(--t3);
  text-transform:uppercase;
  letter-spacing:.05em;
  margin-bottom:8px;
}
.wizard-input,.wizard-text,.wizard-select{
  width:100%;
  border:2px solid var(--brd);
  background:var(--bg);
  color:var(--t1);
  border-radius:var(--radius-md);
  padding:12px 14px;
  font-size:14px;
  font-weight:600;
  outline:none;
  transition: all 0.2s ease;
}
.wizard-input:focus,.wizard-text:focus,.wizard-select:focus{
  border-color:var(--color-primary);
  background: var(--bg) !important;
  color: var(--t1) !important;
  box-shadow: 0 0 0 4px color-mix(in srgb,var(--color-primary) 10%,transparent)
}
.wizard-text{
  resize:vertical;
  min-height:100px;
}

.wizard-actions{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:center;
  justify-content: space-between;
}
.wizard-btn{
  border:2px solid var(--brd);
  border-radius:12px;
  background:var(--bg2);
  color:var(--t3);
  padding:10px 24px;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:8px;
  transition: all 0.2s ease;
}
.wizard-btn:hover{
  border-color:var(--color-primary);
  color:var(--color-primary);
  transform:translateY(-1px);
}
.wizard-btn.primary{
  background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
  border-color:transparent;
  color:#fff;
}
.wizard-btn.primary:hover{
  color:#fff;
  opacity:0.95;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

/* Result Components Styles */
.res-container{display:flex;flex-direction:column;gap:20px}
.res-section{padding:20px;border-radius:16px;background:var(--bg2);border:1px solid var(--brd);box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
.res-section.highlight{border-left:4px solid var(--color-primary);background:var(--bg2);}
[data-bs-theme="dark"] .res-section.highlight { background: rgba(108, 99, 255, 0.05); }

.res-section-title{font-size:11px;font-weight:800;color:var(--t3);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.res-section-title i{font-size:18px;color:var(--color-primary);}

.res-caption{font-size:16px;line-height:1.6;color:var(--t1);font-weight:700;letter-spacing:-0.01em;}
.res-summary{font-size:14px;line-height:1.6;color:var(--t2);font-weight:500;}
.res-summary.diag{padding:12px; background:rgba(37, 99, 235, 0.1); border-radius:10px; border:1px solid rgba(37, 99, 235, 0.2); color:var(--color-primary);}

.res-steps{display:flex;flex-direction:column;gap:12px;}
.res-step{display:flex;gap:16px;align-items:flex-start;padding:20px;border-radius:16px;background:var(--bg2);border:1px solid var(--brd);box-shadow: 0 2px 8px rgba(0,0,0,0.02);}
.res-step-num{width:26px;height:26px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;box-shadow: 0 4px 8px rgba(0,0,0,0.1);}
.res-step-content{flex-grow:1;}
.res-step-title{font-size:15px;font-weight:800;color:var(--t1);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;}
.res-step-why{font-size:12px;color:var(--t3);font-style:italic;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--brd);}
.res-step-instructions{list-style:none;padding:0;margin:0 0 16px;display:flex;flex-direction:column;gap:8px;}
.res-step-instructions li{font-size:13px;color:var(--t2);display:flex;gap:8px;align-items:flex-start;}
.res-step-instructions li::before{content:'→';color:var(--color-primary);font-weight:bold;}
.res-step-logic{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;padding-top:12px;border-top:1px dashed var(--brd);}
.res-logic-box{padding:10px;border-radius:10px;font-size:11px;}
.res-logic-box.expected{background:rgba(22, 163, 74, 0.1);border:1px solid rgba(22, 163, 74, 0.2);color:#22c55e;}
.res-logic-box.fallback{background:rgba(239, 68, 68, 0.1);border:1px solid rgba(239, 68, 68, 0.2);color:#ef4444;}
.res-logic-label{font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;display:block;}

.res-meta-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-top:8px;}
.res-meta-card{padding:16px;border-radius:14px;background:var(--bg2);border:1.5px solid var(--brd);display:flex;align-items:center;gap:12px;}
.res-meta-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.res-meta-label{font-size:10px;font-weight:800;color:var(--t3);text-transform:uppercase;letter-spacing:0.05em;display:block;}
.res-meta-value{font-size:13px;font-weight:700;color:var(--t1);}

.wizard-alert{
  display:none;
  border-radius:12px;
  padding:12px 16px;
  font-size:14px;
  font-weight:600;
  border:1px solid;
  margin-bottom: 24px;
}
.wizard-alert.show{display:block}
.wizard-alert.err{background:#fff1f2;border-color:#ffe4e6;color:#be123c}
.wizard-alert.ok{background:#f0fdf4;border-color:#dcfce7;color:#166534}
</style>

<div id="wizardWrap">
  <div class="wiz-head-card">
    <div class="wiz-head-left">
      <div class="wiz-head-icon">
        <i class="material-symbols-rounded text-white" style="font-size:32px;">auto_fix_high</i>
      </div>
      <div>
        <h1 class="wiz-title">Troubleshooting Wizard</h1>
        <div class="wiz-sub">Générez un flux de dépannage guidé à partir du contexte du problème</div>
      </div>
    </div>
  </div>

  <div id="wizardContent">
    <div class="wizard-alert" id="wizardAlert"></div>

    <div class="wizard-card">
      <h3><i class="material-symbols-rounded text-primary">edit_note</i> Contexte du problème</h3>
      <div class="wizard-grid two">
        <div>
          <label class="wizard-label">Objectif du support</label>
          <input id="wGoal" class="wizard-input" value="Aider l'utilisateur à finaliser le paiement" />
        </div>
        <div>
          <label class="wizard-label">Résumé du problème</label>
          <input id="wIssue" class="wizard-input" value="Bouton de validation inactif après saisie de carte" />
        </div>
      </div>
      <div class="wizard-grid two" style="margin-top:20px">
        <div>
          <label class="wizard-label">Description de l'écran</label>
          <textarea id="wCap" class="wizard-text">Écran de paiement avec formulaire rempli et bouton désactivé</textarea>
        </div>
        <div>
          <label class="wizard-label">Texte visible ou erreur</label>
          <textarea id="wText" class="wizard-text">Message d'erreur : "Impossible de traiter la demande".</textarea>
        </div>
      </div>
      <div class="wizard-grid two" style="margin-top:20px">
        <div>
          <label class="wizard-label">Actions déjà tentées (une par ligne)</label>
          <textarea id="wAct" class="wizard-text">Rafraîchissement de la page
Essai avec une autre carte</textarea>
        </div>
        <div>
          <label class="wizard-label">Indices de contexte (une par ligne)</label>
          <textarea id="wCtx" class="wizard-text">Problème apparu aujourd'hui
Concerne un seul compte client</textarea>
        </div>
      </div>
      <div class="wizard-actions" style="margin-top:24px">
        <div class="d-flex align-items-center gap-2">
          <label class="wizard-label mb-0">Étapes max</label>
          <input id="wMax" class="wizard-input" type="number" min="3" max="8" value="5" style="width:80px" />
        </div>
        <button class="wizard-btn primary" id="wRun">
          <i class="material-symbols-rounded" style="font-size:18px;">magic_button</i> Générer l'assistant
        </button>
      </div>
    </div>

    <div class="wizard-card" id="wResultCard" style="display:none">
      <h3><i class="material-symbols-rounded text-primary">list_alt</i> Flux de résolution généré</h3>
      <div id="wOut" class="res-container"></div>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = (id) => document.getElementById(id);

  function showAlert(message, ok) {
    const box = $('wizardAlert');
    box.textContent = message;
    box.className = 'wizard-alert show ' + (ok ? 'ok' : 'err');
    window.clearTimeout(showAlert._t);
    showAlert._t = window.setTimeout(() => box.className = 'wizard-alert', 4200);
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
            <div><span class="res-meta-label">Risque</span><span class="res-meta-value" style="color:${color}">${data.risk_level.toUpperCase()}</span></div>
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

    // 3. ASSISTANCE / RECOMMENDATIONS (Steps)
    const hints = data.assistance_hints || data.recommendations || data.steps || [];
    if (Array.isArray(hints) && hints.length > 0) {
      const section = document.createElement('div');
      section.className = 'res-section';
      let html = `<div class="res-section-title"><i class="material-symbols-rounded">lightbulb</i> Recommandations & Actions</div><div class="res-steps">`;
      hints.forEach((hint, idx) => {
        let step = hint;
        if (typeof hint === 'string' && hint.trim().startsWith('{')) {
          try { step = JSON.parse(hint); } catch(_) {}
        }

        if (typeof step === 'object' && step.title) {
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

    // 4. OTHER METADATA
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
        <pre style="margin:0; font-size:11px; background:var(--bg); padding:12px; border-radius:10px; border:1px solid var(--brd); color:var(--t2); overflow:auto;">${JSON.stringify(otherData, null, 2)}</pre>
      `;
      res.appendChild(section);
    }

    container.appendChild(res);
    $('wResultCard').style.display = 'block';
  }

  function linesToList(value) {
    return String(value || '').split('\n').map((s) => s.trim()).filter(Boolean);
  }

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
      showAlert('Assistant de dépannage généré.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  });
})();
</script>
@endsection
