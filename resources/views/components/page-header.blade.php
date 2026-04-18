<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if(isset($sub))<p class="page-sub">{{ $sub }}</p>@endif
    </div>
    @if(isset($actions))<div class="page-actions">{{ $actions }}</div>@endif
</div>
