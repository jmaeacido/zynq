<?php

namespace App\Domains\Terminals\Services;

use App\Domains\Terminals\Models\Terminal;

class TerminalComplianceService
{
    /**
     * @return list<string>
     */
    public function missingRequirements(Terminal $terminal): array
    {
        $missing = [];

        if (blank($terminal->machine_identification_number)) {
            $missing[] = 'Machine Identification Number (MIN)';
        }

        if (blank($terminal->permit_to_use_number)) {
            $missing[] = 'Permit to Use (PTU) number';
        }

        if (blank($terminal->serial_number)) {
            $missing[] = 'Serial number';
        }

        if (blank($terminal->software_version)) {
            $missing[] = 'Software version';
        }

        return $missing;
    }

    public function isSaleReady(Terminal $terminal): bool
    {
        return $terminal->active && $this->missingRequirements($terminal) === [];
    }
}
