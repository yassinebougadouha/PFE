<?php $__env->startSection('title', 'Base de Connaissance — RAG'); ?>

<?php $__env->startSection('content'); ?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --rag-p: var(--color-primary, #6C63FF);
  --rag-s: var(--color-secondary, #8B85FF);
  --rag-bg: #F8F9FF;
  --rag-bg2: #FFFFFF;
  --rag-brd: #E8EAFF;
  --rag-t1: #0D0F1A;
  --rag-t2: #2D3047;
  --rag-t3: #6B7280;
  --rag-t4: #9CA3AF;
  --font: 'DM Sans', system-ui, sans-serif;
  --mono: 'DM Mono', monospace;
}
* { box-sizing: border-box; }
.rag-wrap { font-family: var(--font); background: var(--rag-bg); min-height: calc(100vh - 64px); padding: 0; }

/* ── CHAT INTERFACE ── */
.chat-container {
  display: flex; flex-direction: column; height: 600px;
  background: var(--rag-bg2); border: 1px solid var(--rag-brd); border-radius: 14px;
  overflow: hidden;
}
.chat-msgs {
  flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px;
  background: #f9faff;
}
.msg { max-width: 80%; padding: 10px 14px; border-radius: 12px; font-size: 13.5px; line-height: 1.5; }
.msg-user { align-self: flex-end; background: var(--rag-p); color: #fff; border-bottom-right-radius: 2px; }
.msg-ai { align-self: flex-start; background: #fff; color: var(--rag-t1); border: 1px solid var(--rag-brd); border-bottom-left-radius: 2px; }
.chat-input-wrap {
  padding: 16px; border-top: 1px solid var(--rag-brd); display: flex; gap: 10px; background: #fff;
}
.chat-input {
  flex: 1; padding: 10px 14px; border: 1px solid var(--rag-brd); border-radius: 10px;
  font-size: 13px; font-family: var(--font); outline: none;
}
.chat-input:focus { border-color: var(--rag-p); }
.chat-empty {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  color: var(--rag-t4); text-align: center; padding: 40px;
}

/* ── TOP BAR ── */
.rag-topbar {
  background: var(--rag-bg2);
  border-bottom: 1px solid var(--rag-brd);
  padding: 16px 28px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
}
.rag-title { font-size: 20px; font-weight: 700; color: var(--rag-t1); display: flex; align-items: center; gap: 10px; }
.rag-title-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, var(--rag-p), var(--rag-s));
  display: flex; align-items: center; justify-content: center;
}
.rag-tabs {
  display: flex; gap: 4px; background: var(--rag-bg); border: 1px solid var(--rag-brd);
  border-radius: 10px; padding: 4px;
}
.rag-tab {
  padding: 7px 18px; border-radius: 7px; border: none; background: transparent;
  font-size: 13px; font-weight: 600; color: var(--rag-t3); cursor: pointer;
  transition: all .18s; font-family: var(--font); display: flex; align-items: center; gap: 6px;
}
.rag-tab.active {
  background: var(--rag-bg2); color: var(--rag-p);
  box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.rag-tab:hover:not(.active) { color: var(--rag-t1); }
.btn-primary {
  padding: 8px 18px; border-radius: 9px; border: none; cursor: pointer;
  background: linear-gradient(135deg, var(--rag-p), var(--rag-s));
  color: #fff; font-size: 13px; font-weight: 600; font-family: var(--font);
  display: flex; align-items: center; gap: 6px; transition: all .18s;
  box-shadow: 0 3px 10px color-mix(in srgb, var(--rag-p) 35%, transparent);
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 16px color-mix(in srgb, var(--rag-p) 45%, transparent); }
.btn-outline {
  padding: 8px 16px; border-radius: 9px; cursor: pointer;
  background: transparent; border: 1px solid var(--rag-brd);
  color: var(--rag-t2); font-size: 13px; font-weight: 500; font-family: var(--font);
  display: flex; align-items: center; gap: 6px; transition: all .18s;
}
.btn-outline:hover { border-color: var(--rag-p); color: var(--rag-p); }

/* ── CONTENT ── */
.rag-body { padding: 24px 28px; }

/* ── STATS ── */
.rag-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.stat-card {
  background: var(--rag-bg2); border: 1px solid var(--rag-brd); border-radius: 14px;
  padding: 16px 20px; display: flex; align-items: center; gap: 14px;
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(108, 99, 255, 0.08); }
.stat-icon {
  width: 42px; height: 42px; border-radius: 12px; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px;
}
.stat-val { font-size: 22px; font-weight: 700; color: var(--rag-t1); line-height: 1; }
.stat-lbl { font-size: 11px; color: var(--rag-t4); margin-top: 3px; font-weight: 500; }

/* ── SEARCH / FILTER BAR ── */
.rag-bar {
  display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
}
.rag-search {
  flex: 1; display: flex; align-items: center; gap: 8px;
  background: var(--rag-bg2); border: 1px solid var(--rag-brd); border-radius: 10px;
  padding: 8px 14px;
}
.rag-search input {
  flex: 1; border: none; background: transparent; outline: none;
  font-size: 13px; color: var(--rag-t1); font-family: var(--font);
}
.rag-search input::placeholder { color: var(--rag-t4); }
.filter-sel {
  padding: 8px 14px; border-radius: 10px; border: 1px solid var(--rag-brd);
  background: var(--rag-bg2); font-size: 13px; color: var(--rag-t2);
  font-family: var(--font); outline: none; cursor: pointer;
}

/* ── ARTICLES TABLE ── */
.rag-table-wrap {
  background: var(--rag-bg2); border: 1px solid var(--rag-brd); border-radius: 14px; overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}
.rag-table { width: 100%; border-collapse: collapse; }
.rag-table thead tr { border-bottom: 1px solid var(--rag-brd); background: #FAFBFF; }
.rag-table thead th {
  padding: 14px 16px; font-size: 11px; font-weight: 700; color: var(--rag-t4);
  text-transform: uppercase; letter-spacing: .08em; text-align: left;
}
.rag-table tbody tr { border-bottom: 1px solid var(--rag-brd); transition: background .15s; }
.rag-table tbody tr:last-child { border: none; }
.rag-table tbody tr:hover { background: #fcfdff; }
.rag-table td { padding: 16px; font-size: 13.5px; color: var(--rag-t2); vertical-align: middle; }
.art-title { font-weight: 600; color: var(--rag-t1); margin-bottom: 2px; }
.art-excerpt { font-size: 11px; color: var(--rag-t4); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px; }
.badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px;
  border-radius: 99px; font-size: 10px; font-weight: 700;
}
.badge-blue { background: #EEF2FF; color: #4F46E5; }
.badge-green { background: #ECFDF5; color: #059669; }
.badge-orange { background: #FFF7ED; color: #D97706; }
.badge-red { background: #FEF2F2; color: #DC2626; }
.badge-gray { background: #F3F4F6; color: #6B7280; }
.act-btns { display: flex; gap: 5px; }
.act-btn {
  width: 29px; height: 29px; border-radius: 7px; border: 1px solid var(--rag-brd);
  background: transparent; cursor: pointer; display: flex; align-items: center;
  justify-content: center; color: var(--rag-t4); transition: all .14s;
}
.act-btn:hover { border-color: var(--rag-p); color: var(--rag-p); background: color-mix(in srgb, var(--rag-p) 6%, transparent); }
.act-btn.del:hover { border-color: #EF4444; color: #EF4444; background: #FEF2F2; }

/* ── PDF SECTION ── */
.pdf-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.pdf-card {
  background: var(--rag-bg2); border: 1px solid var(--rag-brd); border-radius: 14px;
  padding: 18px; display: flex; flex-direction: column; gap: 12px; transition: all .18s;
}
.pdf-card:hover { border-color: var(--rag-p); box-shadow: 0 4px 20px color-mix(in srgb, var(--rag-p) 10%, transparent); }
.pdf-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  background: linear-gradient(135deg, #FEF2F2, #FEE2E2);
  display: flex; align-items: center; justify-content: center; font-size: 22px;
}
.pdf-name { font-size: 14px; font-weight: 600; color: var(--rag-t1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pdf-meta { font-size: 11px; color: var(--rag-t4); }
.pdf-progress { height: 4px; background: var(--rag-brd); border-radius: 99px; overflow: hidden; }
.pdf-progress-bar { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--rag-p), var(--rag-s)); transition: width .6s; }
.pdf-actions { display: flex; gap: 6px; margin-top: auto; }
.pdf-status { display: flex; align-items: center; gap: 6px; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; }

/* ── DROP ZONE ── */
.drop-zone {
  border: 2px dashed var(--rag-brd); border-radius: 14px; padding: 36px 20px;
  text-align: center; cursor: pointer; transition: all .2s; margin-bottom: 20px;
  background: linear-gradient(135deg, #FAFBFF, #F0F1FF);
}
.drop-zone:hover, .drop-zone.drag { border-color: var(--rag-p); background: color-mix(in srgb, var(--rag-p) 4%, white); }
.drop-icon { font-size: 36px; margin-bottom: 10px; }
.drop-title { font-size: 15px; font-weight: 700; color: var(--rag-t1); margin-bottom: 5px; }
.drop-sub { font-size: 12px; color: var(--rag-t4); }

/* ── MODAL ── */
.modal-bg {
  display: none; position: fixed; inset: 0; z-index: 999;
  background: rgba(0,0,0,.45); backdrop-filter: blur(4px);
  align-items: center; justify-content: center;
}
.modal-bg.on { display: flex; }
.modal {
  background: var(--rag-bg2); border-radius: 18px; width: 560px; max-width: 95vw;
  max-height: 85vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,.18);
  animation: mopen .2s ease;
}
@keyframes mopen { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.modal-hdr {
  padding: 20px 24px 16px; border-bottom: 1px solid var(--rag-brd);
  display: flex; align-items: center; justify-content: space-between;
}
.modal-title { font-size: 16px; font-weight: 700; color: var(--rag-t1); }
.modal-close { background: none; border: none; cursor: pointer; color: var(--rag-t4); font-size: 18px; padding: 4px; border-radius: 6px; }
.modal-close:hover { color: var(--rag-t1); background: var(--rag-bg); }
.modal-body { padding: 20px 24px; }
.form-group { margin-bottom: 16px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--rag-t2); margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: .04em; }
.form-input, .form-textarea, .form-select {
  width: 100%; padding: 10px 13px; border-radius: 10px;
  border: 1px solid var(--rag-brd); background: var(--rag-bg);
  font-size: 13px; color: var(--rag-t1); font-family: var(--font); outline: none;
  transition: border-color .15s;
}
.form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--rag-p); box-shadow: 0 0 0 3px color-mix(in srgb, var(--rag-p) 10%, transparent); }
.form-textarea { resize: vertical; min-height: 120px; line-height: 1.6; }
.modal-foot { padding: 14px 24px 20px; display: flex; justify-content: flex-end; gap: 8px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

/* ── CHUNK PREVIEW ── */
.chunk-list { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
.chunk {
  background: var(--rag-bg); border: 1px solid var(--rag-brd); border-radius: 10px;
  padding: 12px 14px; font-size: 12px; color: var(--rag-t3); font-family: var(--mono);
  line-height: 1.6; position: relative;
}
.chunk-n {
  position: absolute; top: 8px; right: 10px; font-size: 10px; font-weight: 700;
  color: var(--rag-p); background: color-mix(in srgb, var(--rag-p) 10%, transparent);
  padding: 2px 7px; border-radius: 99px;
}

/* ── EMPTY ── */
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon { font-size: 42px; margin-bottom: 12px; }
.empty-title { font-size: 16px; font-weight: 700; color: var(--rag-t1); margin-bottom: 6px; }
.empty-sub { font-size: 13px; color: var(--rag-t4); }

/* ── PAGINATION ── */
.pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--rag-brd); }
.pg-info { font-size: 12px; color: var(--rag-t4); }
.pg-btns { display: flex; gap: 4px; }
.pg-btn {
  width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--rag-brd);
  background: transparent; cursor: pointer; font-size: 12px; font-weight: 600;
  color: var(--rag-t3); transition: all .13s; font-family: var(--font);
  display: flex; align-items: center; justify-content: center;
}
.pg-btn.active { background: var(--rag-p); border-color: var(--rag-p); color: #fff; }
.pg-btn:hover:not(.active) { border-color: var(--rag-p); color: var(--rag-p); }

/* notify toast */
.toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 9999;
  background: #0D0F1A; color: #fff; padding: 12px 18px;
  border-radius: 12px; font-size: 13px; font-family: var(--font);
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 8px 30px rgba(0,0,0,.25); opacity: 0;
  transform: translateY(10px); transition: all .25s; pointer-events: none;
}
.toast.show { opacity: 1; transform: translateY(0); }
</style>

<?php $role = auth()->user()->role; ?>

<div class="rag-wrap">

  
  <div class="rag-topbar">
    <div style="display:flex;align-items:center;gap:16px;">
      <div class="rag-title">
        <div class="rag-title-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        Base de Connaissance
      </div>
      <div class="rag-tabs">
        <button class="rag-tab active" onclick="switchTab('articles',this)">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Articles
        </button>
        <button class="rag-tab" onclick="switchTab('pdfs',this)">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Documents PDF
        </button>
        <button class="rag-tab" onclick="switchTab('chat',this)">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Test Chat
        </button>
      </div>
    </div>
    <div style="display:flex;gap:8px;" id="topActions">
      <button class="btn-outline" onclick="showModal('import')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        Importer
      </button>
      <button class="btn-primary" onclick="showModal('article')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouvel article
      </button>
    </div>
  </div>

  
  <div class="rag-body">

    
    <div class="rag-stats">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        </div>
        <div><div class="stat-val" id="statArticles">0</div><div class="stat-lbl">Articles</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#FEF2F2,#FEE2E2);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
        </div>
        <div><div class="stat-val" id="statPdfs">0</div><div class="stat-lbl">Documents PDF</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div><div class="stat-val" id="statChunks">0</div><div class="stat-lbl">Chunks indexés</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#FFF7ED,#FEE9D1);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div><div class="stat-val" id="statQueries">0</div><div class="stat-lbl">Requêtes ce mois</div></div>
      </div>
    </div>

    
    <div id="tab-articles">
      <div class="rag-bar">
        <div class="rag-search" style="border-radius:14px;padding:10px 16px;border:1.5px solid var(--rag-brd);transition:border-color .2s,box-shadow .2s;" onfocusin="this.style.borderColor='var(--rag-p)';this.style.boxShadow='0 0 0 3px color-mix(in srgb,var(--rag-p) 12%,transparent)'" onfocusout="this.style.borderColor='var(--rag-brd)';this.style.boxShadow='none'">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--rag-t4)" stroke-width="2" style="flex-shrink:0;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" placeholder="Rechercher un article par titre, tag, contenu…" id="artSearch" oninput="filterArticles()" style="font-size:14px;" autocomplete="off">
          <span style="flex-shrink:0;font-size:10px;font-weight:600;color:var(--rag-t4);background:#f7fafc;border:1px solid var(--rag-brd);border-radius:5px;padding:2px 6px;font-family:monospace;">/ ou Ctrl+K</span>
        </div>
        <select class="filter-sel" id="artCatFilter" onchange="filterArticles()">
          <option value="">Toutes catégories</option>
          <option value="TECHNICAL">Technique & API</option>
          <option value="BILLING">Facturation</option>
          <option value="ACCOUNT">Compte & Connexion</option>
          <option value="GENERAL">Général</option>
          <option value="SECURITY">Sécurité</option>
          <option value="TROUBLESHOOTING">Dépannage</option>
          <option value="FAQ">FAQ</option>
          <option value="POLICY">Politiques</option>
          <option value="ONBOARDING">Onboarding</option>
          <option value="FEATURE_GUIDE">Guide des fonctionnalités</option>
        </select>
        <select class="filter-sel" id="artStatusFilter" onchange="filterArticles()">
          <option value="">Tous statuts</option>
          <option value="PUBLISHED">Publié</option>
          <option value="DRAFT">Brouillon</option>
          <option value="ARCHIVED">Archivé</option>
        </select>
      </div>

      
      <div id="bulkBar" style="display:none;align-items:center;justify-content:space-between;padding:10px 20px;background:var(--rag-t1);color:#fff;border-radius:12px;margin-bottom:14px;animation:mi .2s ease;">
        <div style="font-size:13px;font-weight:600;"><span id="bulkCount">0</span> article(s) sélectionné(s)</div>
        <div style="display:flex;gap:8px;">
          <button class="btn-outline" style="border-color:rgba(255,255,255,.2);color:#fff;font-size:11px;padding:5px 12px;" onclick="bulkIndex()">Ré-indexer</button>
          <button class="btn-outline" style="border-color:#EF4444;color:#EF4444;font-size:11px;padding:5px 12px;" onclick="bulkDelete()">Supprimer</button>
          <button class="btn-outline" style="border-color:rgba(255,255,255,.2);color:#fff;font-size:11px;padding:5px 12px;" onclick="cancelBulk()">Annuler</button>
        </div>
      </div>

      <div class="rag-table-wrap">
        <table class="rag-table" id="artTable">
          <thead>
            <tr>
              <th style="width:36px;"><input type="checkbox" id="chkAll" onchange="toggleAll(this)"></th>
              <th>Article</th>
              <th>Catégorie</th>
              <th>Statut</th>
              <th>Chunks</th>
              <th>Modifié</th>
              <th style="width:100px;">Actions</th>
            </tr>
          </thead>
          <tbody id="artBody">
            <tr><td colspan="7"><div style="padding:40px;text-align:center;color:var(--rag-t4);">Chargement des articles...</div></td></tr>
          </tbody>
        </table>
        <div class="pagination">
          <div class="pg-info" id="pgInfo">Affichage 0–0 sur 0 articles</div>
          <div class="pg-btns" id="pgBtns">
          </div>
        </div>
      </div>
    </div>

    
    <div id="tab-pdfs" style="display:none;">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div style="font-size:13px;font-weight:600;color:var(--rag-t2);" id="pdfCount">0 document</div>
        <div style="display:flex;gap:8px;">
          <select class="filter-sel" onchange="filterPdfs(this.value)">
            <option value="">Tous statuts</option>
            <option value="indexed">Indexé</option>
            <option value="pending">En attente</option>
            <option value="error">Erreur</option>
          </select>
          <button class="btn-outline" onclick="ingestAllPdfs()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Tout indexer
          </button>
        </div>
      </div>

      <div class="pdf-grid" id="pdfGrid"></div>
    </div>

    
    <div id="tab-chat" style="display:none;">
      <div class="chat-container">
        <div class="chat-msgs" id="chatMsgs">
          <div class="chat-empty">
            <div style="font-size:32px;margin-bottom:10px;">🤖</div>
            <div style="font-weight:700;color:var(--rag-t1);">Assistant RAG</div>
            <div style="font-size:12px;max-width:280px;">Posez une question pour tester la pertinence des réponses générées à partir de votre base de connaissance.</div>
          </div>
        </div>
        <div class="chat-input-wrap">
          <input type="text" class="chat-input" id="chatInput" placeholder="Posez une question sur vos documents..." onkeydown="if(event.key==='Enter')sendChat()">
          <button class="btn-primary" onclick="sendChat()" id="sendBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
      </div>
    </div>

  </div>
</div>


<div class="modal-bg" id="modalArticle">
  <div class="modal">
    <div class="modal-hdr">
      <span class="modal-title" id="modalArtTitle">Nouvel article</span>
      <button class="modal-close" onclick="closeModal('modalArticle')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Titre</label>
        <input type="text" class="form-input" id="artTitleIn" placeholder="Ex: Comment régénérer un token API ?">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Catégorie</label>
          <select class="form-select" id="artCatIn">
            <option value="GENERAL">Général</option>
            <option value="TECHNICAL">Technique & API</option>
            <option value="BILLING">Facturation</option>
            <option value="ACCOUNT">Compte & Connexion</option>
            <option value="SECURITY">Sécurité</option>
            <option value="TROUBLESHOOTING">Dépannage</option>
            <option value="FAQ">FAQ</option>
            <option value="POLICY">Politiques</option>
            <option value="ONBOARDING">Onboarding</option>
            <option value="FEATURE_GUIDE">Guide des fonctionnalités</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select class="form-select" id="artStatusIn">
            <option value="DRAFT">Brouillon</option>
            <option value="PUBLISHED">Publié</option>
            <option value="ARCHIVED">Archivé</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Contenu</label>
        <textarea class="form-textarea" id="artContentIn" placeholder="Rédigez le contenu de l'article…" style="min-height:180px;"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Tags</label>
        <input type="text" class="form-input" id="artTagsIn" placeholder="api, token, authentification…">
        <div style="font-size:11px;color:var(--rag-t4);margin-top:5px;">Séparez les tags par des virgules</div>
      </div>

      
      <div style="border-top:1px solid var(--rag-brd);padding-top:14px;margin-top:4px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <span style="font-size:12px;font-weight:700;color:var(--rag-t2);text-transform:uppercase;letter-spacing:.04em;">Aperçu chunks</span>
          <button class="btn-outline" style="padding:5px 12px;font-size:11px;" onclick="previewChunks()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/></svg>
            Prévisualiser
          </button>
        </div>
        <div class="chunk-list" id="chunkPreview">
          <div style="font-size:12px;color:var(--rag-t4);text-align:center;padding:16px;">Cliquez sur "Prévisualiser" pour voir le découpage en chunks</div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('modalArticle')">Annuler</button>
      <button class="btn-primary" onclick="saveArticle()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
        Sauvegarder & Indexer
      </button>
    </div>
  </div>
</div>


<div class="modal-bg" id="modalImport">
  <div class="modal">
    <div class="modal-hdr">
      <span class="modal-title">Importer des articles</span>
      <button class="modal-close" onclick="closeModal('modalImport')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div style="padding:14px;border:1px solid var(--rag-brd);border-radius:12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:12px;" onmouseover="this.style.borderColor='var(--rag-p)'" onmouseout="this.style.borderColor='var(--rag-brd)'">
          <div style="width:38px;height:38px;border-radius:9px;background:#F0FDF4;display:flex;align-items:center;justify-content:center;font-size:18px;">📋</div>
          <div><div style="font-size:13px;font-weight:600;color:var(--rag-t1);">Import CSV</div><div style="font-size:11px;color:var(--rag-t4);">titre, contenu, catégorie, statut</div></div>
        </div>
        <div style="padding:14px;border:1px solid var(--rag-brd);border-radius:12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:12px;" onmouseover="this.style.borderColor='var(--rag-p)'" onmouseout="this.style.borderColor='var(--rag-brd)'">
          <div style="width:38px;height:38px;border-radius:9px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:18px;">🌐</div>
          <div><div style="font-size:13px;font-weight:600;color:var(--rag-t1);">Import URL / Sitemap</div><div style="font-size:11px;color:var(--rag-t4);">Scraping automatique de documentation</div></div>
        </div>
        <div style="padding:14px;border:1px solid var(--rag-brd);border-radius:12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:12px;" onmouseover="this.style.borderColor='var(--rag-p)'" onmouseout="this.style.borderColor='var(--rag-brd)'">
          <div style="width:38px;height:38px;border-radius:9px;background:#FFF7ED;display:flex;align-items:center;justify-content:center;font-size:18px;">🔗</div>
          <div><div style="font-size:13px;font-weight:600;color:var(--rag-t1);">Import API externe</div><div style="font-size:11px;color:var(--rag-t4);">Notion, Confluence, Zendesk…</div></div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closeModal('modalImport')">Fermer</button>
    </div>
  </div>
</div>


<div id="modalPdf" style="display:none; position:fixed; inset:0; z-index:9999;
  background:rgba(0,0,0,.5); backdrop-filter:blur(4px);
  align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:18px; width:440px; max-width:95vw;
    max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25);
    animation:mopen .2s ease; position:relative;">
    <div class="modal-hdr">
      <span class="modal-title">Importer un PDF</span>
      <button class="modal-close" onclick="closePdfModal()">✕</button>
    </div>
    <div class="modal-body">
      
      <div id="modalDropZone" style="
        border: 2px dashed var(--rag-brd); border-radius: 14px;
        background: var(--rag-bg); padding: 32px 24px;
        text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
        margin-bottom: 16px;
      " onclick="document.getElementById('pdfInputModal').click()"
         ondragover="event.preventDefault(); this.style.borderColor='var(--rag-p)'; this.style.background='color-mix(in srgb, var(--rag-p) 5%, white)';"
         ondragleave="this.style.borderColor='var(--rag-brd)'; this.style.background='var(--rag-bg)';"
         ondrop="handleModalDrop(event)">
        <input type="file" id="pdfInputModal" accept=".pdf" style="display:none" onchange="selectPdfFile(this.files[0])">
        <div id="modalDropContent">
          <div style="font-size:32px; margin-bottom:8px;">📄</div>
          <div style="font-size:14px; font-weight:600; color:var(--rag-t1); margin-bottom:4px;">Glissez votre PDF ici ou cliquez</div>
          <div style="font-size:12px; color:var(--rag-t4);">Max 50 MB · PDF uniquement</div>
        </div>
      </div>

      
      <div id="selectedFileRow" style="display:none; align-items:center; gap:10px;
        background: var(--rag-bg); border: 1px solid var(--rag-brd); border-radius:10px;
        padding: 10px 14px; margin-bottom:20px; cursor:pointer;"
        onclick="document.getElementById('pdfInputModal').click()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--rag-p)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <div style="flex:1; min-width:0;">
          <div id="selectedFileName" style="font-size:13px; font-weight:600; color:var(--rag-t1); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
          <div id="selectedFileSize" style="font-size:11px; color:var(--rag-t4); margin-top:1px;"></div>
        </div>
        <button onclick="event.stopPropagation(); clearPdfFile();" style="background:none;border:none;cursor:pointer;color:var(--rag-t4);font-size:16px;padding:2px;">✕</button>
      </div>

      
      <div class="form-row" style="margin-bottom:16px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Catégorie</label>
          <select class="form-select" id="pdfCategory">
            <option value="GENERAL">Général</option>
            <option value="TECHNICAL">Technique & API</option>
            <option value="BILLING">Facturation</option>
            <option value="ACCOUNT">Compte & Connexion</option>
            <option value="SECURITY">Sécurité</option>
            <option value="TROUBLESHOOTING">Dépannage</option>
            <option value="FAQ">FAQ</option>
            <option value="POLICY">Politiques</option>
            <option value="ONBOARDING">Onboarding</option>
            <option value="FEATURE_GUIDE">Guide des fonctionnalités</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Langue</label>
          <select class="form-select" id="pdfLanguage">
            <option value="fr">Français</option>
            <option value="en">English</option>
            <option value="ar">العربية</option>
          </select>
        </div>
      </div>

      
      <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; color:var(--rag-t2); user-select:none;">
        <input type="checkbox" id="pdfAutoPublish" checked style="width:15px;height:15px;accent-color:var(--rag-p); cursor:pointer;">
        Publier automatiquement après ingestion
      </label>
    </div>
    <div class="modal-foot">
      <button class="btn-outline" onclick="closePdfModal()">Annuler</button>
      <button class="btn-primary" id="pdfUploadBtn" onclick="uploadSelectedPdf()" disabled style="opacity:.5; cursor:not-allowed;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        Uploader &amp; Ingérer
      </button>
    </div>
  </div>
</div>


<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Sauvegardé</span>
</div>

<script>
// ── DATA ─────────────────────────────────────────────────────
var articles = [];
var filteredArticles = [];
var pdfs = [];
var stats = { articles: 0, pdfs: 0, chunks: 0, queries: 0 };

var currentEditId = null;
var apiBase = '/api/v1/rag';
var pdfBase = '/super-admin/rag'; // PDF upload/ingest via Laravel controller (not wildcard proxy)

// Pagination
var pgSize = 10;
var pgCurr = 1;

// ── Init ──
document.addEventListener('DOMContentLoaded', function() {
  fetchStats();
  fetchArticles();
  fetchPdfs();
});

async function fetchStats() {
  try {
    const res = await fetch(`${apiBase}/stats`);
    const data = await res.json();
    document.getElementById('statArticles').textContent = data.total_articles || 0;
    document.getElementById('statChunks').textContent = data.total_chunks || 0;
    document.getElementById('statQueries').textContent = data.total_tokens || 0;
  } catch (e) { console.error('Stats error:', e); }
}

async function fetchArticles() {
  try {
    const res = await fetch(`${apiBase}/articles?limit=100`);
    const data = await res.json();
    articles = data.items || [];
    filterArticles(); // initial filter & render
  } catch (e) {
    console.error('Articles error:', e);
    document.getElementById('artBody').innerHTML = '<tr><td colspan="7"><div style="padding:20px;text-align:center;color:#EF4444;">Erreur lors du chargement des articles</div></td></tr>';
  }
}

async function fetchPdfs() {
  try {
    const res = await fetch(`${apiBase}/documents`);
    const data = await res.json();
    document.getElementById('statPdfs').textContent = data.total_files || 0;
    pdfs = (data.files || []).map(p => ({
      ...p,
      id: p.article_id || p.filename,
      name: p.filename,
      size: p.size_human,
      status: p.is_ingested ? 'indexed' : 'pending',
      progress: p.is_ingested ? 100 : 0,
      date: p.modified_at ? new Date(p.modified_at).toLocaleDateString() : 'N/A'
    }));
    renderPdfs(pdfs);
  } catch (e) { console.error('PDFs error:', e); }
}

// ── Keyboard shortcut ──
document.addEventListener('keydown', function(e) {
  if ((e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') ||
      (e.ctrlKey && e.key === 'k')) {
    e.preventDefault();
    var s = document.getElementById('artSearch');
    if (s) { s.focus(); s.select(); }
  }
});

// ── TABS ─────────────────────────────────────────────────────
function switchTab(tab, btn) {
  document.querySelectorAll('.rag-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-articles').style.display = tab==='articles' ? 'block' : 'none';
  document.getElementById('tab-pdfs').style.display = tab==='pdfs' ? 'block' : 'none';
  document.getElementById('tab-chat').style.display = tab==='chat' ? 'block' : 'none';

  var topAct = document.getElementById('topActions');
  if (tab === 'pdfs') {
    topAct.innerHTML = `
      <button class="btn-primary" onclick="openPdfModal()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Ajouter PDF
      </button>`;
  } else if (tab === 'chat') {
    topAct.innerHTML = `
      <button class="btn-outline" onclick="clearChat()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
        Effacer
      </button>`;
  } else {
    topAct.innerHTML = `
      <button class="btn-outline" onclick="showModal('import')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        Importer
      </button>
      <button class="btn-primary" onclick="showModal('article')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouvel article
      </button>`;
  }
}

// ── CHAT ─────────────────────────────────────────────────────
async function sendChat() {
  const input = document.getElementById('chatInput');
  const query = input.value.trim();
  if (!query) return;

  const msgs = document.getElementById('chatMsgs');
  if (msgs.querySelector('.chat-empty')) msgs.innerHTML = '';

  // Add User Message
  msgs.innerHTML += `<div class="msg msg-user">${esc(query)}</div>`;
  input.value = '';
  msgs.scrollTop = msgs.scrollHeight;

  // Add AI Loading
  const aiId = 'ai-' + Date.now();
  msgs.innerHTML += `<div class="msg msg-ai" id="${aiId}">...</div>`;
  msgs.scrollTop = msgs.scrollHeight;

  try {
    const res = await fetch(`${apiBase}/generate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
      body: JSON.stringify({
        query: query,
        channel: 'CHAT'
      })
    });
    const data = await res.json();
    document.getElementById(aiId).innerHTML = data.response ? data.response.replace(/\n/g, '<br>') : 'Désolé, je n\'ai pas pu générer de réponse.';
  } catch (e) {
    document.getElementById(aiId).textContent = 'Erreur: Impossible de contacter l\'assistant.';
  }
  msgs.scrollTop = msgs.scrollHeight;
}

function clearChat() {
  document.getElementById('chatMsgs').innerHTML = `
    <div class="chat-empty">
      <div style="font-size:32px;margin-bottom:10px;">🤖</div>
      <div style="font-weight:700;color:var(--rag-t1);">Assistant RAG</div>
      <div style="font-size:12px;max-width:280px;">Posez une question pour tester la pertinence des réponses générées à partir de votre base de connaissance.</div>
    </div>`;
}

// ── ARTICLES ─────────────────────────────────────────────────
function renderArticles(list) {
  var body = document.getElementById('artBody');
  if (!list.length) {
    body.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📭</div><div class="empty-title">Aucun article trouvé</div><div class="empty-sub">Créez votre premier article de base de connaissance</div></div></td></tr>';
    updatePg();
    return;
  }

  // Paginate list
  const start = (pgCurr - 1) * pgSize;
  const page = list.slice(start, start + pgSize);

  body.innerHTML = page.map(a => `
    <tr>
      <td><input type="checkbox" class="art-chk" data-id="${a.id}" onchange="updateBulk()"></td>
      <td>
        <div class="art-title" style="cursor:pointer" onclick="viewArticle('${a.id}')">${esc(a.title)}</div>
        <div class="art-excerpt">${esc(a.content ? a.content.substring(0, 100) + '...' : '')}</div>
        <div style="display:flex;gap:4px;margin-top:5px;flex-wrap:wrap;">
          ${(a.tags||[]).map(t=>`<span style="background:#F0F0FF;color:#6C63FF;font-size:9px;font-weight:700;padding:1px 6px;border-radius:99px;">${t}</span>`).join('')}
        </div>
      </td>
      <td><span class="badge badge-blue">${catLabel(a.category)}</span></td>
      <td>${statusBadge(a.status)}</td>
      <td><span style="font-family:var(--mono);font-size:12px;color:var(--rag-t3);cursor:pointer;text-decoration:underline" onclick="viewChunks('${a.id}')">${a.chunk_count || 0}</span></td>
      <td style="font-size:12px;color:var(--rag-t4);">${a.updated_at ? new Date(a.updated_at).toLocaleDateString() : 'N/A'}</td>
      <td>
        <div class="act-btns">
          <button class="act-btn" onclick="editArticle('${a.id}')" title="Modifier">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="act-btn" onclick="reindexArticle('${a.id}')" title="Ré-indexer">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          </button>
          <button class="act-btn del" onclick="deleteArticle('${a.id}')" title="Supprimer">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
  updatePg();
}

function updatePg() {
  const total = filteredArticles.length;
  const totalPages = Math.ceil(total / pgSize);
  const start = total === 0 ? 0 : (pgCurr - 1) * pgSize + 1;
  const end = Math.min(pgCurr * pgSize, total);

  document.getElementById('pgInfo').textContent = `Affichage ${start}–${end} sur ${total} articles`;

  var btns = document.getElementById('pgBtns');
  btns.innerHTML = '';
  if (totalPages <= 1) return;

  // Simple pager
  const createBtn = (n, label, active) => {
    const b = document.createElement('button');
    b.className = 'pg-btn' + (active ? ' active' : '');
    b.textContent = label || n;
    b.onclick = () => { pgCurr = n; renderArticles(filteredArticles); };
    return b;
  };

  btns.appendChild(createBtn(Math.max(1, pgCurr - 1), '‹'));
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= pgCurr - 1 && i <= pgCurr + 1)) {
      btns.appendChild(createBtn(i, i, i === pgCurr));
    } else if (i === pgCurr - 2 || i === pgCurr + 2) {
      const span = document.createElement('span');
      span.textContent = '...';
      span.style.padding = '0 5px';
      btns.appendChild(span);
    }
  }
  btns.appendChild(createBtn(Math.min(totalPages, pgCurr + 1), '›'));
}

function filterArticles() {
  var q = document.getElementById('artSearch').value.toLowerCase();
  var cat = document.getElementById('artCatFilter').value;
  var status = document.getElementById('artStatusFilter').value;
  
  filteredArticles = articles.filter(a =>
    (!q || a.title.toLowerCase().includes(q) || (a.content && a.content.toLowerCase().includes(q))) &&
    (!cat || a.category === cat) &&
    (!status || a.status === status)
  );
  
  pgCurr = 1;
  renderArticles(filteredArticles);
  updateBulk();
}

// ── BULK ─────────────────────────────────────────────────────
function updateBulk() {
  const checked = document.querySelectorAll('.art-chk:checked');
  const bar = document.getElementById('bulkBar');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    document.getElementById('bulkCount').textContent = checked.length;
  } else {
    bar.style.display = 'none';
    document.getElementById('chkAll').checked = false;
  }
}

function cancelBulk() {
  document.querySelectorAll('.art-chk').forEach(c => c.checked = false);
  updateBulk();
}

async function bulkDelete() {
  const ids = Array.from(document.querySelectorAll('.art-chk:checked')).map(c => c.dataset.id);
  if (!confirm(`Supprimer les ${ids.length} articles sélectionnés ?`)) return;
  
  toast('Suppression en cours...');
  for (const id of ids) {
    await fetch(`${apiBase}/articles/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' } });
  }
  toast('Articles supprimés');
  fetchArticles();
  fetchStats();
}

async function bulkIndex() {
  const ids = Array.from(document.querySelectorAll('.art-chk:checked')).map(c => c.dataset.id);
  toast(`Ré-indexation de ${ids.length} articles...`);
  for (const id of ids) {
    await fetch(`${apiBase}/articles/${id}/index`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' } });
  }
  toast('Ré-indexation terminée');
  fetchArticles();
}

// ── ARTICLE CRUD ─────────────────────────────────────────────
async function viewArticle(id) {
  const a = articles.find(x => x.id === id);
  if (!a) return;
  editArticle(id);
}

async function viewChunks(id) {
  // If id is a UUID, it's an article. If it's a filename, it's a PDF.
  const isPdf = !/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(id);
  
  toast('Chargement des chunks...');
  try {
    let chunks = [];
    if (isPdf) {
      const pdf = pdfs.find(p => p.id === id);
      if (pdf && pdf.article_id) {
        const res = await fetch(`${apiBase}/articles/${pdf.article_id}`);
        const data = await res.json();
        chunks = data.chunks || [];
      } else {
        // Fallback or search
        toast('Document non indexé ou article introuvable.');
        return;
      }
    } else {
      const res = await fetch(`${apiBase}/articles/${id}`);
      const data = await res.json();
      chunks = data.chunks || [];
    }

    if (!chunks.length) {
      toast('Aucun chunk trouvé.');
      return;
    }

    const html = chunks.map((c, i) => `
      <div class="chunk">
        ${esc(c.content)}
        <span class="chunk-n">Chunk ${i+1} · ${c.tokens || 0} tokens</span>
      </div>
    `).join('');
    
    document.getElementById('chunkPreview').innerHTML = html;
    document.getElementById('modalArtTitle').textContent = 'Consultation des Chunks';
    
    showModal('article');
    // Read-only mode for chunks
    document.querySelector('#modalArticle .modal-body > .form-group:first-child').style.display = 'none';
    document.querySelector('#modalArticle .form-row').style.display = 'none';
    document.querySelector('#modalArticle .form-group:nth-child(3)').style.display = 'none';
    document.querySelector('#modalArticle .form-group:nth-child(4)').style.display = 'none';
    document.querySelector('#modalArticle .modal-foot .btn-primary').style.display = 'none';
  } catch (e) {
    console.error(e);
    toast('Erreur de chargement');
  }
}

function editArticle(id) {
  var a = articles.find(x => x.id === id);
  if (!a) return;
  currentEditId = id;
  document.getElementById('modalArtTitle').textContent = 'Modifier l\'article';
  document.getElementById('artTitleIn').value = a.title;
  document.getElementById('artContentIn').value = a.content || '';
  document.getElementById('artCatIn').value = a.category || 'GENERAL';
  document.getElementById('artStatusIn').value = a.status || 'DRAFT';
  document.getElementById('artTagsIn').value = (a.tags||[]).join(', ');
  showModal('article');
}

async function deleteArticle(id) {
  if (!confirm('Supprimer cet article ? Cette action est irréversible.')) return;
  try {
    const res = await fetch(`${apiBase}/articles/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
    });
    if (res.ok) {
      toast('Article supprimé');
      fetchArticles();
      fetchStats();
    } else {
      toast('Erreur lors de la suppression');
    }
  } catch (e) { console.error(e); }
}

async function reindexArticle(id) {
  toast('Ré-indexation en cours…');
  try {
    const res = await fetch(`${apiBase}/articles/${id}/index`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
    });
    if (res.ok) {
      toast('Article ré-indexé avec succès ✓');
      fetchArticles();
    } else {
      toast('Erreur lors de la ré-indexation');
    }
  } catch (e) { console.error(e); }
}

async function saveArticle() {
  var title = document.getElementById('artTitleIn').value.trim();
  var content = document.getElementById('artContentIn').value.trim();
  var category = document.getElementById('artCatIn').value;
  var status = document.getElementById('artStatusIn').value;
  var tags = document.getElementById('artTagsIn').value.split(',').map(t=>t.trim()).filter(Boolean);

  if (!title) { document.getElementById('artTitleIn').style.borderColor='#EF4444'; return; }
  if (!content) { document.getElementById('artContentIn').style.borderColor='#EF4444'; return; }

  const payload = { title, content, category, tags, auto_index: true };

  try {
    let res;
    if (currentEditId) {
      // Find original status
      const oldArt = articles.find(a => a.id === currentEditId);
      
      res = await fetch(`${apiBase}/articles/${currentEditId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
        body: JSON.stringify({ ...payload, re_index: true })
      });

      if (res.ok && oldArt && oldArt.status !== status) {
        // Status changed, call appropriate endpoint
        const endpoint = status === 'PUBLISHED' ? 'publish' : (status === 'ARCHIVED' ? 'archive' : null);
        if (endpoint) {
          await fetch(`${apiBase}/articles/${currentEditId}/${endpoint}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
          });
        }
      }
    } else {
      res = await fetch(`${apiBase}/articles`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
        body: JSON.stringify(payload)
      });
      
      if (res.ok) {
        const newArt = await res.json();
        // If draft was created but user selected something else, call lifecycle endpoint
        if (status === 'PUBLISHED') {
          await fetch(`${apiBase}/articles/${newArt.id}/publish`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
          });
        } else if (status === 'ARCHIVED') {
          await fetch(`${apiBase}/articles/${newArt.id}/archive`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
          });
        }
      }
    }

    if (res.ok) {
      toast(currentEditId ? 'Article mis à jour' : 'Article créé');
      closeModal('modalArticle');
      fetchArticles();
      fetchStats();
      currentEditId = null;
    } else {
      const err = await res.json();
      toast('Erreur: ' + (err.detail || 'Sauvegarde échouée'));
    }
  } catch (e) { console.error(e); }
}

