@extends('layouts.app', ['heading' => 'POS Checkout'])

@section('content')
<form method="post" action="{{ route('pos.sales.store') }}" id="sale-form">
    @csrf
    <div id="offline-warning" class="alert alert-danger d-none py-2">
        <strong><i class="fas fa-plug-circle-xmark me-1"></i>Offline mode is active.</strong>
        Receipts are marked <strong>PENDING SYNC - NOT FINAL OFFICIAL INVOICE</strong> until the server assigns an official invoice number.
    </div>
    <div class="card mb-2">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center py-2">
            <button type="button" class="btn btn-primary btn-sm" id="cache-snapshot"><i class="fas fa-download me-1"></i>Cache Offline Snapshot</button>
            <button type="button" class="btn btn-success btn-sm" id="sync-now"><i class="fas fa-rotate me-1"></i>Sync Now</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('sync.status') }}"><i class="fas fa-list-check me-1"></i>Sync Status</a>
            @can('view reports')
                <a class="btn btn-outline-danger btn-sm" href="{{ route('sync.conflicts') }}"><i class="fas fa-triangle-exclamation me-1"></i>Conflicts</a>
            @endcan
            <span class="compact-meta" id="last-snapshot-at"><i class="fas fa-box-archive me-1"></i>Last snapshot cached at: never</span>
            <span class="compact-meta" id="last-synced-at"><i class="fas fa-clock-rotate-left me-1"></i>Last sync: never</span>
            <span class="badge bg-secondary" id="pos-pending-count">0 pending</span>
            <span class="badge bg-warning text-dark d-none" id="stale-snapshot-warning">Stale snapshot</span>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-2">
                <div class="card-header"><strong>Cart</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Terminal</label>
                            <select name="terminal_id" class="form-select" required>
                                @foreach ($terminals as $terminal)
                                    @php($missing = $compliance->missingRequirements($terminal))
                                    <option value="{{ $terminal->id }}">{{ $terminal->terminal_name }}{{ $missing ? ' - setup incomplete' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Barcode / Search</label>
                            <input id="product-search" class="form-control" list="product-options" placeholder="Scan barcode or type name">
                            <datalist id="product-options">
                                @foreach ($products as $product)
                                    <option data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->selling_price }}" value="{{ $product->barcode ?: $product->sku }}">{{ $product->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <table class="table table-sm table-striped" id="cart-table">
                        <thead><tr><th>Item</th><th style="width:120px">Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="add-selected"><i class="fas fa-plus me-1"></i>Add Product</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-2">
                <div class="card-header"><strong>Payment</strong></div>
                <div class="card-body">
                    <dl class="row mb-2">
                        <dt class="col-6">Subtotal</dt><dd class="col-6 text-end" id="subtotal">0.00</dd>
                        <dt class="col-6">Discounts</dt><dd class="col-6 text-end" id="discount-total">0.00</dd>
                        <dt class="col-6">Total Due</dt><dd class="col-6 text-end h4" id="total">0.00</dd>
                        <dt class="col-6">Change</dt><dd class="col-6 text-end" id="change">0.00</dd>
                    </dl>
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <label class="form-label">Discount type</label>
                            <select name="discounts[0][discount_type]" class="form-select">
                                <option value="">None</option>
                                <option value="regular">Regular</option>
                                <option value="promo">Promo</option>
                                <option value="manual">Manual</option>
                                <option value="senior">Senior</option>
                                <option value="pwd">PWD</option>
                                <option value="solo_parent">Solo Parent</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Mode</label>
                            <select name="discounts[0][value_type]" class="form-select">
                                <option value="amount">Amount</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Value</label>
                            <input name="discounts[0][value]" id="discount-value" type="number" step="0.01" min="0" class="form-control" value="0">
                        </div>
                    </div>
                    <input name="discounts[0][reason]" class="form-control mb-2" placeholder="Discount reason / ID reference">
                    <div class="mb-2">
                        <label class="form-label">Cash amount</label>
                        <input name="payments[0][amount]" id="cash-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[0][payment_method]" value="cash">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cash tendered</label>
                        <input name="payments[0][amount_tendered]" id="cash-tendered" type="number" step="0.01" min="0" class="form-control" value="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Card amount</label>
                        <input name="payments[1][amount]" id="card-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[1][payment_method]" value="card">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Card reference</label>
                        <input name="payments[1][reference_number]" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">E-wallet amount</label>
                        <input name="payments[2][amount]" id="wallet-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[2][payment_method]" value="e_wallet">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">E-wallet reference</label>
                        <input name="payments[2][reference_number]" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100"><i class="fas fa-check me-1"></i>Complete Sale</button>
                    <div class="alert alert-info py-2 px-2 small mt-2 mb-0 d-none" id="offline-reference-note"></div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const products = {{ Js::from($productPayload) }};
