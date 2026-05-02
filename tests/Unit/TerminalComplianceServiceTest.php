<?php

namespace Tests\Unit;

use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use PHPUnit\Framework\TestCase;

class TerminalComplianceServiceTest extends TestCase
{
    public function test_terminal_is_not_sale_ready_without_required_compliance_fields(): void
    {
        $terminal = new Terminal([
            'active' => true,
            'serial_number' => 'SN-1',
            'software_version' => '13.7.0',
        ]);

        $service = new TerminalComplianceService();

        $this->assertFalse($service->isSaleReady($terminal));
        $this->assertSame([
            'Machine Identification Number (MIN)',
            'Permit to Use (PTU) number',
        ], $service->missingRequirements($terminal));
    }

    public function test_terminal_is_sale_ready_when_required_compliance_fields_exist(): void
    {
        $terminal = new Terminal([
            'active' => true,
            'machine_identification_number' => 'MIN-001',
            'permit_to_use_number' => 'PTU-001',
            'serial_number' => 'SN-1',
            'software_version' => '13.7.0',
        ]);

        $this->assertTrue((new TerminalComplianceService())->isSaleReady($terminal));
    }
}