function previewChunks() {
  var content = document.getElementById('artContentIn').value;
  if (!content.trim()) { document.getElementById('chunkPreview').innerHTML = '<div style="font-size:12px;color:#EF4444;text-align:center;padding:10px;">Rédigez d\'abord le contenu de l\'article</div>'; return; }
  var words = content.split(' ');
  var chunks = [];
  var size = 80;
  for (var i=0; i<Math.min(words.length, size*3); i+=size) {
    chunks.push(words.slice(i, i+size).join(' ')+'…');
  }
  document.getElementById('chunkPreview').innerHTML = chunks.map((c,i) => `<div class="chunk">${esc(c)}<span class="chunk-n">Chunk ${i+1}</span></div>`).join('') || '<div style="font-size:12px;color:var(--rag-t4);text-align:center;padding:10px;">Contenu trop court pour être découpé</div>';
}

function toggleAll(chk) {
  document.querySelectorAll('.art-chk').forEach(c => c.checked = chk.checked);
}

// ── PDFs ──────────────────────────────────────────────────────
function renderPdfs(list) {
  var grid = document.getElementById('pdfGrid');
  document.getElementById('pdfCount').textContent = list.length + ' document' + (list.length>1?'s':'');
  if (!list.length) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">📂</div><div class="empty-title">Aucun document PDF</div><div class="empty-sub">Importez vos PDFs pour les indexer dans la base de connaissance</div></div>';
    return;
  }
  grid.innerHTML = list.map(p => {
    var sColor = p.status==='indexed' ? '#059669' : p.status==='processing' ? '#D97706' : '#EF4444';
    var sLabel = p.status==='indexed' ? 'Indexé' : p.status==='processing' ? 'En cours…' : (p.status==='error' ? 'Erreur' : 'En attente');
    return `
    <div class="pdf-card">
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="pdf-icon-wrap">📄</div>
        <div style="flex:1;overflow:hidden;">
          <div class="pdf-name" title="${esc(p.name)}">${esc(p.name)}</div>
          <div class="pdf-meta">${p.size} · ${p.date}</div>
        </div>
      </div>
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <div class="pdf-status">
            <div class="status-dot" style="background:${sColor};${p.status==='processing'?'animation:cpls 1s infinite;':''}"></div>
            <span style="font-size:11px;font-weight:600;color:${sColor};">${sLabel}</span>
          </div>
          ${p.status==='indexed' ? `<span style="font-size:11px;color:var(--rag-t4);">${p.chunk_count || 0} chunks</span>` : ''}
        </div>
        <div class="pdf-progress"><div class="pdf-progress-bar" style="width:${p.progress}%"></div></div>
      </div>
      <div class="pdf-actions">
        ${p.status==='pending' ? `
          <button class="btn-primary" style="flex:1;justify-content:center;padding:6px;" onclick="ingestPdf('${p.name}')">Indexer</button>
        ` : ''}
        ${p.status==='indexed' ? `
          <button class="btn-outline" style="flex:1;justify-content:center;padding:6px;" onclick="viewChunks('${p.id}')">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/></svg>
            Voir chunks
          </button>
        ` : ''}
        <button class="act-btn del" onclick="deletePdf('${p.id}')" title="Supprimer">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
        </button>
      </div>
    </div>`;
  }).join('');
}

