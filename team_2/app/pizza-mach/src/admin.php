<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>店長ダッシュボード</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* 遅延アラート用のスタイル */
        .late-warning { background-color: #ffcccc !important; animation: flash 2s infinite; }
        @keyframes flash { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }
    </style>
</head>
<body>
<div class="container" style="max-width: 800px;">
    <h1>🛡️ 管理ダッシュボード</h1>
    
    <div id="alert-area" class="alert-box">
        <strong>⚠️ 注意:</strong> 配達時間が長引いている注文があります。ドライバーは戻っていませんか？
    </div>

    <div style="background:#eee; padding:15px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
        <span>本日のドライバー人数:</span>
        <div>
            <input type="number" id="driver-count" value="2" style="width:60px; display:inline-block;">
            <button class="btn btn-blue" style="width:auto; padding:5px 15px; margin:0;" onclick="updateShift()">更新</button>
        </div>
    </div>

    <h3>現在の注文状況</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>顧客情報</th>
                <th>経過時間</th>
                <th>アクション</th>
            </tr>
        </thead>
        <tbody id="order-list"></tbody>
    </table>
</div>

<script>
// 3秒ごとに更新
setInterval(fetchOrders, 3000);
fetchOrders();

async function fetchOrders() {
    let res = await fetch('api.php?action=get_orders');
    let orders = await res.json();
    let html = "";
    let hasLateOrder = false;
    let now = new Date();

    orders.forEach(order => {
        // 経過時間の計算
        let orderTime = new Date(order.created_at);
        let diffMin = Math.floor((now - orderTime) / 60000);
        
        let statusLabel = order.status == 0 ? "🔔 新規" : "🛵 配達中";
        let rowClass = "status-" + order.status;
        let btn = "";

        // アラート判定 (配達中で25分以上経過)
        if (order.status == 2 && diffMin >= 25) {
            rowClass += " late-warning";
            hasLateOrder = true;
        }

        // ボタンロジック (2ステップ: 確認 -> 帰着)
        if (order.status == 0) {
            btn = `<button class="btn btn-green" onclick="setStatus(${order.id}, 2)">確認 & 調理開始</button>`;
        } else if (order.status == 2) {
            btn = `<button class="btn btn-blue" onclick="setStatus(${order.id}, 3)">帰着 (完了)</button>`;
        }

        html += `
            <tr class="${rowClass}">
                <td>#${order.id}</td>
                <td>
                    <strong>${order.customer_name}</strong><br>
                    ${order.pizza_size}サイズ / ${order.zip_code}
                </td>
                <td>${diffMin}分経過<br><small>${statusLabel}</small></td>
                <td>${btn}</td>
            </tr>
        `;
    });

    document.getElementById('order-list').innerHTML = html;

    // アラート表示制御
    let alertBox = document.getElementById('alert-area');
    alertBox.style.display = hasLateOrder ? 'block' : 'none';
}

async function setStatus(id, status) {
    await fetch('api.php?action=update_status', {
        method: 'POST',
        body: JSON.stringify({ id, status })
    });
    fetchOrders();
}

async function updateShift() {
    let count = document.getElementById('driver-count').value;
    await fetch('api.php?action=update_capacity', {
        method: 'POST',
        body: JSON.stringify({ count })
    });
    alert("シフト人数を更新しました。受注可能数が変更されます。");
}
</script>
</body>
</html>