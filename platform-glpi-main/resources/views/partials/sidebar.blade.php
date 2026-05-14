<style>
  /* ===== SIDEBAR STRUCTURE ===== */
  #sidenav-main {
    display: flex !important;
    flex-direction: column !important;
    height: calc(100vh - 1rem) !important;
    overflow: hidden !important;
    padding-bottom: 0 !important;
  }
  #sidenav-collapse-main {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }
  #sidenav-main .nav-link {
    display: flex !important;
    align-items: center !important;
    border-radius: 8px !important;
    padding: 9px 10px !important;
    transition: background 0.15s !important;
    text-decoration: none !important;
    white-space: nowrap !important;
  }
  /* Themeable colors (light/dark) */
  #sidenav-main {
    --sidebar-bg: var(--bg-sidebar);
    --sidebar-link: var(--text-main);
    --sidebar-link-hover: var(--text-heading);
    --sidebar-hover-bg: rgba(0, 0, 0, 0.03);
    --sidebar-muted: var(--text-muted);
    --sidebar-divider: var(--border-color);
    --sidebar-toggle: var(--text-muted);
    --sidebar-toggle-hover: var(--text-heading);
    --sidebar-toggle-hover-bg: rgba(0, 0, 0, 0.05);
  }
  [data-bs-theme="dark"] #sidenav-main {
    --sidebar-bg: var(--bg-sidebar);
    --sidebar-link: var(--text-main);
    --sidebar-link-hover: var(--text-heading);
    --sidebar-hover-bg: rgba(255, 255, 255, 0.05);
    --sidebar-muted: var(--text-muted);
    --sidebar-divider: var(--border-color);
    --sidebar-toggle: var(--text-muted);
    --sidebar-toggle-hover: var(--text-heading);
    --sidebar-toggle-hover-bg: rgba(255, 255, 255, 0.08);
  }
  #sidenav-main .nav-link {
    margin: 4px 12px !important;
    padding: 12px 16px !important;
    font-weight: 500 !important;
  }
  #sidenav-main .nav-link.active {
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important;
    box-shadow: 0 10px 15px -3px color-mix(in srgb, var(--color-primary) 25%, transparent) !important;
  }
  #sidenav-main .nav-section {
    padding: 24px 28px 8px !important;
    font-size: 0.75rem !important;
  }
  #sidebarToggleBtn {
    flex-shrink: 0;
    width: 30px; height: 30px;
    border: none; background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    border-radius: 7px; color: var(--sidebar-toggle);
    transition: background 0.15s, color 0.15s; padding: 0;
  }
  #sidebarToggleBtn:hover { background: var(--sidebar-toggle-hover-bg); color: var(--sidebar-toggle-hover); }

  /* iconCollapsed hidden by default */
  #iconCollapsed { display: none; }
</style>

