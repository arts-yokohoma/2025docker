/**
 * ADMIN DASHBOARD - Shift Management & Orders Management
 * Handles staff scheduling, order tracking, and system administration
 */

/**
 * Initialize page on DOM load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const shiftDateElement = document.getElementById('shiftDate');
    if (shiftDateElement) {
        shiftDateElement.valueAsDate = tomorrow;
    }
    
    // Initialize sliders
    updateMorningStaff(5);
    updateEveningStaff(5);
    
    // Load shift history
    updateShiftHistory();
    
    // Set Orders Management as default active tab
    setDefaultTab();
});

/**
 * Set Orders Management as the default active tab on page load
 */
function setDefaultTab() {
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Find and activate Orders Management tab
    document.querySelectorAll('.tab').forEach(tab => {
        if (tab.textContent.includes('Orders Management')) {
            tab.classList.add('active');
        }
    });
    
    // Activate orders tab content
    const ordersTab = document.getElementById('orders-tab');
    if (ordersTab) {
        ordersTab.classList.add('active');
        loadOrders();
    }
}

/**
 * Switch between admin tabs (Shifts vs Orders)
 * @param {string} tabName - 'shifts' or 'orders'
 */
function switchTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to selected tab
    event.target.classList.add('active');
    const tabContent = document.getElementById(tabName + '-tab');
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    // Load data for orders tab
    if (tabName === 'orders') {
        loadOrders();
    }
}

/* ============================================
   SHIFT MANAGEMENT FUNCTIONS
   ============================================ */

/**
 * Update morning staff display
 * @param {number} value - Number of staff
 */
function updateMorningStaff(value) {
    const valueElement = document.getElementById('morningStaffValue');
    const sliderElement = document.getElementById('morningStaffSlider');
    const selectElement = document.getElementById('morningStaffSelect');
    
    if (valueElement) valueElement.textContent = value;
    if (sliderElement) sliderElement.value = value;
    if (selectElement) selectElement.value = value;
    
    calculateCapacity();
}

/**
 * Update evening staff display
 * @param {number} value - Number of staff
 */
function updateEveningStaff(value) {
    const valueElement = document.getElementById('eveningStaffValue');
    const sliderElement = document.getElementById('eveningStaffSlider');
    const selectElement = document.getElementById('eveningStaffSelect');
    
    if (valueElement) valueElement.textContent = value;
    if (sliderElement) sliderElement.value = value;
    if (selectElement) selectElement.value = value;
    
    calculateCapacity();
}

/**
 * Calculate and display order capacity based on staff count
 */
function calculateCapacity() {
    const morningStaffElement = document.getElementById('morningStaffValue');
    const eveningStaffElement = document.getElementById('eveningStaffValue');
    
    if (!morningStaffElement || !eveningStaffElement) return;
    
    const morningStaff = parseInt(morningStaffElement.textContent);
    const eveningStaff = parseInt(eveningStaffElement.textContent);
    
    // Capacity calculation: 2 orders per hour per staff (30 min each)
    const morningCapacity = morningStaff * 8 * 2; // 8 hours × 2 orders/hour
    const eveningCapacity = eveningStaff * 8 * 2; // 8 hours × 2 orders/hour
    const totalCapacity = morningCapacity + eveningCapacity;
    const totalStaff = morningStaff + eveningStaff;
    
    // Update display elements
    updateCapacityDisplay('morningCapacity', morningCapacity);
    updateCapacityDisplay('eveningCapacity', eveningCapacity);
    updateCapacityDisplay('summaryMorningStaff', morningStaff + ' staff');
    updateCapacityDisplay('summaryMorningCapacity', morningCapacity);
    updateCapacityDisplay('summaryEveningStaff', eveningStaff + ' staff');
    updateCapacityDisplay('summaryEveningCapacity', eveningCapacity);
    updateCapacityDisplay('totalStaff', totalStaff);
    updateCapacityDisplay('totalCapacity', totalCapacity);
}

/**
 * Helper function to update capacity display elements
 */
