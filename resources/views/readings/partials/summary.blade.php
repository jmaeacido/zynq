<dl class="row mb-0">
    <dt class="col-sm-4">Session</dt><dd class="col-sm-8">#{{ $session->id ?? $reading['session']->id }} - {{ $reading['session']->status }}</dd>
    <dt class="col-sm-4">Sales Count</dt><dd class="col-sm-8">{{ $reading['sale_count'] }}</dd>
    <dt class="col-sm-4">Gross Sales</dt><dd class="col-sm-8">{{ number_format($reading['gross_sales'], 2) }}</dd>
    <dt class="col-sm-4">Discounts</dt><dd class="col-sm-8">{{ number_format($reading['discounts'], 2) }}</dd>
    <dt class="col-sm-4">Net Sales</dt><dd class="col-sm-8">{{ number_format($reading['net_sales'], 2) }}</dd>
    <dt class="col-sm-4">Cash Payments</dt><dd class="col-sm-8">{{ number_format($reading['cash_payments'], 2) }}</dd>
    <dt class="col-sm-4">Card Payments</dt><dd class="col-sm-8">{{ number_format($reading['card_payments'], 2) }}</dd>
    <dt class="col-sm-4">E-Wallet Payments</dt><dd class="col-sm-8">{{ number_format($reading['e_wallet_payments'], 2) }}</dd>
    <dt class="col-sm-4">Opening Cash</dt><dd class="col-sm-8">{{ number_format($reading['opening_cash'], 2) }}</dd>
    <dt class="col-sm-4">Expected Cash</dt><dd class="col-sm-8">{{ number_format($reading['expected_cash'], 2) }}</dd>
    <dt class="col-sm-4">Actual Cash</dt><dd class="col-sm-8">{{ $reading['actual_cash'] === null ? '-' : number_format($reading['actual_cash'], 2) }}</dd>
    <dt class="col-sm-4">Cash Difference</dt><dd class="col-sm-8">{{ $reading['cash_difference'] === null ? '-' : number_format($reading['cash_difference'], 2) }}</dd>
    @if ($reading['z_valid'] !== null)
        <dt class="col-sm-4">Z Validation</dt><dd class="col-sm-8">{{ $reading['z_valid'] ? 'Valid' : 'Mismatch' }}</dd>
    @endif
</dl>
