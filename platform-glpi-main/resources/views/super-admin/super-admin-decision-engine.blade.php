@extends('layouts.dashboard')
@section('title', 'Moteur de Décisions — Super Admin')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
.main-content{padding:0!important;} .main-content > .container-fluid{padding-top:0!important;padding-bottom:0!important;}
*{box-sizing:border-box;}
:root{
  --p:var(--color-primary,#6C63FF);--s:var(--color-secondary,#8B85FF);
  --bg:#F5F6FA;--bg2:#FFFFFF;--bg3:#F0F1F8;
  --brd:#E4E6EF;--brd2:#D0D3E8;
  --t1:#0D0F1A;--t2:#2D3047;--t3:#6B7280;--t4:#9CA3AF;--t5:#D1D5DB;
  --font:'DM Sans',system-ui,sans-serif;--mono:'DM Mono',monospace;
  --red:#EF4444;--orange:#F59E0B;--green:#10B981;--blue:#3B82F6;
}
[data-bs-theme=dark]{
  --bg:#0C0D14;--bg2:#13141F;--bg3:#1A1B2E;
  --brd:#1E2030;--brd2:#2A2D42;
  --t1:#F1F2F8;--t2:#C8CCDF;--t3:#7880A0;--t4:#4B5068;--t5:#282B3A;
}
#de-wrap{display:flex;flex-direction:column;height:calc(100vh - 82px);font-family:var(--font);background:var(--bg);overflow:hidden;}

/* ══ TOP BAR ══ */
#deTopbar{
  padding:12px 22px;background:var(--bg2);border-bottom:1px solid var(--brd);
  display:flex;align-items:center;gap:14px;flex-shrink:0;flex-wrap:wrap;
}
.de-title{font-size:17px;font-weight:700;color:var(--t1);display:flex;align-items:center;gap:9px;}
.de-icon{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--p),var(--s));display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.de-tabs{display:flex;gap:4px;}
.de-tab{
  padding:6px 16px;border-radius:8px;font-size:13px;font-weight:500;
  border:1px solid var(--brd);background:transparent;cursor:pointer;color:var(--t3);
  font-family:var(--font);transition:all .15s;
}
.de-tab:hover{border-color:var(--p);color:var(--p);}
.de-tab.active{background:linear-gradient(135deg,var(--p),var(--s));color:#fff;border-color:var(--p);box-shadow:0 3px 12px color-mix(in srgb,var(--p) 30%,transparent);}
.de-filters{display:flex;gap:8px;align-items:center;margin-left:auto;}
.de-filter-select{padding:6px 11px;border-radius:8px;border:1px solid var(--brd);background:var(--bg2);color:var(--t2);font-size:12px;font-family:var(--font);outline:none;cursor:pointer;}
.de-filter-select:focus{border-color:var(--p);}
.de-export-btn{padding:6px 14px;border-radius:8px;border:1px solid var(--brd);background:var(--bg2);color:var(--t2);font-size:12px;font-weight:500;cursor:pointer;font-family:var(--font);display:flex;align-items:center;gap:5px;transition:all .15s;}
.de-export-btn:hover{border-color:var(--p);color:var(--p);}

/* ══ KPI strip (TSX: stats cards) ══ */
#deKpiStrip{display:flex;gap:10px;padding:12px 22px;background:var(--bg2);border-bottom:1px solid var(--brd);flex-shrink:0;flex-wrap:wrap;}
.kpi-card{flex:1;min-width:140px;background:var(--bg3);border:1px solid var(--brd);border-radius:10px;padding:12px 16px;}
.kpi-val{font-size:26px;font-weight:800;color:var(--t1);font-family:var(--mono);line-height:1;}
.kpi-label{font-size:10px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;margin-top:4px;}

/* ══ BODY ══ */
#deBody{flex:1;overflow:hidden;display:flex;flex-direction:column;}

/* ══ TAB PANELS ══ */
.tab-panel{display:none;flex:1;overflow-y:auto;}
.tab-panel.active{display:flex;flex-direction:column;}

/* ─ Timeline panel (3-col) ─ */
#panTimeline{flex-direction:row!important;overflow:hidden;}
#deList{width:310px;min-width:310px;background:var(--bg2);border-right:1px solid var(--brd);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;}
.list-hdr{padding:11px 13px 8px;border-bottom:1px solid var(--brd);flex-shrink:0;}
.list-title{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;}
.list-search{display:flex;align-items:center;gap:6px;background:var(--bg3);border:1px solid var(--brd2);border-radius:8px;padding:6px 10px;}
.list-search input{flex:1;border:none;background:transparent;outline:none;font-size:12px;color:var(--t1);font-family:var(--font);}
.list-search input::placeholder{color:var(--t4);}
.list-chips{display:flex;gap:4px;margin-top:7px;flex-wrap:wrap;}
.chip{padding:2px 9px;border-radius:99px;font-size:10px;font-weight:700;border:1px solid var(--brd);background:transparent;cursor:pointer;color:var(--t3);transition:all .12s;font-family:var(--font);}
.chip.active{background:var(--p);color:#fff;border-color:var(--p);}
.ticket-list{flex:1;overflow-y:auto;}
.ticket-list::-webkit-scrollbar{width:4px;}.ticket-list::-webkit-scrollbar-thumb{background:var(--brd2);border-radius:2px;}
.ti{padding:10px 13px;cursor:pointer;border-bottom:1px solid var(--brd);transition:background .12s;border-left:3px solid transparent;}
.ti:hover{background:var(--bg3);}
.ti.active{background:color-mix(in srgb,var(--p) 8%,transparent);border-left-color:var(--p);}
.ti-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;}
.ti-id{font-size:11px;font-weight:700;color:var(--p);font-family:var(--mono);}
.ti-time{font-size:10px;color:var(--t4);}
.ti-title{font-size:12px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px;}
.ti-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.ti-badge{padding:1px 7px;border-radius:99px;font-size:10px;font-weight:600;}
.ti-source{font-size:10px;color:var(--t4);}
.ti-who{font-size:10px;color:var(--t4);}

#deDetail{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.empty-detail{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--t4);gap:12px;}
.empty-detail-icon{width:60px;height:60px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;}
#deTimeline{flex:1;overflow-y:auto;padding:18px 22px;}
#deTimeline::-webkit-scrollbar{width:4px;}.#deTimeline::-webkit-scrollbar-thumb{background:var(--brd2);border-radius:2px;}
.tl-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;}
.tl-ticket-id{font-size:11px;font-weight:700;color:var(--p);font-family:var(--mono);}
.tl-title{font-size:18px;font-weight:700;color:var(--t1);margin:4px 0 6px;}
.tl-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.meta-item{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--t3);}
.decision-box{background:var(--bg3);border:1px solid var(--brd2);border-radius:12px;padding:14px 16px;margin-bottom:16px;}
.decision-box.auto_resolved{border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.06);}
.decision-box.escalated{border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.06);}
.decision-box.clarify{border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.06);}
.db-title{font-size:12px;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.db-scores{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.db-score{text-align:center;padding:10px 8px;background:var(--bg2);border-radius:8px;border:1px solid var(--brd);}
.db-score-val{font-size:20px;font-weight:800;color:var(--t1);font-family:var(--mono);}
.tl-events{display:flex;flex-direction:column;gap:0;}
.tl-event{display:flex;gap:13px;padding:8px 0;}
.tl-dot-col{display:flex;flex-direction:column;align-items:center;width:24px;flex-shrink:0;margin-top:4px;}
.tl-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.tl-line{width:2px;flex:1;background:var(--brd);margin-top:4px;min-height:16px;}
.tl-content{flex:1;padding-bottom:8px;}
.tl-ev-title{font-size:13px;font-weight:600;color:var(--t2);}
.tl-ev-sub{font-size:12px;color:var(--t3);margin-top:2px;}
.tl-ev-time{font-size:11px;color:var(--t4);margin-top:3px;}
.tl-ev-detail{margin-top:6px;padding:8px 10px;background:var(--bg3);border-radius:7px;border:1px solid var(--brd);font-size:12px;color:var(--t2);line-height:1.5;}

#deStats{width:280px;min-width:280px;background:var(--bg2);border-left:1px solid var(--brd);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;}
.stats-hdr{padding:12px 14px 8px;border-bottom:1px solid var(--brd);flex-shrink:0;}
.stats-title{font-size:13px;font-weight:700;color:var(--t1);display:flex;align-items:center;gap:7px;}
.stats-body{flex:1;overflow-y:auto;padding:12px;}
.stats-body::-webkit-scrollbar{width:3px;}.stats-body::-webkit-scrollbar-thumb{background:var(--brd2);border-radius:2px;}
.stat-card{background:var(--bg3);border-radius:10px;padding:12px;margin-bottom:10px;border:1px solid var(--brd);}
.stat-bar{height:6px;border-radius:3px;background:var(--brd2);overflow:hidden;margin-top:7px;}
.stat-bar-fill{height:100%;border-radius:3px;transition:width .6s cubic-bezier(.4,0,.2,1);}
.mini-list{display:flex;flex-direction:column;gap:6px;}
.mini-item{display:flex;align-items:center;gap:8px;}
.mini-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.mini-label{font-size:12px;color:var(--t2);flex:1;}
.mini-val{font-size:12px;font-weight:700;color:var(--t1);font-family:var(--mono);}
.mini-pct{font-size:11px;color:var(--t4);}
.h-bars{display:flex;flex-direction:column;gap:5px;}
.h-bar-row{display:flex;align-items:center;gap:7px;}
.h-bar-label{font-size:10px;color:var(--t3);width:60px;text-align:right;flex-shrink:0;}
.h-bar-track{flex:1;height:14px;background:var(--brd2);border-radius:3px;overflow:hidden;}
.h-bar-fill{height:100%;border-radius:3px;}
.h-bar-val{font-size:10px;font-weight:600;color:var(--t2);width:28px;text-align:right;font-family:var(--mono);}

/* ─ Analyze panel (TSX: analyze tab) ─ */
#panAnalyze{padding:20px 24px;}
.an-two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
@media(max-width:800px){.an-two-col{grid-template-columns:1fr;}}
.an-card{background:var(--bg2);border:1px solid var(--brd);border-radius:12px;padding:18px;}
.an-card-title{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.de-input{width:100%;padding:9px 12px;border-radius:9px;border:1px solid var(--brd);background:var(--bg3);color:var(--t1);font-size:13px;font-family:var(--font);outline:none;transition:border-color .15s;}
.de-input:focus{border-color:var(--p);}
.de-textarea{width:100%;padding:9px 12px;border-radius:9px;border:1px solid var(--brd);background:var(--bg3);color:var(--t1);font-size:13px;font-family:var(--font);outline:none;resize:vertical;min-height:90px;line-height:1.6;transition:border-color .15s;}
.de-textarea:focus{border-color:var(--p);}
.toggle-row{display:flex;align-items:center;justify-content:space-between;background:var(--bg3);border:1px solid var(--brd);border-radius:8px;padding:8px 12px;margin-bottom:6px;}
.toggle-label{font-size:12px;color:var(--t2);}
.de-switch{position:relative;width:36px;height:20px;flex-shrink:0;}
.de-switch input{opacity:0;width:0;height:0;}
.de-switch-slider{position:absolute;inset:0;background:var(--brd2);border-radius:99px;cursor:pointer;transition:background .2s;}
.de-switch-slider:before{content:'';position:absolute;width:14px;height:14px;border-radius:50%;background:#fff;left:3px;top:3px;transition:transform .2s;}
.de-switch input:checked+.de-switch-slider{background:var(--p);}
.de-switch input:checked+.de-switch-slider:before{transform:translateX(16px);}
.de-btn{padding:8px 18px;border-radius:9px;border:none;background:linear-gradient(135deg,var(--p),var(--s));color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);display:inline-flex;align-items:center;gap:7px;transition:opacity .15s;}
.de-btn:hover:not(:disabled){opacity:.88;}
.de-btn:disabled{opacity:.45;cursor:not-allowed;}
.de-btn.outline{background:transparent;border:1px solid var(--brd);color:var(--t2);box-shadow:none;}
.de-btn.outline:hover:not(:disabled){border-color:var(--p);color:var(--p);}
/* Result card (TSX: result block) */
.result-card{background:var(--bg2);border:1px solid var(--brd);border-radius:12px;padding:18px;margin-top:16px;}
.result-badges{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;}
.result-badge{padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;border:1px solid;}
.result-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;}
@media(max-width:700px){.result-grid{grid-template-columns:1fr 1fr;}}
.result-field{background:var(--bg3);border:1px solid var(--brd);border-radius:8px;padding:8px 10px;}
.result-field-label{font-size:10px;color:var(--t4);margin-bottom:2px;font-weight:600;text-transform:uppercase;}
.result-field-val{font-size:13px;font-weight:600;color:var(--t1);}
.result-reasoning{font-size:13px;color:var(--t3);line-height:1.6;margin-bottom:12px;}
.result-suggestions{display:flex;flex-direction:column;gap:6px;}
.result-suggestion{font-size:12px;background:var(--bg3);border:1px solid var(--brd);border-radius:7px;padding:8px 12px;color:var(--t2);line-height:1.5;}
.escalation-alert{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:12px 14px;margin-top:10px;}
.escalation-alert-title{font-size:12px;font-weight:700;color:var(--red);margin-bottom:4px;display:flex;align-items:center;gap:6px;}
.escalation-alert-body{font-size:12px;color:var(--t2);line-height:1.6;white-space:pre-wrap;}

/* ─ History panel ─ */
#panHistory{padding:20px 24px;}
.hist-filter{display:flex;gap:8px;align-items:center;margin-bottom:14px;}
.hist-filter input{flex:1;max-width:280px;}
.de-table{width:100%;border-collapse:collapse;font-size:12px;}
.de-table th{text-align:left;font-size:10px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;padding:8px 10px;border-bottom:2px solid var(--brd);background:var(--bg3);}
.de-table td{padding:9px 10px;border-bottom:1px solid var(--brd);color:var(--t2);vertical-align:middle;}
.de-table tr:last-child td{border-bottom:none;}
.de-table tr:hover td{background:var(--bg3);}
.de-table .mono{font-family:var(--mono);font-size:11px;color:var(--p);font-weight:700;}
.de-table-wrap{background:var(--bg2);border:1px solid var(--brd);border-radius:12px;overflow:hidden;}

/* ─ Stats/Analytics panel ─ */
#panStats{padding:20px 24px;}
.an-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;}
@media(max-width:600px){.an-grid{grid-template-columns:1fr 1fr;}}
.an-kpi{background:var(--bg2);border:1px solid var(--brd);border-radius:12px;padding:14px 16px;}
.an-kpi-val{font-size:28px;font-weight:800;color:var(--t1);font-family:var(--mono);line-height:1;}
.an-kpi-label{font-size:10px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;margin-top:4px;}
.an-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
@media(max-width:700px){.an-row{grid-template-columns:1fr;}}
.chart-bars{display:flex;align-items:flex-end;gap:6px;height:140px;padding-bottom:4px;}
.chart-bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;}
.chart-bar{width:100%;border-radius:4px 4px 0 0;transition:height .4s cubic-bezier(.4,0,.2,1);cursor:default;min-height:2px;}
.chart-bar-label{font-size:9px;color:var(--t4);text-align:center;white-space:nowrap;}
.chart-bar-val{font-size:9px;font-weight:700;color:var(--t3);}
.donut-wrap{display:flex;align-items:center;gap:20px;}
.donut-legend{display:flex;flex-direction:column;gap:7px;flex:1;}
.donut-item{display:flex;align-items:center;gap:7px;font-size:12px;}
.donut-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}

