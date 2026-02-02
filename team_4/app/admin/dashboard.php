<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user role (default to 'staff' if not set)
$userRole = $_SESSION['role'] ?? 'staff';
$isSupervisor = ($userRole === 'supervisor');
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
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h1>Pizza Sales Dashboard</h1>
                <div style="display: flex; align-items: center; gap: 15px; font-size: 14px;">
                    <span style="background: #e2e8f0; padding: 6px 12px; border-radius: 20px;">
                        👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <span style="background: <?php echo $isSupervisor ? '#c3e7d4' : '#fce4ec'; ?>; 
                                 color: <?php echo $isSupervisor ? '#22543d' : '#880e4f'; ?>;
                                 padding: 6px 12px; border-radius: 20px; font-weight: 600;">
                        <?php echo $isSupervisor ? '👨‍💼 Supervisor' : '👷 Staff'; ?>
                    </span>
                    <a href="logout.php" style="color: #e53e3e; text-decoration: none; font-weight: 600;">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="tabs">
            <?php if ($isSupervisor): ?>
            <div class="tab active" onclick="switchTab('shifts')">📋 Shift Management</div>
            <?php endif; ?>
            <div class="tab <?php echo !$isSupervisor ? 'active' : ''; ?>" onclick="switchTab('orders')">📦 Orders Management</div> 
        </div>  
        
        <!-- Shift Management Tab (Supervisor Only) -->
        <div id="shifts-tab" class="tab-content" style="<?php echo !$isSupervisor ? 'display: none;' : ''; ?>">
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

    <script src="js/dashboard.js"></script>
</body>
</html>