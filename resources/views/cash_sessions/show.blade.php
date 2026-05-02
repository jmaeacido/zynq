@extends('layouts.app', ['heading' => 'Cash Session'])

@section('content')
<div class="mb-3">
    <a class="btn btn-outline-primary" href="{{ route('readings.x', $session) }}">X-Reading</a>
    <a class="btn btn-outline-primary" href="{{ route('readings.cashier', $session) }}">Cashier Reading</a>
    <a class="btn btn-outline-primary" href="{{ route('readings.terminal', $session) }}">Terminal Accountability</a>
    @if ($session->status === 'closed')
        <a class="btn btn-outline-primary" href="{{ route('readings.z', $session) }}">Z-Reading</a>
    @endif
</div>
<div class="card">
    <div class="card-body">
        @include('readings.partials.summary', ['reading' => $reading])
        @if ($session->status === 'open')
            <form method="post" action="{{ route('cash-sessions.close', $session) }}" class="mt-3">
                @csrf
                <label class="form-label">Actual cash</label>
                <input name="actual_cash" type="number" min="0" step="0.01" class="form-control mb-3" required>
                <button class="btn btn-primary">Close Session</button>
            </form>
        @endif
    </div>
</div>
@endsection
