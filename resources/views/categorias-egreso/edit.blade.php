@extends('layouts.app')

@section('title', 'Editar Categoría de Egreso')
@section('page-title', 'Editar Categoría de Egreso')

@section('content')
<div class="stat-card">
    @include('categorias-egreso._form')
</div>
@endsection
