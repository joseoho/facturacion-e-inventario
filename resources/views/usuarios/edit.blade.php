@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('page-title', 'Editar Usuario')

@section('content')
<div class="stat-card" style="max-width: 900px;">
    <h5 class="fw-bold mb-1">Editar Usuario</h5>
    <p class="text-muted small mb-4">{{ $user->email }}</p>
    @include('usuarios._form')
</div>
@endsection