function updateCapacityDisplay(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

/**
 * Save shift schedule to database
 */
function saveShiftSchedule() {
    const dateElement = document.getElementById('shiftDate');
    const morningStaffElement = document.getElementById('morningStaffValue');
    const eveningStaffElement = document.getElementById('eveningStaffValue');
    const notesElement = document.getElementById('shiftNotes');
    
    if (!dateElement) return;
    
    const date = dateElement.value;
    const morningStaff = parseInt(morningStaffElement?.textContent || 0);
    const eveningStaff = parseInt(eveningStaffElement?.textContent || 0);
    const notes = notesElement?.value || '';
    
    if (!date) {
        showNotification('Please select a date', 'error');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'save_shift');
    formData.append('date', date);
    formData.append('morning_staff', morningStaff);
    formData.append('evening_staff', eveningStaff);
    formData.append('notes', notes);
    
    // Send to API
    fetch('shift_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Shift schedule saved to database!\nStaff is now available for orders.', 'success');
            updateShiftHistory();
        } else {
            showNotification('❌ Error: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ Error saving shift schedule', 'error');
    });
}

/**
 * Load previously saved shift schedule from database
 */
function loadShiftSchedule() {
    const dateElement = document.getElementById('shiftDate');
    if (!dateElement) return;
    
    const date = dateElement.value;
    
    if (!date) {
        showNotification('Please select a date', 'error');
        return;
    }
    
    fetch(`shift_management.php?action=get_shift&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMorningStaff(data.morning_staff || 0);
                updateEveningStaff(data.evening_staff || 0);
                const notesElement = document.getElementById('shiftNotes');
                if (notesElement) {
                    notesElement.value = data.notes || '';
                }
                
                if (data.morning_staff > 0 || data.evening_staff > 0) {
                    showNotification('✅ Shift schedule loaded from database!', 'success');
                } else {
                    showNotification('No staff scheduled for this date.', 'info');
                }
            } else {
                showNotification('❌ ' + (data.error || 'Error loading schedule'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading shift schedule', 'error');
        });
}

/**
 * Update and display shift history table
 */
function updateShiftHistory() {
    fetch('shift_management.php?action=get_shift_history')
        .then(response => response.json())
        .then(data => {
            const historyContainer = document.getElementById('shiftHistory');
            if (!historyContainer) return;
            
            if (!data.success || !data.history || data.history.length === 0) {
                historyContainer.innerHTML = 
                    '<p style="text-align: center; color: #666; padding: 20px;">No shift history found</p>';
                return;
            }
            
            let historyHTML = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: left;">Date</th>
                                <th style="padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: center;">Morning</th>
                                <th style="padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: center;">Evening</th>
                                <th style="padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: center;">Capacity</th>
                                <th style="padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: left;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.history.forEach(shift => {
                const totalCapacity = ((shift.morning_staff || 0) + (shift.evening_staff || 0)) * 16;
                
                historyHTML += `
                    <tr onclick="loadShiftForDate('${shift.shift_date}')" 
                        style="cursor: pointer; border-bottom: 1px solid #e2e8f0; transition: background 0.2s; hover: {background: #f5f5f5}">
                        <td style="padding: 12px;">${shift.shift_date}</td>
                        <td style="padding: 12px; text-align: center;">${shift.morning_staff || 0} staff</td>
                        <td style="padding: 12px; text-align: center;">${shift.evening_staff || 0} staff</td>
                        <td style="padding: 12px; text-align: center;">${totalCapacity} orders</td>
                        <td style="padding: 12px; color: #666; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${shift.notes || '-'}
                        </td>
                    </tr>
                `;
            });
            
            historyHTML += '</tbody></table></div>';
            historyContainer.innerHTML = historyHTML;
        })
        .catch(error => {
            console.error('Error:', error);
            const historyContainer = document.getElementById('shiftHistory');
            if (historyContainer) {
                historyContainer.innerHTML = 
                    '<p style="color: #dc3545; text-align: center; padding: 20px;">Error loading shift history</p>';
            }
        });
}

/**
 * Load shift for a specific date
 */
function loadShiftForDate(date) {
    const dateElement = document.getElementById('shiftDate');
    if (dateElement) {
        dateElement.value = date;
        loadShiftSchedule();
    }
}

/* ============================================
   ORDERS MANAGEMENT FUNCTIONS
   ============================================ */

/**
 * Load and display orders list
 */
function loadOrders() {
    const statusFilter = document.getElementById('orderStatusFilter');
    const dateFrom = document.getElementById('orderDateFrom');
    const dateTo = document.getElementById('orderDateTo');
    const search = document.getElementById('orderSearch');
    
    let url = 'orders_data.php?action=get_orders';
    if (statusFilter && statusFilter.value) url += `&status=${statusFilter.value}`;
    if (dateFrom && dateFrom.value) url += `&date_from=${dateFrom.value}`;
    if (dateTo && dateTo.value) url += `&date_to=${dateTo.value}`;
    if (search && search.value) url += `&search=${encodeURIComponent(search.value)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            displayOrdersTable(data.orders || []);
            updateOrdersSummary(data.summary || {});
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            const ordersTable = document.getElementById('ordersTable');
            if (ordersTable) {
                ordersTable.innerHTML = '<p>Error loading orders. Please try again.</p>';
            }
        });
}