async function ingestPdf(filename) {
  toast('Ingestion du PDF...');
  try {
    const res = await fetch(`${apiBase}/documents/ingest`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
      body: JSON.stringify({ filename, auto_index: true, auto_publish: true })
    });
    if (res.ok) {
      toast('PDF indexé avec succès');
      fetchPdfs();
      fetchStats();
      fetchArticles();
    } else {
      toast('Erreur lors de l\'ingestion');
    }
  } catch (e) { console.error(e); }
}

function filterPdfs(status) {
  var filtered = status ? pdfs.filter(p => p.status===status) : pdfs;
  renderPdfs(filtered);
}

function deletePdf(id) {
  toast('Suppression non disponible via API documents (supprimer l\'article lié)');
}

async function ingestAllPdfs() {
  if (!confirm('Ingérer tous les PDFs non indexés ?')) return;
  toast('Ingestion globale lancée...');
  try {
    const res = await fetch(`${apiBase}/documents/ingest-all`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
      body: JSON.stringify({ skip_existing: true, auto_index: true, auto_publish: true })
    });
    const data = await res.json();
    toast(`${data.ingested} document(s) ingéré(s)`);
    fetchPdfs();
    fetchStats();
    fetchArticles();
  } catch (e) { console.error(e); }
}

async function reindexAll() {
  if (!confirm('Ré-indexer tous les articles ?')) return;
  toast('Ré-indexation globale lancée...');
  try {
    await fetch(`${apiBase}/reindex-all`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
    });
    toast('Ré-indexation en cours (tâche de fond)');
  } catch (e) { console.error(e); }
}

