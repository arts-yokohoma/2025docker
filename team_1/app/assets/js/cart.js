const CART_KEY = 'cart';

function getCart() {
    const cartJson = localStorage.getItem(CART_KEY);
    return cartJson ? JSON.parse(cartJson) : {};
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function renderCart() {
    const cart = getCart();
    const tbody = document.getElementById('cart-items');
    const table = document.getElementById('cart-table');
    const empty = document.getElementById('cart-empty');
    const summary = document.getElementById('cart-summary');
    const totalEl = document.getElementById('cart-total');

    tbody.innerHTML = '';
    let total = 0;

    const cartKeys = Object.keys(cart);
    if (cartKeys.length === 0) {
        empty.classList.remove('hidden');
        table.classList.add('hidden');
        summary.classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');
    table.classList.remove('hidden');
    summary.classList.remove('hidden');

    cartKeys.forEach(id => {
        const item = cart[id];
        if (!item || !item.qty || !item.price) return;

        const subtotal = item.price * item.qty;
        total += subtotal;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name || '商品'}</td>
            <td>¥${item.price.toLocaleString()}</td>
            <td>
                <button type="button" onclick="updateQty('${id}', -1)">−</button>
                <span>${item.qty}</span>
                <button type="button" onclick="updateQty('${id}', 1)">＋</button>
            </td>
            <td>¥${subtotal.toLocaleString()}</td>
            <td>
                <button type="button" onclick="removeItem('${id}')">✖</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    totalEl.textContent = '¥' + total.toLocaleString();
}

function updateQty(id, diff) {
    const cart = getCart();
    if (!cart[id]) return;

    cart[id].qty = parseInt(cart[id].qty) + diff;
    if (cart[id].qty <= 0) {
        delete cart[id];
    } else {
        cart[id].qty = Math.max(1, cart[id].qty);
    }
    saveCart(cart);
    renderCart();
}

function removeItem(id) {
    const cart = getCart();
    delete cart[id];
    saveCart(cart);
    renderCart();
}

document.addEventListener('DOMContentLoaded', renderCart);
