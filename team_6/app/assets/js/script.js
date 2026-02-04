// ------------------------------
// Global cart array
// ------------------------------
let cart = JSON.parse(localStorage.getItem("cart") || "[]");

// ------------------------------
// Add pizza to cart
// ------------------------------
document.querySelectorAll('.add-cart-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const itemBox = btn.closest('.menu-item');
    const id = btn.getAttribute('data-id');
    const name = itemBox.querySelector('h3').innerText;
    const size = itemBox.querySelector('.pizza-size').value;
    const qty = parseInt(itemBox.querySelector('.pizza-qty').value);

    // Get price based on size
    let price = 0;
    const option = itemBox.querySelector(`.pizza-size option[value="${size}"]`);
    if(option) price = parseInt(option.dataset.price);

    // Check if item already in cart
    let found = false;
    cart.forEach(item => {
      if(item.id == id && item.size == size){
        item.qty += qty;
        found = true;
      }
    });

    if(!found){
      cart.push({id, name, size, qty, price});
    }

    updateCart();
  });
});

// ------------------------------
// Update cart sidebar
// ------------------------------
function updateCart(){
  const cartItems = document.getElementById('cart-items');
  cartItems.innerHTML = '';
  let total = 0;

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.qty;
    total += itemTotal;

    // Create list item
    const li = document.createElement('li');
    li.innerHTML = `${item.name} (${item.size}) x ${item.qty} - ¥${itemTotal} 
      <button class="remove-item" data-index="${index}" style="margin-left:10px;">❌</button>`;
    cartItems.appendChild(li);
  });

  document.getElementById('cart-total').innerText = total;

  // Remove item buttons
  document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', () => {
      const idx = btn.getAttribute('data-index');
      cart.splice(idx, 1);
      updateCart();
    });
  });

  // Save cart in localStorage
  localStorage.setItem('cart', JSON.stringify(cart));
}

// ------------------------------
// Checkout button
// ------------------------------
document.getElementById('checkout-btn').addEventListener('click', () => {
  if(cart.length === 0){
    alert("Please add at least one item to your cart!");
    return;
  }

  // Send cart to PHP via hidden POST form
  const form = document.createElement('form');
  form.method = "POST";
  form.action = "order_confirm.php";

  const input = document.createElement('input');
  input.type = "hidden";
  input.name = "cart_data";
  input.value = JSON.stringify(cart);
  form.appendChild(input);

  document.body.appendChild(form);
  form.submit();
});

// ------------------------------
// Initialize cart display on page load
// ------------------------------
updateCart();


