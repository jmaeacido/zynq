<?php

namespace App\Domains\Branches\Services;

use App\Domains\Branches\Models\Branch;
use Illuminate\Support\Facades\DB;

class BranchService
{
    public function create(array $data): Branch
    {
        return DB::transaction(fn () => Branch::create($data));
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $branch->update($data);

            return $branch->refresh();
        });
    }
}
