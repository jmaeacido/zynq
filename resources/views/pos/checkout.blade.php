@extends('layouts.app', ['heading' => 'POS Checkout'])

@section('content')
<form method="post" action="{{ route('pos.sales.store') }}" id="sale-form">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><strong>Cart</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select" required>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Terminal</label>
                            <select name="terminal_id" class="form-select" required>
                                @foreach ($terminals as $terminal)
                                    @php($missing = $compliance->missingRequirements($terminal))
                                    <option value="{{ $terminal->id }}">{{ $terminal->terminal_name }}{{ $missing ? ' - setup incomplete' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Barcode / Search</label>
                            <input id="product-search" class="form-control" list="product-options" placeholder="Scan barcode or type name">
                            <datalist id="product-options">
                                @foreach ($products as $product)
                                    <option data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->selling_price }}" value="{{ $product->barcode ?: $product->sku }}">{{ $product->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <table class="table table-striped" id="cart-table">
                        <thead><tr><th>Item</th><th style="width:120px">Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <button type="button" class="btn btn-outline-secondary" id="add-selected">Add Product</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><strong>Payment</strong></div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-6">Subtotal</dt><dd class="col-6 text-end" id="subtotal">0.00</dd>
                        <dt class="col-6">Discounts</dt><dd class="col-6 text-end" id="discount-total">0.00</dd>
                        <dt class="col-6">Total Due</dt><dd class="col-6 text-end h4" id="total">0.00</dd>
                        <dt class="col-6">Change</dt><dd class="col-6 text-end" id="change">0.00</dd>
                    </dl>
                    <div class="row">
                        <div class="col-md-5 mb-3">
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
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Mode</label>
                            <select name="discounts[0][value_type]" class="form-select">
                                <option value="amount">Amount</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Value</label>
                            <input name="discounts[0][value]" id="discount-value" type="number" step="0.01" min="0" class="form-control" value="0">
                        </div>
                    </div>
                    <input name="discounts[0][reason]" class="form-control mb-3" placeholder="Discount reason / ID reference">
                    <div class="mb-3">
                        <label class="form-label">Cash amount</label>
                        <input name="payments[0][amount]" id="cash-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[0][payment_method]" value="cash">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cash tendered</label>
                        <input name="payments[0][amount_tendered]" id="cash-tendered" type="number" step="0.01" min="0" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Card amount</label>
                        <input name="payments[1][amount]" id="card-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[1][payment_method]" value="card">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Card reference</label>
                        <input name="payments[1][reference_number]" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-wallet amount</label>
                        <input name="payments[2][amount]" id="wallet-amount" type="number" step="0.01" min="0" class="form-control" value="0">
                        <input type="hidden" name="payments[2][payment_method]" value="e_wallet">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-wallet reference</label>
                        <input name="payments[2][reference_number]" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100">Complete Sale</button>
                </div>
            </div>
        </div>
    </div>
</form>

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
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-index="${index}">Remove</button></td>
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
document.getElementById('add-selected').addEventListener('click', () => {
    const query = document.getElementById('product-search').value.toLowerCase();
    const product = products.find(p => [p.barcode, p.sku, p.name].filter(Boolean).some(value => String(value).toLowerCase() === query || String(value).toLowerCase().includes(query)));
    if (product) addProduct(product);
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
</script>
@endsection
