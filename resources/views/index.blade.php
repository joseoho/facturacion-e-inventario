@extends('layouts.app')

@section('content')


<style>
    .background {
        /* background-image: url('ruta/a/tu/imagen.jpg'); Cambia esto por la ruta de tu imagen */
        background-size: cover; 
        background-position: center; /* Centra la imagen */
        height: 100vh; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        color: white; 
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); 
    }
</style>

<div class="background">
    <h1>FINCA: GESTION DE FINCAS SAAS/INQUILINOS</h1>
</div>
@endsection