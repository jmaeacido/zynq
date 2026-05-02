<?php

namespace App\Domains\Terminals\Services;

use App\Domains\Terminals\Models\Terminal;
use Illuminate\Support\Facades\DB;

class TerminalService
{
    public function create(array $data): Terminal
    {
        return DB::transaction(fn () => Terminal::create($data));
    }

    public function update(Terminal $terminal, array $data): Terminal
    {
        return DB::transaction(function () use ($terminal, $data) {
            $terminal->update($data);

            return $terminal->refresh();
        });
    }
}
