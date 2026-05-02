<?php

namespace App\Domains\Invoicing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'branch_id', 'terminal_id', 'document_type', 'prefix', 'current_number', 'padding', 'reset_policy'])]
class InvoiceSequence extends Model
{
}
