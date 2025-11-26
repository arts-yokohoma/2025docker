<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza Mach Order</title>
    <style>
        /* --- Global CSS (Member 4 Design) --- */
        body { font-family: 'Hiragino Sans', sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; text-align: center; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #ff6600; margin-bottom: 20px; }
        h3 { color: #333; margin-top: 20px; }
        
        /* Input & Buttons */
        input, select { width: 90%; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .btn { width: 100%; padding: 15px; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; color: white; margin-top: 10px; transition: 0.2s; }
        .btn-orange { background-color: #ff6600; }
        .btn-green { background-color: #28a745; }
        .btn-gray { background-color: #6c757d; }
        .btn-blue { background-color: #007bff; }
        .btn:active { transform: scale(0.98); }

        /* Helpers */
        .hidden { display: none; }
        .error { color: red; font-size: 14px; }
        .promise-badge { background: #ffeeba; color: #856404; padding: 5px 10px; border-radius: 20px; font-size: 14px; font-weight: bold; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🍕 Pizza Mach</h1>
    <div class="promise-badge">⏱️ 30分以内にお届け (30 Min Delivery)</div>

    <div id="screen-1">
        <h3>郵便番号を入力してください<br><small>(Zip Code Check)</small></h3>
        <input type="text" id="zipcode" placeholder="例: 123-4567" maxlength="8">
        <p id="zip-error" class="error"></p>
        <button class="btn btn-blue" onclick="checkZip()">エリアを確認 (Check)</button>

        <div id="menu-section" class="hidden">
            <hr style="margin: 20px 0; border: 0.5px solid #eee;">
            <h3>サイズを選んでください<br><small>(Select Size)</small></h3>
            <button class="btn btn-orange" onclick="selectSize('S')">🍕 S Size (¥1,000)</button>
            <button class="btn btn-orange" onclick="selectSize('M')">🍕 M Size (¥2,000)</button>
            <button class="btn btn-orange" onclick="selectSize('L')">🍕 L Size (¥3,000)</button>
        </div>
    </div>

    <div id="screen-2" class="hidden">
        <h3>お届け先情報<br><small>(Delivery Info)</small></h3>
        <p>注文: <strong id="display-size"></strong> Size</p>
        
        <input type="text" id="cust_name" placeholder="お名前 (Name)">
        <input type="tel" id="cust_phone" placeholder="電話番号 (Phone)">
        <input type="text" id="cust_address" placeholder="住所 (Address)">
        
        <button class="btn btn-green" onclick="submitOrder()">注文を確定する (Order)</button>
        <button class="btn btn-gray" onclick="goBack(1)">戻る (Back)</button>
    </div>

    <div id="screen-3" class="hidden">
        <div style="font-size: 50px;">✅</div>
        <h2 style="color: #28a745;">注文完了！</h2>
        <p>ありがとうございました。</p>
        
        <div style="background: #f1f1f1; padding: 15px; border-radius: 10px; text-align: left;">
            <p><strong>お届け予定:</strong> <span id="arrival-time" style="color:red; font-size:18px; font-weight:bold;"></span></p>
            <p><strong>ステータス:</strong> 調理準備中...</p>
        </div>

        <p style="color: #666; font-size: 12px; margin-top: 20px;">
            ※この画面をスクリーンショットで保存してください。
        </p>
        <button class="btn btn-orange" onclick="location.reload()">ホームへ戻る</button>
    </div>

</div>

<script>
    // Data Store
    let orderData = { zip: "", size: "", name: "", phone: "", address: "" };

    // --- STEP 1: Zip Code Check ---
    async function checkZip() {
        let zip = document.getElementById('zipcode').value;
        let errorMsg = document.getElementById('zip-error');
        
        // API ကို လှမ်းစစ်မယ် (Real Check)
        try {
            let response = await fetch('api.php?check_zip=' + zip);
            let data = await response.json();

            if (data.status === 'ok') {
                orderData.zip = zip;
                errorMsg.innerText = "";
                document.getElementById('menu-section').classList.remove('hidden');
            } else {
                errorMsg.innerText = "❌ " + data.msg;
                document.getElementById('menu-section').classList.add('hidden');
            }
        } catch (error) {
            console.error("API Error:", error);
            errorMsg.innerText = "⚠️ Server Error. Please try again.";
        }
    }

    // --- STEP 2: Select Size ---
    function selectSize(size) {
        orderData.size = size;
        document.getElementById('display-size').innerText = size;
        
        // Change Screen
        document.getElementById('screen-1').classList.add('hidden');
        document.getElementById('screen-2').classList.remove('hidden');
    }

    // --- STEP 3: Submit Order (Fixed) ---
    async function submitOrder() {
        // Collect Data
        orderData.name = document.getElementById('cust_name').value;
        orderData.phone = document.getElementById('cust_phone').value;
        orderData.address = document.getElementById('cust_address').value;

        // Validation
        if(!orderData.name || !orderData.phone || !orderData.address) {
            alert("情報を入力してください (Please fill all fields)");
            return;
        }

        // --- REAL API CALL ---
        try {
            let response = await fetch('api.php?action=create_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });
            let result = await response.json();

            if(result.success) {
                // Time Calculation (Now + 30 mins)
                let now = new Date();
                now.setMinutes(now.getMinutes() + 30);
                let timeString = now.getHours() + ":" + String(now.getMinutes()).padStart(2, '0');
                document.getElementById('arrival-time').innerText = timeString + " 以内";
                
                // Show Success Screen
                document.getElementById('screen-2').classList.add('hidden');
                document.getElementById('screen-3').classList.remove('hidden');
            } else {
                alert("⚠️ " + result.message); // Driver Full
            }
        } catch (error) {
            console.error("Order Error:", error);
            alert("System Error! Please contact shop.");
        }
    }

    function goBack(screenNum) {
        document.getElementById('screen-2').classList.add('hidden');
        document.getElementById('screen-' + screenNum).classList.remove('hidden');
    }
</script>

</body>
</html>