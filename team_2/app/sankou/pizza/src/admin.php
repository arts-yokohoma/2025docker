<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogIQ - 店長ダッシュボード</title>
    <style>
        /* Orange White Modern Bold Theme */
        :root {
            --primary: #FF6600;
            --danger: #e74c3c;
            --success: #27ae60;
            --dark: #333;
            --light: #f4f4f4;
        }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: var(--light); color: var(--dark); padding: 20px; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        h1 { color: var(--primary); margin-top: 0; display: flex; align-items: center; justify-content: space-between; }
        
        /* アラートエリア */
        #alert-area {
            display: none;
            background: #ffe6e6;
            color: #c0392b;
            padding: 15px;
            border-left: 5px solid #c0392b;
            margin-bottom: 20px;
            font-weight: bold;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }

        /* コントロールパネル */
        .control-panel {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .driver-input { font-size: 1.2rem; padding: 5px; width: 60px; text-align: center; border: 2px solid #ddd; border-radius: 5px; }

        /* テーブルスタイル */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; background: #eee; padding: 10px; border-bottom: 2px solid #ddd; }
        td { padding: 15px 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        /* ステータスバッジ */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; color: white; display: inline-block; min-width: 60px; text-align: center; }
        .badge-new { background: var(--primary); }
        .badge-cooking { background: #f39c12; }
        .badge-delivering { background: #3498db; }
        
        /* ボタン */
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; transition: 0.2s; }
        .btn-green { background: var(--success); }
        .btn-green:hover { background: #219150; }
        .btn-blue { background: #3498db; }
        .btn-blue:hover { background: #2980b9; }
        .btn-black { background: var(--dark); }

        /* 遅延行のハイライト */
        .late-row { background-color: #fff0f0; }
        .time-alert { color: var(--danger); font-weight: bold; font-size: 1.1em; }

        /* レスポンシブ対応（スマホでテーブルが崩れないように） */
        @media (max-width: 600px) {
            .control-panel { flex-direction: column; gap: 10px; text-align: center; }
            th, td { display: block; width: 100%; box-sizing: border-box; }
            tr { display: block; margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; }
            th { display: none; } /* ヘッダー非表示 */
        }
    </style>
</head>
<body>

<div class="container">
    <h1>
        <span>🛡️ LogIQ Manager</span>
        <button class="btn btn-black" onclick="location.reload()">🔄 更新</button>
    </h1>
    
    <div id="alert-area">
        🚨 警告: お届け時間が迫っている注文があります！ドライバーを確認してください。
    </div>

    <div class="control-panel">
        <div>
            <strong>現在の稼働ドライバー数</strong>
            <p style="margin:5px 0 0 0; font-size:0.8rem; color:#666;">この数値を元にAIが受注上限を計算します</p>
        </div>
        <div>
            <input type="number" id="driver-count" class="driver-input" value="2" min="1" max="10">
            <span style="margin: 0 10px;">名</span>
            <button class="btn btn-blue" onclick="updateShift()">設定反映</button>
        </div>
    </div>

    <h3>📦 リアルタイム注文リスト</h3>
    <table>
        <thead>
            <tr>
                <th width="10%">ID</th>
                <th width="40%">顧客情報 / 注文内容</th>
                <th width="25%">経過時間 / 状態</th>
                <th width="25%">アクション</th>
            </tr>
        </thead>
        <tbody id="order-list">
            </tbody>
    </table>
</div>

<audio id="alert-sound" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg"></audio>

<script>
// 3秒ごとに更新
let intervalId = setInterval(fetchOrders, 3000);
fetchOrders();

// 初回ロード時に現在の設定値を取得（オプション）
// fetchCurrentSettings(); 

async function fetchOrders() {
    try {
        let res = await fetch('api.php?action=get_orders');
        let orders = await res.json();
        renderOrders(orders);
    } catch(e) {
        console.error("API Error:", e);
    }
}

function renderOrders(orders) {
    let html = "";
    let hasLateOrder = false;
    let now = new Date();

    if (orders.length === 0) {
        document.getElementById('order-list').innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px;">現在の注文はありません</td></tr>';
        return;
    }

    orders.forEach(order => {
        let orderTime = new Date(order.created_at);
        let diffMin = Math.floor((now - orderTime) / 60000); // 経過分数
        
        let statusBadge = "";
        let rowClass = "";
        let timeClass = "";
        let actionBtn = "";

        // ステータス分岐
        if (order.status == 0) {
            statusBadge = '<span class="badge badge-new">🔔 新規受信</span>';
            actionBtn = `<button class="btn btn-green" onclick="setStatus(${order.id}, 2)">確認・調理開始</button>`;
        } else if (order.status == 2) { // 配達中
            statusBadge = '<span class="badge badge-delivering">🛵 配達中</span>';
            actionBtn = `<button class="btn btn-blue" onclick="setStatus(${order.id}, 3)">帰着 (完了)</button>`;
            
            // 遅延判定 (25分以上)
            if (diffMin >= 25) {
                rowClass = "late-row";
                timeClass = "time-alert";
                hasLateOrder = true;
            }
        }

        html += `
            <tr class="${rowClass}">
                <td>#${order.id}</td>
                <td>
                    <div style="font-weight:bold; font-size:1.1rem;">${order.customer_name} 様</div>
                    <div>${order.zip_code} / ${order.pizza_size}サイズ</div>
                    <div style="font-size:0.8rem; color:#666;">${order.address}</div>
                </td>
                <td>
                    <div class="${timeClass}" style="font-size:1.2rem;">${diffMin}分 経過</div>
                    ${statusBadge}
                </td>
                <td>${actionBtn}</td>
            </tr>
        `;
    });

    document.getElementById('order-list').innerHTML = html;

    // アラート制御
    let alertBox = document.getElementById('alert-area');
    if (hasLateOrder) {
        if (alertBox.style.display === 'none' || alertBox.style.display === '') {
            // アラートが表示される瞬間に音を鳴らす
            document.getElementById('alert-sound').play().catch(e=>console.log("Audio play blocked"));
        }
        alertBox.style.display = 'block';
    } else {
        alertBox.style.display = 'none';
    }
}

async function setStatus(id, status) {
    if(!confirm("ステータスを更新しますか？")) return;
    
    await fetch('api.php?action=update_status', {
        method: 'POST',
        body: JSON.stringify({ id, status })
    });
    fetchOrders(); // 即時更新
}

async function updateShift() {
    let count = document.getElementById('driver-count').value;
    await fetch('api.php?action=update_capacity', {
        method: 'POST',
        body: JSON.stringify({ count })
    });
    alert(`シフト人数を ${count}名 に変更しました。\nAIが自動的に受注制限を調整します。`);
}
</script>

</body>
</html>