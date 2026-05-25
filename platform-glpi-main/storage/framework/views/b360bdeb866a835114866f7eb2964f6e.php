<?php $__env->startSection('title', 'Assistant L2T'); ?>

<?php $__env->startSection('content'); ?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
/* ── Hide floating widget on this full-page chat ── */
#cwBtn,#cwWrap,#cwWrap #cwPanel{display:none!important;visibility:hidden!important;pointer-events:none!important;}
.main-content{padding:0!important;overflow:hidden;}
*{box-sizing:border-box;}

/* ══ VARIABLES ══ */
#chatWrap{
  --p:#6C63FF;
  --p-soft:#EEEDFE;
  --p-mid:#AFA9EC;
  --p-dark:#3C3489;
  --bg:var(--color-background-tertiary,#F7F8FC);
  --bg2:var(--color-background-primary,#FFFFFF);
  --bg3:var(--color-background-secondary,#F0F1F6);
  --brd:var(--color-border-tertiary,#E4E6EF);
  --brd2:var(--color-border-secondary,#CDD0E0);
  --t1:var(--color-text-primary,#0F1117);
  --t2:var(--color-text-primary,#2D3047);
  --t3:var(--color-text-secondary,#6B7280);
  --t4:var(--color-text-tertiary,#9CA3AF);
  --t5:var(--color-text-tertiary,#C8CDD9);
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
  --radius-md:8px;
  --radius-lg:12px;
  --radius-xl:16px;
}

[data-bs-theme="dark"] #chatWrap {
  --p:#818CF8;
  --p-soft:rgba(129, 140, 248, 0.1);
  --p-mid:#6366F1;
  --p-dark:#4338CA;
  --bg:#0f172a;
  --bg2:#1e293b;
  --bg3:#334155;
  --brd:#334155;
  --brd2:#475569;
  --t1:#f1f5f9;
  --t2:#e2e8f0;
  --t3:#94a3b8;
  --t4:#64748b;
  --t5:#475569;
}

/* ══ LAYOUT ══ */
#chatWrap{
  display:flex;
  height:calc(100vh - 130px);
  border-radius: 20px;
  background:var(--bg);
  font-family:var(--font);
  overflow:hidden;
  position:relative;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  border: 1px solid var(--brd);
}

/* ══ SIDEBAR ══ */
#chatSide{
  width:256px;min-width:256px;
  background:var(--bg2);
  border-right:0.5px solid var(--brd);
  display:flex;flex-direction:column;
  transition:width .25s cubic-bezier(.4,0,.2,1),min-width .25s,opacity .2s;
  overflow:hidden;flex-shrink:0;
}
#chatSide.closed{width:0;min-width:0;opacity:0;border:none;pointer-events:none;}

.side-top{
  padding:14px 14px 12px;
  display:flex;align-items:center;gap:8px;
}
.side-logo{
  width:32px;height:32px;border-radius:9px;
  background:var(--p);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;color:#fff;
}
.side-name{font-size:13px;font-weight:500;color:var(--t1);white-space:nowrap;flex:1;}
.side-icon-btn{
  width:28px;height:28px;border-radius:7px;border:none;
  background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:var(--t3);transition:background .15s,color .15s;flex-shrink:0;
  font-size:15px;
}
.side-icon-btn:hover{background:var(--bg3);color:var(--t1);}

.btn-new{
  margin:0 12px 12px;
  display:flex;align-items:center;gap:8px;
  padding:9px 14px;
  background:var(--bg3);
  border:0.5px solid var(--brd2);
  border-radius:999px;cursor:pointer;
  font-size:13px;font-weight:500;color:var(--t1);
  font-family:var(--font);
  transition:border-color .15s,background .15s,color .15s;
  width:calc(100% - 24px);
}
.btn-new:hover{border-color:var(--p);background:var(--p-soft);color:var(--p);}
.btn-new i{font-size:14px;color:var(--t4);}
.btn-new:hover i{color:var(--p);}

.side-section-label{
  padding:6px 14px 4px;
  font-size:11px;font-weight:500;color:var(--t4);
  text-transform:uppercase;letter-spacing:.06em;
}

.side-list{flex:1;overflow-y:auto;padding:2px 8px;}
.side-list::-webkit-scrollbar{width:3px;}
.side-list::-webkit-scrollbar-thumb{background:var(--brd2);border-radius:2px;}

.ci{
  display:flex;align-items:center;gap:8px;
  padding:8px 9px;border-radius:9px;cursor:pointer;
  font-size:12.5px;color:var(--t3);
  transition:background .12s,color .12s;
}
.ci:hover{background:var(--bg3);color:var(--t2);}
.ci.active{background:var(--p-soft);color:var(--p);font-weight:500;}
.ci i{font-size:13px;flex-shrink:0;}
.ci span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
.ci-meta{font-size:10.5px;color:var(--t5);flex-shrink:0;}

.ci-actions { margin-left: auto; position: relative; }
.ci-dots {
  background: none; border: none; color: var(--t4); cursor: pointer; padding: 2px 4px; border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s, background 0.2s;
}
.ci:hover .ci-dots { opacity: 1; }
.ci-dots:hover { background: var(--bg3); color: var(--t1); }
.conv-menu-item {
  width: 100%; text-align: left; background: none; border: none;
  padding: 8px 12px; font-size: 13px; color: var(--t2); cursor: pointer;
  display: flex; align-items: center; gap: 8px; font-family: var(--font);
  transition: background 0.15s;
}
.conv-menu-item:hover { background: var(--bg3); }

.side-foot{
  padding:12px 14px;
  border-top:0.5px solid var(--brd);
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.side-av{
  width:30px;height:30px;border-radius:50%;
  background:var(--p);
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:500;font-size:12px;flex-shrink:0;
}
.side-un{font-size:13px;font-weight:500;color:var(--t1);}
.side-ur{font-size:11px;color:var(--t4);}
.online-dot{
  width:8px;height:8px;border-radius:50%;
  background:#22c55e;
  box-shadow:0 0 0 2px var(--bg2);
  margin-left:auto;flex-shrink:0;
}

.side-open-btn{
  width:32px;height:32px;border-radius:8px;
  border:0.5px solid var(--brd2);background:var(--bg3);
  cursor:pointer;display:none;align-items:center;justify-content:center;
  color:var(--t3);transition:all .15s;flex-shrink:0;font-size:16px;
}
.side-open-btn:hover{border-color:var(--p);color:var(--p);background:var(--p-soft);}
#chatSide.closed + #chatMain .side-open-btn{display:flex;}

/* ══ MAIN ══ */
#chatMain{flex:1;display:flex;flex-direction:column;overflow:hidden;position:relative;}

/* ══ TOP BAR ══ */
#chatTop{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 18px;
  background:var(--bg2);
  border-bottom:0.5px solid var(--brd);
  flex-shrink:0;gap:12px;
}
.top-l{display:flex;align-items:center;gap:10px;min-width:0;}
.top-r{display:flex;align-items:center;gap:6px;flex-shrink:0;}

.agent-av{
  width:34px;height:34px;border-radius:50%;
  background:var(--p-soft);
  border:1.5px solid var(--p-mid);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:500;color:var(--p);
  flex-shrink:0;transition:all .3s;
}
.agent-name{font-size:14px;font-weight:500;color:var(--t1);}
.agent-status{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--t3);}
.status-dot{width:6px;height:6px;border-radius:50%;background:#22c55e;}

.wrow{display:flex;align-items:center;gap:3px;height:13px;}
.wb{width:3px;border-radius:99px;background:var(--p);transition:height .3s;}
.wb.on{animation:wv .6s ease-in-out infinite alternate;}
@keyframes wv{from{transform:scaleY(.3)}to{transform:scaleY(1)}}

.ibtn{
  width:32px;height:32px;border-radius:8px;
  border:0.5px solid var(--brd2);background:var(--bg3);
  color:var(--t3);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:all .15s;flex-shrink:0;font-size:15px;
}
.ibtn:hover{border-color:var(--p);color:var(--p);background:var(--p-soft);}
.ibtn.active{background:var(--p-soft);border-color:color-mix(in srgb,var(--p) 40%,transparent);color:var(--p);}
.ibtn.callb{background:#dcfce7;border-color:#86efac;color:#16a34a;}
.ibtn.callb:hover{background:#bbf7d0;border-color:#4ade80;}
.ibtn.callb.in-call{background:rgba(239,68,68,.09);border-color:rgba(239,68,68,.3);color:#dc2626;}

/* ══ SHARE BANNER ══ */
#shareBanner{
  display:none;align-items:center;gap:9px;
  padding:7px 18px;flex-shrink:0;
  background:linear-gradient(135deg,var(--p),var(--p-mid));
  color:#fff;font-size:12px;font-weight:500;
}
#shareBanner.on{display:flex;}
.stop-share{
  margin-left:auto;padding:3px 11px;border-radius:20px;
  border:1.5px solid rgba(255,255,255,.5);background:transparent;
  color:#fff;cursor:pointer;font-size:11px;font-weight:600;font-family:var(--font);
}

/* ══ MESSAGES ══ */
#chatMsgs{
  flex:1;overflow-y:auto;padding:20px 0;
  scrollbar-width:thin;scrollbar-color:var(--brd) transparent;
}
#chatMsgs::-webkit-scrollbar{width:4px;}
#chatMsgs::-webkit-scrollbar-thumb{background:var(--brd);border-radius:3px;}

.mrow{
  max-width:720px;width:100%;
  margin:0 auto 2px;padding:4px 20px;
  animation:mi .18s ease;
}
@keyframes mi{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}

.ru{display:flex;justify-content:flex-end;}
.ru .bbl{
  max-width:68%;
  padding:10px 14px;word-break:break-word;
  background:var(--p-soft);
  border:0.5px solid var(--p-mid);
  border-radius:16px 16px 4px 16px;
  font-size:13.5px;line-height:1.65;color:var(--p-dark);
}
[data-bs-theme="dark"] .ru .bbl {
  color: var(--t1);
}

.rb{display:flex;align-items:flex-start;gap:10px;}
.bav{
  width:28px;height:28px;border-radius:50%;
  background:var(--p-soft);border:1.5px solid var(--p-mid);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:500;color:var(--p);
  flex-shrink:0;margin-top:2px;transition:all .3s;
}
.rb .bbl{
  max-width:76%;
  padding:10px 14px;word-break:break-word;
  background:var(--bg3);
  border:0.5px solid var(--brd);
  border-radius:16px 16px 16px 4px;
  font-size:13.5px;line-height:1.75;color:var(--t2);
}
.bbl code{background:var(--bg);padding:1px 5px;border-radius:4px;font-size:12px;}
.bbl pre{background:var(--bg);padding:10px 12px;border-radius:8px;font-size:12px;overflow-x:auto;margin:8px 0;}

.btime{text-align:right;margin-top:4px;font-size:10.5px;color:var(--t5);}

.macts{display:flex;gap:4px;margin-top:5px;opacity:0;transition:opacity .15s;}
.mrow:hover .macts{opacity:1;}
.mact{
  padding:3px 9px;border-radius:6px;
  border:0.5px solid var(--brd2);background:var(--bg2);
  cursor:pointer;font-size:11px;color:var(--t4);
  display:flex;align-items:center;gap:3px;
  transition:all .12s;font-family:var(--font);
}
.mact:hover{border-color:var(--p);color:var(--p);}

.tdots{display:flex;gap:4px;align-items:center;padding:2px 0;}
.tdots span{
  width:6px;height:6px;border-radius:50%;
  background:var(--p-mid);
  animation:td .8s ease-in-out infinite alternate;
}
.tdots span:nth-child(2){animation-delay:.2s;}
.tdots span:nth-child(3){animation-delay:.4s;}
@keyframes td{from{transform:translateY(0)}to{transform:translateY(-5px)}}

.date-divider{
  display:flex;align-items:center;gap:10px;
  max-width:720px;margin:10px auto;padding:0 20px;
}
.date-divider::before,.date-divider::after{
  content:'';flex:1;height:0.5px;background:var(--brd);
}
.date-divider span{font-size:11px;color:var(--t4);white-space:nowrap;}

/* ══ WELCOME ══ */
#welcomeScr{
  flex:1;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  padding:40px 22px;text-align:center;
}
.wav{
  width:64px;height:64px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:24px;font-weight:500;margin:0 auto 14px;
  transition:all .3s;
}
.wtit{font-size:19px;font-weight:500;color:var(--t1);margin:0 0 6px;}
.wsub{font-size:13px;color:var(--t3);margin:0 0 24px;line-height:1.6;max-width:340px;}
.sgrid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;max-width:460px;width:100%;}
.sug{
  padding:12px 14px;border-radius:12px;
  background:var(--bg2);border:0.5px solid var(--brd2);
  cursor:pointer;text-align:left;
  transition:border-color .15s,transform .15s;font-family:var(--font);
}
.sug:hover{border-color:var(--p);transform:translateY(-1px);}
.si{font-size:18px;display:block;margin-bottom:4px;}
.st{display:block;font-size:12.5px;font-weight:500;color:var(--t1);margin-bottom:2px;}
.sd{display:block;font-size:11px;color:var(--t4);}

/* ══ FILE PREVIEW ══ */
#filePrev{
  margin:0 20px 7px;max-width:720px;margin-left:auto;margin-right:auto;
  padding:7px 12px;border-radius:9px;
  background:var(--bg2);border:0.5px solid color-mix(in srgb,var(--p) 32%,transparent);
  display:none;align-items:center;justify-content:space-between;
}
#filePrev.on{display:flex;}

/* ══ INPUT ══ */
#inputWrap{
  padding:10px 16px 14px;
  background:var(--bg2);
  border-top:0.5px solid var(--brd);flex-shrink:0;
}
#inputBox{
  max-width:720px;margin:0 auto;
  background:var(--bg3);
  border:0.5px solid var(--brd2);
  border-radius:14px;
  transition:border-color .2s,box-shadow .2s;
}
#inputBox:focus-within{
  border-color:var(--p);
  box-shadow:0 0 0 3px var(--p-soft);
}
.irow{display:flex;align-items:flex-end;gap:6px;padding:8px;}
#msgIn{
  flex:1;background:transparent;border:none;outline:none;
  color:var(--t1);font-size:13.5px;font-family:var(--font);
  resize:none;max-height:130px;overflow-y:auto;
  line-height:1.6;min-height:20px;padding-top:2px;
}
#msgIn::placeholder{color:var(--t5);}
.inpbtn{
  width:30px;height:30px;border-radius:8px;
  background:transparent;
  border:0.5px solid var(--brd2);color:var(--t3);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:all .15s;flex-shrink:0;font-size:14px;
}
.inpbtn:hover{color:var(--t1);border-color:var(--p);}
.inpbtn.rec{
  background:linear-gradient(135deg,#EF4444,#DC2626);
  border-color:#EF4444;color:#fff;
  animation:rp 1s ease infinite;
  box-shadow:0 0 10px rgba(239,68,68,.35);
}
@keyframes rp{0%,100%{opacity:1}50%{opacity:.7}}
.inpsend{
  width:32px;height:32px;border-radius:9px;
  background:var(--p);border:none;color:#fff;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:opacity .2s,transform .15s;flex-shrink:0;
}
.inpsend:disabled{opacity:.3;cursor:not-allowed;}
.inpsend:not(:disabled):hover{opacity:.88;transform:scale(1.04);}
.ihint{text-align:center;font-size:10.5px;color:var(--t5);max-width:720px;margin:6px auto 0;}

/* ═══════════════════════════════════════════════
   ══ IMPROVED CALL OVERLAY ══
═══════════════════════════════════════════════ */
#callOv{
  display:none;
  position:absolute;inset:0;z-index:200;
  background:var(--bg);
  flex-direction:column;
  overflow:hidden;
  font-family:var(--font);
}
#callOv.on{display:flex;}