/* ─ Playbook panel (TSX: playbook tab) ─ */
#panPlaybook{padding:20px 24px;}

/* ─ Flow panel (existing) ─ */
#panFlow{padding:20px 24px;}
.flow-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;}
@media(max-width:900px){.flow-grid{grid-template-columns:1fr;}}
.flow-card{background:var(--bg2);border:1px solid var(--brd);border-radius:12px;padding:18px;}
.flow-card-title{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:16px;display:flex;align-items:center;gap:7px;}
.flow-diagram{display:flex;flex-direction:column;align-items:center;gap:0;}
.flow-node{padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-align:center;border:2px solid;min-width:200px;}
.flow-node.start{background:linear-gradient(135deg,var(--p),var(--s));color:#fff;border-color:var(--p);box-shadow:0 4px 15px color-mix(in srgb,var(--p) 30%,transparent);}
.flow-node.decision{background:var(--bg3);border-color:var(--brd2);color:var(--t1);}
.flow-node.outcome-green{background:rgba(16,185,129,.1);border-color:#10B981;color:#10B981;}
.flow-node.outcome-orange{background:rgba(245,158,11,.1);border-color:#F59E0B;color:#F59E0B;}
.flow-node.outcome-red{background:rgba(239,68,68,.1);border-color:#EF4444;color:#EF4444;}
.flow-node.outcome-blue{background:rgba(59,130,246,.1);border-color:#3B82F6;color:#3B82F6;}
.flow-arrow{width:2px;height:20px;background:var(--brd2);margin:0 auto;position:relative;}
.flow-arrow::after{content:'';position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-top:6px solid var(--brd2);}
.flow-branches{display:flex;gap:12px;width:100%;justify-content:center;}
.flow-branch{display:flex;flex-direction:column;align-items:center;gap:0;flex:1;}
.flow-branch-label{font-size:10px;font-weight:700;color:var(--t4);padding:2px 8px;background:var(--bg3);border-radius:99px;}
.rule-item{background:var(--bg3);border:1px solid var(--brd);border-radius:10px;padding:12px 14px;margin-bottom:10px;}
.rule-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
.rule-name{font-size:12px;font-weight:700;color:var(--t2);}
.rule-badge{padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;}
.rule-condition{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;}
.rule-input{padding:5px 9px;border-radius:7px;border:1px solid var(--brd2);background:var(--bg2);color:var(--t1);font-size:12px;font-family:var(--mono);width:60px;outline:none;}
.rule-input:focus{border-color:var(--p);}
.rule-op{font-size:11px;color:var(--t4);font-weight:600;}
.rule-save-btn{padding:4px 12px;border-radius:7px;background:var(--p);color:#fff;border:none;font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font);transition:opacity .15s;}
.rule-save-btn:hover{opacity:.85;}

/* Spinner */
.spin{animation:spin .7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes pulse{0%,100%{opacity:.4}50%{opacity:1}}
</style>

<div id="de-wrap">

  {{-- ══ TOP BAR ══ --}}
  <div id="deTopbar">
    <div class="de-title">
      <div class="de-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
      </div>
      Moteur de Décisions IA
    </div>

    <div class="de-tabs">
      <button class="de-tab active" onclick="switchTab('timeline',this)">Timeline</button>
      <button class="de-tab" onclick="switchTab('analyze',this)">Analyser</button>
      <button class="de-tab" onclick="switchTab('history',this)">Historique</button>
      <button class="de-tab" onclick="switchTab('stats',this)">Statistiques</button>
      <button class="de-tab" onclick="switchTab('playbook',this)">Playbook</button>
      <button class="de-tab" onclick="switchTab('flow',this)">Flux IA</button>
    </div>

    <div class="de-filters">
      <select class="de-filter-select" onchange="filterByPeriod(this.value)">
        <option value="today">Aujourd'hui</option>
        <option value="week" selected>7 derniers jours</option>
        <option value="month">30 derniers jours</option>
        <option value="all">Tout</option>
      </select>
      <select class="de-filter-select" onchange="filterBySource(this.value)">
        <option value="">Toutes sources</option>
        <option value="email">Email</option>
        <option value="whatsapp">WhatsApp</option>
        <option value="platform">Plateforme</option>
      </select>
      <button class="de-export-btn" onclick="exportCSV()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exporter CSV
      </button>
    </div>
  </div>

  {{-- ══ KPI STRIP (mirrors TSX stats bar) ══ --}}
  <div id="deKpiStrip">
    <div class="kpi-card">
      <div class="kpi-val" id="kpiTotal">—</div>
      <div class="kpi-label">Total Décisions</div>
    </div>
    <div class="kpi-card" style="border-color:rgba(16,185,129,.3);">
      <div class="kpi-val" style="color:var(--green);" id="kpiAutoResolved">—</div>
      <div class="kpi-label">Auto-résolus</div>
    </div>
    <div class="kpi-card" style="border-color:rgba(239,68,68,.3);">
      <div class="kpi-val" style="color:var(--red);" id="kpiEscalated">—</div>
      <div class="kpi-label">Escaladés</div>
    </div>
    <div class="kpi-card" style="border-color:rgba(108,99,255,.3);">
      <div class="kpi-val" style="color:var(--p);" id="kpiEscalationRate">—</div>
      <div class="kpi-label">Taux d'escalade</div>
    </div>
  </div>

  {{-- ══ BODY ══ --}}
  <div id="deBody">

    {{-- ─ TAB: TIMELINE ─ --}}
    <div class="tab-panel active" id="panTimeline">
      {{-- Left: ticket list --}}
      <div id="deList">
        <div class="list-hdr">
          <div class="list-title">
            <span>Tickets analysés</span>
            <span style="font-size:11px;font-weight:400;color:var(--t4);" id="ticketCount">…</span>
          </div>
          <div class="list-search">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--t4)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" placeholder="Rechercher ticket, client…" oninput="filterTickets(this.value)" id="ticketSearch">
          </div>
          <div class="list-chips">
            <button class="chip active" onclick="filterChip('all',this)">Tous</button>
            <button class="chip" onclick="filterChip('auto_resolved',this)">Auto-résolu</button>
            <button class="chip" onclick="filterChip('escalated',this)">Escaladé</button>
            <button class="chip" onclick="filterChip('clarify',this)">Clarification</button>
          </div>
        </div>
        <div class="ticket-list" id="ticketList">
          <div style="padding:30px;text-align:center;color:var(--t4);font-size:12px;">Chargement…</div>
        </div>
      </div>

      {{-- Middle: timeline detail --}}
      <div id="deDetail">
        <div class="empty-detail" id="deEmptyDetail">
          <div class="empty-detail-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--t4)" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
          </div>
          <div style="font-size:14px;color:var(--t2);font-weight:500;">Sélectionnez un ticket</div>
          <div style="font-size:12px;color:var(--t4);max-width:220px;text-align:center;line-height:1.5;">Pour voir l'analyse complète du moteur de décisions IA</div>
        </div>
        <div id="deTl" style="display:none;flex-direction:column;flex:1;overflow:hidden;">
          <div id="deTimeline"></div>
        </div>
      </div>

      {{-- Right: stats sidebar --}}
      <div id="deStats">
        <div class="stats-hdr">
          <div class="stats-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Statistiques IA
          </div>
        </div>
        <div class="stats-body" id="statsSidebar">
          <div style="padding:20px;text-align:center;color:var(--t4);font-size:12px;">Chargement…</div>
        </div>
      </div>
    </div>

    {{-- ─ TAB: ANALYZE (mirrors TSX "analyze" tab) ─ --}}
    <div class="tab-panel" id="panAnalyze">
      <div class="an-two-col">
        {{-- Analyze by Ticket ID --}}
        <div class="an-card">
          <div class="an-card-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            Analyser par ticket
          </div>
          <input type="text" class="de-input" id="analyzeTicketId" placeholder="ID du ticket (ex: TK-1234)" style="margin-bottom:10px;">
          <div class="toggle-row">
            <span class="toggle-label">Auto-assigner un agent</span>
            <label class="de-switch"><input type="checkbox" id="autoAssign"><span class="de-switch-slider"></span></label>
          </div>
          <div class="toggle-row" style="margin-bottom:12px;">
            <span class="toggle-label">Mettre à jour la priorité auto</span>
            <label class="de-switch"><input type="checkbox" id="autoUpdatePriority" checked><span class="de-switch-slider"></span></label>
          </div>
          <button class="de-btn" id="btnAnalyzeTicket" onclick="doAnalyzeTicket()" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4"/></svg>
            Analyser ticket
          </button>
        </div>

        {{-- Analyze free text --}}
        <div class="an-card">
          <div class="an-card-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            Analyser texte libre
          </div>
          <input type="text" class="de-input" id="analyzeSubject" placeholder="Sujet (optionnel)" style="margin-bottom:8px;">
          <textarea class="de-textarea" id="analyzeText" placeholder="Description du problème…" rows="4" style="margin-bottom:12px;"></textarea>
          <button class="de-btn" id="btnAnalyzeText" onclick="doAnalyzeText()" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4"/></svg>
            Analyser aperçu
          </button>
        </div>
      </div>

      {{-- Result block (hidden until API responds) --}}
      <div id="analyzeResult" style="display:none;"></div>
    </div>

    {{-- ─ TAB: HISTORY (mirrors TSX "history" tab) ─ --}}
    <div class="tab-panel" id="panHistory">
      <div class="hist-filter">
        <input type="text" class="de-input" id="histTicketFilter" placeholder="Filtrer par ID ticket…" style="max-width:280px;" oninput="onHistFilterInput(this.value)">
        <button class="de-btn outline" onclick="clearHistFilter()" id="histClearBtn" disabled>Effacer filtre</button>
      </div>
      <div class="de-table-wrap">
        <div style="overflow-x:auto;">
          <table class="de-table">
            <thead>
              <tr>
                <th>Ticket</th><th>Outcome</th><th>Intention</th>
                <th>Confiance</th><th>Risque</th><th>Règle</th><th>Date</th>
              </tr>
            </thead>
            <tbody id="histTableBody">
              <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--t4);">Chargement…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ─ TAB: STATS (mirrors TSX "stats" tab) ─ --}}
    <div class="tab-panel" id="panStats">
      <div class="an-grid" id="statsKpis">
        <div class="an-kpi"><div class="an-kpi-val" id="anAvgConf">—</div><div class="an-kpi-label">Confiance moy.</div></div>
        <div class="an-kpi"><div class="an-kpi-val" id="anAvgRisk">—</div><div class="an-kpi-label">Risque moy.</div></div>
        <div class="an-kpi"><div class="an-kpi-val" style="color:var(--p);" id="anEscRate">—</div><div class="an-kpi-label">Taux escalade</div></div>
      </div>
      <div class="an-row">
        <div class="an-card">
          <div class="an-card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            Décisions par catégorie
          </div>
          <div class="chart-bars" id="anChartCat"></div>
        </div>
        <div class="an-card">
          <div class="an-card-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            Répartition outcomes
          </div>
          <div class="donut-wrap">
            <svg style="width:110px;height:110px;" viewBox="0 0 36 36" id="anDonut">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--brd2)" stroke-width="3.8"/>
            </svg>
            <div class="donut-legend" id="anDonutLegend"></div>
          </div>
        </div>
      </div>
    </div>

    {{-- ─ TAB: PLAYBOOK (mirrors TSX "playbook" tab) ─ --}}
    <div class="tab-panel" id="panPlaybook">
      {{-- Outcome docs table --}}
      <div class="an-card" style="margin-bottom:16px;">
        <div class="an-card-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
          Tous les outcomes possibles
        </div>
        <div class="de-table-wrap">
          <div style="overflow-x:auto;">
            <table class="de-table">
              <thead><tr><th>Outcome</th><th>Description</th><th>Guidance opérateur</th></tr></thead>
              <tbody id="playbookOutcomes">
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t4);">Chargement…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Decision matrix table --}}
      <div class="an-card">
        <div class="an-card-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Matrice de décision (documentation)
        </div>
        <div class="de-table-wrap">
          <div style="overflow-x:auto;">
            <table class="de-table">
              <thead><tr><th>Catégorie</th><th>Confiance</th><th>Risque</th><th>Outcome</th><th>Règle</th></tr></thead>
              <tbody id="playbookMatrix">
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--t4);">Chargement…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- ─ TAB: FLOW IA (existing) ─ --}}
    <div class="tab-panel" id="panFlow">
      <div class="flow-grid">
        <div class="flow-card">
          <div class="flow-card-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4"/></svg>
            Flow de décision IA
          </div>
          <div class="flow-diagram">
            <div class="flow-node start" style="width:220px;">📥 Ticket reçu<br><span style="font-size:10px;font-weight:400;opacity:.85;">Email / WhatsApp / Plateforme</span></div>
            <div class="flow-arrow"></div>
            <div class="flow-node decision" style="width:220px;">🧠 Classification IA<br><span style="font-size:10px;color:var(--t4);">Catégorie · Priorité · Score</span></div>
            <div class="flow-arrow"></div>
            <div class="flow-node decision" style="width:220px;">⚖️ Évaluation Confiance / Risque</div>
            <div class="flow-arrow"></div>
            <div class="flow-branches">
              <div class="flow-branch">
                <div class="flow-branch-label" style="color:var(--green);">conf ≥ <span id="flConfAuto">80</span> & risque &lt; <span id="flRiskAuto">30</span></div>
                <div class="flow-arrow" style="background:#10B981;"></div>
                <div class="flow-node outcome-green" style="min-width:120px;font-size:12px;">✅ AUTO_RESOLVE</div>
                <div style="font-size:10px;color:var(--t4);margin-top:4px;text-align:center;" id="flCountAuto">— tickets</div>
              </div>
              <div class="flow-branch">
                <div class="flow-branch-label" style="color:var(--orange);">conf <span id="flConfClarifyMin">60</span>–<span id="flConfClarifyMax">79</span></div>
                <div class="flow-arrow" style="background:#F59E0B;"></div>
                <div class="flow-node outcome-orange" style="min-width:120px;font-size:12px;">❓ CLARIFY</div>
                <div style="font-size:10px;color:var(--t4);margin-top:4px;text-align:center;" id="flCountClarify">— tickets</div>
              </div>
              <div class="flow-branch">
                <div class="flow-branch-label" style="color:var(--red);">conf &lt; <span id="flConfEscalate">60</span> OU risque &gt; <span id="flRiskEscalate">60</span></div>
                <div class="flow-arrow" style="background:#EF4444;"></div>
                <div class="flow-node outcome-red" style="min-width:120px;font-size:12px;">🚨 ESCALATE</div>
                <div style="font-size:10px;color:var(--t4);margin-top:4px;text-align:center;" id="flCountEscalate">— tickets</div>
              </div>
              <div class="flow-branch">
                <div class="flow-branch-label" style="color:var(--blue);">Priorité ≥ 4</div>
                <div class="flow-arrow" style="background:#3B82F6;"></div>
                <div class="flow-node outcome-blue" style="min-width:120px;font-size:12px;">📋 ROUTE_ADMIN</div>
                <div style="font-size:10px;color:var(--t4);margin-top:4px;text-align:center;" id="flCountRouted">— tickets</div>
              </div>
            </div>
          </div>
        </div>
        <div class="flow-card" style="overflow-y:auto;max-height:600px;">
          <div class="flow-card-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p)" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Règles IA configurables
          </div>
          <div class="rule-item">
            <div class="rule-header"><span class="rule-name">✅ AUTO_RESOLVE</span><span class="rule-badge" style="background:rgba(16,185,129,.15);color:#10B981;" id="flBadgeAuto">—</span></div>
            <div class="rule-condition"><span class="rule-op">Confiance ≥</span><input type="number" class="rule-input" id="ruleConfAuto" value="80" min="0" max="100"><span class="rule-op">ET Risque &lt;</span><input type="number" class="rule-input" id="ruleRiskAuto" value="30" min="0" max="100"></div>
            <button class="rule-save-btn" onclick="saveRule('auto')">Appliquer</button>
          </div>
          <div class="rule-item">
            <div class="rule-header"><span class="rule-name">❓ CLARIFY</span><span class="rule-badge" style="background:rgba(245,158,11,.15);color:#F59E0B;" id="flBadgeClarify">—</span></div>
            <div class="rule-condition"><span class="rule-op">Confiance</span><input type="number" class="rule-input" id="ruleConfClarifyMin" value="60" min="0" max="100"><span class="rule-op">à</span><input type="number" class="rule-input" id="ruleConfClarifyMax" value="79" min="0" max="100"></div>
            <button class="rule-save-btn" onclick="saveRule('clarify')">Appliquer</button>
          </div>
          <div class="rule-item">
            <div class="rule-header"><span class="rule-name">🚨 ESCALATE_HUMAN</span><span class="rule-badge" style="background:rgba(239,68,68,.15);color:#EF4444;" id="flBadgeEscalate">—</span></div>
            <div class="rule-condition"><span class="rule-op">Confiance &lt;</span><input type="number" class="rule-input" id="ruleConfEscalate" value="60" min="0" max="100"><span class="rule-op">OU Risque &gt;</span><input type="number" class="rule-input" id="ruleRiskEscalate" value="60" min="0" max="100"></div>
            <button class="rule-save-btn" onclick="saveRule('escalate')">Appliquer</button>
          </div>
          <div class="rule-item">
            <div class="rule-header"><span class="rule-name">📋 ROUTE_ADMIN</span><span class="rule-badge" style="background:rgba(59,130,246,.15);color:#3B82F6;" id="flBadgeRouted">—</span></div>
            <div class="rule-condition"><span class="rule-op">Priorité ≥</span><input type="number" class="rule-input" id="rulePriorityRoute" value="4" min="1" max="5"></div>
            <button class="rule-save-btn" onclick="saveRule('route')">Appliquer</button>
          </div>
          <div style="font-size:11px;color:var(--t4);padding:8px;background:var(--bg3);border-radius:8px;line-height:1.5;">
            ⚠️ Les modifications sont appliquées aux nouveaux tickets uniquement.
          </div>
        </div>
      </div>
    </div>

  </div>{{-- /#deBody --}}
</div>{{-- /#de-wrap --}}

<script>
// ══════════════════════════════════════════════════════════════════
//  L2T — Moteur de Décisions  |  Blade  |  mirrors DecisionsPage.tsx
// ══════════════════════════════════════════════════════════════════

// ── CSRF ───────────────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function apiFetch(url, opts = {}) {
  if (url.startsWith('/super-admin/')) {
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      ...opts,
    });
    if (!res.ok) {
      const body = await res.json().catch(() => ({}));
      throw new Error(body.message || body.error || `HTTP ${res.status}`);
    }
    return res.json();
  }
  return window.supportApiFetch(url.replace(/^\/api\/v1/, ''), opts);
}

function mapDecisionResult(res, fallbackTicketId) {
  return {
    id: res.id ? String(res.id) : undefined,
    ticket_id: res.ticket_id ? String(res.ticket_id) : fallbackTicketId,
    outcome: String(res.decision_outcome ?? res.outcome ?? '').toLowerCase(),
    confidence: Number(res.confidence_score ?? res.confidence ?? 0),
    confidence_level: String(res.confidence_level ?? '').toLowerCase(),
    intent_category: String(res.intent_category ?? '').toLowerCase(),
    risk_score: Number(res.risk_score ?? 0),
    risk_level: String(res.risk_level ?? 'medium').toLowerCase(),
    matched_rules: Array.isArray(res?.matched_rules?.rules)
      ? res.matched_rules.rules.map(String)
      : Array.isArray(res?.matched_rules)
        ? res.matched_rules.map(String)
        : [],
    reasoning: String(res.reasoning ?? ''),
    response_suggestions: Array.isArray(res?.response_suggestions?.suggestions)
      ? res.response_suggestions.suggestions.map(String)
      : Array.isArray(res?.response_suggestions)
        ? res.response_suggestions.map(String)
        : [],
    suggested_priority: String(res.suggested_priority ?? '').toLowerCase(),
    suggested_agent_name: res.suggested_agent_name ? String(res.suggested_agent_name) : null,
    escalation_summary: res.escalation_summary ? String(res.escalation_summary) : null,
    created_at: res.created_at ? String(res.created_at) : undefined,
  };
}

// ── State ──────────────────────────────────────────────────────────
let ALL_TICKETS      = [];
let ALL_STATS        = {};
let GLOBAL_STATS     = null;   // from decisionsApi.stats()
let PLAYBOOK_DATA    = null;   // from decisionsApi.getOutcomeDocs()
let HISTORY_DATA     = [];
let selectedTicketId = null;
let activeChip       = 'all';
let currentDays      = 7;
let currentSource    = '';
let activeTabKey     = 'timeline';
let histFilterDebounce;

const RULES = { confAuto:80, riskAuto:30, confClarifyMin:60, confClarifyMax:79, confEscalate:60, riskEscalate:60, priorityRoute:4 };
const pColors   = {5:'#EF4444',4:'#F59E0B',3:'#3B82F6',2:'#6B7280',1:'#9CA3AF'};
const oColors   = {auto_resolved:'#10B981',escalated:'#EF4444',clarify:'#F59E0B',routed:'#3B82F6'};
const oLabels   = {auto_resolved:'AUTO_RESOLVE',escalated:'ESCALATE',clarify:'CLARIFY',routed:'ROUTE'};
const catColors = ['#EF4444','#3B82F6','#F59E0B','#8B5CF6','#10B981','#9CA3AF'];
const srcIcons  = {email:'📧',whatsapp:'💬',platform:'🖥️',web:'🖥️'};
const catLabels = {incident_technique:'Incident tech.',integration_api:'API / Intégr.',facturation:'Facturation',plateforme:'Plateforme',paiement_mobile:'Paiement Mobile',autre:'Autre'};

function escH(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function humanize(v){ return v ? String(v).replace(/_/g,' ') : '—'; }
function pctFmt(v){ return v !== undefined && v !== null ? (v*100).toFixed(0)+'%' : '—'; }
function badge(color, text){ return `<span class="ti-badge" style="background:${color}18;color:${color};">${text}</span>`; }

function outcomeBadge(outcome) {
  const c = oColors[outcome] || '#3B82F6';
  return `<span style="padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;background:${c}18;color:${c};border:1px solid ${c}44;">${humanize(outcome)}</span>`;
}

// ══════════════════════════════════════════════════════════════════
//  TAB SWITCHING
// ══════════════════════════════════════════════════════════════════
function switchTab(tab, btn) {
  activeTabKey = tab;
  document.querySelectorAll('.de-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('pan' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');

  if (tab === 'stats')    renderStatsPanel();
  if (tab === 'playbook') loadPlaybook();
  if (tab === 'history')  loadHistory();
  if (tab === 'flow')     renderFlow();
}

// ══════════════════════════════════════════════════════════════════
//  API 1: decisionsApi.stats() -> GET /decision-engine/stats
//  (TSX: useQuery({ queryKey:['decision-stats'], queryFn: decisionsApi.stats }))
// ══════════════════════════════════════════════════════════════════
async function loadGlobalStats() {
  try {
    GLOBAL_STATS = await apiFetch('/decision-engine/stats');
    renderKpiStrip(GLOBAL_STATS);
    if (activeTabKey === 'stats') renderStatsPanel();
  } catch(e) { console.warn('Stats unavailable:', e.message); }
}

function renderKpiStrip(s) {
  if (!s) return;
  document.getElementById('kpiTotal').textContent         = s.total_decisions ?? '—';
  document.getElementById('kpiAutoResolved').textContent  = s.auto_resolved ?? '—';
  document.getElementById('kpiEscalated').textContent     = s.escalated ?? '—';
  document.getElementById('kpiEscalationRate').textContent = s.escalation_rate !== undefined ? pctFmt(s.escalation_rate) : '—';
}

function renderStatsPanel() {
  const s = GLOBAL_STATS;
  if (!s) { loadGlobalStats(); return; }

  // KPI row
  document.getElementById('anAvgConf').textContent = s.avg_confidence !== undefined ? pctFmt(s.avg_confidence) : '—';
  document.getElementById('anAvgRisk').textContent  = s.avg_risk !== undefined ? pctFmt(s.avg_risk) : '—';
  document.getElementById('anEscRate').textContent  = s.escalation_rate !== undefined ? pctFmt(s.escalation_rate) : '—';

  // Category bar chart
  const catData = s.decisions_by_category || {};
  const maxC    = Math.max(...Object.values(catData), 1);
  document.getElementById('anChartCat').innerHTML = Object.entries(catData).map(([k, v], i) => `
    <div class="chart-bar-wrap">
      <div class="chart-bar-val">${v}</div>
      <div class="chart-bar" style="height:${Math.round((v/maxC)*100)}%;background:${catColors[i]||'#9CA3AF'};min-height:${v?4:2}px;" title="${catLabels[k]||k}: ${v}"></div>
      <div class="chart-bar-label">${(catLabels[k]||k).split('/')[0].trim()}</div>
    </div>`).join('');

  // Outcome donut
  const outcomeData = s.decisions_by_outcome || {};
  const total = Object.values(outcomeData).reduce((a, b) => a + b, 0) || 1;
  const circumference = 2 * Math.PI * 15.9;
  let offset = 0;
  const outcomeColors = { auto_resolved:'#10B981', clarify:'#F59E0B', escalated:'#EF4444', routed:'#3B82F6' };
  const circles = Object.entries(outcomeData).map(([k, v]) => {
    const c = outcomeColors[k] || '#9CA3AF';
    const pct = v / total;
    const dash = pct * circumference;
    const circ = `<circle cx="18" cy="18" r="15.9" fill="none" stroke="${c}" stroke-width="3.8"
      stroke-dasharray="${dash} ${circumference - dash}"
      stroke-dashoffset="${-offset}"
      transform="rotate(-90 18 18)" opacity="${v?1:.15}"/>`;
    offset += dash;
    return circ;
  }).join('');
  document.getElementById('anDonut').innerHTML =
    `<circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--brd2)" stroke-width="3.8"/>${circles}`;
  document.getElementById('anDonutLegend').innerHTML = Object.entries(outcomeData).map(([k, v]) => {
    const c = outcomeColors[k] || '#9CA3AF';
    return `<div class="donut-item">
      <div class="donut-dot" style="background:${c};"></div>
      <span style="flex:1;color:var(--t2);">${humanize(k)}</span>
      <span style="font-weight:700;font-family:var(--mono);color:var(--t1);">${v}</span>
      <span style="color:var(--t4);font-size:11px;"> ${Math.round((v/total)*100)}%</span>
    </div>`;
  }).join('');
}

// ══════════════════════════════════════════════════════════════════
//  API 2: decisionsApi.history(ticketId?) -> GET /decision-engine/decisions[/{ticketId}]
//  (TSX: useQuery({ queryKey:['decision-history', id||'all'], queryFn: () => decisionsApi.history(id||undefined) }))
// ══════════════════════════════════════════════════════════════════
async function loadHistory(ticketId) {
  const tbody = document.getElementById('histTableBody');
  tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--t4);">Chargement…</td></tr>`;
  try {
    const url = ticketId ? `/decision-engine/decisions/${encodeURIComponent(ticketId)}` : '/decision-engine/decisions';
    const historyPayload = await apiFetch(url);
    HISTORY_DATA = (historyPayload.decisions || []).map(d => mapDecisionResult(d, ticketId));
    renderHistoryTable(HISTORY_DATA);
  } catch(e) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--red);font-size:12px;">Erreur : ${escH(e.message)}</td></tr>`;
  }
}

function renderHistoryTable(rows) {
  const tbody = document.getElementById('histTableBody');
  if (!rows || !rows.length) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--t4);">Aucune décision trouvée.</td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map(d => `
    <tr>
      <td class="mono">${escH(d.ticket_id ?? '—')}</td>
      <td>${outcomeBadge(d.outcome)}</td>
      <td>${escH(humanize(d.intent_category))}</td>
      <td style="font-family:var(--mono);font-weight:700;color:${d.confidence>=.8?'var(--green)':d.confidence>=.6?'var(--orange)':'var(--red)'};">${pctFmt(d.confidence)}</td>
      <td>${outcomeBadge(d.risk_level)}</td>
      <td style="color:var(--t4);font-size:11px;">${escH(d.matched_rules?.[0] ?? '—')}</td>
      <td style="color:var(--t4);">${d.created_at ? new Date(d.created_at).toLocaleString('fr-FR') : '—'}</td>
    </tr>`).join('');
}

function onHistFilterInput(val) {
  clearTimeout(histFilterDebounce);
  document.getElementById('histClearBtn').disabled = !val.trim();
  histFilterDebounce = setTimeout(() => loadHistory(val.trim() || undefined), 400);
}

function clearHistFilter() {
  document.getElementById('histTicketFilter').value = '';
  document.getElementById('histClearBtn').disabled = true;
  loadHistory();
}

// ══════════════════════════════════════════════════════════════════
//  API 3: decisionsApi.getOutcomeDocs() -> GET /decision-engine/outcomes-docs
//  (TSX: useQuery({ queryKey:['decision-outcomes-docs'], queryFn: decisionsApi.getOutcomeDocs }))
// ══════════════════════════════════════════════════════════════════
async function loadPlaybook() {
  if (PLAYBOOK_DATA) { renderPlaybook(PLAYBOOK_DATA); return; }
  try {
    PLAYBOOK_DATA = await apiFetch('/decision-engine/outcomes-docs');
    renderPlaybook(PLAYBOOK_DATA);
  } catch(e) {
    document.getElementById('playbookOutcomes').innerHTML =
      `<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--red);font-size:12px;">Erreur : ${escH(e.message)}</td></tr>`;
    document.getElementById('playbookMatrix').innerHTML =
      `<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--red);font-size:12px;">Erreur : ${escH(e.message)}</td></tr>`;
  }
}

