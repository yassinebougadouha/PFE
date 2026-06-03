<?php $__env->startSection('title', 'Detail appel vocal - L2T Support'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $role = auth()->user()->role ?? 'admin';
  $backUrl = $role === 'admin' ? route('admin.voice-calls') : route('super-admin.voice-calls');
?>

<style>
.vcd-page{min-height:calc(100vh - 120px);padding:22px 24px 34px;font-family:'DM Sans',system-ui,sans-serif;color:var(--text-main,#334155);}
.vcd-wrap{max-width:980px;margin:0 auto;}
.vcd-top{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap;}
.vcd-back,.vcd-btn{display:inline-flex;align-items:center;gap:7px;border:1.5px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);color:var(--text-main,#334155);border-radius:10px;padding:8px 13px;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;font-family:inherit;}
.vcd-btn-primary{background:linear-gradient(135deg,var(--color-primary,#1a56db),var(--color-secondary,#764ba2));border:none;color:#fff;}
.vcd-btn-danger{background:#ef4444;border-color:#ef4444;color:#fff;}
.vcd-btn:disabled{opacity:.55;cursor:not-allowed;}
.vcd-head{display:flex;gap:14px;align-items:flex-start;margin-bottom:18px;}
.vcd-icon{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--color-primary,#1a56db) 12%,transparent);color:var(--color-primary,#1a56db);flex:0 0 auto;}
.vcd-title{font-size:20px;font-weight:800;color:var(--text-heading,#1e293b);line-height:1.2;margin:0;}
.vcd-sub{font-size:12px;color:var(--text-muted,#64748b);margin-top:4px;}
.vcd-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px;}
.vcd-stat,.vcd-section{background:var(--bg-card,#fff);border:1.5px solid var(--border-color,#e2e8f0);border-radius:16px;box-shadow:var(--card-shadow,0 1px 4px rgba(0,0,0,.04));}
.vcd-stat{padding:14px 16px;}
.vcd-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted,#64748b);margin-bottom:6px;}
.vcd-value{font-size:14px;font-weight:800;color:var(--text-heading,#1e293b);word-break:break-word;}
.vcd-section{margin-bottom:16px;overflow:hidden;}
.vcd-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px;border-bottom:1px solid var(--border-color,#e2e8f0);background:color-mix(in srgb,var(--bg-body,#f8fafc) 72%,transparent);}
.vcd-section-title{font-size:13px;font-weight:800;color:var(--text-heading,#1e293b);}
.vcd-section-desc{font-size:11px;color:var(--text-muted,#64748b);margin-top:2px;}
.vcd-section-body{padding:18px;}
.vcd-transcript{max-height:410px;overflow:auto;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);padding:14px;font:12px/1.7 'DM Mono',ui-monospace,monospace;white-space:pre-wrap;color:var(--text-main,#334155);}
.vcd-empty{padding:26px;text-align:center;color:var(--text-muted,#64748b);font-size:13px;}
.vcd-alert{display:flex;align-items:flex-start;gap:9px;border-radius:12px;padding:10px 12px;font-size:13px;margin-top:12px;}
.vcd-alert-ok{border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d;}
.vcd-alert-bad{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;}
.vcd-summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
.vcd-block{border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);padding:12px 14px;}
.vcd-block p{font-size:13px;line-height:1.55;margin:0;color:var(--text-main,#334155);}
.vcd-actions{display:grid;gap:8px;margin-top:10px;}
.vcd-action{display:flex;gap:10px;align-items:flex-start;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);padding:10px 12px;}
.vcd-form-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end;}
.vcd-input,.vcd-textarea{width:100%;border:1.5px solid var(--border-color,#e2e8f0);border-radius:10px;background:var(--input-bg,#fff);color:var(--text-main,#334155);padding:10px 12px;font-size:13px;outline:none;font-family:inherit;}
.vcd-input:focus,.vcd-textarea:focus{border-color:var(--color-primary,#1a56db);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary,#1a56db) 14%,transparent);}
.vcd-textarea{min-height:130px;resize:vertical;line-height:1.55;}
.vcd-create-grid{display:grid;gap:12px;margin-top:14px;}
.vcd-divider{display:flex;align-items:center;gap:12px;margin:15px 0;color:var(--text-muted,#64748b);font-size:11px;font-weight:700;}
.vcd-divider:before,.vcd-divider:after{content:'';height:1px;background:var(--border-color,#e2e8f0);flex:1;}
.vcd-spinner{animation:vcd-spin .8s linear infinite;}
@keyframes vcd-spin{to{transform:rotate(360deg)}}
[data-bs-theme="dark"] .vcd-stat,[data-bs-theme="dark"] .vcd-section,[data-bs-theme="dark"] .vcd-back,[data-bs-theme="dark"] .vcd-btn{background:#1e293b;border-color:#334155;color:#cbd5e1;}
[data-bs-theme="dark"] .vcd-section-head{background:rgba(255,255,255,.02);border-color:#334155;}
[data-bs-theme="dark"] .vcd-block,[data-bs-theme="dark"] .vcd-action,[data-bs-theme="dark"] .vcd-transcript{background:rgba(255,255,255,.025);border-color:#334155;}
@media(max-width:760px){.vcd-page{padding:16px}.vcd-grid,.vcd-summary-grid{grid-template-columns:1fr}.vcd-form-grid{grid-template-columns:1fr}.vcd-top{align-items:stretch}.vcd-btn,.vcd-back{justify-content:center}}
</style>

<div class="vcd-page">
  <div class="vcd-wrap">
    <div class="vcd-top">
      <a class="vcd-back" href="<?php echo e($backUrl); ?>">
        <span class="material-symbols-rounded" style="font-size:17px;">chevron_left</span>
        Retour aux appels
      </a>
      <button class="vcd-btn" id="refreshBtn" type="button" onclick="loadCallDetail()">
        <span class="material-symbols-rounded" style="font-size:16px;">refresh</span>
        Rafraichir
      </button>
    </div>

    <div id="loadingBox" class="vcd-section">
      <div class="vcd-empty">Chargement du detail de l'appel...</div>
    </div>

    <div id="detailBox" style="display:none;">
      <div class="vcd-head">
        <div class="vcd-icon"><span class="material-symbols-rounded">phone_in_talk</span></div>
        <div>
          <h1 class="vcd-title" id="callTitle">-</h1>
          <div class="vcd-sub" id="callSub">-</div>
        </div>
      </div>

      <div class="vcd-grid">
        <div class="vcd-stat"><div class="vcd-label">Duree</div><div class="vcd-value" id="durationValue">-</div></div>
        <div class="vcd-stat"><div class="vcd-label">Room SID</div><div class="vcd-value" id="roomSidValue">-</div></div>
        <div class="vcd-stat"><div class="vcd-label">Enregistrement</div><div class="vcd-value" id="recordingValue">-</div></div>
      </div>

      <div class="vcd-section" id="audioSection" style="display:none;">
        <div class="vcd-section-head">
          <div>
            <div class="vcd-section-title">Enregistrement audio</div>
            <div class="vcd-section-desc">Lecture directe depuis le journal d'appel.</div>
          </div>
        </div>
        <div class="vcd-section-body" id="audioBody"></div>
      </div>

      <div class="vcd-section">
        <div class="vcd-section-head">
          <div>
            <div class="vcd-section-title">Transcription</div>
            <div class="vcd-section-desc">Texte complet capture pendant ou apres l'appel.</div>
          </div>
        </div>
        <div class="vcd-section-body" id="transcriptBody"></div>
      </div>

      <div class="vcd-section">
        <div class="vcd-section-head">
          <div>
            <div class="vcd-section-title">Synthese post-appel</div>
            <div class="vcd-section-desc">Resume IA, actions et suggestions de ticket.</div>
          </div>
          <button class="vcd-btn vcd-btn-primary" id="summaryBtn" type="button" onclick="generateSummary()">
            <span class="material-symbols-rounded" style="font-size:16px;">auto_awesome</span>
            Generer
          </button>
        </div>
        <div class="vcd-section-body" id="summaryBody">
          <div class="vcd-empty">Generez une synthese pour pre-remplir le ticket.</div>
        </div>
      </div>

      <div class="vcd-section">
        <div class="vcd-section-head">
          <div>
            <div class="vcd-section-title">Ticket lie et escalation</div>
            <div class="vcd-section-desc">Liez un ticket existant ou creez-en un depuis l'appel.</div>
          </div>
        </div>
        <div class="vcd-section-body">
          <div class="vcd-form-grid">
            <label>
              <div class="vcd-label">ID ticket existant</div>
              <input class="vcd-input" id="existingTicketId" placeholder="UUID du ticket">
            </label>
            <button class="vcd-btn" id="linkBtn" type="button" onclick="linkExistingTicket()">
              <span class="material-symbols-rounded" style="font-size:16px;">link</span>
              Lier
            </button>
          </div>

          <div class="vcd-divider">ou creer un nouveau ticket</div>

          <div class="vcd-create-grid">
            <label>
              <div class="vcd-label">Sujet</div>
              <input class="vcd-input" id="ticketSubject" placeholder="Sujet du ticket">
            </label>
            <label>
              <div class="vcd-label">Description</div>
              <textarea class="vcd-textarea" id="ticketDescription" placeholder="Description du probleme et contexte de l'appel"></textarea>
            </label>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <button class="vcd-btn vcd-btn-primary" id="createTicketBtn" type="button" onclick="createLinkedTicket()">
                <span class="material-symbols-rounded" style="font-size:16px;">add_task</span>
                Creer et lier
              </button>
              <button class="vcd-btn vcd-btn-danger" id="escalateBtn" type="button" onclick="escalateCall()">
                <span class="material-symbols-rounded" style="font-size:16px;">flag</span>
                Escalader
              </button>
            </div>
          </div>

          <div id="linkFeedback" style="display:none;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const CALL_ID = <?php echo json_encode($callId, 15, 512) ?>;
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const apiUrl = (path) => window.supportApiUrl ? window.supportApiUrl(path) : '/api/v1/' + String(path || '').replace(/^\//, '');
let currentCall = null;
let currentSummary = null;

function escHtml(value){
  return String(value ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtDuration(secs){
  if(!secs) return '-';
  const m = Math.floor(secs / 60);
  const s = Math.round(secs % 60);
  return m > 0 ? `${m}m ${s}s` : `${s}s`;
}
function setButtonLoading(btn, isLoading, label){
  if(!btn) return;
  btn.disabled = isLoading;
  if(isLoading){
    btn.dataset.originalHtml = btn.dataset.originalHtml || btn.innerHTML;
    btn.innerHTML = `<span class="material-symbols-rounded vcd-spinner" style="font-size:16px;">progress_activity</span>${label}`;
  }else if(btn.dataset.originalHtml){
    btn.innerHTML = btn.dataset.originalHtml;
  }
}
async function requestJson(path, options = {}){
  const headers = Object.assign({'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, options.headers || {});
  const res = await fetch(apiUrl(path), Object.assign({}, options, {headers}));
  if(!res.ok){
    const data = await res.json().catch(() => ({}));
    const detail = data.detail || data.message || data.error || ('HTTP ' + res.status);
    throw new Error(Array.isArray(detail) ? detail.join(', ') : String(detail));
  }
  if(res.status === 204) return null;
  return res.json();
}

async function loadCallDetail(){
  const btn = document.getElementById('refreshBtn');
  setButtonLoading(btn, true, 'Chargement');
  try{
    const call = await requestJson(`/voice-calls/${CALL_ID}`);
    currentCall = call;
    renderCall(call);
    document.getElementById('loadingBox').style.display = 'none';
    document.getElementById('detailBox').style.display = 'block';
  }catch(error){
    document.getElementById('loadingBox').innerHTML = `<div class="vcd-alert vcd-alert-bad"><span class="material-symbols-rounded">error</span><span>${escHtml(error.message)}</span></div>`;
  }finally{
    setButtonLoading(btn, false);
  }
}

function renderCall(call){
  document.getElementById('callTitle').textContent = call.room_name || '-';
  const start = call.started_at ? new Date(call.started_at).toLocaleString('fr-FR') : '-';
  const end = call.ended_at ? ' - ' + new Date(call.ended_at).toLocaleString('fr-FR') : '';
  document.getElementById('callSub').textContent = start + end;
  document.getElementById('durationValue').textContent = fmtDuration(call.duration_seconds);
  document.getElementById('roomSidValue').textContent = call.room_sid || '-';
  document.getElementById('recordingValue').textContent = call.audio_file_path ? 'Disponible' : 'Aucun';
  document.getElementById('transcriptBody').innerHTML = call.transcript
    ? `<div class="vcd-transcript">${escHtml(call.transcript)}</div>`
    : `<div class="vcd-empty">Aucune transcription disponible.</div>`;
  if(call.audio_file_path){
    document.getElementById('audioSection').style.display = 'block';
    loadAudio(call.id);
  }else{
    document.getElementById('audioSection').style.display = 'none';
  }
}

async function loadAudio(id){
  const body = document.getElementById('audioBody');
  body.innerHTML = '<div class="vcd-empty">Chargement de l audio...</div>';
  try{
    const res = await fetch(apiUrl(`/voice-calls/${id}/audio`), {headers:{'X-CSRF-TOKEN':CSRF}});
    if(!res.ok) throw new Error('HTTP ' + res.status);
    const url = URL.createObjectURL(await res.blob());
    body.innerHTML = `<audio controls preload="metadata" src="${url}" style="width:100%;border-radius:12px;accent-color:var(--color-primary,#1a56db);"></audio>`;
  }catch(error){
    body.innerHTML = `<div class="vcd-alert vcd-alert-bad"><span class="material-symbols-rounded">error</span><span>Audio indisponible: ${escHtml(error.message)}</span></div>`;
  }
}

async function generateSummary(){
  if(!currentCall) return;
  const btn = document.getElementById('summaryBtn');
  setButtonLoading(btn, true, 'Generation');
  document.getElementById('summaryBody').innerHTML = '<div class="vcd-empty">Analyse de la transcription...</div>';
  try{
    const summary = await requestJson(`/voice-calls/${currentCall.id}/post-call-summary`, {method:'POST', body:JSON.stringify({})});
    currentSummary = summary;
    if(!document.getElementById('ticketSubject').value && summary.ticket_subject_suggestion){
      document.getElementById('ticketSubject').value = summary.ticket_subject_suggestion;
    }
    if(!document.getElementById('ticketDescription').value && summary.ticket_description_suggestion){
      document.getElementById('ticketDescription').value = summary.ticket_description_suggestion;
    }
    renderSummary(summary);
  }catch(error){
    document.getElementById('summaryBody').innerHTML = `<div class="vcd-alert vcd-alert-bad"><span class="material-symbols-rounded">error</span><span>${escHtml(error.message)}</span></div>`;
  }finally{
    setButtonLoading(btn, false);
    btn.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">auto_awesome</span>Regenerer';
  }
}

function renderSummary(summary){
  const items = Array.isArray(summary.action_items) ? summary.action_items : [];
  document.getElementById('summaryBody').innerHTML = `
    <div class="vcd-summary-grid">
      <div class="vcd-block"><div class="vcd-label">Probleme client</div><p>${escHtml(summary.customer_issue || '-')}</p></div>
      <div class="vcd-block"><div class="vcd-label">Resolution</div><p>${escHtml(summary.resolution_status || '-')}</p></div>
    </div>
    <div class="vcd-block" style="margin-bottom:12px;"><div class="vcd-label">Synthese</div><p>${escHtml(summary.summary || '-')}</p></div>
    <div class="vcd-block"><div class="vcd-label">Suivi recommande</div><p>${escHtml(summary.follow_up_recommendation || '-')}</p></div>
    ${items.length ? `<div class="vcd-actions">${items.map(item => `
      <div class="vcd-action">
        <span class="material-symbols-rounded" style="font-size:18px;color:var(--color-primary,#1a56db);">check_circle</span>
        <div><div style="font-size:13px;font-weight:800;color:var(--text-heading,#1e293b);">${escHtml(item.title || '-')}</div><div style="font-size:11px;color:var(--text-muted,#64748b);margin-top:3px;">${escHtml(item.owner || '-')} - ${escHtml(item.priority || '-')}</div></div>
      </div>`).join('')}</div>` : ''}
  `;
}

async function linkExistingTicket(){
  if(!currentCall) return;
  const ticketId = document.getElementById('existingTicketId').value.trim();
  if(!ticketId){ showFeedback('bad', 'Saisissez un ID de ticket.'); return; }
  const btn = document.getElementById('linkBtn');
  setButtonLoading(btn, true, 'Liaison');
  try{
    const data = await requestJson(`/voice-calls/${currentCall.id}/link-ticket`, {method:'POST', body:JSON.stringify({ticket_id:ticketId})});
    showFeedback('ok', `Ticket ${data.ticket_id} lie avec succes.`);
  }catch(error){ showFeedback('bad', error.message); }
  finally{ setButtonLoading(btn, false); }
}

async function createLinkedTicket(){
  if(!currentCall) return null;
  const subject = document.getElementById('ticketSubject').value.trim() || currentSummary?.ticket_subject_suggestion || '';
  const description = document.getElementById('ticketDescription').value.trim() || currentSummary?.ticket_description_suggestion || '';
  if(!subject || !description){ showFeedback('bad', 'Renseignez un sujet et une description.'); return null; }
  const btn = document.getElementById('createTicketBtn');
  setButtonLoading(btn, true, 'Creation');
  try{
    const data = await requestJson(`/voice-calls/${currentCall.id}/link-ticket`, {method:'POST', body:JSON.stringify({subject, description})});
    document.getElementById('existingTicketId').value = data.ticket_id || '';
    showFeedback('ok', `Ticket ${data.ticket_id} ${data.link_type === 'created' ? 'cree et lie' : 'lie'} avec succes.`);
    return data.ticket_id;
  }catch(error){ showFeedback('bad', error.message); return null; }
  finally{ setButtonLoading(btn, false); }
}

async function escalateCall(){
  const btn = document.getElementById('escalateBtn');
  setButtonLoading(btn, true, 'Escalade');
  try{
    let ticketId = document.getElementById('existingTicketId').value.trim();
    if(!ticketId){
      ticketId = await createLinkedTicket();
    }
    if(!ticketId) return;
    await requestJson(`/tickets/${ticketId}/status`, {method:'POST', body:JSON.stringify({status:'escalated'})});
    showFeedback('ok', `Ticket ${ticketId} escalade vers l'administration.`);
  }catch(error){ showFeedback('bad', error.message); }
  finally{ setButtonLoading(btn, false); }
}

function showFeedback(type, message){
  const el = document.getElementById('linkFeedback');
  el.style.display = 'flex';
  el.className = 'vcd-alert ' + (type === 'ok' ? 'vcd-alert-ok' : 'vcd-alert-bad');
  el.innerHTML = `<span class="material-symbols-rounded">${type === 'ok' ? 'check_circle' : 'error'}</span><span>${escHtml(message)}</span>`;
}

document.addEventListener('DOMContentLoaded', loadCallDetail);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/voice-call-detail.blade.php ENDPATH**/ ?>