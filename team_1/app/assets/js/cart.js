const CART_KEY = 'pizza_cart';

function getCart() {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
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

    if (cart.length === 0) {
        empty.classList.remove('hidden');
        table.classList.add('hidden');
        summary.classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');
    table.classList.remove('hidden');
    summary.classList.remove('hidden');

    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;

        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${item.price}円</td>
                <td>
                    <input type="number" min="1" value="${item.qty}"
                        onchange="updateQty(${index}, this.value)">
                </td>
                <td>${subtotal}円</td>
                <td>
                    <button onclick="removeItem(${index})">✖</button>
                </td>
            </tr>
        `;
    });

    totalEl.textContent = total;
}

function updateQty(index, qty) {
    const cart = getCart();
    cart[index].qty = parseInt(qty);
    saveCart(cart);
    renderCart();
}

function removeItem(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
    renderCart();
}

document.addEventListener('DOMContentLoaded', renderCart);