let cart = [];
const money = value => Number(value || 0).toFixed(2);
function renderCart() {
    const tbody = document.querySelector('#cart-table tbody');
    tbody.innerHTML = '';
    let total = 0;
    cart.forEach((item, index) => {
        const line = item.price * item.quantity;
        total += line;
        tbody.insertAdjacentHTML('beforeend', `<tr>
            <td>${item.name}<input type="hidden" name="items[${index}][product_id]" value="${item.id}"></td>
            <td><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" class="form-control form-control-sm cart-qty" data-index="${index}" value="${item.quantity}"></td>
            <td>${money(item.price)}</td>
            <td>${money(line)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${index}"><i class="fas fa-trash me-1"></i>Remove</button></td>
        </tr>`);
    });
    const discountValue = Number(document.getElementById('discount-value').value || 0);
    const discountMode = document.querySelector('[name="discounts[0][value_type]"]').value;
    const discountType = document.querySelector('[name="discounts[0][discount_type]"]').value;
    const discount = discountType ? Math.min(total, discountMode === 'percent' ? total * (discountValue / 100) : discountValue) : 0;
    const netTotal = Math.max(0, total - discount);
    document.getElementById('subtotal').textContent = money(total);
    document.getElementById('discount-total').textContent = money(discount);
    document.getElementById('total').textContent = money(netTotal);
    const paid = Number(document.getElementById('cash-amount').value || 0) + Number(document.getElementById('card-amount').value || 0) + Number(document.getElementById('wallet-amount').value || 0);
    const cashChange = Math.max(0, Number(document.getElementById('cash-tendered').value || 0) - Number(document.getElementById('cash-amount').value || 0));
    document.getElementById('change').textContent = money(cashChange + Math.max(0, paid - netTotal));
}
function addProduct(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) existing.quantity += 1;
    else cart.push({...product, quantity: 1});
    renderCart();
}
document.getElementById('add-selected').addEventListener('click', async () => {
    const query = document.getElementById('product-search').value.toLowerCase();
    const source = await offlineProducts();
    const product = source.find(p => [p.barcode, p.sku, p.name].filter(Boolean).some(value => String(value).toLowerCase() === query || String(value).toLowerCase().includes(query)));
    if (product) addProduct({id: product.id, name: product.name, barcode: product.barcode, sku: product.sku, price: Number(product.price || product.selling_price), tax_type: product.tax_type});
});
document.addEventListener('input', event => {
    if (event.target.classList.contains('cart-qty')) cart[event.target.dataset.index].quantity = Number(event.target.value || 0);
    renderCart();
});
document.addEventListener('click', event => {
    if (event.target.classList.contains('remove-item')) {
        cart.splice(event.target.dataset.index, 1);
        renderCart();
    }
});
['cash-amount', 'cash-tendered', 'card-amount', 'wallet-amount', 'discount-value'].forEach(id => document.getElementById(id).addEventListener('input', renderCart));
document.querySelector('[name="discounts[0][value_type]"]').addEventListener('change', renderCart);
document.querySelector('[name="discounts[0][discount_type]"]').addEventListener('change', renderCart);

