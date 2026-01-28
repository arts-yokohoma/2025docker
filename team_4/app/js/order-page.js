/**
 * ORDER PAGE - Quantity Management & Form Handling
 * Handles pizza quantity adjustments, total calculation, and form submission
 */

// Initialize quantities and prices
let quantities = {
    small: 1,
    medium: 2,
    large: 1
};

let prices = {
    small: 0,
    medium: 0,
    large: 0
};

/**
 * Set prices from PHP data
 * Called when page loads with prices from database
 */
function initializePrices(smallPrice, mediumPrice, largePrice) {
    prices.small = smallPrice;
    prices.medium = mediumPrice;
    prices.large = largePrice;
    updateTotal();
}

/**
 * Change quantity for a pizza size
 * @param {string} size - 'small', 'medium', or 'large'
 * @param {number} change - +1 or -1
 */
function changeQty(size, change) {
    const newQty = quantities[size] + change;
    
    // Prevent negative quantities
    if (newQty < 0) return;
    
    quantities[size] = newQty;
    
    // Update UI elements
    const sizeInitial = size.charAt(0).toUpperCase();
    const sizeLabel = size[0];
    
    // Update display quantity
    const displayElement = document.getElementById(size + 'Qty');
    if (displayElement) {
        displayElement.textContent = quantities[size];
    }
    
    // Update count in summary
    const countElement = document.getElementById(sizeLabel + 'Count');
    if (countElement) {
        countElement.textContent = quantities[size];
    }
    
    // Update hidden form input
    const inputElement = document.getElementById('hidden' + sizeInitial + size.slice(1));
    if (inputElement) {
        inputElement.value = quantities[size];
    }
    
    updateTotal();
}

/**
 * Calculate and update total amount
 */
function updateTotal() {
    let total = 0;
    let itemCount = 0;
    
    // Calculate totals for each size
    for (let size in quantities) {
        total += quantities[size] * prices[size];
        itemCount += quantities[size];
    }
    
    // Format and display total
    const totalElement = document.getElementById('total');
    if (totalElement) {
        totalElement.textContent = total.toLocaleString('ja-JP');
    }
    
    // Update item count
    const itemCountElement = document.getElementById('itemCount');
    if (itemCountElement) {
        itemCountElement.textContent = itemCount;
    }
    
    // Update individual counts display
    const sCountElement = document.getElementById('sCount');
    if (sCountElement) sCountElement.textContent = quantities.small;
    
    const mCountElement = document.getElementById('mCount');
    if (mCountElement) mCountElement.textContent = quantities.medium;
    
    const lCountElement = document.getElementById('lCount');
    if (lCountElement) lCountElement.textContent = quantities.large;
}

/**
 * Validate and submit order form
 */
function setupFormValidation() {
    const orderForm = document.getElementById('orderForm');
    
    if (!orderForm) return;
    
    orderForm.addEventListener('submit', function(e) {
        // Calculate total items
        const itemCount = quantities.small + quantities.medium + quantities.large;
        
        // Validate that at least one item is selected
        if (itemCount === 0) {
            e.preventDefault();
            showAlert('Please select at least one pizza.', 'error');
            return;
        }
        
        // Validate customer information
        const name = document.querySelector('input[name="name"]');
        const phone = document.querySelector('input[name="phone"]');
        const address = document.querySelector('input[name="address"]');
        
        if (!name || !name.value.trim()) {
            e.preventDefault();
            showAlert('Please enter your name.', 'error');
            return;
        }
        
        if (!phone || !phone.value.trim()) {
            e.preventDefault();
            showAlert('Please enter your phone number.', 'error');
            return;
        }
        
        if (!address || !address.value.trim()) {
            e.preventDefault();
            showAlert('Please enter your delivery address.', 'error');
            return;
        }
        
        // Show processing state
        const submitBtn = document.querySelector('.confirm-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<span>⏳ Processing Order...</span>';
            submitBtn.disabled = true;
        }
    });
}

/**
 * Show alert message
 * @param {string} message - Message to display
 * @param {string} type - 'success', 'error', or 'info'
 */
function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#d4edda' : '#d1ecf1'};
        border: 2px solid ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#0c5460'};
        color: ${type === 'error' ? '#721c24' : type === 'success' ? '#155724' : '#0c5460'};
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 9999;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        alertDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => alertDiv.remove(), 300);
    }, 4000);
}

/**
 * Initialize page when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});
