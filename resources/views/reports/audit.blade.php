@extends('layouts.app', ['heading' => 'Audit Trail Report'])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>User</th><th>Module</th><th>Action</th><th>Record</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->created_at->format('Y-m-d H:i') }}</td><td>{{ $row->user_id }}</td><td>{{ $row->module }}</td><td>{{ $row->action }}</td><td>{{ class_basename($row->record_type) }} #{{ $row->record_id }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No audit rows.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $rows->links() }}
@endsection
