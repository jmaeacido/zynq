<?php

namespace App\Domains\Branches\Http\Controllers;

use App\Domains\Branches\Http\Requests\StoreBranchRequest;
use App\Domains\Branches\Http\Requests\UpdateBranchRequest;
use App\Domains\Branches\Models\Branch;
use App\Domains\Branches\Services\BranchService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $branches = $context->scopeForUser(Branch::with('tenant')->latest(), $request->user())->paginate(20);

        return view('branches.index', ['branches' => $branches]);
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('branches.form', [
            'branch' => new Branch(),
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
        ]);
    }

    public function store(StoreBranchRequest $request, BranchService $service): RedirectResponse
    {
        $branch = $service->create($request->validated());

        return redirect()->route('branches.edit', $branch)->with('status', 'Branch created.');
    }

    public function edit(Request $request, Branch $branch, TenantContext $context): View
    {
        return view('branches.form', [
            'branch' => $branch,
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, BranchService $service): RedirectResponse
    {
        $service->update($branch, $request->validated());

        return back()->with('status', 'Branch updated.');
    }
}