function renderPlaybook(data) {
  // Outcomes table
  const outcomes = data?.outcomes || [];
  document.getElementById('playbookOutcomes').innerHTML = outcomes.length
    ? outcomes.map(o => `
        <tr>
          <td>${outcomeBadge(o.outcome)}</td>
          <td style="color:var(--t3);font-size:12px;line-height:1.5;">${escH(o.description)}</td>
          <td style="font-size:12px;line-height:1.5;">${escH(o.operator_guidance)}</td>
        </tr>`).join('')
    : `<tr><td colspan="3" style="text-align:center;padding:24px;color:var(--t4);">Aucune documentation disponible.</td></tr>`;

  // Matrix table
  const matrix = data?.matrix || [];
  document.getElementById('playbookMatrix').innerHTML = matrix.length
    ? matrix.map((r, i) => `
        <tr>
          <td>${escH(humanize(r.category))}</td>
          <td>${escH(humanize(r.confidence_level))}</td>
          <td>${outcomeBadge(r.risk_level)}</td>
          <td>${outcomeBadge(r.outcome)}</td>
          <td style="color:var(--t4);font-size:11px;">${escH(r.matched_rule)}</td>
        </tr>`).join('')
    : `<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--t4);">Aucune matrice disponible.</td></tr>`;
}

