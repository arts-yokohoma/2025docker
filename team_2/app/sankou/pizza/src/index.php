<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogIQ - Pizza Mach</title>
    <style>
        /* Orange White Modern Bold Theme */
        :root {
            --primary: #FF6600; /* LogIQ Orange */
            --secondary: #333333;
            --light: #FFF5E6;
            --white: #ffffff;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 20px;
            color: var(--secondary);
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(255, 102, 0, 0.15);
        }
        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 5px;
        }
        h2 { font-size: 1.2rem; margin-top: 20px; }
        .badge {
            background: var(--secondary);
            color: #fff;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 20px;
        }
        input, select {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        input:focus { border-color: var(--primary); outline: none; }
        
        /* Buttons */
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-orange { background-color: var(--primary); color: white; }
        .btn-orange:hover { background-color: #e65c00; }
        .btn-gray { background-color: #eee; color: #555; }
        .btn-outline { 
            background: transparent; 
            border: 2px solid var(--primary); 
            color: var(--primary); 
            margin-top: 20px;
        }

        .hidden { display: none; }
        .error { color: red; font-size: 0.9rem; }
        
        /* Timer Style */
        .timer-box {
            text-align: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🍕 LogIQ</h1>
    <div style="text-align:center;">
        <span class="badge">Pizza Mach Ordering System</span>
    </div>

    <div id="screen-zip">
        <h2>📍 配達エリア確認</h2>
        <p style="font-size:0.9rem; color:#666;">郵便番号を入力してください</p>
        <input type="tel" id="zipcode" placeholder="例: 123-4567" maxlength="8">
        <p id="zip-msg" class="error"></p>
        <button class="btn btn-orange" onclick="checkZip()">注文に進む</button>
        
        <hr style="margin: 30px 0; border:0; border-top:1px solid #eee;">
        <button class="btn btn-outline" onclick="showRecheck()">🚚 注文状況を確認する</button>
    </div>

    <div id="screen-form" class="hidden">
        <h2>🍕 メニュー選択</h2>
        <select id="size">
            <option value="S">マルゲリータ S (¥1,000)</option>
            <option value="M">マルゲリータ M (¥2,000)</option>
            <option value="L">マルゲリータ L (¥3,000)</option>
        </select>
        
        <h2>👤 お届け先</h2>
        <input type="text" id="name" placeholder="お名前">
        <input type="tel" id="phone" placeholder="電話番号 (IDとして使用)">
        <input type="text" id="address" placeholder="ご住所">
        
        <button class="btn btn-orange" onclick="submitOrder()">注文を確定する</button>
        <button class="btn btn-gray" onclick="location.reload()">戻る</button>
    </div>

    <div id="screen-success" class="hidden" style="text-align: center;">
        <h2 style="color:#27ae60;">注文を受け付けました</h2>
        <p>ただいまピザを作っています！</p>
        
        <div class="timer-box" id="countdown">25:00</div>
        <p>お届け予定</p>

        <div style="background:#f9f9f9; padding:15px; border-radius:10px; margin-top:20px;">
            ORDER ID: <span id="order-id" style="font-weight:bold;">#---</span>
        </div>
        <button class="btn btn-gray" onclick="location.reload()">トップへ戻る</button>
    </div>

    <div id="screen-recheck" class="hidden">
        <h2>🚚 注文状況の確認</h2>
        <p>注文時の電話番号を入力してください</p>
        <input type="tel" id="check-phone" placeholder="電話番号">
        <button class="btn btn-orange" onclick="checkStatus()">状況を見る</button>
        <button class="btn btn-gray" onclick="location.reload()">戻る</button>
    </div>
</div>

<script>
let orderZip = "";

// 1. Check Zip Code
async function checkZip() {
    let zip = document.getElementById('zipcode').value;
    // Mock API Call
    if (zip.length >= 3) {
        orderZip = zip;
        document.getElementById('screen-zip').classList.add('hidden');
        document.getElementById('screen-form').classList.remove('hidden');
    } else {
        document.getElementById('zip-msg').innerText = "正しい郵便番号を入力してください";
    }
}

// 2. Submit Order
async function submitOrder() {
    let phone = document.getElementById('phone').value;
    
    // Simulate API Success
    if (phone) {
        document.getElementById('order-id').innerText = "#" + Math.floor(Math.random() * 10000);
        document.getElementById('screen-form').classList.add('hidden');
        document.getElementById('screen-success').classList.remove('hidden');
        startTimer();
    } else {
        alert("電話番号は必須です");
    }
}

// 3. Show Recheck Screen
function showRecheck() {
    document.getElementById('screen-zip').classList.add('hidden');
    document.getElementById('screen-recheck').classList.remove('hidden');
}

// 4. Check Status Logic
function checkStatus() {
    let phone = document.getElementById('check-phone').value;
    if(phone) {
        document.getElementById('screen-recheck').classList.add('hidden');
        document.getElementById('screen-success').classList.remove('hidden');
        // Resume Timer for demo
        document.getElementById('countdown').innerText = "12:45"; 
    } else {
        alert("番号を入力してください");
    }
}

// 5. Countdown Timer Logic
function startTimer() {
    let duration = 25 * 60; // 25 minutes
    let timer = duration, minutes, seconds;
    setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        document.getElementById('countdown').textContent = minutes + ":" + seconds;
        if (--timer < 0) timer = duration;
    }, 1000);
}
</script>
</body>
</html>