/* Animated mesh background */
#callBg{
  position:absolute;inset:0;
  pointer-events:none;
  overflow:hidden;
}
.call-blob{
  position:absolute;
  border-radius:50%;
  filter:blur(72px);
  opacity:0.18;
  animation:blobFloat 8s ease-in-out infinite alternate;
}
.call-blob-1{
  width:380px;height:380px;
  top:-80px;left:-60px;
  background:radial-gradient(circle,#6C63FF,#4C44CC);
  animation-duration:9s;
}
.call-blob-2{
  width:280px;height:280px;
  bottom:-40px;right:-40px;
  background:radial-gradient(circle,#8B85FF,#6C63FF);
  animation-duration:7s;animation-delay:2s;
}
.call-blob-3{
  width:200px;height:200px;
  top:40%;left:50%;
  background:radial-gradient(circle,#A78BFA,#7C3AED);
  opacity:0.09;
  animation-duration:11s;animation-delay:1s;
}
@keyframes blobFloat{
  from{transform:translate(0,0) scale(1);}
  to{transform:translate(20px,30px) scale(1.12);}
}

/* Subtle grid overlay */
#callBg::after{
  content:'';
  position:absolute;inset:0;
  background-image:
    linear-gradient(var(--brd) 1px,transparent 1px),
    linear-gradient(90deg,var(--brd) 1px,transparent 1px);
  background-size:40px 40px;
  opacity: 0.3;
}

/* ── Call Header ── */
.call-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 24px 0;
  position:relative;z-index:2;
  flex-shrink:0;
}
.call-header-left{display:flex;align-items:center;gap:10px;}
.call-header-right{display:flex;align-items:center;gap:8px;}

/* Live indicator pill */
.call-live-pill{
  display:flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:99px;
  background:var(--bg2);
  border:1px solid var(--brd);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.call-live-dot{
  width:7px;height:7px;border-radius:50%;
  background:var(--t4);
  transition:background .3s,box-shadow .3s;
}
.call-live-dot.active{
  background:#4ADE80;
  box-shadow:0 0 0 3px rgba(74,222,128,.2);
  animation:livePulse 2s ease infinite;
}
@keyframes livePulse{
  0%,100%{box-shadow:0 0 0 3px rgba(74,222,128,.2);}
  50%{box-shadow:0 0 0 6px rgba(74,222,128,.08);}
}
.call-live-time{
  color:var(--t2);
  font-size:12px;
  font-weight: 500;
  font-variant-numeric:tabular-nums;
  letter-spacing:.02em;
}

/* Rec badge */
#callRecBadge{
  display:none;align-items:center;gap:6px;
  padding:5px 12px;border-radius:99px;
  background:rgba(239,68,68,.12);
  border:1px solid rgba(239,68,68,.28);
  backdrop-filter:blur(8px);
}
#callRecBadge.on{display:flex;}
.rec-dot-anim{
  width:7px;height:7px;border-radius:50%;
  background:#EF4444;
  animation:livePulse2 1.2s ease infinite;
}
@keyframes livePulse2{
  0%,100%{box-shadow:0 0 0 2px rgba(239,68,68,.25);}
  50%{box-shadow:0 0 0 5px rgba(239,68,68,.08);}
}
#callRecBadge span{font-size:11px;color:#FCA5A5;font-weight:500;}

/* Role badge */
#callRoleBadge{
  padding:5px 12px;border-radius:99px;
  font-size:11px;font-weight:600;letter-spacing:.03em;
  backdrop-filter:blur(8px);
}

/* ── Call Error ── */
#callError{
  display:none;
  position:absolute;top:76px;left:50%;transform:translateX(-50%);
  background:rgba(239,68,68,.13);
  border:1px solid rgba(239,68,68,.35);
  border-radius:12px;
  padding:10px 18px;
  font-size:12px;color:#FCA5A5;
  backdrop-filter:blur(8px);
  text-align:center;max-width:80%;z-index:10;
  animation:slideDown .2s ease;
}
#callError.on{display:block;}
@keyframes slideDown{from{opacity:0;transform:translateX(-50%) translateY(-6px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}

/* ── Call Center Stage ── */
.call-stage{
  flex:1;
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  gap:20px;
  position:relative;z-index:2;
  padding:0 24px;
}

/* Avatar wrapper with rings */
#cavwrap{
  position:relative;
  display:flex;align-items:center;justify-content:center;
  width:176px;height:176px;
}

/* Animated rings */
.call-ring{
  position:absolute;
  border-radius:50%;
  border:1px solid rgba(108,99,255,.25);
  animation:callRingExpand 2.4s ease-out infinite;
  top:50%;left:50%;
  transform:translate(-50%,-50%);
}
.call-ring:nth-child(1){ width:156px;height:156px; }
.call-ring:nth-child(2){ width:188px;height:188px;animation-delay:.5s; }
.call-ring:nth-child(3){ width:224px;height:224px;animation-delay:1s; }
@keyframes callRingExpand{
  0%{opacity:.55;transform:translate(-50%,-50%) scale(.92);}
  100%{opacity:0;transform:translate(-50%,-50%) scale(1.08);}
}

/* Main avatar */
#cav{
  width:112px;height:112px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:38px;font-weight:600;
  position:relative;z-index:2;
  transition:all .5s cubic-bezier(.4,0,.2,1);
  letter-spacing:-.01em;
}

/* Agent info */
.call-agent-info{text-align:center;}
#cagn{
  font-size:22px;font-weight:600;
  color:var(--t1);
  letter-spacing:-.02em;
  margin:0 0 6px;
}
#callStatus{
  font-size:12px;color:var(--t4);
  font-weight:500;
  transition:opacity .3s;
}

/* Wave visualizer */
#cwv{
  display:flex;justify-content:center;
  align-items:center;
  gap:3px;
  height:28px;
  margin-top:4px;
}
.cwb{
  width:3px;border-radius:99px;
  background:rgba(108,99,255,.7);
  transition:height .15s;
}

/* ── Self preview pill ── */
.call-self-preview{
  position:absolute;
  bottom:18px;right:20px;
  display:flex;align-items:center;gap:8px;
  padding:7px 12px 7px 8px;
  background:var(--bg2);
  border:1px solid var(--brd);
  border-radius:99px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  z-index:5;
  transition:border-color .2s;
}
.call-self-preview:hover{border-color:var(--p);}
.call-self-av{
  width:26px;height:26px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:600;
  background:rgba(108,99,255,.35);
  border:1.5px solid var(--p-mid);
  color:var(--p);
  flex-shrink:0;
}
.call-self-name{font-size:11px;color:var(--t3);font-weight:600;}
.call-self-mic{
  width:16px;height:16px;
  display:flex;align-items:center;justify-content:center;
  color:var(--t4);
  font-size:12px;
  flex-shrink:0;
}
.call-self-mic.muted{color:#EF4444;}

/* ═══════════════════════════════════════════════
   ══ IMPROVED CALL CONTROLS ══
═══════════════════════════════════════════════ */
.call-controls{
  display:flex;align-items:center;justify-content:center;
  gap:12px;
  padding:20px 24px 28px;
  position:relative;z-index:2;
  flex-shrink:0;
}

/* Control button base */
.cc{
  display:flex;flex-direction:column;align-items:center;gap:5px;
  cursor:pointer;
  -webkit-user-select:none;user-select:none;
  background:none;border:none;
  padding:0;
}
.cc-btn{
  width:52px;height:52px;border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  background:var(--bg3);
  border:1px solid var(--brd);
  color:var(--t3);
  font-size:18px;
  transition:all .2s cubic-bezier(.4,0,.2,1);
  position:relative;
  overflow:hidden;
}
.cc-btn::before{
  content:'';
  position:absolute;inset:0;border-radius:inherit;
  background:linear-gradient(135deg,var(--p-soft),transparent);
  opacity:0;transition:opacity .2s;
}
.cc:hover .cc-btn{
  background:var(--p-soft);
  border-color:var(--p-mid);
  color:var(--p);
  transform:translateY(-2px);
}
.cc:hover .cc-btn::before{opacity:1;}
.cc:active .cc-btn{transform:translateY(0) scale(.96);}
.cc-label{
  font-size:10px;color:var(--t4);
  font-weight:600;letter-spacing:.02em;
  transition:color .2s;
  white-space:nowrap;
}
.cc:hover .cc-label{color:var(--p);}

/* Active states */
.cc.on .cc-btn{
  background:var(--p-soft);
  border-color:var(--p-mid);
  color:var(--p);
}
.cc.on .cc-label{color:var(--p);}
.cc.rec-on .cc-btn{
  background:rgba(239,68,68,.18);
  border-color:rgba(239,68,68,.45);
  color:#EF4444;
}
.cc.rec-on .cc-label{color:#EF4444;}
.cc.scr-on .cc-btn{
  background:rgba(56,189,248,.15);
  border-color:rgba(56,189,248,.4);
  color:#0284C7;
}
.cc.scr-on .cc-label{color:#0284C7;}

/* End call button */
.call-end-wrap{display:flex;flex-direction:column;align-items:center;gap:5px;}
.cend{
  width:60px;height:60px;border-radius:18px;
  background:linear-gradient(135deg,#EF4444,#DC2626);
  border:none;color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 16px rgba(239,68,68,.3);
  transition:all .2s cubic-bezier(.4,0,.2,1);
  font-size:20px;
}
.cend:hover{
  transform:translateY(-2px) scale(1.04);
  box-shadow:0 6px 20px rgba(239,68,68,.4);
}
.cend:active{transform:scale(.97);}
.call-end-label{font-size:10px;color:#EF4444;font-weight:600;letter-spacing:.02em;}

/* ── Divider between control groups ── */
.ctrl-divider{
  width:1px;height:32px;
  background:var(--brd);
  margin:0 4px;
  align-self:center;
}

/* ═══════════════════════════════════════════════
   ══ IMPROVED SCREEN SHARE – IN-CALL PANEL ══
═══════════════════════════════════════════════ */
#callScrPanel{
  display:none;
  position:absolute;
  bottom:110px;right:18px;
  width:340px;
  z-index:20;
  flex-direction:column;
  gap:8px;
  pointer-events:none;
}
#callScrPanel.on{display:flex;}
#callScrPanel > *{pointer-events:auto;}

/* Analysis bubble */
#callScrBubble{
  display:none;
  background:rgba(12,12,28,.88);
  border:1px solid rgba(108,99,255,.28);
  border-radius:16px;
  padding:14px 16px;
  backdrop-filter:blur(16px);
  box-shadow:0 8px 32px rgba(0,0,0,.5);
}
#callScrBubble.on{display:block;}
#callScrBubbleTitle{
  font-size:10px;font-weight:600;
  color:rgba(165,180,252,.8);
  text-transform:uppercase;letter-spacing:.08em;
  margin-bottom:6px;
  display:flex;align-items:center;gap:5px;
}
#callScrBubbleTitle::before{
  content:'';
  width:6px;height:6px;border-radius:50%;
  background:#818CF8;
  display:inline-block;
  animation:livePulse 2s ease infinite;
}
#callScrBubbleText{
  font-size:12px;color:rgba(209,213,219,.9);
  line-height:1.65;
  margin:0;
}