<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 my-2" id="sidenav-main" style="background: var(--sidebar-bg) !important; border:none !important;">

  {{-- HEADER: Logo + Toggle --}}
  <div id="sidenavHeader"
       style="flex-shrink:0; display:flex; align-items:center; padding:12px 14px 10px; gap:10px;">

    <a href="{{ url('/') }}" id="logoLink"
       style="flex:1; display:flex; align-items:center; overflow:hidden; min-width:0; text-decoration:none;">
      @php $logoPath = App\Models\Setting::get('logo_path'); @endphp
      <img src="{{ $logoPath ? asset($logoPath) . '?v=' . filemtime(public_path($logoPath)) : asset('assets/img/logo l2t.png') }}"
           id="sidenavLogo"
           style="height:140px; width:auto; max-width:500px; object-fit:contain; display:block;">
    </a>

    <button id="sidebarToggleBtn" onclick="toggleSidebar()" title="Toggle sidebar">
      <svg id="iconExpanded" width="17" height="17" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2.5"/>
        <line x1="9" y1="3" x2="9" y2="21"/>
        <polyline points="5 8 7 10 5 12"/>
      </svg>
      <svg id="iconCollapsed" width="17" height="17" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2.5"/>
        <line x1="9" y1="3" x2="9" y2="21"/>
        <polyline points="13 8 15 10 13 12"/>
      </svg>
    </button>
  </div>

  <hr class="horizontal dark mt-0 mb-1" style="flex-shrink:0;">

  {{-- NAV SCROLL AREA --}}
  <div id="sidenav-collapse-main" style="padding:4px 8px 12px;">
    <ul class="navbar-nav" style="gap:1px; padding:0; margin:0; list-style:none;">

      @if(auth()->user()->role === 'super_admin')

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.dashboard') }}"
             style="{{ request()->routeIs('super-admin.dashboard') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">dashboard</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Dashboard</span>
            <span class="nav-badge ms-auto" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;font-size:9px;padding:3px 7px;border-radius:10px;flex-shrink:0;">SA</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">Gestion utilisateurs</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.admins*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.admins') }}"
             style="{{ request()->routeIs('super-admin.admins*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">shield_person</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Gérer les admins</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.clients*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.clients') }}"
             style="{{ request()->routeIs('super-admin.clients*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">groups</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Gérer les clients</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">Tickets</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.tickets*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.tickets') }}"
             style="{{ request()->routeIs('super-admin.tickets*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">confirmation_number</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Tous les tickets</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">Communication</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.conversations') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.conversations') }}"
             style="{{ request()->routeIs('super-admin.conversations') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">forum</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Conversations</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.voice-calls*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.voice-calls') }}"
             style="{{ request()->routeIs('super-admin.voice-calls*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">phone_in_talk</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Appels & Vocaux</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.ai-draft-snippets') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.ai-draft-snippets') }}"
             style="{{ request()->routeIs('super-admin.ai-draft-snippets') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">text_snippet</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Snippets brouillons IA</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('voice-agents.runtime') || request()->routeIs('super-admin.voice-agents') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('voice-agents.runtime') }}"
             style="{{ request()->routeIs('voice-agents.runtime') || request()->routeIs('super-admin.voice-agents') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">settings_voice</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Voice Agents Runtime</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">IA & Décisions</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.escalations') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.escalations') }}"
             style="{{ request()->routeIs('super-admin.escalations') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">warning_amber</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Escalations</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.decision-engine*') || request()->routeIs('decision-engine.runtime') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('decision-engine.runtime') }}"
             style="{{ request()->routeIs('super-admin.decision-engine*') || request()->routeIs('decision-engine.runtime') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">psychology</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Moteur de Décisions</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('visual-ai') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('visual-ai') }}"
             style="{{ request()->routeIs('visual-ai') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">computer</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Visual AI</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.rag') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.rag') }}"
             style="{{ request()->routeIs('super-admin.rag') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">auto_stories</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Base de Connaissance</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">Système</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.settings*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.settings') }}"
             style="{{ request()->routeIs('super-admin.settings*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">tune</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Paramètres</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('super-admin.logs*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('super-admin.logs') }}"
             style="{{ request()->routeIs('super-admin.logs*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">receipt_long</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Logs & Audit</span>
          </a>
        </li>

      @elseif(auth()->user()->role === 'admin')

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.dashboard') }}"
             style="{{ request()->routeIs('admin.dashboard') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">dashboard</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Dashboard</span>
            <span class="nav-badge ms-auto" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;font-size:9px;padding:3px 7px;border-radius:10px;flex-shrink:0;">A</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.tickets*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.tickets') }}"
             style="{{ request()->routeIs('admin.tickets*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">support_agent</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Gérer les tickets</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.clients*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.clients') }}"
             style="{{ request()->routeIs('admin.clients*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">groups</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Clients</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">Communication</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.chat') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.chat') }}"
             style="{{ request()->routeIs('admin.chat') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">forum</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Chat</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.whatsapp') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.whatsapp') }}"
             style="{{ request()->routeIs('admin.whatsapp') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">chat_bubble</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">WhatsApp</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.voice-calls*') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.voice-calls') }}"
             style="{{ request()->routeIs('admin.voice-calls*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">phone_in_talk</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Appels & Vocaux</span>
          </a>
        </li>

        <li class="nav-item"><div class="nav-section">IA & Escalations</div></li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.escalations') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.escalations') }}"
             style="{{ request()->routeIs('admin.escalations') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">warning_amber</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Escalations</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('admin.urgent-tickets') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('admin.urgent-tickets') }}"
             style="{{ request()->routeIs('admin.urgent-tickets') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">priority_high</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Tickets urgents</span>
          </a>
        </li>

      @else
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('client.dashboard') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('client.dashboard') }}"
             style="{{ request()->routeIs('client.dashboard') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">dashboard</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('tickets.index') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('tickets.index') }}"
             style="{{ request()->routeIs('tickets.index') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">confirmation_number</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Mes tickets</span>
          </a>
        </li>
      @endif

      @if(auth()->user()->role === 'client')
        
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('chat') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('chat') }}"
             style="{{ request()->routeIs('chat') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">forum</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Chat</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('troubleshooting-wizard') ? 'active text-white' : 'text-dark' }}"
             href="{{ route('troubleshooting-wizard') }}"
             style="{{ request()->routeIs('troubleshooting-wizard') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
            <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">troubleshoot</i>
            <span class="nav-link-text ms-2" style="font-size:0.925rem;">Troubleshooting Wizard</span>
          </a>
        </li>
      @endif

      @if(auth()->user()->role === 'client')
      <li class="nav-item"><div class="nav-section">Paramètres</div></li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('client.settings') || request()->routeIs('profile.*') ? 'active text-white' : 'text-dark' }}"
           href="{{ route('client.settings') }}"
           style="{{ request()->routeIs('client.settings') || request()->routeIs('profile.*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
          <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">manage_accounts</i>
          <span class="nav-link-text ms-2" style="font-size:0.925rem;">Paramètres</span>
        </a>
      </li>
      @elseif(auth()->user()->role === 'admin')
      <li class="nav-item"><div class="nav-section">Paramètres</div></li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.user-settings') || request()->routeIs('profile.*') ? 'active text-white' : 'text-dark' }}"
           href="{{ route('admin.user-settings') }}"
           style="{{ request()->routeIs('admin.user-settings') || request()->routeIs('profile.*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
          <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">manage_accounts</i>
          <span class="nav-link-text ms-2" style="font-size:0.925rem;">Paramètres</span>
        </a>
      </li>
      @else
      <li class="nav-item"><div class="nav-section">Compte</div></li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('profile.*') ? 'active text-white' : 'text-dark' }}"
           href="{{ route('profile.edit') }}"
           style="{{ request()->routeIs('profile.*') ? 'background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));' : '' }}">
          <i class="material-symbols-rounded" style="font-size:23px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;flex-shrink:0;">account_circle</i>
          <span class="nav-link-text ms-2" style="font-size:0.925rem;">Mon profil</span>
        </a>
      </li>
      @endif

    </ul>
  </div>

