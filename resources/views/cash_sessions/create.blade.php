@extends('layouts.app', ['heading' => 'Open Cash Session'])

@section('content')
<form method="post" action="{{ route('cash-sessions.store') }}" class="card">
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Terminal</label>
                <select name="terminal_id" class="form-select" required>
                    @foreach ($terminals as $terminal)
                        <option value="{{ $terminal->id }}">{{ $terminal->terminal_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Opening cash</label>
                <input name="opening_cash" type="number" min="0" step="0.01" class="form-control" required>
            </div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Open Session</button></div>
</form>
@endsection
