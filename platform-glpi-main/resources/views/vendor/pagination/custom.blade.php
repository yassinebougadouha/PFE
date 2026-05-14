@if ($paginator->hasPages())
<nav aria-label="Pagination" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 px-2">

  {{-- Info results --}}
  <p class="text-xs text-secondary mb-0">
    Affichage <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
    sur <strong>{{ $paginator->total() }}</strong> résultats
  </p>

  {{-- Boutons pagination --}}
  <ul class="pagination mb-0" style="gap:4px;">

    {{-- Précédent --}}
    @if ($paginator->onFirstPage())
      <li class="page-item disabled">
        <span class="page-link border-radius-md px-3 py-2"
              style="font-size:13px;color:#adb5bd;border:1px solid #e9ecef;background:#f8f9fa;cursor:not-allowed;">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_left</i>
        </span>
      </li>
    @else
      <li class="page-item">
        <a class="page-link border-radius-md px-3 py-2"
           href="{{ $paginator->previousPageUrl() }}"
           style="font-size:13px;border:1px solid #e9ecef;color:var(--color-primary);background:white;transition:all 0.2s;"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.color='white';"
           onmouseout="this.style.background='white';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_left</i>
        </a>
      </li>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
      @if (is_string($element))
        <li class="page-item disabled">
          <span class="page-link border-radius-md px-3 py-2"
                style="font-size:13px;border:1px solid #e9ecef;background:#f8f9fa;color:#adb5bd;">
            …
          </span>
        </li>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <li class="page-item active">
              <span class="page-link border-radius-md px-3 py-2"
                    style="font-size:13px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));
                           border:none;color:white;font-weight:600;
                           box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                {{ $page }}
              </span>
            </li>
          @else
            <li class="page-item">
              <a class="page-link border-radius-md px-3 py-2"
                 href="{{ $url }}"
                 style="font-size:13px;border:1px solid #e9ecef;color:#495057;background:white;transition:all 0.2s;"
                 onmouseover="this.style.background='#f0f4ff';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.borderColor='#c5cbe8';"
                 onmouseout="this.style.background='white';this.style.color='#495057';this.style.borderColor='#e9ecef';">
                {{ $page }}
              </a>
            </li>
          @endif
        @endforeach
      @endif
    @endforeach

    {{-- Suivant --}}
    @if ($paginator->hasMorePages())
      <li class="page-item">
        <a class="page-link border-radius-md px-3 py-2"
           href="{{ $paginator->nextPageUrl() }}"
           style="font-size:13px;border:1px solid #e9ecef;color:var(--color-primary);background:white;transition:all 0.2s;"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();this.style.color='white';"
           onmouseout="this.style.background='white';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_right</i>
        </a>
      </li>
    @else
      <li class="page-item disabled">
        <span class="page-link border-radius-md px-3 py-2"
              style="font-size:13px;color:#adb5bd;border:1px solid #e9ecef;background:#f8f9fa;cursor:not-allowed;">
          <i class="material-symbols-rounded" style="font-size:16px;vertical-align:middle;">chevron_right</i>
        </span>
      </li>
    @endif

  </ul>
</nav>
@endif