<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ピザマッハ注文</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>🍕 ピザマッハ</h1>
    <div style="text-align:center;"><span class="badge">⏱️ 30分以内にお届け</span></div>

    <div id="screen-zip">
        <h2>配達エリアの確認</h2>
        <input type="text" id="zipcode" placeholder="郵便番号 (例: 123-4567)" maxlength="8">
        <p id="zip-msg" class="error"></p>
        <button class="btn btn-blue" onclick="checkZip()">エリアを確認する</button>
    </div>

    <div id="screen-form" class="hidden">
        <h2>メニュー選択</h2>
        <select id="size">
            <option value="S">Sサイズ (¥1,000)</option>
            <option value="M">Mサイズ (¥2,000)</option>
            <option value="L">Lサイズ (¥3,000)</option>
        </select>
        
        <h2>お届け先情報</h2>
        <input type="text" id="name" placeholder="お名前">
        <input type="tel" id="phone" placeholder="電話番号">
        <input type="text" id="address" placeholder="ご住所">
        
        <button class="btn btn-green" onclick="submitOrder()">注文を確定する</button>
        <button class="btn btn-gray" onclick="location.reload()">戻る</button>
    </div>

    <div id="screen-success" class="hidden" style="text-align: center;">
        <h2 style="color:#27ae60; font-size: 2rem;">注文完了！</h2>
        <p>以下の番号をドライバーにお伝えください</p>
        <div style="border: 3px dashed #333; padding: 20px; margin: 20px 0;">
            ORDER ID<br>
            <span id="order-id" style="font-size: 3rem; font-weight: bold; color: #d35400;">#---</span>
        </div>
        <p>お届け予定: <strong>30分以内</strong></p>
        <small>※この画面を閉じてお待ちください</small>
    </div>
</div>

<script>
let orderZip = "";

async function checkZip() {
    let zip = document.getElementById('zipcode').value;
    let res = await fetch(`api.php?check_zip=${zip}`);
    let data = await res.json();
    
    if (data.status === 'ok') {
        orderZip = zip;
        document.getElementById('screen-zip').classList.add('hidden');
        document.getElementById('screen-form').classList.remove('hidden');
    } else {
        document.getElementById('zip-msg').innerText = "申し訳ありません。配達エリア外です。";
    }
}

async function submitOrder() {
    let data = {
        zip: orderZip,
        size: document.getElementById('size').value,
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value
    };

    if (!data.name || !data.phone || !data.address) return alert("全ての項目を入力してください");

    let res = await fetch('api.php?action=create_order', {
        method: 'POST',
        body: JSON.stringify(data)
    });
    let result = await res.json();

    if (result.success) {
        document.getElementById('order-id').innerText = "#" + result.id;
        document.getElementById('screen-form').classList.add('hidden');
        document.getElementById('screen-success').classList.remove('hidden');
    } else {
        alert(result.message); // 満員の場合のメッセージ
    }
}
</script>
</body>
</html>