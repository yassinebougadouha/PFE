<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl position-sticky blur top-1 z-index-3 mt-2"
     style="backdrop-filter: saturate(200%) blur(30px); padding-top:14px !important; padding-bottom:14px !important; min-height:70px; border-radius: 20px !important; box-shadow: var(--card-shadow) !important; border: 1px solid var(--border-color) !important;">
  <div class="container-fluid px-3">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="d-none d-lg-block me-auto">
      <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0">
        <li class="breadcrumb-item text-sm">
          <a class="text-secondary" href="{{ route('dashboard') }}">
            <i class="material-symbols-rounded" style="font-size:18px;vertical-align:middle;color:var(--color-primary);">home</i>
          </a>
        </li>
        <li class="breadcrumb-item text-sm font-weight-bold active" style="color:var(--text-heading); letter-spacing:-0.01em;">
          @yield('page-title','Dashboard')
        </li>
      </ol>
    </nav>
     
    <div class="d-flex align-items-center justify-content-end ms-auto" id="navbar" style="gap:12px;flex-wrap:nowrap;min-width:0;">

      {{-- GLOBAL SEARCH --}}
      <div class="d-flex align-items-center position-relative navbar-search-wrap">
        <div class="input-group input-group-outline border-radius-lg" style="border-radius:14px !important; overflow:hidden; border: 2px solid var(--border-color) !important; background: var(--input-bg); transition: all 0.2s ease;">
          <span class="input-group-text bg-transparent border-0" style="padding-left:12px;">
            <i class="material-symbols-rounded text-secondary" style="font-size:20px; color:var(--text-muted) !important;">search</i>
          </span>
          <input type="text" class="form-control border-0"
                 placeholder="Rechercher..."
                 id="globalSearch"
                 autocomplete="off"
                 style="font-family: var(--font-main); font-weight: 500; font-size:14px; padding: 10px 12px 10px 0; background: transparent !important; color: var(--text-main) !important;">
        </div>
        <div id="searchResults"
             class="position-absolute shadow-lg border-radius-lg d-none"
             style="top:45px; left:0; right:0; z-index:9999; border-radius:14px; overflow:visible;
                    box-shadow:0 8px 32px rgba(0,0,0,0.13); border:1px solid var(--border-color); background: var(--bg-card);">
          <div id="searchResultsInner"
               style="max-height:400px; overflow-y:scroll; overflow-x:hidden;
                      border-radius:14px;
                      padding:8px;">
          </div>
        </div>
      </div>

      <style>
      /* ── Navbar search responsive ── */
      .navbar-search-wrap {
        width: 300px;
        min-width: 0;
        flex-shrink: 1;
      }
      @media (max-width: 1199.98px) {
        .navbar-search-wrap { width: 220px; }
      }
      @media (max-width: 767.98px) {
        .navbar-search-wrap { width: 160px; }
        .navbar-search-wrap .form-control { font-size: 13px; }
      }
      @media (max-width: 479.98px) {
        .navbar-search-wrap { width: 120px; }
      }
      /* Never wrap/break the right-side icons */
      #navbar { flex-wrap: nowrap !important; }
      #navbar ul.navbar-nav { flex-wrap: nowrap !important; }
      </style>

      <ul class="navbar-nav d-flex align-items-center justify-content-end ms-2">


        {{-- NOTIFICATIONS — système DB par ticket --}}
        @php
          use App\Models\Notification as Notif;
          $notifications = Notif::where('user_id', auth()->id())
              ->where('is_read', false)
              ->latest()
              ->limit(20)
              ->get();
          $notifCount = $notifications->count();
        @endphp

        <li class="nav-item dropdown px-2">
          <a href="#" class="nav-link text-body p-0 position-relative d-flex align-items-center justify-content-center" 
             style="width:40px; height:40px; border-radius:12px; background:var(--bg-body); border: 1.5px solid var(--border-color); transition: all 0.2s ease;"
             onmouseover="this.style.background='var(--bg-card)'; this.style.borderColor='var(--color-primary)'; this.style.transform='translateY(-1px)'"
             onmouseout="this.style.background='var(--bg-body)'; this.style.borderColor='var(--border-color)'; this.style.transform='none'"
             data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false">
            <i class="material-symbols-rounded" style="font-size:22px; color:var(--text-muted);">notifications</i>
            @if($notifCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                  style="font-size:9px; padding:4px 6px; background:linear-gradient(135deg, #ef4444, #b91c1c); border: 2px solid #fff; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);" id="notif-badge">{{ $notifCount }}</span>
            @endif
          </a>

          <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu mt-2 p-0"
               style="min-width:390px;max-width:420px;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.15);overflow:visible;border:none;z-index:10000;">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between px-3 py-3"
                 style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
              <div class="d-flex align-items-center">
                <i class="material-symbols-rounded text-white me-2" style="font-size:20px;">notifications</i>
                <span class="text-white font-weight-bold text-sm">Notifications</span>
              </div>
              @if($notifCount > 0)
              <span class="badge" style="background:rgba(255,255,255,0.25);color:white;font-size:11px;">
                {{ $notifCount }} non lue(s)
              </span>
              @endif
            </div>

            {{-- Liste --}}
            <div id="notif-list"
                 style="max-height:430px;overflow-y:scroll;overflow-x:hidden;padding:6px 0;scroll-behavior:smooth;">
              @forelse($notifications as $notif)
              @php
                $gradients = [
                  'warning' => 'linear-gradient(135deg,#f59e0b,#d97706)',
                  'success' => 'linear-gradient(135deg,#10b981,#059669)',
                  'info'    => 'linear-gradient(135deg,#3b82f6,#1d4ed8)',
                  'primary' => 'linear-gradient(135deg,var(--color-primary),var(--color-secondary))',
                  'danger'  => 'linear-gradient(135deg,#ef4444,#b91c1c)',
                ];
                $grad = $gradients[$notif->color] ?? $gradients['primary'];
                $ago  = $notif->created_at->diffForHumans();
              @endphp
              <div class="notif-card" id="notif-{{ $notif->id }}"
                   data-notif-id="{{ $notif->id }}"
                   data-notif-url="{{ $notif->url ?? '' }}"
                   style="margin:8px 12px;border-radius:14px;background:white;
                          box-shadow:0 2px 12px rgba(102,126,234,0.10);
                          border:1px solid #ede9fe;overflow:hidden;transition:all 0.2s ease;">
                <div style="display:flex;align-items:stretch;">
                  <div style="width:4px;background:{{ $grad }};flex-shrink:0;"></div>
                  <div class="notif-inner d-flex align-items-center px-3 py-3 w-100"
                       style="cursor:pointer;gap:12px;">
                    <div style="width:42px;height:42px;border-radius:11px;flex-shrink:0;
                                background:{{ $grad }};
                                display:flex;align-items:center;justify-content:center;
                                box-shadow:0 3px 8px rgba(0,0,0,0.13);">
                      <i class="material-symbols-rounded text-white" style="font-size:22px;">{{ $notif->icon }}</i>
                    </div>
                    <div style="flex:1;min-width:0;">
                      <p class="mb-0" style="font-size:13px;font-weight:600;color:#1e293b;line-height:1.35;white-space:normal;">
                        {{ $notif->title }}
                      </p>
                      @if($notif->body)
                      <p class="mb-1" style="font-size:12px;color:#64748b;line-height:1.3;white-space:normal;">
                        {{ Str::limit($notif->body, 60) }}
                      </p>
                      @endif
                      <div class="d-flex align-items-center" style="gap:4px;">
                        <i class="material-symbols-rounded" style="font-size:11px;color:#94a3b8;">schedule</i>
                        <span style="font-size:11px;color:#94a3b8;">{{ $ago }}</span>
                      </div>
                    </div>
                    <button type="button"
                       class="notif-dismiss-btn"
                       data-id="{{ $notif->id }}"
                       title="Marquer comme lu"
                       style="width:28px;height:28px;border-radius:50%;flex-shrink:0;
                              background:#f1f5f9;border:none;
                              display:flex;align-items:center;justify-content:center;
                              cursor:pointer;"
                       onmouseenter="this.style.background='#e2e8f0'"
                       onmouseleave="this.style.background='#f1f5f9'">
                      <i class="material-symbols-rounded" style="font-size:15px;color:#64748b;">close</i>
                    </button>
                  </div>
                </div>
              </div>
              @empty
              <div class="text-center py-5" id="notif-empty">
                <i class="material-symbols-rounded" style="font-size:48px;color:#cbd5e1;">notifications_none</i>
                <p class="text-sm text-secondary mt-2 mb-0">Aucune nouvelle notification</p>
              </div>
              @endforelse
            </div>

            @if($notifCount > 0)
            <div class="px-3 py-2 text-center" style="background:#f8fafc;border-top:1px solid #e2e8f0;" id="notif-footer">
              <button onclick="markAllRead()" type="button"
                 class="btn btn-sm mb-0 px-3 py-2"
                 style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;border:none;border-radius:8px;font-size:12px;font-weight:600;">
                <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">done_all</i>
                Tout marquer comme lu
              </button>
            </div>
            @endif
          </div>
        </li>

        <style>
        /* ── Notification dropdown ── */
        .notif-dropdown-menu {
          border-radius: 14px !important;
          overflow: visible !important;   /* MUST be visible so child scroll works */
          display: flex !important;
          flex-direction: column !important;
          clip-path: inset(0 round 14px);  /* keeps border-radius visual without overflow:hidden */
        }
        /* Bootstrap override — prevent .dropdown-menu from cutting scroll */
        .notif-dropdown-menu.show {
          overflow: visible !important;
        }
        #notif-list {
          scrollbar-width: thin;
          scrollbar-color: var(--color-primary, #667eea) #f1f5f9;
          overscroll-behavior: contain;
          -webkit-overflow-scrolling: touch;
        }
        #notif-list::-webkit-scrollbar { width: 6px; }
        #notif-list::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 6px; margin: 4px 0; }
        #notif-list::-webkit-scrollbar-thumb {
          background: var(--color-primary, #667eea);
          border-radius: 6px;
          min-height: 30px;
        }
        #notif-list::-webkit-scrollbar-thumb:hover { background: var(--color-secondary, #764ba2); }

        /* ── Search dropdown ── */
        #searchResults {
          transition: none;
          clip-path: inset(0 round 14px);
        }
        #searchResultsInner {
          scrollbar-width: thin;
          scrollbar-color: var(--color-primary, #667eea) #f8f9fa;
          overscroll-behavior: contain;
          -webkit-overflow-scrolling: touch;
        }
        #searchResultsInner::-webkit-scrollbar { width: 6px; }
        #searchResultsInner::-webkit-scrollbar-track { background: #f8f9fa; border-radius: 6px; margin: 4px 0; }
        #searchResultsInner::-webkit-scrollbar-thumb {
          background: var(--color-primary, #667eea);
          border-radius: 6px;
          min-height: 30px;
        }
        #searchResultsInner::-webkit-scrollbar-thumb:hover { background: var(--color-secondary, #764ba2); }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        </style>
        <script>
        var _notifCsrf = "{{ csrf_token() }}";

        // Prevent page scroll when scrolling inside dropdowns
        document.addEventListener('DOMContentLoaded', function() {
          ['notif-list', 'searchResultsInner'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('wheel', function(e) {
              var atTop    = this.scrollTop === 0 && e.deltaY < 0;
              var atBottom = this.scrollTop + this.clientHeight >= this.scrollHeight && e.deltaY > 0;
              if (!atTop && !atBottom) e.stopPropagation();
            }, { passive: true });
          });
        });

        // ── Helper: build notification card HTML safely via data-url attribute ──
        function _buildNotifCard(id, grad, icon, title, bodyHtml, ago, url) {
          var card = document.createElement('div');
          card.className = 'notif-card';
          card.id = 'notif-' + id;
          card.dataset.url = url || '';
          card.style.cssText = 'margin:8px 12px;border-radius:14px;background:white;box-shadow:0 2px 12px rgba(102,126,234,0.10);border:1px solid #ede9fe;overflow:hidden;transition:all 0.2s ease;';
          card.innerHTML =
            '<div style="display:flex;align-items:stretch;">'
            + '<div style="width:4px;background:' + grad + ';flex-shrink:0;"></div>'
            + '<div class="d-flex align-items-center px-3 py-3 w-100 notif-inner" style="cursor:pointer;gap:12px;">'
            + '<div style="width:42px;height:42px;border-radius:11px;flex-shrink:0;background:' + grad + ';display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.13);">'
            + '<i class="material-symbols-rounded text-white" style="font-size:22px;">' + icon + '</i></div>'
            + '<div style="flex:1;min-width:0;">'
            + '<p class="mb-0" style="font-size:13px;font-weight:600;color:#1e293b;line-height:1.35;white-space:normal;">' + title + '</p>'
            + bodyHtml
            + '<div class="d-flex align-items-center" style="gap:4px;"><i class="material-symbols-rounded" style="font-size:11px;color:#94a3b8;">schedule</i><span style="font-size:11px;color:#94a3b8;">' + ago + '</span></div>'
            + '</div>'
            + '<button type="button" class="notif-dismiss-btn" data-id="' + id + '" style="width:28px;height:28px;border-radius:50%;flex-shrink:0;background:#f1f5f9;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;">'
            + '<i class="material-symbols-rounded" style="font-size:15px;color:#64748b;">close</i></button>'
            + '</div></div>';

          // Attacher les events proprement (sans inline onclick)
          card.querySelector('.notif-inner').addEventListener('click', function(e) {
            if (e.target.closest('.notif-dismiss-btn')) return;
            readAndGo(id, card.dataset.url);
          });
          card.querySelector('.notif-dismiss-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            dismissNotif(id);
          });
          return card;
        }

        function animateRemove(el, cb) {
          if (!el) return;
          el.style.transition  = "all 0.3s ease";
          el.style.opacity     = "0";
          el.style.transform   = "translateX(20px)";
          el.style.maxHeight   = el.offsetHeight + "px";
          setTimeout(function() {
            el.style.maxHeight     = "0";
            el.style.margin        = "0";
            el.style.paddingTop    = "0";
            el.style.paddingBottom = "0";
          }, 200);
          setTimeout(function() { el.remove(); if(cb) cb(); }, 500);
        }

        function updateBadge() {
          var list  = document.getElementById("notif-list");
          var badge = document.getElementById("notif-badge");
          var count = list ? list.querySelectorAll(".notif-card").length : 0;
          if (count === 0) {
            if (badge) badge.remove();
            var footer = document.getElementById("notif-footer");
            if (footer) footer.remove();
            if (list) list.innerHTML = '<div class="text-center py-5"><i class="material-symbols-rounded" style="font-size:48px;color:#cbd5e1;">notifications_none</i><p class="text-sm text-secondary mt-2 mb-0">Aucune nouvelle notification</p></div>';
          } else {
            if (badge) badge.textContent = count;
          }
        }

        function dismissNotif(id) {
          var el = document.getElementById("notif-" + id);
          fetch("/notifications/" + id + "/read", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": _notifCsrf, "Accept": "application/json" }
          }).catch(function(){});
          animateRemove(el, updateBadge);
        }

        function readAndGo(id, url) {
          // Mark as read (fire-and-forget)
          fetch("/notifications/" + id + "/read", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": _notifCsrf, "Accept": "application/json" }
          }).catch(function(){});

          var dest = (url || "").trim();
          if (!dest || dest === "#" || dest === "") {
            var el = document.getElementById("notif-" + id);
            animateRemove(el, updateBadge);
            return;
          }

          // Fermer le dropdown Bootstrap proprement avant de naviguer
          var bell = document.querySelector('[data-bs-toggle="dropdown"]');
          if (bell) {
            try {
              var bsDropdown = bootstrap.Dropdown.getInstance(bell);
              if (bsDropdown) bsDropdown.hide();
            } catch(e) {}
          }

          // Naviguer après un court délai (laisser le dropdown se fermer)
          setTimeout(function() {
            window.location.href = dest;
          }, 80);
        }

        // Attacher events sur les cartes déjà rendues par Blade
        // Le script est après les cartes dans le DOM — exécution immédiate
        (function attachNotifEvents() {
          document.querySelectorAll('.notif-card').forEach(function(card) {
            var id  = parseInt(card.getAttribute('data-notif-id'));
            var url = card.getAttribute('data-notif-url') || '';

            var inner = card.querySelector('.notif-inner');
            if (inner) {
              inner.addEventListener('click', function(e) {
                if (e.target.closest('.notif-dismiss-btn')) return;
                readAndGo(id, url);
              });
            }
            var btn = card.querySelector('.notif-dismiss-btn');
            if (btn) {
              btn.addEventListener('click', function(e) {
                e.stopPropagation();
                dismissNotif(id);
              });
            }
          });
        })();

        // Fermer le dropdown si on clique en dehors (bootstrap data-bs-auto-close=false)
        document.addEventListener('click', function(e) {
          var dropdownMenu = document.querySelector('.notif-dropdown-menu.show');
          var bell         = document.querySelector('[data-bs-toggle="dropdown"]');
          if (dropdownMenu && !dropdownMenu.contains(e.target) && bell && !bell.contains(e.target)) {
            var bsDropdown = bootstrap.Dropdown.getInstance(bell);
            if (bsDropdown) bsDropdown.hide();
          }
        });

        // ── Auto-polling toutes les 15 secondes ──────────────────────────────
        var _notifKnownIds = new Set(
          Array.from(document.querySelectorAll('.notif-card')).map(function(el) {
            return parseInt(el.id.replace('notif-', ''));
          })
        );
        var _notifGradients = {
          'warning': 'linear-gradient(135deg,#f59e0b,#d97706)',
          'success': 'linear-gradient(135deg,#10b981,#059669)',
          'info':    'linear-gradient(135deg,#3b82f6,#1d4ed8)',
          'primary': 'linear-gradient(135deg,var(--color-primary),var(--color-secondary))',
          'danger':  'linear-gradient(135deg,#ef4444,#b91c1c)',
        };

        function pollNotifications() {
          fetch('/notifications/poll', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _notifCsrf }
          })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            var list   = document.getElementById('notif-list');
            var badge  = document.getElementById('notif-badge');
            var footer = document.getElementById('notif-footer');
            if (!list) return;

            data.notifications.forEach(function(n) {
              if (_notifKnownIds.has(n.id)) return;
              _notifKnownIds.add(n.id);

              var grad = _notifGradients[n.color] || _notifGradients['primary'];
              var bodyHtml = n.body ? '<p class="mb-1" style="font-size:12px;color:#64748b;line-height:1.3;white-space:normal;">' + n.body + '</p>' : '';

              var card = _buildNotifCard(n.id, grad, n.icon, n.title, bodyHtml, n.ago, n.url || '');
              card.style.opacity = '0';
              card.style.transform = 'translateX(-16px)';

              var empty = document.getElementById('notif-empty');
              if (empty) empty.remove();

              list.insertBefore(card, list.firstChild);
              setTimeout(function() {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateX(0)';
              }, 50);
            });

            // Mettre à jour le badge
            var count = data.count;
            if (count > 0) {
              if (badge) {
                badge.textContent = count;
                badge.style.display = '';
              } else {
                var bell = document.querySelector('.nav-item.dropdown .nav-link i.material-symbols-rounded');
                if (bell && bell.parentNode) {
                  var b = document.createElement('span');
                  b.id = 'notif-badge';
                  b.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-gradient-danger';
                  b.style.cssText = 'font-size:8px;padding:3px 5px;';
                  b.textContent = count;
                  bell.parentNode.appendChild(b);
                }
              }
              if (!footer) {
                var f = document.createElement('div');
                f.id = 'notif-footer';
                f.className = 'px-3 py-2 text-center';
                f.style.cssText = 'background:#f8fafc;border-top:1px solid #e2e8f0;';
                f.innerHTML = '<button onclick="markAllRead()" type="button" class="btn btn-sm mb-0 px-3 py-2" style="background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));color:white;border:none;border-radius:8px;font-size:12px;font-weight:600;"><i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">done_all</i>Tout marquer comme lu</button>';
                var dropMenu = list.closest('.dropdown-menu');
                if (dropMenu) dropMenu.appendChild(f);
              }
            } else {
              if (badge) badge.style.display = 'none';
            }
          })
          .catch(function() {});
        }

        setInterval(pollNotifications, 15000);

        function markAllRead() {
          fetch("/notifications/read-all", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": _notifCsrf, "Accept": "application/json" }
          }).catch(function(){});
          var list = document.getElementById("notif-list");
          if (list) {
            var cards = list.querySelectorAll(".notif-card");
            var delay = 0;
            cards.forEach(function(card) {
              setTimeout(function() { animateRemove(card, null); }, delay);
              delay += 80;
            });
            setTimeout(updateBadge, delay + 520);
          }
        }
        </script>


        {{-- User Profile --}}
        {{-- User Profile --}}
        <li class="nav-item dropdown ps-2" style="z-index:10001;">
          <a href="#" class="nav-link text-body p-0 d-flex align-items-center px-3 py-2" 
             style="border-radius:16px; background:#f8fafc; border: 1.5px solid #f1f5f9; transition: all 0.2s ease;"
             onmouseover="this.style.background='#fff'; this.style.borderColor='var(--color-primary)'; this.style.transform='translateY(-1px)'"
             onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#f1f5f9'; this.style.transform='none'"
             data-bs-toggle="dropdown">
            @php
              $roleColors = [
                'super_admin' => ['bg'=>'linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%)','name'=>'Super Admin'],
                'admin'       => ['bg'=>'linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%)','name'=>'Admin'],
                'client'      => ['bg'=>'linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%)','name'=>'Client'],
              ];
              $role     = auth()->user()->role;
              $roleData = $roleColors[$role] ?? ['bg'=>'#6c757d','name'=>'User'];
              $initials = strtoupper(substr(auth()->user()->name, 0, 2));
            @endphp
            <div class="d-flex align-items-center">
              <div class="avatar avatar-sm shadow-sm me-2" style="background:{{ $roleData['bg'] }};overflow:hidden; border-radius:12px !important; width:34px; height:34px;">
                @if(auth()->user()->avatar)
                  <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                @else
                  <span class="text-white text-xs font-weight-bold" style="font-size:11px;">{{ $initials }}</span>
                @endif
              </div>
              <div class="d-none d-lg-block text-start">
                <span class="text-sm font-weight-bold text-dark d-block" style="line-height:1.1; font-size:13px; letter-spacing:-0.01em;">
                  {{ Str::limit(auth()->user()->name, 16) }}
                </span>
                <span class="text-xxs font-weight-bold uppercase" style="line-height:1; color:#94a3b8; font-size:9px; letter-spacing:0.02em;">{{ $roleData['name'] }}</span>
              </div>
              <i class="material-symbols-rounded ms-2" style="font-size:18px; color:#94a3b8;">expand_more</i>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end px-2 py-3 mt-2" style="min-width:280px; border-radius:20px; border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.12);">
            <li class="mb-2 px-2">
              <div class="d-flex align-items-center p-2" style="background:#f8fafc; border-radius:14px;">
                <div class="avatar avatar-md shadow-sm" style="background:{{ $roleData['bg'] }};overflow:hidden; border-radius:12px !important;">
                  @if(auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                @else
                  <span class="text-white font-weight-bold" style="font-size:14px;">{{ $initials }}</span>
                @endif
                </div>
                <div class="ms-3">
                  <h6 class="mb-0 text-sm font-weight-bold" style="letter-spacing:-0.01em;">{{ auth()->user()->name }}</h6>
                  <p class="text-xs text-secondary mb-1" style="font-weight:500;">{{ auth()->user()->email }}</p>
                  <span class="badge" style="background:{{ $roleData['bg'] }}; color:white; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; padding: 4px 10px; border-radius:8px;">
                    {{ $roleData['name'] }}
                  </span>
                </div>
              </div>
            </li>
            <li><hr class="horizontal dark my-2" style="opacity:0.05;"></li>
            <li>
              <a class="dropdown-item border-radius-lg d-flex align-items-center py-2 px-3" href="{{ route('profile.edit') }}" style="border-radius:12px !important; transition: all 0.2s ease;">
                <div style="width:32px; height:32px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                  <i class="material-symbols-rounded text-dark" style="font-size:18px;">person</i>
                </div>
                <span class="text-sm font-weight-bold">Mon profil</span>
              </a>
            </li>
            @if(auth()->user()->role === 'super_admin')
            <li>
              <a class="dropdown-item border-radius-lg d-flex align-items-center py-2 px-3" href="{{ route('super-admin.settings') }}" style="border-radius:12px !important; transition: all 0.2s ease;">
                <div style="width:32px; height:32px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                  <i class="material-symbols-rounded text-dark" style="font-size:18px;">settings</i>
                </div>
                <span class="text-sm font-weight-bold">Paramètres système</span>
              </a>
            </li>
            @endif
            <li><hr class="horizontal dark my-2" style="opacity:0.05;"></li>
            <li>
              <a class="dropdown-item border-radius-lg d-flex align-items-center py-2 px-3"
                 href="{{ route('logout') }}"
                 style="border-radius:12px !important; transition: all 0.2s ease;"
                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <div style="width:32px; height:32px; border-radius:10px; background:#fef2f2; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                  <i class="material-symbols-rounded text-danger" style="font-size:18px;">logout</i>
                </div>
                <span class="text-sm text-danger font-weight-bold">Déconnexion</span>
              </a>
              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </li>
          </ul>
        </li>

        {{-- Mobile Toggle --}}
        <li class="nav-item d-xl-none ps-3">
          <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line bg-dark"></i>
              <i class="sidenav-toggler-line bg-dark"></i>
              <i class="sidenav-toggler-line bg-dark"></i>
            </div>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>

{{-- SEARCH SCRIPT --}}
<script>
(function() {
  const input  = document.getElementById('globalSearch');
  const outer  = document.getElementById('searchResults');
  let   inner  = document.getElementById('searchResultsInner');
  let   timer  = null;
  if (!input || !outer) return;

  // Ensure inner exists (fallback)
  if (!inner) { inner = outer; }

  input.addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    if (q.length < 1) { outer.classList.add('d-none'); inner.innerHTML = ''; return; }

    // Show loading
    inner.innerHTML = `<div class="text-center py-3">
      <div style="width:20px;height:20px;border:2px solid var(--color-primary,#667eea);border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 6px;"></div>
      <p class="text-xs text-secondary mb-0">Recherche en cours...</p>
    </div>`;
    outer.classList.remove('d-none');

    timer = setTimeout(function() {
      fetch('/search?q=' + encodeURIComponent(q), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(data => {
        if (!data.results || data.results.length === 0) {
          inner.innerHTML = `<div class="text-center py-4">
            <i class="material-symbols-rounded" style="font-size:36px;color:#cbd5e1;">search_off</i>
            <p class="text-xs text-secondary mt-2 mb-0">Aucun résultat pour <strong>"${q}"</strong></p>
          </div>`;
        } else {
          // Group by type
          const tickets = data.results.filter(r => r.type === 'ticket');
          const users   = data.results.filter(r => r.type === 'user');
          const others  = data.results.filter(r => r.type !== 'ticket' && r.type !== 'user');

          let html = '';

          const renderItem = r => `
            <a href="${r.url}" class="d-flex align-items-center text-decoration-none px-3 py-2"
               style="border-radius:10px;transition:background 0.15s;gap:10px;"
               onmouseover="this.style.background='rgba(102,126,234,0.07)'"
               onmouseout="this.style.background=''">
              <div style="width:34px;height:34px;border-radius:9px;background:${r.color};flex-shrink:0;
                          display:flex;align-items:center;justify-content:center;
                          box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                <i class="material-symbols-rounded text-white" style="font-size:17px;">${r.icon}</i>
              </div>
              <div style="min-width:0;flex:1;">
                <p class="text-sm font-weight-bold mb-0 text-dark text-truncate">${r.title}</p>
                <p class="text-xs mb-0" style="color:#94a3b8;" >${r.subtitle}</p>
              </div>
              ${r.source === 'glpi' ? '<span style="font-size:9px;background:#f0f4ff;color:#667eea;padding:2px 6px;border-radius:10px;flex-shrink:0;font-weight:700;">GLPI</span>' : ''}
            </a>`;

          if (tickets.length) {
            html += `<div style="padding:6px 12px 2px;"><p class="text-xs font-weight-bold mb-1" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">
              <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">confirmation_number</i>Tickets (${tickets.length})</p></div>`;
            html += tickets.map(renderItem).join('');
          }
          if (users.length) {
            if (tickets.length) html += '<hr style="margin:4px 12px;border-color:#f1f5f9;">';
            html += `<div style="padding:6px 12px 2px;"><p class="text-xs font-weight-bold mb-1" style="color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">
              <i class="material-symbols-rounded me-1" style="font-size:12px;vertical-align:middle;">people</i>Utilisateurs (${users.length})</p></div>`;
            html += users.map(renderItem).join('');
          }
          if (others.length) {
            if (tickets.length || users.length) html += '<hr style="margin:4px 12px;border-color:#f1f5f9;">';
            html += others.map(renderItem).join('');
          }

          inner.innerHTML = html;
        }
        outer.classList.remove('d-none');
      })
      .catch(() => {
        inner.innerHTML = `<div class="text-center py-3">
          <p class="text-xs text-secondary mb-0">Erreur de connexion</p>
        </div>`;
      });
    }, 280);
  });

  document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !outer.contains(e.target)) {
      outer.classList.add('d-none');
    }
  });

  input.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { outer.classList.add('d-none'); input.blur(); }
  });
})();
</script>