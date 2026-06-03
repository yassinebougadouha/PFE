<?php $__env->startSection('title', 'Moteur de Décisions - L2T Support'); ?>

<?php $__env->startSection('content'); ?>
<style>
.de-page {
  --de-bg: #f8fafc;
  --de-panel: #ffffff;
  --de-panel-hover: #fdfefe;
  --de-border: rgba(226, 232, 240, 0.8);
  --de-text-primary: #0f172a;
  --de-text-secondary: #475569;
  --de-text-muted: #94a3b8;
  --de-primary: #4f46e5;
  --de-primary-gradient: linear-gradient(135deg, #6366f1, #4f46e5);
  --de-success: #10b981;
  --de-warning: #f59e0b;
  --de-danger: #f43f5e;
  --de-shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
  --de-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
  --de-shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02);
  --de-shadow-premium: 0 12px 30px -5px rgba(79, 70, 229, 0.05);
  
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

[data-bs-theme="dark"] .de-page {
  --de-bg: #090d16;
  --de-panel: #111827;
  --de-panel-hover: #1f2937;
  --de-border: rgba(55, 65, 81, 0.5);
  --de-text-primary: #f3f4f6;
  --de-text-secondary: #9ca3af;
  --de-text-muted: #6b7280;
  --de-primary: #6366f1;
  --de-primary-gradient: linear-gradient(135deg, #818cf8, #6366f1);
  --de-shadow-premium: 0 12px 30px -5px rgba(99, 102, 241, 0.08);
}

/* ── Header ── */
.de-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.de-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.de-title {
  font-size: 32px;
  font-weight: 850;
  color: var(--de-text-primary);
  margin: 0;
  letter-spacing: -0.03em;
  background: var(--de-primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.de-sub {
  font-size: 14px;
  color: var(--de-text-secondary);
  font-weight: 500;
}

/* ── KPIs ── */
.de-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}
@media(max-width: 980px) {
  .de-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media(max-width: 540px) {
  .de-kpis { grid-template-columns: 1fr; }
}
.de-kpi {
  background: var(--de-panel);
  border: 1px solid var(--de-border);
  border-radius: 16px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: var(--de-shadow-sm);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}
.de-kpi::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: var(--de-primary);
  opacity: 0.8;
}
.de-kpi:hover {
  transform: translateY(-4px);
  box-shadow: var(--de-shadow-lg);
  border-color: var(--de-primary);
}
.de-kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.kpi-total { border-left-color: #8b5cf6; }
.kpi-total .de-kpi-icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.kpi-total::before { background: #8b5cf6; }

.kpi-auto { border-left-color: #10b981; }
.kpi-auto .de-kpi-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.kpi-auto::before { background: #10b981; }

.kpi-esc { border-left-color: #f43f5e; }
.kpi-esc .de-kpi-icon { background: rgba(244, 63, 94, 0.1); color: #f43f5e; }
.kpi-esc::before { background: #f43f5e; }

.kpi-rate { border-left-color: #f59e0b; }
.kpi-rate .de-kpi-icon { background: rgba(245, 158, 17, 0.1); color: #f59e0b; }
.kpi-rate::before { background: #f59e0b; }

.de-kpi-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.de-kpi-content span {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--de-text-muted);
}
.de-kpi-content strong {
  font-size: 32px;
  color: var(--de-text-primary);
  font-family: 'JetBrains Mono', Consolas, monospace;
  font-weight: 800;
  line-height: 1;
}

/* ── Tab Switcher ── */
.de-tabs-wrapper {
  display: flex;
  justify-content: flex-start;
}
.de-tabs {
  display: inline-flex;
  gap: 6px;
  padding: 6px;
  background: rgba(226, 232, 240, 0.6);
  border-radius: 16px;
  border: 1px solid var(--de-border);
}
[data-bs-theme="dark"] .de-tabs {
  background: rgba(17, 24, 39, 0.6);
}
.de-tab {
  border: 1px solid transparent;
  background: transparent;
  color: var(--de-text-secondary);
  padding: 10px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.de-tab:hover {
  color: var(--de-text-primary);
}
.de-tab.active {
  background: var(--de-panel);
  border-color: var(--de-border);
  color: var(--de-primary);
  box-shadow: var(--de-shadow-md);
  transform: scale(1.02);
}

/* ── Cards & Panes ── */
.de-pane {
  display: none;
  flex-direction: column;
  gap: 20px;
}
.de-pane.active {
  display: flex;
  animation: fadeIn 0.35s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

.de-card {
  border: 1px solid var(--de-border);
  background: var(--de-panel);
  border-radius: 20px;
  padding: 28px;
  box-shadow: var(--de-shadow-premium);
  transition: all 0.3s ease;
}
.de-card.highlight {
  border-left: 5px solid var(--de-primary);
}

.de-grid {
  display: grid;
  gap: 20px;
}
.de-grid.two {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media(max-width: 820px) {
  .de-grid.two { grid-template-columns: 1fr; }
}

.de-card h3 {
  font-size: 18px;
  font-weight: 800;
  color: var(--de-text-primary);
  margin: 0 0 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.02em;
}
.de-card h3 i {
  color: var(--de-primary);
  font-size: 22px;
}

/* ── Form Fields ── */
.de-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.de-label {
  display: block;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--de-text-muted);
}
.de-input, .de-text {
  width: 100%;
  border: 2px solid var(--de-border);
  background: var(--de-bg);
  color: var(--de-text-primary);
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  outline: none;
  transition: all 0.2s ease;
}
.de-text {
  min-height: 120px;
  resize: vertical;
}
.de-input:focus, .de-text:focus {
  border-color: var(--de-primary);
  background: var(--de-panel);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--de-primary) 12%, transparent);
}

/* ── Actions & Buttons ── */
.de-actions {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}
.de-btn {
  border: 2px solid var(--de-border);
  background: var(--de-panel);
  color: var(--de-text-primary);
  border-radius: 12px;
  padding: 12px 20px;
  font-size: 13px;
  font-weight: 750;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  box-shadow: var(--de-shadow-sm);
}
.de-btn:hover:not(:disabled) {
  border-color: var(--de-primary);
  color: var(--de-primary);
  transform: translateY(-1px);
  box-shadow: var(--de-shadow-md);
}
.de-btn.primary {
  border-color: transparent;
  background: var(--de-primary-gradient);
  color: #fff;
}
.de-btn.primary:hover:not(:disabled) {
  opacity: 0.95;
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.25);
  color: #fff;
}
.de-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Badges & Cells ── */
.de-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}
.de-badge {
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid var(--de-border);
  background: var(--de-bg);
  color: var(--de-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  transition: all 0.2s ease;
}
.de-badge.outcome-badge {
  background: rgba(79, 70, 229, 0.1);
  color: var(--de-primary);
  border-color: rgba(79, 70, 229, 0.2);
}

.de-result-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
}
@media(max-width: 800px) {
  .de-result-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media(max-width: 520px) {
  .de-result-grid { grid-template-columns: 1fr; }
}
.de-cell {
  border: 1.5px solid var(--de-border);
  background: var(--de-bg);
  border-radius: 14px;
  padding: 16px;
  transition: all 0.2s ease;
}
.de-cell:hover {
  border-color: var(--de-primary);
  background: var(--de-panel);
}
.de-cell span {
  display: block;
  font-size: 10px;
  font-weight: 800;
  color: var(--de-text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
}
.de-cell strong {
  display: block;
  margin-top: 6px;
  font-size: 15px;
  font-weight: 750;
  color: var(--de-text-primary);
}

/* ── Suggestion Box ── */
.sug-title {
  font-size: 14px;
  font-weight: 850;
  color: var(--de-text-primary);
  margin: 16px 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.sug-list {
  display: grid;
  gap: 10px;
}
.sug-item {
  border: 1px solid var(--de-border);
  background: var(--de-panel);
  border-radius: 12px;
  padding: 14px 18px;
  font-size: 13.5px;
  color: var(--de-text-secondary);
  line-height: 1.5;
  border-left: 4px solid var(--de-success);
}

/* ── Tables ── */
.de-table-wrap {
  overflow: auto;
  border: 1px solid var(--de-border);
  border-radius: 16px;
  box-shadow: var(--de-shadow-sm);
  background: var(--de-panel);
}
.de-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.de-table th {
  padding: 14px 16px;
  text-align: left;
  background: rgba(226, 232, 240, 0.4);
  color: var(--de-text-secondary);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .06em;
  text-transform: uppercase;
  border-bottom: 2px solid var(--de-border);
}
[data-bs-theme="dark"] .de-table th {
  background: rgba(17, 24, 39, 0.4);
}
.de-table td {
  padding: 14px 16px;
  border-top: 1px solid var(--de-border);
  color: var(--de-text-secondary);
  vertical-align: middle;
}
.de-table tbody tr:hover td {
  background: var(--de-panel-hover);
  color: var(--de-text-primary);
}

/* ── Stats Panels ── */
.de-bars {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  min-height: 220px;
  padding: 20px 10px 10px;
}
.de-bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.de-bar {
  width: 100%;
  max-width: 52px;
  border-radius: 8px 8px 0 0;
  background: var(--de-primary-gradient);
  min-height: 6px;
  transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.de-bar-l {
  font-size: 11px;
  font-weight: 700;
  color: var(--de-text-secondary);
  text-align: center;
}

.de-pie-legend {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px;
}
.de-legend-row {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 13px;
  color: var(--de-text-secondary);
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.de-legend-row:hover {
  background: var(--de-bg);
}
.de-dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  flex-shrink: 0;
}

/* ── Config Styles ── */
.dec-head {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.dec-subtitle {
  font-size: 14px;
  color: var(--de-text-secondary);
  margin: 6px 0 0;
}
.dec-alert {
  border-radius: 12px;
  padding: 14px 18px;
  font-size: 13.5px;
  margin-bottom: 20px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.dec-alert-icon {
  font-size: 18px;
  flex-shrink: 0;
}
.dec-alert.info {
  border: 1px solid rgba(79, 70, 229, 0.2);
  background: rgba(79, 70, 229, 0.06);
  color: var(--de-primary);
}
.dec-alert.warn {
  border: 1px solid rgba(245, 158, 11, 0.2);
  background: rgba(245, 158, 11, 0.06);
  color: #b45309;
}
[data-bs-theme="dark"] .dec-alert.warn {
  color: #fcd34d;
}

.dec-grid {
  display: grid;
  gap: 20px;
}
.dec-grid.cols2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.dec-grid.cols3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media(max-width: 1024px) {
  .dec-grid.cols3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media(max-width: 768px) {
  .dec-grid.cols2, .dec-grid.cols3 { grid-template-columns: 1fr; }
}

.dec-label-full {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}
.dec-value {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px;
  font-weight: 800;
  color: var(--de-primary);
  background: rgba(79, 70, 229, 0.08);
  padding: 4px 10px;
  border-radius: 8px;
}

.dec-slider-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.dec-slider {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: var(--de-border);
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}
.dec-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  background: var(--de-primary);
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  transition: transform 0.1s ease;
}
.dec-slider::-webkit-slider-thumb:hover {
  transform: scale(1.15);
}
.dec-range-labels {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  font-weight: 700;
  color: var(--de-text-muted);
}

.dec-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: var(--de-bg);
  border-radius: 12px;
  border: 1px solid var(--de-border);
}
.dec-toggle input[type="checkbox"] {
  width: 44px;
  height: 24px;
  appearance: none;
  background: var(--de-border);
  border-radius: 12px;
  cursor: pointer;
  position: relative;
  transition: background 0.2s;
  flex-shrink: 0;
}
.dec-toggle input[type="checkbox"]:checked {
  background: var(--de-primary);
}
.dec-toggle input[type="checkbox"]::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border-radius: 999px;
  background: white;
  top: 2px;
  left: 2px;
  transition: left 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.dec-toggle input[type="checkbox"]:checked::before {
  left: 22px;
}
.dec-toggle-label {
  font-size: 14px;
  font-weight: 750;
  color: var(--de-text-primary);
}
.dec-toggle-desc {
  font-size: 12px;
  color: var(--de-text-secondary);
  margin-top: 2px;
}

/* Alert Boxes */
.de-alert {
  display: none;
  border-radius: 12px;
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 700;
  border: 1px solid transparent;
}
.de-alert.show {
  display: flex;
  align-items: center;
  gap: 8px;
}
.de-alert.err {
  border-color: rgba(244, 63, 94, 0.3);
  background: rgba(244, 63, 94, 0.08);
  color: var(--de-danger);
}
.de-alert.ok {
  border-color: rgba(16, 185, 129, 0.3);
  background: rgba(16, 185, 129, 0.08);
  color: var(--de-success);
}

.de-empty {
  font-size: 13px;
  color: var(--de-text-muted);
  text-align: center;
  font-weight: 500;
}
</style>

<div class="de-page" id="dePage">
  <div class="de-head">
    <div class="de-title-wrap">
      <h1 class="de-title">Moteur de Décisions</h1>
      <div class="de-sub">Analysez les tickets, consultez l'historique, les statistiques et configurez le moteur de décision.</div>
    </div>
  </div>

  <div class="de-kpis">
    <div class="de-kpi kpi-total">
      <div class="de-kpi-icon"><i class="material-symbols-rounded">analytics</i></div>
      <div class="de-kpi-content">
        <span>Décisions Totales</span>
        <strong id="kTotal">-</strong>
      </div>
    </div>
    <div class="de-kpi kpi-auto">
      <div class="de-kpi-icon"><i class="material-symbols-rounded">task_alt</i></div>
      <div class="de-kpi-content">
        <span>Résolus Auto</span>
        <strong id="kAuto">-</strong>
      </div>
    </div>
    <div class="de-kpi kpi-esc">
      <div class="de-kpi-icon"><i class="material-symbols-rounded">warning</i></div>
      <div class="de-kpi-content">
        <span>Escaladés</span>
        <strong id="kEsc">-</strong>
      </div>
    </div>
    <div class="de-kpi kpi-rate">
      <div class="de-kpi-icon"><i class="material-symbols-rounded">percent</i></div>
      <div class="de-kpi-content">
        <span>Taux d'Escalade</span>
        <strong id="kRate">-</strong>
      </div>
    </div>
  </div>

  <div class="de-tabs-wrapper">
    <div class="de-tabs" id="deTabs">
      <button class="de-tab active" data-tab="analyze">Analyser</button>
      <button class="de-tab" data-tab="history">Historique</button>
      <button class="de-tab" data-tab="stats">Statistiques</button>
      <button class="de-tab" data-tab="playbook">Scénarios (Playbook)</button>
      <?php if(auth()->user()->role === 'super_admin'): ?>
        <button class="de-tab" data-tab="config">Configuration</button>
      <?php endif; ?>
    </div>
  </div>

  <div id="deAlert" class="de-alert"></div>

  <!-- Pane Analyser -->
  <section id="pane-analyze" class="de-pane active">
    <div class="de-grid two">
      <div class="de-card">
        <h3><i class="material-symbols-rounded">badge</i> Analyser par ticket</h3>
        <div class="de-grid" style="gap: 12px">
          <div class="de-field">
            <label class="de-label">ID du Ticket</label>
            <input id="aTicket" class="de-input" placeholder="Ex: 42" />
          </div>
          <div style="margin-top: 10px; display: grid; gap: 10px">
            <label style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--de-text-secondary); cursor: pointer">
              Assigner l'agent automatiquement
              <input id="aAssign" type="checkbox" style="width:18px;height:18px;cursor:pointer" />
            </label>
            <label style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--de-text-secondary); cursor: pointer">
              Mettre à jour la priorité automatiquement
              <input id="aPrio" type="checkbox" checked style="width:18px;height:18px;cursor:pointer" />
            </label>
          </div>
          <div class="de-actions" style="margin-top: 14px">
            <button class="de-btn primary" id="aRunTicket">
              <i class="material-symbols-rounded">analytics</i> Analyser le ticket
            </button>
          </div>
        </div>
      </div>

      <div class="de-card">
        <h3><i class="material-symbols-rounded">article</i> Analyser un texte libre</h3>
        <div class="de-grid" style="gap: 12px">
          <div class="de-field">
            <label class="de-label">Sujet (Optionnel)</label>
            <input id="aSubject" class="de-input" placeholder="Sujet du problème..." />
          </div>
          <div class="de-field">
            <label class="de-label">Description du problème</label>
            <textarea id="aText" class="de-text" placeholder="Saisissez la description du problème ici..."></textarea>
          </div>
          <div class="de-actions" style="margin-top: 10px">
            <button class="de-btn primary" id="aRunText">
              <i class="material-symbols-rounded">preview</i> Analyser l'aperçu
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Result Card -->
    <div id="aResult" class="de-card highlight" style="display:none">
      <h3><i class="material-symbols-rounded">insights</i> Résultats de l'analyse</h3>
      <div class="de-badges" id="aBadges"></div>
      <div class="de-result-grid" id="aGrid"></div>
      
      <div class="de-field" style="margin-top: 16px">
        <label class="de-label">Raisonnement du Moteur de Décisions</label>
        <p id="aReason" style="margin: 6px 0 0; color: var(--de-text-secondary); font-size: 14px; line-height: 1.5; font-weight: 500;"></p>
      </div>

      <div class="de-field" style="margin-top: 16px">
        <label class="de-label">Suggestions de réponses (Playbook)</label>
        <div id="aSug" class="sug-list"></div>
      </div>
      
      <div id="aEsc" style="margin-top: 16px; display: none; border: 1px solid rgba(244,63,94,0.3); background: rgba(244,63,94,0.06); border-radius: 12px; padding: 16px; font-size: 13.5px; color: var(--de-danger); font-weight: 600; line-height: 1.5;"></div>
    </div>
  </section>

  <!-- Pane Historique -->
  <section id="pane-history" class="de-pane">
    <div class="de-card">
      <div class="de-actions">
        <input id="hFilter" class="de-input" placeholder="Filtrer par ID de ticket..." style="max-width: 280px" />
        <button class="de-btn" id="hRefresh"><i class="material-symbols-rounded">refresh</i> Actualiser</button>
        <button class="de-btn" id="hClear"><i class="material-symbols-rounded">clear_all</i> Effacer le filtre</button>
      </div>
    </div>
    <div class="de-table-wrap">
      <table class="de-table">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>Résultat</th>
            <th>Intention</th>
            <th>Confiance</th>
            <th>Risque</th>
            <th>Règle Appliquée</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="hBody">
          <tr><td colspan="7" class="de-empty">Chargement de l'historique...</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Pane Statistiques -->
  <section id="pane-stats" class="de-pane">
    <div class="de-grid two">
      <div class="de-card">
        <h3><i class="material-symbols-rounded">bar_chart</i> Décisions par catégorie</h3>
        <div id="sCat" class="de-bars"></div>
      </div>
      <div class="de-card">
        <h3><i class="material-symbols-rounded">pie_chart</i> Décisions par résultat</h3>
        <div id="sOut"></div>
      </div>
    </div>
  </section>

  <!-- Pane Playbook -->
  <section id="pane-playbook" class="de-pane">
    <div class="de-card">
      <h3><i class="material-symbols-rounded">rule</i> Tous les résultats de décision</h3>
      <div class="de-table-wrap">
        <table class="de-table">
          <thead>
            <tr>
              <th>Résultat</th>
              <th>Description</th>
              <th>Directives opérateur</th>
            </tr>
          </thead>
          <tbody id="pOutcomes">
            <tr><td colspan="3" class="de-empty">Chargement des scénarios...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="de-card">
      <h3><i class="material-symbols-rounded">grid_on</i> Matrice de décision</h3>
      <div class="de-table-wrap">
        <table class="de-table">
          <thead>
            <tr>
              <th>Catégorie</th>
              <th>Niveau de Confiance</th>
              <th>Niveau de Risque</th>
              <th>Résultat</th>
              <th>Règle Appliquée</th>
            </tr>
          </thead>
          <tbody id="pMatrix">
            <tr><td colspan="5" class="de-empty">Chargement de la matrice...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Pane Configuration (Super Admin) -->
  <?php if(auth()->user()->role === 'super_admin'): ?>
    <section id="pane-config" class="de-pane">
      <div class="dec-card">
        <div class="dec-head">
          <h2 class="de-title" style="font-size: 24px;">Configuration du Moteur</h2>
          <span class="dec-badge">Accès Super Admin</span>
        </div>
        <div class="dec-subtitle">Ajustez les seuils de confiance et de risque, configurez les règles d'escalade et réglez les boosts en temps réel.</div>
        
        <div class="dec-alert info" style="margin-top: 16px">
          <div class="dec-alert-icon">ℹ️</div>
          <div>
            <strong>Ajustement en direct :</strong> Les modifications s'appliquent immédiatement à la production. Enregistrez après validation de l'impact.
          </div>
        </div>
        <div id="decContent"></div>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
(function(){
  const $ = (id) => document.getElementById(id);
  const COLORS = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6'];

  const outcomeTranslation = {
    'auto_resolve': 'Résolu Automatiquement',
    'auto_resolved': 'Résolu Automatiquement',
    'suggest_response': 'Réponse Suggérée',
    'clarify': 'Clarification Requise',
    'escalate_human': 'Escalade Humaine',
    'escalated': 'Escalade Humaine',
    'route_agent': 'Routage Agent',
    'routed': 'Routage Agent'
  };

  const intentTranslation = {
    'general': 'Général',
    'incident_technique': 'Incident Technique',
    'integration_api': 'Intégration API',
    'facturation': 'Facturation',
    'plateforme': 'Plateforme',
    'paiement_mobile': 'Paiement Mobile',
    'autre': 'Autre'
  };

  const riskTranslation = {
    'low': 'Risque Faible',
    'medium': 'Risque Moyen',
    'high': 'Risque Élevé',
    'critical': 'Risque Critique'
  };

  const priorityTranslation = {
    'low': 'Priorité Basse',
    'medium': 'Priorité Moyenne',
    'high': 'Priorité Haute',
    'critical': 'Priorité Critique'
  };

  const confidenceTranslation = {
    'high': 'Confiance Élevée',
    'medium': 'Confiance Moyenne',
    'low': 'Confiance Faible'
  };

  function translateValue(map, val) {
    if (!val) return '-';
    const key = String(val).toLowerCase();
    return map[key] || humanize(val);
  }

  function showAlert(message, ok) {
    const el = $('deAlert');
    el.textContent = message;
    el.className = 'de-alert show ' + (ok ? 'ok' : 'err');
    clearTimeout(showAlert._t);
    showAlert._t = setTimeout(() => { el.className = 'de-alert'; }, 4200);
  }

  async function api(path, options) {
    const opts = options || {};
    if (window.supportBackendFetch) return window.supportBackendFetch(path, opts);
    const headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});
    if (opts.body && !(opts.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const res = await fetch('/api/v1/' + String(path || '').replace(/^\//, ''), Object.assign({}, opts, { headers }));
    const text = await res.text();
    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (_) { data = { message: text }; }
    if (!res.ok) {
      const errMsg = typeof data.detail === 'object' ? JSON.stringify(data.detail) : (data.detail || data.message || res.statusText || 'Échec de la requête');
      throw new Error(errMsg);
    }
    return data;
  }

  function humanize(v) {
    if (!v) return '-';
    return String(v).replace(/_/g, ' ');
  }

  function pct(v) {
    const n = Number(v || 0);
    return Math.round(n * 100) + '%';
  }

  function badge(text, isOutcome = false) {
    const el = document.createElement('span');
    el.className = 'de-badge' + (isOutcome ? ' outcome-badge' : '');
    el.textContent = text;
    return el;
  }

  function switchTab(tab) {
    document.querySelectorAll('.de-tab').forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.de-pane').forEach((p) => p.classList.toggle('active', p.id === 'pane-' + tab));
    if (tab === 'config' && !window.decConfigUI) {
      window.decConfigUI = new DecisionConfigUI();
    }
  }

  // ═════════════════════════════════════════════════════════════════════════
  // Decision Engine Config UI Class
  // ═════════════════════════════════════════════════════════════════════════
  class DecisionConfigUI {
    constructor() {
      this.contentEl = document.getElementById('decContent');
      this.config = null;
      this.draft = null;
      this.isLoading = true;
      this.isSaving = false;
      this.init();
    }
    async init() {
      try {
        await this.loadConfig();
        this.render();
      } catch (error) {
        console.error('Failed to load config:', error);
        this.showError(error.message || 'Impossible de charger la configuration.');
      }
    }
    async loadConfig() {
      const response = await fetch('/api/v1/decision-engine/config', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      this.config = await response.json();
      this.draft = JSON.parse(JSON.stringify(this.config));
      this.isLoading = false;
    }
    async saveConfig() {
      this.isSaving = true;
      this.render();
      try {
        const response = await fetch('/api/v1/decision-engine/config', {
          method: 'PUT',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(this.draft)
        });
        if (!response.ok) {
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.detail || `HTTP ${response.status}`);
        }
        this.config = await response.json();
        this.draft = JSON.parse(JSON.stringify(this.config));
        this.showSuccess('Configuration enregistrée avec succès.');
      } catch (error) {
        this.showError('Échec de l\'enregistrement: ' + error.message);
      } finally {
        this.isSaving = false;
        this.render();
      }
    }
    resetDraft() {
      this.draft = JSON.parse(JSON.stringify(this.config));
      this.render();
    }
    hasOrderingIssue() {
      return (
        this.draft.confidence_medium_threshold >= this.draft.confidence_high_threshold ||
        this.draft.risk_medium_threshold >= this.draft.risk_high_threshold ||
        this.draft.risk_high_threshold >= this.draft.risk_critical_threshold
      );
    }
    updateSlider(key, value) {
      this.draft[key] = parseFloat(value);
      this.render();
    }
    toggleCheckbox(key) {
      this.draft[key] = !this.draft[key];
      this.render();
    }
    showError(msg) {
      const alert = document.createElement('div');
      alert.className = 'dec-alert err';
      alert.innerHTML = `<div class="dec-alert-icon">✕</div><div>${msg}</div>`;
      this.contentEl.insertBefore(alert, this.contentEl.firstChild);
      setTimeout(() => alert.remove(), 5000);
    }
    showSuccess(msg) {
      const alert = document.createElement('div');
      alert.className = 'dec-alert info';
      alert.style.borderColor = 'rgba(16,185,129,.3)';
      alert.style.background = 'rgba(16,185,129,.1)';
      alert.style.color = '#065f46';
      alert.innerHTML = `<div class="dec-alert-icon">✓</div><div>${msg}</div>`;
      this.contentEl.insertBefore(alert, this.contentEl.firstChild);
      setTimeout(() => alert.remove(), 4000);
    }
    formatValue(value, step) {
      const decimals = (step.toString()).split('.')[1]?.length ?? 0;
      return value.toFixed(decimals);
    }
    renderSlider(key, label, min, max, step) {
      const value = this.draft[key];
      const formatted = this.formatValue(value, step);
      return `
        <div class="dec-field">
          <div class="dec-label-full">
            <label class="dec-label">${label}</label>
            <span class="dec-value">${formatted}</span>
          </div>
          <div class="dec-slider-wrap">
            <input type="range" min="${min}" max="${max}" step="${step}" value="${value}"
                   class="dec-slider" data-key="${key}" />
            <div class="dec-range-labels">
              <span>${this.formatValue(min, step)}</span>
              <span>${this.formatValue(max, step)}</span>
            </div>
          </div>
        </div>
      `;
    }
    renderToggle(key, label, desc) {
      const checked = this.draft[key] ? 'checked' : '';
      return `
        <div class="dec-field">
          <div class="dec-toggle">
            <input type="checkbox" ${checked} class="dec-toggle-input" data-key="${key}" />
            <div>
              <div class="dec-toggle-label">${label}</div>
              <div class="dec-toggle-desc">${desc}</div>
            </div>
          </div>
        </div>
      `;
    }
    render() {
      if (!this.contentEl) return;
      if (this.isLoading) {
        this.contentEl.innerHTML = '<div class="dec-loading"><div class="dec-spinner"></div></div>';
        return;
      }
      const orderingIssue = this.hasOrderingIssue();
      this.contentEl.innerHTML = `
        ${orderingIssue ? `
          <div class="dec-alert warn">
            <div class="dec-alert-icon">⚠️</div>
            <div>
              <strong>Problème d'ordre des seuils :</strong> Les seuils "Moyens" doivent rester inférieurs aux seuils "Élevés".
            </div>
          </div>
        ` : ''}
        <div class="dec-grid cols2" style="margin-top:16px">
          ${this.renderSlider('confidence_high_threshold', 'Confiance Élevée (Seuil)', 0.45, 0.98, 0.01)}
          ${this.renderSlider('confidence_medium_threshold', 'Confiance Moyenne (Seuil)', 0.05, 0.94, 0.01)}
        </div>
        <div class="dec-grid cols3" style="margin-top:16px">
          ${this.renderSlider('risk_critical_threshold', 'Risque Critique (Seuil)', 0.45, 1, 0.01)}
          ${this.renderSlider('risk_high_threshold', 'Risque Élevé (Seuil)', 0.2, 0.98, 0.01)}
          ${this.renderSlider('risk_medium_threshold', 'Risque Moyen (Seuil)', 0.05, 0.9, 0.01)}
        </div>
        <div class="dec-grid cols2" style="margin-top:16px">
          ${this.renderSlider('low_confidence_risk_boost', 'Boost Risque (Confiance Faible)', 0, 0.4, 0.01)}
          ${this.renderSlider('medium_confidence_risk_boost', 'Boost Risque (Confiance Moyenne)', 0, 0.25, 0.01)}
        </div>
        <div class="dec-grid" style="margin-top:16px;gap:10px">
          ${this.renderToggle('enforce_security_escalation', 'Escalade de Sécurité', 'Escalader automatiquement les tickets liés à la sécurité.')}
          ${this.renderToggle('enforce_critical_escalation', 'Escalade Critique', 'Escalader automatiquement les tickets à risque critique.')}
          ${this.renderToggle('low_confidence_general_suggest', 'Suggestions Générales', 'Suggérer des instructions guidées pour les cas de confiance faible.')}
        </div>
        <div class="dec-actions">
          <button class="dec-btn primary" id="decSaveBtn" ${this.isSaving ? 'disabled' : ''}>
            ${this.isSaving ? 'Enregistrement...' : 'Enregistrer la configuration'}
          </button>
          <button class="dec-btn secondary" id="decResetBtn" ${this.isSaving ? 'disabled' : ''}>
            Réinitialiser
          </button>
        </div>
      `;
      this.setupEventListeners();
    }
    setupEventListeners() {
      this.contentEl.querySelectorAll('.dec-slider').forEach(s => {
        s.addEventListener('input', (e) => this.updateSlider(e.target.dataset.key, e.target.value));
      });
      this.contentEl.querySelectorAll('.dec-toggle-input').forEach(c => {
        c.addEventListener('change', (e) => this.toggleCheckbox(e.target.dataset.key));
      });
      const sBtn = document.getElementById('decSaveBtn');
      if (sBtn) sBtn.addEventListener('click', () => this.saveConfig());
      const rBtn = document.getElementById('decResetBtn');
      if (rBtn) rBtn.addEventListener('click', () => this.resetDraft());
    }
  }

  document.querySelectorAll('.de-tab').forEach((b) => b.addEventListener('click', () => switchTab(b.dataset.tab)));

  function renderResult(res) {
    const card = $('aResult');
    card.style.display = '';

    const badges = $('aBadges');
    badges.innerHTML = '';
    
    // Add outcome badge (translated)
    const rawOutcome = res.outcome || res.decision_outcome;
    badges.appendChild(badge(translateValue(outcomeTranslation, rawOutcome), true));
    
    // Add risk level badge (translated)
    badges.appendChild(badge(translateValue(riskTranslation, res.risk_level)));
    
    // Add priority badge if available (translated)
    if (res.suggested_priority) {
      badges.appendChild(badge(translateValue(priorityTranslation, res.suggested_priority)));
    }
    
    // Add confidence badge
    const confVal = res.confidence ?? res.confidence_score;
    const confBadge = document.createElement('span');
    confBadge.className = 'de-badge';
    confBadge.textContent = 'Confiance ' + pct(confVal);
    badges.appendChild(confBadge);

    const grid = $('aGrid');
    const riskScore = res.risk_score !== undefined ? pct(res.risk_score) : '-';
    
    const vals = [
      ['Intention Détectée', translateValue(intentTranslation, res.intent_category)],
      ['Niveau de Confiance', translateValue(confidenceTranslation, res.confidence_level)],
      ['Score de Risque', riskScore],
      ['Agent Suggéré', res.suggested_agent_name || 'Aucun (Routage auto)'],
    ];
    grid.innerHTML = vals.map(([k, v]) => '<div class="de-cell"><span>' + k + '</span><strong>' + String(v).replace(/</g, '&lt;') + '</strong></div>').join('');

    $('aReason').textContent = res.reasoning || 'Aucun raisonnement fourni.';

    const sug = $('aSug');
    sug.innerHTML = '';
    const suggestions = Array.isArray(res.response_suggestions) ? res.response_suggestions.slice(0, 4) : [];
    if (suggestions.length === 0) {
      sug.innerHTML = '<div class="de-empty" style="text-align: left;">Aucune suggestion disponible pour ce scénario.</div>';
    } else {
      suggestions.forEach((s) => {
        const row = document.createElement('div');
        row.className = 'sug-item';
        row.textContent = s;
        sug.appendChild(row);
      });
    }

    const esc = $('aEsc');
    if (res.escalation_summary) {
      esc.style.display = '';
      esc.innerHTML = '<strong>Alerte d\'Escalade :</strong> ' + String(res.escalation_summary).replace(/</g, '&lt;');
    } else {
      esc.style.display = 'none';
      esc.textContent = '';
    }
  }

  async function runAnalyzeTicket() {
    try {
      const ticket = ($('aTicket').value || '').trim();
      if (!ticket) throw new Error('L\'ID du ticket est requis.');
      const payload = {
        ticket_id: ticket,
        auto_assign: $('aAssign').checked,
        auto_update_priority: $('aPrio').checked,
      };
      const res = await api('/decision-engine/analyze', { method: 'POST', body: JSON.stringify(payload) });
      renderResult(res);
      showAlert('Ticket analysé avec succès.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  }

  async function runAnalyzeText() {
    try {
      const text = ($('aText').value || '').trim();
      if (!text) throw new Error('La description du problème est requise.');
      const payload = { text: text, subject: (($('aSubject').value || '').trim() || undefined) };
      const res = await api('/decision-engine/analyze-text', { method: 'POST', body: JSON.stringify(payload) });
      renderResult(res);
      showAlert('Texte libre analysé avec succès.', true);
    } catch (err) {
      showAlert(err.message || String(err), false);
    }
  }

  async function loadStats() {
    const s = await api('/decision-engine/stats');
    $('kTotal').textContent = String(s.total_decisions ?? '-');
    $('kAuto').textContent = String(s.auto_resolved ?? '-');
    $('kEsc').textContent = String(s.escalated ?? '-');
    $('kRate').textContent = s.escalation_rate !== undefined ? pct(s.escalation_rate) : '-';

    const cat = s.decisions_by_category || {};
    const catEntries = Object.entries(cat);
    const catMax = Math.max(1, ...catEntries.map((x) => Number(x[1] || 0)));
    $('sCat').innerHTML = catEntries.length
      ? catEntries.map(([k,v],i) => {
          const h = Math.max(8, Math.round((Number(v || 0) / catMax) * 160));
          return '<div class="de-bar-col"><div class="de-bar" style="height:' + h + 'px;background:' + COLORS[i % COLORS.length] + '"></div><div class="de-bar-l">' + translateValue(intentTranslation, k) + ' (' + v + ')</div></div>';
        }).join('')
      : '<div class="de-empty">Aucune donnée par catégorie.</div>';

    const out = s.decisions_by_outcome || {};
    const outEntries = Object.entries(out);
    const outTotal = Math.max(1, outEntries.reduce((acc, cur) => acc + Number(cur[1] || 0), 0));
    $('sOut').innerHTML = outEntries.length
      ? '<div class="de-pie-legend">' + outEntries.map(([k,v], i) => {
          const p = Math.round((Number(v || 0) / outTotal) * 100);
          return '<div class="de-legend-row"><span class="de-dot" style="background:' + COLORS[i % COLORS.length] + '"></span><span>' + translateValue(outcomeTranslation, k) + '</span><strong style="margin-left:auto">' + v + ' (' + p + '%)</strong></div>';
        }).join('') + '</div>'
      : '<div class="de-empty">Aucune donnée de résultat.</div>';
  }

  async function loadHistory() {
    const filter = ($('hFilter').value || '').trim();
    const endpoint = filter ? '/decision-engine/decisions/' + encodeURIComponent(filter) : '/decision-engine/decisions';
    const res = await api(endpoint);
    const rows = Array.isArray(res?.decisions) ? res.decisions : [];
    const body = $('hBody');
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="de-empty">Aucune décision trouvée dans l\'historique.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((d) => {
      const conf = pct(d.confidence_score ?? d.confidence);
      const dt = d.created_at ? new Date(d.created_at).toLocaleString('fr-FR') : '-';
      const rule = Array.isArray(d?.matched_rules?.rules) ? d.matched_rules.rules[0] : (Array.isArray(d.matched_rules) ? d.matched_rules[0] : '-');
      return '<tr>'
        + '<td style="font-weight: 700; color: var(--de-primary);">' + String(d.ticket_id || '-').replace(/</g, '&lt;') + '</td>'
        + '<td>' + translateValue(outcomeTranslation, d.decision_outcome || d.outcome) + '</td>'
        + '<td>' + translateValue(intentTranslation, d.intent_category) + '</td>'
        + '<td style="font-family: \'JetBrains Mono\', monospace; font-weight: 700;">' + conf + '</td>'
        + '<td>' + translateValue(riskTranslation, d.risk_level) + '</td>'
        + '<td><code style="font-size: 11px; background: var(--de-bg); padding: 4px 8px; border-radius: 6px;">' + String(rule || '-').replace(/</g, '&lt;') + '</code></td>'
        + '<td>' + dt + '</td>'
        + '</tr>';
    }).join('');
  }

  async function loadPlaybook() {
    const res = await api('/decision-engine/outcomes-docs');
    const outcomes = Array.isArray(res?.outcomes) ? res.outcomes : [];
    const matrix = Array.isArray(res?.matrix) ? res.matrix : [];

    $('pOutcomes').innerHTML = outcomes.length
      ? outcomes.map((o) => '<tr><td style="font-weight: 700; color: var(--de-primary);">' + translateValue(outcomeTranslation, o.outcome) + '</td><td>' + String(o.description || '').replace(/</g, '&lt;') + '</td><td>' + String(o.operator_guidance || '').replace(/</g, '&lt;') + '</td></tr>').join('')
      : '<tr><td colspan="3" class="de-empty">Aucune documentation de résultat disponible.</td></tr>';

    $('pMatrix').innerHTML = matrix.length
      ? matrix.map((r) => '<tr><td>' + translateValue(intentTranslation, r.category) + '</td><td>' + translateValue(confidenceTranslation, r.confidence_level) + '</td><td>' + translateValue(riskTranslation, r.risk_level) + '</td><td style="font-weight: 700; color: var(--de-primary);">' + translateValue(outcomeTranslation, r.outcome) + '</td><td><code style="font-size: 11px; background: var(--de-bg); padding: 4px 8px; border-radius: 6px;">' + String(r.matched_rule || '').replace(/</g, '&lt;') + '</code></td></tr>').join('')
      : '<tr><td colspan="5" class="de-empty">Aucune règle définie dans la matrice.</td></tr>';
  }

  $('aRunTicket').addEventListener('click', runAnalyzeTicket);
  $('aRunText').addEventListener('click', runAnalyzeText);
  $('hRefresh').addEventListener('click', () => loadHistory().catch((e) => showAlert(e.message || String(e), false)));
  $('hClear').addEventListener('click', () => { $('hFilter').value = ''; loadHistory().catch((e) => showAlert(e.message || String(e), false)); });

  Promise.all([loadStats(), loadHistory(), loadPlaybook()]).catch((err) => {
    showAlert(err.message || String(err), false);
  });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/support/decision-engine.blade.php ENDPATH**/ ?>