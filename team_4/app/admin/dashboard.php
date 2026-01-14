<<<<<<< HEAD:team_4/app/admin/dashboard.php
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
            <div class="tab active" onclick="switchTab('sales')">Sales Analysis</div>
            <div class="tab" onclick="switchTab('shifts')">Shift Management</div>
        </div>
        
        <!-- Sales Analysis Tab -->
        <div id="sales-tab" class="tab-content active">
            <div class="controls">
                <label>Time Period:</label>
                <select id="periodSelect">
                    <option value="daily">Daily (Last 7 Days)</option>
                    <option value="weekly">Weekly (Last 12 Weeks)</option>
                    <option value="monthly">Monthly (Last 12 Months)</option>
                </select>
                <label>Pizza Type:</label>
                <select id="pizzaSelect">
                    <option value="">small size</option>
                    <option value="">medium</option>
                    <option value="">local</option>
                </select>
                <button onclick="loadSalesData()">Load Data</button>
                <button onclick="generateReport()">Generate Python Report</button>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>Sales Data</h3>
                <div id="salesTable"></div>
            </div>
        </div>
        
        <!-- Shift Management Tab -->
        <div id="shifts-tab" class="tab-content">
            <div class="controls">
                <h2>Tomorrow's Shift Schedule</h2>
                <p>Set employee shifts for tomorrow to calculate production capacity.</p>
                
                <div class="shift-form">
                    <div class="form-group">
                        <label>Date for Shift Planning:</label>
                        <input type="date" id="shiftDate" value="">
                    </div>
                    
                    <div class="capacity-summary" id="capacitySummary">
                        <h4>Capacity Summary</h4>
                        <p>Set shift details below to see capacity calculations</p>
                    </div>
                    
                    <h3>Shift Slots</h3>
                    <div class="shift-slots">
                        <!-- Morning Shift -->
                        <div class="shift-slot">
                            <h4>Morning Shift (8AM - 4PM)</h4>
                            <div class="form-group">
                                <label>Chefs:</label>
                                <input type="number" id="morningChefs" min="0" value="2" onchange="calculateCapacity()">
                            </div>
                        </div>
                        
                        <!-- Evening Shift -->
                        <div class="shift-slot">
                            <h4>Evening Shift (4PM - 12AM)</h4>
                            <div class="form-group">
                                <label>Chefs:</label>
                                <input type="number" id="eveningChefs" min="0" value="3" onchange="calculateCapacity()">
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-group">
                        <label>Special Notes:</label>
                        <textarea id="shiftNotes" rows="3" placeholder="Any special notes or instructions for tomorrow's shifts..."></textarea>
                    </div>
                    
                    <button onclick="saveShiftSchedule()" style="background: #28a745;">Save Shift Schedule</button>
                    <button onclick="loadShiftSchedule()" style="background: #6c757d;">Load Saved Schedule</button>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>Shift History</h3>
                <div id="shiftHistory"></div>
            </div>
        </div>
        
    </div>

    <script>
        let salesChart = null;
        let capacityChart = null;
        
        // Initialize date field with tomorrow's date
        document.getElementById('shiftDate').value = getTomorrowDate();
        
        // Update efficiency value displays
        document.getElementById('morningEfficiency').addEventListener('input', function() {
            document.getElementById('morningEfficiencyValue').textContent = this.value + '%';
        });
        document.getElementById('eveningEfficiency').addEventListener('input', function() {
            document.getElementById('eveningEfficiencyValue').textContent = this.value + '%';
        });
        document.getElementById('nightEfficiency').addEventListener('input', function() {
            document.getElementById('nightEfficiencyValue').textContent = this.value + '%';
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
            if (tabName === 'shifts') {
                loadShiftSchedule();
            } else if (tabName === 'capacity') {
                calculateCapacity(true);
            }
        }
        
        function getTomorrowDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        }
        
        // Load pizza options
        fetch('sales_data.php?pizzas=true')
            .then(response => response.json())
            .then(pizzas => {
                const select = document.getElementById('pizzaSelect');
                pizzas.forEach(pizza => {
                    const option = document.createElement('option');
                    option.value = pizza.id;
                    option.textContent = pizza.name;
                    select.appendChild(option);
                });
            });
        
        function loadSalesData() {
            const period = document.getElementById('periodSelect').value;
            const pizzaId = document.getElementById('pizzaSelect').value;
            let url = `sales_data.php?period=${period}`;
            if (pizzaId) {
                url += `&pizza_id=${pizzaId}`;
            }
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    displaySalesTable(data);
                    updateChart(data, period);
                });
        }
        
        function displaySalesTable(data) {
            const tableContainer = document.getElementById('salesTable');
            if (data.length === 0) {
                tableContainer.innerHTML = '<p>No sales data found.</p>';
                return;
            }
            let tableHTML = '<table><thead><tr><th>Pizza</th><th>Size</th><th>Period</th><th>Quantity</th><th>Revenue</th></tr></thead><tbody>';
            data.forEach(item => {
                tableHTML += `
                    <tr>
                        <td>${item.pizza_name}</td>
                        <td>${item.size_name}</td>
                        <td>${item.sale_day || item.week_label || item.month_label}</td>
                        <td>${item.total_quantity}</td>
                        <td>$${parseFloat(item.total_revenue).toFixed(2)}</td>
                    </tr>
                `;
            });
            tableHTML += '</tbody></table>';
            tableContainer.innerHTML = tableHTML;
        }
        
        function updateChart(data, period) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            if (salesChart) {
                salesChart.destroy();
            }
            // Group data by period and pizza
            const groupedData = {};
            data.forEach(item => {
                const key = item.sale_day || item.week_label || item.month_label;
                if (!groupedData[key]) {
                    groupedData[key] = {};
                }
                const pizzaKey = `${item.pizza_name} - ${item.size_name}`;
                groupedData[key][pizzaKey] = parseFloat(item.total_revenue);
            });
            const labels = Object.keys(groupedData);
            const pizzaTypes = [...new Set(data.map(item => `${item.pizza_name} - ${item.size_name}`))];
            const datasets = pizzaTypes.map((pizzaType, index) => {
                const backgroundColors = [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ];
                return {
                    label: pizzaType,
                    data: labels.map(label => groupedData[label][pizzaType] || 0),
                    backgroundColor: backgroundColors[index % backgroundColors.length],
                    borderColor: backgroundColors[index % backgroundColors.length],
                    borderWidth: 1
                };
            });
            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: period.charAt(0).toUpperCase() + period.slice(1)
                            }
                        }
                    }
                }
            });
        }
        
        function updateDetailedCapacity(total, morning, evening, night) {
            // Get historical sales data for comparison
            fetch('sales_data.php?period=daily&days=7')
                .then(response => response.json())
                .then(data => {
                    const lastWeekAvg = calculateAverageSales(data);
                    const capacityUtilization = lastWeekAvg > 0 ? 
                        Math.min(100, Math.round((lastWeekAvg / total) * 100)) : 0;
                    
                    const detailedHTML = `
                        <h4>Detailed Capacity Analysis</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                            <div style="background: white; padding: 15px; border-radius: 5px;">
                                <h5>Morning Shift</h5>
                                <p style="font-size: 24px; font-weight: bold; color: #007BFF;">${morning}</p>
                                <p>Estimated pizzas</p>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 5px;">
                                <h5>Evening Shift</h5>
                                <p style="font-size: 24px; font-weight: bold; color: #28a745;">${evening}</p>
                                <p>Estimated pizzas</p>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 5px;">
                                <h5>Night Shift</h5>
                                <p style="font-size: 24px; font-weight: bold; color: #6c757d;">${night}</p>
                                <p>Estimated pizzas</p>
                            </div>
                        </div>
                        <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 5px;">
                            <h5>Capacity vs Historical Demand</h5>
                            <p><strong>Last Week Average Sales:</strong> ${lastWeekAvg} pizzas/day</p>
                            <p><strong>Tomorrow's Capacity:</strong> ${total} pizzas</p>
                            <p><strong>Expected Utilization:</strong> ${capacityUtilization}%</p>
                            <div style="height: 20px; background: #e9ecef; border-radius: 10px; margin-top: 10px;">
                                <div style="height: 100%; width: ${capacityUtilization}%; background: ${capacityUtilization > 90 ? '#dc3545' : capacityUtilization > 70 ? '#ffc107' : '#28a745'}; border-radius: 10px;"></div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('detailedCapacity').innerHTML = detailedHTML;
                });
        }
        
        function calculateAverageSales(data) {
            if (!data || data.length === 0) return 0;
            const totalQuantity = data.reduce((sum, item) => sum + parseInt(item.total_quantity), 0);
            return Math.round(totalQuantity / data.length);
        }
        
        function updateCapacityChart(morning, evening, night) {
            const ctx = document.getElementById('capacityChart').getContext('2d');
            if (capacityChart) {
                capacityChart.destroy();
            }
            
            capacityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Morning', 'Evening', 'Night'],
                    datasets: [{
                        label: 'Production Capacity (Pizzas)',
                        data: [morning, evening, night],
                        backgroundColor: ['#007BFF', '#28a745', '#6c757d'],
                        borderColor: ['#0056B3', '#1e7e34', '#545b62'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Pizzas'
                            }
                        }
                    }
                }
            });
        }
        
        function generatePizzaEstimates(totalCapacity) {
            // Fetch popular pizza types
            fetch('sales_data.php?popular=true')
                .then(response => response.json())
                .then(data => {
                    let estimatesHTML = '<table><thead><tr><th>Pizza Type</th><th>Historical %</th><th>Estimated Quantity</th><th>Prep Time (min)</th></tr></thead><tbody>';
                    
                    // Assuming data has pizza types with percentages
                    data.forEach((pizza, index) => {
                        const percentage = pizza.percentage || (100 / data.length);
                        const estimatedQty = Math.round(totalCapacity * (percentage / 100));
                        const prepTime = estimatedQty * 15; // Assuming 15 min per pizza
                        
                        estimatesHTML += `
                            <tr>
                                <td>${pizza.name}</td>
                                <td>${percentage.toFixed(1)}%</td>
                                <td>${estimatedQty}</td>
                                <td>${prepTime} minutes</td>
                            </tr>
                        `;
                    });
                    
                    estimatesHTML += '</tbody></table>';
                    document.getElementById('pizzaEstimates').innerHTML = estimatesHTML;
                });
        }
        
        function saveShiftSchedule() {
            const shiftData = {
                date: document.getElementById('shiftDate').value,
                morning: {
                    chefs: document.getElementById('morningChefs').value,
                    assistants: document.getElementById('morningAssistants').value,
                    efficiency: document.getElementById('morningEfficiency').value
                },
                evening: {
                    chefs: document.getElementById('eveningChefs').value,
                    assistants: document.getElementById('eveningAssistants').value,
                    efficiency: document.getElementById('eveningEfficiency').value
                },
                night: {
                    chefs: document.getElementById('nightChefs').value,
                    assistants: document.getElementById('nightAssistants').value,
                    efficiency: document.getElementById('nightEfficiency').value
                },
                notes: document.getElementById('shiftNotes').value
            };
            
            // Save to server (you'll need to implement this endpoint)
            fetch('shift_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(shiftData)
            })
            .then(response => response.json())
            .then(data => {
                alert('Shift schedule saved successfully!');
                updateShiftHistory();
            })
            .catch(error => {
                console.error('Error saving shift schedule:', error);
                alert('Shift schedule saved locally (demo mode)');
                // Save to localStorage for demo
                localStorage.setItem('lastShiftSchedule', JSON.stringify(shiftData));
            });
        }
        
        function loadShiftSchedule() {
            // Try to load from server first, then localStorage
            const shiftDate = document.getElementById('shiftDate').value;
            
            fetch(`shift_data.php?date=${shiftDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data && !data.error) {
                        populateShiftForm(data);
                    } else {
                        // Try localStorage
                        const saved = localStorage.getItem('lastShiftSchedule');
                        if (saved) {
                            const savedData = JSON.parse(saved);
                            populateShiftForm(savedData);
                        }
                    }
                })
                .catch(() => {
                    // Fallback to localStorage
                    const saved = localStorage.getItem('lastShiftSchedule');
                    if (saved) {
                        const savedData = JSON.parse(saved);
                        populateShiftForm(savedData);
                    }
                });
        }
        
        function populateShiftForm(data) {
            if (data.morning) {
                document.getElementById('morningChefs').value = data.morning.chefs || 2;
                document.getElementById('morningAssistants').value = data.morning.assistants || 2;
                document.getElementById('morningEfficiency').value = data.morning.efficiency || 85;
                document.getElementById('morningEfficiencyValue').textContent = (data.morning.efficiency || 85) + '%';
            }
            
            if (data.evening) {
                document.getElementById('eveningChefs').value = data.evening.chefs || 3;
                document.getElementById('eveningAssistants').value = data.evening.assistants || 3;
                document.getElementById('eveningEfficiency').value = data.evening.efficiency || 90;
                document.getElementById('eveningEfficiencyValue').textContent = (data.evening.efficiency || 90) + '%';
            }
            
            if (data.night) {
                document.getElementById('nightChefs').value = data.night.chefs || 1;
                document.getElementById('nightAssistants').value = data.night.assistants || 1;
                document.getElementById('nightEfficiency').value = data.night.efficiency || 75;
                document.getElementById('nightEfficiencyValue').textContent = (data.night.efficiency || 75) + '%';
            }
            
            if (data.notes) {
                document.getElementById('shiftNotes').value = data.notes;
            }
            
            calculateCapacity();
        }
        
        function calculateOptimalStaffing() {
            // Fetch historical sales data for tomorrow's day of week
            fetch('sales_data.php?optimal=true')
                .then(response => response.json())
                .then(data => {
                    // Calculate optimal staffing based on historical data
                    const avgPizzas = data.average_pizzas || 150;
                    const dayOfWeek = data.day_of_week || 'normal';
                    
                    // Simple algorithm for optimal staffing
                    let morningChefs, morningAssistants, eveningChefs, eveningAssistants, nightChefs, nightAssistants;
                    
                    if (avgPizzas < 100) { // Slow day
                        morningChefs = 1;
                        morningAssistants = 1;
                        eveningChefs = 2;
                        eveningAssistants = 1;
                        nightChefs = 1;
                        nightAssistants = 0;
                    } else if (avgPizzas < 200) { // Normal day
                        morningChefs = 2;
                        morningAssistants = 2;
                        eveningChefs = 3;
                        eveningAssistants = 2;
                        nightChefs = 1;
                        nightAssistants = 1;
                    } else { // Busy day
                        morningChefs = 3;
                        morningAssistants = 2;
                        eveningChefs = 4;
                        eveningAssistants = 3;
                        nightChefs = 2;
                        nightAssistants = 1;
                    }
                    
                    // Update form with optimal values
                    document.getElementById('morningChefs').value = morningChefs;
                    document.getElementById('morningAssistants').value = morningAssistants;
                    document.getElementById('eveningChefs').value = eveningChefs;
                    document.getElementById('eveningAssistants').value = eveningAssistants;
                    document.getElementById('nightChefs').value = nightChefs;
                    document.getElementById('nightAssistants').value = nightAssistants;
                    
                    calculateCapacity();
                    
                    alert(`Optimal staffing calculated based on historical average of ${avgPizzas} pizzas for this day type.`);
                });
        }
        
        function updateShiftHistory() {
            // Fetch shift history
            fetch('shift_data.php?history=true')
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        document.getElementById('shiftHistory').innerHTML = '<p>No shift history found.</p>';
                        return;
                    }
                    
                    let historyHTML = '<table><thead><tr><th>Date</th><th>Total Staff</th><th>Total Capacity</th><th>Notes</th></tr></thead><tbody>';
                    
                    data.forEach(shift => {
                        historyHTML += `
                            <tr onclick="loadShiftForDate('${shift.date}')" style="cursor: pointer;">
                                <td>${shift.date}</td>
                                <td>${shift.total_staff}</td>
                                <td>${shift.total_capacity} pizzas</td>
                                <td>${shift.notes ? shift.notes.substring(0, 50) + '...' : ''}</td>
                            </tr>
                        `;
                    });
                    
                    historyHTML += '</tbody></table>';
                    document.getElementById('shiftHistory').innerHTML = historyHTML;
                })
                .catch(() => {
                    document.getElementById('shiftHistory').innerHTML = '<p>Unable to load shift history.</p>';
                });
        }
        
        function loadShiftForDate(date) {
            document.getElementById('shiftDate').value = date;
            loadShiftSchedule();
        }
        
        function generateReport() {
            const period = document.getElementById('periodSelect').value;
            const pizzaId = document.getElementById('pizzaSelect').value;
            fetch('http://localhost:5000/generate-report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    period: period,
                    pizza_id: pizzaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.report_path) {
                    alert('Report generated successfully!');
                    window.open(data.report_path, '_blank');
                } else {
                    alert('Error generating report: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error connecting to Python service: ' + error);
            });
        }
        
        // Initialize on load
        loadSalesData();
        updateShiftHistory();
        
        // Calculate initial capacity
        setTimeout(() => {
            calculateCapacity();
        }, 100);
    </script>
</body>
</html>