const dbName = 'zynq-pos-offline';
const snapshotKey = 'current-pos-snapshot';
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const snapshotUrl = @json(route('sync.snapshot'));
const syncUrl = @json(route('sync.offline-sales.store'));
const staleSnapshotMinutes = @json((int) config('offline.stale_snapshot_minutes', 240));
const getField = name => document.querySelector(`[name="${name}"]`);
const stable = value => {
    if (Array.isArray(value)) return value.map(stable);
    if (value && typeof value === 'object') {
        return Object.keys(value).sort().reduce((sorted, key) => {
            sorted[key] = stable(value[key]);
            return sorted;
        }, {});
    }
    return value;
};
const sha256 = async value => {
    const encoded = new TextEncoder().encode(JSON.stringify(stable(value)));
    const digest = await crypto.subtle.digest('SHA-256', encoded);
    return Array.from(new Uint8Array(digest)).map(byte => byte.toString(16).padStart(2, '0')).join('');
};
const withStore = async (name, mode, callback) => {
    const db = await window.ZynqOfflineShell.openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(name, mode);
        const result = callback(tx.objectStore(name));
        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error);
    });
};
const requestFromStore = async (name, mode, callback) => {
    const db = await window.ZynqOfflineShell.openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(name, mode);
        const req = callback(tx.objectStore(name));
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
};
const putOfflineSale = sale => withStore('offline_sales', 'readwrite', store => store.put(sale));
const deleteOfflineSale = id => withStore('offline_sales', 'readwrite', store => store.delete(id));
const putSnapshot = snapshot => withStore('snapshots', 'readwrite', store => store.put({key: snapshotKey, ...snapshot}));
const getSnapshot = () => requestFromStore('snapshots', 'readonly', store => store.get(snapshotKey));
const offlineProducts = async () => {
    if (navigator.onLine) return products;
    const snapshot = await getSnapshot();
    return snapshot?.products || [];
};
const cleanPayments = () => [0, 1, 2].map(index => ({
    payment_method: getField(`payments[${index}][payment_method]`)?.value,
    amount: Number(getField(`payments[${index}][amount]`)?.value || 0),
    amount_tendered: getField(`payments[${index}][amount_tendered]`)?.value || null,
    reference_number: getField(`payments[${index}][reference_number]`)?.value || null,
})).filter(payment => payment.payment_method && payment.amount > 0);
const cleanDiscounts = () => {
    const type = getField('discounts[0][discount_type]').value;
    const value = Number(getField('discounts[0][value]').value || 0);
    if (!type || value <= 0) return [];
    return [{
        discount_type: type,
        value_type: getField('discounts[0][value_type]').value,
        value,
        reason: getField('discounts[0][reason]').value || null,
        reference_number: null,
        approved_by_user_id: null,
    }];
};
const buildSalePayload = snapshot => ({
    items: cart.map(item => ({
        product_id: item.id,
        quantity: Number(item.quantity),
        unit_price: Number(item.price),
        tax_type: item.tax_type || 'VATABLE',
    })),
    payments: cleanPayments(),
    discounts: cleanDiscounts(),
});
const updateOfflineUi = () => {
    document.getElementById('offline-warning').classList.toggle('d-none', navigator.onLine);
    document.getElementById('sync-now').disabled = !navigator.onLine;
    document.getElementById('sync-now').title = navigator.onLine ? '' : 'Cannot sync while offline. Return online to sync pending sales.';
    window.ZynqOfflineShell.allSales().then(sales => {
        const count = sales.filter(item => ['pending_sync', 'sync_failed', 'conflict'].includes(item.sync_status)).length;
        document.getElementById('pos-pending-count').textContent = count + ' pending';
    });
    getSnapshot().then(snapshot => {
        if (!snapshot?.downloaded_at) return;
        const downloaded = new Date(snapshot.downloaded_at);
        document.getElementById('last-snapshot-at').innerHTML = '<i class="fas fa-box-archive me-1"></i>Last snapshot cached at: ' + downloaded.toLocaleString();
        const stale = (Date.now() - downloaded.getTime()) > staleSnapshotMinutes * 60 * 1000;
        document.getElementById('stale-snapshot-warning').classList.toggle('d-none', !stale);
    });
    window.ZynqOfflineShell.refresh();
};
const cacheSnapshot = async () => {
    const params = new URLSearchParams({
        branch_id: getField('branch_id').value,
        terminal_id: getField('terminal_id').value,
    });
    const response = await fetch(snapshotUrl + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
    if (!response.ok) throw new Error((await response.json()).message || 'Unable to cache offline snapshot.');
    const snapshot = await response.json();
    await putSnapshot(snapshot);
    document.getElementById('last-snapshot-at').innerHTML = '<i class="fas fa-box-archive me-1"></i>Last snapshot cached at: ' + new Date(snapshot.downloaded_at).toLocaleString();
    await window.ZynqOfflineShell.refresh();
    return snapshot;
};
const queueOfflineSale = async () => {
    const snapshot = await getSnapshot();
    if (!snapshot) throw new Error('No offline snapshot is cached for this terminal.');
    if (!snapshot.cash_session || snapshot.cash_session.status !== 'open') throw new Error('Offline sale blocked: no cached open cash session.');
    if (!snapshot.terminal.compliant) throw new Error('Offline sale blocked: cached terminal compliance is incomplete.');
    if (cart.length === 0) throw new Error('Sale must contain at least one product.');

    const cachedProducts = new Map((snapshot.products || []).map(product => [Number(product.id), product]));
    for (const item of cart) {
        const cached = cachedProducts.get(Number(item.id));
        if (!cached) throw new Error('Offline sale blocked: cart contains a product outside the cached snapshot.');
        if (Number(cached.stock_estimate || 0) < Number(item.quantity)) throw new Error('Offline sale blocked: local stock estimate is insufficient.');
        cached.stock_estimate = Number(cached.stock_estimate || 0) - Number(item.quantity);
    }

    const downloaded = new Date(snapshot.downloaded_at);
    if ((Date.now() - downloaded.getTime()) > staleSnapshotMinutes * 60 * 1000) throw new Error('Offline sale blocked: cached product, price, tax, and stock snapshot is stale.');

    const sale = buildSalePayload(snapshot);
    const now = new Date();
    const idempotencyKey = crypto.randomUUID();
    const offlineReference = 'OFF-' + snapshot.terminal.terminal_code + '-' + now.getTime();
    const queued = {
        idempotency_key: idempotencyKey,
        offline_reference: offlineReference,
        tenant_id: snapshot.tenant.id,
        branch_id: snapshot.branch.id,
        terminal_id: snapshot.terminal.id,
        cashier_id: snapshot.cashier.id,
        cash_session_id: snapshot.cash_session.id,
        created_offline_at: now.toISOString(),
        payload_hash: await sha256(sale),
        tax_snapshot: {vat_rate: Number(snapshot.vat_settings.rate)},
        sale,
        sync_status: 'pending_sync',
        last_error: null,
    };
    await putOfflineSale(queued);
    await putSnapshot({...snapshot, products: Array.from(cachedProducts.values())});
    const note = document.getElementById('offline-reference-note');
    note.className = 'alert alert-warning py-2 px-2 small mt-2 mb-0';
    note.innerHTML = '<strong>PENDING SYNC - NOT FINAL OFFICIAL INVOICE</strong><br>Temporary reference: ' + offlineReference + '.';
    cart = [];
    renderCart();
    await window.ZynqOfflineShell.refresh();
};
const syncPendingSales = async () => {
    if (!navigator.onLine) throw new Error('Cannot sync while offline.');
    const sales = (await window.ZynqOfflineShell.allSales()).filter(item => ['pending_sync', 'sync_failed', 'conflict'].includes(item.sync_status));
    document.getElementById('sync-now').disabled = true;
    document.getElementById('sync-now').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';
    let syncedCount = 0;
    let conflictCount = 0;
    for (const sale of sales) {
        document.getElementById('last-synced-at').innerHTML = '<i class="fas fa-clock-rotate-left me-1"></i>Last sync: ' + new Date().toLocaleString();
        const response = await fetch(syncUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(sale),
        });
        const result = await response.json();
        if (response.ok && result.status === 'synced') {
            syncedCount++;
            await deleteOfflineSale(sale.idempotency_key);
            const note = document.getElementById('offline-reference-note');
            note.className = 'alert alert-success py-2 px-2 small mt-2 mb-0';
            note.innerHTML = `<strong>Synced ${result.offline_reference} as official invoice ${result.invoice_number}</strong><br>${result.invoice_reprint_url ? `<a href="${result.invoice_reprint_url}">Reprint final invoice</a>` : ''}`;
        } else {
            if (result.status === 'conflict') conflictCount++;
            sale.sync_status = result.status === 'conflict' ? 'conflict' : 'sync_failed';
            sale.last_error = result.message || 'Sync failed.';
            sale.conflicts = result.conflicts || [];
            await putOfflineSale(sale);
        }
    }
    document.getElementById('sync-now').disabled = !navigator.onLine;
    document.getElementById('sync-now').innerHTML = '<i class="fas fa-rotate me-1"></i>Sync Now';
    await window.ZynqOfflineShell.refresh();
    return {total: sales.length, syncedCount, conflictCount};
};
document.getElementById('cache-snapshot').addEventListener('click', async () => {
    Swal.fire({title: 'Caching snapshot', text: 'Downloading current products, tax, terminal, and cash session data.', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
    try {
        await cacheSnapshot();
        Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Snapshot cached', timer: 1800, showConfirmButton: false});
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Snapshot failed', text: error.message});
    }
});
document.getElementById('sync-now').addEventListener('click', async () => {
    Swal.fire({title: 'Syncing offline sales', text: 'Sending pending receipts to the server.', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
    try {
        const result = await syncPendingSales();
        const icon = result.conflictCount > 0 ? 'warning' : 'success';
        Swal.fire({toast: true, position: 'top-end', icon, title: result.conflictCount > 0 ? 'Sync needs review' : 'Sync complete', text: `${result.syncedCount} synced, ${result.conflictCount} conflict${result.conflictCount === 1 ? '' : 's'}.`, timer: 2400, showConfirmButton: false});
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Sync failed', text: error.message});
    }
});
document.getElementById('sale-form').addEventListener('submit', async event => {
    if (navigator.onLine) return;
    event.preventDefault();
    Swal.fire({title: 'Saving offline receipt', text: 'This receipt is pending sync and is not a final official invoice.', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
    try {
        await queueOfflineSale();
        Swal.fire({toast: true, position: 'top-end', icon: 'warning', title: 'Offline receipt queued', text: 'PENDING SYNC - NOT FINAL OFFICIAL INVOICE.', timer: 2400, showConfirmButton: false});
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Offline sale blocked', text: error.message});
    }
});
window.addEventListener('online', () => syncPendingSales().catch(() => window.ZynqOfflineShell.refresh()));
window.addEventListener('online', updateOfflineUi);
window.addEventListener('offline', updateOfflineUi);
document.addEventListener('DOMContentLoaded', updateOfflineUi);
</script>
@endpush
@endsection
