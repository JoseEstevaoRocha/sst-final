@extends('layouts.app')
@section('title','Matriz de Risco')
@section('content')
<div class="page-header"><div><h1 class="page-title">Matriz de Risco</h1><p class="page-sub">Probabilidade × Severidade por GHE</p></div></div>
<div class="card mb-20">
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:2px;padding:4px;background:var(--border)">
        @php $labels=['Baixo','Médio','Alto','Crítico']; @endphp
        @for($s=5;$s>=1;$s--)
        @for($p=1;$p<=5;$p++)
        @php $v=$p*$s; $c=$v>=15?'danger':($v>=8?'warning':($v>=4?'info':'success')); @endphp
        <div style="padding:14px;text-align:center;background:rgba(var(--{{ $c }}-rgb,0,0,0),.1);border:1px solid var(--border)">
            <div class="font-bold" style="font-size:18px">{{ $v }}</div>
            <div class="text-10 text-muted">P{{ $p }}×S{{ $s }}</div>
        </div>
        @endfor
        @endfor
    </div>
    <div class="flex gap-16 mt-14" style="font-size:12px">
        <span><span class="badge badge-success">1–3</span> Baixo</span>
        <span><span class="badge badge-info">4–7</span> Médio</span>
        <span><span class="badge badge-warning">8–14</span> Alto</span>
        <span><span class="badge badge-danger">15–25</span> Crítico</span>
    </div>
</div>
<div class="card p-0">
    <div class="table-wrap"><table class="table">
    <thead><tr><th>GHE</th><th>RISCO</th><th>CATEGORIA</th><th>P</th><th>S</th><th>NÍVEL</th></tr></thead>
    <tbody>
    @forelse($ghes as $g)
        @forelse($g->riscos as $r)
        <tr>
            <td class="font-bold text-12">{{ $g->codigo }} — {{ $g->nome }}</td>
            <td class="text-13 font-bold">{{ $r->nome }}</td>
            <td class="text-12">{{ ucfirst($r->categoria) }}</td>
            <td class="font-bold">{{ $r->pivot->probabilidade }}</td>
            <td class="font-bold">{{ $r->pivot->severidade }}</td>
            <td><span class="badge badge-{{ ['Baixo'=>'success','Médio'=>'info','Alto'=>'warning','Crítico'=>'danger'][$r->pivot->nivel_risco]??'secondary' }}">{{ $r->pivot->nivel_risco }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-muted text-12" style="padding:8px 14px;font-style:italic">{{ $g->codigo }} — sem riscos vinculados</td></tr>
        @endforelse
    @empty
    <tr><td colspan="6"><div class="empty-state py-24"><p>Nenhum GHE cadastrado</p></div></td></tr>
    @endforelse
    </tbody></table>
</div>
</div>
@endsection