function handleDrop(e) {
  e.preventDefault(); document.getElementById('dropZone').classList.remove('drag');
  // Open modal with the dropped file
  const file = e.dataTransfer.files[0];
  if (file && file.type === 'application/pdf') {
    openPdfModal(file);
  }
}

var selectedPdfFile = null;

function openPdfModal(file) {
  selectedPdfFile = null;
  document.getElementById('selectedFileRow').style.display = 'none';
  document.getElementById('modalDropContent').style.display = 'block';
  var btn = document.getElementById('pdfUploadBtn');
  btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed';
  document.getElementById('pdfInputModal').value = '';
  var m = document.getElementById('modalPdf');
  m.style.display = 'flex';
  // close on backdrop click
  m.onclick = function(e) { if (e.target === m) closePdfModal(); };
  if (file) selectPdfFile(file);
}

function closePdfModal() {
  document.getElementById('modalPdf').style.display = 'none';
}

function handleModalDrop(e) {
  e.preventDefault();
  document.getElementById('modalDropZone').style.borderColor = 'var(--rag-brd)';
  document.getElementById('modalDropZone').style.background = 'var(--rag-bg)';
  const file = e.dataTransfer.files[0];
  if (file) selectPdfFile(file);
}

function selectPdfFile(file) {
  if (!file || file.type !== 'application/pdf') {
    toast('Veuillez sélectionner un fichier PDF');
    return;
  }
  selectedPdfFile = file;
  document.getElementById('selectedFileName').textContent = file.name;
  document.getElementById('selectedFileSize').textContent = (file.size / (1024*1024)).toFixed(1) + ' MB';
  document.getElementById('selectedFileRow').style.display = 'flex';
  document.getElementById('modalDropContent').style.display = 'none';
  document.getElementById('pdfUploadBtn').disabled = false;
  document.getElementById('pdfUploadBtn').style.opacity = '1';
  document.getElementById('pdfUploadBtn').style.cursor = 'pointer';
}