/* Video wrapper */
#callScrVideoWrap{
  position:relative;border-radius:16px;overflow:hidden;
  border:1px solid rgba(255,255,255,.14);
  background:#000;aspect-ratio:16/9;
  box-shadow:0 12px 40px rgba(0,0,0,.6),0 0 0 1px rgba(108,99,255,.1);
}
#callScrVideo{width:100%;height:100%;object-fit:cover;display:block;}

/* Top badges row */
#callScrBadge{
  position:absolute;top:10px;left:10px;
  display:none;align-items:center;gap:5px;
  padding:4px 10px;border-radius:99px;
  background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.14);
  backdrop-filter:blur(6px);
}
#callScrBadge.on{display:flex;}
.cscr-dot{width:6px;height:6px;border-radius:50%;background:#EF4444;animation:livePulse2 1s infinite;flex-shrink:0;}
#callScrFrameLabel{font-size:10px;font-weight:500;color:rgba(255,255,255,.85);font-family:var(--font);font-variant-numeric:tabular-nums;}
#callScrLivePill{
  position:absolute;top:10px;right:10px;
  padding:4px 10px;border-radius:99px;
  background:rgba(108,99,255,.3);border:1px solid rgba(108,99,255,.5);
  font-size:10px;font-weight:600;color:#C4BFFF;
  font-family:var(--font);backdrop-filter:blur(6px);
  letter-spacing:.04em;
}

/* Analyzing bar */
#callScrAnalyzingBar{
  position:absolute;bottom:0;left:0;right:0;
  display:none;align-items:center;gap:7px;
  padding:8px 12px;
  background:linear-gradient(to top,rgba(0,0,0,.85),transparent);
}
#callScrAnalyzingBar.on{display:flex;}
.scr-spin{
  width:12px;height:12px;
  border:2px solid rgba(108,99,255,.35);border-top-color:#818CF8;
  border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0;
}
@keyframes spin{to{transform:rotate(360deg)}}
#callScrAnalyzingBar span{font-size:11px;color:rgba(255,255,255,.6);font-family:var(--font);}

/* Error overlay */
#callScrError{
  position:absolute;inset:0;
  display:none;flex-direction:column;align-items:center;justify-content:center;
  gap:6px;background:rgba(0,0,0,.78);
  padding:16px;text-align:center;
}
#callScrError.on{display:flex;}
#callScrErrorText{font-size:11px;color:#FCA5A5;line-height:1.5;margin:0;}

#callScrTimestamp{
  text-align:center;
  font-size:10px;color:rgba(255,255,255,.2);
  font-family:var(--font);
  padding:2px 0;
}

/* ══ SCREEN PANEL (chat) ══ */
#screenPanel{
  display:none;
  position:absolute;bottom:80px;right:18px;
  width:308px;
  background:var(--bg2);
  border:0.5px solid var(--brd);
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 32px rgba(0,0,0,.12),0 0 0 0.5px var(--brd);
  z-index:30;
  animation:panelReveal .2s ease;
}
#screenPanel.on{display:block;}
@keyframes panelReveal{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.spanel-hdr{
  padding:11px 14px;border-bottom:0.5px solid var(--brd);
  display:flex;align-items:center;justify-content:space-between;
  background:var(--bg2);
}
.spanel-tit{font-size:12px;font-weight:600;color:var(--t1);}
.spanel-badge{font-size:10px;padding:2px 8px;border-radius:99px;font-weight:500;}
.spanel-body{padding:12px 14px;max-height:200px;overflow-y:auto;}
.spanel-text{font-size:12px;color:var(--t2);line-height:1.65;margin:0;}
.spanel-meta{font-size:10px;color:var(--t4);margin-top:6px;}
.spanel-close{
  background:none;border:none;cursor:pointer;
  color:var(--t4);display:flex;align-items:center;justify-content:center;
  padding:2px;font-size:14px;
  transition:color .15s;
}
.spanel-close:hover{color:var(--t2);}

/* ═══════════════════════════════════════════════
   ══ SPLIT VIEW — SCREEN + MINI CALL PANEL ══
═══════════════════════════════════════════════ */
#splitView{
  display:none;
  position:absolute;
  inset:0;
  z-index:50;
  background:#050510;
  overflow:hidden;
}

#splitView.on{
  display:flex;
}

/* LEFT SIDE: Screen Preview */
#splitScr{
  width:50%;
  min-width:50%;
  display:flex;
  flex-direction:column;
  background:#060613;
  border-right:1px solid rgba(255,255,255,.06);
}

/* RIGHT SIDE: Mini Call Panel */
#miniCallPanel{
  width:50%;
  min-width:50%;
  position:relative;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  background:
    radial-gradient(circle at top left,
      rgba(108,99,255,.25),
      transparent 40%),
    radial-gradient(circle at bottom right,
      rgba(139,133,255,.18),
      transparent 40%),
    #08071A;
}

/* TOP: Live indicator + Close */
.mini-call-top{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:18px 22px;
}

.mini-live{
  display:flex;
  align-items:center;
  gap:8px;
  padding:7px 14px;
  border-radius:999px;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.1);
  backdrop-filter:blur(8px);
}

.mini-live-dot{
  width:8px;
  height:8px;
  border-radius:50%;
  background:#4ADE80;
  animation:livePulse 1.5s infinite;
}

#miniCallTime{
  color:rgba(255,255,255,.75);
  font-size:12px;
  font-weight:500;
}

.mini-close{
  width:34px;
  height:34px;
  border:none;
  border-radius:12px;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.08);
  color:#fff;
  cursor:pointer;
  transition:.2s;
}

.mini-close:hover{
  background:rgba(255,255,255,.12);
}

/* CENTER: Avatar + Agent info */
.mini-call-center{
  flex:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  padding:20px;
}

.mini-avatar-wrap{
  position:relative;
  width:160px;
  height:160px;
  display:flex;
  align-items:center;
  justify-content:center;
}

.mini-ring{
  position:absolute;
  width:130px;
  height:130px;
  border-radius:50%;
  border:1px solid rgba(108,99,255,.25);
  animation:callRingExpand 2s infinite;
}

.mini-ring-2{
  width:170px;
  height:170px;
  animation-delay:.6s;
}

#miniAvatar{
  width:92px;
  height:92px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(108,99,255,.15);
  border:2px solid rgba(108,99,255,.45);
  color:#A5B4FC;
  font-size:34px;
  font-weight:700;
  backdrop-filter:blur(12px);
  z-index:2;
}

.mini-agent-name{
  margin-top:26px;
  font-size:22px;
  font-weight:700;
  color:#fff;
}

.mini-agent-status{
  margin-top:8px;
  font-size:13px;
  color:rgba(255,255,255,.45);
}

.mini-wave{
  display:flex;
  align-items:center;
  gap:4px;
  margin-top:18px;
  height:20px;
}

.mini-wave span{
  width:4px;
  border-radius:999px;
  background:#8B85FF;
  animation:wv .7s infinite alternate;
}

.mini-wave span:nth-child(1){
  height:6px;
}

.mini-wave span:nth-child(2){
  height:16px;
  animation-delay:.15s;
}

.mini-wave span:nth-child(3){
  height:10px;
  animation-delay:.3s;
}

.mini-wave span:nth-child(4){
  height:18px;
  animation-delay:.45s;
}

/* CONTROLS: Mute, Video, End */
.mini-controls{
  display:flex;
  justify-content:center;
  gap:16px;
  padding:26px;
}

.mini-btn{
  width:58px;
  height:58px;
  border-radius:18px;
  border:1px solid rgba(255,255,255,.1);
  background:rgba(255,255,255,.08);
  color:#fff;
  font-size:20px;
  cursor:pointer;
  transition:.2s;
  backdrop-filter:blur(10px);
}

.mini-btn:hover{
  transform:translateY(-2px);
  background:rgba(255,255,255,.14);
}

.mini-btn.on{
  background:rgba(255,99,71,.4);
  border-color:rgba(255,99,71,.6);
}

.mini-btn.end{
  background:linear-gradient(135deg,#EF4444,#DC2626);
  border:none;
}

/* SCREEN HEADER */
.sphdr{
  display:flex;
  align-items:center;
  gap:10px;
  padding:16px 18px;
  border-bottom:0.5px solid rgba(255,255,255,.08);
  background:#060613;
  flex-shrink:0;
}

.sphdr-dark{
  background:#060613;
}

.sp-analyze-btn{
  padding:6px 12px;
  border:0.5px solid rgba(108,99,255,.38);
  background:rgba(108,99,255,.12);
  border-radius:8px;
  color:#A5B4FC;
  font-size:11px;
  font-weight:600;
  cursor:pointer;
  transition:all .15s;
  font-family:var(--font);
  display:flex;
  align-items:center;
  gap:5px;
}

.sp-analyze-btn:hover{
  background:rgba(108,99,255,.22);
  border-color:rgba(108,99,255,.55);
}

/* SCREEN PREVIEW AREA */
#sprev{
  flex:1;
  display:flex;
  flex-direction:column;
  background:#0a0a14;
  overflow:hidden;
}

#spIdle{
  flex:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:14px;
  padding:24px;
}

.sp-idle-icon{
  width:56px;
  height:56px;
  border-radius:16px;
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.08);
  display:flex;
  align-items:center;
  justify-content:center;
  color:rgba(255,255,255,.25);
}

.sp-idle-title{
  font-size:14px;
  font-weight:500;
  color:rgba(255,255,255,.55);
  text-align:center;
  margin:0;
}

.sp-idle-sub{
  font-size:11px;
  color:rgba(255,255,255,.28);
  text-align:center;
  line-height:1.65;
  max-width:220px;
  margin:0;
}

/* Video area */
#spVideoWrap{
  flex:1;
  position:relative;
  display:none;
  overflow:hidden;
}

#spVideoWrap.on{
  display:flex;
}

#spVideo{
  width:100%;
  height:100%;
  object-fit:contain;
  background:#000;
  display:block;
}

/* Frame badge */
#spFrameBadge{
  display:none;
  position:absolute;
  top:12px;
  left:12px;
  align-items:center;
  gap:5px;
  padding:4px 11px;
  border-radius:99px;
  background:rgba(0,0,0,.55);
  border:1px solid rgba(255,255,255,.14);
  backdrop-filter:blur(6px);
}

.sp-rec-dot{
  width:6px;
  height:6px;
  border-radius:50%;
  background:#EF4444;
  animation:livePulse2 1s infinite;
  flex-shrink:0;
}

#spFrameLabel{
  font-size:10px;
  font-weight:500;
  color:rgba(255,255,255,.85);
  font-family:var(--font);
  font-variant-numeric:tabular-nums;
}

/* Live pill */
.sp-live-badge{
  position:absolute;
  top:12px;
  right:12px;
  padding:4px 10px;
  border-radius:99px;
  background:rgba(108,99,255,.28);
  border:1px solid rgba(108,99,255,.45);
  font-size:10px;
  font-weight:600;
  color:#C4BFFF;
  font-family:var(--font);
  backdrop-filter:blur(6px);
  letter-spacing:.04em;
}