/**
 * Display orders in table format
 */
function displayOrdersTable(orders) {
    const tableContainer = document.getElementById('ordersTable');
    if (!tableContainer) return;
    
    if (!orders || orders.length === 0) {
        tableContainer.innerHTML = '<p>No orders found.</p>';
        return;
    }
    
    const statusColors = {
        'pending': '#ffc107',
        'confirmed': '#17a2b8',
        'preparing': '#007bff',
        'out_for_delivery': '#6f42c1',
        'delivered': '#28a745',
        'cancelled': '#dc3545'
    };
    
    let tableHTML = `
        <table class="orders-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Order #</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Customer</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Phone</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Items</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Total</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Status</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Date</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    orders.forEach(order => {
        const items = [];
        if (order.small_quantity > 0) items.push(`${order.small_quantity} Small`);
        if (order.medium_quantity > 0) items.push(`${order.medium_quantity} Medium`);
        if (order.large_quantity > 0) items.push(`${order.large_quantity} Large`);
        
        tableHTML += `
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;">${order.order_number}</td>
                <td style="padding: 12px;">${order.customer_name}</td>
                <td style="padding: 12px;">${order.customer_phone}</td>
                <td style="padding: 12px;">${items.join(', ')}</td>
                <td style="padding: 12px; font-weight: bold;">¥${parseFloat(order.total_amount).toLocaleString()}</td>
                <td style="padding: 12px;">
                    <span style="background: ${statusColors[order.status] || '#6c757d'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        ${order.status}
                    </span>
                </td>
                <td style="padding: 12px;">${new Date(order.order_date).toLocaleDateString()}</td>
                <td style="padding: 12px;">
                    <button onclick="viewOrderDetails(${order.id})" style="padding: 4px 8px; margin-right: 4px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">View</button>
                    <select onchange="updateOrderStatus(${order.id}, this.value)" style="padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Change</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="preparing">Preparing</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </td>
            </tr>
        `;
    });
    
    tableHTML += '</tbody></table>';
    tableContainer.innerHTML = tableHTML;
}

/**
 * Update orders summary cards
 */
function updateOrdersSummary(summary) {
    updateElementText('todayOrders', summary.today_orders || 0);
    updateElementText('pendingOrders', summary.pending_orders || 0);
    updateElementText('todayRevenue', '¥' + (parseFloat(summary.today_revenue) || 0).toLocaleString());
    updateElementText('avgOrderValue', '¥' + (parseFloat(summary.avg_order_value) || 0).toLocaleString());
}

/**
 * Helper function to update element text
 */
function updateElementText(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
    }
}

/**
 * View order details in new window
 */
function viewOrderDetails(orderId) {
    window.open(`order_details.php?id=${orderId}`, '_blank');
}

/**
 * Update order status
 */
function updateOrderStatus(orderId, newStatus) {
    if (!newStatus) return;
    
    if (!confirm('Change order status to ' + newStatus + '?')) return;
    
    fetch('orders_data.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Order status updated successfully!', 'success');
            loadOrders();
        } else {
            showNotification('❌ Error: ' + (data.error || 'Failed to update'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ Error updating order status', 'error');
    });
}

/**
 * Export orders to Excel
 */
function exportOrdersExcel() {
    const statusFilter = document.getElementById('orderStatusFilter');
    const dateFrom = document.getElementById('orderDateFrom');
    const dateTo = document.getElementById('orderDateTo');
    const search = document.getElementById('orderSearch');
    
    let url = 'orders_data.php?action=export_excel';
    if (statusFilter && statusFilter.value) url += `&status=${statusFilter.value}`;
    if (dateFrom && dateFrom.value) url += `&date_from=${dateFrom.value}`;
    if (dateTo && dateTo.value) url += `&date_to=${dateTo.value}`;
    if (search && search.value) url += `&search=${encodeURIComponent(search.value)}`;
    
    window.location.href = url;
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    const colors = {
        'success': { bg: '#d4edda', border: '#28a745', text: '#155724' },
        'error': { bg: '#f8d7da', border: '#dc3545', text: '#721c24' },
        'info': { bg: '#d1ecf1', border: '#0c5460', text: '#0c5460' }
    };
    
    const color = colors[type] || colors.info;
    alert(message); // Simple alert for now - can be enhanced with toast notifications
}
