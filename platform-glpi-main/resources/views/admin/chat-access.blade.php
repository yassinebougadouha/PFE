@extends('layouts.dashboard')
@section('title', 'Acces chat - L2T Support')

@section('content')
@php
  $selectedUserId = optional($selectedUser ?? null)->id;
@endphp

<style>
.ca-wrap{padding:22px;max-width:1200px;margin:0 auto;}
.ca-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
.ca-title{font-size:22px;font-weight:800;color:var(--text-heading,#1e293b);}
.ca-sub{font-size:13px;color:var(--text-muted,#64748b);margin-top:3px;}
.ca-btn{border:1px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;cursor:pointer;}
.ca-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:14px;}
.ca-stat{border:1px solid var(--border-color,#e2e8f0);border-radius:12px;background:var(--bg-card,#fff);padding:12px;}
.ca-stat-l{font-size:11px;color:var(--text-muted,#64748b);text-transform:uppercase;letter-spacing:.04em;}
.ca-stat-v{font-size:22px;font-weight:800;color:var(--text-heading,#1e293b);margin-top:5px;}
.ca-card{border:1px solid var(--border-color,#e2e8f0);border-radius:14px;background:var(--bg-card,#fff);overflow:hidden;}
.ca-tools{display:flex;gap:10px;align-items:center;padding:12px;border-bottom:1px solid var(--border-color,#e2e8f0);flex-wrap:wrap;}
.ca-input{height:38px;border:1px solid var(--border-color,#e2e8f0);border-radius:10px;padding:0 12px;min-width:280px;flex:1;font-size:13px;}
.ca-seg{display:inline-flex;border:1px solid var(--border-color,#e2e8f0);border-radius:10px;padding:2px;background:var(--bg-body,#f8fafc);}
.ca-seg button{border:0;background:transparent;padding:6px 10px;font-size:12px;font-weight:700;border-radius:8px;cursor:pointer;color:var(--text-muted,#64748b);}
.ca-seg button.on{background:var(--color-primary,#2563eb);color:#fff;}
.ca-table-wrap{overflow:auto;}
.ca-table{width:100%;border-collapse:collapse;min-width:980px;}
.ca-table th,.ca-table td{padding:11px 12px;border-bottom:1px solid var(--border-color,#e2e8f0);font-size:13px;vertical-align:middle;}
.ca-table th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted,#64748b);text-align:left;}
.ca-user-name{font-weight:700;color:var(--text-heading,#1e293b);}
.ca-user-mail{font-size:12px;color:var(--text-muted,#64748b);margin-top:2px;}
.ca-badge{display:inline-flex;padding:3px 8px;border-radius:999px;border:1px solid;font-size:11px;font-weight:700;}
.ca-badge.ok{color:#166534;background:#f0fdf4;border-color:#bbf7d0;}
.ca-badge.ro{color:#92400e;background:#fffbeb;border-color:#fde68a;}
.ca-mode{font-size:12px;font-weight:700;color:var(--text-muted,#64748b);}
.ca-switch{width:42px;height:24px;border-radius:999px;border:1px solid #cbd5e1;background:#e2e8f0;position:relative;cursor:pointer;display:inline-block;vertical-align:middle;}
.ca-switch::after{content:'';position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;background:#fff;transition:all .2s;box-shadow:0 1px 2px rgba(0,0,0,.2);}
.ca-switch.on{background:#16a34a;border-color:#16a34a;}
.ca-switch.on::after{left:20px;}
.ca-switch.disabled{opacity:.5;cursor:not-allowed;}
.ca-actions{display:flex;gap:8px;justify-content:flex-end;}
.ca-action{border:1px solid var(--border-color,#e2e8f0);background:var(--bg-card,#fff);border-radius:8px;padding:6px 10px;font-size:12px;font-weight:700;cursor:pointer;}
.ca-action.main{background:var(--color-primary,#2563eb);border-color:var(--color-primary,#2563eb);color:#fff;}
.ca-empty{padding:26px;text-align:center;color:var(--text-muted,#64748b);}
@media(max-width:960px){.ca-grid{grid-template-columns:1fr 1fr;}}
</style>

<div class="ca-wrap">
  <div class="ca-head">
    <div>
      <div class="ca-title">Acces Chat</div>
      <div class="ca-sub">Gerez les droits de reponse Conversations et WhatsApp comme dans la supervision React.</div>
    </div>
    <button class="ca-btn" id="refreshBtn" type="button">Rafraichir</button>
  </div>

  <div class="ca-grid">
    <div class="ca-stat"><div class="ca-stat-l">Operateurs</div><div class="ca-stat-v" id="sTotal">0</div></div>
    <div class="ca-stat"><div class="ca-stat-l">Acces complet</div><div class="ca-stat-v" id="sFull">0</div></div>
    <div class="ca-stat"><div class="ca-stat-l">Lecture seule</div><div class="ca-stat-v" id="sReadOnly">0</div></div>
    <div class="ca-stat"><div class="ca-stat-l">Chat uniquement</div><div class="ca-stat-v" id="sConvOnly">0</div></div>
    <div class="ca-stat"><div class="ca-stat-l">WhatsApp uniquement</div><div class="ca-stat-v" id="sWaOnly">0</div></div>
  </div>

  <div class="ca-card">
    <div class="ca-tools">
      <input id="searchInput" class="ca-input" placeholder="Rechercher par nom ou email...">
      <div class="ca-seg" id="roleSeg">
        <button type="button" data-role="all" class="on">Tous</button>
        <button type="button" data-role="SUPER_ADMIN">Super Admins</button>
        <button type="button" data-role="ADMIN">Admins</button>
      </div>
    </div>

    <div class="ca-table-wrap">
      <table class="ca-table">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Role</th>
            <th>Reply Conversations</th>
            <th>Reply WhatsApp</th>
            <th>Mode</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody id="userBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const CURRENT_USER_ID = @json((string) auth()->id());
const INITIAL_SELECTED_USER_ID = @json($selectedUserId ? (string) $selectedUserId : null);
const OPERATORS_INDEX_URL = @json(route('admin.chat-access.operators.index'));

let allUsers = [];
let roleFilter = 'all';
let searchText = '';

function esc(v){
  return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function requestJson(path, opts = {}){
  const headers = Object.assign({ 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF }, opts.headers || {});
  const res = await fetch(path, Object.assign({}, opts, { headers }));
  if(!res.ok){
    const data = await res.json().catch(() => ({}));
    throw new Error(data.detail || data.message || ('HTTP ' + res.status));
  }
  if (res.status === 204) return null;
  return res.json();
}

function isReadOnly(user){
  return !user.can_reply_conversations || !user.can_reply_whatsapp;
}

function updateStats(users){
  const total = users.length;
  const full = users.filter(u => u.can_reply_conversations && u.can_reply_whatsapp).length;
  const ro = users.filter(isReadOnly).length;
  const convOnly = users.filter(u => u.can_reply_conversations && !u.can_reply_whatsapp).length;
  const waOnly = users.filter(u => !u.can_reply_conversations && u.can_reply_whatsapp).length;
  document.getElementById('sTotal').textContent = String(total);
  document.getElementById('sFull').textContent = String(full);
  document.getElementById('sReadOnly').textContent = String(ro);
  document.getElementById('sConvOnly').textContent = String(convOnly);
  document.getElementById('sWaOnly').textContent = String(waOnly);
}

function filteredUsers(){
  const q = searchText.trim().toLowerCase();
  return allUsers
    .filter(u => roleFilter === 'all' ? true : u.role === roleFilter)
    .filter(u => !q || (`${u.full_name} ${u.email}`).toLowerCase().includes(q))
    .sort((a,b) => String(a.full_name || '').localeCompare(String(b.full_name || '')));
}

function roleLabel(role){
  if (role === 'ADMIN') return 'Admin';
  if (role === 'SUPER_ADMIN') return 'Super Admin';
  return role || '-';
}

function switchHtml(isOn, userId, field, disabled){
  const cls = `ca-switch ${isOn ? 'on' : ''} ${disabled ? 'disabled' : ''}`;
  return `<span class="${cls}" data-user="${esc(userId)}" data-field="${esc(field)}" data-on="${isOn ? '1' : '0'}"></span>`;
}

function renderUsers(){
  const users = filteredUsers();
  const body = document.getElementById('userBody');
  if(!users.length){
    body.innerHTML = `<tr><td colspan="6"><div class="ca-empty">Aucun operateur trouve.</div></td></tr>`;
    return;
  }

  const highlighted = INITIAL_SELECTED_USER_ID ? users.find(u => String(u.id) === String(INITIAL_SELECTED_USER_ID)) : null;
  const sorted = highlighted ? [highlighted, ...users.filter(u => u.id !== highlighted.id)] : users;

  body.innerHTML = sorted.map(u => {
    const self = String(u.id) === CURRENT_USER_ID;
    const mode = isReadOnly(u)
      ? `<span class="ca-badge ro">Lecture seule</span>`
      : `<span class="ca-badge ok">Acces complet</span>`;
    return `
      <tr>
        <td>
          <div class="ca-user-name">${esc(u.full_name || '-')}</div>
          <div class="ca-user-mail">${esc(u.email || '-')}</div>
        </td>
        <td>${esc(roleLabel(u.role))}</td>
        <td>${switchHtml(Boolean(u.can_reply_conversations), u.id, 'can_reply_conversations', self)} <span class="ca-mode">${u.can_reply_conversations ? 'Can reply' : 'Read only'}</span></td>
        <td>${switchHtml(Boolean(u.can_reply_whatsapp), u.id, 'can_reply_whatsapp', self)} <span class="ca-mode">${u.can_reply_whatsapp ? 'Can reply' : 'Read only'}</span></td>
        <td>${mode}</td>
        <td>
          <div class="ca-actions">
            <button class="ca-action" data-user-ro="${esc(u.id)}" data-readonly="1" ${self ? 'disabled' : ''}>Read only</button>
            <button class="ca-action main" data-user-ro="${esc(u.id)}" data-readonly="0" ${self ? 'disabled' : ''}>Acces complet</button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

async function loadUsers(){
  const btn = document.getElementById('refreshBtn');
  btn.disabled = true;
  try{
    const data = await requestJson(OPERATORS_INDEX_URL);
    allUsers = Array.isArray(data.users) ? data.users : [];
    updateStats(allUsers);
    renderUsers();
  }catch(err){
    document.getElementById('userBody').innerHTML = `<tr><td colspan="6"><div class="ca-empty">${esc(err.message || 'Chargement impossible')}</div></td></tr>`;
  }finally{
    btn.disabled = false;
  }
}

async function patchUser(userId, payload){
  await requestJson(`${OPERATORS_INDEX_URL}/${encodeURIComponent(userId)}`, {
    method:'PATCH',
    body: JSON.stringify(payload),
  });
  await loadUsers();
}

document.getElementById('refreshBtn').addEventListener('click', loadUsers);

document.getElementById('searchInput').addEventListener('input', (e) => {
  searchText = e.target.value || '';
  renderUsers();
});

document.getElementById('roleSeg').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-role]');
  if(!btn) return;
  roleFilter = btn.getAttribute('data-role') || 'all';
  document.querySelectorAll('#roleSeg button[data-role]').forEach(el => el.classList.remove('on'));
  btn.classList.add('on');
  renderUsers();
});

document.getElementById('userBody').addEventListener('click', async (e) => {
  const sw = e.target.closest('.ca-switch');
  if(sw && !sw.classList.contains('disabled')){
    const userId = sw.getAttribute('data-user');
    const field = sw.getAttribute('data-field');
    const isOn = sw.getAttribute('data-on') === '1';
    if(userId && field){
      try{
        await patchUser(userId, { [field]: !isOn });
      }catch(err){
        alert(err.message || 'Mise a jour impossible');
      }
    }
    return;
  }

  const roBtn = e.target.closest('button[data-user-ro]');
  if(roBtn){
    const userId = roBtn.getAttribute('data-user-ro');
    const readOnly = roBtn.getAttribute('data-readonly') === '1';
    if(userId){
      try{
        await patchUser(userId, {
          can_reply_conversations: !readOnly,
          can_reply_whatsapp: !readOnly,
        });
      }catch(err){
        alert(err.message || 'Mise a jour impossible');
      }
    }
  }
});

document.addEventListener('DOMContentLoaded', loadUsers);
</script>
@endsection