/* Analyzing bar */
#spAnalyzingBar{
  position:absolute;
  bottom:0;
  left:0;
  right:0;
  display:none;
  align-items:center;
  gap:8px;
  padding:9px 14px;
  background:linear-gradient(to top,rgba(0,0,0,.88),transparent);
}

#spAnalyzingBar.on{
  display:flex;
}

.sp-spin{
  width:13px;
  height:13px;
  border:2px solid rgba(108,99,255,.35);
  border-top-color:#818CF8;
  border-radius:50%;
  animation:spin .7s linear infinite;
  flex-shrink:0;
}

#spAnalyzingBar span{
  font-size:11px;
  color:rgba(255,255,255,.55);
  font-family:var(--font);
}

/* Analysis bubble */
#sbubble{
  position:absolute;
  bottom:48px;
  left:12px;
  right:12px;
  background:rgba(10,10,26,.94);
  border:1px solid rgba(108,99,255,.28);
  border-radius:14px;
  padding:13px 14px;
  display:none;
  backdrop-filter:blur(12px);
  box-shadow:0 4px 24px rgba(0,0,0,.5);
  animation:panelReveal .2s ease;
}

#sbubble.on{
  display:block;
}

/* Timestamp */
#spTimestamp{
  padding:5px 14px;
  text-align:center;
  font-size:10px;
  color:rgba(255,255,255,.2);
  font-family:var(--font);
  flex-shrink:0;
  background:#060613;
  letter-spacing:.02em;
}

/* Error */
#spError{
  position:absolute;
  inset:0;
  display:none;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:8px;
  background:rgba(0,0,0,.78);
  padding:24px;
  text-align:center;
}

#spError.on{
  display:flex;
}

#spErrorText{
  font-size:12px;
  color:#FCA5A5;
  line-height:1.6;
  margin:0;
}

/* MOBILE RESPONSIVE */
@media(max-width:768px){
  #splitView{
    flex-direction:column;
  }
  
  #splitScr,
  #miniCallPanel{
    width:100%;
    min-width:100%;
  }
  
  #splitScr{
    height:55%;
  }
  
  #miniCallPanel{
    height:45%;
  }
}
</style>

<?php
  $me   = auth()->user();
  $sugs = [
    ['🔌','Problème connexion','Mon service ne répond plus'],
    ['📤','SMS non délivrés','Ma campagne ne part pas'],
    ['🔑','Token API expiré','Mon token ne fonctionne plus'],
    ['💳','Question facturation','Je veux recharger mes crédits'],
  ];
?>

<div id="lkAudioSink" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden;pointer-events:none;"></div>

<div id="chatWrap">

  
  <div id="chatSide">
    <div class="side-top">
      <div class="side-logo" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <span class="side-name">L2T Chat</span>
      <button class="side-icon-btn" onclick="toggleSearch()" title="Rechercher" aria-label="Rechercher">
        <i class="ti ti-search"></i>
      </button>
      <button class="side-icon-btn" onclick="toggleSide()" title="Réduire" aria-label="Réduire le panneau">
        <i class="ti ti-layout-sidebar-left-collapse"></i>
      </button>
    </div>

    <button type="button" class="btn-new" onclick="newConv()">
      <i class="ti ti-edit"></i>
      Nouvelle discussion
    </button>

    <div id="sideSearch" style="display:none;padding:0 12px 10px;">
      <div style="display:flex;align-items:center;gap:7px;background:var(--bg3);border:0.5px solid var(--brd);border-radius:10px;padding:7px 11px;">
        <i class="ti ti-search" style="font-size:13px;color:var(--t4);"></i>
        <input id="sideSearchIn" type="text" placeholder="Rechercher…"
          style="flex:1;border:none;background:none;outline:none;font-size:13px;color:var(--t1);font-family:var(--font);"
          oninput="filterSide(this.value)">
        <button type="button" onclick="closeSearch()" style="background:none;border:none;cursor:pointer;color:var(--t4);padding:0;display:flex;flex-shrink:0;font-size:13px;">
          <i class="ti ti-x"></i>
        </button>
      </div>
    </div>

    <div class="side-section-label">Discussions récentes</div>
    <div class="side-list" id="sideList">
      <div class="ci active">
        <i class="ti ti-message"></i>
        <span>Bienvenue</span>
        <span class="ci-meta">maintenant</span>
      </div>
    </div>

    <div class="side-foot">
      <div class="side-av"><?php echo e(strtoupper(substr($me->name,0,1))); ?></div>
      <div>
        <div class="side-un"><?php echo e(explode(' ',trim($me->name))[0]); ?></div>
        <div class="side-ur">Support client</div>
      </div>
      <div class="online-dot" title="En ligne"></div>
    </div>
  </div>

  
  <div id="chatMain">
    <div id="chatTop">
      <div class="top-l">
        <button type="button" class="side-open-btn" onclick="toggleSide()" title="Afficher les discussions" aria-label="Ouvrir le panneau latéral">
          <i class="ti ti-menu-2" style="font-size:16px;"></i>
        </button>
        <div class="agent-av" id="topAv">L</div>
        <div>
          <div class="agent-name">L2T Assistant</div>
          <div class="agent-status" id="topSt">
            <div class="status-dot"></div>
            <span>En ligne</span>
          </div>
          <div class="wrow" id="topWv" style="display:none;">
            <div class="wb on" style="height:5px;animation-delay:0s;"></div>
            <div class="wb on" style="height:11px;animation-delay:.1s;"></div>
            <div class="wb on" style="height:7px;animation-delay:.2s;"></div>
            <div class="wb on" style="height:13px;animation-delay:.3s;"></div>
            <div class="wb on" style="height:5px;animation-delay:.4s;"></div>
          </div>
        </div>
      </div>
      <div class="top-r">
        <button class="ibtn" id="btnScr" onclick="startSplit()" title="Partager l'écran" aria-label="Partager l'écran">
          <i class="ti ti-device-desktop"></i>
        </button>
        <button class="ibtn callb" id="callBtn" onclick="toggleCall()" title="Démarrer un appel" aria-label="Démarrer / terminer un appel">
          <i class="ti ti-phone"></i>
        </button>
      </div>
    </div>

    <div id="shareBanner">
      <i class="ti ti-device-desktop" style="font-size:14px;"></i>
      Vous partagez votre écran — Vue côte à côte activée
      <button class="stop-share" onclick="closeSplit()">Arrêter</button>
    </div>

    <div id="chatMsgs" role="log" aria-live="polite">
      <div id="welcomeScr">
        <div class="wav" id="wAv"></div>
        <h1 class="wtit">Bonjour <?php echo e(explode(' ',trim($me->name))[0]); ?> 👋</h1>
        <p class="wsub">Je suis votre assistant L2T. Tapez votre question, utilisez le micro ou démarrez un appel.</p>
        <div class="sgrid">
          <?php $__currentLoopData = $sugs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <button class="sug" onclick="suggest('<?php echo e($s[2]); ?>')">
            <span class="si"><?php echo e($s[0]); ?></span>
            <span class="st"><?php echo e($s[1]); ?></span>
            <span class="sd"><?php echo e($s[2]); ?></span>
          </button>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    </div>

    
    <div id="screenPanel">
      <div class="spanel-hdr">
        <span class="spanel-tit">Analyse d'écran</span>
        <div style="display:flex;align-items:center;gap:6px;">
          <span class="spanel-badge" id="screenBadge" style="background:var(--p-soft);color:var(--p);border:0.5px solid var(--p-mid);">Gemini</span>
          <button class="spanel-close" onclick="closeScreenPanel()" aria-label="Fermer">
            <i class="ti ti-x"></i>
          </button>
        </div>
      </div>
      <div class="spanel-body">
        <p class="spanel-text" id="screenText">Analyse en cours…</p>
        <p class="spanel-meta" id="screenMeta"></p>
      </div>
    </div>

    <div id="filePrev">
      <span id="fpName" style="color:var(--t2);font-size:12px;font-family:var(--font);"></span>
      <button onclick="rmFile()" style="background:none;border:none;color:var(--t4);cursor:pointer;font-size:15px;" aria-label="Supprimer le fichier">✕</button>
    </div>

    <div id="inputWrap">
      <div id="inputBox">
        <div class="irow">
          <label class="inpbtn" style="cursor:pointer;" title="Joindre un fichier" aria-label="Joindre un fichier">
            <i class="ti ti-paperclip"></i>
            <input type="file" id="fileIn" style="display:none;" onchange="attachFile(this)">
          </label>
          <textarea id="msgIn" rows="1" placeholder="Tapez votre message ou utilisez le micro…"
            aria-label="Champ de message"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg();}"
            oninput="autoH(this)"></textarea>
          <button class="inpbtn" id="voiceBtn" title="Maintenir pour enregistrer" aria-label="Enregistrement vocal"
            onmousedown="startRec()" onmouseup="stopRec()" onmouseleave="cancelRec()">
            <i class="ti ti-microphone"></i>
          </button>
          <button class="inpsend" id="sendBtn" onclick="sendMsg()" aria-label="Envoyer">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><path d="M22 2L11 13M22 2L15 22 11 13M22 2L2 9l9 4"/></svg>
          </button>
        </div>
      </div>
      <div class="ihint">L2T Support IA · Conversations sauvegardées · Vocal transcrit en français automatiquement</div>
    </div>
  </div>

  
  <div id="callOv" role="dialog" aria-label="Appel en cours">

    
    <div id="callBg">
      <div class="call-blob call-blob-1"></div>
      <div class="call-blob call-blob-2"></div>
      <div class="call-blob call-blob-3"></div>
    </div>

    
    <div id="callError" role="alert"></div>

    
    <div class="call-header">
      <div class="call-header-left">
        <div class="call-live-pill">
          <div class="call-live-dot" id="callPls"></div>
          <span class="call-live-time" id="callTm">00:00</span>
        </div>
        <div id="callRecBadge">
          <div class="rec-dot-anim"></div>
          <span>REC</span>
        </div>
      </div>
      <div class="call-header-right">
        <div id="callRoleBadge" id="callRB"></div>
      </div>
    </div>

    
    <div class="call-stage">
      <div id="cavwrap">
        <div class="call-ring"></div>
        <div class="call-ring"></div>
        <div class="call-ring"></div>
        <div id="cav">L</div>
      </div>

      <div class="call-agent-info">
        <div id="cagn">L2T Chat</div>
        <div id="callStatus">Connexion en cours…</div>
        <div id="cwv"></div>
      </div>

      
      <div class="call-self-preview">
        <div class="call-self-av"><?php echo e(strtoupper(substr($me->name,0,1))); ?></div>
        <span class="call-self-name"><?php echo e(explode(' ',trim($me->name))[0]); ?></span>
        <span class="call-self-mic" id="selfMicIcon"><i class="ti ti-microphone" style="font-size:12px;"></i></span>
      </div>
    </div>

    
    <div id="callScrPanel">
      <div id="callScrBubble">
        <div id="callScrBubbleTitle">Live Analysis</div>
        <p id="callScrBubbleText"></p>
      </div>
      <div id="callScrVideoWrap">
        <video id="callScrVideo" autoplay muted playsinline></video>
        <div id="callScrBadge"><div class="cscr-dot"></div><span id="callScrFrameLabel">frame #1</span></div>
        <div id="callScrLivePill">LIVE</div>
        <div id="callScrAnalyzingBar"><div class="scr-spin"></div><span>Analyse Gemini…</span></div>
        <div id="callScrError"><div style="font-size:22px;">⚠️</div><p id="callScrErrorText">Le partage s'est terminé.</p></div>
      </div>
      <div id="callScrTimestamp"></div>
    </div>

    
    <div class="call-controls">
      
      <button class="cc" id="mutBtn" onclick="toggleMute()" title="Micro" aria-label="Muet">
        <div class="cc-btn"><i class="ti ti-microphone"></i></div>
        <span class="cc-label">Muet</span>
      </button>

      
      <button class="cc" id="recBtn" onclick="toggleCallRec()" title="Enregistrer" aria-label="Enregistrer l'appel">
        <div class="cc-btn"><i class="ti ti-circle"></i></div>
        <span class="cc-label">Enreg.</span>
      </button>

      <div class="ctrl-divider"></div>

      
      <div class="call-end-wrap">
        <button class="cend" onclick="endCall()" aria-label="Terminer l'appel">
          <i class="ti ti-phone-off" style="font-size:20px;"></i>
        </button>
        <span class="call-end-label">Raccrocher</span>
      </div>

      <div class="ctrl-divider"></div>

      
      <button class="cc" id="scrBtn" onclick="toggleCallSplit()" title="Partager écran" aria-label="Partager l'écran">
        <div class="cc-btn"><i class="ti ti-device-desktop"></i></div>
        <span class="cc-label">Écran</span>
      </button>
    </div>

  </div>

  
  <div id="splitView">

    
    <div id="splitScr">
      <div class="sphdr sphdr-dark">
        <div id="spRecDot" style="width:7px;height:7px;border-radius:50%;background:#4B5563;flex-shrink:0;transition:background .3s,box-shadow .3s;"></div>
        <span id="spHeaderLabel" style="font-weight:500;">Partage d'écran</span>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
          <span id="spEngineBadge" style="display:none;font-size:10px;font-weight:600;padding:3px 9px;border-radius:99px;background:rgba(108,99,255,.18);border:1px solid rgba(108,99,255,.38);color:#A5B4FC;letter-spacing:.03em;">Gemini</span>
          <button class="sp-analyze-btn" onclick="analyzeScreen()">
            <i class="ti ti-sparkles" style="font-size:11px;"></i>
            Analyser
          </button>
        </div>
      </div>
      <div id="sprev">
        <div id="spIdle">
          <div class="sp-idle-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <path d="M2 3h20a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM8 21h8M12 17v4"/>
            </svg>
          </div>
          <p class="sp-idle-title">Écran non partagé</p>
          <p class="sp-idle-sub">Cliquez sur Partager l'écran pour activer la vue en direct et l'analyse IA.</p>
        </div>
        <div id="spVideoWrap">
          <video id="spVideo" autoplay muted playsinline aria-label="Aperçu du partage d'écran"></video>
          <div id="spFrameBadge">
            <div class="sp-rec-dot"></div>
            <span id="spFrameLabel">frame #1</span>
          </div>
          <div class="sp-live-badge">LIVE</div>
          <div id="spAnalyzingBar"><div class="sp-spin"></div><span>Analyse Gemini en cours…</span></div>
          <div id="sbubble"></div>
          <div id="spError"><div style="font-size:24px;margin-bottom:4px;">⚠️</div><p id="spErrorText">Le partage d'écran s'est terminé.</p></div>
        </div>
        <div id="spTimestamp"></div>
      </div>
    </div>

    
    <div id="miniCallPanel">
      
      <div class="mini-call-top">
        <div class="mini-live">
          <div class="mini-live-dot"></div>
          <span id="miniCallTime">00:00</span>
        </div>
        <button class="mini-close" onclick="closeSplit()" aria-label="Fermer le partage">
          <i class="ti ti-x"></i>
        </button>
      </div>

      
      <div class="mini-call-center">
        <div class="mini-avatar-wrap">
          <div class="mini-ring"></div>
          <div class="mini-ring mini-ring-2"></div>
          <div id="miniAvatar" style="color:var(--p);"></div>
        </div>
        <div class="mini-agent-name" id="miniAgentName">L2T Support</div>
        <div class="mini-agent-status" id="miniAgentStatus">En ligne</div>
        <div class="mini-wave" id="miniWave" style="display:none;">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>

      
      <div class="mini-controls">
        <button class="mini-btn" id="miniMuteBtn" onclick="toggleMute()" aria-label="Couper le micro">
          <i class="ti ti-microphone"></i>
        </button>
        <button class="mini-btn" id="miniVideoBtn" onclick="toggleCallSplit()" aria-label="Caméra">
          <i class="ti ti-video"></i>
        </button>
        <button class="mini-btn end" onclick="endCall()" aria-label="Terminer l'appel">
          <i class="ti ti-phone-off"></i>
        </button>
      </div>
    </div>

  </div>

