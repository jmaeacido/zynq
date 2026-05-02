@extends('layouts.app', ['heading' => $category->exists ? 'Edit Product Category' : 'New Product Category'])

@section('content')
<form method="post" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card">
    @csrf
    @if ($category->exists) @method('put') @endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $category->tenant_id) === $tenant->id)>{{ $tenant->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $category->name) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Code</label><input name="code" class="form-control" value="{{ old('code', $category->code) }}" required></div>
            <div class="col-md-8 mb-3"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $category->description) }}"></div>
            <div class="col-md-12 mb-3"><div class="form-check"><input type="hidden" name="active" value="0"><input id="active" name="active" value="1" type="checkbox" class="form-check-input" @checked(old('active', $category->active ?? true))><label class="form-check-label" for="active">Active category</label></div></div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Category</button></div>
</form>
@endsection
