<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza Sales Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #333; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .controls { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .chart-container { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        select, button { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pizza Sales Dashboard</h1>
        </div>
        
        <div class="controls">
            <label>Time Period:</label>
            <select id="periodSelect">
                <option value="daily">Daily (Last 7 Days)</option>
                <option value="weekly">Weekly (Last 12 Weeks)</option>
                <option value="monthly">Monthly (Last 12 Months)</option>
            </select>
            
            <label>Pizza Type:</label>
            <select id="pizzaSelect">
                <option value="">All Pizzas</option>
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

    <script>
        let salesChart = null;
        
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

        // Load initial data
        loadSalesData();
    </script>
</body>
</html>