</div>

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var ME   = <?php echo json_encode($me->name, 15, 512) ?>;
var AG   = { id: 'l2t', name: 'L2T Chat', color: '#6C63FF', wave: '#8B85FF', role: 'Support Général' };

// ── STATE ─────────────────────────────────────────────────────────────────────
var S = {
  convId: null, loading: false, cnt: 0, sideOpen: true,
  isRec: false, recMedia: null, recChunks: [], recSecs: 0, recTimer: null,
  inCall: false, callTimer: null, callSecs: 0, callMuted: false,
  livekitRoom: null, livekitToken: null, livekitUrl: null,
  callRec: false, callRecMedia: null, callRecChunks: [], callRecStream: null,
  splitOpen: false, shareStream: null, speakTO: null, file: null,
  callScrActive: false,
  screenLoopActive: false, screenLoopTimer: null, screenFrameIndex: 0,
  screenSessionId: null,
  agentState: 'idle',
};

// ── REMOTE AUDIO MANAGEMENT ───────────────────────────────────────────────────
var _lkRemoteAudioEls = new Set();
function lkGetAudioSink() { return document.getElementById('lkAudioSink'); }
function lkAttachRemoteAudioTrack(track) {
  if (!track || track.kind !== 'audio') return;
  var audioEl = track.attach();
  audioEl.autoplay = true; audioEl.playsInline = true; audioEl.muted = false; audioEl.controls = false;
  lkGetAudioSink().appendChild(audioEl);
  _lkRemoteAudioEls.add(audioEl);
  var tryPlay = function() { if (typeof audioEl.play === 'function') audioEl.play().catch(function(){}); };
  if (audioEl.readyState >= 1) { tryPlay(); }
  else { audioEl.addEventListener('loadeddata', tryPlay, { once: true }); }
}
function lkDetachRemoteAudioTrack(track) {
  if (!track || typeof track.detach !== 'function') return;
  track.detach().forEach(function(el) {
    try { el.pause(); } catch(e) {}
    try { el.remove(); } catch(e) {}
    _lkRemoteAudioEls.delete(el);
  });
}
function lkClearRemoteAudio() {
  _lkRemoteAudioEls.forEach(function(el) {
    try { el.pause(); } catch(e) {}
    try { el.remove(); } catch(e) {}
  });
  _lkRemoteAudioEls.clear();
  lkGetAudioSink().innerHTML = '';
}

// ── AGENT STATE ───────────────────────────────────────────────────────────────
function setAgentState(state) {
  S.agentState = state;
  switch (state) {
    case 'speaking': setCallStatus('L\'agent répond…'); startWaveAnimation(); break;
    case 'thinking': setCallStatus('L\'agent réfléchit…'); stopWaveAnimation(); break;
    case 'listening': setCallStatus('L\'agent écoute'); stopWaveAnimation(); break;
    default: setCallStatus('En ligne'); stopWaveAnimation();
  }
}

// ── INIT ──────────────────────────────────────────────────────────────────────

// ── GLOBAL FLOATING CONV MENU ────────────────────────────────────────────────
(function() {
  var gm = document.createElement('div');
  gm.id = 'globalConvMenu';
  gm.style.cssText = [
    'display:none',
    'position:fixed',
    'background:var(--bg2)',
    'border:1px solid var(--brd)',
    'border-radius:10px',
    'box-shadow:0 6px 20px rgba(0,0,0,0.15)',
    'z-index:9999',
    'min-width:150px',
    'padding:5px 0',
    'font-family:var(--font)',
  ].join(';');
  document.body.appendChild(gm);
})();

var _activeConvMenuId = null;

function openConvMenu(id, btnEl) {
  _activeConvMenuId = id;
  var gm = document.getElementById('globalConvMenu');
    console.log('Opening conversation menu for ID:', id);
    gm.innerHTML =
    '<button class="conv-menu-item" onclick="renameConv(' + id + ')"><i class="ti ti-pencil"></i> Renommer</button>' +
    '<div style="height:0.5px;background:var(--brd);margin:4px 0;"></div>' +
    '<button class="conv-menu-item" style="color:#ef4444;" onclick="deleteConv(' + id + ')"><i class="ti ti-trash"></i> Supprimer</button>';

  var rect = btnEl.getBoundingClientRect();
  var menuW = 160;
  var left  = rect.right - menuW;
  if (left < 4) left = 4;
  var top = rect.bottom + 4;
  if (top + 90 > window.innerHeight) top = rect.top - 90;

  gm.style.left    = left + 'px';
  gm.style.top     = top  + 'px';
  gm.style.display = 'block';
}

function closeConvMenu() {
  var gm = document.getElementById('globalConvMenu');
  if (gm) gm.style.display = 'none';
  _activeConvMenuId = null;
}

document.addEventListener('click', function(e) {
  var gm = document.getElementById('globalConvMenu');
  if (gm && !gm.contains(e.target) && !e.target.closest('.ci-dots')) closeConvMenu();
});

// ── LOAD PAST CONVERSATIONS ───────────────────────────────────────────────────
function loadPastConversations() {
  fetch('/chat/conversations', {
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json' }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    var list = document.getElementById('sideList');
    if (!list || !data.conversations) return;
    list.innerHTML = '';
    data.conversations.forEach(function(conv) {
      var item = document.createElement('div');
      item.className = 'ci';
      item.dataset.id = conv.id;
      var d = conv.updated_at ? new Date(conv.updated_at).toLocaleDateString('fr-FR') : '';
      item.innerHTML = `
        <i class="ti ti-message"></i>
        <span>${conv.subject || 'Chat'}</span>
        <span class="ci-meta">${d}</span>
        <div class="ci-actions">
          <button class="ci-dots" onclick="event.stopPropagation(); openConvMenu(${conv.id}, this)" title="Options">
            <i class="ti ti-dots-vertical"></i>
          </button>
        </div>
      `;
      item.addEventListener('click', function() {
        loadConversation(conv.id);
      });
      list.appendChild(item);
    });
  })
  .catch(function(e) { console.warn('loadPastConversations error', e); });
}

// Menu action functions
function renameConv(id) {
  closeConvMenu();
  var ci = document.querySelector('.ci[data-id="' + id + '"]');
  if (!ci) return;
  var span = ci.querySelector('span');
  if (!span) return;
  var original = span.textContent.trim();
  var nextName = window.prompt('Renommer la discussion', original);
  if (nextName === null) return;
  nextName = nextName.trim();
  if (!nextName) {
    alert('Le nom ne peut pas être vide.');
    return;
  }
  if (nextName === original) return;

  fetch('/chat/conversations/' + id, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ subject: nextName })
  })
  .then(function(r) {
    return r.json().then(function(data) {
      if (!r.ok) throw (data || { message: 'Erreur lors du renommage.' });
      return data;
    });
  })
  .then(function() {
    span.textContent = nextName;
  })
  .catch(function(err) {
    alert(err.message || 'Erreur lors du renommage.');
  });
}

function deleteConv(id) {
  closeConvMenu();
  if (!confirm('Supprimer cette discussion ?')) return;
  fetch('/chat/conversations/' + id, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
  }).then(function() {
    var ci = document.querySelector('.ci[data-id="' + id + '"]');
    if (ci) ci.remove();
    var msgs = document.getElementById('chatMsgs');
    if (S.convId === id) {
      S.convId = null; S.cnt = 0;
      msgs.innerHTML = '';
      var ws = document.createElement('div'); ws.id = 'welcomeScr';
      ws.style.cssText = 'flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 22px;text-align:center;';
      ws.innerHTML = '<div class="wav" id="wAv"></div><h1 class="wtit">Discussion supprimée</h1><p class="wsub">Démarrez un nouveau chat.</p>';
      msgs.appendChild(ws); renderWelcome();
    }
  }).catch(function() { alert('Erreur lors de la suppression.'); });
}

function isOwnMessage(msg) {
  var currentUserId = String(<?php echo e(auth()->id()); ?>);
  var senderId = msg && (msg.sender_id ?? msg.user_id ?? msg.author_id);
  if (senderId !== undefined && senderId !== null) {
    return String(senderId) === currentUserId;
  }
  if (typeof msg?.role === 'string') return msg.role === 'user';
  if (typeof msg?.sender === 'string') return msg.sender === 'user';
  if (typeof msg?.type === 'string') return msg.type === 'user';
  return false;
}

function loadConversation(convId) {
  S.convId = convId;
  fetch('/chat/conversations/' + convId + '/messages', {
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json' }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    var msgs = document.getElementById('chatMsgs');
    msgs.innerHTML = '';
    var list = Array.isArray(data) ? data : (data.messages || []);
    list.forEach(function(msg) {
        var isUser = isOwnMessage(msg);
        var content = msg.content || msg.text || msg.message || '';
        if (isUser) appendU(content); else appendBot(content);
    });
    document.querySelectorAll('.ci').forEach(function(i) { i.classList.remove('active'); });
    var item = document.querySelector('.ci[data-id="' + convId + '"]');
    if (item) item.classList.add('active');
  })
  .catch(function(e) { console.warn('loadConversation error', e); });
}

document.addEventListener('DOMContentLoaded', function() {
  renderWelcome();
  initMiniCallPanel();
  loadPastConversations();
  loadPastConversations();
});

