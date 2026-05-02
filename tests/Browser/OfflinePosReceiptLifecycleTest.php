<?php

namespace Tests\Browser;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Terminals\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\DuskTestCase;

class OfflinePosReceiptLifecycleTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_offline_receipt_becomes_final_invoice_after_sync(): void
    {
        [$cashier, $product, $terminal] = $this->fixture();

        $this->browse(function (Browser $browser) use ($cashier, $product, $terminal): void {
            $browser->loginAs($cashier)
                ->visitRoute('pos.checkout')
                ->waitForText('Cache Offline Snapshot')
                ->click('#cache-snapshot')
                ->waitUntil("document.querySelector('#last-snapshot-at').textContent.includes('Last snapshot cached at:') && ! document.querySelector('#last-snapshot-at').textContent.includes('never')", 10);

            $this->emulateOffline($browser);

            $browser->waitForText('Offline mode is active')
                ->type('#product-search', $product->sku)
                ->click('#add-selected')
                ->waitForText($product->name)
                ->type('#cash-amount', '25')
                ->type('#cash-tendered', '25')
                ->press('Complete Sale')
                ->waitForText('PENDING SYNC - NOT FINAL OFFICIAL INVOICE')
                ->assertSee('1 pending');

            $this->emulateOnline($browser);

            $browser->click('#sync-now')
                ->waitForText('official invoice SI-'.$terminal->terminal_code.'-00000001', 10)
                ->assertSee('Reprint final invoice')
                ->assertSeeLink('Reprint final invoice')
                ->assertSee('0 pending');
        });
    }

    public function test_failed_sync_displays_conflict_status(): void
    {
        [$cashier, $product] = $this->fixture();

        $this->browse(function (Browser $browser) use ($cashier, $product): void {
            $browser->loginAs($cashier)
                ->visitRoute('pos.checkout')
                ->waitForText('Cache Offline Snapshot')
                ->click('#cache-snapshot')
                ->waitUntil("document.querySelector('#last-snapshot-at').textContent.includes('Last snapshot cached at:') && ! document.querySelector('#last-snapshot-at').textContent.includes('never')", 10);

            $this->emulateOffline($browser);

            $browser->waitForText('Offline mode is active')
                ->type('#product-search', $product->sku)
                ->click('#add-selected')
                ->waitForText($product->name)
                ->type('#cash-amount', '25')
                ->type('#cash-tendered', '25')
                ->press('Complete Sale')
                ->waitForText('PENDING SYNC - NOT FINAL OFFICIAL INVOICE');

            $product->update(['selling_price' => 30]);

            $this->emulateOnline($browser);

            $browser->click('#sync-now')
                ->waitUntil("document.querySelector('#sync-now').textContent === 'Sync Now'", 10)
                ->visitRoute('sync.status')
                ->waitForText('Conflict', 10)
                ->assertSee('Conflict')
                ->assertSee('Pending')
                ->visitRoute('sync.conflicts')
                ->assertSee('Conflict')
                ->assertSee('price_changed')
                ->assertSee('A product price changed after the offline snapshot.');
        });
    }

    private function emulateOffline(Browser $browser): void
    {
        $this->network($browser, true);
        $browser->script("window.dispatchEvent(new Event('offline'));");
    }

    private function emulateOnline(Browser $browser): void
    {
        $this->network($browser, false);
        $browser->script("window.dispatchEvent(new Event('online'));");
    }

    private function network(Browser $browser, bool $offline): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Network.enable',
            'params' => (object) [],
        ]);

        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Network.emulateNetworkConditions',
            'params' => (object) [
                'offline' => $offline,
                'latency' => 0,
                'downloadThroughput' => $offline ? 0 : -1,
                'uploadThroughput' => $offline ? 0 : -1,
            ],
        ]);
    }

    private function fixture(): array
    {
        Permission::findOrCreate('create sales');
        Permission::findOrCreate('view reports');

        Role::findOrCreate('Cashier')->givePermissionTo('create sales', 'view reports');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'business_name' => 'Dusk Offline Tenant',
            'registered_address' => 'Dusk Offline Address',
            'tin' => '300-000-000-333',
            'taxpayer_type' => 'VAT',
            'invoice_footer' => 'Thank you.',
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'branch_name' => 'Dusk Branch',
            'branch_code' => 'DUSK',
            'address' => 'Dusk Branch Address',
        ]);

        $terminal = Terminal::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'terminal_name' => 'Dusk POS',
            'terminal_code' => 'DSK01',
            'machine_identification_number' => 'MIN-DSK01',
            'permit_to_use_number' => 'PTU-DSK01',
            'serial_number' => 'SN-DSK01',
            'software_version' => '13.7.0',
        ]);

        $cashier = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);
        $cashier->assignRole('Cashier');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(CashSessionService::class)->open($branch, $terminal, $cashier, 100);

        $category = ProductCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dusk General',
            'code' => 'DUSK-GEN',
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sku' => 'DUSK-SKU-1',
            'barcode' => '4800000099999',
            'name' => 'Dusk Receipt Product',
            'unit' => 'pcs',
            'selling_price' => 25,
            'cost_price' => 10,
            'tax_type' => 'VATABLE',
        ]);

        app(InventoryService::class)->stockIn($product, $branch, 10, 'Dusk opening stock', $cashier);

        return [$cashier, $product, $terminal];
    }
}
