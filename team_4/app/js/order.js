// Pizza price configuration
const pizzaPrices = {
  small: 980,
  medium: 1280,
  large: 1180
};

// Current order quantities
let orderQuantities = {
  small: 1,
  medium: 2,
  large: 1
};

// Function to change quantity
function changeQty(size, change) {
  const newQty = orderQuantities[size] + change;
  
  if (newQty >= 0) {
    orderQuantities[size] = newQty;
    document.getElementById(`${size}Qty`).textContent = newQty;
    updateOrderSummary();
  }
}

// Function to update the order summary
function updateOrderSummary() {
  // Calculate totals
  const smallTotal = orderQuantities.small * pizzaPrices.small;
  const mediumTotal = orderQuantities.medium * pizzaPrices.medium;
  const largeTotal = orderQuantities.large * pizzaPrices.large;
  
  const totalPrice = smallTotal + mediumTotal + largeTotal;
  
  // Update display
  document.getElementById('total').textContent = totalPrice;
  document.getElementById('sCount').textContent = orderQuantities.small;
  document.getElementById('mCount').textContent = orderQuantities.medium;
  document.getElementById('lCount').textContent = orderQuantities.large;
  
  // Calculate total items
  const totalItems = orderQuantities.small + orderQuantities.medium + orderQuantities.large;
  document.getElementById('itemCount').textContent = totalItems;
  
  // Update hidden form inputs
  document.getElementById('hiddenSmall').value = orderQuantities.small;
  document.getElementById('hiddenMedium').value = orderQuantities.medium;
  document.getElementById('hiddenLarge').value = orderQuantities.large;
  document.getElementById('hiddenTotal').value = totalPrice;
}

// Initialize
updateOrderSummary();