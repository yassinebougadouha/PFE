<!-- resources/views/admin/escalations.blade.php 
React call  Blade HTTP 
callticketsApi.list({ page, size:20, status:'escalated' })GET /tickets?status=escalated&page=N&per_page=20
ticketsApi.get(id)GET /tickets/{id}
decisionsApi.escalateTicket(id)POST /decisions/{id}/
escalatedecisionsApi.suggestions(id)GET /decisions/{id}/
suggestionsticketsApi.update(id, {status, priority, escalation_flag})PATCH /tickets/{id}
conversationsApi.send(conversation_id, content)POST /conversations/{conversation_id}/messages
-->

@extends('layouts.dashboard')
@section('title', 'Escalations')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --ep: var(--color-primary, #6C63FF);
  --es: var(--color-secondary, #8B85FF);
  --ebg: #F8F9FF;
  --ebg2: #FFFFFF;
  --ebrd: #E8EAFF;
  --et1: #0D0F1A;
  --et2: #2D3047;
  --et3: #6B7280;
  --et4: #9CA3AF;
  --font: 'DM Sans', system-ui, sans-serif;
  --mono: 'DM Mono', monospace;
}
* { box-sizing: border-box; }
.esc-wrap { font-family: var(--font); background: var(--ebg); min-height: 0; display: flex; flex-direction: column; overflow: hidden; }

/* ── TOP BAR ── */
.esc-top {
  background: var(--ebg2); border-bottom: 1px solid var(--ebrd);
  padding: 13px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-shrink: 0;
}
.esc-title { font-size: 18px; font-weight: 700; color: var(--et1); display: flex; align-items: center; gap: 9px; }
.esc-icon { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg,#FEF2F2,#FECACA); display: flex; align-items: center; justify-content: center; }
.stat-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.stat-pill { padding: 5px 14px; border-radius: 99px; font-size: 12px; font-weight: 700; border: 1px solid; display: flex; align-items: center; gap: 5px; }
.btn-sm {
  padding: 6px 14px; border-radius: 8px; border: 1px solid var(--ebrd);
  background: var(--ebg2); font-size: 12px; font-weight: 600; color: var(--et2);
  cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: var(--font); transition: all .15s;
}
.btn-sm:hover { border-color: var(--ep); color: var(--ep); }
.btn-sm:disabled { opacity: .45; cursor: not-allowed; }
.btn-sm.primary { background: linear-gradient(135deg,var(--ep),var(--es)); color:#fff; border-color: var(--ep); box-shadow: 0 3px 10px color-mix(in srgb, var(--ep) 30%, transparent); }
.btn-sm.primary:hover { transform: translateY(-1px); }

/* ── 3-COL LAYOUT ── */
.esc-body { display: flex; flex: 1; overflow: hidden; min-height: 0; }

/* ── LEFT: TICKET LIST ── */
#escList { width: 300px; min-width: 300px; background: var(--ebg2); border-right: 1px solid var(--ebrd); display: flex; flex-direction: column; overflow: hidden; min-height: 0; }
.list-hdr { padding: 12px 14px 8px; border-bottom: 1px solid var(--ebrd); flex-shrink: 0; }
.list-search { display: flex; align-items: center; gap: 7px; background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 9px; padding: 6px 11px; }
.list-search input { flex: 1; border: none; background: transparent; outline: none; font-size: 12px; color: var(--et1); font-family: var(--font); }
.list-filters { display: flex; gap: 5px; margin-top: 8px; flex-wrap: wrap; }
.filter-chip { padding: 3px 10px; border-radius: 99px; font-size: 10px; font-weight: 700; border: 1px solid var(--ebrd); background: transparent; cursor: pointer; color: var(--et3); transition: all .13s; font-family: var(--font); }
.filter-chip.active, .filter-chip:hover { border-color: var(--ep); color: var(--ep); background: color-mix(in srgb, var(--ep) 8%, transparent); }
.esc-items { flex: 1; overflow-y: auto; }
.esc-items::-webkit-scrollbar { width: 3px; } .esc-items::-webkit-scrollbar-thumb { background: var(--ebrd); border-radius: 2px; }
.esc-item { padding: 12px 14px; border-bottom: 1px solid var(--ebrd); cursor: pointer; transition: background .12s; position: relative; border-left: 3px solid transparent; }
.esc-item:hover { background: #FAFAFF; }
.esc-item.active { background: color-mix(in srgb, var(--ep) 6%, transparent); border-left-color: var(--ep); }
.esc-item.urgent:not(.active) { border-left-color: #F59E0B; background: rgba(245,158,11,.04); }
.esc-item-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; gap: 6px; }
.esc-subject { font-size: 12px; font-weight: 600; color: var(--et1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.prio-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.esc-source { font-size: 10px; color: var(--et4); font-family: var(--mono); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.esc-preview { font-size: 11px; color: var(--et3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.esc-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 5px; }
.esc-date { font-size: 10px; color: var(--et4); }
.prio-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 99px; }
.list-pagination { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--ebrd); font-size: 11px; color: var(--et4); flex-shrink: 0; }
.pg-btn { width: 26px; height: 26px; border-radius: 7px; border: 1px solid var(--ebrd); background: var(--ebg); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--et3); font-family: var(--font); transition: all .13s; }
.pg-btn:disabled { opacity: .35; cursor: not-allowed; }
.pg-btn:not(:disabled):hover { border-color: var(--ep); color: var(--ep); }

/* ── MIDDLE: DETAIL ── */
#escDetail { flex: 1; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid var(--ebrd); min-height: 0; }
.detail-hdr { padding: 14px 20px; background: var(--ebg2); border-bottom: 1px solid var(--ebrd); flex-shrink: 0; }
.detail-subject { font-size: 15px; font-weight: 700; color: var(--et1); margin-bottom: 4px; }
.detail-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-source { font-size: 11px; color: var(--et4); font-family: var(--mono); }
.detail-tabs { display: flex; gap: 2px; padding: 0 20px; background: var(--ebg2); border-bottom: 1px solid var(--ebrd); flex-shrink: 0; }
.dtab { padding: 9px 14px; font-size: 12px; font-weight: 600; color: var(--et3); cursor: pointer; border-bottom: 2px solid transparent; background: none; border-top: none; border-left: none; border-right: none; font-family: var(--font); transition: all .13s; }
.dtab.active { color: var(--ep); border-bottom-color: var(--ep); }
.dtab:hover:not(.active) { color: var(--et2); }
.detail-body { flex: 1; overflow-y: auto; padding: 20px; min-height: 0; }
.detail-body::-webkit-scrollbar { width: 4px; } .detail-body::-webkit-scrollbar-thumb { background: var(--ebrd); }
.tab-panel { display: none; } .tab-panel.active { display: block; }
.msg-card { background: var(--ebg2); border: 1px solid var(--ebrd); border-radius: 12px; padding: 16px; margin-bottom: 14px; }
.msg-card.internal { border-color: rgba(56,189,248,.35); background: rgba(56,189,248,.04); }
.msg-from { font-size: 12px; font-weight: 700; color: var(--et1); margin-bottom: 4px; display: flex; align-items: center; gap: 7px; }
.msg-from-av { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content:center; font-size: 10px; font-weight: 700; flex-shrink: 0; }
.msg-body { font-size: 13px; color: var(--et2); line-height: 1.65; white-space: pre-wrap; }
.msg-time { font-size: 10px; color: var(--et4); margin-top: 8px; }
.decision-card { background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 12px; padding: 14px; margin-bottom: 12px; }
.decision-card.high, .decision-card.critical { border-color: rgba(239,68,68,.35); }
.decision-card.medium { border-color: rgba(245,158,11,.35); }
.decision-card.low { border-color: rgba(5,150,105,.35); }
.conf-bar { height: 3px; background: var(--ebrd); border-radius: 99px; overflow: hidden; margin-top: 6px; }
.conf-fill { height: 100%; border-radius: 99px; }
.metric-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
.metric-tile { background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 10px; padding: 10px 12px; }
.metric-label { font-size: 10px; font-weight: 700; color: var(--et4); text-transform: uppercase; letter-spacing: .06em; }
.metric-value { font-size: 17px; font-weight: 700; color: var(--et1); margin-top: 4px; }
.risk-box { border-radius: 12px; padding: 13px; margin-bottom: 12px; border: 1px solid; }
.risk-box.high, .risk-box.critical { border-color: rgba(239,68,68,.4); background: rgba(239,68,68,.06); }
.risk-box.medium { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.06); }
.risk-box.low { border-color: rgba(5,150,105,.4); background: rgba(5,150,105,.06); }
.action-list { list-style: none; padding: 0; margin: 0; }
.action-list li { display: flex; align-items: flex-start; gap: 8px; padding: 9px 12px; background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 9px; margin-bottom: 6px; font-size: 12px; color: var(--et2); line-height: 1.5; }
.ai-badge { padding: 2px 8px; border-radius: 99px; font-size: 9px; font-weight: 700; background: linear-gradient(135deg, var(--ep), var(--es)); color: #fff; display: inline-block; }
.section-title { font-size: 11px; font-weight: 700; color: var(--et4); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }

/* ── RIGHT: PANEL ── */
#escPanel { width: 280px; min-width: 280px; background: var(--ebg2); display: flex; flex-direction: column; overflow-y: auto; min-height: 0; }
#escPanel::-webkit-scrollbar { width: 3px; }
.panel-section { padding: 14px 16px; border-bottom: 1px solid var(--ebrd); }
.panel-sec-title { font-size: 10px; font-weight: 700; color: var(--et4); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px; }
.form-sel, .form-inp {
  width: 100%; padding: 8px 11px; border-radius: 9px; border: 1px solid var(--ebrd);
  background: var(--ebg); font-size: 12px; color: var(--et1); font-family: var(--font); outline: none; transition: border-color .15s;
}
.form-sel:focus, .form-inp:focus { border-color: var(--ep); }
.form-lbl { font-size: 11px; font-weight: 600; color: var(--et2); margin-bottom: 5px; display: block; }
.form-grp { margin-bottom: 10px; }
.suggested-reply { padding: 8px 10px; background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 8px; font-size: 11px; color: var(--et2); cursor: pointer; transition: all .13s; margin-bottom: 5px; line-height: 1.4; width: 100%; text-align: left; font-family: var(--font); }
.suggested-reply:hover { border-color: var(--ep); background: color-mix(in srgb, var(--ep) 5%, white); }
.reply-area { width: 100%; padding: 9px 11px; border-radius: 9px; border: 1px solid var(--ebrd); background: var(--ebg); font-size: 12px; color: var(--et1); font-family: var(--font); resize: vertical; min-height: 80px; outline: none; line-height: 1.6; }
.reply-area:focus { border-color: var(--ep); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ep) 10%, transparent); }
.reply-area:disabled { opacity: .5; cursor: not-allowed; }
.apply-btn {
  width: 100%; padding: 9px; border-radius: 9px; border: none; cursor: pointer;
  background: linear-gradient(135deg, var(--ep), var(--es)); color: #fff;
  font-size: 13px; font-weight: 700; font-family: var(--font); transition: all .18s;
  box-shadow: 0 3px 10px color-mix(in srgb, var(--ep) 30%, transparent);
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.apply-btn:hover:not(:disabled) { transform: translateY(-1px); }
.apply-btn:disabled { opacity: .4; cursor: not-allowed; box-shadow: none; }
.rerun-btn {
  width: 100%; padding: 7px; border-radius: 9px; border: 1px solid var(--ebrd);
  background: transparent; color: var(--et3); font-size: 12px; font-weight: 600;
  font-family: var(--font); cursor: pointer; transition: all .15s; margin-top: 6px;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.rerun-btn:hover:not(:disabled) { border-color: var(--ep); color: var(--ep); }
.rerun-btn:disabled { opacity: .4; cursor: not-allowed; }
.panel-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 40px 20px; text-align: center; color: var(--et4); gap: 10px; }
.panel-empty-icon { font-size: 36px; }
.panel-empty-title { font-size: 14px; font-weight: 600; color: var(--et2); }
.spotlight-box { border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid; }
.spotlight-box .prog-row { margin-bottom: 10px; }
.prog-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--et3); margin-bottom: 4px; }
.prog-track { height: 5px; background: var(--ebrd); border-radius: 99px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 99px; transition: width .6s; }
.curr-state-box { background: var(--ebg); border: 1px solid var(--ebrd); border-radius: 9px; padding: 10px; margin-bottom: 10px; }

/* ── STATES ── */
.loading-row { display: flex; align-items: center; gap: 9px; padding: 18px; font-size: 12px; color: var(--et3); }
.spin { width: 14px; height: 14px; border: 2px solid var(--ebrd); border-top-color: var(--ep); border-radius: 50%; animation: spin .7s linear infinite; flex-shrink: 0; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px; text-align: center; color: var(--et4); gap: 8px; }
.empty-state .ei { font-size: 28px; }
.empty-state .et { font-size: 13px; font-weight: 600; color: var(--et2); }
.empty-state .es { font-size: 11px; }
.skeleton { background: linear-gradient(90deg, var(--ebrd) 25%, color-mix(in srgb, var(--ebrd) 60%, white) 50%, var(--ebrd) 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 6px; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.sk-line { height: 11px; margin-bottom: 8px; }

/* ── TOAST ── */
.toast { position:fixed;bottom:22px;right:22px;z-index:9999;background:#0D0F1A;color:#fff;padding:11px 16px;border-radius:11px;font-size:12px;font-family:var(--font);display:flex;align-items:center;gap:7px;box-shadow:0 8px 28px rgba(0,0,0,.22);opacity:0;transform:translateY(8px);transition:all .22s;pointer-events:none; }
.toast.show { opacity:1;transform:translateY(0); }

@keyframes cpls { 0%,100%{opacity:1}50%{opacity:.3} }
</style>

<div class="esc-wrap">

  {{-- TOP BAR --}}
  <div class="esc-top">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
      <div class="esc-title">
        <div class="esc-icon">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        Escalations
      </div>
      <div class="stat-pills" id="statPills">
        <div class="stat-pill" style="background:#FEF2F2;border-color:#FECACA;color:#EF4444;">
          <div style="width:6px;height:6px;border-radius:50%;background:#EF4444;animation:cpls 1s infinite;"></div>
          <span id="statTotal">—</span> total
        </div>
        <div class="stat-pill" style="background:#FFF7ED;border-color:#FED7AA;color:#D97706;">⬆ <span id="statUrgent">—</span> Urgentes</div>
        <div class="stat-pill" style="background:#FEF2F2;border-color:#FECACA;color:#EF4444;">🔴 <span id="statCritical">—</span> Critiques</div>
      </div>
    </div>
    <div style="display:flex;gap:6px;">
      <button class="btn-sm" id="btnRefresh" onclick="loadTickets()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Refresh
      </button>
    </div>
  </div>

  <div class="esc-body">

    {{-- LEFT: LIST --}}
    <div id="escList">
      <div class="list-hdr">
        <div class="list-search">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--et4)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" placeholder="Rechercher sujet, ID, priorité…" id="escSearch" oninput="filterLocal()">
        </div>
        <div class="list-filters" id="filterChips">
          <button class="filter-chip active" onclick="setMode('all',this)">Tous</button>
          <button class="filter-chip" onclick="setMode('urgent',this)">Urgents</button>
          <button class="filter-chip" onclick="setMode('critical',this)">Critiques</button>
        </div>
      </div>
      <div class="esc-items" id="escItems">
        <div class="loading-row"><div class="spin"></div>Chargement…</div>
      </div>
      <div class="list-pagination">
        <span id="pgLabel">Page 1</span>
        <div style="display:flex;gap:4px;">
          <button class="pg-btn" id="pgPrev" onclick="changePage(-1)" disabled>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button class="pg-btn" id="pgNext" onclick="changePage(1)" disabled>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>

    {{-- MIDDLE: DETAIL --}}
    <div id="escDetail">
      <div id="detailEmpty" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--et4);">
        <div style="font-size:40px;">🔔</div>
        <div style="font-size:14px;font-weight:600;color:var(--et2);">Sélectionnez une escalation</div>
        <div style="font-size:12px;">Choisissez un ticket dans la liste</div>
      </div>
      <div id="detailContent" style="display:none;flex-direction:column;flex:1;min-height:0;overflow:hidden;">
        <div class="detail-hdr">
          <div class="detail-subject" id="detailSubject"></div>
          <div class="detail-meta">
            <span class="detail-source" id="detailSource"></span>
            <span id="detailPrioBadge"></span>
            <span id="detailDate" style="font-size:11px;color:var(--et4);"></span>
          </div>
        </div>
        <div class="detail-tabs">
          <button class="dtab active" onclick="switchTab('overview',this)">Overview</button>
          <button class="dtab" onclick="switchTab('timeline',this)">Timeline</button>
          <button class="dtab" onclick="switchTab('decisions',this)">Decision trail</button>
        </div>
        <div class="detail-body">
          <div class="tab-panel active" id="tab-overview"></div>
          <div class="tab-panel" id="tab-timeline"></div>
          <div class="tab-panel" id="tab-decisions"></div>
        </div>
      </div>
    </div>

    {{-- RIGHT: PANEL --}}
    <div id="escPanel">
      <div id="panelEmpty" class="panel-empty">
        <div class="panel-empty-icon">⚡</div>
        <div class="panel-empty-title">Panneau IA</div>
        <div style="font-size:12px;">Sélectionnez une escalation pour voir les recommandations IA</div>
      </div>
      <div id="panelContent" style="display:none;flex-direction:column;min-height:0;overflow-y:auto;">

        {{-- Spotlight --}}
        <div class="panel-section" id="spotlightSection"></div>

        {{-- Override & resolve --}}
        <div class="panel-section">
          <div class="panel-sec-title">Override & Résoudre</div>
          <div class="curr-state-box" id="currStateBadges" style="font-size:11px;color:var(--et3);">—</div>
          <div class="form-grp">
            <label class="form-lbl">Statut</label>
            <select class="form-sel" id="overrideStatus">
              <option value="open">Open</option>
              <option value="in_progress">In progress</option>
              <option value="resolved" selected>Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div class="form-grp">
            <label class="form-lbl">Priorité</label>
            <select class="form-sel" id="overridePriority">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>
          <div style="display:flex;gap:6px;">
            <button class="apply-btn" id="applyBtn" onclick="applyOverride()" style="flex:1;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
              Apply
            </button>
            <button class="rerun-btn" id="rerunBtn" onclick="rerunAI()" style="width:auto;padding:0 12px;margin:0;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              Re-run
            </button>
          </div>
        </div>

        {{-- Suggested replies --}}
        <div class="panel-section">
          <div class="panel-sec-title">Réponses suggérées <span class="ai-badge">IA</span></div>
          <div id="sugReplies" style="margin-bottom:10px;">
            <div style="font-size:11px;color:var(--et4);">—</div>
          </div>
          <div class="form-grp">
            <label class="form-lbl">Réponse au client</label>
            <textarea class="reply-area" id="replyArea" placeholder="Rédigez ou sélectionnez une réponse…" disabled></textarea>
          </div>
          <p id="replyHint" style="font-size:10px;color:var(--et4);margin-bottom:6px;"></p>
          <button class="apply-btn" id="sendBtn" onclick="sendReply()" disabled>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Envoyer
          </button>
        </div>

      </div>
    </div>

  </div>
</div>

<div class="toast" id="toast">
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── STATE ─────────────────────────────────────────────────────────────────────
var S = {
  tickets: [],          // raw list from API
  filtered: [],         // after search / mode filter
  selectedId: null,
  ticket: null,         // full ticket object
  pkg: null,            // EscalationPackage from /decisions/{id}/escalate
  suggestions: [],      // string[] from /decisions/{id}/suggestions
  page: 1,
  totalPages: 1,
  total: 0,
  mode: 'all',          // 'all' | 'urgent' | 'critical'
  pkgLoading: false,
  listLoading: false,
};
var PAGE_SIZE = 20;

// ── BOOT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () { loadTickets(); });

// ── LIST: GET /tickets?status=escalated&page=N&per_page=20 ────────────────────
function loadTickets() {
  S.listLoading = true;
  document.getElementById('escItems').innerHTML = '<div class="loading-row"><div class="spin"></div>Chargement…</div>';
  document.getElementById('btnRefresh').disabled = true;

  fetch('/api/v1/tickets/glpi-list?range=' + ((S.page - 1) * PAGE_SIZE) + '-' + (S.page * PAGE_SIZE - 1), {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    S.tickets    = data.tickets || data.data || [];
    S.total      = data.total || S.tickets.length;
    S.totalPages = Math.max(1, Math.ceil(S.total / PAGE_SIZE));
    S.listLoading = false;
    document.getElementById('btnRefresh').disabled = false;
    applyFilter();
    updateStatPills();
    updatePagination();
    // Auto-select first if nothing selected
    if (!S.selectedId && S.filtered.length) selectTicket(S.filtered[0].id);
  })
  .catch(function (err) {
    S.listLoading = false;
    document.getElementById('btnRefresh').disabled = false;
    document.getElementById('escItems').innerHTML = '<div class="empty-state"><div class="ei">⚠️</div><div class="et">Erreur de chargement</div><div class="es">' + esc(String(err)) + '</div></div>';
  });
}

function changePage(delta) {
  S.page = Math.max(1, Math.min(S.totalPages, S.page + delta));
  loadTickets();
}

function updatePagination() {
  document.getElementById('pgLabel').textContent = 'Page ' + S.page + ' / ' + S.totalPages;
  document.getElementById('pgPrev').disabled = S.page <= 1;
  document.getElementById('pgNext').disabled = S.page >= S.totalPages;
}

function updateStatPills() {
  var urgent   = S.tickets.filter(function (t) { return t.priority === 'high' || t.priority === 'critical'; }).length;
  var critical = S.tickets.filter(function (t) { return t.priority === 'critical'; }).length;
  document.getElementById('statTotal').textContent    = S.total;
  document.getElementById('statUrgent').textContent   = urgent;
  document.getElementById('statCritical').textContent = critical;
}

// ── LOCAL FILTER ──────────────────────────────────────────────────────────────
function setMode(mode, btn) {
  S.mode = mode;
  document.querySelectorAll('.filter-chip').forEach(function (b) { b.classList.remove('active'); });
  btn.classList.add('active');
  applyFilter();
}

function filterLocal() { applyFilter(); }

function applyFilter() {
  var q = (document.getElementById('escSearch').value || '').toLowerCase().trim();
  S.filtered = S.tickets.filter(function (t) {
    if (S.mode === 'urgent'   && t.priority !== 'high' && t.priority !== 'critical') return false;
    if (S.mode === 'critical' && t.priority !== 'critical') return false;
    if (!q) return true;
    return [t.subject, t.description, t.id, t.priority, t.status, t.channel_source].some(function (f) {
      return String(f || '').toLowerCase().includes(q);
    });
  });
  renderList();
}

// ── RENDER LIST ───────────────────────────────────────────────────────────────
function renderList() {
  var el = document.getElementById('escItems');
  if (!S.filtered.length) {
    el.innerHTML = '<div class="empty-state"><div class="ei">🔍</div><div class="et">Aucun ticket trouvé</div><div class="es">Essayez un filtre différent.</div></div>';
    return;
  }
  el.innerHTML = S.filtered.map(function (t) {
    var pc      = prioColor(t.priority);
    var urgent  = t.priority === 'high' || t.priority === 'critical';
    var active  = S.selectedId === t.id;
    var relTime = formatRelative(t.updated_at);
    return '<div class="esc-item' + (active ? ' active' : '') + (urgent && !active ? ' urgent' : '') + '" onclick="selectTicket(\'' + esc(t.id) + '\')">' +
      '<div class="esc-item-top">' +
        '<div class="esc-subject">' + esc(t.subject || '—') + '</div>' +
        '<div class="prio-dot" style="background:' + pc + ';' + (urgent ? 'box-shadow:0 0 5px ' + pc + '80;' : '') + '"></div>' +
      '</div>' +
      '<div class="esc-source">' + esc(t.channel_source || t.id || '') + '</div>' +
      '<div class="esc-preview">' + esc(summarize(t.description, 80)) + '</div>' +
      '<div class="esc-meta">' +
        '<span class="esc-date">' + relTime + '</span>' +
        '<span class="prio-badge" style="background:' + pc + '18;color:' + pc + ';">' + esc(t.priority || '—') + '</span>' +
      '</div>' +
    '</div>';
  }).join('');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function getFapiId(id) {
  var t = S.tickets.find(function (t) { return t.glpi_ticket_id == id || t.id == id; });
  return t ? (t.fastapi_ticket_id || null) : null;
}

// ── SELECT TICKET ─────────────────────────────────────────────────────────────
// 1. GET /tickets/{id}
// 2. POST /decisions/{id}/escalate   → EscalationPackage
// 3. GET  /decisions/{id}/suggestions → { suggestions: string[] }
function selectTicket(id) {
  if (S.selectedId === id) return;
  S.selectedId = id;
  S.ticket     = null;
  S.pkg        = null;
  S.suggestions = [];
  renderList();
  showDetailLoading();
  showPanelLoading();

  var fapiId = getFapiId(id);
  if (!fapiId) {
    toast('Ce ticket n\'a pas de correspondance locale — certaines fonctionnalités ne sont pas disponibles.');
    document.getElementById('detailEmpty').style.display   = 'flex';
    document.getElementById('detailContent').style.display = 'none';
    document.getElementById('panelEmpty').style.display    = 'flex';
    return;
  }

  // 1 — Ticket detail
  fetch('/api/v1/tickets/' + encodeURIComponent(fapiId), {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
  })
  .then(function (r) { return r.json(); })
  .then(function (t) {
    S.ticket = t;
    renderDetailHeader(t);
    renderCurrState(t);
    // Pre-fill override selects
    document.getElementById('overrideStatus').value   = 'resolved';
    document.getElementById('overridePriority').value = t.priority || 'medium';
    // Enable / disable reply
    var hasConv = Boolean(t.conversation_id);
    document.getElementById('replyArea').disabled = !hasConv;
    document.getElementById('sendBtn').disabled   = !hasConv;
    document.getElementById('replyHint').textContent = hasConv
      ? 'Envoi via la conversation liée.'
      : 'Réponse désactivée — aucune conversation liée à ce ticket.';
  })
  .catch(function (err) {
    toast('Erreur ticket : ' + err.message);
  });

  // 2 — Escalation package (try backend first, fallback to client-side)
  S.pkgLoading = true;
  (function loadPkg() {
    fetch('/api/v1/decision-engine/escalate/' + encodeURIComponent(fapiId), {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({})
    })
    .then(function (r) {
      if (!r.ok) throw new Error('Backend indisponible');
      return r.json();
    })
    .then(function (pkg) {
      S.pkg = pkg;
      S.pkgLoading = false;
      renderOverview(pkg);
      renderTimeline(pkg);
      renderDecisions(pkg);
      renderSpotlight(pkg);
    })
    .catch(function () {
      // Build package from local ticket data
      var t = S.ticket;
      if (!t) { S.pkgLoading = false; return; }
      var summary = '=== ESCALATION SUMMARY ===\nTicket: ' + t.subject + '\nStatus: ' + t.status + '\nPriority: ' + t.priority + '\n\n--- Description ---\n' + (t.description || '').slice(0, 1000) + '\n\n=== ACTION REQUIRED ===\nThis ticket requires human review.';
      var risk = (t.priority === 'critical' || t.priority === 'high') ? 'high' : 'medium';
      var pkg = {
        ticket_id: t.id,
        ticket_subject: t.subject,
        ticket_description: (t.description || '').slice(0, 2000),
        intent_category: 'general',
        confidence_score: 0.5,
        risk_score: t.priority === 'critical' ? 0.8 : t.priority === 'high' ? 0.6 : 0.3,
        risk_level: risk,
        conversation_history: [],
        previous_decisions: [],
        summary: summary,
        recommended_actions: [
          'Review the ticket details carefully',
          t.priority === 'critical' || t.priority === 'high' ? 'Prioritize immediate response' : 'Contact the customer for clarification if needed',
          'Update the ticket status when resolved'
        ]
      };
      S.pkg = pkg;
      S.pkgLoading = false;
      renderOverview(pkg);
      renderSpotlight(pkg);
    });
  })();

  // 3 — Suggestions
  fetch('/api/v1/decision-engine/suggestions/' + encodeURIComponent(fapiId), {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
  })
  .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
  .then(function (data) {
    S.suggestions = data.suggestions || [];
    if (data.suggestions && data.suggestions.length) {
      renderSuggestionButtons(data.suggestions);
    }
  })
  .catch(function () {
    S.suggestions = [];
  });
}

// ── DETAIL: HEADER ────────────────────────────────────────────────────────────
function renderDetailHeader(t) {
  document.getElementById('detailEmpty').style.display    = 'none';
  document.getElementById('detailContent').style.display = 'flex';
  document.getElementById('detailSubject').textContent   = t.subject || '—';
  document.getElementById('detailSource').textContent    = t.channel_source || t.id || '';
  document.getElementById('detailDate').textContent      = formatRelative(t.updated_at);
  var pc = prioColor(t.priority);
  document.getElementById('detailPrioBadge').innerHTML =
    '<span class="prio-badge" style="background:' + pc + '18;color:' + pc + ';">' + esc(t.priority || '—') + '</span>' +
    '&nbsp;<span class="prio-badge" style="background:var(--ebg);border:1px solid var(--ebrd);color:var(--et3);">' + esc(t.status || '—') + '</span>';
  // Switch to overview tab
  switchTab('overview', document.querySelector('.dtab'));
  document.getElementById('panelEmpty').style.display    = 'none';
  document.getElementById('panelContent').style.display = 'flex';
}

function showDetailLoading() {
  document.getElementById('detailEmpty').style.display    = 'none';
  document.getElementById('detailContent').style.display = 'flex';
  document.getElementById('detailSubject').textContent   = 'Chargement…';
  document.getElementById('detailSource').textContent    = '';
  document.getElementById('detailDate').textContent      = '';
  document.getElementById('detailPrioBadge').innerHTML   = '';
  ['overview','timeline','decisions'].forEach(function (id) {
    document.getElementById('tab-' + id).innerHTML =
      skLines([85, 60, 90, 50, 75]);
  });
}

function showPanelLoading() {
  document.getElementById('panelEmpty').style.display    = 'none';
  document.getElementById('panelContent').style.display = 'flex';
  document.getElementById('spotlightSection').innerHTML  = skLines([80, 60, 90]);
  document.getElementById('sugReplies').innerHTML        = skLines([90, 70]);
  document.getElementById('currStateBadges').textContent = '—';
}

function skLines(widths) {
  return widths.map(function (w) {
    return '<div class="skeleton sk-line" style="width:' + w + '%;"></div>';
  }).join('');
}

// ── TAB SWITCHER ──────────────────────────────────────────────────────────────
function switchTab(name, btn) {
  document.querySelectorAll('.dtab').forEach(function (b) { b.classList.remove('active'); });
  document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
  if (btn) btn.classList.add('active');
  var panel = document.getElementById('tab-' + name);
  if (panel) panel.classList.add('active');
}

// ── OVERVIEW TAB ──────────────────────────────────────────────────────────────
function renderOverview(pkg) {
  var parsed = parseSummary(pkg.summary);
  var pc     = riskColor(pkg.risk_level);
  var html   = '';

  // Risk warning banner
  if (pkg.risk_level === 'high' || pkg.risk_level === 'critical') {
    html += '<div class="risk-box ' + esc(pkg.risk_level) + '" style="margin-bottom:14px;">' +
      '<div style="display:flex;align-items:center;gap:7px;margin-bottom:5px;">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="' + pc + '" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' +
        '<span style="font-size:12px;font-weight:700;color:' + pc + ';">Revue immédiate recommandée</span>' +
      '</div>' +
      '<div style="font-size:11px;color:var(--et2);line-height:1.55;">Risque <strong>' + esc(pkg.risk_level) + '</strong> avec ' + pct(pkg.confidence_score) + ' de confiance IA. Confirmez l\'impact client avant de clore le dossier.</div>' +
    '</div>';
  }

  // Metrics
  html += '<div class="metric-grid">' +
    metricTile('Confiance IA',    pct(pkg.confidence_score)) +
    metricTile('Score de risque', pct(pkg.risk_score)) +
    metricTile('Événements',      (pkg.conversation_history || []).length) +
  '</div>';

  // Summary block
  if (parsed) {
    html += '<div style="margin-bottom:14px;">';
    if (parsed.description) {
      html += '<div class="section-title">Description</div>';
      html += '<div style="background:var(--ebg);border:1px solid var(--ebrd);border-radius:10px;padding:12px;font-size:12px;color:var(--et2);line-height:1.6;white-space:pre-wrap;margin-bottom:10px;">' + esc(parsed.description) + '</div>';
    }
    if (parsed.riskFactors && parsed.riskFactors.length) {
      html += '<div class="section-title">Facteurs de risque</div>';
      html += '<div style="border:1px solid rgba(245,158,11,.4);background:rgba(245,158,11,.06);border-radius:10px;padding:12px;margin-bottom:10px;">';
      parsed.riskFactors.forEach(function (f) {
        html += '<div style="display:flex;align-items:flex-start;gap:7px;font-size:12px;color:var(--et2);line-height:1.5;margin-bottom:6px;"><span style="color:#D97706;flex-shrink:0;margin-top:1px;">⚠</span>' + esc(f) + '</div>';
      });
      html += '</div>';
    }
    if (parsed.actionRequired) {
      html += '<div class="section-title">Action requise</div>';
      html += '<div style="border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.05);border-radius:10px;padding:12px;font-size:12px;font-weight:600;color:var(--et1);line-height:1.55;margin-bottom:10px;">' + esc(parsed.actionRequired) + '</div>';
    }
    html += '</div>';
  } else if (pkg.summary) {
    html += '<div class="section-title">Résumé IA</div>';
    html += '<div style="background:var(--ebg);border:1px solid var(--ebrd);border-radius:10px;padding:12px;font-size:12px;color:var(--et2);line-height:1.6;white-space:pre-wrap;margin-bottom:14px;">' + esc(pkg.summary) + '</div>';
  }

  // Recommended actions
  if (pkg.recommended_actions && pkg.recommended_actions.length) {
    html += '<div class="section-title">Actions recommandées <span class="ai-badge">IA</span></div>';
    html += '<ul class="action-list">';
    pkg.recommended_actions.forEach(function (a) {
      html += '<li><span style="color:#059669;flex-shrink:0;margin-top:1px;">✓</span>' + esc(a) + '</li>';
    });
    html += '</ul>';
  }

  document.getElementById('tab-overview').innerHTML = html;
}

function metricTile(label, value) {
  return '<div class="metric-tile"><div class="metric-label">' + esc(label) + '</div><div class="metric-value">' + esc(String(value)) + '</div></div>';
}

// ── TIMELINE TAB ──────────────────────────────────────────────────────────────
function renderTimeline(pkg) {
  var history = pkg.conversation_history || [];
  var html = '';
  if (!history.length) {
    html = '<div class="empty-state"><div class="ei">🕐</div><div class="et">Aucun historique</div><div class="es">Aucun message capturé pour ce ticket.</div></div>';
  } else {
    html += '<div style="font-size:11px;color:var(--et4);margin-bottom:14px;">' + history.length + ' événement(s)</div>';
    history.forEach(function (m) {
      var internal = Boolean(m.is_internal);
      html += '<div class="msg-card' + (internal ? ' internal' : '') + '">' +
        '<div class="msg-from">' +
          '<div class="msg-from-av" style="background:' + (internal ? 'rgba(56,189,248,.15)' : '#EEF2FF') + ';color:' + (internal ? '#0ea5e9' : '#4F46E5') + ';">' + (m.sender_id ? esc(String(m.sender_id)[0].toUpperCase()) : '?') + '</div>' +
          esc(m.sender_id || (internal ? 'Note interne' : 'Client')) +
          '<span style="font-size:10px;font-weight:400;color:var(--et4);margin-left:auto;">' + (internal ? '<span style="padding:1px 6px;border-radius:4px;background:rgba(56,189,248,.12);font-size:9px;font-weight:700;color:#0ea5e9;">INTERNE</span>&nbsp;' : '') + formatDateTime(m.created_at) + '</span>' +
        '</div>' +
        '<div class="msg-body">' + esc(m.content || '') + '</div>' +
      '</div>';
    });
  }
  document.getElementById('tab-timeline').innerHTML = html;
}

// ── DECISIONS TAB ─────────────────────────────────────────────────────────────
function renderDecisions(pkg) {
  var decisions = pkg.previous_decisions || [];
  var html = '';
  if (!decisions.length) {
    html = '<div class="empty-state"><div class="ei">📋</div><div class="et">Aucune décision antérieure</div><div class="es">Aucune tentative enregistrée pour ce ticket.</div></div>';
  } else {
    html += '<div style="font-size:11px;color:var(--et4);margin-bottom:14px;">' + decisions.length + ' tentative(s)</div>';
    decisions.forEach(function (d) {
      var rl  = normalizeRisk(d.risk_level);
      var pc  = riskColor(rl);
      var conf = d.confidence_score !== undefined && d.confidence_score !== null ? pct(d.confidence_score) : '—';
      html += '<div class="decision-card ' + esc(rl) + '">' +
        '<div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:10px;">' +
          '<span class="prio-badge" style="background:' + pc + '18;color:' + pc + ';">' + esc(rl) + '</span>' +
          '<span class="prio-badge" style="background:var(--ebg);border:1px solid var(--ebrd);color:var(--et3);">Outcome: ' + esc(d.decision_outcome || '—') + '</span>' +
          '<span style="font-size:10px;color:var(--et4);">Confiance: ' + conf + '</span>' +
          (d.created_at ? '<span style="font-size:10px;color:var(--et4);margin-left:auto;">' + formatDateTime(d.created_at) + '</span>' : '') +
        '</div>' +
        '<div style="height:1px;background:var(--ebrd);margin-bottom:10px;"></div>' +
        '<div style="font-size:12px;color:var(--et3);line-height:1.55;">' + esc(d.reasoning || '—') + '</div>' +
        (d.confidence_score !== undefined && d.confidence_score !== null
          ? '<div class="conf-bar" style="margin-top:8px;"><div class="conf-fill" style="width:' + Math.round(d.confidence_score * 100) + '%;background:' + pc + ';"></div></div>'
          : '') +
      '</div>';
    });
  }
  document.getElementById('tab-decisions').innerHTML = html;
}

// ── SPOTLIGHT (right panel) ───────────────────────────────────────────────────
function renderSpotlight(pkg) {
  var pc   = riskColor(pkg.risk_level);
  var rs   = Math.round((pkg.risk_score || 0) * 100);
  var cs   = Math.round((pkg.confidence_score || 0) * 100);
  var primary = (pkg.recommended_actions || [])[0] || '';

  var html = '<div class="section-title">Escalation spotlight <span class="ai-badge">IA</span></div>' +
    '<div class="spotlight-box ' + esc(pkg.risk_level || 'medium') + '">' +
      '<div class="prog-row">' +
        '<div class="prog-label"><span>Niveau de risque</span><span style="font-weight:700;color:' + pc + ';">' + esc((pkg.risk_level || '').toUpperCase()) + '</span></div>' +
        '<div class="prog-track"><div class="prog-fill" style="width:' + rs + '%;background:' + pc + ';"></div></div>' +
      '</div>' +
      '<div class="prog-row">' +
        '<div class="prog-label"><span>Confiance IA</span><span style="font-weight:700;">' + cs + '%</span></div>' +
        '<div class="prog-track"><div class="prog-fill" style="width:' + cs + '%;background:var(--ep);"></div></div>' +
      '</div>' +
      (primary
        ? '<div style="background:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.4);border-radius:8px;padding:9px;margin-top:6px;">' +
            '<div style="font-size:10px;font-weight:700;color:var(--et4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Meilleure prochaine action</div>' +
            '<div style="font-size:12px;font-weight:600;color:var(--et1);line-height:1.5;">' + esc(primary) + '</div>' +
          '</div>'
        : '') +
    '</div>';
  document.getElementById('spotlightSection').innerHTML = html;
}

function renderCurrState(t) {
  var pc = prioColor(t.priority);
  document.getElementById('currStateBadges').innerHTML =
    '<div style="font-size:10px;font-weight:700;color:var(--et4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">État actuel</div>' +
    '<span class="prio-badge" style="background:var(--ebg);border:1px solid var(--ebrd);color:var(--et3);">' + esc(t.status || '—') + '</span>&nbsp;' +
    '<span class="prio-badge" style="background:' + pc + '18;color:' + pc + ';">' + esc(t.priority || '—') + '</span>';
}

// ── SUGGESTIONS ───────────────────────────────────────────────────────────────
function renderSuggestions() {
  var el = document.getElementById('sugReplies');
  if (!S.suggestions.length) {
    el.innerHTML = '<div style="font-size:11px;color:var(--et4);">Aucune suggestion disponible.</div>';
    return;
  }
  el.innerHTML = S.suggestions.slice(0, 4).map(function (s) {
    return '<button class="suggested-reply" onclick="insertReply(this)">' + esc(s) + '</button>';
  }).join('');
}

function insertReply(btn) {
  document.getElementById('replyArea').value = btn.textContent;
  toast('Réponse insérée');
}

// ── APPLY OVERRIDE: PATCH /tickets/{id} ──────────────────────────────────────
function applyOverride() {
  if (!S.selectedId) return;
  var status   = document.getElementById('overrideStatus').value;
  var priority = document.getElementById('overridePriority').value;
  document.getElementById('applyBtn').disabled = true;

  fetch('/api/v1/tickets/' + encodeURIComponent(getFapiId(S.selectedId)), {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({
      status: status,
      priority: priority,
      escalation_flag: status === 'resolved' || status === 'closed' ? false : true,
    })
  })
  .then(function (r) { if (!r.ok) return r.json().then(function (e) { throw new Error(e.message || 'Erreur'); }); return r.json(); })
  .then(function () {
    toast('Override appliqué : ' + status + ' / ' + priority + ' ✓');
    document.getElementById('applyBtn').disabled = false;
    // Reset and reload
    S.selectedId = null;
    S.ticket     = null;
    document.getElementById('detailEmpty').style.display    = 'flex';
    document.getElementById('detailContent').style.display  = 'none';
    document.getElementById('panelEmpty').style.display     = 'flex';
    document.getElementById('panelContent').style.display   = 'none';
    loadTickets();
  })
  .catch(function (err) {
    document.getElementById('applyBtn').disabled = false;
    toast('Erreur override : ' + err.message);
  });
}

// ── SEND REPLY: POST /conversations/{id}/messages ─────────────────────────────
function sendReply() {
  var txt = document.getElementById('replyArea').value.trim();
  if (!txt) { toast('Rédigez une réponse d\'abord'); return; }
  if (!S.ticket || !S.ticket.conversation_id) { toast('Aucune conversation liée à ce ticket.'); return; }

  document.getElementById('sendBtn').disabled = true;

  fetch('/api/v1/conversations/' + encodeURIComponent(S.ticket.conversation_id) + '/messages', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ content: txt })
  })
  .then(function (r) { if (!r.ok) return r.json().then(function (e) { throw new Error(e.message || 'Erreur'); }); return r.json(); })
  .then(function () {
    document.getElementById('replyArea').value = '';
    document.getElementById('sendBtn').disabled = false;
    toast('Réponse envoyée au client ✓');
    // Refresh timeline if package already loaded
    if (S.pkg) {
      S.pkg.conversation_history = S.pkg.conversation_history || [];
      S.pkg.conversation_history.push({ content: txt, is_internal: false, sender_id: 'You', created_at: new Date().toISOString() });
      renderTimeline(S.pkg);
    }
  })
  .catch(function (err) {
    document.getElementById('sendBtn').disabled = false;
    toast('Erreur envoi : ' + err.message);
  });
}

// ── RE-RUN AI: POST /decisions/{id}/escalate ──────────────────────────────────
function rerunAI() {
  if (!S.selectedId) return;
  document.getElementById('rerunBtn').disabled = true;
  S.pkgLoading = true;
  document.getElementById('tab-overview').innerHTML = '<div class="loading-row"><div class="spin"></div>Analyse IA en cours…</div>';
  document.getElementById('spotlightSection').innerHTML = skLines([80, 60, 90]);

  fetch('/api/v1/decision-engine/escalate/' + encodeURIComponent(getFapiId(S.selectedId)), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({})
  })
  .then(function (r) { if (!r.ok) return r.json().then(function (e) { throw new Error(e.message || 'Erreur'); }); return r.json(); })
  .then(function (pkg) {
    S.pkg = pkg;
    S.pkgLoading = false;
    document.getElementById('rerunBtn').disabled = false;
    renderOverview(pkg);
    renderTimeline(pkg);
    renderDecisions(pkg);
    renderSpotlight(pkg);
    toast('Analyse IA complétée ✓');
  })
  .catch(function (err) {
    S.pkgLoading = false;
    document.getElementById('rerunBtn').disabled = false;
    toast('Re-run IA échoué : ' + err.message);
  });
}

