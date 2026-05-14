@extends('layouts.dashboard')
@section('title', 'Conversations - L2T Support')

@section('content')
<style>
.cv-wrap{padding:20px;height:calc(100vh - 84px);display:flex;flex-direction:column;gap:12px;}
.cv-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.cv-title{font-size:22px;font-weight:800;color:var(--text-heading,#1e293b);}
.cv-sub{font-size:13px;color:var(--text-muted,#64748b);margin-top:3px;}
.cv-btn{border:1px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer;}
.cv-main{flex:1;min-height:0;display:grid;grid-template-columns:360px 1fr;gap:12px;}
.cv-panel{border:1px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);border-radius:14px;display:flex;flex-direction:column;min-height:0;overflow:hidden;}
.cv-tools{padding:10px;border-bottom:1px solid var(--border-color,#e2e8f0);display:flex;gap:8px;align-items:center;}
.cv-input{height:36px;border:1px solid var(--border-color,#e2e8f0);border-radius:9px;padding:0 10px;font-size:13px;}
.cv-input.full{width:100%;}
.cv-list{flex:1;overflow:auto;}
.cv-item{padding:11px 12px;border-bottom:1px solid var(--border-color,#e2e8f0);cursor:pointer;}
.cv-item:hover{background:var(--bg-body,#f8fafc);}
.cv-item.on{background:color-mix(in srgb,var(--color-primary,#2563eb) 10%,transparent);}
.cv-item-top{display:flex;justify-content:space-between;gap:8px;align-items:center;}
.cv-item-title{font-size:13px;font-weight:700;color:var(--text-heading,#1e293b);}
.cv-item-sub{font-size:11px;color:var(--text-muted,#64748b);margin-top:4px;}
.cv-item-badge{font-size:10px;border:1px solid #cbd5e1;border-radius:99px;padding:2px 7px;color:#475569;}
.cv-thread-head{padding:12px;border-bottom:1px solid var(--border-color,#e2e8f0);display:flex;justify-content:space-between;gap:8px;align-items:flex-start;}
.cv-thread-title{font-size:15px;font-weight:800;color:var(--text-heading,#1e293b);}
.cv-thread-meta{font-size:12px;color:var(--text-muted,#64748b);margin-top:3px;}
.cv-messages{flex:1;overflow:auto;padding:14px;display:flex;flex-direction:column;gap:9px;background:var(--bg-body,#f8fafc);}
.cv-msg{max-width:78%;padding:10px 12px;border-radius:12px;font-size:13px;line-height:1.6;border:1px solid;}
.cv-msg.user{align-self:flex-start;background:#fff;border-color:#e2e8f0;color:#1e293b;border-top-left-radius:3px;}
.cv-msg.agent{align-self:flex-end;background:color-mix(in srgb,var(--color-primary,#2563eb) 10%,#fff);border-color:color-mix(in srgb,var(--color-primary,#2563eb) 25%,transparent);color:#0f172a;border-top-right-radius:3px;}
.cv-msg-time{font-size:10px;color:#64748b;margin-top:5px;text-align:right;}
.cv-compose{padding:10px;border-top:1px solid var(--border-color,#e2e8f0);display:flex;gap:8px;}
.cv-compose textarea{flex:1;min-height:42px;max-height:120px;resize:vertical;border:1px solid var(--border-color,#e2e8f0);border-radius:10px;padding:9px 10px;font-size:13px;outline:none;}
.cv-send{border:0;background:var(--color-primary,#2563eb);color:#fff;border-radius:10px;padding:0 14px;font-size:12px;font-weight:700;cursor:pointer;}
.cv-empty{padding:22px;text-align:center;color:var(--text-muted,#64748b);}
@media(max-width:960px){.cv-main{grid-template-columns:1fr;}.cv-wrap{height:auto;min-height:calc(100vh - 84px);}}
</style>

<div class="cv-wrap">
  <div class="cv-head">
    <div>
      <div class="cv-title">Conversations Chat</div>
      <div class="cv-sub">Supervision des conversations via les memes endpoints que le projet React.</div>
    </div>
    <button type="button" id="refreshConversationsBtn" class="cv-btn">Rafraichir</button>
  </div>

  <div class="cv-main">
    <section class="cv-panel">
      <div class="cv-tools">
        <input id="conversationSearch" class="cv-input full" placeholder="Rechercher (titre, statut, user)">
      </div>
      <div class="cv-list" id="conversationList"></div>
    </section>

    <section class="cv-panel">
      <div class="cv-thread-head">
        <div>
          <div class="cv-thread-title" id="threadTitle">Selectionnez une conversation</div>
          <div class="cv-thread-meta" id="threadMeta">Aucun fil charge.</div>
        </div>
        <button type="button" id="refreshThreadBtn" class="cv-btn" disabled>Actualiser fil</button>
      </div>
      <div class="cv-messages" id="threadMessages">
        <div class="cv-empty">Choisissez une conversation depuis la liste.</div>
      </div>
      <div class="cv-compose">
        <textarea id="replyInput" placeholder="Repondre a cette conversation..."></textarea>
        <button type="button" class="cv-send" id="sendReplyBtn" disabled>Envoyer</button>
      </div>
    </section>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const apiUrl = (path) => window.supportApiUrl ? window.supportApiUrl(path) : '/api/v1/' + String(path || '').replace(/^\//, '');

let conversations = [];
let selectedConversationId = null;
let searchText = '';
let listPollTimer = null;

function esc(v){
  return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function requestJson(path, opts = {}){
  const headers = Object.assign({ 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF }, opts.headers || {});
  const res = await fetch(apiUrl(path), Object.assign({}, opts, { headers }));
  if(!res.ok){
    const data = await res.json().catch(() => ({}));
    throw new Error(data.detail || data.message || ('HTTP ' + res.status));
  }
  if (res.status === 204) return null;
  return res.json();
}

function conversationTitle(c){
  return c.subject || c.title || `Conversation ${c.id.slice(0,8)}`;
}

function filteredConversations(){
  const q = searchText.trim().toLowerCase();
  const sorted = [...conversations].sort((a,b) => new Date(b.updated_at || b.created_at || 0) - new Date(a.updated_at || a.created_at || 0));
  if(!q) return sorted;
  return sorted.filter(c => `${conversationTitle(c)} ${c.user_id} ${c.status} ${c.channel}`.toLowerCase().includes(q));
}

function setThreadHeader(c){
  document.getElementById('threadTitle').textContent = c ? conversationTitle(c) : 'Selectionnez une conversation';
  document.getElementById('threadMeta').textContent = c ? `ID ${c.id} | User ${c.user_id} | ${c.status || '-'} | ${c.channel || '-'}` : 'Aucun fil charge.';
  document.getElementById('refreshThreadBtn').disabled = !c;
  document.getElementById('sendReplyBtn').disabled = !c;
}

function renderConversationList(){
  const items = filteredConversations();
  const box = document.getElementById('conversationList');
  if(!items.length){
    box.innerHTML = '<div class="cv-empty">Aucune conversation chat.</div>';
    return;
  }

  box.innerHTML = items.map(c => {
    const active = c.id === selectedConversationId ? ' on' : '';
    const updated = c.updated_at ? new Date(c.updated_at).toLocaleString('fr-FR') : '-';
    return `
      <div class="cv-item${active}" data-conv-id="${esc(c.id)}">
        <div class="cv-item-top">
          <span class="cv-item-title">${esc(conversationTitle(c))}</span>
          <span class="cv-item-badge">${esc(c.status || '-')}</span>
        </div>
        <div class="cv-item-sub">${esc(c.user_id || '-')} | ${esc(c.channel || '-')} | ${esc(updated)}</div>
      </div>`;
  }).join('');
}

async function loadConversations(keepSelection = true){
  const btn = document.getElementById('refreshConversationsBtn');
  btn.disabled = true;
  try{
    const res = await requestJson('/conversations?channel=CHAT&include_total=false');
    conversations = Array.isArray(res.conversations) ? res.conversations : (Array.isArray(res) ? res : []);
    if(!keepSelection || !conversations.find(c => c.id === selectedConversationId)){
      selectedConversationId = conversations.length ? conversations[0].id : null;
    }
    renderConversationList();
    const selected = conversations.find(c => c.id === selectedConversationId) || null;
    setThreadHeader(selected);
    if(selected){
      await loadMessages(selected.id);
    }else{
      document.getElementById('threadMessages').innerHTML = '<div class="cv-empty">Aucune conversation disponible.</div>';
    }
  }catch(err){
    document.getElementById('conversationList').innerHTML = `<div class="cv-empty">${esc(err.message || 'Chargement impossible')}</div>`;
  }finally{
    btn.disabled = false;
  }
}

function mapMessageRole(msg){
  const role = String(msg.sender_type || msg.role || '').toLowerCase();
  return role === 'user' || role === 'client' ? 'user' : 'agent';
}

async function loadMessages(conversationId){
  const box = document.getElementById('threadMessages');
  box.innerHTML = '<div class="cv-empty">Chargement des messages...</div>';
  try{
    const messages = await requestJson(`/conversations/${encodeURIComponent(conversationId)}/messages`);
    if(!Array.isArray(messages) || !messages.length){
      box.innerHTML = '<div class="cv-empty">Cette conversation ne contient pas encore de messages.</div>';
      return;
    }
    const sorted = [...messages].sort((a,b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
    box.innerHTML = sorted.map(m => {
      const kind = mapMessageRole(m);
      const when = m.created_at ? new Date(m.created_at).toLocaleString('fr-FR') : '-';
      return `
        <div class="cv-msg ${kind}">
          <div>${esc(m.content || m.message || '')}</div>
          <div class="cv-msg-time">${esc(when)}</div>
        </div>`;
    }).join('');
    box.scrollTop = box.scrollHeight;
  }catch(err){
    box.innerHTML = `<div class="cv-empty">${esc(err.message || 'Impossible de charger les messages')}</div>`;
  }
}

async function sendReply(){
  const input = document.getElementById('replyInput');
  const text = (input.value || '').trim();
  if(!selectedConversationId || !text) return;
  const btn = document.getElementById('sendReplyBtn');
  btn.disabled = true;
  try{
    await requestJson(`/conversations/${encodeURIComponent(selectedConversationId)}/messages`, {
      method:'POST',
      body: JSON.stringify({ content: text }),
    });
    input.value = '';
    await loadMessages(selectedConversationId);
    await loadConversations(true);
  }catch(err){
    alert(err.message || 'Envoi impossible');
  }finally{
    btn.disabled = false;
  }
}

document.getElementById('conversationSearch').addEventListener('input', (e) => {
  searchText = e.target.value || '';
  renderConversationList();
});

document.getElementById('conversationList').addEventListener('click', async (e) => {
  const row = e.target.closest('[data-conv-id]');
  if(!row) return;
  selectedConversationId = row.getAttribute('data-conv-id');
  renderConversationList();
  const selected = conversations.find(c => c.id === selectedConversationId) || null;
  setThreadHeader(selected);
  if(selectedConversationId){
    await loadMessages(selectedConversationId);
  }
});

document.getElementById('refreshConversationsBtn').addEventListener('click', () => loadConversations(true));
document.getElementById('refreshThreadBtn').addEventListener('click', () => selectedConversationId && loadMessages(selectedConversationId));
document.getElementById('sendReplyBtn').addEventListener('click', sendReply);
document.getElementById('replyInput').addEventListener('keydown', (e) => {
  if(e.key === 'Enter' && (e.ctrlKey || e.metaKey)){
    e.preventDefault();
    sendReply();
  }
});

document.addEventListener('DOMContentLoaded', async () => {
  await loadConversations(false);
  listPollTimer = setInterval(() => loadConversations(true), 20000);
});

window.addEventListener('beforeunload', () => {
  if(listPollTimer){
    clearInterval(listPollTimer);
    listPollTimer = null;
  }
});
</script>
@endsection
