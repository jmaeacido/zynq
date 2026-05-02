@extends('layouts.app', ['heading' => 'Product Categories'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('categories.create') }}">New Category</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Category</th><th>Tenant</th><th>Code</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>{{ $category->name }}<br><span class="text-muted">{{ $category->description }}</span></td>
                    <td>{{ $category->tenant->business_name }}</td>
                    <td>{{ $category->code }}</td>
                    <td><span class="badge bg-{{ $category->active ? 'success' : 'secondary' }}">{{ $category->active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('categories.edit', $category) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No categories yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $categories->links() }}
@endsection
