@extends('layouts.app')

@section('title', 'Nuevo Usuario')
@section('page-title', 'Nuevo Usuario')

@section('content')
<div class="stat-card" style="max-width: 900px;">
    <h5 class="fw-bold mb-4">Nuevo Usuario</h5>
    @include('usuarios._form')
</div>
@endsection