function clearPdfFile() {
  selectedPdfFile = null;
  document.getElementById('selectedFileRow').style.display = 'none';
  document.getElementById('modalDropContent').style.display = 'block';
  document.getElementById('pdfInputModal').value = '';
  document.getElementById('pdfUploadBtn').disabled = true;
  document.getElementById('pdfUploadBtn').style.opacity = '.5';
  document.getElementById('pdfUploadBtn').style.cursor = 'not-allowed';
}

async function uploadSelectedPdf() {
  if (!selectedPdfFile) return;

  const btn = document.getElementById('pdfUploadBtn');
  const category = document.getElementById('pdfCategory').value;
  const language = document.getElementById('pdfLanguage').value;
  const autoPublish = document.getElementById('pdfAutoPublish').checked;
  const filename = selectedPdfFile.name;

  const spinSvg = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>`;
  const uploadSvg = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>`;

  function resetBtn() {
    btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer';
    btn.innerHTML = uploadSvg + ' Uploader &amp; Ingérer';
  }

  btn.disabled = true; btn.style.opacity = '.8'; btn.style.cursor = 'not-allowed';

  try {
    // ── ÉTAPE 1 : Upload du fichier ──────────────────────
    btn.innerHTML = spinSvg + ' Envoi du PDF...';
    const formData = new FormData();
    formData.append('file', selectedPdfFile);

    const uploadRes = await fetch(`${pdfBase}/documents/upload`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
      body: formData
    });

    if (!uploadRes.ok) {
      const errData = await uploadRes.json().catch(() => ({}));
      console.error('Upload error response:', uploadRes.status, errData);
      const detail = errData.detail;
      const msg = Array.isArray(detail)
        ? detail.map(e => (e.msg || e.message || JSON.stringify(e))).join(' | ')
        : (typeof detail === 'string' ? detail : errData.message || errData.error || ('Erreur upload ' + uploadRes.status));
      toast('❌ Upload ' + uploadRes.status + ': ' + msg);
      resetBtn(); return;
    }

    console.log('✅ Upload OK, starting ingest for:', filename);

    // ── ÉTAPE 2 : Ingestion (extraction + indexation) ────
    btn.innerHTML = spinSvg + ' Ingestion en cours...';
    const ingestRes = await fetch(`${apiBase}/documents/ingest`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        filename: filename,
        category: category,
        language: language,
        auto_publish: autoPublish,
        auto_index: true,
        tags: []
      })
    });

    if (!ingestRes.ok) {
      const errData = await ingestRes.json().catch(() => ({}));
      console.error('Ingest error response:', ingestRes.status, errData);
      const detail = errData.detail;
      const msg = Array.isArray(detail)
        ? detail.map(e => (e.msg || e.message || JSON.stringify(e))).join(' | ')
        : (typeof detail === 'string' ? detail : errData.message || errData.error || ('Erreur ingestion ' + ingestRes.status));
      toast('⚠️ Ingest ' + ingestRes.status + ': ' + msg);
      closePdfModal(); fetchPdfs(); return;
    }

    const result = await ingestRes.json();
    closePdfModal();
    toast(`✅ "${result.title}" ingéré — ${result.chunks_created} chunks`);
    // Switch to PDFs tab and refresh all data
    await fetchPdfs();
    await fetchStats();
    await fetchArticles();
    // Switch to PDFs tab to show the result
    var pdfTab = document.querySelector('.rag-tab[onclick*="pdfs"]');
    if (pdfTab) switchTab('pdfs', pdfTab);

  } catch (e) {
    console.error(e);
    toast('❌ Erreur réseau');
    resetBtn();
  }
}