</aside>

{{-- Mobile overlay — close sidebar when clicking outside --}}
<div id="mobileOverlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1040;"
     onclick="closeMobileSidebar()"></div>

<script>
/* ================================================================
   DESKTOP TOGGLE (collapsed / expanded)
================================================================ */
window.toggleSidebar = function () {
  var isMobile = window.innerWidth < 1200;

  if (isMobile) {
    /* On mobile/tablet: toggle open/close */
    if (document.body.classList.contains('sidebar-mobile-open')) {
      closeMobileSidebar();
    } else {
      openMobileSidebar();
    }
    return;
  }

  /* Desktop: collapsed mode */
  var now = localStorage.getItem('sidebar_collapsed') === '1';
  now = !now;
  localStorage.setItem('sidebar_collapsed', now ? '1' : '0');

  document.documentElement.classList.toggle('sidebar-collapsed', now);
  document.body.classList.toggle('sidebar-collapsed', now);

  /* Force margin after material-dashboard.min.js */
  document.querySelectorAll('.main-content').forEach(function(el) {
    el.style.setProperty('margin-left', now ? '72px' : '', 'important');
  });
};

/* ================================================================
   MOBILE SIDEBAR OPEN / CLOSE
================================================================ */
function openMobileSidebar() {
  document.body.classList.add('sidebar-mobile-open');
  var overlay = document.getElementById('mobileOverlay');
  if (overlay) overlay.style.display = 'block';
}

window.closeMobileSidebar = function() {
  document.body.classList.remove('sidebar-mobile-open');
  var overlay = document.getElementById('mobileOverlay');
  if (overlay) overlay.style.display = 'none';
};

/* Close mobile sidebar when a nav link is clicked */
document.querySelectorAll('#sidenav-main .nav-link').forEach(function(link) {
  link.addEventListener('click', function() {
    if (window.innerWidth < 1200) closeMobileSidebar();
  });
});

/* ================================================================
   HAMBURGER BUTTON in navbar — inject if not present
   (looks for element with id="mobileSidebarBtn")
================================================================ */
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('mobileSidebarBtn');
  if (btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (window.innerWidth < 1200) {
        openMobileSidebar();
      } else {
        window.toggleSidebar();
      }
    });
  }
});
</script>
