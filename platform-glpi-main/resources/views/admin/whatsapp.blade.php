@extends('layouts.dashboard')
@section('title', 'WhatsApp — L2T Support')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
<style>
.main-content{padding:0!important;overflow:hidden;}
*{box-sizing:border-box;margin:0;padding:0;}

/* ══ SCREENS OVERLAY ══ */
#wa-screens{
  position:absolute;inset:0;z-index:999;
  background:#111b21;
  display:flex;align-items:center;justify-content:center;
}

/* Loading */
#scr-loading{
  display:flex;flex-direction:column;align-items:center;gap:20px;
  animation:fadeIn .4s ease;
}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.wa-logo-big{width:72px;height:72px;color:#aebac1;}
.wa-loading-bar{width:180px;height:3px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden;}
.wa-loading-fill{height:100%;width:0;background:#00a884;border-radius:2px;animation:loadBar 1.8s cubic-bezier(.4,0,.2,1) forwards;}
@keyframes loadBar{0%{width:0}60%{width:75%}100%{width:100%}}
.wa-loading-enc{display:flex;align-items:center;gap:6px;font-size:13px;color:#8696a0;font-family:'Segoe UI',system-ui,sans-serif;}

/* Connect card */
#scr-connect{
  display:none;width:100%;height:100%;
  align-items:center;justify-content:center;animation:fadeIn .35s ease;
}
.connect-card{
  background:#1f2937;border-radius:16px;
  width:min(720px,90vw);position:relative;
  box-shadow:0 32px 80px rgba(0,0,0,.5);overflow:hidden;
}
.back-btn{
  position:absolute;top:20px;left:20px;width:32px;height:32px;border-radius:50%;
  background:transparent;border:none;cursor:pointer;color:#aebac1;
  display:none;align-items:center;justify-content:center;transition:background .15s;z-index:2;
}
.back-btn:hover{background:rgba(255,255,255,.08);}

/* QR screen */
#scr-qr{display:none;padding:40px 48px;animation:fadeIn .3s ease;}
.qr-layout{display:flex;gap:60px;align-items:flex-start;}
.qr-left h1{font-size:24px;font-weight:400;color:#e9edef;margin-bottom:28px;line-height:1.3;}
.qr-steps-list{display:flex;flex-direction:column;gap:20px;}
.qr-step-row{display:flex;align-items:flex-start;gap:12px;}
.qr-step-num{width:24px;height:24px;border-radius:50%;background:#374151;border:1.5px solid #4b5563;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#e9edef;flex-shrink:0;margin-top:1px;}
.qr-step-text{font-size:14px;color:#aebac1;line-height:1.5;}
.qr-step-text strong{color:#e9edef;}
.qr-help{margin-top:22px;font-size:14px;color:#00a884;cursor:pointer;}
.qr-help:hover{text-decoration:underline;}
.qr-right{display:flex;flex-direction:column;align-items:center;gap:14px;flex-shrink:0;}
.qr-box{width:230px;height:230px;background:#fff;border-radius:8px;padding:10px;display:flex;align-items:center;justify-content:center;position:relative;}
.qr-box img{display:block;border-radius:6px;}
.qr-refresh-overlay{
  position:absolute;inset:0;border-radius:8px;background:rgba(255,255,255,.92);
  display:none;align-items:center;justify-content:center;flex-direction:column;gap:10px;
}
.qr-refresh-overlay.show{display:flex;}
.qr-regen-btn{padding:8px 18px;border-radius:99px;background:#00a884;border:none;color:#fff;font-size:13px;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;}
.qr-regen-btn:hover{background:#00cf9d;}
.qr-timer-txt{font-size:13px;color:#8696a0;display:flex;align-items:center;gap:5px;}
.qr-phone-link{color:#00a884;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;}
.qr-phone-link:hover{text-decoration:underline;}

/* Phone screen */
#scr-phone{display:none;padding:52px 48px;text-align:center;animation:fadeIn .3s ease;}
#scr-phone h1{font-size:24px;font-weight:400;color:#e9edef;margin-bottom:32px;}
.phone-form{display:flex;flex-direction:column;align-items:center;gap:12px;max-width:360px;margin:0 auto 28px;}
.country-select{width:100%;padding:12px 16px;border-radius:99px;background:#374151;border:1.5px solid #4b5563;color:#e9edef;font-size:15px;font-family:inherit;cursor:default;display:flex;align-items:center;justify-content:space-between;}
.phone-input-wrap{width:100%;padding:12px 16px;border-radius:99px;background:#374151;border:1.5px solid #4b5563;display:flex;align-items:center;gap:8px;}
.phone-input-wrap input{flex:1;background:transparent;border:none;outline:none;font-size:15px;color:#e9edef;font-family:inherit;letter-spacing:.5px;}
.phone-input-wrap input::placeholder{color:#4b5563;}
.phone-next-btn{padding:13px 40px;border-radius:99px;background:#00a884;border:none;color:#fff;font-size:15px;font-weight:500;cursor:pointer;font-family:inherit;transition:background .15s;margin-top:8px;}
.phone-next-btn:hover{background:#00cf9d;}
.phone-qr-link{color:#00a884;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;justify-content:center;}
.phone-qr-link:hover{text-decoration:underline;}

/* Code screen */
#scr-code{display:none;padding:42px 56px;animation:fadeIn .3s ease;}
#scr-code h1{font-size:24px;font-weight:400;color:#e9edef;margin-bottom:4px;}
.code-edit{color:#00a884;font-size:14px;cursor:pointer;}
.code-edit:hover{text-decoration:underline;}
.code-box{width:100%;padding:18px;border-radius:12px;background:#fff;margin:18px 0 28px;display:flex;align-items:center;justify-content:center;gap:8px;}
.code-char{width:52px;height:52px;border-radius:8px;background:#f0f2f5;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:500;color:#111b21;font-family:'Courier New',monospace;}
.code-dash{font-size:24px;font-weight:300;color:#54656f;}
.code-steps{display:flex;flex-direction:column;gap:18px;}
.code-step-row{display:flex;align-items:flex-start;gap:14px;}
.code-step-num{width:26px;height:26px;border-radius:50%;background:#374151;border:1.5px solid #4b5563;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#e9edef;flex-shrink:0;margin-top:1px;}
.code-step-text{font-size:14px;color:#aebac1;line-height:1.55;}
.code-step-text strong{color:#e9edef;}

/* ══ MAIN UI ══ */
#wa-root{
  display:none;height:calc(100vh - 64px);
  font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
  overflow:hidden;background:#111b21;
}

/* Left */
#wa-left{width:30%;min-width:340px;max-width:420px;display:flex;flex-direction:column;background:#111b21;border-right:1px solid rgba(134,150,160,.2);}
.wa-top-bar{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:#202c33;flex-shrink:0;}
.wa-my-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#00a884,#00cf9d);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;cursor:pointer;}
.wa-top-icons{display:flex;gap:4px;}
.wa-top-icon{width:40px;height:40px;border-radius:50%;background:transparent;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#aebac1;transition:background .15s;}
.wa-top-icon:hover{background:rgba(255,255,255,.08);}
.wa-tabs-bar{display:flex;border-bottom:1px solid rgba(134,150,160,.15);padding:0 16px;background:#111b21;flex-shrink:0;}
.wa-tab{padding:10px 12px;font-size:13px;font-weight:600;color:#8696a0;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;font-family:inherit;}
.wa-tab.active{color:#00a884;border-bottom-color:#00a884;}
.wa-tab-badge{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#00a884;color:#fff;font-size:10px;font-weight:700;margin-left:5px;vertical-align:middle;}
.wa-search-wrap{padding:8px 12px;background:#111b21;flex-shrink:0;}
.wa-search-box{display:flex;align-items:center;gap:9px;background:#202c33;border-radius:8px;padding:7px 14px;}
.wa-search-box input{flex:1;background:transparent;border:none;outline:none;font-size:14px;color:#e9edef;font-family:inherit;}
.wa-search-box input::placeholder{color:#8696a0;}
.wa-conv-list{flex:1;overflow-y:auto;}
.wa-conv-list::-webkit-scrollbar{width:6px;}
.wa-conv-list::-webkit-scrollbar-thumb{background:#2a3942;border-radius:3px;}
.wa-conv-item{display:flex;align-items:center;gap:13px;padding:12px 16px;cursor:pointer;border-bottom:1px solid rgba(134,150,160,.08);transition:background .12s;}
.wa-conv-item:hover{background:#202c33;}
.wa-conv-item.active{background:#2a3942;}
.wa-av{width:49px;height:49px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:17px;color:#fff;flex-shrink:0;}
.wa-ci-info{flex:1;min-width:0;}
.wa-ci-top{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:3px;}
.wa-ci-name{font-size:15px;font-weight:400;color:#e9edef;}
.wa-ci-time{font-size:11.5px;color:#8696a0;flex-shrink:0;}
.wa-ci-time.unread{color:#00a884;}
.wa-ci-bot{display:flex;align-items:center;justify-content:space-between;}
.wa-ci-prev{font-size:13.5px;color:#8696a0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
.wa-badge{background:#00a884;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:5px;}
.ia-chip-wa{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;background:rgba(108,99,255,.18);color:#8B85FF;margin-left:4px;flex-shrink:0;}

/* Right */
#wa-right{flex:1;display:flex;overflow:hidden;}
#wa-intro{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#222e35;text-align:center;gap:12px;padding:20px;}
.wa-intro-icon{width:80px;height:80px;color:#aebac1;margin-bottom:4px;}
.wa-intro-title{font-size:32px;font-weight:300;color:#e9edef;}
.wa-intro-sub{font-size:14px;color:#8696a0;max-width:380px;line-height:1.6;}
.wa-intro-enc{display:flex;align-items:center;gap:6px;margin-top:16px;font-size:13px;color:#8696a0;padding:8px 16px;border-top:1px solid rgba(134,150,160,.15);}

/* Active chat area */
#wa-active{display:none;flex-direction:column;flex:1;overflow:hidden;}
.wa-chat-hdr{display:flex;align-items:center;gap:12px;padding:10px 16px;background:#202c33;border-bottom:1px solid rgba(134,150,160,.1);flex-shrink:0;}
.wa-chat-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;cursor:pointer;flex-shrink:0;}
.wa-chat-name{font-size:15px;font-weight:600;color:#e9edef;}
.wa-chat-sub{font-size:12.5px;color:#8696a0;margin-top:1px;}
.wa-chat-actions{margin-left:auto;display:flex;gap:4px;align-items:center;}

/* SLA chip */
.sla-chip{display:inline-flex;align-items:center;gap:4px;border-radius:99px;border:1px solid;padding:2px 8px;font-size:11px;font-weight:500;}
.sla-chip.pending{background:#0d2a3a;border-color:#164e63;color:#67e8f9;}
.sla-chip.overdue{background:#2d1515;border-color:#7f1d1d;color:#fca5a5;}
.sla-chip.none{background:#1a1a1a;border-color:#374151;color:#6b7280;}

/* Messages */
.wa-msgs{flex:1;overflow-y:auto;padding:12px 8%;display:flex;flex-direction:column;gap:2px;background-color:#0b141a;}
.wa-msgs::-webkit-scrollbar{width:6px;}
.wa-msgs::-webkit-scrollbar-thumb{background:#2a3942;border-radius:3px;}
.wa-date-sep{text-align:center;margin:8px 0;}
.wa-date-sep span{background:#182229;color:#8696a0;font-size:12px;padding:5px 12px;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.3);}
.wa-msg-row{display:flex;margin-bottom:2px;}
.wa-msg-row.out{justify-content:flex-end;}
.wa-msg-row.in{justify-content:flex-start;}
.wa-bubble{max-width:65%;padding:6px 10px 8px;border-radius:8px;font-size:14.5px;line-height:1.5;word-break:break-word;position:relative;}
.wa-bubble.out{background:#005c4b;color:#e9edef;border-top-right-radius:2px;}
.wa-bubble.in{background:#202c33;color:#e9edef;border-top-left-radius:2px;}
.wa-meta{display:flex;justify-content:flex-end;align-items:center;gap:3px;font-size:11px;color:rgba(134,150,160,.85);margin-top:2px;}
.wa-tick{color:#53bdeb;font-size:13px;}

/* sys msg */
.wa-sys-row{display:flex;justify-content:center;margin:8px 0;}
.wa-sys-bubble{background:#182229;border-radius:8px;padding:6px 14px;font-size:12px;color:#8696a0;border-left:3px solid #00a884;max-width:400px;text-align:left;}
.wa-sys-bubble strong{color:#e9edef;}

/* Input area */
.wa-input-wrap{display:flex;align-items:flex-end;gap:10px;padding:10px 16px;background:#202c33;border-top:1px solid rgba(134,150,160,.1);flex-shrink:0;}
.wa-inp-icon{width:42px;height:42px;border-radius:50%;background:transparent;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#aebac1;transition:color .14s;flex-shrink:0;}
.wa-inp-icon:hover{color:#e9edef;}
.wa-input-box{flex:1;background:#2a3942;border-radius:10px;padding:10px 14px;border:none;outline:none;font-size:15px;color:#e9edef;font-family:inherit;resize:none;max-height:140px;overflow-y:auto;min-height:22px;line-height:1.5;}
.wa-input-box::placeholder{color:#8696a0;}
.wa-input-box.ai-draft{border:1px solid rgba(139,133,255,.4);background:rgba(139,133,255,.07);}
.wa-send-btn{width:44px;height:44px;border-radius:50%;border:none;cursor:pointer;background:#00a884;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .14s;}
.wa-send-btn:hover:not(:disabled){background:#00cf9d;}
.wa-send-btn:disabled{background:#374151;cursor:not-allowed;}
.wa-typing-bbl{background:#202c33;border-top-left-radius:2px;padding:10px 14px;}
.wa-typing-dots{display:flex;gap:4px;align-items:center;}
.wa-typing-dots span{width:8px;height:8px;border-radius:50%;background:#8696a0;animation:wt .8s ease-in-out infinite;}
.wa-typing-dots span:nth-child(2){animation-delay:.15s;}
.wa-typing-dots span:nth-child(3){animation-delay:.30s;}
@keyframes wt{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

/* AI toolbar */
.wa-ai-bar{display:flex;align-items:center;gap:8px;padding:6px 16px;background:#111b21;border-top:1px solid rgba(134,150,160,.08);flex-shrink:0;flex-wrap:wrap;}
.wa-ai-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:2px 7px;border-radius:99px;border:1px solid rgba(139,133,255,.4);background:rgba(139,133,255,.1);color:#8B85FF;}
.wa-ai-btn{padding:5px 12px;border-radius:99px;border:1px solid rgba(134,150,160,.2);background:transparent;font-size:12px;font-weight:600;color:#aebac1;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;transition:all .15s;}
.wa-ai-btn:hover:not(:disabled){border-color:#00a884;color:#00a884;background:rgba(0,168,132,.08);}
.wa-ai-btn:disabled{opacity:.4;cursor:not-allowed;}
.wa-ai-btn.active{border-color:#00a884;color:#00a884;background:rgba(0,168,132,.1);}
.wa-snippet-select{background:#202c33;border:1px solid rgba(134,150,160,.2);border-radius:7px;color:#aebac1;font-size:12px;padding:4px 8px;font-family:inherit;outline:none;max-width:180px;}
.wa-snippet-select:focus{border-color:#00a884;}

/* Suspension banner */
.wa-suspension-banner{display:flex;align-items:flex-start;gap:8px;padding:8px 16px;background:rgba(239,68,68,.08);border-top:1px solid rgba(239,68,68,.2);flex-shrink:0;}
.wa-suspension-banner p{font-size:12px;color:#fca5a5;}
.wa-suspension-banner strong{color:#ef4444;}

/* Mark read button */
.wa-mark-read-btn{padding:5px 12px;border-radius:99px;border:1px solid rgba(134,150,160,.2);background:transparent;font-size:12px;font-weight:600;color:#aebac1;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:5px;transition:all .15s;}
.wa-mark-read-btn:hover{border-color:#00a884;color:#00a884;}

/* Context panel (right sidebar) */
#wa-context{width:280px;flex-shrink:0;display:flex;flex-direction:column;background:#111b21;border-left:1px solid rgba(134,150,160,.2);overflow-y:auto;}
#wa-context::-webkit-scrollbar{width:4px;}
#wa-context::-webkit-scrollbar-thumb{background:#2a3942;border-radius:2px;}
.ctx-header{padding:12px 14px;border-bottom:1px solid rgba(134,150,160,.15);background:#202c33;flex-shrink:0;}
.ctx-header p{font-size:13px;font-weight:600;color:#e9edef;}
.ctx-header span{font-size:11px;color:#8696a0;}
.ctx-section{border-radius:10px;border:1px solid rgba(134,150,160,.15);background:rgba(32,44,51,.5);margin:8px;overflow:hidden;}
.ctx-section-hdr{display:flex;align-items:center;justify-content:space-between;padding:9px 12px;cursor:pointer;user-select:none;}
.ctx-section-label{display:flex;align-items:center;gap:6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8696a0;}
.ctx-section-body{padding:10px 12px;border-top:1px solid rgba(134,150,160,.1);}
.ctx-field{display:grid;grid-template-columns:62px minmax(0,1fr);gap:4px 10px;align-items:start;margin-bottom:6px;}
.ctx-field-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#8696a0;padding-top:1px;}
.ctx-field-val{font-size:12px;color:#e9edef;word-break:break-all;line-height:1.5;}
.ctx-field-val.mono{font-family:'Courier New',monospace;font-size:11px;}
.ctx-status-pill{display:flex;align-items:center;gap:6px;border-radius:8px;padding:7px 10px;font-size:11px;font-weight:600;margin-bottom:8px;}
.ctx-status-pill.active{background:rgba(0,168,132,.1);border:1px solid rgba(0,168,132,.25);color:#00a884;}
.ctx-status-pill.inactive{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);color:#f59e0b;}
.ctx-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.ctx-toggle-row{display:flex;align-items:center;justify-content:space-between;background:rgba(0,0,0,.2);border-radius:8px;padding:7px 10px;margin-bottom:8px;}
.ctx-toggle-label{font-size:12px;color:#e9edef;}
.ctx-toggle-sub{font-size:10px;color:#8696a0;margin-top:1px;}
.ctx-switch{position:relative;width:36px;height:20px;flex-shrink:0;}
.ctx-switch input{opacity:0;width:0;height:0;}
.ctx-switch-slider{position:absolute;inset:0;background:#374151;border-radius:99px;cursor:pointer;transition:background .2s;}
.ctx-switch-slider:before{content:'';position:absolute;width:14px;height:14px;border-radius:50%;background:#fff;left:3px;top:3px;transition:transform .2s;}
.ctx-switch input:checked+.ctx-switch-slider{background:#00a884;}
.ctx-switch input:checked+.ctx-switch-slider:before{transform:translateX(16px);}
.ctx-switch input:disabled+.ctx-switch-slider{opacity:.4;cursor:not-allowed;}
.ctx-pause-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:4px;}
.ctx-pause-btn{padding:6px;border-radius:99px;border:1px solid rgba(134,150,160,.2);background:transparent;font-size:11px;font-weight:600;color:#aebac1;cursor:pointer;font-family:inherit;transition:all .15s;text-align:center;}
.ctx-pause-btn:hover:not(:disabled){border-color:#00a884;color:#00a884;}
.ctx-pause-btn:disabled{opacity:.4;cursor:not-allowed;}
.ctx-note{font-size:10px;color:#8696a0;margin-top:6px;}
.ctx-summary-section p{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8696a0;margin-bottom:4px;}
.ctx-summary-section .body{font-size:12px;color:#aebac1;line-height:1.6;margin-bottom:10px;}
.ctx-res-badge{display:inline-flex;align-items:center;border-radius:99px;border:1px solid;padding:2px 8px;font-size:11px;font-weight:500;margin-bottom:4px;}
.ctx-res-badge.resolved{border-color:#065f46;background:rgba(6,95,70,.2);color:#34d399;}
.ctx-res-badge.partially_resolved{border-color:#78350f;background:rgba(120,53,15,.15);color:#fbbf24;}
.ctx-res-badge.in_progress{border-color:#1e3a5f;background:rgba(30,58,95,.2);color:#60a5fa;}
.ctx-res-badge.unresolved{border-color:#7f1d1d;background:rgba(127,29,29,.15);color:#f87171;}
.ctx-snapshot{margin:8px;padding:12px;border-radius:10px;border:1px solid rgba(134,150,160,.15);background:rgba(32,44,51,.5);}
.ctx-snapshot-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#8696a0;margin-bottom:10px;}
.ctx-snap-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.ctx-snap-item p:first-child{font-size:10px;color:#8696a0;}
.ctx-snap-item p:last-child{font-size:12px;font-weight:600;color:#e9edef;margin-top:2px;}
.ctx-ticket-btn{margin:4px 8px 8px;width:calc(100% - 16px);padding:10px;border-radius:99px;border:none;background:#00a884;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:7px;transition:background .15s;}
.ctx-ticket-btn:hover:not(:disabled){background:#00cf9d;}
.ctx-ticket-btn:disabled{background:#374151;cursor:not-allowed;color:#6b7280;}
.ctx-chevron{width:14px;height:14px;color:#8696a0;transition:transform .2s;}
.ctx-chevron.open{transform:rotate(180deg);}
.ctx-hidden{display:none;}

/* Ticket modal */
.wa-modal-bg{display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;}
.wa-modal-bg.on{display:flex;}
.wa-modal{background:#1f2937;border-radius:14px;width:500px;max-width:95vw;box-shadow:0 24px 60px rgba(0,0,0,.5);animation:fadeIn .2s ease;}
.wa-modal-hdr{padding:18px 22px 14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between;}
.wa-modal-title{font-size:15px;font-weight:600;color:#e9edef;display:flex;align-items:center;gap:8px;}
.wa-modal-close{background:none;border:none;cursor:pointer;color:#8696a0;font-size:18px;}
.wa-modal-body{padding:18px 22px;}
.wa-f-grp{margin-bottom:14px;}
.wa-f-lbl{font-size:11px;font-weight:700;color:#8696a0;margin-bottom:5px;display:block;text-transform:uppercase;letter-spacing:.04em;}
.wa-f-in,.wa-f-sel,.wa-f-ta{width:100%;padding:9px 12px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:#111b21;font-size:13px;color:#e9edef;font-family:inherit;outline:none;}
.wa-f-in:focus,.wa-f-sel:focus,.wa-f-ta:focus{border-color:#00a884;box-shadow:0 0 0 3px rgba(0,168,132,.15);}
.wa-f-ta{resize:vertical;min-height:80px;line-height:1.6;}
.wa-f-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.wa-modal-foot{padding:12px 22px 18px;display:flex;justify-content:flex-end;gap:8px;}
.wa-btn-cancel{padding:8px 16px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:transparent;font-size:13px;color:#aebac1;cursor:pointer;font-family:inherit;}
.wa-btn-save{padding:8px 18px;border-radius:8px;border:none;background:#00a884;color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px;}
.wa-btn-save:hover{background:#00cf9d;}
.wa-ai-pre{background:rgba(0,168,132,.1);border:1px solid rgba(0,168,132,.25);border-radius:9px;padding:9px 12px;font-size:12px;color:#aebac1;margin-bottom:12px;display:flex;gap:8px;}

/* Toast */
#wa-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#202c33;color:#e9edef;padding:10px 20px;border-radius:10px;font-size:13px;font-family:'Segoe UI',sans-serif;z-index:9000;box-shadow:0 4px 20px rgba(0,0,0,.5);transition:transform .3s cubic-bezier(.34,1.56,.64,1);pointer-events:none;}
#wa-toast.show{transform:translateX(-50%) translateY(0);}

/* Spinner */
.spin{animation:spin .7s linear infinite;}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
</style>

<div style="position:relative;height:calc(100vh - 64px);overflow:hidden;display:flex;flex-direction:column;">

{{-- ══ SCREENS OVERLAY ══ --}}
<div id="wa-screens">
  <div id="scr-loading">
    <svg class="wa-logo-big" viewBox="0 0 60 60" fill="currentColor">
      <path d="M30 0C13.4 0 0 13.4 0 30c0 5.9 1.7 11.5 4.7 16.2L.2 59l13.2-4.3C17.8 57.5 23.7 59 30 59c16.6 0 30-13.4 30-30S46.6 0 30 0zm0 53.7c-5.5 0-10.6-1.7-14.9-4.7l-.4-.3-9.6 3.1 2-9.4-.4-.4C4.5 38.4 3 34.4 3 30 3 15 15 3 30 3s27 12 27 27-12 27-27 27z"/>
      <path d="M43.2 35.5c-.8-.4-4.7-2.3-5.4-2.6-.7-.3-1.2-.4-1.7.4-.5.8-2 2.6-2.5 3.1-.5.5-.9.6-1.7.2-.8-.4-3.4-1.2-6.4-4-2.4-2.1-4-4.7-4.4-5.5-.4-.8 0-1.2.3-1.6.3-.3.8-.9 1.2-1.4.4-.5.5-.8.8-1.3.3-.5.1-1-.1-1.4-.2-.4-1.7-4.1-2.4-5.6-.6-1.5-1.2-1.3-1.7-1.3l-1.5-.1c-.5 0-1.3.2-2 .9-.7.7-2.7 2.6-2.7 6.4s2.8 7.5 3.2 8c.4.5 5.5 8.4 13.3 11.8 1.9.8 3.4 1.3 4.5 1.7 1.9.6 3.6.5 5 .3 1.5-.2 4.7-1.9 5.3-3.8.7-1.9.7-3.5.5-3.8-.3-.4-.7-.6-1.5-1z"/>
    </svg>
    <div style="font-size:24px;font-weight:300;color:#e9edef;font-family:'Segoe UI',sans-serif;">WhatsApp</div>
    <div class="wa-loading-bar"><div class="wa-loading-fill"></div></div>
    <div class="wa-loading-enc">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Chiffré de bout en bout
    </div>
  </div>

  <div id="scr-connect">
    <div class="connect-card">
      <button class="back-btn" id="backBtn" onclick="goBack()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      </button>

      {{-- QR --}}
      <div id="scr-qr">
        <div class="qr-layout">
          <div class="qr-left">
            <h1>Connectez-vous sur WhatsApp</h1>
            <div class="qr-steps-list">
              <div class="qr-step-row"><div class="qr-step-num">1</div><div class="qr-step-text">Scannez le code QR avec la caméra de votre téléphone</div></div>
              <div class="qr-step-row"><div class="qr-step-num">2</div><div class="qr-step-text">Appuyez sur le lien pour ouvrir <strong>WhatsApp</strong></div></div>
              <div class="qr-step-row"><div class="qr-step-num">3</div><div class="qr-step-text">Scannez à nouveau le code QR pour associer votre navigateur Web à votre compte</div></div>
            </div>
            <div class="qr-help">Besoin d'aide ? ↗</div>
          </div>
          <div class="qr-right">
            <div class="qr-box" style="width:230px;height:230px;position:relative;">
              {{-- Real QR image fetched from VITE_QR_BRIDGE_URL via buildQrPngUrl() --}}
              <img id="qrImg" src="" alt="QR Code WhatsApp"
                style="width:210px;height:210px;border-radius:6px;object-fit:contain;display:block;"
                onload="onQrImageLoad()"
                onerror="onQrImageError()">
              <div class="qr-refresh-overlay" id="qrRefreshOverlay">
                <div style="font-size:13px;color:#54656f;text-align:center;margin-bottom:4px;" id="qrOverlayMsg">Le code QR a expiré</div>
                <div style="font-size:11px;color:#8696a0;margin-bottom:8px;" id="qrOverlaySub"></div>
                <button class="qr-regen-btn" onclick="regenQr()">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                  Cliquer pour rafraîchir
                </button>
              </div>
            </div>
            <div class="qr-timer-txt">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              <span id="qrCountdown">60</span>s
            </div>
            <div class="qr-phone-link" onclick="showPhoneScreen()">
              Se connecter avec un numéro de téléphone
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00a884" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
            </div>
            <a href="{{ config('services.whatsapp.qr_bridge_url', env('VITE_QR_BRIDGE_URL', 'http://localhost:3000/qr')) }}"
               target="_blank" rel="noreferrer"
               style="margin-top:4px;padding:9px 22px;border-radius:99px;background:transparent;border:1px solid rgba(134,150,160,.3);color:#aebac1;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:7px;text-decoration:none;justify-content:center;transition:all .15s;"
               onmouseover="this.style.borderColor='#00a884';this.style.color='#00a884';"
               onmouseout="this.style.borderColor='rgba(134,150,160,.3)';this.style.color='#aebac1';">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              Ouvrir l'URL du bridge
            </a>
            <button onclick="connectWa()" style="padding:9px 22px;border-radius:99px;background:rgba(0,168,132,.12);border:1.5px dashed rgba(0,168,132,.45);color:#00a884;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:7px;transition:all .15s;width:100%;justify-content:center;"
              onmouseover="this.style.background='rgba(0,168,132,.22)'"
              onmouseout="this.style.background='rgba(0,168,132,.12)'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              Voir l'interface (démo)
            </button>
          </div>
        </div>
      </div>

      {{-- PHONE --}}
      <div id="scr-phone">
        <h1>Saisissez un numéro de téléphone.</h1>
        <div class="phone-form">
          <div class="country-select">
            <span>🇹🇳 Tunisie</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <div class="phone-input-wrap">
            <span style="color:#aebac1;font-size:15px;">+216</span>
            <input type="tel" id="phoneInput" placeholder="__ ___ ___" maxlength="11" oninput="formatPhone(this)" onkeydown="if(event.key==='Enter')goToCodeScreen()">
          </div>
          <button class="phone-next-btn" onclick="goToCodeScreen()">Suivant</button>
        </div>
        <div class="phone-qr-link" onclick="showQrScreen()">
          Se connecter avec un code QR
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00a884" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg>
        </div>
      </div>

      {{-- CODE --}}
      <div id="scr-code">
        <h1>Saisissez le code sur le téléphone <span class="code-edit" onclick="showPhoneScreen()">modifier</span></h1>
        <div class="code-box">
          <div class="code-char" id="c1">J</div><div class="code-char" id="c2">Z</div>
          <div class="code-char" id="c3">G</div><div class="code-char" id="c4">J</div>
          <div class="code-dash">-</div>
          <div class="code-char" id="c5">V</div><div class="code-char" id="c6">9</div>
          <div class="code-char" id="c7">T</div><div class="code-char" id="c8">A</div>
        </div>
        <div class="code-steps">
          <div class="code-step-row"><div class="code-step-num">1</div><div class="code-step-text">Ouvrez <strong>WhatsApp</strong> sur votre téléphone</div></div>
          <div class="code-step-row"><div class="code-step-num">2</div><div class="code-step-text">Sur Android, appuyez sur <strong>Menu ⋮</strong> · Sur iPhone, appuyez sur <strong>Paramètres ⚙</strong></div></div>
          <div class="code-step-row"><div class="code-step-num">3</div><div class="code-step-text">Appuyez sur <strong>Appareils connectés</strong>, puis <strong>Connecter un appareil</strong></div></div>
          <div class="code-step-row"><div class="code-step-num">4</div><div class="code-step-text">Appuyez sur <strong>Connecter avec un numéro de téléphone</strong> et saisissez ce code</div></div>
        </div>
        <div style="margin-top:28px;text-align:center;">
          <button onclick="connectWa()" style="padding:12px 32px;border-radius:99px;background:#00a884;border:none;color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;">✓ Confirmer la connexion</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══ MAIN UI ══ --}}
<div id="wa-root" style="display:flex;flex:1;">
  {{-- Left sidebar --}}
  <div id="wa-left">
    <div class="wa-top-bar">
      <div class="wa-my-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
      <div class="wa-top-icons">
        <button class="wa-top-icon" id="statusIcon" title="Statut connexion">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="spin"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </button>
        <button class="wa-top-icon" title="Rafraîchir" onclick="refreshAll()">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
        </button>
        <button class="wa-top-icon" title="Menu">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
        </button>
      </div>
    </div>
    <div style="padding:4px 14px 6px;font-size:11px;" id="waStatusTxt">
      <span style="color:#8696a0;">Vérification du statut…</span>
    </div>
    <div class="wa-tabs-bar">
      <button class="wa-tab active" onclick="switchTab('all',this)">Toutes</button>
      <button class="wa-tab" onclick="switchTab('unread',this)">Non lues <span class="wa-tab-badge" id="unreadBadge" style="display:none;">0</span></button>
      <button class="wa-tab" onclick="switchTab('ia',this)">IA Auto</button>
      <button class="wa-tab" onclick="switchTab('tickets',this)">Tickets</button>
    </div>
    <div class="wa-search-wrap">
      <div class="wa-search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8696a0" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" placeholder="Rechercher ou démarrer une discussion" id="waSearch" oninput="filterConvs(this.value)">
      </div>
    </div>
    <div class="wa-conv-list" id="waConvList">
      <div style="padding:24px;text-align:center;color:#8696a0;font-size:13px;">Chargement…</div>
    </div>
  </div>

  {{-- Right: thread + context --}}
  <div id="wa-right">
    <div id="wa-intro">
      <svg class="wa-intro-icon" viewBox="0 0 60 60" fill="currentColor">
        <path d="M30 0C13.4 0 0 13.4 0 30c0 5.9 1.7 11.5 4.7 16.2L.2 59l13.2-4.3C17.8 57.5 23.7 59 30 59c16.6 0 30-13.4 30-30S46.6 0 30 0zm0 53.7c-5.5 0-10.6-1.7-14.9-4.7l-.4-.3-9.6 3.1 2-9.4-.4-.4C4.5 38.4 3 34.4 3 30 3 15 15 3 30 3s27 12 27 27-12 27-27 27z"/>
      </svg>
      <div class="wa-intro-title">WhatsApp Business</div>
      <div class="wa-intro-sub">Supervision des conversations clients L2T. Gérez les tickets et escaladez directement depuis cette interface.</div>
      <div class="wa-intro-enc">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Chiffré de bout en bout · L2T Support IA
      </div>
    </div>

    <div id="wa-active">
      {{-- Chat header --}}
      <div class="wa-chat-hdr">
        <div class="wa-chat-av" id="chatHdrAv"></div>
        <div style="min-width:0;">
          <div class="wa-chat-name" id="chatHdrName"></div>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="wa-chat-sub" id="chatHdrSub"></div>
            <span id="slaHeaderChip"></span>
          </div>
        </div>
        <div class="wa-chat-actions">
          <button class="wa-mark-read-btn" id="markReadBtn" style="display:none;" onclick="doMarkRead()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Marquer comme lu
          </button>
          <button class="wa-top-icon" onclick="showTicketModal()" title="Créer ticket" style="color:#00a884;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </button>
          <button class="wa-top-icon" title="Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
          </button>
        </div>
      </div>

      {{-- Messages --}}
      <div class="wa-msgs" id="waMsgs">
        <div style="padding:24px;text-align:center;color:#8696a0;font-size:13px;">Sélectionnez une conversation.</div>
      </div>

      {{-- Suspension banner (hidden by default) --}}
      <div class="wa-suspension-banner" id="suspensionBanner" style="display:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
          <strong>Réponses suspendues</strong>
          <p id="suspensionReason">Un administrateur a suspendu vos réponses.</p>
        </div>
      </div>

      {{-- AI toolbar --}}
      <div class="wa-ai-bar" id="waAiBar">
        <label class="ctx-switch" title="Mode brouillon IA">
          <input type="checkbox" id="aiDraftToggle" checked onchange="onAiDraftToggleChange()">
          <span class="ctx-switch-slider"></span>
        </label>
        <div>
          <div style="font-size:12px;font-weight:600;color:#e9edef;">Brouillon IA</div>
          <div style="font-size:10px;color:#8696a0;" id="aiDraftAvailLabel">Suggestions auto</div>
        </div>
        <span class="wa-ai-badge" id="aiDraftBadge" style="display:none;">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          Brouillon IA
        </span>
        <button class="wa-ai-btn" id="generateDraftBtn" onclick="doGenerateDraft()" disabled>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          Générer
        </button>

        {{-- Snippets --}}
        <select class="wa-snippet-select" id="snippetSelect">
          <option value="">Sélectionner un snippet…</option>
        </select>
        <button class="wa-ai-btn" id="insertSnippetBtn" onclick="doInsertSnippet()" disabled>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Insérer
        </button>
      </div>

      {{-- Input --}}
      <div class="wa-input-wrap">
        <button class="wa-inp-icon" title="Emoji">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
        </button>
        <textarea class="wa-input-box" id="waInput" rows="1"
          placeholder="Entrez un message (Ctrl+Entrée pour envoyer)"
          onkeydown="if(event.key==='Enter'&&(event.ctrlKey||event.metaKey)){event.preventDefault();waSend();}"
          oninput="autoH(this);onReplyInput(this.value)"></textarea>
        <button class="wa-send-btn" id="waSendBtn" onclick="waSend()" disabled>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:2px 16px 6px;">
        <span style="font-size:10px;color:#8696a0;">Ctrl+Entrée pour envoyer</span>
        <span style="font-size:10px;color:#f59e0b;display:none;" id="readOnlyLabel">Mode lecture seule</span>
        <span style="font-size:10px;color:#ef4444;display:none;" id="suspendedLabel">Contactez un administrateur.</span>
      </div>
    </div>
  </div>

  {{-- Right context panel --}}
  <div id="wa-context">
    <div class="ctx-header">
      <p>Contexte</p>
      <span>Métadonnées & Contrôles IA</span>
    </div>

    <div id="ctxEmpty" style="flex:1;display:flex;align-items:center;justify-content:center;color:#8696a0;font-size:13px;padding:24px;text-align:center;">
      Sélectionnez une conversation.
    </div>

    <div id="ctxContent" style="display:none;">
      {{-- Customer section --}}
      <div class="ctx-section">
        <div class="ctx-section-hdr" onclick="toggleCtxSection('customer')">
          <div class="ctx-section-label">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Client
          </div>
          <svg class="ctx-chevron open" id="chevron-customer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="ctx-section-body" id="body-customer">
          <div id="ctxCustomerContent" style="font-size:12px;color:#8696a0;">Chargement…</div>
        </div>
      </div>

      {{-- AI Controls section --}}
      <div class="ctx-section">
        <div class="ctx-section-hdr" onclick="toggleCtxSection('ai-control')">
          <div class="ctx-section-label">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Contrôles IA
          </div>
          <svg class="ctx-chevron" id="chevron-ai-control" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="ctx-section-body ctx-hidden" id="body-ai-control">
          <div id="ctxAiControlContent" style="font-size:12px;color:#8696a0;">Chargement…</div>
        </div>
      </div>

      {{-- AI Summary section --}}
      <div class="ctx-section">
        <div class="ctx-section-hdr" onclick="toggleCtxSection('ai-summary')">
          <div class="ctx-section-label">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            Résumé IA
          </div>
          <div style="display:flex;align-items:center;gap:4px;">
            <button onclick="event.stopPropagation();refreshSummary()" style="background:none;border:none;cursor:pointer;color:#8696a0;padding:2px;" title="Rafraîchir le résumé">
              <svg width="12" height="12" id="summaryRefreshIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>
            </button>
            <svg class="ctx-chevron" id="chevron-ai-summary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </div>
        </div>
        <div class="ctx-section-body ctx-hidden" id="body-ai-summary">
          <div id="ctxSummaryContent" style="font-size:12px;color:#8696a0;">Chargement…</div>
        </div>
      </div>

      {{-- Snapshot --}}
      <div class="ctx-snapshot">
        <div class="ctx-snapshot-title">Snapshot</div>
        <div class="ctx-snap-grid" id="ctxSnapshotGrid">
          <div class="ctx-snap-item"><p>Créé</p><p id="snapCreated">—</p></div>
          <div class="ctx-snap-item"><p>Mis à jour</p><p id="snapUpdated">—</p></div>
          <div class="ctx-snap-item"><p>Messages</p><p id="snapMsgs">—</p></div>
          <div class="ctx-snap-item"><p>Non lus</p><p id="snapUnread">—</p></div>
        </div>
        <div style="margin-top:10px;" id="slaSnapshotSection">
          <p style="font-size:10px;color:#8696a0;margin-bottom:6px;">SLA</p>
          <div id="slaChipContainer"><span style="font-size:11px;color:#8696a0;">Chargement…</span></div>
        </div>
      </div>

      {{-- View ticket CTA --}}
      <button class="ctx-ticket-btn" id="ctxTicketBtn" disabled onclick="openLinkedTicket()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Aucun ticket lié
      </button>
    </div>
  </div>
</div>
</div>

{{-- Ticket modal --}}
<div class="wa-modal-bg" id="tkModal">
  <div class="wa-modal">
    <div class="wa-modal-hdr">
      <div class="wa-modal-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#00a884" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>
        Créer un ticket
      </div>
      <button class="wa-modal-close" onclick="closeTkModal()">✕</button>
    </div>
    <div class="wa-modal-body">
      <div class="wa-ai-pre" id="tkAiPre">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00a884" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        <div><strong style="color:#e9edef;">IA :</strong> <span id="tkAiTxt">Analyse en cours…</span></div>
      </div>
      <div class="wa-f-grp"><label class="wa-f-lbl">Client</label><input type="text" class="wa-f-in" id="tkClient" readonly style="opacity:.7;"></div>
      <div class="wa-f-grp"><label class="wa-f-lbl">Titre</label><input type="text" class="wa-f-in" id="tkTitle" placeholder="Résumé du problème…"></div>
      <div class="wa-f-row">
        <div class="wa-f-grp"><label class="wa-f-lbl">Catégorie</label><select class="wa-f-sel" id="tkCat"><option>Facturation</option><option>Technique & API</option><option>Plateforme SMS</option><option>Connexion</option><option>Général</option></select></div>
        <div class="wa-f-grp"><label class="wa-f-lbl">Priorité</label><select class="wa-f-sel" id="tkPrio"><option>Moyenne</option><option>Basse</option><option>Haute</option><option>Critique</option></select></div>
      </div>
      <div class="wa-f-grp"><label class="wa-f-lbl">Description</label><textarea class="wa-f-ta" id="tkDesc" placeholder="Détails du problème…"></textarea></div>
    </div>
    <div class="wa-modal-foot">
      <button class="wa-btn-cancel" onclick="closeTkModal()">Annuler</button>
      <button class="wa-btn-save" onclick="createTicket()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/></svg>
        Créer le ticket
      </button>
    </div>
  </div>
</div>

<div id="wa-toast">Message envoyé</div>

<script>
// ══ CONFIG ══════════════════════════════════════════════════════
const BASE_URL = window.SUPPORT_API_BASE_URL || '/api/v1';
const CURRENT_USER_ID = '{{ auth()->user()->id ?? "" }}';
const CURRENT_USER_ROLE = '{{ (auth()->user()->role ?? "agent") === "super_admin" ? "admin" : (auth()->user()->role ?? "agent") }}';

// ══ STATE ════════════════════════════════════════════════════════
let selId = null;          // selected conversation id
let activeTab = 'all';
let conversations = [];    // from /api/whatsapp/inbox
let messages = [];         // from /api/whatsapp/thread/:id
let snippets = [];         // from /api/conversations/snippets
let conversationPolicy = null;  // auto-reply policy
let conversationSla = null;     // SLA predictor
let customerProfile = null;     // user profile
let convSummary = null;         // AI summary

// Assisted-draft state
let aiDraftMode = true;
let aiDraftAvailable = false;
let hasPendingDraft = false;
let pendingDraftText = null;
let pendingDraftGeneratedAt = null;
let lastDraftSourceMessageId = null;

// Suspension state
let isReplySuspended = false;
let suspensionReason = null;
let canReplyWhatsApp = true; // will be refined if API exposes role info

// Polling intervals
let inboxInterval, threadInterval, statusInterval, slaInterval, policyInterval, suspensionInterval;

// Open accordion sections
let openSections = { customer: true, 'ai-control': false, 'ai-summary': false };

// QR
let qrTimer, qrSecs = 60;

// ══ CSRF helper ══════════════════════════════════════════════════
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiFetch(url, opts = {}) {
  const base = String(BASE_URL).replace(/\/$/, '');
  const path = String(url).startsWith(base) ? String(url).slice(base.length) : url;
  return window.supportApiFetch(path, opts);
}

// ══ QR BRIDGE CONFIG ═════════════════════════════════════════════
// Mirrors: getClientConfigRaw('VITE_QR_BRIDGE_URL') from the TSX
const QR_BRIDGE_URL = '{{ config("services.whatsapp.qr_bridge_url", env("VITE_QR_BRIDGE_URL", "http://localhost:3000/qr")) }}';
const QR_EXPIRES_AFTER_SECONDS = 60;
const QR_WAIT_RETRY_SECONDS = 3;

let qrVersion = 0;         // bumped on every refresh — cache-busts the img src
let qrImageUnavailable = false;
let qrAutoRetryTO = null;
let qrAutoExpireTO = null;

// Mirrors buildQrPngUrl() from the TSX exactly
function buildQrPngUrl(baseUrl, version) {
  try {
    const parsed = new URL(baseUrl);
    if (parsed.pathname.endsWith('/qr/')) {
      parsed.pathname = parsed.pathname.replace(/\/qr\/$/, '/qr.png');
    } else if (parsed.pathname.endsWith('/qr.json')) {
      parsed.pathname = parsed.pathname.replace(/\/qr\.json$/, '/qr.png');
    } else if (parsed.pathname.endsWith('/qr')) {
      parsed.pathname = parsed.pathname + '.png';
    } else if (!parsed.pathname.endsWith('/qr.png')) {
      parsed.pathname = parsed.pathname.replace(/\/$/, '') + '/qr.png';
    }
    parsed.searchParams.set('_v', String(version));
    return parsed.toString();
  } catch (e) {
    const base = baseUrl.split('?')[0].replace(/\/$/, '');
    const endpoint = base.endsWith('/qr') ? base + '.png'
      : base.endsWith('/qr.png') ? base
      : base + '/qr.png';
    return endpoint + '?_v=' + version;
  }
}

function applyQrSrc() {
  const img = document.getElementById('qrImg');
  if (img) img.src = buildQrPngUrl(QR_BRIDGE_URL, qrVersion);
}

function onQrImageLoad() {
  qrImageUnavailable = false;
  hideQrOverlay();
}

function onQrImageError() {
  qrImageUnavailable = true;
  // Show overlay with "waiting" message, auto-retry after QR_WAIT_RETRY_SECONDS
  showQrOverlay(
    'En attente du QR code…',
    'Nouvelle tentative automatique dans ' + QR_WAIT_RETRY_SECONDS + 's'
  );
  clearTimeout(qrAutoRetryTO);
  qrAutoRetryTO = setTimeout(function () {
    refreshQrImage();
  }, QR_WAIT_RETRY_SECONDS * 1000);
}

function hideQrOverlay() {
  const o = document.getElementById('qrRefreshOverlay');
  if (o) o.classList.remove('show');
}

function showQrOverlay(msg, sub) {
  const o = document.getElementById('qrRefreshOverlay');
  const m = document.getElementById('qrOverlayMsg');
  const s = document.getElementById('qrOverlaySub');
  if (m) m.textContent = msg || 'Le code QR a expiré';
  if (s) s.textContent = sub || '';
  if (o) o.classList.add('show');
}

// Bump version → new src → triggers onload / onerror
function refreshQrImage() {
  qrImageUnavailable = false;
  qrVersion++;
  applyQrSrc();
}

function regenQr() {
  clearTimeout(qrAutoRetryTO);
  clearTimeout(qrAutoExpireTO);
  hideQrOverlay();
  refreshQrImage();
  startQrTimer();
}

function startQrTimer() {
  qrSecs = QR_EXPIRES_AFTER_SECONDS;
  clearInterval(qrTimer);
  qrTimer = setInterval(function () {
    qrSecs--;
    const el = document.getElementById('qrCountdown');
    if (el) el.textContent = qrSecs;
    if (qrSecs <= 0) {
      clearInterval(qrTimer);
      // Mirror TSX: auto-refresh after 250 ms when timer hits 0
      qrAutoExpireTO = setTimeout(function () {
        showQrOverlay('Le code QR a expiré. Rafraîchissement…', '');
        refreshQrImage();
        startQrTimer();
      }, 250);
    }
  }, 1000);
}

function showQrScreen() {
  document.getElementById('backBtn').style.display = 'none';
  document.getElementById('scr-qr').style.display = 'block';
  document.getElementById('scr-phone').style.display = 'none';
  document.getElementById('scr-code').style.display = 'none';
  // Reset state, load the live QR image from the bridge
  qrImageUnavailable = false;
  hideQrOverlay();
  applyQrSrc();   // ← this is what was missing: actually sets img.src
  startQrTimer();
}

function showPhoneScreen() {
  clearInterval(qrTimer);
  document.getElementById('backBtn').style.display = 'flex';
  document.getElementById('scr-qr').style.display = 'none';
  document.getElementById('scr-phone').style.display = 'block';
  document.getElementById('scr-code').style.display = 'none';
  setTimeout(() => document.getElementById('phoneInput').focus(), 100);
}

function goToCodeScreen() {
  document.getElementById('scr-phone').style.display = 'none';
  document.getElementById('scr-code').style.display = 'block';
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  ['c1','c2','c3','c4','c5','c6','c7','c8'].forEach(id => {
    const el = document.getElementById(id); if (el) el.textContent = chars[Math.floor(Math.random()*chars.length)];
  });
}

function goBack() { document.getElementById('scr-code').style.display='none'; document.getElementById('scr-phone').style.display='none'; showQrScreen(); }

function connectWa() {
  clearInterval(qrTimer);
  const screens = document.getElementById('wa-screens');
  screens.style.transition = 'opacity .4s ease'; screens.style.opacity = '0';
  setTimeout(function() {
    screens.style.display = 'none';
    document.getElementById('wa-root').style.display = 'flex';
    initApp();
  }, 400);
}

// ══ INIT ═════════════════════════════════════════════════════════
function initApp() {
  fetchStatus();
  fetchInbox();
  fetchSnippets();

  statusInterval   = setInterval(fetchStatus, 20000);
  inboxInterval    = setInterval(fetchInbox, 10000);
}

// ══ API: STATUS ═══════════════════════════════════════════════════
// TSX: whatsappApi.status() → GET /api/whatsapp/status
async function fetchStatus() {
  try {
    const data = await apiFetch(`${BASE_URL}/whatsapp/status`);
    renderStatus(data.connected);
  } catch(e) {
    renderStatus(false);
  }
}

function renderStatus(connected) {
  const icon = document.getElementById('statusIcon');
  const txt  = document.getElementById('waStatusTxt');
  if (connected === true) {
    icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a884" stroke-width="2" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>`;
    txt.innerHTML = `<span style="color:#00a884;">● Connecté</span>`;
  } else if (connected === false) {
    icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>`;
    txt.innerHTML = `<span style="color:#ef4444;">● Déconnecté</span>`;
  } else {
    icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8696a0" stroke-width="2" stroke-linecap="round" class="spin"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>`;
    txt.innerHTML = `<span style="color:#8696a0;">Vérification…</span>`;
  }
}

// ══ API: INBOX ════════════════════════════════════════════════════
// TSX: whatsappApi.inbox({ unreadOnly, limit: 200 }) → GET /api/whatsapp/inbox?unread_only=...&limit=200
async function fetchInbox() {
  try {
    const unreadOnly = activeTab === 'unread';
    const data = await apiFetch(`${BASE_URL}/whatsapp/inbox?unread_only=${unreadOnly}&limit=200`);
    conversations = data.conversations || [];
    renderList(document.getElementById('waSearch').value);
    if (!selId && conversations.length > 0) selectConv(conversations[0].id);
  } catch(e) {
    document.getElementById('waConvList').innerHTML =
      `<div style="padding:16px;color:#ef4444;font-size:12px;">Erreur de chargement: ${escH(e.message)}</div>`;
  }
}

// ══ API: SNIPPETS ═════════════════════════════════════════════════
// TSX: conversationsApi.listSnippets({ channel: 'WHATSAPP' }) → GET /api/conversations/snippets?channel=WHATSAPP
async function fetchSnippets() {
  try {
    const data = await apiFetch(`${BASE_URL}/conversations/automation/snippets?channel=WHATSAPP`);
    snippets = data.snippets || [];
    // Merge fallbacks
    const FALLBACKS = [
      { id:'fallback-handoff', title:'Handoff', shortcut:'handoff', body:'Merci de votre patience. Je transfère votre dossier à un spécialiste.' },
      { id:'fallback-investigating', title:'Investigation en cours', shortcut:'investigating', body:'Merci de votre patience. Nous analysons votre demande et vous tiendrons informé.' },
      { id:'fallback-need-details', title:'Détails manquants', shortcut:'need-details', body:'Pour avancer, pourriez-vous nous fournir plus de détails, le message d\'erreur exact et une capture d\'écran si possible ?' },
    ];
    const seen = new Set(snippets.map(s => `${(s.shortcut||'').toLowerCase()}::${s.title.toLowerCase()}`));
    for (const fb of FALLBACKS) {
      const key = `${(fb.shortcut||'').toLowerCase()}::${fb.title.toLowerCase()}`;
      if (!seen.has(key)) snippets.push(fb);
    }
    renderSnippetSelect();
  } catch(e) {
    console.warn('Snippets unavailable:', e.message);
  }
}

function renderSnippetSelect() {
  const sel = document.getElementById('snippetSelect');
  sel.innerHTML = '<option value="">Sélectionner un snippet…</option>' +
    snippets.map(s => `<option value="${s.id}">${s.shortcut ? s.shortcut+' · ' : ''}${escH(s.title)}</option>`).join('');
  sel.onchange = () => { document.getElementById('insertSnippetBtn').disabled = !sel.value; };
}

// ══ API: THREAD ═══════════════════════════════════════════════════
// TSX: whatsappApi.thread(id, { limit: 500 }) → GET /api/whatsapp/thread/:id?limit=500
async function fetchThread(id) {
  try {
    const data = await apiFetch(`${BASE_URL}/whatsapp/inbox/${id}?limit=500`);
    const raw = data.messages || [];
    messages = [...raw].sort((a, b) => {
      const aTs = Date.parse(a.created_at||''); const bTs = Date.parse(b.created_at||'');
      const aHas = !isNaN(aTs); const bHas = !isNaN(bTs);
      if (aHas && bHas && aTs !== bTs) return aTs - bTs;
      if (aHas !== bHas) return aHas ? -1 : 1;
      return a.id.localeCompare(b.id);
    });
    renderMsgs();
    updateSnapshots();
    maybeAutoGenerateDraft();
  } catch(e) {
    document.getElementById('waMsgs').innerHTML =
      `<div style="padding:24px;text-align:center;color:#ef4444;font-size:13px;">Erreur: ${escH(e.message)}</div>`;
  }
}

// TSX: conversationsApi.slaPredictor(id) → GET /api/conversations/:id/sla-predictor
async function fetchSla(id) {
  try {
    conversationSla = await apiFetch(`${BASE_URL}/conversations/${id}/sla-predictor`);
    renderSlaChips();
  } catch(e) { conversationSla = null; renderSlaChips(); }
}

// TSX: conversationsApi.conversationAutoReply(id) → GET /api/conversations/:id/auto-reply
async function fetchPolicy(id) {
  try {
    conversationPolicy = await apiFetch(`${BASE_URL}/conversations/${id}/ai-auto-reply`);
    aiDraftAvailable = Boolean(conversationPolicy?.assisted_draft_available);
    renderAiControlSection();
    updateAiBar();
  } catch(e) { conversationPolicy = null; renderAiControlSection(); }
}

// TSX: whatsappApi.summary(id, { maxMessages: 200 }) → GET /api/whatsapp/:id/summary?max_messages=200
async function fetchSummary(id) {
  try {
    document.getElementById('ctxSummaryContent').innerHTML = `<span style="color:#8696a0;">Génération du résumé…</span>`;
    convSummary = await apiFetch(`${BASE_URL}/whatsapp/inbox/${id}/summary?max_messages=200`, { method: 'POST' });
    renderSummarySection();
  } catch(e) {
    convSummary = null;
    document.getElementById('ctxSummaryContent').innerHTML = `<span style="color:#ef4444;font-size:12px;">${escH(e.message)}</span>`;
  }
}

// TSX: usersApi.getById(customerId) → GET /api/users/:id
async function fetchCustomerProfile(userId) {
  if (!userId) { renderCustomerSection(); return; }
  try {
    customerProfile = await apiFetch(`${BASE_URL}/users/${userId}`);
    renderCustomerSection();
  } catch(e) { customerProfile = null; renderCustomerSection(); }
}

// TSX: conversationsApi.agentReplySuspension(convId, userId) → GET /api/conversations/:convId/agent-reply-suspension/:userId
async function fetchSuspension(convId) {
  if (CURRENT_USER_ROLE !== 'agent' || !CURRENT_USER_ID) { isReplySuspended = false; updateSuspensionUI(); return; }
  try {
    const data = await apiFetch(`${BASE_URL}/conversations/${convId}/agent-reply-suspensions/${CURRENT_USER_ID}`);
    isReplySuspended = data.suspended || false;
    suspensionReason = data.reason?.trim() || null;
  } catch(e) { isReplySuspended = false; }
  updateSuspensionUI();
}

// ══ SELECT CONVERSATION ═══════════════════════════════════════════
function selectConv(id) {
  const conv = conversations.find(c => c.id === id); if (!conv) return;
  selId = id;
  customerProfile = null; conversationPolicy = null; conversationSla = null; convSummary = null;
  messages = []; isReplySuspended = false;
  hasPendingDraft = false; pendingDraftText = null; pendingDraftGeneratedAt = null; lastDraftSourceMessageId = null;

  // Update UI
  document.getElementById('wa-intro').style.display = 'none';
  document.getElementById('wa-active').style.display = 'flex';
  document.getElementById('ctxEmpty').style.display = 'none';
  document.getElementById('ctxContent').style.display = 'block';

  const label = convLabel(conv);
  const color = avatarColor(conv.id);
  const av = document.getElementById('chatHdrAv');
  av.style.background = color; av.textContent = getInitials(label);
  document.getElementById('chatHdrName').textContent = label;
  document.getElementById('chatHdrSub').textContent = conv.contact_phone || 'WhatsApp';
  document.getElementById('slaHeaderChip').innerHTML = '';

  const unread = conv.unread_count || 0;
  const markBtn = document.getElementById('markReadBtn');
  markBtn.style.display = unread > 0 ? 'flex' : 'none';

  // Reset textarea
  const inp = document.getElementById('waInput');
  inp.value = ''; inp.className = 'wa-input-box'; autoH(inp);
  document.getElementById('waSendBtn').disabled = true;
  document.getElementById('aiDraftBadge').style.display = 'none';

  // Reset context sections
  document.getElementById('ctxCustomerContent').innerHTML = '<span style="color:#8696a0;">Chargement…</span>';
  document.getElementById('ctxAiControlContent').innerHTML = '<span style="color:#8696a0;">Chargement…</span>';
  document.getElementById('ctxSummaryContent').innerHTML = '<span style="color:#8696a0;">Chargement…</span>';
  renderSnapshotBasic(conv);
  document.getElementById('slaChipContainer').innerHTML = '<span style="font-size:11px;color:#8696a0;">Chargement…</span>';
  document.getElementById('ctxTicketBtn').disabled = true;
  document.getElementById('ctxTicketBtn').textContent = 'Aucun ticket lié';

  // Render list
  renderList(document.getElementById('waSearch').value);

  // Clear old intervals and restart
  clearInterval(threadInterval); clearInterval(slaInterval); clearInterval(policyInterval); clearInterval(suspensionInterval);

  // Fetch everything for this conversation
  fetchThread(id);
  fetchCustomerProfile(conv.user_id);
  fetchPolicy(id);
  fetchSla(id);
  fetchSuspension(id);
  fetchSummary(id);

  threadInterval     = setInterval(() => fetchThread(id), 6000);
  slaInterval        = setInterval(() => fetchSla(id), 15000);
  policyInterval     = setInterval(() => fetchPolicy(id), 15000);
  suspensionInterval = setInterval(() => fetchSuspension(id), 15000);
}

// ══ API: SEND REPLY ═══════════════════════════════════════════════
// TSX: whatsappApi.reply(convId, content, { usedAssistedDraft, assistedDraftEdited, assistedDraftGeneratedAt })
// → POST /api/whatsapp/:id/reply
async function waSend() {
  const inp = document.getElementById('waInput');
  const content = inp.value.trim();
  if (!content || !selId) return;
  if (!canSendReply()) return;

  const btn = document.getElementById('waSendBtn');
  btn.disabled = true;

  const usedAssistedDraft = hasPendingDraft;
  const normalizeText = t => t.replace(/\s+/g,' ').trim();
  const assistedDraftEdited = usedAssistedDraft && pendingDraftText !== null
    ? normalizeText(content) !== normalizeText(pendingDraftText)
    : undefined;

  try {
    await apiFetch(`${BASE_URL}/whatsapp/reply/${selId}`, {
      method: 'POST',
      body: JSON.stringify({
        message: content,
        used_assisted_draft: usedAssistedDraft,
        assisted_draft_edited: assistedDraftEdited,
        assisted_draft_generated_at: usedAssistedDraft ? pendingDraftGeneratedAt : undefined,
      }),
    });
    inp.value = ''; inp.className = 'wa-input-box'; autoH(inp);
    hasPendingDraft = false; pendingDraftText = null; pendingDraftGeneratedAt = null;
    document.getElementById('aiDraftBadge').style.display = 'none';
    await fetchThread(selId);
    await fetchInbox();
  } catch(e) {
    showToast('Erreur d\'envoi: ' + e.message);
  }
  btn.disabled = !inp.value.trim() || !canSendReply();
}

// ══ API: MARK READ ════════════════════════════════════════════════
// TSX: whatsappApi.markRead(convId) → POST /api/whatsapp/:id/mark-read
async function doMarkRead() {
  if (!selId) return;
  const btn = document.getElementById('markReadBtn');
  btn.style.opacity = '.5';
  try {
    const res = await apiFetch(`${BASE_URL}/whatsapp/inbox/${selId}/read`, { method: 'POST', body: JSON.stringify({ message_ids: null }) });
    showToast(`${res.messages_marked_read || 0} message(s) marqué(s) comme lu(s)`);
    btn.style.display = 'none';
    await fetchInbox();
    await fetchThread(selId);
  } catch(e) {
    showToast('Erreur: ' + e.message);
  }
  btn.style.opacity = '1';
}

// ══ API: ASSISTED DRAFT ════════════════════════════════════════════
// TSX: conversationsApi.assistedDraft(convId) → POST /api/conversations/:id/assisted-draft
async function doGenerateDraft() {
  if (!selId || !aiDraftAvailable) return;
  const btn = document.getElementById('generateDraftBtn');
  btn.disabled = true;
  btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/></svg> Génération…`;
  try {
    const queued = await apiFetch(`${BASE_URL}/conversations/${selId}/assisted-draft/jobs`, { method: 'POST' });
    const res = await waitForAssistedDraft(selId, queued.job_id);
    const inp = document.getElementById('waInput');
    inp.value = res.draft;
    inp.className = 'wa-input-box ai-draft';
    autoH(inp);
    hasPendingDraft = true;
    pendingDraftText = res.draft;
    pendingDraftGeneratedAt = res.generated_at;
    lastDraftSourceMessageId = res.source_message_id;
    document.getElementById('aiDraftBadge').style.display = 'inline-flex';
    document.getElementById('waSendBtn').disabled = false;
  } catch(e) {
    showToast('Brouillon IA indisponible: ' + e.message);
  }
  btn.disabled = false;
  btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> Générer`;
}

function onAiDraftToggleChange() {
  aiDraftMode = document.getElementById('aiDraftToggle').checked;
  updateAiBar();
}

function updateAiBar() {
  const label = document.getElementById('aiDraftAvailLabel');
  const btn = document.getElementById('generateDraftBtn');
  label.textContent = aiDraftAvailable ? 'Suggestions auto' : 'Conversations chat uniquement';
  const latestCustomerMsg = getLatestCustomerMessage();
  btn.disabled = !aiDraftAvailable || !aiDraftMode || !latestCustomerMsg || !selId;
}

function maybeAutoGenerateDraft() {
  if (!aiDraftMode || !aiDraftAvailable) return;
  const latestMsg = getLatestCustomerMessage();
  if (!latestMsg) return;
  if (document.getElementById('waInput').value.trim()) return;
  if (lastDraftSourceMessageId === latestMsg.id) return;
  doGenerateDraft();
}

async function waitForAssistedDraft(conversationId, jobId) {
  const startedAt = Date.now();
  while (true) {
    const status = await apiFetch(`${BASE_URL}/conversations/${conversationId}/assisted-draft/jobs/${jobId}`);
    if (status.status === 'succeeded') {
      if (status.assisted_draft) return status.assisted_draft;
      throw new Error('Brouillon IA terminÃ© sans contenu');
    }
    if (status.status === 'failed') {
      throw new Error(status.error || 'GÃ©nÃ©ration du brouillon IA Ã©chouÃ©e');
    }
    if (Date.now() - startedAt > 90000) {
      throw new Error('DÃ©lai de gÃ©nÃ©ration du brouillon dÃ©passÃ©');
    }
    await new Promise(resolve => setTimeout(resolve, 1000));
  }
}

function getLatestCustomerMessage() {
  const conv = conversations.find(c => c.id === selId);
  const userId = conv?.user_id;
  return [...messages].reverse().find(m => {
    if (m.direction) return m.direction === 'inbound';
    return userId && m.sender_id === userId;
  }) || null;
}

function onReplyInput(val) {
  document.getElementById('waSendBtn').disabled = !val.trim() || !canSendReply();
  if (!val.trim()) {
    hasPendingDraft = false; pendingDraftText = null; pendingDraftGeneratedAt = null;
    document.getElementById('aiDraftBadge').style.display = 'none';
    document.getElementById('waInput').className = 'wa-input-box';
  }
}

// ══ API: SNIPPETS INSERT ══════════════════════════════════════════
function doInsertSnippet() {
  const sel = document.getElementById('snippetSelect');
  const snippet = snippets.find(s => s.id === sel.value); if (!snippet) return;
  const conv = conversations.find(c => c.id === selId);
  const latestMsg = getLatestCustomerMessage();
  const customerId = conv?.user_id || '';
  const customerLabel = (() => {
    if (!customerId) return 'Client';
    if (customerId.includes('@')) return customerId.split('@')[0] || 'Client';
    return 'Client ' + customerId.slice(0, 8);
  })();
  const inp = document.getElementById('waInput');
  const rendered = snippet.body
    .replace(/\{\{\s*customer_name\s*\}\}/gi, customerLabel)
    .replace(/\{\{\s*customer_id\s*\}\}/gi, customerId)
    .replace(/\{\{\s*conversation_id\s*\}\}/gi, selId || '')
    .replace(/\{\{\s*latest_customer_message\s*\}\}/gi, latestMsg?.content || '')
    .replace(/\{\{\s*(assisted_draft|current_draft)\s*\}\}/gi, inp.value)
    .replace(/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/g, '')
    .trim();
  if (!rendered) return;
  const current = inp.value.trim();
  inp.value = current ? rendered + '\n\n' + current : rendered;
  autoH(inp);
  document.getElementById('waSendBtn').disabled = !canSendReply();
  showToast('Snippet « ' + snippet.title + ' » inséré');
}

// ══ API: AUTO-REPLY POLICY ════════════════════════════════════════
// TSX: conversationsApi.setConversationAutoReply(id, { ai_auto_reply_enabled }) → PUT /api/conversations/:id/auto-reply
async function toggleAutoReply(enabled) {
  if (!selId) return;
  try {
    conversationPolicy = await apiFetch(`${BASE_URL}/conversations/${selId}/ai-auto-reply`, {
      method: 'PUT',
      body: JSON.stringify({ ai_auto_reply_enabled: enabled }),
    });
    renderAiControlSection();
    showToast(enabled ? 'Auto-réponse IA activée' : 'Auto-réponse IA désactivée');
  } catch(e) { showToast('Erreur: ' + e.message); }
}

// TSX: conversationsApi.setConversationAutoReplyPause(id, { minutes }) → POST /api/conversations/:id/auto-reply/pause
async function pauseAutoReply(minutes) {
  if (!selId) return;
  try {
    conversationPolicy = await apiFetch(`${BASE_URL}/conversations/${selId}/ai-auto-reply/pause`, {
      method: 'POST',
      body: JSON.stringify({ minutes }),
    });
    renderAiControlSection();
    showToast('Auto-réponse mise en pause');
  } catch(e) { showToast('Erreur: ' + e.message); }
}

// TSX: conversationsApi.clearConversationAutoReplyPause(id) → DELETE (or POST) /api/conversations/:id/auto-reply/pause
async function clearPause() {
  if (!selId) return;
  try {
    conversationPolicy = await apiFetch(`${BASE_URL}/conversations/${selId}/ai-auto-reply/pause`, { method: 'DELETE' });
    renderAiControlSection();
    showToast('Pause levée — auto-réponse reprend');
  } catch(e) { showToast('Erreur: ' + e.message); }
}

// ══ RENDER: CONTEXT SECTIONS ══════════════════════════════════════
function renderCustomerSection() {
  const conv = conversations.find(c => c.id === selId);
  const label = conv ? convLabel(conv) : '';
  const el = document.getElementById('ctxCustomerContent');
  const name = customerProfile?.full_name?.trim() || label || '—';
  const email = customerProfile?.email?.trim() || '—';
  const phone = customerProfile?.phone_number?.trim() || conv?.contact_phone || '—';
  const uid = conv?.user_id || '—';
  el.innerHTML = `
    <div class="ctx-field"><span class="ctx-field-lbl">Nom</span><span class="ctx-field-val">${escH(name)}</span></div>
    <div class="ctx-field"><span class="ctx-field-lbl">Email</span><span class="ctx-field-val">${escH(email)}</span></div>
    <div class="ctx-field"><span class="ctx-field-lbl">Téléphone</span><span class="ctx-field-val">${escH(phone)}</span></div>
    <div class="ctx-field"><span class="ctx-field-lbl">ID</span><span class="ctx-field-val mono">${escH(uid)}</span></div>`;
}

function renderAiControlSection() {
  const el = document.getElementById('ctxAiControlContent');
  if (!conversationPolicy) { el.innerHTML = '<span style="color:#8696a0;">Indisponible.</span>'; return; }
  const p = conversationPolicy;
  const isActive = p.effective_ai_auto_reply_enabled;
  const blockLabel = formatBlockReason(p.block_reason);
  const canToggle = CURRENT_USER_ROLE === 'admin';
  const canPause  = CURRENT_USER_ROLE === 'admin' || CURRENT_USER_ROLE === 'agent';
  const pausedUntil = p.ai_auto_reply_paused_until ? formatPauseUntil(p.ai_auto_reply_paused_until) : null;

  el.innerHTML = `
    <div class="ctx-status-pill ${isActive ? 'active' : 'inactive'}">
      <span class="ctx-dot" style="background:${isActive ? '#00a884' : '#f59e0b'};"></span>
      ${isActive ? 'Auto-réponse active' : escH(blockLabel)}
    </div>
    <div class="ctx-toggle-row">
      <div>
        <div class="ctx-toggle-label">Activer l'auto-réponse</div>
        <div class="ctx-toggle-sub">Paramètre persistant</div>
      </div>
      <label class="ctx-switch">
        <input type="checkbox" ${p.ai_auto_reply_enabled ? 'checked' : ''} ${!canToggle ? 'disabled' : ''} onchange="toggleAutoReply(this.checked)">
        <span class="ctx-switch-slider"></span>
      </label>
    </div>
    ${!canToggle ? '<div class="ctx-note">Administrateurs uniquement.</div>' : ''}
    <div style="margin-top:8px;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8696a0;margin-bottom:6px;">Minuterie de pause</div>
      <div class="ctx-pause-grid">
        <button class="ctx-pause-btn" ${!canPause ? 'disabled' : ''} onclick="pauseAutoReply(30)">Pause 30m</button>
        <button class="ctx-pause-btn" ${!canPause ? 'disabled' : ''} onclick="pauseAutoReply(120)">Pause 2h</button>
        <button class="ctx-pause-btn" ${!canPause ? 'disabled' : ''} onclick="pauseAutoReply(1440)">Pause 24h</button>
        <button class="ctx-pause-btn" ${!canPause || !p.ai_auto_reply_paused_until ? 'disabled' : ''} onclick="clearPause()">Reprendre</button>
      </div>
      ${pausedUntil ? `<div class="ctx-note">En pause jusqu'au ${escH(pausedUntil)}</div>` : ''}
    </div>`;
}

function renderSummarySection() {
  const el = document.getElementById('ctxSummaryContent');
  if (!convSummary) { el.innerHTML = '<span style="color:#8696a0;">Non disponible.</span>'; return; }
  const s = convSummary;
  const resClass = s.resolution_state || 'unresolved';
  const resLabel = resClass.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase());
  el.innerHTML = `
    <div class="ctx-summary-section">
      <p>Problème</p>
      <div class="body">${escH(s.problem_summary || '—')}</div>
      <p>Résolution</p>
      <span class="ctx-res-badge ${resClass}">${escH(resLabel)}</span>
      <div class="body" style="margin-top:4px;">${escH(s.resolution_description || '—')}</div>
      <p>Prochaine action</p>
      <div class="body">${escH(s.next_action || '—')}</div>
    </div>`;
}

function renderSnapshotBasic(conv) {
  document.getElementById('snapCreated').textContent = formatDateTime(conv?.created_at);
  document.getElementById('snapUpdated').textContent = formatDateTime(conv?.updated_at);
  document.getElementById('snapUnread').textContent = String(conv?.unread_count || 0);
}

function updateSnapshots() {
  document.getElementById('snapMsgs').textContent = String(messages.length);
  const conv = conversations.find(c => c.id === selId);
  if (conv) renderSnapshotBasic(conv);
}

function renderSlaChips() {
  const sla = conversationSla;
  const headerChip = document.getElementById('slaHeaderChip');
  const container = document.getElementById('slaChipContainer');
  const ticketBtn = document.getElementById('ctxTicketBtn');

  if (!sla) {
    headerChip.innerHTML = '';
    container.innerHTML = '<span style="font-size:11px;color:#8696a0;">SLA indisponible</span>';
  } else {
    const sr = sla.seconds_remaining;
    const pending = sr !== null && sr !== undefined;
    const overdue = pending && sr <= 0;
    const chipClass = pending ? (overdue ? 'overdue' : 'pending') : 'none';
    const label = pending ? (overdue ? `En retard ${formatSlaCountdown(Math.abs(sr))} il y a` : `${formatSlaCountdown(sr)} restant`) : 'Pas de réponse en attente';
    const chip = `<span class="sla-chip ${chipClass}">⏱ ${escH(label)}</span>`;

    headerChip.innerHTML = chip;

    const rl = sla.risk_level || 'low';
    const riskColor = rl==='critical'?'#ef4444':rl==='high'?'#f59e0b':rl==='medium'?'#f97316':'#00a884';
    container.innerHTML = `${chip} <span class="sla-chip" style="border-color:${riskColor}20;background:${riskColor}10;color:${riskColor};margin-left:4px;">⚠ ${escH(rl.toUpperCase())}</span>`;

    // Ticket CTA
    const linkedTicketId = sla.escalation_ticket_id?.trim() || null;
    if (linkedTicketId) {
      ticketBtn.disabled = false;
      ticketBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg> Voir le ticket lié`;
      ticketBtn.dataset.ticketId = linkedTicketId;
    } else {
      ticketBtn.disabled = true;
      ticketBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg> Aucun ticket lié`;
      delete ticketBtn.dataset.ticketId;
    }
  }
}

function openLinkedTicket() {
  const id = document.getElementById('ctxTicketBtn').dataset.ticketId;
  if (id) window.location.href = `/tickets/${id}`;
}

// ══ RENDER: MESSAGES ══════════════════════════════════════════════
function renderMsgs() {
  const el = document.getElementById('waMsgs');
  if (!messages.length) { el.innerHTML = '<div style="padding:24px;text-align:center;color:#8696a0;font-size:13px;">Aucun message.</div>'; return; }
  const conv = conversations.find(c => c.id === selId);
  const userId = conv?.user_id || '';
  let html = '', lastDate = null;
  messages.forEach(m => {
    const isInbound = m.direction ? m.direction === 'inbound' : m.sender_id === userId;
    const dateStr = m.created_at ? new Date(m.created_at).toLocaleDateString('fr-FR', { weekday:'short', month:'short', day:'numeric' }) : '';
    if (dateStr && dateStr !== lastDate) { lastDate = dateStr; html += `<div class="wa-date-sep"><span>${escH(dateStr)}</span></div>`; }
    const t = formatDateTime(m.created_at);
    const dir = isInbound ? 'in' : 'out';
    const tick = !isInbound ? (m.is_read ? '<span class="wa-tick">✓✓</span>' : '<span style="color:#8696a0;">✓</span>') : '';
    html += `<div class="wa-msg-row ${dir}"><div class="wa-bubble ${dir}">${escH(m.content||'').replace(/\n/g,'<br>')}${m.ia_generated?'<div style="font-size:10px;color:#00a884;margin-top:3px;">🤖 IA Auto</div>':''}<div class="wa-meta">${t} ${tick}</div></div></div>`;
  });
  el.innerHTML = html;
  el.scrollTop = el.scrollHeight;
}

// ══ RENDER: CONVERSATION LIST ══════════════════════════════════════
function renderList(q = '') {
  const el = document.getElementById('waConvList');
  const searchText = (q || '').trim().toLowerCase();
  const filtered = conversations.filter(c => {
    const tabOk = activeTab === 'all' || activeTab === 'unread' ? (c.unread_count > 0) : true;
    if (activeTab === 'unread' && c.unread_count === 0) return false;
    const hay = [convLabel(c), c.contact_phone||'', c.subject||'', c.last_message||''].join(' ').toLowerCase();
    return !searchText || hay.includes(searchText);
  });

  if (!filtered.length) { el.innerHTML = '<div style="padding:24px;text-align:center;color:#8696a0;font-size:13px;">Aucune conversation.</div>'; return; }

  const tickSvg = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#53bdeb" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/><polyline points="24 6 13 17 8 12"/></svg>`;

  el.innerHTML = filtered.map(c => {
    const label = convLabel(c);
    const color = avatarColor(c.id);
    const initials = getInitials(label);
    const unread = c.unread_count || 0;
    const time = formatDateTime(c.last_message_at || c.updated_at);
    const preview = c.last_message || '';
    const isActive = c.id === selId;
    return `<div class="wa-conv-item${isActive ? ' active' : ''}" onclick="selectConv('${c.id}')">
      <div class="wa-av" style="background:${color};position:relative;">
        ${escH(initials)}
        ${unread > 0 ? `<span style="position:absolute;top:-2px;right:-2px;width:18px;height:18px;border-radius:50%;background:#00a884;color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;">${unread > 9 ? '9+' : unread}</span>` : ''}
      </div>
      <div class="wa-ci-info">
        <div class="wa-ci-top">
          <span class="wa-ci-name" style="${unread > 0 ? 'font-weight:600;' : ''}">${escH(label)}</span>
          <span class="wa-ci-time${unread > 0 ? ' unread' : ''}">${escH(time)}</span>
        </div>
        <div class="wa-ci-bot">
          <span class="wa-ci-prev">${c.last_message && c.last_message_direction === 'outbound' ? tickSvg : ''}${escH(preview.slice(0, 50))}${preview.length > 50 ? '…' : ''}</span>
          ${unread > 0 ? `<span class="wa-badge">${unread > 9 ? '9+' : unread}</span>` : ''}
        </div>
      </div>
    </div>`;
  }).join('');

  // Update unread badge
  const totalUnread = conversations.reduce((a, c) => a + (c.unread_count || 0), 0);
  const badge = document.getElementById('unreadBadge');
  badge.textContent = totalUnread;
  badge.style.display = totalUnread > 0 ? 'inline-flex' : 'none';
}

// ══ SUSPENSION UI ═════════════════════════════════════════════════
function updateSuspensionUI() {
  const banner = document.getElementById('suspensionBanner');
  const reason = document.getElementById('suspensionReason');
  const suspended = document.getElementById('suspendedLabel');
  const readOnly = document.getElementById('readOnlyLabel');
  if (isReplySuspended) {
    banner.style.display = 'flex';
    reason.textContent = suspensionReason ? `Raison : ${suspensionReason}` : 'Un administrateur a suspendu vos réponses.';
    suspended.style.display = 'block'; readOnly.style.display = 'none';
  } else if (!canReplyWhatsApp) {
    banner.style.display = 'none';
    readOnly.style.display = 'block'; suspended.style.display = 'none';
  } else {
    banner.style.display = 'none'; suspended.style.display = 'none'; readOnly.style.display = 'none';
  }
  document.getElementById('waInput').disabled = !canSendReply();
  document.getElementById('waInput').placeholder = isReplySuspended
    ? 'Réponses suspendues pour cette conversation'
    : !canReplyWhatsApp ? 'Lecture seule — votre compte ne peut pas envoyer de réponses'
    : 'Entrez un message (Ctrl+Entrée pour envoyer)';
}

function canSendReply() { return canReplyWhatsApp && !isReplySuspended && Boolean(selId); }

// ══ TICKET MODAL ══════════════════════════════════════════════════
function showTicketModal() {
  const conv = conversations.find(c => c.id === selId); if (!conv) return;
  const label = convLabel(conv);
  document.getElementById('tkClient').value = label + (conv.contact_phone ? ` (${conv.contact_phone})` : '');
  const msgText = messages.filter(m => {
    const userId = conv.user_id;
    return m.direction ? m.direction === 'inbound' : m.sender_id === userId;
  }).map(m => m.content || '').join(' ');

  let cat = 'Général', prio = 'Moyenne', title = `Demande WhatsApp — ${label}`;
  if (/facture|fac|crédit|recharge|paiement/i.test(msgText)) { cat = 'Facturation'; title = `Question facturation — ${label}`; }
  else if (/api|token|401|403|500|erreur/i.test(msgText)) { cat = 'Technique & API'; prio = 'Haute'; title = `Problème API — ${label}`; }
  else if (/sms|campagne|envoi|délivrés/i.test(msgText)) { cat = 'Plateforme SMS'; title = `Problème SMS — ${label}`; }
  else if (/connexion|login|compte/i.test(msgText)) { cat = 'Connexion'; prio = 'Haute'; title = `Problème connexion — ${label}`; }

  document.getElementById('tkTitle').value = title;
  document.getElementById('tkDesc').value = messages.filter(m => {
    const userId = conv.user_id;
    return m.direction ? m.direction === 'inbound' : m.sender_id === userId;
  }).map(m => m.content || '').join('\n');
  document.getElementById('tkCat').value = cat;
  document.getElementById('tkPrio').value = prio;
  document.getElementById('tkAiTxt').textContent = `Catégorie : ${cat} · Priorité : ${prio} (confiance 80%)`;
  document.getElementById('tkModal').classList.add('on');
}

function closeTkModal() { document.getElementById('tkModal').classList.remove('on'); }

function createTicket() {
  const title = document.getElementById('tkTitle').value.trim();
  if (!title) { document.getElementById('tkTitle').style.borderColor = '#EF4444'; return; }
  const tkId = '#TK-' + Math.floor(Math.random() * 9000 + 1000);
  closeTkModal();
  showToast('Ticket ' + tkId + ' créé avec succès ✓');
}

// ══ REFRESH ═══════════════════════════════════════════════════════
function refreshAll() {
  fetchStatus(); fetchInbox();
  if (selId) { fetchThread(selId); fetchSummary(selId); fetchSla(selId); fetchPolicy(selId); }
  showToast('Données actualisées');
}

async function refreshSummary() {
  if (!selId) return;
  document.getElementById('summaryRefreshIcon').classList.add('spin');
  await fetchSummary(selId);
  document.getElementById('summaryRefreshIcon').classList.remove('spin');
}

// ══ ACCORDION TOGGLE ══════════════════════════════════════════════
function toggleCtxSection(key) {
  openSections[key] = !openSections[key];
  const body = document.getElementById(`body-${key}`);
  const chevron = document.getElementById(`chevron-${key}`);
  body.classList.toggle('ctx-hidden', !openSections[key]);
  chevron.classList.toggle('open', openSections[key]);
}

function switchTab(tab, btn) {
  activeTab = tab;
  document.querySelectorAll('.wa-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  fetchInbox();
}

function filterConvs(q) { renderList(q); }

// ══ HELPERS ═══════════════════════════════════════════════════════
function convLabel(c) {
  return c.contact_name || c.contact_phone || c.subject || c.id.slice(0, 8);
}

const AVATAR_COLORS = ['#10B981','#6C63FF','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316'];
function avatarColor(id) {
  let hash = 0;
  for (let i = 0; i < id.length; i++) hash = (hash * 31 + id.charCodeAt(i)) | 0;
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

function getInitials(label) {
  return label.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase() || '?';
}

function formatDateTime(value) {
  if (!value) return 'Maintenant';
  const date = new Date(value);
  if (isNaN(date.getTime())) return 'Maintenant';
  const now = new Date();
  if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
  return date.toLocaleDateString('fr-FR', { month:'short', day:'numeric' });
}

function formatSlaCountdown(totalSeconds) {
  if (!isFinite(totalSeconds)) return 'bientôt';
  const s = Math.max(0, Math.floor(totalSeconds));
  const d = Math.floor(s / 86400);
  const h = Math.floor((s % 86400) / 3600);
  const m = Math.floor((s % 3600) / 60);
  if (d > 0) return `${d}j ${h}h`;
  if (h > 0) return `${h}h ${m}m`;
  if (m > 0) return `${m}m`;
  return `${s}s`;
}

function formatBlockReason(reason) {
  switch (reason) {
    case 'channel_disabled': return 'Bloqué par la politique du canal';
    case 'conversation_disabled': return 'Bloqué par le toggle de conversation';
    case 'pause_active': return 'En pause (minuterie)';
    default: return 'Non bloqué';
  }
}

function formatPauseUntil(value) {
  if (!value) return 'bientôt';
  const d = new Date(value);
  if (isNaN(d.getTime())) return 'bientôt';
  return d.toLocaleString('fr-FR', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
}

function escH(t) {
  return String(t || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function autoH(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 140) + 'px'; }

let toastTO;
function showToast(msg) {
  const t = document.getElementById('wa-toast'); t.textContent = msg; t.classList.add('show');
  clearTimeout(toastTO); toastTO = setTimeout(() => t.classList.remove('show'), 2500);
}

function formatPhone(inp) {
  let v = inp.value.replace(/\D/g,'');
  if (v.length > 2 && v.length <= 5) v = v.substring(0,2)+' '+v.substring(2);
  else if (v.length > 5) v = v.substring(0,2)+' '+v.substring(2,5)+' '+v.substring(5,8);
  inp.value = v;
}

// ══ BOOT ═════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    document.getElementById('scr-loading').style.display = 'none';
    document.getElementById('scr-connect').style.display = 'flex';
    showQrScreen();
  }, 2000);
});
</script>
@endsection
