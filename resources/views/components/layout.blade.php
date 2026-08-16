@extends('layouts.app')

@section('title', $title ?? 'Sistema de Facturación')
@section('page-title', $pageTitle ?? $title ?? 'Dashboard')

@section('content')
    {{ $slot }}
@endsection