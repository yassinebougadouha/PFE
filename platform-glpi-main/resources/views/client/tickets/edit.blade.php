@extends('layouts.dashboard')
@section('title','Modifier le ticket')
@section('page-title','Modifier le ticket')

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
         style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);">
      <div class="d-flex align-items-center">
        <div class="avatar avatar-lg bg-white border-radius-lg p-2 me-3 shadow">
          <i class="material-symbols-rounded" style="font-size:30px;color:var(--color-primary);">edit</i>
        </div>
        <div>
          <h5 class="text-white font-weight-bolder mb-0">Modifier le ticket #{{ $ticket->id }}</h5>
          <p class="text-white text-sm mb-0 opacity-8">Vous pouvez modifier uniquement les tickets en attente</p>
        </div>
      </div>
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

        <form method="POST" action="{{ route('tickets.update', $ticket->id) }}">
          @csrf
          @method('PUT')

          {{-- TITRE --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              TITRE <span class="text-danger">*</span>
            </label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $ticket->title) }}"
                   placeholder="Brève description du problème">
            @error('title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- DESCRIPTION --}}
          <div class="mb-4">
            <label class="form-label text-xs font-weight-bold text-uppercase text-secondary">
              DESCRIPTION DÉTAILLÉE <span class="text-danger">*</span>
            </label>
            <textarea name="content" rows="6"
                      class="form-control @error('content') is-invalid @enderror"
                      placeholder="Expliquez votre problème en détail">{{ old('content', $ticket->description) }}</textarea>
            @error('content')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          {{-- ACTIONS --}}
          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">close</i>
              Annuler
            </a>
            <button type="submit" class="btn text-white"
                    style="background: linear-gradient(135deg,var(--color-primary),var(--color-secondary));">
              <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i>
              Enregistrer les modifications
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

@endsection