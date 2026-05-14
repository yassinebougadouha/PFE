@extends('layouts.dashboard')
@section('title', 'Supervision WhatsApp — Super Admin')

@section('content')
<style>
/* ── Reset & Full-page takeover ── */
.main-content { padding: 0 !important; overflow: hidden; }
* { box-sizing: border-box; margin: 0; padding: 0; }

@import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600&display=swap');

#wa-root {
  display: flex;
  height: calc(100vh - 64px);
  font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
  overflow: hidden;
  background: #111b21;
  position: relative;
}

/* ═══ COL 1: ADMINS LIST ═══ */
#wa-admins-col {
  width: 280px;
  min-width: 280px;
  display: flex;
  flex-direction: column;
  background: #111b21;
  border-right: 1px solid #222d34;
  flex-shrink: 0;
}

.wa-admins-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #202c33;
  flex-shrink: 0;
}
.wa-admins-title {
  font-size: 15px; font-weight: 600; color: #e9edef;
}
.wa-admins-list {
  flex: 1; overflow-y: auto;
}
.wa-admins-list::-webkit-scrollbar { width: 6px; }
.wa-admins-list::-webkit-scrollbar-thumb { background: #2a3942; border-radius: 3px; }

.wa-admin-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px; cursor: pointer;
  border-bottom: 1px solid rgba(134,150,160,.1);
  transition: background .12s;
}
.wa-admin-item:hover { background: #202c33; }
.wa-admin-item.active { background: #2a3942; border-left: 3px solid #00a884; padding-left: 11px; }

.wa-admin-av-col {
  width: 44px; height: 44px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 15px; color: #fff;
  flex-shrink: 0;
}
.wa-admin-info-col { flex: 1; min-width: 0; }
.wa-admin-name-col { font-size: 14px; font-weight: 500; color: #e9edef; margin-bottom: 2px; }
.wa-admin-label-col { font-size: 12px; color: #8696a0; }
.wa-admin-badge { background: #00a884; color: #fff; border-radius: 50%;
  width: 22px; height: 22px; font-size: 10px; font-weight: 700;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ═══ COL 2: CONVS LIST ═══ */
#wa-convs-col {
  width: 340px;
  min-width: 340px;
  display: flex;
  flex-direction: column;
  background: #111b21;
  border-right: 1px solid #222d34;
  flex-shrink: 0;
}

.wa-convs-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #202c33;
  flex-shrink: 0;
}
.wa-convs-title {
  font-size: 14px; font-weight: 600; color: #e9edef;
}
.wa-convs-list {
  flex: 1; overflow-y: auto;
}
.wa-convs-list::-webkit-scrollbar { width: 6px; }
.wa-convs-list::-webkit-scrollbar-thumb { background: #2a3942; border-radius: 3px; }

.wa-conv-item {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; cursor: pointer;
  border-bottom: 1px solid rgba(134,150,160,.1);
  transition: background .12s;
}
.wa-conv-item:hover { background: #202c33; }
.wa-conv-item.active { background: #2a3942; }

.wa-conv-av { width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0;
}
.wa-conv-info-col { flex: 1; min-width: 0; }
.wa-conv-name-col { font-size: 13px; font-weight: 500; color: #e9edef; }
.wa-conv-preview-col { font-size: 12px; color: #8696a0;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 1px;
}

/* ═══ COL 3: MESSAGES (READ-ONLY) ═══ */
#wa-msgs-col {
  flex: 1; display: flex; flex-direction: column; overflow: hidden;
  background: #0b141a;
}

.wa-msgs-header {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px;
  background: #202c33;
  border-bottom: 1px solid #222d34;
  flex-shrink: 0;
}
.wa-msgs-av { width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0;
}
.wa-msgs-info { flex: 1; }
.wa-msgs-name { font-size: 15px; font-weight: 600; color: #e9edef; }
.wa-msgs-status { font-size: 12px; color: #00a884; margin-top: 1px; }

.wa-readonly-badge {
  background: rgba(220,38,38,.2); color: #ff6b6b;
  border: 1px solid #ff6b6b; border-radius: 4px;
  padding: 4px 10px; font-size: 11px; font-weight: 600;
  display: flex; align-items: center; gap: 4px;
}

.wa-messages-area {
  flex: 1; overflow-y: auto;
  padding: 16px 8%;
  display: flex; flex-direction: column; gap: 4px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Cg opacity='.03' fill='%23fff'%3E%3Cpath d='M40 0a40 40 0 1 0 0 80A40 40 0 0 0 40 0zm0 72a32 32 0 1 1 0-64 32 32 0 0 1 0 64z'/%3E%3C/g%3E%3C/svg%3E");
}
.wa-messages-area::-webkit-scrollbar { width: 6px; }
.wa-messages-area::-webkit-scrollbar-thumb { background: #2a3942; border-radius: 3px; }

.wa-msg-wrap { display: flex; margin-bottom: 2px; }
.wa-msg-wrap.out { justify-content: flex-end; }
.wa-msg-wrap.in  { justify-content: flex-start; }

.wa-bubble {
  max-width: 65%; padding: 7px 12px 8px; border-radius: 8px;
  font-size: 14px; line-height: 1.55; word-break: break-word;
}
.wa-bubble.out { background: #005c4b; color: #e9edef; border-top-right-radius: 2px; }
.wa-bubble.in  { background: #202c33; color: #e9edef; border-top-left-radius: 2px; }
.wa-bubble-time { font-size: 11px; color: #8696a0; text-align: right; margin-top: 3px;
  display: flex; justify-content: flex-end; align-items: center; gap: 3px;
}
.wa-tick { color: #53bdeb; font-size: 12px; }

.wa-date-sep { text-align: center; margin: 12px 0 8px; }
.wa-date-sep span { background: #182229; color: #8696a0;
  font-size: 11.5px; padding: 4px 12px; border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0,0,0,.3);
}

/* Input locked */
.wa-input-locked {
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
  background: #202c33;
  border-top: 1px solid #222d34;
  flex-shrink: 0;
  gap: 8px;
  color: #ff6b6b;
  font-size: 13px;
  font-weight: 500;
}

.wa-empty-msgs {
  flex: 1; display: flex; align-items: center; justify-content: center;
  color: #8696a0; font-size: 14px;
}

/* Light mode */
[data-bs-theme=light] #wa-root { background: #f8f9fa; }
[data-bs-theme=light] #wa-admins-col { background: #fff; border-color: #e0e0e0; }
[data-bs-theme=light] #wa-convs-col { background: #fff; border-color: #e0e0e0; }
[data-bs-theme=light] .wa-admins-header, [data-bs-theme=light] .wa-convs-header, [data-bs-theme=light] .wa-msgs-header {
  background: #f5f5f5; }
[data-bs-theme=light] .wa-admin-item:hover, [data-bs-theme=light] .wa-conv-item:hover { background: #f0f0f0; }
[data-bs-theme=light] .wa-admin-item.active, [data-bs-theme=light] .wa-conv-item.active { background: #ebebeb; }
[data-bs-theme=light] .wa-admin-name-col, [data-bs-theme=light] .wa-convs-title,
[data-bs-theme=light] .wa-msgs-name, [data-bs-theme=light] .wa-admins-title { color: #111b21; }
[data-bs-theme=light] .wa-bubble.in { background: #fff; }
[data-bs-theme=light] .wa-bubble.out { background: #d9fdd3; color: #111b21; }
[data-bs-theme=light] .wa-messages-area { background-color: #efeae2; }
[data-bs-theme=light] .wa-input-locked { background: #f5f5f5; border-color: #e0e0e0; }

@media (max-width: 1200px) {
  #wa-admins-col { width: 250px; }
  #wa-convs-col { width: 300px; }
}
</style>

<div id="wa-root">

  <!-- ══ COL 1: ADMINS ══ -->
  <div id="wa-admins-col">
    <div class="wa-admins-header">
      <span class="wa-admins-title">👥 Admins Actifs</span>
    </div>
    <div class="wa-admins-list" id="waAdminsList"></div>
  </div>

  <!-- ══ COL 2: CONVERSATIONS ══ -->
  <div id="wa-convs-col">
    <div class="wa-convs-header">
      <span class="wa-convs-title" id="waConvsTitle">Conversations</span>
    </div>
    <div class="wa-convs-list" id="waConvsList"></div>
  </div>

  <!-- ══ COL 3: MESSAGES (READ-ONLY) ══ -->
  <div id="wa-msgs-col">
    <div class="wa-msgs-header">
      <div class="wa-msgs-av" id="waMsgAv"></div>
      <div class="wa-msgs-info">
        <div class="wa-msgs-name" id="waMsgName">Sélectionnez une conversation</div>
        <div class="wa-msgs-status">En ligne</div>
      </div>
      <div class="wa-readonly-badge">
        <span>🔒</span>
        <span>Lecture seule</span>
      </div>
    </div>
    <div class="wa-messages-area" id="waMessages">
      <div class="wa-empty-msgs">Sélectionnez une conversation</div>
    </div>
    <div class="wa-input-locked">
      <span>🔒</span>
      <span>Super Admin — Supervision en lecture seule</span>
    </div>
  </div>

</div>

<script>
// ── SUPER ADMIN MOCK DATA ──
const admins = [
  {
    id: "6", label: "#6 — Farah", color: "#6366F1", badge: 3,
    conversations: [
      {
        id: "a6c1", contact: "Ahmed Mansouri", initials: "AM",
        preview: "Mon service SMS ne répond plus...", time: "10:32",
        messages: [
          { dir: "in",  text: "Bonjour, mon service SMS ne répond plus depuis ce matin.", time: "10:28", date: "Aujourd'hui" },
          { dir: "out", text: "Bonjour ! Je vérifie cela immédiatement. Quel est votre numéro de compte ?", time: "10:30" },
          { dir: "in",  text: "Mon compte : TN-2024-5678. C'est urgent.", time: "10:32" },
        ]
      },
      {
        id: "a6c2", contact: "Sonia Belhadj", initials: "SB",
        preview: "Merci pour votre aide!", time: "09:15",
        messages: [
          { dir: "in",  text: "Bonjour, j'ai besoin d'aide pour configurer mon API.", time: "09:10", date: "Aujourd'hui" },
          { dir: "out", text: "Bonjour Sonia ! Bien sûr, envoyez-moi vos paramètres actuels.", time: "09:12" },
          { dir: "in",  text: "Merci pour votre aide!", time: "09:15" },
        ]
      },
      {
        id: "a6c3", contact: "Mohamed Triki", initials: "MT",
        preview: "+21655000000", time: "Hier",
        messages: [
          { dir: "in",  text: "+21655000000", time: "Hier 14:00", date: "Hier" },
          { dir: "out", text: "Bonjour, comment puis-je vous aider ?", time: "Hier 14:05" },
        ]
      }
    ]
  },
  {
    id: "7", label: "#7 — Yassine", color: "#F59E0B", badge: 4,
    conversations: [
      {
        id: "a7c1", contact: "Farah Yassine", initials: "FY",
        preview: "Mon token API est expiré", time: "11:05",
        messages: [
          { dir: "in",  text: "Bonjour, mon token API ne fonctionne plus depuis hier soir.", time: "11:00", date: "Aujourd'hui" },
          { dir: "out", text: "Je comprends. J'ai créé un ticket de renouvellement (TK-2041) avec priorité haute.", time: "11:05" },
        ]
      },
      {
        id: "a7c2", contact: "Khaled Trabelsi", initials: "KT",
        preview: "FAC-2024-00891", time: "10:45",
        messages: [
          { dir: "in",  text: "Bonjour, j'ai une erreur sur ma dernière facture.", time: "10:40", date: "Aujourd'hui" },
          { dir: "out", text: "Bonjour Khaled ! Pouvez-vous m'envoyer le numéro de facture ?", time: "10:43" },
          { dir: "in",  text: "FAC-2024-00891", time: "10:45" },
          { dir: "out", text: "Je vérifie cela. Vous aurez une réponse dans 30 minutes.", time: "10:47" },
        ]
      },
      {
        id: "a7c3", contact: "Ines Chaabane", initials: "IC",
        preview: "Quand sera résolu mon ticket?", time: "Hier",
        messages: [
          { dir: "in",  text: "Bonjour, quand sera résolu mon ticket TK-2041 ?", time: "Hier 16:00", date: "Hier" },
          { dir: "out", text: "Bonjour Ines, votre ticket est en traitement. Délai estimé : 2h.", time: "Hier 16:10" },
        ]
      },
      {
        id: "a7c4", contact: "Mzoughi Salah", initials: "MS",
        preview: "Demande de devis 50k SMS", time: "Hier",
        messages: [
          { dir: "in",  text: "Bonjour, je souhaite un devis pour 50 000 SMS.", time: "Hier 11:00", date: "Hier" },
          { dir: "out", text: "Bonjour ! Je vous prépare un devis personnalisé. Quel est votre secteur d'activité ?", time: "Hier 11:15" },
        ]
      }
    ]
  }
];

let selectedAdminId = null;
let selectedConvId = null;

function renderAdmins() {
  document.getElementById('waAdminsList').innerHTML = admins.map((a, i) => `
    <div class="wa-admin-item ${selectedAdminId === a.id ? 'active' : ''}" onclick="selectAdmin('${a.id}')">
      <div class="wa-admin-av-col" style="background: ${a.color};">${a.label.split('—')[0].trim()}</div>
      <div class="wa-admin-info-col">
        <div class="wa-admin-name-col">${a.label.split('—')[1].trim()}</div>
        <div class="wa-admin-label-col">${a.conversations.length} conversation(s)</div>
      </div>
      ${a.badge ? `<div class="wa-admin-badge">${a.badge}</div>` : ''}
    </div>
  `).join('');
}

function renderConvList(adminId) {
  const admin = admins.find(a => a.id === adminId);
  if (!admin) return document.getElementById('waConvsList').innerHTML = '';

  document.getElementById('waConvsList').innerHTML = admin.conversations.map(c => `
    <div class="wa-conv-item ${selectedConvId === c.id ? 'active' : ''}" onclick="selectConv('${adminId}', '${c.id}')">
      <div class="wa-conv-av" style="background: ${admin.color}; opacity: 0.8;">${c.initials}</div>
      <div class="wa-conv-info-col">
        <div class="wa-conv-name-col">${c.contact}</div>
        <div class="wa-conv-preview-col">${c.preview}</div>
      </div>
    </div>
  `).join('');
}

function renderMessages(adminId, convId) {
  const admin = admins.find(a => a.id === adminId);
  const conv = admin?.conversations.find(c => c.id === convId);
  if (!conv) return;

  document.getElementById('waMsgName').textContent = conv.contact;
  document.getElementById('waMsgAv').style.background = admin.color;
  document.getElementById('waMsgAv').textContent = conv.initials;
  document.getElementById('waConvsTitle').textContent = `${conv.contact} (${admin.label})`;

  let html = '';
  let lastDate = null;

  conv.messages.forEach(m => {
    if (m.date && m.date !== lastDate) {
      html += `<div class="wa-date-sep"><span>${m.date}</span></div>`;
      lastDate = m.date;
    }
    const tick = m.dir === 'out' ? '<span class="wa-tick">✓✓</span>' : '';
    html += `
      <div class="wa-msg-wrap ${m.dir}">
        <div class="wa-bubble ${m.dir}">
          ${escapeHtml(m.text)}
          <div class="wa-bubble-time">${m.time}${tick}</div>
        </div>
      </div>
    `;
  });

  document.getElementById('waMessages').innerHTML = html;
  document.getElementById('waMessages').scrollTop = document.getElementById('waMessages').scrollHeight;
}

function selectAdmin(adminId) {
  selectedAdminId = adminId;
  selectedConvId = null;
  renderAdmins();
  renderConvList(adminId);
  document.getElementById('waMsgName').textContent = 'Sélectionnez une conversation';
  document.getElementById('waMessages').innerHTML = '<div class="wa-empty-msgs">Sélectionnez une conversation</div>';
}

function selectConv(adminId, convId) {
  selectedConvId = convId;
  renderConvList(adminId);
  renderMessages(adminId, convId);
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;', "\n": '<br>' };
  return String(text).replace(/[&<>"'\n]/g, m => map[m]);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  renderAdmins();
  if (admins.length) selectAdmin(admins[0].id);
});
</script>

@endsection