function initMiniCallPanel() {
  // Initialize mini call panel avatar and name
  var miniAvatar = document.getElementById('miniAvatar');
  if (miniAvatar) {
    miniAvatar.textContent = AG.name[0];
    miniAvatar.style.background = 'rgba(108,99,255,.15)';
    miniAvatar.style.borderColor = 'rgba(108,99,255,.45)';
    miniAvatar.style.color = '#A5B4FC';
  }
  
  var miniName = document.getElementById('miniAgentName');
  if (miniName) miniName.textContent = AG.name;
  
  var miniStatus = document.getElementById('miniAgentStatus');
  if (miniStatus) miniStatus.textContent = 'En ligne';
  
  var miniTime = document.getElementById('miniCallTime');
  if (miniTime) miniTime.textContent = '00:00';
}

function renderWelcome() {
  var el = document.getElementById('wAv');
  if (!el) return;
  el.style.cssText = 'background:var(--p-soft);border:1.5px solid var(--p-mid);color:var(--p);font-weight:500;font-size:24px;';
  el.textContent = AG.name[0];
}

// ── SIDEBAR ───────────────────────────────────────────────────────────────────
function toggleSide() {
  S.sideOpen = !S.sideOpen;
  document.getElementById('chatSide').classList.toggle('closed', !S.sideOpen);
}
function toggleSearch() {
  var box = document.getElementById('sideSearch');
  var isOpen = box.style.display !== 'none';
  box.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) setTimeout(function() { document.getElementById('sideSearchIn').focus(); }, 50);
}
function closeSearch() {
  document.getElementById('sideSearch').style.display = 'none';
  document.getElementById('sideSearchIn').value = '';
  filterSide('');
}
function filterSide(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#sideList .ci').forEach(function(el) {
    var txt = (el.querySelector('span') || {}).textContent || '';
    el.style.display = (!q || txt.toLowerCase().includes(q)) ? '' : 'none';
  });
}
function newConv() {
  S.convId = null; S.cnt = 0;
  var msgs = document.getElementById('chatMsgs'); msgs.innerHTML = '';
  var ws = document.createElement('div'); ws.id = 'welcomeScr';
  ws.style.cssText = 'flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 22px;text-align:center;';
  ws.innerHTML = '<div class="wav" id="wAv"></div><h1 class="wtit">Nouveau chat 💬</h1><p class="wsub">Comment puis-je vous aider ?</p>';
  msgs.appendChild(ws); renderWelcome();
  document.getElementById('msgIn').value = ''; autoH(document.getElementById('msgIn'));
  var list = document.getElementById('sideList');
  var item = document.createElement('div'); item.className = 'ci active';
  item.innerHTML = '<i class="ti ti-message"></i><span>Nouveau chat</span><span class="ci-meta">maintenant</span>';
  document.querySelectorAll('.ci').forEach(function(i) { i.classList.remove('active'); });
  list.insertBefore(item, list.firstChild);
}

// ── CHAT SEND ─────────────────────────────────────────────────────────────────
function sendMsg() {
  if (S.loading) return;
  var inp = document.getElementById('msgIn'), txt = inp.value.trim();
  if (!txt && !S.file) return;
  rmW();
  if (S.file) { appendUFile(S.file); S.file = null; document.getElementById('filePrev').classList.remove('on'); }
  else appendU(txt);
  inp.value = ''; autoH(inp);
  doSend(txt);
}
function suggest(t) { document.getElementById('msgIn').value = t; sendMsg(); }
function doSend(text) {
  showTyp(); S.loading = true; document.getElementById('sendBtn').disabled = true;
  S.cnt++;
  if (S.cnt === 1) addSide(text);
  fetch('/chat/send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ message: text, conversation_id: S.convId || undefined })
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    hideTyp();
    appendBot(d.response || d.message || 'Message reçu ✅');
    if (d.conversation_id && !S.convId) S.convId = d.conversation_id;
    showSpeak();
  })
  .catch(function() { hideTyp(); appendBot('⚠️ Service indisponible. Réessayez.'); })
  .finally(function() { S.loading = false; document.getElementById('sendBtn').disabled = false; });
}
function addSide(t) {
  var label = t.length > 25 ? t.substring(0, 25) + '…' : t;
  var list = document.getElementById('sideList');
  var item = document.createElement('div'); item.className = 'ci active';
  item.innerHTML = '<i class="ti ti-message"></i><span>' + esc(label) + '</span><span class="ci-meta">maintenant</span>';
  document.querySelectorAll('.ci').forEach(function(i) { i.classList.remove('active'); });
  list.prepend(item);
}
function addDateDivider() {
  var msgs = document.getElementById('chatMsgs');
  var d = document.createElement('div'); d.className = 'date-divider';
  d.innerHTML = '<span>Aujourd\'hui</span>';
  msgs.appendChild(d);
}
function rmW() {
  var w = document.getElementById('welcomeScr');
  if (w) { addDateDivider(); w.remove(); }
}
function appendU(txt, t) {
  var msgs = document.getElementById('chatMsgs');
  var d = document.createElement('div'); d.className = 'mrow';
  d.innerHTML = '<div class="ru"><div class="bbl">' + esc(txt) + '<div class="btime">' + (t || now()) + '</div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
  if (S.splitOpen) mirrorInSplit(d.cloneNode(true));
}
function appendUFile(file) {
  var msgs = document.getElementById('chatMsgs');
  var d = document.createElement('div'); d.className = 'mrow';
  d.innerHTML = '<div class="ru"><div class="bbl"><div style="display:flex;align-items:center;gap:7px;"><span style="font-size:17px;">📎</span><div><div style="font-size:13px;font-weight:500;">' + esc(file.name) + '</div><div style="font-size:11px;color:var(--t4);">' + (file.size/1024).toFixed(1) + ' KB</div></div></div><div class="btime">' + now() + '</div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
}
function appendBot(txt, t) {
  var msgs = document.getElementById('chatMsgs');
  var d = document.createElement('div'); d.className = 'mrow';
  d.innerHTML = '<div class="rb"><div class="bav">' + AG.name[0] + '</div><div><div class="bbl">' + fmt(txt) + '<div class="btime">' + (t ? fmtT(t) : now()) + '</div></div><div class="macts"><button class="mact" onclick="cpMsg(this)"><i class="ti ti-copy" style="font-size:11px;"></i> Copier</button></div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
  // Note: Old split chat view removed; new split view is screen+mini call panel
  // if (S.splitOpen) mirrorInSplit(d.cloneNode(true));
}
// Old mirrorInSplit function removed - new split view is screen+mini call panel only
function showTyp() {
  var msgs = document.getElementById('chatMsgs');
  var d = document.createElement('div'); d.className = 'mrow'; d.id = 'trow';
  d.innerHTML = '<div class="rb"><div class="bav">' + AG.name[0] + '</div><div class="bbl"><div class="tdots"><span></span><span></span><span></span></div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
}
function hideTyp() { var e = document.getElementById('trow'); if (e) e.remove(); }
function showSpeak() {
  document.getElementById('topWv').style.display = 'flex';
  document.getElementById('topSt').style.display = 'none';
  clearTimeout(S.speakTO);
  S.speakTO = setTimeout(function() {
    document.getElementById('topWv').style.display = 'none';
    document.getElementById('topSt').style.display = 'flex';
  }, 3500);
}
function cpMsg(btn) {
  var t = btn.closest('div').previousElementSibling.textContent;
  navigator.clipboard.writeText(t).then(function() {
    btn.innerHTML = '<i class="ti ti-check" style="font-size:11px;"></i> Copié';
    setTimeout(function() { btn.innerHTML = '<i class="ti ti-copy" style="font-size:11px;"></i> Copier'; }, 2000);
  });
}

// ── VOICE RECORDING ───────────────────────────────────────────────────────────
function startRec() {
  navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
    S.isRec = true; S.recChunks = []; S.recSecs = 0;
    document.getElementById('voiceBtn').classList.add('rec');
    S.recTimer = setInterval(function() { S.recSecs++; }, 1000);
    var preferredMime = getPreferredAudioMime();
    S.recMedia = preferredMime ? new MediaRecorder(stream, { mimeType: preferredMime }) : new MediaRecorder(stream);
    S.recMedia.ondataavailable = function(e) { if (e.data.size > 0) S.recChunks.push(e.data); };
    S.recMedia.onstop = function() {
      clearInterval(S.recTimer);
      stream.getTracks().forEach(function(t) { t.stop(); });
      S.isRec = false;
      document.getElementById('voiceBtn').classList.remove('rec');
      var mimeType = S.recMedia.mimeType || preferredMime || 'audio/webm';
      processVoiceBlob(new Blob(S.recChunks, { type: mimeType }), mimeType, S.recSecs);
    };
    S.recMedia.start();
  }).catch(function() { alert('Microphone non accessible'); });
}
function stopRec()   { if (S.recMedia && S.isRec) S.recMedia.stop(); }
function cancelRec() { if (S.recMedia && S.isRec) S.recMedia.stop(); }
function processVoiceBlob(blob, mimeType, durSec) {
  if (!blob || !blob.size) { appendBot('⚠️ Aucun audio capturé.'); return; }
  var ext = getAudioExt(mimeType);
  var file = new File([blob], 'voice-' + Date.now() + '.' + ext, { type: mimeType });
  rmW(); appendVoicePlaceholder(durSec);
  var fd = new FormData(); fd.append('file', file);
  window.supportBackendFetch('/voice/transcribe', { method: 'POST', body: fd })
    .then(function(d) {
      var transcript = (d.text || '').trim();
      if (!transcript) { appendBot('⚠️ Aucune parole détectée dans l\'enregistrement.'); return; }
      var ph = document.getElementById('voicePlaceholder'); if (ph) ph.remove();
      appendVoiceSent(durSec, transcript);
      doSend(transcript);
    })
    .catch(function() {
      var ph = document.getElementById('voicePlaceholder'); if (ph) ph.remove();
      appendBot('⚠️ Transcription impossible. Réessayez.');
    });
}
function appendVoicePlaceholder(durSec) {
  var msgs = document.getElementById('chatMsgs');
  var dur = '0:' + String(durSec || 1).padStart(2, '0');
  var d = document.createElement('div'); d.className = 'mrow'; d.id = 'voicePlaceholder';
  d.innerHTML = '<div class="ru"><div class="bbl"><div style="display:flex;align-items:center;gap:9px;"><span style="font-size:13px;color:var(--t4);">🎙️ Transcription… ' + dur + '</span></div><div class="btime">' + now() + '</div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
}
function appendVoiceSent(durSec, transcript) {
  var msgs = document.getElementById('chatMsgs');
  var dur = '0:' + String(durSec || 1).padStart(2, '0');
  var d = document.createElement('div'); d.className = 'mrow';
  d.innerHTML = '<div class="ru"><div class="bbl"><div style="display:flex;align-items:center;gap:9px;"><span style="font-size:13px;">▶ ' + dur + '</span></div><div style="display:inline-flex;align-items:center;gap:3px;margin-top:6px;padding:3px 7px;border-radius:6px;background:var(--p-soft);border:0.5px solid var(--p-mid);font-size:11px;color:var(--p);">✏️ Transcrit : ' + esc(transcript) + '</div><div class="btime">' + now() + '</div></div></div>';
  msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
}

