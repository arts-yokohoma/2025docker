<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        .status-0 { background-color: #ffeeba; } /* New Order */
        .status-1 { background-color: #c3e6cb; } /* Cooking */
        .status-2 { background-color: #b8daff; } /* Delivering */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px;">
    <h1>🛡️ Manager Dashboard</h1>
    
    <div style="background:#eee; padding:15px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
        <span><strong>現在のドライバー人数 (Shift):</strong></span>
        <div>
            <input type="number" id="driver_count" value="2" style="width:60px; padding:5px;">
            <button class="btn btn-blue" style="width:auto; padding:5px 15px;" onclick="updateCapacity()">更新 (Update)</button>
        </div>
    </div>

    <h3>注文一覧 (Order List)</h3>
    <table id="order-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Zip / Info</th>
                <th>Size</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="table-body">
            </tbody>
    </table>

    <audio id="notif-sound" src="ding.mp3"></audio>
</div>

<script>
    let currentOrderIds = [];

    // --- 1. Real-time Polling (2 Seconds) ---
    setInterval(fetchOrders, 2000);

    async function fetchOrders() {
        let res = await fetch('api.php?action=get_orders');
        let orders = await res.json();
        let tbody = document.getElementById('table-body');
        tbody.innerHTML = "";

        let newOrderFound = false;

        orders.forEach(order => {
            // Check for new order sound
            if (!currentOrderIds.includes(order.id) && order.status == 0) {
                newOrderFound = true;
            }
            if (!currentOrderIds.includes(order.id)) {
                currentOrderIds.push(order.id);
            }

            // Status Text
            let statusText = ["🟡 New", "🍳 Cooking", "🛵 Delivering"][order.status];
            let rowClass = "status-" + order.status;

            // Buttons Logic
            let buttons = "";
            if(order.status == 0) {
                buttons = `<button class="btn btn-green" onclick="updateStatus(${order.id}, 1)">確認 (Confirm)</button>`;
            } else if (order.status == 1) {
                buttons = `<button class="btn btn-blue" onclick="updateStatus(${order.id}, 2)">配達開始 (Go)</button>`;
            } else if (order.status == 2) {
                buttons = `<button class="btn btn-gray" onclick="updateStatus(${order.id}, 3)">帰着 (Return)</button>`;
            }

            // Render Row
            let row = `
                <tr class="${rowClass}">
                    <td>#${order.id}</td>
                    <td>
                        <strong>${order.customer_name}</strong><br>
                        ${order.phone}<br>
                        Zip: ${order.zip_code}
                    </td>
                    <td>${order.pizza_size}</td>
                    <td>${statusText}</td>
                    <td>${buttons}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        if (newOrderFound) {
            document.getElementById('notif-sound').play().catch(e => console.log("Click page to enable sound"));
        }
    }

    // --- 2. Update Status ---
    async function updateStatus(id, newStatus) {
        await fetch('api.php?action=update_status', {
            method: 'POST',
            body: JSON.stringify({ id: id, status: newStatus })
        });
        fetchOrders(); // Refresh immediately
    }

    // --- 3. Update Shift Capacity ---
    async function updateCapacity() {
        let count = document.getElementById('driver_count').value;
        await fetch('api.php?action=update_capacity', {
            method: 'POST',
            body: JSON.stringify({ count: count })
        });
        alert("Driver Capacity Updated!");
    }
</script>

</body>
</html>