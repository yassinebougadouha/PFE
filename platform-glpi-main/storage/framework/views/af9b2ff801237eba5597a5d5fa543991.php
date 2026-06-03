<?php $__env->startSection('title', 'Appels Vocaux — L2T Support'); ?>

<?php $__env->startSection('content'); ?>
<style>
/* ── Reset & Base ── */
*{box-sizing:border-box;}
.main-content{padding:0!important;overflow:hidden;}

/* ── Page wrap ── */
#vc-page{
  height:calc(100vh - 64px);
  font-family:'DM Sans',system-ui,sans-serif;
  background:var(--color-background-primary,#F8FAFC);
  overflow:hidden;
  display:flex;
  flex-direction:column;
}

/* ── Scrollable inner ── */
#vc-scroll{
  flex:1;
  overflow-y:auto;
  padding:28px 32px;
}
#vc-scroll::-webkit-scrollbar{width:4px;}
#vc-scroll::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:2px;}

/* ── Header ── */
.vc-page-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:16px;
  margin-bottom:24px;
  flex-wrap:wrap;
}
.vc-page-header-left{display:flex;align-items:center;gap:14px;}
.vc-header-icon{
  width:40px;height:40px;border-radius:14px;
  background:color-mix(in srgb,var(--color-primary,#6366f1) 12%,transparent);
  color:var(--color-primary,#6366f1);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
}
.vc-page-title{font-size:17px;font-weight:700;color:var(--color-text-primary,#1e293b);letter-spacing:-0.02em;line-height:1.2;}
.vc-page-sub{font-size:12px;color:var(--color-text-muted,#64748b);margin-top:3px;font-weight:500;}

/* ── Ghost button ── */
.btn-ghost{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 14px;border-radius:10px;
  border:1.5px solid var(--color-border,#e2e8f0);
  background:transparent;
  color:var(--color-text-secondary,#475569);
  font-size:12px;font-weight:700;
  cursor:pointer;font-family:inherit;
  transition:all .18s ease;
}
.btn-ghost:hover{border-color:var(--color-primary,#6366f1);color:var(--color-primary,#6366f1);background:color-mix(in srgb,var(--color-primary,#6366f1) 5%,transparent);}
.btn-ghost svg{flex-shrink:0;}

/* ── Primary button ── */
.btn-primary{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 16px;border-radius:10px;
  background:var(--color-primary,#6366f1);
  color:#fff;border:none;
  font-size:12px;font-weight:700;
  cursor:pointer;font-family:inherit;
  transition:all .18s ease;
  box-shadow:0 3px 10px color-mix(in srgb,var(--color-primary,#6366f1) 25%,transparent);
}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px color-mix(in srgb,var(--color-primary,#6366f1) 35%,transparent);}
.btn-primary:disabled{opacity:.5;pointer-events:none;}

/* ── Danger button ── */
.btn-danger{
  display:inline-flex;align-items:center;gap:7px;
  padding:7px 16px;border-radius:10px;
  background:#ef4444;color:#fff;border:none;
  font-size:12px;font-weight:700;
  cursor:pointer;font-family:inherit;
  transition:all .18s ease;
}
.btn-danger:hover{background:#dc2626;}
.btn-danger:disabled{opacity:.5;pointer-events:none;}

/* ── Section card ── */
.section-card{
  background:var(--color-background-card,#fff);
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:20px;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
  overflow:hidden;
  margin-bottom:20px;
}
.section-card-head{
  display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  padding:16px 20px;
  border-bottom:1px solid var(--color-border,#e2e8f0);
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 60%,transparent);
}
.section-card-head-left{display:flex;align-items:flex-start;gap:10px;}
.section-head-icon{
  width:28px;height:28px;border-radius:8px;flex-shrink:0;margin-top:1px;
  background:color-mix(in srgb,var(--color-primary,#6366f1) 10%,transparent);
  color:var(--color-primary,#6366f1);
  display:flex;align-items:center;justify-content:center;
}
.section-card-title{font-size:13px;font-weight:700;color:var(--color-text-primary,#1e293b);}
.section-card-desc{font-size:11px;color:var(--color-text-muted,#64748b);margin-top:2px;}
.section-card-body{padding:20px;}

/* ── Stat tiles ── */
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
.stat-tile{
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:14px;
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 50%,transparent);
  padding:14px 16px;
}
.stat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted,#64748b);margin-bottom:5px;}
.stat-value{font-size:15px;font-weight:700;color:var(--color-text-primary,#1e293b);font-family:'DM Mono',monospace;}
.stat-sub{font-size:11px;color:var(--color-text-muted,#64748b);margin-top:2px;}

/* ── List table ── */
.list-card{
  background:var(--color-background-card,#fff);
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:20px;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
  overflow:hidden;
}
.list-card-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 20px;
  border-bottom:1px solid var(--color-border,#e2e8f0);
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 60%,transparent);
}
.list-card-title{font-size:13px;font-weight:700;color:var(--color-text-primary,#1e293b);}
.list-card-count{font-size:11px;font-weight:500;color:var(--color-text-muted,#64748b);margin-left:6px;}
.list-table-header{
  display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:8px;
  padding:10px 20px;
  border-bottom:1px solid var(--color-border,#e2e8f0);
  font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
  color:var(--color-text-muted,#64748b);
}
.list-row{
  display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:8px;
  align-items:center;
  padding:14px 20px;
  border-bottom:1px solid var(--color-border,#e2e8f0);
  cursor:pointer;
  transition:background .15s ease;
}
.list-row:last-child{border-bottom:none;}
.list-row:hover{background:color-mix(in srgb,var(--color-primary,#6366f1) 4%,transparent);}
.list-row-icon{
  width:28px;height:28px;border-radius:8px;flex-shrink:0;
  background:color-mix(in srgb,var(--color-primary,#6366f1) 8%,transparent);
  color:color-mix(in srgb,var(--color-primary,#6366f1) 70%,transparent);
  display:flex;align-items:center;justify-content:center;
  transition:background .15s;
}
.list-row:hover .list-row-icon{background:color-mix(in srgb,var(--color-primary,#6366f1) 15%,transparent);}
.list-room{display:flex;align-items:center;gap:10px;min-width:0;}
.list-room-name{font-size:13px;font-weight:600;color:var(--color-text-primary,#1e293b);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.list-meta{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted,#64748b);}
.list-date-main{font-size:12px;color:var(--color-text-muted,#64748b);}
.list-date-ago{font-size:11px;color:color-mix(in srgb,var(--color-text-muted,#64748b) 60%,transparent);}

/* ── Badges ── */
.badge-recorded{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:99px;
  background:#f0fdf4;border:1px solid #bbf7d0;
  color:#16a34a;font-size:10px;font-weight:700;
}
.badge-none{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:99px;
  background:var(--color-background-secondary,#f1f5f9);border:1px solid var(--color-border,#e2e8f0);
  color:var(--color-text-muted,#64748b);font-size:10px;font-weight:700;
}
.badge-priority-high{display:inline-flex;align-items:center;padding:2px 8px;border-radius:99px;background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;font-size:10px;font-weight:700;text-transform:uppercase;}
.badge-priority-medium{display:inline-flex;align-items:center;padding:2px 8px;border-radius:99px;background:#fef9c3;border:1px solid #fef08a;color:#a16207;font-size:10px;font-weight:700;text-transform:uppercase;}
.badge-priority-low{display:inline-flex;align-items:center;padding:2px 8px;border-radius:99px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:10px;font-weight:700;text-transform:uppercase;}

/* ── Back button ── */
.btn-back{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;border-radius:8px;
  background:transparent;border:none;
  color:var(--color-text-muted,#64748b);font-size:12px;font-weight:700;
  cursor:pointer;font-family:inherit;
  transition:all .15s;margin-bottom:20px;margin-left:-4px;
}
.btn-back:hover{color:var(--color-primary,#6366f1);background:color-mix(in srgb,var(--color-primary,#6366f1) 5%,transparent);}

/* ── Detail header ── */
.detail-header{display:flex;align-items:flex-start;gap:14px;margin-bottom:24px;}
.detail-icon{
  width:44px;height:44px;border-radius:16px;flex-shrink:0;
  background:color-mix(in srgb,var(--color-primary,#6366f1) 12%,transparent);
  color:var(--color-primary,#6366f1);
  display:flex;align-items:center;justify-content:center;
}
.detail-title{font-size:17px;font-weight:700;color:var(--color-text-primary,#1e293b);letter-spacing:-0.02em;}
.detail-sub{font-size:12px;color:var(--color-text-muted,#64748b);margin-top:3px;}

/* ── Transcript box ── */
.transcript-box{
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 60%,transparent);
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:14px;padding:16px;
  font-size:12px;font-family:'DM Mono',monospace;
  line-height:1.7;color:var(--color-text-secondary,#475569);
  white-space:pre-wrap;max-height:360px;overflow-y:auto;
}
.transcript-box::-webkit-scrollbar{width:3px;}
.transcript-box::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:2px;}

/* ── Summary sections ── */
.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
.summary-block{
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:12px;
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 50%,transparent);
  padding:12px 14px;
}
.summary-block-full{
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:12px;
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 50%,transparent);
  padding:12px 14px;margin-bottom:12px;
}
.summary-block-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted,#64748b);margin-bottom:5px;}
.summary-block-text{font-size:13px;color:var(--color-text-primary,#1e293b);line-height:1.6;}
.action-item{
  display:flex;align-items:flex-start;gap:10px;
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:12px;
  background:color-mix(in srgb,var(--color-background-secondary,#f1f5f9) 50%,transparent);
  padding:10px 14px;margin-bottom:8px;
}
.action-item:last-child{margin-bottom:0;}
.action-item-title{font-size:13px;font-weight:600;color:var(--color-text-primary,#1e293b);}
.action-item-meta{display:flex;align-items:center;gap:8px;margin-top:4px;font-size:11px;color:var(--color-text-muted,#64748b);}

/* ── Form controls ── */
.vc-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted,#64748b);display:block;margin-bottom:6px;}
.vc-input{
  width:100%;height:40px;
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:10px;padding:0 14px;
  font-size:13px;color:var(--color-text-primary,#1e293b);
  font-family:'DM Mono',monospace;
  background:var(--color-background-card,#fff);
  outline:none;transition:border-color .2s,box-shadow .2s;
}
.vc-input:focus{border-color:var(--color-primary,#6366f1);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary,#6366f1) 10%,transparent);}
.vc-textarea{
  width:100%;
  border:1.5px solid var(--color-border,#e2e8f0);
  border-radius:10px;padding:12px 14px;
  font-size:13px;color:var(--color-text-primary,#1e293b);
  background:var(--color-background-card,#fff);
  outline:none;transition:border-color .2s,box-shadow .2s;
  resize:none;font-family:inherit;line-height:1.6;
}
.vc-textarea:focus{border-color:var(--color-primary,#6366f1);box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary,#6366f1) 10%,transparent);}
.or-divider{display:flex;align-items:center;gap:12px;padding:4px 0;}
.or-line{flex:1;border-top:1px solid var(--color-border,#e2e8f0);}
.or-text{font-size:11px;font-weight:600;color:var(--color-text-muted,#64748b);}

/* ── Alert ── */
.alert-success{
  display:flex;align-items:center;gap:10px;
  border:1px solid #bbf7d0;background:#f0fdf4;
  border-radius:12px;padding:10px 14px;
  font-size:13px;color:#15803d;
}
.alert-error{
  display:flex;align-items:center;gap:10px;
  border:1px solid #fecaca;background:#fef2f2;
  border-radius:12px;padding:10px 14px;
  font-size:13px;color:#b91c1c;
}

/* ── Empty / loading ── */
.vc-empty{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:12px;padding:60px 20px;text-align:center;
}
.vc-empty-icon{
  width:52px;height:52px;border-radius:50%;
  background:var(--color-background-secondary,#f1f5f9);
  display:flex;align-items:center;justify-content:center;
}
.vc-empty-title{font-size:14px;font-weight:600;color:var(--color-text-secondary,#475569);}
.vc-empty-sub{font-size:12px;color:var(--color-text-muted,#64748b);max-width:280px;line-height:1.5;}

/* ── Skeleton ── */
@keyframes shimmer{0%,100%{opacity:1}50%{opacity:.5}}
.skel{border-radius:6px;background:var(--color-border,#e2e8f0);animation:shimmer 1.4s ease-in-out infinite;}

/* ── Audio player ── */
audio{width:100%;border-radius:12px;accent-color:var(--color-primary,#6366f1);}

/* ── Spinner ── */
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.spinning{animation:spin .8s linear infinite;}

/* ── Separator ── */
.vc-sep{border:none;border-top:1px solid var(--color-border,#e2e8f0);margin:16px 0;}

/* Dark mode adjustments */
[data-bs-theme="dark"] .section-card,
[data-bs-theme="dark"] .list-card{background:#1e293b!important;border-color:#334155!important;}
[data-bs-theme="dark"] .section-card-head,
[data-bs-theme="dark"] .list-card-head,
[data-bs-theme="dark"] .list-table-header{background:rgba(255,255,255,0.02)!important;border-color:#334155!important;}
[data-bs-theme="dark"] .list-row{border-color:#334155!important;}
[data-bs-theme="dark"] .list-row:hover{background:rgba(99,102,241,0.08)!important;}
[data-bs-theme="dark"] .stat-tile,
[data-bs-theme="dark"] .summary-block,
[data-bs-theme="dark"] .summary-block-full,
[data-bs-theme="dark"] .action-item{background:rgba(255,255,255,0.03)!important;border-color:#334155!important;}
[data-bs-theme="dark"] .vc-input,
[data-bs-theme="dark"] .vc-textarea{background:#0f172a!important;border-color:#334155!important;color:#e2e8f0!important;}
[data-bs-theme="dark"] .transcript-box{background:rgba(255,255,255,0.02)!important;border-color:#334155!important;color:#94a3b8!important;}

@media(max-width:768px){
  #vc-scroll{padding:16px;}
  .stat-grid{grid-template-columns:1fr 1fr;}
  .summary-grid{grid-template-columns:1fr;}
  .list-table-header,.list-row{grid-template-columns:2fr 1fr 1fr;}
  .list-row>*:nth-child(4),.list-row>*:nth-child(5),.list-table-header>*:nth-child(4),.list-table-header>*:nth-child(5){display:none;}
}
</style>

<?php $me = auth()->user(); ?>

<div id="vc-page">
  <div id="vc-scroll">

    
    <div id="vcListView">
      <div class="vc-page-header">
        <div class="vc-page-header-left">
          <div class="vc-header-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <div class="vc-page-title">Appels Vocaux</div>
            <div class="vc-page-sub">Enregistrements, transcriptions et résumés IA de vos appels</div>
          </div>
        </div>
        <button class="btn-ghost" onclick="loadCalls()" id="refreshBtn">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" id="refreshIcon"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Rafraîchir
        </button>
      </div>

      <div class="list-card">
        <div class="list-card-head">
          <div style="display:flex;align-items:center;">
            <span class="list-card-title">Historique des appels</span>
            <span class="list-card-count" id="callCount"></span>
          </div>
        </div>

        <div class="list-table-header">
          <span>Salle</span>
          <span>Durée</span>
          <span>Date</span>
          <span>Transcription</span>
          <span>Enregistrement</span>
        </div>

        <div id="vcListBody">
          
          <?php for($i=0;$i<6;$i++): ?>
          <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:8px;padding:14px 20px;border-bottom:1px solid var(--color-border,#e2e8f0);">
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="skel" style="width:28px;height:28px;border-radius:8px;"></div>
              <div class="skel" style="width:120px;height:13px;"></div>
            </div>
            <div class="skel" style="width:60px;height:13px;"></div>
            <div class="skel" style="width:80px;height:13px;"></div>
            <div class="skel" style="width:70px;height:13px;"></div>
            <div class="skel" style="width:80px;height:20px;border-radius:99px;"></div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    
    <div id="vcDetailView" style="display:none;max-width:860px;">

      <button class="btn-back" onclick="showListView()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Retour aux appels
      </button>

      
      <div class="detail-header">
        <div class="detail-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <div>
          <div class="detail-title" id="dTitle">—</div>
          <div class="detail-sub" id="dSub">—</div>
        </div>
      </div>

      
      <div class="stat-grid">
        <div class="stat-tile">
          <div class="stat-label">Durée</div>
          <div class="stat-value" id="dDuration">—</div>
        </div>
        <div class="stat-tile">
          <div class="stat-label">Room SID</div>
          <div class="stat-value" style="font-size:11px;word-break:break-all;" id="dRoomSid">—</div>
        </div>
        <div class="stat-tile">
          <div class="stat-label">Enregistrement</div>
          <div id="dRecordingBadge"></div>
        </div>
      </div>

      
      <div class="section-card" id="audioSection" style="display:none;">
        <div class="section-card-head">
          <div class="section-card-head-left">
            <div class="section-head-icon">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
            </div>
            <div>
              <div class="section-card-title">Enregistrement Audio</div>
            </div>
          </div>
        </div>
        <div class="section-card-body">
          <div id="audioContent">
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--color-text-muted,#64748b);">
              <svg class="spinning" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
              Chargement de l'audio…
            </div>
          </div>
        </div>
      </div>

      
      <div class="section-card">
        <div class="section-card-head">
          <div class="section-card-head-left">
            <div class="section-head-icon">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div>
              <div class="section-card-title">Transcription</div>
            </div>
          </div>
        </div>
        <div class="section-card-body">
          <div id="dTranscript"></div>
        </div>
      </div>

      
      <div class="section-card">
        <div class="section-card-head">
          <div class="section-card-head-left">
            <div class="section-head-icon">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
            </div>
            <div>
              <div class="section-card-title">Résumé Post-Appel IA</div>
              <div class="section-card-desc">Synthèse automatique, éléments d'action et recommandations</div>
            </div>
          </div>
          <button class="btn-primary" id="summaryBtn" onclick="generateSummary()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
            Générer
          </button>
        </div>
        <div class="section-card-body">
          <div id="summaryContent">
            <div style="text-align:center;padding:24px 0;font-size:13px;color:var(--color-text-muted,#64748b);font-style:italic;">
              Cliquez sur "Générer" pour produire une synthèse IA de cet appel.
            </div>
          </div>
        </div>
      </div>

      
      <div class="section-card">
        <div class="section-card-head">
          <div class="section-card-head-left">
            <div class="section-head-icon">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            </div>
            <div>
              <div class="section-card-title">Liaison Ticket</div>
              <div class="section-card-desc">Rattachez cet appel à un ticket existant ou créez-en un nouveau</div>
            </div>
          </div>
        </div>
        <div class="section-card-body" style="display:flex;flex-direction:column;gap:16px;">

          
          <div>
            <label class="vc-label">Lier un ticket existant</label>
            <div style="display:flex;gap:8px;">
              <input type="text" class="vc-input" id="existingTicketId" placeholder="UUID ou ID du ticket…" style="flex:1;">
              <button class="btn-ghost" id="linkBtn" onclick="linkExistingTicket()" style="flex-shrink:0;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Lier
              </button>
            </div>
          </div>

          <div class="or-divider">
            <div class="or-line"></div>
            <span class="or-text">ou créer nouveau</span>
            <div class="or-line"></div>
          </div>

          
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
              <label class="vc-label">Sujet du ticket</label>
              <input type="text" class="vc-input" id="ticketSubject" placeholder="Sujet du nouveau ticket…">
            </div>
            <div>
              <label class="vc-label">Description</label>
              <textarea class="vc-textarea" id="ticketDescription" rows="5" placeholder="Description du ticket…"></textarea>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <button class="btn-primary" id="createTicketBtn" onclick="createLinkedTicket()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                Créer & Lier
              </button>
              <button class="btn-danger" id="escalateBtn" onclick="escalateCall()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
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
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const apiUrl = (path) => {
  if (window.supportApiUrl) return window.supportApiUrl(path);
  return '/api/v1/' + String(path || '').replace(/^\//, '');
};
const VOICE_CALL_DETAIL_BASE = <?php echo json_encode(auth()->user()->role === 'admin' ? url('/admin/voice-calls') : url('/super-admin/voice-calls'), 15, 512) ?>;
let currentCall = null;
let currentSummary = null;

// ── HELPERS ───────────────────────────────────────────────────────
function fmtDuration(secs){
  if(!secs) return '—';
  const m=Math.floor(secs/60), s=Math.round(secs%60);
  return m>0?`${m}m ${s}s`:`${s}s`;
}
function timeAgo(dateStr){
  const diff=Date.now()-new Date(dateStr).getTime();
  const mins=Math.floor(diff/60000);
  if(mins<1) return 'À l\'instant';
  if(mins<60) return `il y a ${mins}m`;
  const hrs=Math.floor(mins/60);
  if(hrs<24) return `il y a ${hrs}h`;
  const days=Math.floor(hrs/24);
  if(days<7) return `il y a ${days}j`;
  return new Date(dateStr).toLocaleDateString('fr-FR');
}
function icon(name,size=14,extra=''){
  const icons={
    phone:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>`,
    clock:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>`,
    file:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`,
    mic:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>`,
    micoff:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><line x1="1" y1="1" x2="23" y2="23"/><path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"/><path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2a7 7 0 0 1-.11 1.23"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>`,
    check:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
    alert:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
    user:`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" ${extra}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
  };
  return icons[name]??'';
}
function priorityBadge(p){
  const map={high:'badge-priority-high',medium:'badge-priority-medium',low:'badge-priority-low'};
  const cls=map[(p||'').toLowerCase()]||'badge-none';
  return `<span class="${cls}">${p||'—'}</span>`;
}
function setLoading(btn, yes, loadText=''){
  if(!btn) return;
  btn.disabled=yes;
  if(yes && loadText) btn.innerHTML=`<svg class="spinning" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>${loadText}`;
}

// ── LOAD CALL LIST ─────────────────────────────────────────────────
async function loadCalls(){
  const btn=document.getElementById('refreshBtn');
  const ri=document.getElementById('refreshIcon');
  if(ri) ri.classList.add('spinning');
  if(btn) btn.disabled=true;
  try{
    const res=await fetch(apiUrl('/voice-calls?skip=0&limit=100'),{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
    if(!res.ok) throw new Error('HTTP '+res.status);
    const data=await res.json();
    const calls=data.items??[];
    document.getElementById('callCount').textContent=data.total?`(${data.total})`:'';
    renderCallList(calls);
  }catch(e){
    document.getElementById('vcListBody').innerHTML=`
      <div class="vc-empty">
        <div class="vc-empty-icon">${icon('alert',22)}</div>
        <div class="vc-empty-title">Erreur de chargement</div>
        <div class="vc-empty-sub">${e.message}</div>
      </div>`;
  }finally{
    if(ri) ri.classList.remove('spinning');
    if(btn) btn.disabled=false;
  }
}

function renderCallList(calls){
  if(!calls.length){
    document.getElementById('vcListBody').innerHTML=`
      <div class="vc-empty">
        <div class="vc-empty-icon">${icon('phone',22)}</div>
        <div class="vc-empty-title">Aucun appel enregistré</div>
        <div class="vc-empty-sub">Les appels et messages vocaux apparaîtront ici une fois reçus.</div>
      </div>`;
    return;
  }
  document.getElementById('vcListBody').innerHTML=calls.map(c=>`
    <div class="list-row" onclick="selectCall('${c.id}')" style="cursor:pointer;">
      <div class="list-room">
        <div class="list-row-icon">${icon('phone',14)}</div>
        <span class="list-room-name">${escHtml(c.room_name||'—')}</span>
      </div>
      <div class="list-meta">${icon('clock',12)}&nbsp;${fmtDuration(c.duration_seconds)}</div>
      <div>
        <div class="list-date-main">${new Date(c.started_at).toLocaleDateString('fr-FR')}</div>
        <div class="list-date-ago">${timeAgo(c.started_at)}</div>
      </div>
      <div class="list-meta">${icon('file',12)}&nbsp;${c.transcript?c.transcript.length+' car':'<span style="font-style:italic;font-size:11px;">—</span>'}</div>
      <div>${c.audio_file_path
        ?`<span class="badge-recorded">${icon('mic',11)}Enregistré</span>`
        :`<span class="badge-none">${icon('micoff',11)}Aucun</span>`}</div>
    </div>`).join('');
}

function escHtml(str){
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── SELECT CALL ────────────────────────────────────────────────────
function selectCall(callId){
  if(!callId) return;
  window.location.href = VOICE_CALL_DETAIL_BASE.replace(/\/$/, '') + '/' + encodeURIComponent(callId);
}

async function fetchCallDetail(id){
  try{
    const res=await fetch(apiUrl(`/voice-calls/${id}`),{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
    if(!res.ok) return;
    const c=await res.json();
    currentCall=c;
    // Update transcript if richer
    if(c.transcript){
      document.getElementById('dTranscript').innerHTML=`<div class="transcript-box">${escHtml(c.transcript)}</div>`;
    }
  }catch(e){}
}

async function loadAudio(id){
  document.getElementById('audioContent').innerHTML=`
    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--color-text-muted,#64748b);">
      <svg class="spinning" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
      Chargement de l'audio…
    </div>`;
  try{
    const res=await fetch(apiUrl(`/voice-calls/${id}/audio`),{headers:{'X-CSRF-TOKEN':CSRF}});
    if(!res.ok) throw new Error('HTTP '+res.status);
    const blob=await res.blob();
    const url=URL.createObjectURL(blob);
    document.getElementById('audioContent').innerHTML=`<audio controls preload="metadata" src="${url}" style="width:100%;border-radius:12px;accent-color:var(--color-primary,#6366f1);">Votre navigateur ne supporte pas l'audio.</audio>`;
  }catch(e){
    document.getElementById('audioContent').innerHTML=`
      <div class="alert-error">${icon('alert',16)} Impossible de charger l'audio : ${escHtml(e.message)}</div>`;
  }
}

function showListView(){
  document.getElementById('vcDetailView').style.display='none';
  document.getElementById('vcListView').style.display='block';
  document.getElementById('vc-scroll').scrollTop=0;
  currentCall=null; currentSummary=null;
}

function resetDetailState(){
  document.getElementById('summaryContent').innerHTML=`<div style="text-align:center;padding:24px 0;font-size:13px;color:var(--color-text-muted,#64748b);font-style:italic;">Cliquez sur "Générer" pour produire une synthèse IA de cet appel.</div>`;
  const sb=document.getElementById('summaryBtn');
  if(sb){sb.disabled=false;sb.innerHTML=`${icon('',12)} Générer`;sb.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>Générer`;}
  document.getElementById('existingTicketId').value='';
  document.getElementById('ticketSubject').value='';
  document.getElementById('ticketDescription').value='';
  document.getElementById('linkFeedback').style.display='none';
}

// ── GENERATE SUMMARY ───────────────────────────────────────────────
async function generateSummary(){
  if(!currentCall) return;
  const btn=document.getElementById('summaryBtn');
  btn.disabled=true;
  btn.innerHTML=`<svg class="spinning" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>Génération…`;
  document.getElementById('summaryContent').innerHTML=`
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:32px 0;font-size:13px;color:var(--color-text-muted,#64748b);">
      <svg class="spinning" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
      Analyse de la transcription…
    </div>`;
  try{
    const res=await fetch(apiUrl(`/voice-calls/${currentCall.id}/post-call-summary`),{
      method:'POST',
      headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF}
    });
    if(!res.ok){const e=await res.json().catch(()=>({}));throw new Error(e.message||'HTTP '+res.status);}
    const s=await res.json();
    currentSummary=s;
    // Pre-fill ticket fields
    if(!document.getElementById('ticketSubject').value && s.ticket_subject_suggestion)
      document.getElementById('ticketSubject').value=s.ticket_subject_suggestion;
    if(!document.getElementById('ticketDescription').value && s.ticket_description_suggestion)
      document.getElementById('ticketDescription').value=s.ticket_description_suggestion;
    renderSummary(s);
    btn.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>Régénérer`;
  }catch(e){
    document.getElementById('summaryContent').innerHTML=`<div class="alert-error">${icon('alert',16)} ${escHtml(e.message)}</div>`;
    btn.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>Réessayer`;
  }finally{
    btn.disabled=false;
  }
}

function renderSummary(s){
  let html=`
    <div class="summary-grid">
      <div class="summary-block">
        <div class="summary-block-label">Problème client</div>
        <div class="summary-block-text">${escHtml(s.customer_issue||'—')}</div>
      </div>
      <div class="summary-block">
        <div class="summary-block-label">Statut de résolution</div>
        <div class="summary-block-text">${escHtml(s.resolution_status||'—')}</div>
      </div>
    </div>
    <div class="summary-block-full">
      <div class="summary-block-label">Synthèse</div>
      <div class="summary-block-text">${escHtml(s.summary||'—')}</div>
    </div>
    <div class="summary-block-full">
      <div class="summary-block-label">Recommandation de suivi</div>
      <div class="summary-block-text">${escHtml(s.follow_up_recommendation||'—')}</div>
    </div>`;

  if(s.action_items?.length){
    html+=`<div style="margin-top:4px;">
      <div class="summary-block-label" style="margin-bottom:10px;">Éléments d'action</div>`;
    s.action_items.forEach(item=>{
      html+=`<div class="action-item">
        <div style="color:color-mix(in srgb,var(--color-primary,#6366f1) 60%,transparent);flex-shrink:0;margin-top:2px;">${icon('check',16)}</div>
        <div style="flex:1;min-width:0;">
          <div class="action-item-title">${escHtml(item.title||'—')}</div>
          <div class="action-item-meta">
            ${icon('user',12)}&nbsp;${escHtml(item.owner||'—')}
            &nbsp;&nbsp;${priorityBadge(item.priority)}
          </div>
        </div>
      </div>`;
    });
    html+=`</div>`;
  }
  document.getElementById('summaryContent').innerHTML=html;
}

// ── TICKET LINKAGE ─────────────────────────────────────────────────
async function linkExistingTicket(){
  if(!currentCall) return;
  const tid=document.getElementById('existingTicketId').value.trim();
  if(!tid){showLinkFeedback('error','Veuillez saisir l\'ID du ticket existant.');return;}
  const btn=document.getElementById('linkBtn');
  setLoading(btn,true,'Liaison…');
  try{
    const res=await fetch(apiUrl(`/voice-calls/${currentCall.id}/link-ticket`),{
      method:'POST',
      headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body:JSON.stringify({ticket_id:tid})
    });
    if(!res.ok){const e=await res.json().catch(()=>({}));throw new Error(e.message||'HTTP '+res.status);}
    const data=await res.json();
    showLinkFeedback('success',`Ticket <strong>${escHtml(data.ticket_id)}</strong> lié avec succès.`);
  }catch(e){
    showLinkFeedback('error',escHtml(e.message));
  }finally{
    btn.disabled=false;
    btn.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Lier`;
  }
}

async function createLinkedTicket(){
  if(!currentCall) return;
  const subject=document.getElementById('ticketSubject').value.trim();
  const description=document.getElementById('ticketDescription').value.trim();
  if(!subject||!description){showLinkFeedback('error','Générez d\'abord un résumé ou renseignez le sujet et la description.');return;}
  const btn=document.getElementById('createTicketBtn');
  setLoading(btn,true,'Création…');
  try{
    const res=await fetch(apiUrl(`/voice-calls/${currentCall.id}/link-ticket`),{
      method:'POST',
      headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body:JSON.stringify({subject,description})
    });
    if(!res.ok){const e=await res.json().catch(()=>({}));throw new Error(e.message||'HTTP '+res.status);}
    const data=await res.json();
    showLinkFeedback('success',`Ticket <strong>${escHtml(data.ticket_id)}</strong> ${data.link_type==='created'?'créé et lié':'lié'} avec succès.`);
  }catch(e){
    showLinkFeedback('error',escHtml(e.message));
  }finally{
    btn.disabled=false;
    btn.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>Créer & Lier`;
  }
}

async function escalateCall(){
  if(!currentCall) return;
  const btn=document.getElementById('escalateBtn');
  setLoading(btn,true,'Escalade…');
  try{
    let ticketId=document.getElementById('existingTicketId').value.trim()||null;
    // If no existing ticket id, create/link first
    if(!ticketId){
      const subject=document.getElementById('ticketSubject').value.trim()||currentSummary?.ticket_subject_suggestion||'';
      const description=document.getElementById('ticketDescription').value.trim()||currentSummary?.ticket_description_suggestion||'';
      if(!subject&&!description) throw new Error('Renseignez un sujet/description ou liez un ticket existant avant d\'escalader.');
      const lr=await fetch(apiUrl(`/voice-calls/${currentCall.id}/link-ticket`),{
        method:'POST',
        headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({subject,description})
      });
      if(!lr.ok){const e=await lr.json().catch(()=>({}));throw new Error(e.message||'Lien ticket échoué');}
      const ld=await lr.json();
      ticketId=ld.ticket_id;
    }
    // Update ticket status to escalated
    const er=await fetch(apiUrl(`/tickets/${ticketId}/status`),{
      method:'POST',
      headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
      body:JSON.stringify({status:'escalated'})
    });
    if(!er.ok){const e=await er.json().catch(()=>({}));throw new Error(e.message||'Escalade échouée');}
    showLinkFeedback('success',`Ticket <strong>${escHtml(ticketId)}</strong> escaladé avec succès.`);
  }catch(e){
    showLinkFeedback('error',escHtml(e.message));
  }finally{
    btn.disabled=false;
    btn.innerHTML=`<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>Escalader`;
  }
}

function showLinkFeedback(type, msg){
  const el=document.getElementById('linkFeedback');
  el.style.display='flex';
  el.className=type==='success'?'alert-success':'alert-error';
  el.innerHTML=`${icon(type==='success'?'check':'alert',16)} <span>${msg}</span>`;
}

// ── INIT ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>loadCalls());
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/super-admin/voice-calls.blade.php ENDPATH**/ ?>