// ══════════════════════════════════════════════════════════════════
//  API 4: decisionsApi.analyzeTicket(id, opts) -> POST /decision-engine/analyze
//  (TSX: useMutation({ mutationFn: () => decisionsApi.analyzeTicket(ticketIdTrimmed, { auto_assign, auto_update_priority }) }))
// ══════════════════════════════════════════════════════════════════
async function doAnalyzeTicket() {
  const ticketId = document.getElementById('analyzeTicketId').value.trim();
  if (!ticketId) return;
  const btn = document.getElementById('btnAnalyzeTicket');
  setLoading(btn, true, 'Analyse en cours…');
  try {
    const result = await apiFetch('/decision-engine/analyze', {
      method: 'POST',
      body: JSON.stringify({
        ticket_id:            ticketId,
        auto_assign:          document.getElementById('autoAssign').checked,
        auto_update_priority: document.getElementById('autoUpdatePriority').checked,
      }),
    });
    renderAnalyzeResult(mapDecisionResult(result, ticketId));
  } catch(e) {
    showAnalyzeError(e.message);
  }
  setLoading(btn, false, 'Analyser ticket');
}

// ══════════════════════════════════════════════════════════════════
//  API 5: decisionsApi.analyzeText(text, subject?) -> POST /decision-engine/analyze-text
//  (TSX: useMutation({ mutationFn: () => decisionsApi.analyzeText(freeText.trim(), freeSubject.trim()||undefined) }))
// ══════════════════════════════════════════════════════════════════
async function doAnalyzeText() {
  const text    = document.getElementById('analyzeText').value.trim();
  const subject = document.getElementById('analyzeSubject').value.trim();
  if (!text) return;
  const btn = document.getElementById('btnAnalyzeText');
  setLoading(btn, true, 'Analyse en cours…');
  try {
    const result = await apiFetch('/decision-engine/analyze-text', {
      method: 'POST',
      body: JSON.stringify({ text, ...(subject ? { subject } : {}) }),
    });
    renderAnalyzeResult(mapDecisionResult(result));
  } catch(e) {
    showAnalyzeError(e.message);
  }
  setLoading(btn, false, 'Analyser aperçu');
}

