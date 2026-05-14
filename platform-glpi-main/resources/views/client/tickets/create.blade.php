@extends('layouts.dashboard')
@section('title','Créer un ticket')
@section('page-title','Créer un ticket')

@section('content')

<div class="row mb-3">
  <div class="col-12">
    <a href="{{ route('tickets.index') }}" class="btn btn-link text-secondary ps-0">
      <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">arrow_back</i>
      Retour à mes tickets
    </a>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 mx-auto">

    {{-- HEADER --}}
    <div class="card shadow-lg border-radius-lg mb-4 p-3"
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="d-flex align-items-center">
        <div class="avatar avatar-lg bg-white border-radius-lg p-2 me-3 shadow">
          <i class="material-symbols-rounded" style="font-size:30px;color:#667eea;">confirmation_number</i>
        </div>
        <div>
          <h5 class="text-white font-weight-bolder mb-0">Créer un ticket</h5>
          <p class="text-white text-sm mb-0 opacity-8">Décrivez votre problème, notre IA vous aide à le formuler</p>
        </div>
      </div>
    </div>

    {{-- 🤖 BADGE IA REAL-TIME — ✅ sans bouton Appliquer --}}
    <div id="aiBadge" class="card mb-4 d-none" style="border-left:4px solid #667eea;">
      <div class="card-body py-3 px-4">
        <div class="d-flex align-items-start gap-2">
          <i class="material-symbols-rounded mt-1" style="font-size:20px;color:#667eea;flex-shrink:0;">smart_toy</i>
          <div class="flex-grow-1">
            <p class="mb-1 text-xs font-weight-bold text-uppercase text-secondary">Classification IA (Groq LLM)</p>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span id="aiCategoryBadge" class="badge badge-sm text-white" style="background:#667eea;font-size:11px;padding:4px 10px;"></span>
              <span id="aiPriorityBadge" class="badge badge-sm text-white" style="font-size:11px;padding:4px 10px;"></span>
              <span id="aiConfidence" class="text-xs text-secondary"></span>
              {{-- ✅ Feedback appliqué --}}
              <span id="aiAppliedBadge" class="text-xs text-success d-none">
                <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">check_circle</i>
                Appliqué automatiquement
              </span>
            </div>
          </div>
        </div>

        {{-- Solutions LLM --}}
        <div id="aiSolutions" class="mt-3 d-none">
          <p class="text-xs font-weight-bold text-secondary mb-2">
            <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">lightbulb</i>
            Solutions suggérées par l'IA :
          </p>
          <div id="aiSolutionsList"></div>
        </div>
      </div>
    </div>

    {{-- TICKETS SIMILAIRES --}}
    <div id="similarTickets" class="card mb-4 border border-warning d-none">
      <div class="card-header pb-0 pt-3 px-4">
        <div class="d-flex align-items-center">
          <i class="material-symbols-rounded text-warning me-2">lightbulb</i>
          <h6 class="mb-0 font-weight-bold text-warning">Tickets similaires trouvés</h6>
        </div>
        <p class="text-xs text-muted mb-0">Votre problème a peut-être déjà été résolu</p>
      </div>
      <div class="card-body px-4 pb-3" id="similarList"></div>
    </div>

    {{-- FORMULAIRE --}}
    <div class="card">
      <div class="card-body px-4 pb-4 pt-4">

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
          @foreach($errors->all() as $e)
            <p class="text-xs mb-0">{{ $e }}</p>
          @endforeach
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="ticketForm">
          @csrf

          {{-- Champs cachés remplis par l'IA --}}
          <input type="hidden" name="urgency"  id="hiddenUrgency"  value="3">
          <input type="hidden" name="impact"   id="hiddenImpact"   value="3">
          <input type="hidden" name="priority" id="hiddenPriority" value="3">

          {{-- MOTIF --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Motif <span class="text-danger">*</span>
            </label>
            <select name="category" id="categorySelect" class="form-control form-select"
                    style="height:45px;border:1px solid #d2d6da;border-radius:8px;" required>
              <option value="">-- Sélectionnez un motif --</option>
              <option value="incident_technique"  {{ old('category')=='incident_technique'?'selected':'' }}>🔴 Incident technique</option>
              <option value="integration_api"     {{ old('category')=='integration_api'?'selected':'' }}>🔵 Intégration API SMS</option>
              <option value="facturation"         {{ old('category')=='facturation'?'selected':'' }}>🟡 Facturation & Commande</option>
              <option value="plateforme"          {{ old('category')=='plateforme'?'selected':'' }}>🟢 Plateforme L2T</option>
              <option value="paiement_mobile"     {{ old('category')=='paiement_mobile'?'selected':'' }}>🟠 Paiement Mobile</option>
              <option value="autre"               {{ old('category')=='autre'?'selected':'' }}>⚪ Autre demande</option>
            </select>

            {{-- Suggestions par catégorie --}}
            <div id="categorySuggestion" class="mt-2 d-none p-3 border-radius-md"
                 style="background:#f0f4ff;border-left:3px solid #667eea;">
              <p class="text-xs font-weight-bold mb-1" style="color:#667eea;">
                <i class="material-symbols-rounded me-1" style="font-size:14px;vertical-align:middle;">tips_and_updates</i>
                Exemples :
              </p>
              <ul id="suggestionList" class="text-xs text-secondary mb-0 ps-3"></ul>
            </div>
          </div>

          {{-- TITRE --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Titre <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-outline {{ old('title') ? 'is-filled' : '' }}">
              <label class="form-label">Brève description du problème</label>
              <input type="text" name="title" id="ticketTitle" class="form-control"
                     value="{{ old('title') }}" required autocomplete="off">
            </div>
            @error('title')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- DESCRIPTION --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Description détaillée <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-outline {{ old('content') ? 'is-filled' : '' }}">
              <label class="form-label">Expliquez votre problème en détail</label>
              <textarea name="content" id="ticketContent" class="form-control" rows="6" required>{{ old('content') }}</textarea>
            </div>
            {{-- Bouton reformulation LLM --}}
            <button type="button" id="reformulateBtn" class="btn btn-sm btn-outline-secondary mt-2 mb-0 d-none"
                    style="font-size:11px;" onclick="reformulateDescription()">
              <i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">auto_fix_high</i>
              Améliorer avec l'IA
            </button>
            @error('content')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
          </div>

          {{-- ✅ FICHIERS — multi-sélection accumulative --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              Pièces jointes (optionnel)
            </label>
            <div id="dropZone" class="border-radius-lg p-4 text-center"
                 style="border:2px dashed #667eea;background:#f8f9ff;cursor:pointer;transition:all 0.2s;"
                 ondragover="event.preventDefault();this.style.background='#e8edff';"
                 ondragleave="this.style.background='#f8f9ff';"
                 ondrop="handleDrop(event)"
                 onclick="document.getElementById('fileInput').click()">
              <i class="material-symbols-rounded text-primary" style="font-size:36px;">upload_file</i>
              <p class="text-sm text-secondary mb-1 mt-2">
                Glissez-déposez vos fichiers ici ou <span class="text-primary font-weight-bold">sélectionnez</span>
              </p>
              <p class="text-xs text-secondary mb-0">PDF, images, documents (max 5MB par fichier)</p>
              {{-- ✅ multiple + pas de name ici, on gère via JS --}}
              <input type="file" id="fileInput" multiple class="d-none"
                     accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.txt"
                     onchange="addFiles(this.files)">
            </div>

            {{-- ✅ Liste des fichiers sélectionnés avec bouton supprimer --}}
            <div id="fileList" class="mt-2"></div>

            {{-- ✅ Inputs cachés générés dynamiquement --}}
            <div id="fileInputsContainer"></div>
          </div>

          {{-- BOUTONS --}}
          <hr class="horizontal dark my-3">
          <div class="d-flex justify-content-between">
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary mb-0">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">close</i>
              Annuler
            </a>
            <button type="submit" class="btn mb-0 text-white"
                    style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">send</i>
              Soumettre le ticket
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<script>
var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var aiTimer = null;
var searchTimer = null;
var lastAiResult = null;

var PRIORITY_COLORS = {1:'#6c757d',2:'#17a2b8',3:'#ffc107',4:'#dc3545',5:'#343a40'};

// ═══════════════════════════════════════════════════════════════
// ✅ GESTION FICHIERS — accumulative (plusieurs ajouts)
// ═══════════════════════════════════════════════════════════════
var allFiles = new DataTransfer(); // stocke tous les fichiers accumulés

function addFiles(newFiles) {
  Array.from(newFiles).forEach(function(f) {
    // éviter les doublons par nom
    var exists = false;
    for (var i = 0; i < allFiles.files.length; i++) {
      if (allFiles.files[i].name === f.name) { exists = true; break; }
    }
    if (!exists) allFiles.items.add(f);
  });
  // réinitialiser l'input pour permettre de re-sélectionner le même fichier
  document.getElementById('fileInput').value = '';
  renderFileList();
}

function removeFile(index) {
  var newDt = new DataTransfer();
  Array.from(allFiles.files).forEach(function(f, i) {
    if (i !== index) newDt.items.add(f);
  });
  allFiles = newDt;
  renderFileList();
}

function renderFileList() {
  var list = document.getElementById('fileList');
  var container = document.getElementById('fileInputsContainer');

  if (allFiles.files.length === 0) {
    list.innerHTML = '';
    container.innerHTML = '';
    return;
  }

  var html = '<div class="p-2 border-radius-md mt-1" style="background:#f8f9fa;">';
  html += '<p class="text-xs font-weight-bold text-secondary mb-2">' + allFiles.files.length + ' fichier(s) sélectionné(s) :</p>';

  Array.from(allFiles.files).forEach(function(f, i) {
    var size = f.size < 1024*1024
      ? (f.size/1024).toFixed(0) + ' KB'
      : (f.size/1024/1024).toFixed(1) + ' MB';
    var icon = f.type.startsWith('image/') ? 'image' : (f.type === 'application/pdf' ? 'picture_as_pdf' : 'attach_file');
    html += '<div class="d-flex align-items-center p-2 mb-1 border-radius-md" style="background:#fff;border:1px solid #e9ecef;">'
      + '<i class="material-symbols-rounded text-primary me-2" style="font-size:18px;">' + icon + '</i>'
      + '<span class="text-xs font-weight-bold flex-grow-1">' + f.name + '</span>'
      + '<span class="text-xs text-secondary me-3">' + size + '</span>'
      + '<button type="button" class="btn btn-sm mb-0 btn-outline-danger py-0 px-1" '
      + 'style="font-size:10px;" onclick="removeFile(' + i + ')" title="Supprimer">'
      + '<i class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">close</i>'
      + '</button>'
      + '</div>';
  });
  html += '</div>';
  list.innerHTML = html;

  // ✅ Mettre à jour le vrai input file avec tous les fichiers accumulés
  // On utilise un seul input file caché avec les fichiers via DataTransfer
  container.innerHTML = '';
  // Créer un input caché qui contient tous les fichiers
  var realInput = document.createElement('input');
  realInput.type = 'file';
  realInput.name = 'attachments[]';
  realInput.multiple = true;
  realInput.style.display = 'none';
  realInput.id = 'realFileInput';
  container.appendChild(realInput);
  // Assigner les fichiers accumulés
  document.getElementById('realFileInput').files = allFiles.files;
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.background = '#f8f9ff';
  addFiles(e.dataTransfer.files);
}

// ═══════════════════════════════════════════════════════════════
// ✅ AVANT SUBMIT — synchroniser les fichiers dans le form
// ═══════════════════════════════════════════════════════════════
document.getElementById('ticketForm').addEventListener('submit', function() {
  var realInput = document.getElementById('realFileInput');
  if (realInput) {
    realInput.files = allFiles.files;
  }
});

// ═══════════════════════════════════════════════════════════════
// 🤖 Classification LLM — ✅ s'applique automatiquement
// ═══════════════════════════════════════════════════════════════
function runAiClassify() {
  var title = document.getElementById('ticketTitle').value.trim();
  var desc  = document.getElementById('ticketContent').value.trim();
  if (title.length < 5) {
    document.getElementById('aiBadge').classList.add('d-none');
    return;
  }

  fetch('/tickets/classify', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({title: title, description: desc})
  })
  .then(r => r.json())
  .then(data => {
    if (!data.available) { document.getElementById('aiBadge').classList.add('d-none'); return; }
    lastAiResult = data;

    document.getElementById('aiCategoryBadge').textContent = data.category_label;
    document.getElementById('aiPriorityBadge').textContent = 'Priorité ' + data.priority_label;
    document.getElementById('aiPriorityBadge').style.background = PRIORITY_COLORS[data.priority] || '#667eea';
    document.getElementById('aiConfidence').textContent = 'Confiance: ' + data.confidence + '%';
    document.getElementById('aiBadge').scrollIntoView({ 
    behavior: 'smooth', 
    block: 'nearest' 
});
    if (data.solutions && data.solutions.length > 0) {
      var html = data.solutions.map(s =>
        '<div class="p-2 mb-1 border-radius-md" style="background:#f0f4ff;border-left:2px solid #667eea;">' +
        '<p class="text-xs mb-0">' + s + '</p></div>'
      ).join('');
      document.getElementById('aiSolutionsList').innerHTML = html;
      document.getElementById('aiSolutions').classList.remove('d-none');
    }

    document.getElementById('aiBadge').classList.remove('d-none');
    document.getElementById('reformulateBtn').classList.remove('d-none');

    // ✅ Appliquer automatiquement sans bouton
    applyAiClassification(true);
  })
  .catch(() => document.getElementById('aiBadge').classList.add('d-none'));
}

// ✅ auto=true → pas de feedback visuel fort (juste badge discret)
function applyAiClassification(auto) {
  if (!lastAiResult) return;

  document.getElementById('hiddenPriority').value = lastAiResult.priority;
  document.getElementById('hiddenUrgency').value  = lastAiResult.urgency || lastAiResult.priority;
  document.getElementById('hiddenImpact').value   = Math.min(lastAiResult.priority + 1, 5);

  var select = document.getElementById('categorySelect');
  if (lastAiResult.category && select) {
    select.value = lastAiResult.category;
    select.dispatchEvent(new Event('change'));
  }

  // Montrer le badge "Appliqué automatiquement"
  var appliedBadge = document.getElementById('aiAppliedBadge');
  appliedBadge.classList.remove('d-none');
}

// ═══════════════════════════════════════════════════════════════
// 🤖 Reformulation — ✅ description seulement (sans Titre:)
// ═══════════════════════════════════════════════════════════════
function reformulateDescription() {
  var title = document.getElementById('ticketTitle').value.trim();
  var desc  = document.getElementById('ticketContent').value.trim();
  if (!desc) return;

  var btn = document.getElementById('reformulateBtn');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> En cours...';
  btn.disabled  = true;

  fetch('/tickets/reformulate', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({title: title, description: desc})
  })
  .then(r => r.json())
  .then(data => {
    if (data.available && data.reformulated) {
      // ✅ Nettoyer le préfixe "Titre: ..." et "Description: ..." si présent
      var cleaned = data.reformulated;

      // Supprimer ligne "Titre : ..."
      cleaned = cleaned.replace(/^titre\s*[:：]\s*.+\n?/im, '');
      // Supprimer préfixe "Description : " ou "Description: "
      cleaned = cleaned.replace(/^description\s*[:：]\s*/im, '');
      // Supprimer "Titre : xxx\nDescription : " inline
      cleaned = cleaned.replace(/titre\s*[:：][^\n]+\n?description\s*[:：]\s*/i, '');
      // Trim final
      cleaned = cleaned.trim();

      document.getElementById('ticketContent').value = cleaned;
      var group = document.getElementById('ticketContent').closest('.input-group');
      if (group) group.classList.add('is-filled');
    }
    btn.innerHTML = '<i class="material-symbols-rounded me-1" style="font-size:13px;vertical-align:middle;">auto_fix_high</i> Améliorer avec l\'IA';
    btn.disabled  = false;
  })
  .catch(() => {
    btn.innerHTML = 'Améliorer avec l\'IA';
    btn.disabled  = false;
  });
}

// ═══════════════════════════════════════════════════════════════
// Tickets similaires
// ═══════════════════════════════════════════════════════════════
document.getElementById('ticketTitle').addEventListener('input', function() {
  clearTimeout(aiTimer);
  clearTimeout(searchTimer);
  var q = this.value.trim();

  aiTimer = setTimeout(runAiClassify, 700);

  if (q.length < 4) { document.getElementById('similarTickets').classList.add('d-none'); return; }
  searchTimer = setTimeout(() => {
    fetch('/tickets/similar?q=' + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
      if (data.tickets && data.tickets.length > 0) {
        var seen = {}, unique = [];
        data.tickets.forEach(function(t) {
          var k = t.title.trim().toLowerCase();
          if (!seen[k]) { seen[k] = true; unique.push(t); }
        });

        var html = unique.map(function(t) {
          var desc = t.description && t.description.trim().length > 2
            ? '<p class="text-xs text-secondary mb-2" style="line-height:1.5;">' + t.description + '</p>' : '';
          var sol = t.solution
            ? '<div class="d-flex align-items-start gap-2 p-2 border-radius-md" style="background:#e8f5e9;border-left:3px solid #38a169;"><i class="material-symbols-rounded" style="font-size:15px;color:#38a169;flex-shrink:0;">check_circle</i><p class="text-xs mb-0" style="color:#276749;"><strong>Solution:</strong> ' + t.solution + '</p></div>' : '';
          var badge = (t.source === 'glpi')
            ? '<span style="font-size:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2px 8px;border-radius:20px;margin-left:8px;">GLPI</span>' : '';
          return '<div class="mb-2 p-3 border-radius-md" style="background:#fffbf0;border-left:3px solid #ffc107;">'
            + '<div class="d-flex align-items-center mb-1"><p class="text-sm font-weight-bold mb-0">' + t.title + '</p>' + badge + '</div>'
            + desc + sol + '</div>';
        }).join('');

        document.getElementById('similarList').innerHTML = html;
        document.getElementById('similarTickets').classList.remove('d-none');
      } else {
        document.getElementById('similarTickets').classList.add('d-none');
      }
    }).catch(() => {});
  }, 500);
});

document.getElementById('ticketContent').addEventListener('input', function() {
  clearTimeout(aiTimer);
  aiTimer = setTimeout(runAiClassify, 1000);
});

// ═══════════════════════════════════════════════════════════════
// Suggestions par catégorie
// ═══════════════════════════════════════════════════════════════
var suggestions = {
  'incident_technique': ["L'API SMS ne répond plus / timeout","Messages SMS non délivrés","Erreur 500 lors de l'envoi en masse","Interruption du service SMS 2 TV"],
  'integration_api':    ["Problème d'authentification API (token invalide)","Erreur lors de l'appel API: paramètre invalide","Limite de requêtes API atteinte"],
  'facturation':        ["Demande de facture pour le mois en cours","Crédit SMS épuisé, comment recharger?","Demande de devis pour envoi SMS"],
  'plateforme':         ["Impossible de se connecter à Didon SMS","Campagne SMS planifiée non envoyée","SMS STOP non pris en compte"],
  'paiement_mobile':    ["Transaction de micropaiement refusée","Un client n'a pas été débité","Problème de monétisation sur contenu"],
  'autre':              ["Demande de démonstration","Question sur les tarifs SMS","Demande de partenariat avec L2T"]
};

document.getElementById('categorySelect').addEventListener('change', function() {
  var val = this.value;
  var box  = document.getElementById('categorySuggestion');
  var list = document.getElementById('suggestionList');
  if (val && suggestions[val]) {
    list.innerHTML = suggestions[val].map(s =>
      '<li class="mb-1" style="cursor:pointer;" onclick="useSuggestion(\'' + s.replace(/'/g,"\\'") + '\')">' +
      '→ <span class="text-primary">' + s + '</span></li>'
    ).join('');
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
});

function useSuggestion(text) {
  var inp = document.getElementById('ticketTitle');
  inp.value = text;
  var group = inp.closest('.input-group');
  if (group) group.classList.add('is-filled');
  inp.dispatchEvent(new Event('input'));
  inp.focus();
}

// ═══════════════════════════════════════════════════════════════
// Floating labels
// ═══════════════════════════════════════════════════════════════
function bindFloating(el) {
  var group = el.closest('.input-group');
  if (!group) return;
  var toggle = () => el.value.trim() ? group.classList.add('is-filled') : group.classList.remove('is-filled');
  el.addEventListener('input', toggle);
  el.addEventListener('blur', toggle);
  toggle();
}

document.addEventListener('DOMContentLoaded', function() {
  bindFloating(document.getElementById('ticketTitle'));
  document.querySelectorAll('textarea.form-control').forEach(bindFloating);
});
</script>

@endsection