// ── LIVE CALL (LiveKit) ───────────────────────────────────────────────────────
function toggleCall() { if (S.inCall) { endCall(); } else { startCall(); } }
function startCall() {
  setCallStatus('Connexion en cours…');
  showCallOverlay();
  window.supportBackendFetch('/voice-agents/support-call-token', { method: 'GET' })
    .then(function(data) {
      S.livekitToken = data.token;
      S.livekitUrl   = data.url;
      connectLiveKit(data.url, data.token);
    })
    .catch(function(err) { showCallError('Impossible de démarrer l\'appel : ' + err.message); });
}
function connectLiveKit(serverUrl, token) {
  if (typeof LivekitClient === 'undefined' && typeof window.LivekitClient === 'undefined') {
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
    script.onload = function() { doConnectLiveKit(serverUrl, token); };
    script.onerror = function() { showCallError('SDK LiveKit non disponible. Vérifiez votre connexion.'); };
    document.head.appendChild(script);
  } else {
    doConnectLiveKit(serverUrl, token);
  }
}
function doConnectLiveKit(serverUrl, token) {
  var LK = window.LivekitClient || window.livekit;
  if (!LK || !LK.Room) { showCallError('SDK LiveKit introuvable.'); return; }
  var room = new LK.Room({ adaptiveStream: true, dynacast: true });
  S.livekitRoom = room;
  room.on(LK.RoomEvent.Connected, function() {
    S.inCall = true;
    setCallStatus('Connecté — activation du micro…');
    setPulse(true); startCallTimer();
    document.getElementById('callBtn').classList.add('in-call');
    if (typeof room.startAudio === 'function') room.startAudio().catch(function(){});
    room.localParticipant.setMicrophoneEnabled(true)
      .then(function() { setCallStatus('En ligne'); setAgentState('idle'); })
      .catch(function(err) { showCallError('Micro inaccessible : ' + (err.message || err)); });
    room.remoteParticipants.forEach(function(participant) {
      participant.trackPublications.forEach(function(pub) {
        if (pub.track) lkAttachRemoteAudioTrack(pub.track);
      });
    });
  });
  room.on(LK.RoomEvent.TrackSubscribed, function(track) {
    lkAttachRemoteAudioTrack(track);
    setCallStatus('Support connecté');
    startWaveAnimation();
  });
  room.on(LK.RoomEvent.TrackUnsubscribed, function(track) { lkDetachRemoteAudioTrack(track); });
  room.on(LK.RoomEvent.ActiveSpeakersChanged, function(speakers) {
    var localId = room.localParticipant && room.localParticipant.identity;
    var agentSpeak = speakers.some(function(s) { return s.identity !== localId; });
    var userSpeak  = speakers.some(function(s) { return s.identity === localId; });
    if (agentSpeak) {
      startWaveAnimation();
      if (S.agentState !== 'thinking') setCallStatus('L\'agent répond…');
    } else if (userSpeak) {
      stopWaveAnimation();
      if (S.agentState === 'idle') setCallStatus('Vous parlez…');
    } else {
      if (S.agentState === 'idle' || S.agentState === 'listening') { stopWaveAnimation(); setCallStatus('En ligne'); }
    }
  });
  room.on(LK.RoomEvent.DataReceived, function(payload) {
    try {
      var text = new TextDecoder().decode(payload);
      var msg  = JSON.parse(text);
      if (msg && msg.type === 'state' && msg.state) setAgentState(msg.state);
      if (msg && msg.type === 'transcript' && msg.text) appendBot(msg.text);
    } catch(e) {}
  });
  room.on(LK.RoomEvent.ParticipantConnected, function(participant) {
    setCallStatus('Support connecté'); startWaveAnimation();
    participant.trackPublications.forEach(function(pub) {
      if (pub.track) lkAttachRemoteAudioTrack(pub.track);
    });
  });
  room.on(LK.RoomEvent.ParticipantDisconnected, function() {
    setCallStatus('En attente du support…'); stopWaveAnimation(); setAgentState('idle');
  });
  room.on(LK.RoomEvent.MediaDevicesError, function(err) {
    showCallError('Accès micro refusé : ' + (err.message || ''));
  });
  room.on(LK.RoomEvent.Disconnected, function() {
    lkClearRemoteAudio(); setAgentState('idle');
    if (S.inCall) endCall();
  });
  setCallStatus('Connexion LiveKit…');
  room.connect(serverUrl, token).catch(function(err) { showCallError('Échec de connexion : ' + (err.message || err)); });
}
function endCall() {
  var wasInCall = S.inCall;
  S.inCall = false;
  if (S.livekitRoom) { try { S.livekitRoom.disconnect(); } catch(e) {} S.livekitRoom = null; }
  clearInterval(S.callTimer); S.callTimer = null;
  S.callMuted = false;
  document.getElementById('mutBtn').classList.remove('on');
  document.getElementById('callBtn').classList.remove('in-call');
  stopWaveAnimation(); setAgentState('idle'); lkClearRemoteAudio();
  if (S.callRec) stopCallRec();
  if (S.callScrActive) stopCallScreenShare();
  var dur = document.getElementById('callTm').textContent;
  hideCallOverlay();
  if (wasInCall) appendBot('[Appel terminé — ' + dur + ' avec ' + AG.name + ']');
}
function showCallOverlay() {
  var ov = document.getElementById('callOv'); ov.classList.add('on');
  // Update blob colors to match agent color
  document.querySelector('.call-blob-1').style.background = 'radial-gradient(circle,' + AG.color + ',' + AG.color + 'CC)';
  document.querySelector('.call-blob-2').style.background = 'radial-gradient(circle,' + AG.wave + ',' + AG.color + ')';
  var av = document.getElementById('cav');
  av.style.cssText = [
    'background:color-mix(in srgb,' + AG.color + ' 14%,rgba(255,255,255,.04))',
    'border:2px solid ' + AG.color + '55',
    'color:' + AG.color,
    'box-shadow:0 0 0 1px ' + AG.color + '22,0 16px 64px ' + AG.color + '30',
    'transition:all .5s cubic-bezier(.4,0,.2,1)',
    'font-family:var(--font)',
    'width:112px;height:112px;border-radius:50%',
    'display:flex;align-items:center;justify-content:center',
    'font-size:38px;font-weight:600;position:relative;z-index:2;letter-spacing:-.01em'
  ].join(';');
  av.textContent = AG.name[0];
  document.getElementById('cagn').textContent = AG.name;
  // Update rings color
  document.querySelectorAll('.call-ring').forEach(function(r) {
    r.style.borderColor = AG.color + '22';
  });
  // Role badge
  var rb = document.getElementById('callRoleBadge');
  if (rb) {
    rb.textContent = AG.role;
    rb.style.cssText = 'padding:5px 12px;border-radius:99px;font-size:11px;font-weight:600;letter-spacing:.03em;backdrop-filter:blur(8px);background:' + AG.color + '1C;border:1px solid ' + AG.color + '33;color:' + AG.color + ';';
  }
  hideCallError();
}
function hideCallOverlay() { document.getElementById('callOv').classList.remove('on'); }
function setCallStatus(msg) {
  var el = document.getElementById('callStatus');
  if (el) el.textContent = msg;
  // Also update mini call panel
  var miniEl = document.getElementById('miniAgentStatus');
  if (miniEl) miniEl.textContent = msg;
}
function showCallError(msg) {
  var el = document.getElementById('callError'); el.textContent = msg; el.classList.add('on');
  setTimeout(function() { el.classList.remove('on'); }, 6000);
  setCallStatus('Erreur');
}
function hideCallError() { document.getElementById('callError').classList.remove('on'); }
function setPulse(active) {
  var p = document.getElementById('callPls');
  if (active) { p.classList.add('active'); }
  else         { p.classList.remove('active'); }
}
function startCallTimer() {
  S.callSecs = 0;
  S.callTimer = setInterval(function() {
    S.callSecs++;
    var timeStr = String(Math.floor(S.callSecs/60)).padStart(2,'0') + ':' + String(S.callSecs%60).padStart(2,'0');
    document.getElementById('callTm').textContent = timeStr;
    // Also update mini call panel
    var miniEl = document.getElementById('miniCallTime');
    if (miniEl) miniEl.textContent = timeStr;
  }, 1000);
}
function startWaveAnimation() {
  var wv = document.getElementById('cwv'); wv.innerHTML = '';
  for (var i = 0; i < 11; i++) {
    var b = document.createElement('div'); b.className = 'cwb';
    var h = Math.sin(i/11*Math.PI)*22+6;
    b.style.cssText = [
      'background:' + AG.color,
      'opacity:.75',
      'height:' + h + 'px',
      'animation:wv ' + (0.55 + i*.07) + 's ease-in-out infinite alternate',
      'animation-delay:' + (i*.06) + 's'
    ].join(';');
    wv.appendChild(b);
  }
  // Also show mini wave animation
  var miniWave = document.getElementById('miniWave');
  if (miniWave) miniWave.style.display = 'flex';
}

function stopWaveAnimation() {
  document.getElementById('cwv').innerHTML = '';
  // Also hide mini wave animation
  var miniWave = document.getElementById('miniWave');
  if (miniWave) miniWave.style.display = 'none';
}
function toggleMute() {
  S.callMuted = !S.callMuted;
  document.getElementById('mutBtn').classList.toggle('on', S.callMuted);
  // Also toggle mini mute button
  var miniMuteBtn = document.getElementById('miniMuteBtn');
  if (miniMuteBtn) miniMuteBtn.classList.toggle('on', S.callMuted);
  
  // Update self mic icon
  var mic = document.getElementById('selfMicIcon');
  if (mic) {
    mic.classList.toggle('muted', S.callMuted);
    mic.innerHTML = S.callMuted
      ? '<i class="ti ti-microphone-off" style="font-size:12px;"></i>'
      : '<i class="ti ti-microphone" style="font-size:12px;"></i>';
  }
  if (S.livekitRoom) S.livekitRoom.localParticipant.setMicrophoneEnabled(!S.callMuted);
}

// ── CALL RECORDING ────────────────────────────────────────────────────────────
function toggleCallRec() { if (!S.callRec) startCallRec(); else stopCallRec(); }
function startCallRec() {
  navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
    S.callRec = true; S.callRecChunks = []; S.callRecStream = stream;
    document.getElementById('recBtn').classList.add('rec-on');
    document.getElementById('callRecBadge').classList.add('on');
    var preferredMime = getPreferredAudioMime();
    S.callRecMedia = preferredMime ? new MediaRecorder(stream, { mimeType: preferredMime }) : new MediaRecorder(stream);
    S.callRecMedia.ondataavailable = function(e) { if (e.data.size > 0) S.callRecChunks.push(e.data); };
    S.callRecMedia.onstop = function() {
      stream.getTracks().forEach(function(t) { t.stop(); });
      S.callRec = false; S.callRecStream = null;
      document.getElementById('recBtn').classList.remove('rec-on');
      document.getElementById('callRecBadge').classList.remove('on');
      var mimeType = S.callRecMedia.mimeType || preferredMime || 'audio/webm';
      var blob = new Blob(S.callRecChunks, { type: mimeType });
      if (blob.size > 0) transcribeCallRecording(blob, mimeType);
      else appendBot('📝 Enregistrement vide — aucune transcription.');
    };
    S.callRecMedia.start();
  }).catch(function() { alert('Microphone non accessible'); });
}
function stopCallRec() { if (S.callRecMedia && S.callRec) S.callRecMedia.stop(); }
function transcribeCallRecording(blob, mimeType) {
  var ext  = getAudioExt(mimeType);
  var file = new File([blob], 'call-rec-' + Date.now() + '.' + ext, { type: mimeType });
  var fd   = new FormData(); fd.append('file', file);
  appendBot('📝 Transcription de l\'appel en cours…');
  window.supportBackendFetch('/voice/transcribe', { method: 'POST', body: fd })
    .then(function(d) {
      var text = (d.text || '').trim();
      appendBot(text ? '📝 Transcription de l\'appel :\n' + text : '📝 Aucune parole détectée dans l\'enregistrement.');
    })
    .catch(function() { appendBot('⚠️ Transcription de l\'appel impossible.'); });
}

// ── SCREEN SHARING + ANALYSIS ──────────────────────────────────────────────────
var SCREEN_TARGET_FPS    = 2;
var SCREEN_MAX_FPS       = 5;
var SCREEN_LOOP_DELAY_MS = 1250;
var SCREEN_READY_TIMEOUT = 1200;

