// order_edit.js
document.addEventListener('DOMContentLoaded', function() {
    let nextItemId = 10000; // For new items

    // Original customer data for cancel functionality
    const originalCustomerData = {
        name: document.getElementById('customerName').value,
        phone: document.getElementById('customerPhone').value,
        address: document.getElementById('customerAddress').value
    };

    // Status change with visual update
    const statusSelect = document.getElementById('statusSelect');
    const statusBadge = document.getElementById('statusBadge');
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');

    statusSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const color = selectedOption.dataset.color;
        const label = selectedOption.dataset.label;

        statusBadge.style.background = color + '20';
        statusBadge.style.color = color;
        statusDot.style.background = color;
        statusText.textContent = label;
    });

    // Customer editing
    const editCustomerBtn = document.getElementById('editCustomerBtn');
    const saveCustomerBtn = document.getElementById('saveCustomerBtn');
    const cancelCustomerBtn = document.getElementById('cancelCustomerBtn');
    const customerView = document.getElementById('customerView');
    const customerEdit = document.getElementById('customerEdit');

    editCustomerBtn.addEventListener('click', function() {
        customerView.style.display = 'none';
        customerEdit.style.display = 'block';
        editCustomerBtn.style.display = 'none';
    });

    cancelCustomerBtn.addEventListener('click', function() {
        // Restore original values
        document.getElementById('customerName').value = originalCustomerData.name;
        document.getElementById('customerPhone').value = originalCustomerData.phone;
        document.getElementById('customerAddress').value = originalCustomerData.address;
        
        customerView.style.display = 'block';
        customerEdit.style.display = 'none';
        editCustomerBtn.style.display = 'block';
    });

    saveCustomerBtn.addEventListener('click', function() {
        // Get values
        const name = document.getElementById('customerName').value;
        const phone = document.getElementById('customerPhone').value;
        const address = document.getElementById('customerAddress').value;

        // Update display
        document.getElementById('displayName').textContent = name;
        document.getElementById('displayPhone').textContent = phone;
        document.getElementById('displayAddress').textContent = address;

        // Update original data
        originalCustomerData.name = name;
        originalCustomerData.phone = phone;
        originalCustomerData.address = address;

        customerView.style.display = 'block';
        customerEdit.style.display = 'none';
        editCustomerBtn.style.display = 'block';
    });

    // Product search
    const addProductBtn = document.getElementById('addProductBtn');
    const productSearch = document.getElementById('productSearch');
    const closeSearchBtn = document.getElementById('closeSearchBtn');
    const searchInput = document.getElementById('searchInput');
    const productList = document.getElementById('productList');
    const productItems = productList.querySelectorAll('.product-item');

    addProductBtn.addEventListener('click', function() {
        productSearch.style.display = 'block';
        searchInput.focus();
    });

    closeSearchBtn.addEventListener('click', function() {
        productSearch.style.display = 'none';
        searchInput.value = '';
        productItems.forEach(item => item.classList.remove('hidden'));
    });

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        productItems.forEach(item => {
            const name = item.querySelector('span').textContent.toLowerCase();
            if (name.includes(query)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    // Add product to order
    productItems.forEach(item => {
        item.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const productName = this.dataset.name;
            const productPrice = parseFloat(this.dataset.price);

            // Check if item already exists
            const tableBody = document.getElementById('itemsTableBody');
            const existingRow = Array.from(tableBody.querySelectorAll('tr')).find(row => {
                const menuIdInput = row.querySelector('input[name="menu_id[]"]');
                return menuIdInput && menuIdInput.value === menuId;
            });

            if (existingRow) {
                // Increment quantity
                const qtyInput = existingRow.querySelector('.qty-input');
                qtyInput.value = parseInt(qtyInput.value) + 1;
                updateRowSubtotal(existingRow);
            } else {
                // Add new row
                const newRow = createItemRow('', menuId, productName, productPrice, 1);
                tableBody.appendChild(newRow);
            }

            updateTotals();
            
            productSearch.style.display = 'none';
            searchInput.value = '';
            productItems.forEach(item => item.classList.remove('hidden'));
        });
    });

    function createItemRow(itemId, menuId, name, price, quantity) {
        const row = document.createElement('tr');
        row.dataset.itemId = itemId || 'new-' + nextItemId++;
        row.innerHTML = `
            <td>
                <input type="hidden" name="item_id[]" value="${itemId}">
                <input type="hidden" name="menu_id[]" value="${menuId}">
                <span class="item-name">${name}</span>
            </td>
            <td class="text-right price-cell">
                <input type="hidden" name="price[]" class="price-input" value="${price}">
                <span class="price-display">¥${parseInt(price).toLocaleString('ja-JP')}</span>
            </td>
            <td class="text-center">
                <div class="quantity-control">
                    <button type="button" class="qty-btn qty-minus">−</button>
                    <input type="number" name="quantity[]" class="qty-input" value="${quantity}" data-price="${price}" min="1">
                    <button type="button" class="qty-btn qty-plus">+</button>
                </div>
            </td>
            <td class="text-right subtotal-cell">¥${(parseInt(price) * parseInt(quantity)).toLocaleString('ja-JP')}</td>
            <td class="text-center">
                <button type="button" class="btn-delete">🗑️</button>
            </td>
        `;
        return row;
    }

    // Quantity controls
    document.getElementById('itemsTableBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('qty-minus')) {
            const input = e.target.parentElement.querySelector('.qty-input');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateRowSubtotal(e.target.closest('tr'));
                updateTotals();
            }
        } else if (e.target.classList.contains('qty-plus')) {
            const input = e.target.parentElement.querySelector('.qty-input');
            input.value = parseInt(input.value) + 1;
            updateRowSubtotal(e.target.closest('tr'));
            updateTotals();
        } else if (e.target.classList.contains('btn-delete')) {
            if (confirm('この商品を削除しますか?')) {
                e.target.closest('tr').remove();
                updateTotals();
            }
        }
    });

    document.getElementById('itemsTableBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input')) {
            const value = parseInt(e.target.value);
            if (value < 1 || isNaN(value)) {
                e.target.value = 1;
            }
            updateRowSubtotal(e.target.closest('tr'));
            updateTotals();
        }
    });

    function updateRowSubtotal(row) {
        const qtyInput = row.querySelector('.qty-input');
        const priceInput = row.querySelector('.price-input');
        const price = parseFloat(priceInput.value);
        const quantity = parseInt(qtyInput.value);
        const subtotal = price * quantity;
        row.querySelector('.subtotal-cell').textContent = '¥' + parseInt(subtotal).toLocaleString('ja-JP');
    }

    function updateTotals() {
        const rows = document.querySelectorAll('#itemsTableBody tr');
        let subtotal = 0;
        let totalItems = 0;

        rows.forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const price = parseFloat(priceInput.value);
            const quantity = parseInt(qtyInput.value);
            subtotal += price * quantity;
            totalItems += quantity;
        });

        document.getElementById('totalItemsCount').textContent = totalItems;
        document.getElementById('subtotalAmount').textContent = '¥' + parseInt(subtotal).toLocaleString('ja-JP');
        document.getElementById('totalAmount').textContent = '¥' + parseInt(subtotal).toLocaleString('ja-JP');
    }

    // Calculate total on page load
    updateTotals();

    // Form validation before submit
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('#itemsTableBody tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('少なくとも1つの商品を追加してください。');
            return false;
        }

        // Check if all rows have valid menu selections
        let hasInvalidRow = false;
        rows.forEach(row => {
            const menuId = row.querySelector('input[name="menu_id[]"]').value;
            if (!menuId) {
                hasInvalidRow = true;
            }
        });

        if (hasInvalidRow) {
            e.preventDefault();
            alert('すべての商品を選択してください。');
            return false;
        }

        return true;
    });
});