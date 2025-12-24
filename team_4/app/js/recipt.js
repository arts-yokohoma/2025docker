//Get URL parameters
const urlParams = new URLSearchParams(window.location.search);

// Pizza prices
const pizzaPrices = {
  small: 980,
  medium: 1280,
  large: 1180
};

// Pizza names
const pizzaNames = {
  small: "Margherita Pizza (Small)",
  medium: "Pepperoni Pizza (Medium)",
  large: "Vegetarian Deluxe (Large)"
};

// Get order data from URL
const orderData = {
  name: urlParams.get('name') || 'John Doe',
  phone: urlParams.get('phone') || '+1 (555) 123-4567',
  email: urlParams.get('email') || 'customer@example.com',
  address: urlParams.get('address') || '123 Main Street, City',
  smallQty: parseInt(urlParams.get('smallQty')) || 1,
  mediumQty: parseInt(urlParams.get('mediumQty')) || 2,
  largeQty: parseInt(urlParams.get('largeQty')) || 1,
  total: parseInt(urlParams.get('total')) || 1286
};

// Generate order ID and timestamp
const orderId = 'PZ-' + Date.now().toString().slice(-8);
const now = new Date();
const orderDate = now.toLocaleDateString('en-US', { 
  weekday: 'long', 
  year: 'numeric', 
  month: 'long', 
  day: 'numeric' 
});
const orderTime = now.toLocaleTimeString('en-US', { 
  hour: '2-digit', 
  minute: '2-digit' 
});

// Calculate subtotals
const subtotal = orderData.smallQty * pizzaPrices.small + 
                 orderData.mediumQty * pizzaPrices.medium + 
                 orderData.largeQty * pizzaPrices.large;
const tax = subtotal * 0.08; // 8% tax
const grandTotal = subtotal + tax;

// Populate receipt data
document.getElementById('orderId').textContent = orderId;
document.getElementById('orderDate').textContent = orderDate;
document.getElementById('orderTime').textContent = orderTime;
document.getElementById('customerName').textContent = orderData.name;
document.getElementById('customerPhone').textContent = orderData.phone;
document.getElementById('customerEmail').textContent = orderData.email;
document.getElementById('customerAddress').textContent = orderData.address;
document.getElementById('orderTotal').textContent = grandTotal.toFixed(0);

// Populate order items table
const orderItemsTable = document.getElementById('orderItems');
orderItemsTable.innerHTML = '';

// Add small pizza if quantity > 0
if (orderData.smallQty > 0) {
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${pizzaNames.small}</td>
    <td>${orderData.smallQty}</td>
    <td>¥${pizzaPrices.small}</td>
    <td>¥${(orderData.smallQty * pizzaPrices.small)}</td>
  `;
  orderItemsTable.appendChild(row);
}

// Add medium pizza if quantity > 0
if (orderData.mediumQty > 0) {
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${pizzaNames.medium}</td>
    <td>${orderData.mediumQty}</td>
    <td>¥${pizzaPrices.medium}</td>
    <td>¥${(orderData.mediumQty * pizzaPrices.medium)}</td>
  `;
  orderItemsTable.appendChild(row);
}

// Add large pizza if quantity > 0
if (orderData.largeQty > 0) {
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${pizzaNames.large}</td>
    <td>${orderData.largeQty}</td>
    <td>¥${pizzaPrices.large}</td>
    <td>¥${(orderData.largeQty * pizzaPrices.large)}</td>
  `;
  orderItemsTable.appendChild(row);
}

// Add tax row
const taxRow = document.createElement('tr');
taxRow.innerHTML = `
  <td colspan="3" style="text-align: right; font-weight: 600;">Tax (8%)</td>
  <td>¥${tax.toFixed(0)}</td>
`;
orderItemsTable.appendChild(taxRow);

// Add delivery row
const deliveryRow = document.createElement('tr');
deliveryRow.innerHTML = `
  <td colspan="3" style="text-align: right; font-weight: 600;">Delivery Fee</td>
  <td>¥0 <span style="color: green; font-size: 12px;">(FREE)</span></td>
`;
orderItemsTable.appendChild(deliveryRow);

// Generate QR code data
const qrData = {
  restaurant: "Pizza Palace",
  orderId: orderId,
  customer: {
    name: orderData.name,
    phone: orderData.phone,
    email: orderData.email,
    address: orderData.address
  },
  order: {
    items: {
      small: { quantity: orderData.smallQty, price: pizzaPrices.small },
      medium: { quantity: orderData.mediumQty, price: pizzaPrices.medium },
      large: { quantity: orderData.largeQty, price: pizzaPrices.large }
    },
    subtotal: subtotal,
    tax: tax.toFixed(0),
    total: grandTotal.toFixed(0)
  },
  orderDate: orderDate + ' ' + orderTime,
  estimatedDelivery: "30-45 minutes",
  storeContact: "+1 (800) PIZZA-NOW"
};

// Generate QR code
QRCode.toCanvas(document.getElementById('qr-code'), 
  JSON.stringify(qrData, null, 2),
  {
    width: 200,
    margin: 1,
    color: {
      dark: '#b1352f',
      light: '#ffffff'
    }
  },
  function(error) {
    if (error) console.error(error);
  }
);

// Add Font Awesome icons
const faScript = document.createElement('script');
faScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js';
document.head.appendChild(faScript);