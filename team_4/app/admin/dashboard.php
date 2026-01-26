<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza Sales Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/adminstyle.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pizza Sales Dashboard</h1>
        </div>
        
        <div class="tabs">
            <div class="tab" onclick="switchTab('shifts')">Shift Management</div>
            <div class="tab" onclick="switchTab('orders')">Orders Management</div> 
        </div>  
        
        <!-- Shift Management Tab -->
        <div id="shifts-tab" class="tab-content">
            <!-- Capacity Rules -->
            <div class="capacity-rules">
                <h3>Staff Capacity Rules</h3>
                <ul>
                    <li>✅ <strong>1 staff = 1 order at a time</strong></li>
                    <li>⏱️ <strong>30 minutes processing time</strong> per order</li>
                    <li>📊 <strong>Maximum capacity:</strong> Staff count × 2 orders per hour</li>
                    <li>🔄 <strong>Staff become available</strong> after completing order</li>
                </ul>
            </div>
            
            <!-- Shift Planning Section -->
            <div class="controls">
                <h2>Shift Planning</h2>
                <p>Set staff allocation for the selected date.</p>
                
                <div class="shift-form">
                    <div class="form-group">
                        <label>Date for Shift Planning:</label>
                        <input type="date" id="shiftDate" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="shift-planning-grid">
                        <!-- Morning Shift -->
                        <div class="shift-slot">
                            <h4>Morning Shift (8AM - 4PM)</h4>
                            <div class="shift-slider">
                                <input type="range" id="morningStaffSlider" min="1" max="15" value="5" 
                                       onchange="updateMorningStaff(this.value)">
                                <span class="shift-value" id="morningStaffValue">5</span>
                                <span>staff</span>
                            </div>
                            <div class="summary-label">
                                Capacity: <span id="morningCapacity">80</span> orders (2 orders/hour per staff)
                            </div>
                        </div>
                        
                        <!-- Evening Shift -->
                        <div class="shift-slot">
                            <h4>Evening Shift (4PM - 12AM)</h4>
                            <div class="shift-slider">
                                <input type="range" id="eveningStaffSlider" min="1" max="15" value="5" 
                                       onchange="updateEveningStaff(this.value)">
                                <span class="shift-value" id="eveningStaffValue">5</span>
                                <span>staff</span>
                            </div>
                            <div class="summary-label">
                                Capacity: <span id="eveningCapacity">80</span> orders (2 orders/hour per staff)
                            </div>
                        </div>
                    </div>
                    
                    <!-- Capacity Summary -->
                    <div class="capacity-summary" id="capacitySummary" style="margin: 20px 0;">
                        <h4>Capacity Summary</h4>
                        <div class="capacity-summary-grid">
                            <div class="summary-card">
                                <h5>Morning Shift</h5>
                                <p class="summary-value" id="summaryMorningStaff">5 staff</p>
                                <p class="summary-label"><span id="summaryMorningCapacity">80</span> orders capacity</p>
                            </div>
                            <div class="summary-card evening">
                                <h5>Evening Shift</h5>
                                <p class="summary-value" id="summaryEveningStaff">5 staff</p>
                                <p class="summary-label"><span id="summaryEveningCapacity">80</span> orders capacity</p>
                            </div>
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px;">
                            <h5 style="margin: 0 0 10px 0;">Total Daily Capacity</h5>
                            <p style="font-size: 20px; font-weight: bold; margin: 0 0 10px 0;">
                                <span id="totalStaff">10</span> staff × 16 orders/day = 
                                <span id="totalCapacity">160</span> orders
                            </p>
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                Each staff can handle 2 orders per hour (30 minutes per order)
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Special Notes:</label>
                        <textarea id="shiftNotes" rows="3" placeholder="Any special notes or instructions..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button onclick="saveShiftSchedule()" style="background: #28a745;">Save Shift Schedule</button>
                        <button onclick="loadShiftSchedule()" style="background: #6c757d;">Load Saved Schedule</button>
                        <button onclick="calculateCapacity()" style="background: #007bff;">Recalculate</button>
                    </div>
                </div>
            </div>
            
            <!-- Shift History -->
            <div class="chart-container">
                <h3>Shift History</h3>
                <div id="shiftHistory"></div>
            </div>
        </div>
        
        <!-- Orders Management Tab (unchanged) -->
        <div id="orders-tab" class="tab-content">
            <div class="controls">
                <h2>Customer Orders Management</h2>
                <p>View and manage all customer pizza orders.</p>
                
                <!-- Filters -->
                <div class="filter-controls">
                    <label>Status:</label>
                    <select id="orderStatusFilter" onchange="loadOrders()">
                        <option value="">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="preparing">Preparing</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    
                    <label>Date Range:</label>
                    <input type="date" id="orderDateFrom" onchange="loadOrders()">
                    <input type="date" id="orderDateTo" onchange="loadOrders()">
                    
                    <label>Search:</label>
                    <input type="text" id="orderSearch" placeholder="Customer name/phone/order#" 
                           onkeyup="loadOrders()">
                    
                    <button onclick="exportOrdersExcel()" style="background: #28a745;">
                        Export to Excel
                    </button>
                    
                    <button onclick="loadOrders()" style="background: #007bff;">
                        Refresh
                    </button>
                </div>
            </div>
            
            <div class="orders-container">
                <div class="orders-summary">
                    <div class="summary-card">
                        <h4>Today's Orders</h4>
                        <p id="todayOrders">0</p>
                    </div>
                    <div class="summary-card">
                        <h4>Pending</h4>
                        <p id="pendingOrders">0</p>
                    </div>
                    <div class="summary-card">
                        <h4>Revenue Today</h4>
                        <p id="todayRevenue">¥0</p>
                    </div>
                    <div class="summary-card">
                        <h4>Avg Order Value</h4>
                        <p id="avgOrderValue">¥0</p>
                    </div>
                </div>
                
                <div class="orders-table-container">
                    <h3>All Orders</h3>
                    <div id="ordersTable">
                        <p>Loading orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('shiftDate').valueAsDate = tomorrow;
            
            // Initialize sliders
            updateMorningStaff(5);
            updateEveningStaff(5);
            
            // Load shift history
            updateShiftHistory();
        });
        
        // Tab switching function
        function switchTab(tabName) {
            // Update tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activate selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Load data for specific tabs
            if (tabName === 'orders') {
                loadOrders();
            }
        }
        
        // Shift Planning Functions
        function updateMorningStaff(value) {
            document.getElementById('morningStaffValue').textContent = value;
            document.getElementById('morningStaffSlider').value = value;
            calculateCapacity();
        }
        
        function updateEveningStaff(value) {
            document.getElementById('eveningStaffValue').textContent = value;
            document.getElementById('eveningStaffSlider').value = value;
            calculateCapacity();
        }
        
        function calculateCapacity() {
            const morningStaff = parseInt(document.getElementById('morningStaffValue').textContent);
            const eveningStaff = parseInt(document.getElementById('eveningStaffValue').textContent);
            
            // Capacity calculation: 2 orders per hour per staff (30 min each)
            const morningCapacity = morningStaff * 8 * 2; // 8 hours × 2 orders/hour
            const eveningCapacity = eveningStaff * 8 * 2; // 8 hours × 2 orders/hour
            const totalCapacity = morningCapacity + eveningCapacity;
            const totalStaff = morningStaff + eveningStaff;
            
            // Update displays
            document.getElementById('morningCapacity').textContent = morningCapacity;
            document.getElementById('eveningCapacity').textContent = eveningCapacity;
            
            // Update summary cards
            document.getElementById('summaryMorningStaff').textContent = morningStaff + ' staff';
            document.getElementById('summaryMorningCapacity').textContent = morningCapacity;
            document.getElementById('summaryEveningStaff').textContent = eveningStaff + ' staff';
            document.getElementById('summaryEveningCapacity').textContent = eveningCapacity;
            document.getElementById('totalStaff').textContent = totalStaff;
            document.getElementById('totalCapacity').textContent = totalCapacity;
        }
        
        function saveShiftSchedule() {
            const date = document.getElementById('shiftDate').value;
            const morningStaff = parseInt(document.getElementById('morningStaffValue').textContent);
            const eveningStaff = parseInt(document.getElementById('eveningStaffValue').textContent);
            const notes = document.getElementById('shiftNotes').value;
            
            if (!date) {
                alert('Please select a date');
                return;
            }
            
            // Save to localStorage
            const shiftData = {
                date: date,
                morning_staff: morningStaff,
                evening_staff: eveningStaff,
                notes: notes,
                saved_at: new Date().toISOString()
            };
            
            localStorage.setItem('shift_' + date, JSON.stringify(shiftData));
            
            // Save to history
            let history = JSON.parse(localStorage.getItem('shift_history') || '[]');
            history.push(shiftData);
            localStorage.setItem('shift_history', JSON.stringify(history.slice(-20)));
            
            alert('Shift schedule saved successfully!');
            updateShiftHistory();
        }
        
        function loadShiftSchedule() {
            const date = document.getElementById('shiftDate').value;
            
            try {
                const saved = localStorage.getItem('shift_' + date);
                if (saved) {
                    const data = JSON.parse(saved);
                    
                    updateMorningStaff(data.morning_staff || 5);
                    updateEveningStaff(data.evening_staff || 5);
                    document.getElementById('shiftNotes').value = data.notes || '';
                    
                    alert('Shift schedule loaded!');
                } else {
                    alert('No saved schedule found for this date. Using defaults.');
                }
            } catch (e) {
                alert('Error loading schedule. Using defaults.');
            }
        }
        
        // Shift History Functions
        function updateShiftHistory() {
            try {
                const history = JSON.parse(localStorage.getItem('shift_history') || '[]');
                
                if (history.length === 0) {
                    document.getElementById('shiftHistory').innerHTML = 
                        '<p style="text-align: center; color: #666; padding: 20px;">No shift history found</p>';
                    return;
                }
                
                let historyHTML = `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Date</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Morning Staff</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Evening Staff</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Total Capacity</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #e2e8f0;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                history.reverse().forEach(shift => {
                    const totalCapacity = ((shift.morning_staff || 0) + (shift.evening_staff || 0)) * 16;
                    
                    historyHTML += `
                        <tr onclick="loadShiftForDate('${shift.date}')" 
                            style="cursor: pointer; border-bottom: 1px solid #e2e8f0; transition: background 0.2s;">
                            <td style="padding: 12px;">${shift.date}</td>
                            <td style="padding: 12px;">${shift.morning_staff || 0}</td>
                            <td style="padding: 12px;">${shift.evening_staff || 0}</td>
                            <td style="padding: 12px;">${totalCapacity} orders</td>
                            <td style="padding: 12px; color: #666; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${shift.notes || '-'}
                            </td>
                        </tr>
                    `;
                });
                
                historyHTML += '</tbody></table></div>';
                document.getElementById('shiftHistory').innerHTML = historyHTML;
            } catch (e) {
                document.getElementById('shiftHistory').innerHTML = 
                    '<p style="color: #dc3545; text-align: center; padding: 20px;">Error loading shift history</p>';
            }
        }
        
        function loadShiftForDate(date) {
            document.getElementById('shiftDate').value = date;
            loadShiftSchedule();
        }
        
        // Order Management Functions (your existing code)
        function loadOrders() {
            const status = document.getElementById('orderStatusFilter').value;
            const dateFrom = document.getElementById('orderDateFrom').value;
            const dateTo = document.getElementById('orderDateTo').value;
            const search = document.getElementById('orderSearch').value;
            
            let url = 'orders_data.php?action=get_orders';
            if (status) url += `&status=${status}`;
            if (dateFrom) url += `&date_from=${dateFrom}`;
            if (dateTo) url += `&date_to=${dateTo}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    displayOrdersTable(data.orders);
                    updateOrdersSummary(data.summary);
                })
                .catch(error => {
                    console.error('Error loading orders:', error);
                    document.getElementById('ordersTable').innerHTML = 
                        '<p>Error loading orders. Please try again.</p>';
                });
        }

        function displayOrdersTable(orders) {
            const tableContainer = document.getElementById('ordersTable');
            
            if (!orders || orders.length === 0) {
                tableContainer.innerHTML = '<p>No orders found.</p>';
                return;
            }
            
            let tableHTML = `
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            orders.forEach(order => {
                const items = [];
                if (order.small_quantity > 0) items.push(`${order.small_quantity} Small`);
                if (order.medium_quantity > 0) items.push(`${order.medium_quantity} Medium`);
                if (order.large_quantity > 0) items.push(`${order.large_quantity} Large`);
                
                const statusColors = {
                    'pending': '#ffc107',
                    'confirmed': '#17a2b8',
                    'preparing': '#007bff',
                    'out_for_delivery': '#6f42c1',
                    'delivered': '#28a745',
                    'cancelled': '#dc3545'
                };
                
                tableHTML += `
                    <tr data-order-id="${order.id}">
                        <td>${order.order_number}</td>
                        <td>${order.customer_name}</td>
                        <td>${order.customer_phone}</td>
                        <td>${items.join(', ')}</td>
                        <td>¥${parseFloat(order.total_amount).toLocaleString()}</td>
                        <td>
                            <span class="status-badge" style="background: ${statusColors[order.status] || '#6c757d'}">
                                ${order.status}
                            </span>
                        </td>
                        <td>${new Date(order.order_date).toLocaleDateString()}</td>
                        <td>
                            <button onclick="viewOrderDetails(${order.id})" class="btn-view">
                                View
                            </button>
                            <select onchange="updateOrderStatus(${order.id}, this.value)" 
                                    class="status-select">
                                <option value="">Change Status</option>
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

        function updateOrdersSummary(summary) {
            document.getElementById('todayOrders').textContent = summary.today_orders || 0;
            document.getElementById('pendingOrders').textContent = summary.pending_orders || 0;
            document.getElementById('todayRevenue').textContent = 
                '¥' + (parseFloat(summary.today_revenue) || 0).toLocaleString();
            document.getElementById('avgOrderValue').textContent = 
                '¥' + (parseFloat(summary.avg_order_value) || 0).toLocaleString();
        }

        function viewOrderDetails(orderId) {
            window.open(`order_details.php?id=${orderId}`, '_blank');
        }

        function updateOrderStatus(orderId, newStatus) {
            if (!newStatus) return;
            
            if (confirm('Change order status to ' + newStatus + '?')) {
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
                        alert('Order status updated successfully!');
                        loadOrders();
                    } else {
                        alert('Error updating status: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error updating order status');
                });
            }
        }

        function exportOrdersExcel() {
            const status = document.getElementById('orderStatusFilter').value;
            const dateFrom = document.getElementById('orderDateFrom').value;
            const dateTo = document.getElementById('orderDateTo').value;
            const search = document.getElementById('orderSearch').value;
            
            let url = 'orders_data.php?action=export_excel';
            if (status) url += `&status=${status}`;
            if (dateFrom) url += `&date_from=${dateFrom}`;
            if (dateTo) url += `&date_to=${dateTo}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            
            window.location.href = url;
        }
    </script>
</body>
</html>