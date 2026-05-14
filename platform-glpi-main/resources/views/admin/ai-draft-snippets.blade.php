@extends('layouts.dashboard')
@section('title', 'Snippets brouillons IA - L2T Support')

@section('content')
<style>
.ads-page{min-height:calc(100vh - 120px);padding:22px 24px 34px;font-family:'DM Sans',system-ui,sans-serif;color:var(--text-main,#334155);}
.ads-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap;}
.ads-title{font-size:22px;font-weight:850;color:var(--text-heading,#1e293b);line-height:1.15;margin:0;}
.ads-sub{font-size:13px;color:var(--text-muted,#64748b);margin-top:5px;}
.ads-actions{display:flex;gap:10px;flex-wrap:wrap;}
.ads-btn{display:inline-flex;align-items:center;gap:7px;border:1.5px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);color:var(--text-main,#334155);border-radius:10px;padding:9px 13px;font-size:12px;font-weight:800;cursor:pointer;font-family:inherit;}
.ads-btn-primary{border:none;color:#fff;background:linear-gradient(135deg,var(--color-primary,#1a56db),var(--color-secondary,#764ba2));}
.ads-btn-danger{background:#ef4444;border-color:#ef4444;color:#fff;}
.ads-btn:disabled{opacity:.55;cursor:not-allowed;}
.ads-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:16px;}
.ads-stat,.ads-panel{background:var(--bg-card,#fff);border:1.5px solid var(--border-color,#e2e8f0);border-radius:16px;box-shadow:var(--card-shadow,0 1px 4px rgba(0,0,0,.04));}
.ads-stat{padding:14px 16px;}
.ads-label{font-size:10px;font-weight:850;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted,#64748b);margin-bottom:6px;}
.ads-stat-value{font-size:23px;font-weight:850;color:var(--text-heading,#1e293b);}
.ads-layout{display:grid;grid-template-columns:360px minmax(0,1fr);gap:16px;}
.ads-panel{overflow:hidden;}
.ads-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid var(--border-color,#e2e8f0);background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);}
.ads-panel-title{font-size:14px;font-weight:850;color:var(--text-heading,#1e293b);}
.ads-panel-sub{font-size:11px;color:var(--text-muted,#64748b);margin-top:3px;}
.ads-panel-body{padding:16px;}
.ads-input,.ads-select,.ads-textarea{width:100%;border:1.5px solid var(--border-color,#e2e8f0);border-radius:10px;background:var(--input-bg,#fff);color:var(--text-main,#334155);padding:10px 12px;font-size:13px;outline:none;font-family:inherit;}
.ads-textarea{min-height:220px;resize:vertical;line-height:1.55;}
.ads-input:focus,.ads-select:focus,.ads-textarea:focus{border-color:var(--color-primary,#1a56db);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary,#1a56db) 14%,transparent);}
.ads-filters{display:grid;gap:10px;margin-bottom:14px;}
.ads-tabs{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;}
.ads-tab{border:1.5px solid var(--border-color,#e2e8f0);background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);border-radius:10px;padding:9px 10px;text-align:left;font-size:11px;font-weight:850;color:var(--text-muted,#64748b);cursor:pointer;}
.ads-tab.active{border-color:var(--color-primary,#1a56db);color:var(--text-heading,#1e293b);background:color-mix(in srgb,var(--color-primary,#1a56db) 8%,transparent);}
.ads-list{display:grid;gap:9px;max-height:640px;overflow:auto;}
.ads-item{border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);padding:12px;text-align:left;cursor:pointer;color:inherit;}
.ads-item.active{border-color:var(--color-primary,#1a56db);background:color-mix(in srgb,var(--color-primary,#1a56db) 8%,transparent);}
.ads-item-top{display:flex;justify-content:space-between;gap:10px;}
.ads-item-title{font-size:13px;font-weight:850;color:var(--text-heading,#1e293b);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ads-item-desc{font-size:12px;color:var(--text-muted,#64748b);line-height:1.45;margin-top:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.ads-badges{display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;}
.ads-badge{font-size:10px;font-weight:850;border-radius:999px;border:1px solid var(--border-color,#e2e8f0);padding:3px 7px;color:var(--text-muted,#64748b);background:var(--bg-card,#fff);}
.ads-badge-active{border-color:#bbf7d0;background:#f0fdf4;color:#15803d;}
.ads-form{display:grid;gap:14px;}
.ads-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.ads-switch{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1.5px solid var(--border-color,#e2e8f0);border-radius:12px;background:color-mix(in srgb,var(--bg-body,#f8fafc) 70%,transparent);padding:12px;}
.ads-switch input{width:18px;height:18px;}
.ads-alert{display:flex;gap:8px;border-radius:12px;padding:10px 12px;font-size:13px;margin-bottom:14px;}
.ads-alert-ok{border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d;}
.ads-alert-bad{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;}
.ads-empty{padding:30px;text-align:center;color:var(--text-muted,#64748b);font-size:13px;}
.ads-spinner{animation:ads-spin .8s linear infinite;}
@keyframes ads-spin{to{transform:rotate(360deg)}}
[data-bs-theme="dark"] .ads-stat,[data-bs-theme="dark"] .ads-panel,[data-bs-theme="dark"] .ads-btn,[data-bs-theme="dark"] .ads-badge{background:#1e293b;border-color:#334155;color:#cbd5e1;}
[data-bs-theme="dark"] .ads-panel-head{background:rgba(255,255,255,.02);border-color:#334155;}
[data-bs-theme="dark"] .ads-item,[data-bs-theme="dark"] .ads-tab,[data-bs-theme="dark"] .ads-switch{background:rgba(255,255,255,.025);border-color:#334155;}
@media(max-width:1100px){.ads-stats{grid-template-columns:repeat(2,1fr)}.ads-layout{grid-template-columns:1fr}.ads-list{max-height:none}}
@media(max-width:650px){.ads-page{padding:16px}.ads-stats,.ads-form-row{grid-template-columns:1fr}.ads-actions{width:100%}.ads-btn{justify-content:center;flex:1}}
</style>

<div class="ads-page">
  <div class="ads-head">
    <div>
      <h1 class="ads-title">Snippets brouillons IA</h1>
      <div class="ads-sub">Gerez les snippets partages utilises dans Chat, WhatsApp et Email.</div>
    </div>
    <div class="ads-actions">
      <button class="ads-btn" id="refreshBtn" onclick="loadSnippets()">
        <span class="material-symbols-rounded" style="font-size:16px;">refresh</span>
        Rafraichir
      </button>
      <button class="ads-btn ads-btn-primary" onclick="startCreateSnippet()">
        <span class="material-symbols-rounded" style="font-size:16px;">add</span>
        Nouveau snippet
      </button>
    </div>
  </div>

  <div id="alertBox" style="display:none;"></div>

  <div class="ads-stats">
    <div class="ads-stat"><div class="ads-label">Dans le filtre</div><div class="ads-stat-value" id="statTotal">0</div></div>
    <div class="ads-stat"><div class="ads-label">Actifs</div><div class="ads-stat-value" id="statActive">0</div></div>
    <div class="ads-stat"><div class="ads-label">Inactifs</div><div class="ads-stat-value" id="statInactive">0</div></div>
    <div class="ads-stat"><div class="ads-label">Chat / WhatsApp</div><div class="ads-stat-value" id="statChatWa">0</div></div>
    <div class="ads-stat"><div class="ads-label">Email</div><div class="ads-stat-value" id="statEmail">0</div></div>
  </div>

  <div class="ads-layout">
    <div class="ads-panel">
      <div class="ads-panel-head">
        <div>
          <div class="ads-panel-title">Bibliotheque</div>
          <div class="ads-panel-sub" id="scopeText">Tous les canaux</div>
        </div>
        <span class="ads-badge" id="shownBadge">0 affiches</span>
      </div>
      <div class="ads-panel-body">
        <div class="ads-filters">
          <input class="ads-input" id="searchInput" placeholder="Rechercher" oninput="renderSnippets()">
          <div class="ads-tabs" id="channelTabs"></div>
        </div>
        <div class="ads-list" id="snippetList">
          <div class="ads-empty">Chargement...</div>
        </div>
      </div>
    </div>

    <div class="ads-panel">
      <div class="ads-panel-head">
        <div>
          <div class="ads-panel-title" id="editorTitle">Editeur</div>
          <div class="ads-panel-sub" id="editorSub">Selectionnez un snippet ou creez-en un nouveau.</div>
        </div>
        <div class="ads-actions">
          <button class="ads-btn ads-btn-danger" id="deleteBtn" onclick="deleteSnippet()" disabled>
            <span class="material-symbols-rounded" style="font-size:16px;">delete</span>
            Supprimer
          </button>
          <button class="ads-btn" id="resetBtn" onclick="resetForm()" disabled>Reinitialiser</button>
          <button class="ads-btn ads-btn-primary" id="saveBtn" onclick="saveSnippet()" disabled>
            <span class="material-symbols-rounded" style="font-size:16px;">save</span>
            Enregistrer
          </button>
        </div>
      </div>
      <div class="ads-panel-body">
        <div class="ads-form" id="editorForm">
          <div class="ads-form-row">
            <label>
              <div class="ads-label">Titre</div>
              <input class="ads-input" id="snippetTitle" placeholder="Investigation en cours">
            </label>
            <label>
              <div class="ads-label">Raccourci</div>
              <input class="ads-input" id="snippetShortcut" placeholder="investigation">
            </label>
          </div>
          <div class="ads-form-row">
            <label>
              <div class="ads-label">Canal</div>
              <select class="ads-select" id="snippetChannel">
                <option value="CHAT">Chat</option>
                <option value="WHATSAPP">WhatsApp</option>
                <option value="EMAIL">Email</option>
              </select>
            </label>
            <label class="ads-switch">
              <span>
                <span style="display:block;font-size:13px;font-weight:850;color:var(--text-heading,#1e293b);">Actif pour les operateurs</span>
                <span style="display:block;font-size:11px;color:var(--text-muted,#64748b);margin-top:2px;">Masquez-le sans le supprimer.</span>
              </span>
              <input type="checkbox" id="snippetActive" checked>
            </label>
          </div>
          <label>
            <div class="ads-label">Description</div>
            <input class="ads-input" id="snippetDescription" placeholder="Note courte pour savoir quand l'utiliser">
          </label>
          <label>
            <div class="ads-label">Corps</div>
            <textarea class="ads-textarea" id="snippetBody" placeholder="Bonjour @{{customer_name}}, nous analysons votre demande et revenons vers vous rapidement."></textarea>
          </label>
          <div class="ads-switch">
            <span style="font-size:12px;color:var(--text-muted,#64748b);line-height:1.45;">
              Variables utiles: <code>@{{customer_name}}</code>, <code>@{{conversation_id}}</code>, <code>@{{latest_customer_message}}</code>, <code>@{{current_draft}}</code>.
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const apiUrl = (path) => window.supportApiUrl ? window.supportApiUrl(path) : '/api/v1/' + String(path || '').replace(/^\//, '');
const CHANNELS = [
  {value:'ALL', label:'Tous', desc:'Tous les snippets configures.'},
  {value:'CHAT', label:'Chat', desc:'Snippets pour conversations chat.'},
  {value:'WHATSAPP', label:'WhatsApp', desc:'Snippets pour reponses WhatsApp.'},
  {value:'EMAIL', label:'Email', desc:'Modeles de brouillons email.'},
];
let snippets = [];
let filtered = [];
let selectedId = null;
let mode = 'idle';
let channelFilter = 'ALL';

function escHtml(value){
  return String(value ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function normalizeChannel(value){
  value = String(value || 'CHAT').toUpperCase();
  return ['CHAT','WHATSAPP','EMAIL'].includes(value) ? value : 'CHAT';
}
function channelLabel(value){
  value = normalizeChannel(value);
  if(value === 'WHATSAPP') return 'WhatsApp';
  if(value === 'EMAIL') return 'Email';
  return 'Chat';
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
function showAlert(type, message){
  const box = document.getElementById('alertBox');
  box.style.display = 'flex';
  box.className = 'ads-alert ' + (type === 'ok' ? 'ads-alert-ok' : 'ads-alert-bad');
  box.innerHTML = `<span class="material-symbols-rounded">${type === 'ok' ? 'check_circle' : 'error'}</span><span>${escHtml(message)}</span>`;
  window.clearTimeout(showAlert.timer);
  showAlert.timer = window.setTimeout(() => { box.style.display = 'none'; }, 4200);
}
function setLoading(btn, loading, label){
  if(!btn) return;
  btn.disabled = loading;
  if(loading){
    btn.dataset.originalHtml = btn.dataset.originalHtml || btn.innerHTML;
    btn.innerHTML = `<span class="material-symbols-rounded ads-spinner" style="font-size:16px;">progress_activity</span>${label}`;
  }else if(btn.dataset.originalHtml){
    btn.innerHTML = btn.dataset.originalHtml;
  }
}
function renderTabs(){
  document.getElementById('channelTabs').innerHTML = CHANNELS.map(c => `
    <button class="ads-tab ${channelFilter === c.value ? 'active' : ''}" onclick="setChannel('${c.value}')">${c.label}</button>
  `).join('');
  document.getElementById('scopeText').textContent = CHANNELS.find(c => c.value === channelFilter)?.desc || '';
}
function setChannel(value){
  channelFilter = value;
  renderTabs();
  renderSnippets();
}
async function loadSnippets(){
  const btn = document.getElementById('refreshBtn');
  setLoading(btn, true, 'Chargement');
  try{
    const data = await requestJson('/conversations/automation/snippets?include_inactive=true');
    snippets = Array.isArray(data.snippets) ? data.snippets.map(s => ({
      id:String(s.id || ''),
      title:String(s.title || ''),
      body:String(s.body || ''),
      description:s.description || '',
      shortcut:s.shortcut || '',
      channel:normalizeChannel(s.channel),
      is_active:Boolean(s.is_active),
      created_at:s.created_at || '',
      updated_at:s.updated_at || '',
    })).sort((a,b) => {
      if(a.is_active !== b.is_active) return a.is_active ? -1 : 1;
      return String(b.updated_at).localeCompare(String(a.updated_at));
    }) : [];
    if(!selectedId && snippets.length) selectedId = snippets[0].id;
    renderSnippets();
    if(selectedId) selectSnippet(selectedId, false);
  }catch(error){
    document.getElementById('snippetList').innerHTML = `<div class="ads-alert ads-alert-bad"><span class="material-symbols-rounded">error</span><span>${escHtml(error.message)}</span></div>`;
  }finally{
    setLoading(btn, false);
  }
}
function renderSnippets(){
  const query = document.getElementById('searchInput').value.trim().toLowerCase();
  filtered = snippets.filter(s => {
    if(channelFilter !== 'ALL' && s.channel !== channelFilter) return false;
    if(!query) return true;
    return `${s.title} ${s.description} ${s.shortcut} ${s.body}`.toLowerCase().includes(query);
  });
  updateStats();
  document.getElementById('shownBadge').textContent = `${filtered.length} affiches`;
  const list = document.getElementById('snippetList');
  if(!filtered.length){
    list.innerHTML = '<div class="ads-empty">Aucun snippet trouve.</div>';
    return;
  }
  list.innerHTML = filtered.map(s => `
    <button class="ads-item ${selectedId === s.id ? 'active' : ''}" onclick="selectSnippet('${s.id}')">
      <div class="ads-item-top">
        <div style="min-width:0;">
          <div class="ads-item-title">${escHtml(s.title)}</div>
          <div class="ads-item-desc">${escHtml(s.description || s.body)}</div>
        </div>
        <div class="ads-badges">
          <span class="ads-badge ${s.is_active ? 'ads-badge-active' : ''}">${s.is_active ? 'Actif' : 'Inactif'}</span>
          <span class="ads-badge">${channelLabel(s.channel)}</span>
        </div>
      </div>
      <div style="margin-top:10px;font-size:11px;color:var(--text-muted,#64748b);display:flex;justify-content:space-between;gap:8px;">
        <span>${escHtml(s.shortcut || 'Sans raccourci')}</span>
        <span>${s.updated_at ? new Date(s.updated_at).toLocaleDateString('fr-FR') : ''}</span>
      </div>
    </button>
  `).join('');
}
function updateStats(){
  const scoped = channelFilter === 'ALL' ? snippets : snippets.filter(s => s.channel === channelFilter);
  document.getElementById('statTotal').textContent = scoped.length;
  document.getElementById('statActive').textContent = scoped.filter(s => s.is_active).length;
  document.getElementById('statInactive').textContent = scoped.filter(s => !s.is_active).length;
  document.getElementById('statChatWa').textContent = snippets.filter(s => s.channel === 'CHAT' || s.channel === 'WHATSAPP').length;
  document.getElementById('statEmail').textContent = snippets.filter(s => s.channel === 'EMAIL').length;
}
function formPayload(){
  return {
    title:document.getElementById('snippetTitle').value.trim(),
    shortcut:document.getElementById('snippetShortcut').value.trim() || null,
    channel:normalizeChannel(document.getElementById('snippetChannel').value),
    is_active:document.getElementById('snippetActive').checked,
    description:document.getElementById('snippetDescription').value.trim() || null,
    body:document.getElementById('snippetBody').value.trim(),
  };
}
function fillForm(snippet){
  document.getElementById('snippetTitle').value = snippet?.title || '';
  document.getElementById('snippetShortcut').value = snippet?.shortcut || '';
  document.getElementById('snippetChannel').value = normalizeChannel(snippet?.channel || (channelFilter === 'ALL' ? 'CHAT' : channelFilter));
  document.getElementById('snippetActive').checked = snippet?.is_active !== false;
  document.getElementById('snippetDescription').value = snippet?.description || '';
  document.getElementById('snippetBody').value = snippet?.body || '';
}
function selectSnippet(id, rerender = true){
  const snippet = snippets.find(s => s.id === id);
  if(!snippet) return;
  selectedId = id;
  mode = 'edit';
  fillForm(snippet);
  document.getElementById('editorTitle').textContent = 'Editeur';
  document.getElementById('editorSub').textContent = `Modification de ${snippet.title}`;
  document.getElementById('saveBtn').disabled = false;
  document.getElementById('resetBtn').disabled = false;
  document.getElementById('deleteBtn').disabled = false;
  if(rerender) renderSnippets();
}
function startCreateSnippet(){
  mode = 'create';
  selectedId = null;
  fillForm({channel:channelFilter === 'ALL' ? 'CHAT' : channelFilter, is_active:true});
  document.getElementById('editorTitle').textContent = 'Nouveau snippet';
  document.getElementById('editorSub').textContent = 'Creez un snippet partage pour les operateurs.';
  document.getElementById('saveBtn').disabled = false;
  document.getElementById('resetBtn').disabled = false;
  document.getElementById('deleteBtn').disabled = true;
  renderSnippets();
}
function validatePayload(payload){
  if(!payload.title){ showAlert('bad', 'Le titre est obligatoire.'); return false; }
  if(!payload.body){ showAlert('bad', 'Le corps du snippet est obligatoire.'); return false; }
  return true;
}
async function saveSnippet(){
  const payload = formPayload();
  if(!validatePayload(payload)) return;
  const btn = document.getElementById('saveBtn');
  setLoading(btn, true, 'Enregistrement');
  try{
    let saved;
    if(mode === 'create' || !selectedId){
      saved = await requestJson('/conversations/automation/snippets', {method:'POST', body:JSON.stringify(payload)});
      showAlert('ok', 'Snippet cree.');
    }else{
      saved = await requestJson(`/conversations/automation/snippets/${selectedId}`, {method:'PATCH', body:JSON.stringify(payload)});
      showAlert('ok', 'Snippet mis a jour.');
    }
    selectedId = saved.id;
    mode = 'edit';
    await loadSnippets();
  }catch(error){
    showAlert('bad', error.message);
  }finally{
    setLoading(btn, false);
  }
}
function resetForm(){
  if(mode === 'create'){
    fillForm({channel:channelFilter === 'ALL' ? 'CHAT' : channelFilter, is_active:true});
    return;
  }
  const snippet = snippets.find(s => s.id === selectedId);
  if(snippet) fillForm(snippet);
}
async function deleteSnippet(){
  const snippet = snippets.find(s => s.id === selectedId);
  if(!snippet) return;
  if(!window.confirm(`Supprimer "${snippet.title}" ? Le snippet sera desactive.`)) return;
  const btn = document.getElementById('deleteBtn');
  setLoading(btn, true, 'Suppression');
  try{
    await requestJson(`/conversations/automation/snippets/${snippet.id}`, {method:'DELETE'});
    selectedId = null;
    showAlert('ok', 'Snippet desactive.');
    await loadSnippets();
  }catch(error){
    showAlert('bad', error.message);
  }finally{
    setLoading(btn, false);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderTabs();
  loadSnippets();
});
</script>
@endsection