function startSplit() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) { alert('Partage d\'écran non supporté'); return; }
  navigator.mediaDevices.getDisplayMedia({ video: { frameRate: { ideal: SCREEN_TARGET_FPS, max: SCREEN_MAX_FPS } }, audio: false })
    .then(function(stream) {
      S.shareStream = stream;
      var vt = stream.getVideoTracks()[0];
      if (vt) {
        vt.onended = function() {
          document.getElementById('spError').classList.add('on');
          document.getElementById('spErrorText').textContent = 'Le partage d\'écran s\'est terminé. Cliquez sur Partager pour reprendre.';
          closeSplit();
        };
        try { vt.applyConstraints({ frameRate: SCREEN_TARGET_FPS }); } catch(e) {}
      }
      document.getElementById('btnScr').classList.add('active');
      document.getElementById('shareBanner').classList.add('on');
      openSplit();
      startScreenSharingLoop(stream);
    })
    .catch(function() {});
}
function startScreenSharingLoop(stream) {
  S.screenLoopActive = true; S.screenFrameIndex = 0;
  S.screenSessionId = createScreenSessionId();
  clearScreenLoopTimeout();
  var scheduleNext = function(run) {
    clearScreenLoopTimeout();
    if (!S.screenLoopActive) return;
    S.screenLoopTimer = setTimeout(function() { run(); }, SCREEN_LOOP_DELAY_MS);
  };
  var runIteration = function() {
    if (!S.screenLoopActive) return;
    var tracks = stream.getVideoTracks();
    if (!tracks.length || tracks.every(function(t) { return t.readyState !== 'live'; })) { closeSplit(); return; }
    S.screenFrameIndex++;
    var frameIdx = S.screenFrameIndex;
    captureScreenFrame(stream)
      .then(function(frameFile) {
        if (!S.screenLoopActive) return;
        if (!frameFile) { scheduleNext(runIteration); return; }
        setScreenPanelAnalyzing(frameIdx);
        return uploadScreenFrames([frameFile], frameIdx);
      })
      .then(function(result) {
        if (!S.screenLoopActive || !result) return;
        displayScreenAnalysis(result, frameIdx);
        scheduleNext(runIteration);
      })
      .catch(function(err) {
        if (!S.screenLoopActive) return;
        setScreenPanelError(String(err));
        scheduleNext(runIteration);
      });
  };
  runIteration();
}
function stopScreenSharingLoop() { S.screenLoopActive = false; clearScreenLoopTimeout(); }
function clearScreenLoopTimeout() {
  if (S.screenLoopTimer !== null) { clearTimeout(S.screenLoopTimer); S.screenLoopTimer = null; }
}
function captureScreenFrame(stream) {
  return new Promise(function(resolve) {
    var video = document.createElement('video');
    video.srcObject = stream; video.autoplay = true; video.muted = true; video.playsInline = true;
    var playP = video.play();
    var afterPlay = function() {
      if (!video.videoWidth || !video.videoHeight || video.readyState < 2) {
        var timer = setTimeout(function() { video.removeEventListener('loadeddata', onReady); doCapture(); }, SCREEN_READY_TIMEOUT);
        var onReady = function() { clearTimeout(timer); doCapture(); };
        video.addEventListener('loadeddata', onReady, { once: true });
      } else { doCapture(); }
    };
    var doCapture = function() {
      var w = video.videoWidth || 1280, h = video.videoHeight || 720;
      var canvas = document.createElement('canvas'); canvas.width = w; canvas.height = h;
      var ctx = canvas.getContext('2d');
      if (!ctx) { video.pause(); video.srcObject = null; resolve(null); return; }
      ctx.drawImage(video, 0, 0, w, h);
      canvas.toBlob(function(blob) {
        video.pause(); video.srcObject = null;
        if (!blob) { resolve(null); return; }
        resolve(new File([blob], 'screenshare-frame-' + Date.now() + '.png', { type: 'image/png' }));
      }, 'image/png');
    };
    if (playP && typeof playP.then === 'function') { playP.catch(function(){}).then(afterPlay); }
    else { afterPlay(); }
  });
}
function uploadScreenFrames(frameFiles, frameNumber) {
  var fd = new FormData();
  frameFiles.forEach(function(f) { fd.append('frames', f); });
  fd.append('consent', 'true');
  fd.append('source_fps', String(SCREEN_TARGET_FPS));
  fd.append('target_fps', String(SCREEN_TARGET_FPS));
  fd.append('frame_number', String(frameNumber));
  fd.append('use_gemini_embeddings', 'true');
  if (S.convId) fd.append('support_call_room_name', 'chat-' + S.convId);
  return window.supportBackendFetch('/visual-ai/screenshare/assist', { method: 'POST', body: fd });
}
function displayScreenAnalysis(result, frameNumber) {
  var caption   = (result.final_frame && result.final_frame.caption   || '').trim();
  var ocrText   = (result.final_frame && result.final_frame.ocr_text_preview || '').trim();
  var hints     = result.assistance_hints || [];
  var firstHint = hints.find(function(h) {
    var n = (h || '').trim().toLowerCase();
    return n && !n.startsWith('processed ') && !n.startsWith('average ui transition');
  }) || '';
  firstHint = firstHint.replace(/^visible text:\s*/i,'').replace(/^ui cues:\s*/i,'').trim();
  var text = ocrText || firstHint || caption || 'Écran analysé — aucun contenu lisible détecté.';

  document.getElementById('spAnalyzingBar').classList.remove('on');
  var bbl = document.getElementById('sbubble');
  bbl.innerHTML =
    '<div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">' +
      '<div style="width:20px;height:20px;border-radius:50%;background:rgba(108,99,255,.2);border:1px solid rgba(108,99,255,.4);color:#A5B4FC;font-size:10px;font-weight:600;display:flex;align-items:center;justify-content:center;">' + AG.name[0] + '</div>' +
      '<span style="color:rgba(165,180,252,.7);font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">Analyse IA</span>' +
      '<span style="margin-left:auto;font-size:10px;color:rgba(255,255,255,.2);">frame #' + frameNumber + '</span>' +
    '</div>' +
    '<p style="color:rgba(209,213,219,.88);font-size:11.5px;line-height:1.6;margin:0;">' + esc(text) + '</p>';
  bbl.classList.add('on');

  var lbl = document.getElementById('spFrameLabel'); if (lbl) lbl.textContent = 'frame #' + frameNumber;
  var ts = new Date().toLocaleTimeString('fr-FR', { hour:'2-digit',minute:'2-digit',second:'2-digit' });
  document.getElementById('spTimestamp').textContent = 'Actualisé à ' + ts;
  document.getElementById('screenText').textContent = text;
  document.getElementById('screenMeta').textContent = 'Frame #' + frameNumber + ' · ' + ts;
  document.getElementById('screenPanel').classList.add('on');
}
function setScreenPanelAnalyzing(frameNumber) {
  document.getElementById('spAnalyzingBar').classList.add('on');
  var lbl = document.getElementById('spFrameLabel'); if (lbl) lbl.textContent = 'frame #' + frameNumber;
  document.getElementById('screenText').textContent = 'Analyse de la frame #' + frameNumber + '…';
  document.getElementById('screenPanel').classList.add('on');
}
function setScreenPanelError(msg) {
  document.getElementById('spAnalyzingBar').classList.remove('on');
  document.getElementById('screenText').textContent = '⚠️ ' + msg;
}
function closeScreenPanel() { document.getElementById('screenPanel').classList.remove('on'); }
function createScreenSessionId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') return crypto.randomUUID();
  return 'screen-' + Date.now() + '-' + Math.random().toString(36).slice(2,8);
}

// ── SPLIT VIEW ────────────────────────────────────────────────────────────────
function openSplit() {
  S.splitOpen = true;
  var sv = document.getElementById('splitView'); sv.classList.add('on');
  // Initialize mini call panel (screen sharing + call interface)
  attachStreamToVideo(S.shareStream);
}
function attachStreamToVideo(stream) {
  var video    = document.getElementById('spVideo');
  var wrap     = document.getElementById('spVideoWrap');
  var idle     = document.getElementById('spIdle');
  var badge    = document.getElementById('spFrameBadge');
  var engBadge = document.getElementById('spEngineBadge');
  var recDot   = document.getElementById('spRecDot');
  if (stream) {
    video.srcObject = stream; video.play().catch(function(){});
    wrap.classList.add('on'); idle.style.display = 'none'; badge.style.display = 'flex';
    engBadge.style.display = 'inline-flex';
    recDot.style.background = '#EF4444';
    recDot.style.boxShadow = '0 0 0 3px rgba(239,68,68,.2)';
    document.getElementById('spHeaderLabel').textContent = 'Partage en direct';
  } else {
    video.srcObject = null; wrap.classList.remove('on'); idle.style.display = 'flex';
    badge.style.display = 'none'; engBadge.style.display = 'none';
    recDot.style.background = '#4B5563'; recDot.style.boxShadow = 'none';
    document.getElementById('spHeaderLabel').textContent = 'Partage d\'écran';
    document.getElementById('sbubble').classList.remove('on');
    document.getElementById('spTimestamp').textContent = '';
    document.getElementById('spError').classList.remove('on');
    document.getElementById('spAnalyzingBar').classList.remove('on');
  }
}
function closeSplit() {
  S.splitOpen = false;
  stopScreenSharingLoop();
  attachStreamToVideo(null);
  document.getElementById('splitView').classList.remove('on');
  document.getElementById('btnScr').classList.remove('active');
  document.getElementById('shareBanner').classList.remove('on');
  document.getElementById('screenPanel').classList.remove('on');
  document.getElementById('scrBtn').classList.remove('scr-on');
  if (S.shareStream) { S.shareStream.getTracks().forEach(function(t) { t.stop(); }); S.shareStream = null; }
  if (S.inCall) showCallOverlay();
}
function analyzeScreen() {
  if (!S.shareStream) { alert('Activez d\'abord le partage d\'écran.'); return; }
  S.screenFrameIndex++;
  var frameIdx = S.screenFrameIndex;
  setScreenPanelAnalyzing(frameIdx);
  captureScreenFrame(S.shareStream)
    .then(function(f) { return f ? uploadScreenFrames([f], frameIdx) : Promise.reject(new Error('Capture vide')); })
    .then(function(r) {
      displayScreenAnalysis(r, frameIdx);
      var txt = document.getElementById('sbubble').querySelector('p');
      appendBot('📺 Analyse écran — ' + (txt ? txt.textContent : ''));
    })
    .catch(function(e) { setScreenPanelError(e.message); appendBot('⚠️ Analyse écran échouée : ' + e.message); });
}
function sendSplitMsg() {
  var inp = document.getElementById('spinIn'), txt = inp.value.trim(); if (!txt) return;
  inp.value = ''; autoH(inp); appendU(txt); doSend(txt);
}

// ── IN-CALL SCREEN SHARE ───────────────────────────────────────────────────────
function toggleCallSplit() {
  if (S.splitOpen && S.shareStream) { closeSplit(); showCallOverlay(); document.getElementById('scrBtn').classList.remove('scr-on'); return; }
  if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) { showCallError('Partage d\'écran non supporté.'); return; }
  var scrBtn = document.getElementById('scrBtn');
  scrBtn.disabled = true; scrBtn.style.opacity = '0.55';
  var scrLabel = scrBtn.querySelector('.cc-label'); if (scrLabel) scrLabel.textContent = '…';
  navigator.mediaDevices.getDisplayMedia({ video: { frameRate: { ideal: SCREEN_TARGET_FPS, max: SCREEN_MAX_FPS } }, audio: false })
    .then(function(stream) {
      S.shareStream = stream;
      var vt = stream.getVideoTracks()[0];
      if (vt) {
        vt.onended = function() {
          document.getElementById('spError').classList.add('on');
          document.getElementById('spErrorText').textContent = 'Le partage d\'écran s\'est terminé.';
          closeSplit(); if (S.inCall) showCallOverlay(); document.getElementById('scrBtn').classList.remove('scr-on');
        };
        try { vt.applyConstraints({ frameRate: SCREEN_TARGET_FPS }); } catch(e) {}
      }
      scrBtn.disabled = false; scrBtn.style.opacity = ''; if (scrLabel) scrLabel.textContent = 'Écran';
      scrBtn.classList.add('scr-on');
      document.getElementById('btnScr').classList.add('active');
      document.getElementById('shareBanner').classList.add('on');
      hideCallOverlay();
      openSplit();
      startScreenSharingLoop(stream);
    })
    .catch(function(err) {
      scrBtn.disabled = false; scrBtn.style.opacity = ''; if (scrLabel) scrLabel.textContent = 'Écran';
      var msg = err && err.name === 'NotAllowedError' ? 'Permission refusée pour le partage d\'écran.' : 'Partage d\'écran annulé.';
      showCallError(msg);
    });
}

// ── FILES ──────────────────────────────────────────────────────────────────────
function attachFile(inp) {
  if (!inp.files[0]) return;
  S.file = inp.files[0];
  document.getElementById('filePrev').classList.add('on');
  document.getElementById('fpName').textContent = '📎 ' + inp.files[0].name;
}
function rmFile() { S.file = null; document.getElementById('filePrev').classList.remove('on'); document.getElementById('fileIn').value = ''; }

// ── AUDIO HELPERS ──────────────────────────────────────────────────────────────
function getPreferredAudioMime() {
  if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) return '';
  var candidates = ['audio/webm;codecs=opus','audio/webm','audio/ogg;codecs=opus','audio/mp4'];
  return candidates.find(function(c) { return MediaRecorder.isTypeSupported(c); }) || '';
}
function getAudioExt(mimeType) {
  if (!mimeType) return 'webm';
  if (mimeType.includes('ogg')) return 'ogg';
  if (mimeType.includes('mp4') || mimeType.includes('m4a')) return 'm4a';
  return 'webm';
}

// ── GENERIC HELPERS ────────────────────────────────────────────────────────────
function autoH(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 130) + 'px'; }
function esc(t) { return String(t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>'); }
function fmt(t) { var s = esc(t); s = s.replace(/```([\s\S]*?)```/g,'<pre><code>$1</code></pre>'); s = s.replace(/`([^`\n]+)`/g,'<code>$1</code>'); s = s.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>'); return s; }
function now() { return new Date().toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}); }
function fmtT(dt) { return new Date(dt).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}); }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { if (S.inCall) endCall(); else if (S.splitOpen) closeSplit(); } });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/client/chat.blade.php ENDPATH**/ ?>