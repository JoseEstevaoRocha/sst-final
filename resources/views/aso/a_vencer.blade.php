@extends('layouts.app')
@section('title','ASO — a_vencer')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">ASO — {{ ucfirst(str_replace('_',' ','a_vencer')) }}</h1></div>
    <a href="{{ route('asos.index') }}" class="btn btn-secondary">← Voltar</a>
</div>
@include('aso._table', ['asos' => isset($asos) ? $asos : (isset($pendentes) ? $pendentes : collect())])
@endsection
