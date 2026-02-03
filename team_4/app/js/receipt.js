/**
 * Receipt Page JavaScript
 * Handles QR code display, print functionality, and clipboard operations
 */

/**
 * Initialize receipt functionality when page loads
 * Sets up event listeners for print, QR code validation, and context menu
 */
function initializeReceipt() {
  setupPrintHandlers();
  setupQRCodeValidation();
  setupQRCodeContextMenu();
}

/**
 * Set up print event handlers
 * Hides action buttons during print and shows them after
 */
function setupPrintHandlers() {
  window.addEventListener('beforeprint', function() {
    document.querySelector('.action-buttons').style.display = 'none';
  });

  window.addEventListener('afterprint', function() {
    document.querySelector('.action-buttons').style.display = 'flex';
  });
}

/**
 * Validate QR code image loading
 * Shows fallback text if QR code fails to load from API
 */
function setupQRCodeValidation() {
  window.addEventListener('load', function() {
    const qrImage = document.getElementById('qrCodeImage');
    
    // Check if image loaded successfully after 1 second
    setTimeout(function() {
      if (!qrImage || !qrImage.complete || qrImage.naturalHeight === 0) {
        // QR code failed to load, show fallback
        const fallbackElement = document.getElementById('qrFallback');
        if (fallbackElement) {
          fallbackElement.style.display = 'block';
        }
      }
    }, 1000);
  });
}

/**
 * Set up right-click context menu for QR code
 * Informs user how to save the QR code image
 */
function setupQRCodeContextMenu() {
  const qrImage = document.getElementById('qrCodeImage');
  if (qrImage) {
    qrImage.addEventListener('contextmenu', function(e) {
      alert('QRコードを右クリックして「名前を付けて画像を保存」を選択してください。');
    });
  }
}

/**
 * Copy order code to clipboard
 * Uses modern Clipboard API with fallback to execCommand
 * Displays alert with success/failure message
 */
function copyOrderCode() {
  // Get order ID from the DOM or from global variable if available
  const orderIdElement = document.querySelector('.info-value');
  const code = orderIdElement ? orderIdElement.textContent.trim() : 'Order Code';
  
  // Try modern Clipboard API first
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(code).then(function() {
      showCopySuccess(code);
    }).catch(function() {
      fallbackCopy(code);
    });
  } else {
    // Fallback for older browsers or non-secure contexts
    fallbackCopy(code);
  }
}

/**
 * Fallback copy method using execCommand
 * Creates temporary textarea, copies text, and cleans up
 * @param {string} code - The text to copy
 */
function fallbackCopy(code) {
  const textArea = document.createElement('textarea');
  textArea.value = code;
  textArea.style.position = 'fixed';
  textArea.style.left = '-999999px';
  textArea.style.top = '-999999px';
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    const successful = document.execCommand('copy');
    if (successful) {
      showCopySuccess(code);
    } else {
      showCopyError(code);
    }
  } catch (err) {
    showCopyError(code);
  }
  
  document.body.removeChild(textArea);
}

/**
 * Display success message when order code is copied
 * @param {string} code - The copied order code
 */
function showCopySuccess(code) {
  alert('注文コードをコピーしました: ' + code);
}

/**
 * Display error message when copy fails
 * Prompts user to copy manually
 * @param {string} code - The order code that failed to copy
 */
function showCopyError(code) {
  alert('コピーに失敗しました。手動でコピーしてください: ' + code);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeReceipt);
} else {
  initializeReceipt();
}