async function handleFiles(files) {
  const file = files[0];
  if (!file || file.type !== 'application/pdf') return;

  toast('Téléchargement du PDF...');
  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await fetch(`${apiBase}/documents/upload`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
      body: formData
    });
    if (res.ok) {
      toast('PDF téléchargé, prêt pour l\'ingestion');
      fetchPdfs();
    } else {
      toast('Erreur lors du téléchargement');
    }
  } catch (e) { console.error(e); }
}

// ── MODALS ────────────────────────────────────────────────────
function showModal(name) {
  if (name==='article') { 
    currentEditId=null; 
    document.getElementById('artTitleIn').value=''; 
    document.getElementById('artContentIn').value=''; 
    document.getElementById('artTagsIn').value=''; 
    document.getElementById('modalArtTitle').textContent='Nouvel article'; 
    document.getElementById('chunkPreview').innerHTML='<div style="font-size:12px;color:var(--rag-t4);text-align:center;padding:16px;">Cliquez sur "Prévisualiser" pour voir le découpage en chunks</div>'; 
    
    // Reset field visibility
    document.querySelector('#modalArticle .modal-body > .form-group:first-child').style.display = 'block';
    document.querySelector('#modalArticle .form-row').style.display = 'grid';
    document.querySelector('#modalArticle .form-group:nth-child(3)').style.display = 'block';
    document.querySelector('#modalArticle .form-group:nth-child(4)').style.display = 'block';
    document.querySelector('#modalArticle .modal-foot .btn-primary').style.display = 'flex';

    document.getElementById('modalArticle').classList.add('on'); 
  }
  if (name==='import') document.getElementById('modalImport').classList.add('on');
}
function closeModal(id) { document.getElementById(id).classList.remove('on'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', function(e){ if(e.target===this)this.classList.remove('on'); }));

// ── HELPERS ───────────────────────────────────────────────────
function statusBadge(s) {
  var map = { 'PUBLISHED':'badge-green', 'DRAFT':'badge-orange', 'ARCHIVED':'badge-gray' };
  var labelMap = { 'PUBLISHED': 'Publié', 'DRAFT': 'Brouillon', 'ARCHIVED': 'Archivé' };
  return `<span class="badge ${map[s]||'badge-gray'}">${labelMap[s]||s}</span>`;
}
function catLabel(c) {
  var map = {
    'TECHNICAL': 'Technique & API',
    'BILLING': 'Facturation',
    'ACCOUNT': 'Compte & Connexion',
    'GENERAL': 'Général',
    'SECURITY': 'Sécurité',
    'TROUBLESHOOTING': 'Dépannage',
    'FAQ': 'FAQ',
    'POLICY': 'Politiques',
    'ONBOARDING': 'Onboarding',
    'FEATURE_GUIDE': 'Guide fonctionnalités'
  };
  return map[c] || c || 'Général';
}
function esc(t) { return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function toast(msg) {
  var el=document.getElementById('toast'); document.getElementById('toastMsg').textContent=msg;
  el.classList.add('show'); clearTimeout(el._to); el._to=setTimeout(()=>el.classList.remove('show'),3000);
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/rag.blade.php ENDPATH**/ ?>