// ── SUMMARY PARSER (mirrors parseEscalationSummary from React) ────────────────
function parseSummary(text) {
  if (!text || !text.trim()) return null;
  var parsed = { riskFactors: [] };
  var descLines = [], actionLines = [];
  var section = 'none';
  var lines = text.split(/\r?\n/);
  var hasContent = false;

  for (var i = 0; i < lines.length; i++) {
    var raw  = lines[i];
    var line = raw.trim();

    if (/^===\s*ESCALATION SUMMARY\s*===$/i.test(line) || /^---\s*AI ANALYSIS\s*---$/i.test(line)) { section = 'none'; continue; }
    if (/^RISK FACTORS\s*:\s*$/i.test(line))                  { section = 'risk-factors'; continue; }
    if (/^---\s*DESCRIPTION\s*---$/i.test(line))              { section = 'description';  continue; }
    if (/^===\s*ACTION REQUIRED\s*===$/i.test(line))          { section = 'action-required'; continue; }
    if (!line) {
      if (section === 'description' && descLines.length && descLines[descLines.length - 1] !== '') descLines.push('');
      continue;
    }

    var v;
    if ((v = readVal(line, 'Ticket'))          !== null) { parsed.ticket          = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Status'))          !== null) { parsed.status          = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Priority'))        !== null) { parsed.priority        = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Channel'))         !== null) { parsed.channel         = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Intent Category')) !== null) { parsed.intentCategory  = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Confidence Score'))!== null) { parsed.confidenceScore = v; section = 'none'; hasContent = true; continue; }
    if ((v = readVal(line, 'Risk Score'))      !== null) { parsed.riskScore       = v; section = 'none'; hasContent = true; continue; }

    if (section === 'risk-factors') {
      var f = line.replace(/^[•*\-]\s*/, '').replace(/^\d+[.)]\s*/, '').trim();
      if (f) { parsed.riskFactors.push(f); hasContent = true; }
      continue;
    }
    if (section === 'description')    { descLines.push(raw.trim()); continue; }
    if (section === 'action-required'){ actionLines.push(line); continue; }
  }

  var desc   = descLines.join('\n').trim();
  var action = actionLines.join(' ').replace(/\s+/g, ' ').trim();
  if (desc)   { parsed.description    = desc;   hasContent = true; }
  if (action) { parsed.actionRequired = action; hasContent = true; }

  return hasContent ? parsed : null;
}

