@extends('layouts.app')

@section('title', 'Nueva Categoría de Egreso')
@section('page-title', 'Nueva Categoría de Egreso')

@section('content')
<div class="stat-card">
    @include('categorias-egreso._form')
</div>
@endsection
