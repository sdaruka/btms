<!-- resources/views/components/metric-card.blade.php -->
<div class="card shadow-sm mb-3" id="card-dashboard">
    <div class="card-body">
        <h6 class="text-muted">{{ $title }}</h6>
        <h3 class="fw-bold mb-0">{{ $value }}</h3>
        @if ($note)
            <small class="text-success">{{ $note }}</small>
        @endif
    </div>
</div>