function readVal(line, label) {
  var prefix = label + ':';
  if (!line.toLowerCase().startsWith(prefix.toLowerCase())) return null;
  return line.slice(prefix.length).trim();
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
function prioColor(p) {
  if (!p) return '#6B7280';
  var l = p.toLowerCase();
  if (l === 'critical' || l === 'high')   return '#EF4444';
  if (l === 'medium')                     return '#D97706';
  return '#6B7280';
}

function riskColor(r) {
  if (!r) return '#6B7280';
  var l = r.toLowerCase();
  if (l === 'critical') return '#EF4444';
  if (l === 'high')     return '#F59E0B';
  if (l === 'medium')   return '#D97706';
  return '#059669';
}

function normalizeRisk(r) {
  var v = String(r || '').toLowerCase();
  if (v === 'low' || v === 'medium' || v === 'high' || v === 'critical') return v;
  return 'medium';
}

function pct(v, digits) {
  if (v === null || v === undefined || isNaN(v)) return '—';
  return (v * 100).toFixed(digits || 0) + '%';
}

function summarize(text, max) {
  var t = String(text || '').replace(/\s+/g, ' ').trim();
  if (!t) return '';
  if (t.length <= max) return t;
  return t.slice(0, max - 1).trimRight() + '…';
}

function formatRelative(val) {
  if (!val) return '—';
  var d = new Date(val);
  if (isNaN(d.getTime())) return val;
  var diff = Date.now() - d.getTime();
  var m = Math.round(diff / 60000);
  if (m < 1)  return 'À l\'instant';
  if (m < 60) return 'Il y a ' + m + 'min';
  var h = Math.round(m / 60);
  if (h < 24) return 'Il y a ' + h + 'h';
  return 'Il y a ' + Math.round(h / 24) + 'j';
}

function formatDateTime(val) {
  if (!val) return '—';
  var d = new Date(val);
  if (isNaN(d.getTime())) return val;
  return d.toLocaleString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function esc(t) {
  return String(t || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function toast(msg, isError) {
  var el  = document.getElementById('toast');
  var ico = el.querySelector('svg');
  ico.setAttribute('stroke', isError ? '#EF4444' : '#4ADE80');
  document.getElementById('toastMsg').textContent = msg;
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(function () { el.classList.remove('show'); }, 3200);
}

// Fix esc-wrap height to fill remaining viewport below navbar
(function resizeEsc() {
  var wrap = document.querySelector('.esc-wrap');
  if (!wrap) return;
  var top = wrap.getBoundingClientRect().top;
  wrap.style.height = (window.innerHeight - top - 1) + 'px';
})();
</script>
@endsection