function renderAnalyzeResult(r) {
  // Mirrors TSX result block exactly: badges, 4-col grid, reasoning, matched rules, suggestions, escalation alert
  const oc    = oColors[r.outcome] || '#3B82F6';
  const rl    = r.risk_level;
  const rlCol = rl === 'high' || rl === 'critical' ? 'var(--red)' : rl === 'medium' ? 'var(--orange)' : 'var(--green)';

  const badges = `
    <div class="result-badges">
      <span class="result-badge" style="color:${oc};border-color:${oc}44;background:${oc}11;">${humanize(r.outcome)}</span>
      <span class="result-badge" style="color:${rlCol};border-color:${rlCol}44;background:${rlCol}11;">${humanize(r.risk_level)}</span>
      ${r.suggested_priority ? `<span class="result-badge" style="color:${pColors[r.suggested_priority]||'#6B7280'};border-color:${pColors[r.suggested_priority]||'#6B7280'}44;background:${pColors[r.suggested_priority]||'#6B7280'}11;">Priorité ${r.suggested_priority}</span>` : ''}
      <span class="result-badge" style="color:var(--p);border-color:var(--p)44;background:var(--p)11;">Confiance : ${pctFmt(r.confidence)}</span>
    </div>`;

  const grid = `
    <div class="result-grid">
      <div class="result-field"><div class="result-field-label">Intention</div><div class="result-field-val">${escH(humanize(r.intent_category))}</div></div>
      <div class="result-field"><div class="result-field-label">Niveau confiance</div><div class="result-field-val">${escH(humanize(r.confidence_level))}</div></div>
      <div class="result-field"><div class="result-field-label">Score risque</div><div class="result-field-val">${r.risk_score !== undefined ? pctFmt(r.risk_score) : '—'}</div></div>
      <div class="result-field"><div class="result-field-label">Agent suggéré</div><div class="result-field-val">${escH(r.suggested_agent_name ?? '—')}</div></div>
    </div>`;

  const reasoning = r.reasoning
    ? `<p class="result-reasoning">${escH(r.reasoning)}</p>` : '';

  const rules = r.matched_rules && r.matched_rules.length
    ? `<div style="margin-bottom:10px;">
        <div style="font-size:11px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Règles correspondantes</div>
        <div style="display:flex;flex-wrap:wrap;gap:4px;">
          ${r.matched_rules.map(rule => `<span style="font-size:11px;background:var(--bg3);border:1px solid var(--brd);border-radius:5px;padding:2px 8px;color:var(--t2);">${escH(rule)}</span>`).join('')}
        </div>
      </div>` : '';

  const suggestions = r.response_suggestions && r.response_suggestions.length
    ? `<div style="margin-bottom:10px;">
        <div style="font-size:11px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Réponses suggérées</div>
        <div class="result-suggestions">
          ${r.response_suggestions.slice(0, 4).map(s => `<div class="result-suggestion">${escH(s)}</div>`).join('')}
        </div>
      </div>` : '';

  const escalation = r.escalation_summary
    ? `<div class="escalation-alert">
        <div class="escalation-alert-title">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Résumé d'escalade
        </div>
        <div class="escalation-alert-body">${escH(r.escalation_summary)}</div>
      </div>` : '';

  const el = document.getElementById('analyzeResult');
  el.style.display = 'block';
  el.innerHTML = `
    <div class="result-card">
      <div style="font-size:13px;font-weight:700;color:var(--t1);margin-bottom:12px;">Résultat de l'analyse</div>
      ${badges}${grid}${reasoning}${rules}${suggestions}${escalation}
    </div>`;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showAnalyzeError(msg) {
  const el = document.getElementById('analyzeResult');
  el.style.display = 'block';
  el.innerHTML = `<div class="escalation-alert" style="margin-top:16px;"><div class="escalation-alert-title">Erreur</div><div class="escalation-alert-body">${escH(msg)}</div></div>`;
}

// ══════════════════════════════════════════════════════════════════
//  TIMELINE: load ticket list via existing /super-admin/decision-engine/tickets
// ══════════════════════════════════════════════════════════════════
async function loadTickets(days = currentDays, source = currentSource) {
  currentDays   = days;
  currentSource = source;
  document.getElementById('ticketList').innerHTML = skeleton(6);
  document.getElementById('ticketCount').textContent = '…';
  try {
    const url  = `/super-admin/decision-engine/tickets?days=${days}${source ? '&source='+encodeURIComponent(source) : ''}`;
    const data = await apiFetch(url);
    ALL_TICKETS = data.tickets ?? [];
    ALL_STATS   = data.stats   ?? {};
    renderRightStats(ALL_STATS);
    renderTicketList('', activeChip);
    if (activeTabKey === 'flow') renderFlow();
    if (!selectedTicketId && ALL_TICKETS.length) selectTicket(ALL_TICKETS[0].db_id);
  } catch(e) {
    document.getElementById('ticketList').innerHTML =
      `<div style="text-align:center;padding:30px;color:var(--red);font-size:12px;">
        <div style="font-size:24px;margin-bottom:8px;">⚠️</div>
        Erreur de chargement<br><span style="color:var(--t4);">${escH(e.message)}</span>
        <br><br><button onclick="loadTickets()" style="padding:6px 14px;border-radius:8px;background:var(--p);color:#fff;border:none;cursor:pointer;font-size:12px;">Réessayer</button>
      </div>`;
  }
}

function renderTicketList(q = '', chip = activeChip) {
  const el   = document.getElementById('ticketList');
  const ql   = (q || document.getElementById('ticketSearch')?.value || '').toLowerCase();
  const filt = ALL_TICKETS.filter(t => {
    const matchText = !ql || t.id.toLowerCase().includes(ql) || (t.client||'').toLowerCase().includes(ql) || (t.title||'').toLowerCase().includes(ql);
    const matchChip = chip === 'all' || t.outcome === chip;
    return matchText && matchChip;
  });
  document.getElementById('ticketCount').textContent = filt.length + ' ticket' + (filt.length !== 1 ? 's' : '');
  if (!filt.length) { el.innerHTML = `<div style="text-align:center;padding:30px;color:var(--t4);font-size:12px;">Aucun ticket trouvé</div>`; return; }
  el.innerHTML = filt.map(t => `
    <div class="ti${selectedTicketId == t.db_id ? ' active' : ''}" onclick="selectTicket(${t.db_id})">
      <div class="ti-top"><span class="ti-id">${escH(t.id)}</span><span class="ti-time">${escH(t.date)}</span></div>
      <div class="ti-title">${escH(t.title)}</div>
      <div class="ti-meta">
        ${badge(pColors[t.priority]||'#6B7280', t.priority_label)}
        ${badge(oColors[t.outcome]||'#3B82F6', oLabels[t.outcome]||t.outcome)}
        <span class="ti-source">${srcIcons[t.source]||'📋'} ${escH(t.source)}</span>
      </div>
      <div class="ti-who" style="margin-top:3px;">${escH(t.client)} · Conf. <strong style="color:var(--t2);">${t.confidence}%</strong></div>
    </div>`).join('');
}

async function selectTicket(dbId) {
  selectedTicketId = dbId;
  renderTicketList();
  document.getElementById('deEmptyDetail').style.display = 'none';
  const dtl = document.getElementById('deTl'); dtl.style.display = 'flex';
  document.getElementById('deTimeline').innerHTML =
    `<div style="padding:30px;display:flex;flex-direction:column;align-items:center;gap:10px;color:var(--t4);">
       <div style="width:36px;height:36px;border-radius:50%;border:3px solid var(--p);border-top-color:transparent;animation:spin .7s linear infinite;"></div>
       <div style="font-size:12px;">Chargement timeline…</div>
     </div>`;
  try {
    const { ticket, events } = await apiFetch(`/super-admin/decision-engine/tickets/${dbId}`);
    renderTimeline(ticket, events);
  } catch(e) {
    document.getElementById('deTimeline').innerHTML = `<div style="padding:20px;color:var(--red);font-size:12px;">Erreur : ${escH(e.message)}</div>`;
  }
}

function renderTimeline(t, events) {
  const oc   = oColors[t.outcome] || '#3B82F6';
  const oL   = { auto_resolved:'AUTO_RESOLVE', escalated:'ESCALATE_HUMAN', clarify:'CLARIFY', routed:'ROUTE_AGENT' };
  const srcMap = { email:'📧 Email', whatsapp:'💬 WhatsApp', platform:'🖥️ Plateforme', web:'🖥️ Plateforme' };
  const slaColor = t.sla_pct > 100 ? '#EF4444' : t.sla_pct > 80 ? '#F59E0B' : '#10B981';
  const slaW     = Math.min(t.sla_pct, 100);
  const evHtml   = (events || []).map((ev, i) => {
    const isLast = i === events.length - 1;
    const detail = ev.detail ? `<div class="tl-ev-detail">${escH(ev.detail).replace(/\n/g,'<br>')}</div>` : '';
    return `<div class="tl-event">
      <div class="tl-dot-col">
        <div class="tl-dot" style="background:${ev.color};box-shadow:0 0 0 3px ${ev.color}22;"></div>
        ${!isLast ? '<div class="tl-line"></div>' : ''}
      </div>
      <div class="tl-content">
        <div class="tl-ev-title">${ev.icon||''} ${escH(ev.title)}</div>
        <div class="tl-ev-sub">${escH(ev.sub||'')}</div>
        <div class="tl-ev-time">${escH(ev.time||'')}</div>
        ${detail}
      </div>
    </div>`;
  }).join('');
  document.getElementById('deTimeline').innerHTML = `
    <div class="tl-hdr">
      <div>
        <div class="tl-ticket-id">${escH(t.id)}</div>
        <div class="tl-title">${escH(t.title)}</div>
        <div class="tl-meta">
          <span class="meta-item">👤 ${escH(t.client)}</span>
          <span class="meta-item">${srcMap[t.source]||'📋'}</span>
          <span class="meta-item" style="color:${t.sla_pct>100?'var(--red)':'var(--t3)'};">⏱ SLA: ${escH(t.sla_used)} / ${escH(t.sla_limit)}</span>
          ${t.assigned_admin ? `<span class="meta-item">🧑‍💼 ${escH(t.assigned_admin)}</span>` : ''}
        </div>
      </div>
    </div>
    <div class="decision-box ${t.outcome}">
      <div class="db-title" style="color:${oc};">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        Décision Moteur IA
      </div>
      <div class="db-scores">
        <div class="db-score"><div class="db-score-val">${t.confidence}<span style="font-size:14px;">%</span></div><div style="font-size:10px;color:var(--t4);margin-top:2px;font-weight:600;text-transform:uppercase;">Confiance</div></div>
        <div class="db-score"><div class="db-score-val" style="color:${t.risk>60?'#EF4444':t.risk>30?'#F59E0B':'#10B981'}">${t.risk}<span style="font-size:14px;">/100</span></div><div style="font-size:10px;color:var(--t4);margin-top:2px;font-weight:600;text-transform:uppercase;">Risque</div></div>
        <div class="db-score"><div class="db-score-val" style="font-size:14px;color:${oc};">${oL[t.outcome]||t.outcome}</div><div style="font-size:10px;color:var(--t4);margin-top:2px;font-weight:600;text-transform:uppercase;">Résultat</div></div>
      </div>
      <div style="margin-top:10px;">
        <div style="font-size:10px;color:var(--t4);margin-bottom:4px;font-weight:700;text-transform:uppercase;">SLA utilisé</div>
        <div style="height:6px;border-radius:3px;background:var(--brd2);overflow:hidden;"><div style="height:100%;width:${slaW}%;border-radius:3px;background:${slaColor};transition:width .6s;"></div></div>
        <div style="font-size:10px;color:${slaColor};margin-top:3px;">${escH(t.sla_used)} / ${escH(t.sla_limit)} (${t.sla_pct}%)</div>
      </div>
    </div>
    <div style="font-size:11px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Historique complet</div>
    <div class="tl-events">${evHtml}</div>`;
}

function renderRightStats(s) {
  if (!s || !Object.keys(s).length) return;
  const total = s.total || 1;
  const resolved = s.auto_resolved || 0;
  const escalated = s.escalated || 0;
  const clarify = s.clarify || 0;
  const routed = s.routed || 0;
  const rate = s.resolution_rate ?? Math.round((resolved / total) * 100);
  const conf = s.avg_confidence ?? 0;
  const srcData = s.by_source || {};
  const catData = s.by_category || {};
  const admins = s.admins || [];
  const srcTotal = Object.values(srcData).reduce((a, b) => a + b, 0) || 1;
  const srcBars = Object.entries(srcData).map(([k, v]) => `
    <div class="h-bar-row">
      <span class="h-bar-label">${k === 'web' ? 'Plateforme' : k.charAt(0).toUpperCase() + k.slice(1)}</span>
      <div class="h-bar-track"><div class="h-bar-fill" style="width:${Math.round((v/srcTotal)*100)}%;background:${k==='email'?'#F59E0B':k==='whatsapp'?'#25D366':'var(--p)'};"></div></div>
      <span class="h-bar-val">${v}</span>
    </div>`).join('');
  const catItems = Object.entries(catData).slice(0, 5).map(([k, v], i) => `
    <div class="mini-item"><div class="mini-dot" style="background:${catColors[i]||'#9CA3AF'};"></div><span class="mini-label">${catLabels[k]||k}</span><span class="mini-val">${v}</span></div>`).join('');
  const adminItems = admins.slice(0, 4).map(a => `
    <div class="mini-item"><div class="mini-dot" style="background:var(--p);"></div><span class="mini-label">${escH(a.name)}</span><span class="mini-val" style="color:${a.score>=80?'var(--green)':a.score>=60?'var(--orange)':'var(--red)'};">${a.score}%</span></div>`).join('');
  document.getElementById('statsSidebar').innerHTML = `
    <div class="stat-card">
      <div style="font-size:26px;font-weight:800;color:var(--t1);font-family:var(--mono);">${rate}<span style="font-size:18px;color:var(--green);">%</span></div>
      <div style="font-size:10px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Taux résolution IA</div>
      <div style="font-size:11px;color:var(--t3);margin-top:3px;">${resolved} / ${total} tickets résolus</div>
      <div class="stat-bar"><div class="stat-bar-fill" style="width:${rate}%;background:linear-gradient(90deg,var(--green),#34D399);"></div></div>
    </div>
    <div class="stat-card">
      <div style="font-size:11px;font-weight:700;color:var(--t1);margin-bottom:10px;">Décisions IA</div>
      <div class="mini-list">
        <div class="mini-item"><div class="mini-dot" style="background:var(--green);"></div><span class="mini-label">Auto-résolu</span><span class="mini-val">${resolved}</span><span class="mini-pct">${Math.round((resolved/total)*100)}%</span></div>
        <div class="mini-item"><div class="mini-dot" style="background:var(--orange);"></div><span class="mini-label">Clarification</span><span class="mini-val">${clarify}</span><span class="mini-pct">${Math.round((clarify/total)*100)}%</span></div>
        <div class="mini-item"><div class="mini-dot" style="background:var(--red);"></div><span class="mini-label">Escaladé</span><span class="mini-val">${escalated}</span><span class="mini-pct">${Math.round((escalated/total)*100)}%</span></div>
        <div class="mini-item"><div class="mini-dot" style="background:var(--blue);"></div><span class="mini-label">Routé admin</span><span class="mini-val">${routed}</span><span class="mini-pct">${Math.round((routed/total)*100)}%</span></div>
      </div>
    </div>
    <div class="stat-card">
      <div style="font-size:26px;font-weight:800;font-family:var(--mono);color:var(--t1);">${conf}<span style="font-size:16px;color:var(--p);">%</span></div>
      <div style="font-size:10px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Confiance moyenne</div>
      <div class="stat-bar"><div class="stat-bar-fill" style="width:${conf}%;background:linear-gradient(90deg,var(--p),var(--s));"></div></div>
    </div>
    ${srcBars ? `<div class="stat-card"><div style="font-size:11px;font-weight:700;color:var(--t1);margin-bottom:10px;">Sources</div><div class="h-bars">${srcBars}</div></div>` : ''}
    ${catItems ? `<div class="stat-card"><div style="font-size:11px;font-weight:700;color:var(--t1);margin-bottom:10px;">Catégories IA</div><div class="mini-list">${catItems}</div></div>` : ''}
    ${adminItems ? `<div class="stat-card"><div style="font-size:11px;font-weight:700;color:var(--t1);margin-bottom:10px;">Performance admins</div><div class="mini-list">${adminItems}</div></div>` : ''}`;
}

// ── Flow tab (local state only) ─────────────────────────────────────
function renderFlow() {
  const s = ALL_STATS; if (!s) return;
  document.getElementById('flCountAuto').textContent     = (s.auto_resolved || 0) + ' tickets';
  document.getElementById('flCountClarify').textContent  = (s.clarify || 0) + ' tickets';
  document.getElementById('flCountEscalate').textContent = (s.escalated || 0) + ' tickets';
  document.getElementById('flCountRouted').textContent   = (s.routed || 0) + ' tickets';
  document.getElementById('flBadgeAuto').textContent     = (s.auto_resolved || '—') + ' tickets';
  document.getElementById('flBadgeClarify').textContent  = (s.clarify || '—') + ' tickets';
  document.getElementById('flBadgeEscalate').textContent = (s.escalated || '—') + ' tickets';
  document.getElementById('flBadgeRouted').textContent   = (s.routed || '—') + ' tickets';
  syncRuleLabels();
}

function syncRuleLabels() {
  const get = id => document.getElementById(id)?.value;
  ['flConfAuto','flRiskAuto','flConfClarifyMin','flConfClarifyMax','flConfEscalate','flRiskEscalate'].forEach(id => {
    const input = document.getElementById('rule' + id.replace('fl','').replace(/^(.)/, m => m.toUpperCase()));
    const el = document.getElementById(id);
    if (el && input) el.textContent = input.value;
  });
}

function saveRule(type) {
  syncRuleLabels();
  const btns = document.querySelectorAll('.rule-save-btn');
  btns.forEach(b => {
    if (b.getAttribute('onclick') === `saveRule('${type}')`) {
      const orig = b.textContent; b.textContent = '✅ Appliqué'; b.style.background = '#10B981';
      setTimeout(() => { b.textContent = orig; b.style.background = ''; }, 1800);
    }
  });
}

// ── Filter controls ─────────────────────────────────────────────────
function filterTickets(q)  { renderTicketList(q, activeChip); }
function filterChip(chip, btn) {
  activeChip = chip;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  renderTicketList('', chip);
}
function filterByPeriod(v) {
  const days = v==='today'?1:v==='week'?7:v==='month'?30:365;
  selectedTicketId = null;
  loadTickets(days, currentSource);
}
function filterBySource(v) { selectedTicketId = null; loadTickets(currentDays, v||''); }

// ── Analyze tab: enable buttons on input ────────────────────────────
document.getElementById('analyzeTicketId').addEventListener('input', function() {
  document.getElementById('btnAnalyzeTicket').disabled = !this.value.trim();
});
document.getElementById('analyzeText').addEventListener('input', function() {
  document.getElementById('btnAnalyzeText').disabled = !this.value.trim();
});
document.addEventListener('input', e => { if (e.target.classList.contains('rule-input')) syncRuleLabels(); });

// ── CSV export ──────────────────────────────────────────────────────
function exportCSV() {
  const headers = ['ID','Titre','Client','Catégorie','Priorité','Décision','Confiance%','Risque','SLA','Date'];
  const rows = ALL_TICKETS.map(t => [
    t.id, `"${(t.title||'').replace(/"/g,'""')}"`, `"${(t.client||'').replace(/"/g,'""')}"`,
    catLabels[t.category]||t.category||'', t.priority_label,
    oLabels[t.outcome]||t.outcome, t.confidence, t.risk,
    `${t.sla_used}/${t.sla_limit}`, t.date
  ].join(','));
  const csv  = [headers.join(','), ...rows].join('\n');
  const blob = new Blob(['\ufeff'+csv], { type:'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = Object.assign(document.createElement('a'), { href:url, download:`l2t-decisions-${currentDays}j.csv` });
  a.click(); URL.revokeObjectURL(url);
}

// ── Helpers ─────────────────────────────────────────────────────────
function setLoading(btn, on, label) {
  btn.disabled = on;
  btn.innerHTML = on
    ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" class="spin"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/></svg> ${label}`
    : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4"/></svg> ${label}`;
}

function skeleton(n = 5) {
  return Array.from({length:n}, (_,i) => `
    <div style="padding:10px 13px;border-bottom:1px solid var(--brd);">
      <div style="height:10px;width:${60+(i%3)*15}%;background:var(--brd2);border-radius:4px;margin-bottom:6px;animation:pulse 1.4s ease-in-out infinite;"></div>
      <div style="height:8px;width:${40+(i%2)*20}%;background:var(--brd2);border-radius:4px;animation:pulse 1.4s ease-in-out infinite;"></div>
    </div>`).join('');
}

// ── Boot ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  // Load stats globally (used by KPI strip and stats tab)
  loadGlobalStats();

  // Load timeline tickets
  await loadTickets();

  // Deep-link: ?ticket=123
  const ticketId = parseInt(new URLSearchParams(window.location.search).get('ticket'));
  if (ticketId) {
    let found = ALL_TICKETS.find(t => t.db_id === ticketId);
    if (!found) { await loadTickets(90); found = ALL_TICKETS.find(t => t.db_id === ticketId); }
    if (found) selectTicket(found.db_id);
  }
});
</script>
@endsection
