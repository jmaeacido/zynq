<?php

namespace App\Domains\Invoicing\Services;

use App\Domains\Invoicing\Models\InvoiceSequence;
use App\Domains\Terminals\Models\Terminal;
use Illuminate\Database\QueryException;

class InvoiceNumberService
{
    public function next(Terminal $terminal, string $documentType = 'sales_invoice'): string
    {
        $sequence = InvoiceSequence::where('tenant_id', $terminal->tenant_id)
            ->where('branch_id', $terminal->branch_id)
            ->where('terminal_id', $terminal->id)
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            try {
                $sequence = InvoiceSequence::create([
                    'tenant_id' => $terminal->tenant_id,
                    'branch_id' => $terminal->branch_id,
                    'terminal_id' => $terminal->id,
                    'document_type' => $documentType,
                    'prefix' => 'SI-'.$terminal->terminal_code,
                    'current_number' => 0,
                    'padding' => 8,
                    'reset_policy' => 'never',
                ]);
            } catch (QueryException) {
                $sequence = InvoiceSequence::where('tenant_id', $terminal->tenant_id)
                    ->where('branch_id', $terminal->branch_id)
                    ->where('terminal_id', $terminal->id)
                    ->where('document_type', $documentType)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        }

        $sequence->current_number++;
        $sequence->save();

        return $sequence->prefix.'-'.str_pad((string) $sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);
    }
}
