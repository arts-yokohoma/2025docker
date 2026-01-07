<?php
include 'core/db.php'; // လမ်းကြောင်းမှန်အောင် စစ်ပါ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Postal code ရှာတဲ့ Form ဖြစ်ရင်
    if (isset($_POST['postal_code'])) {
        $p_code = $_POST['postal_code'];
        // ရှာဖွေတဲ့ Logic ရေးရန်
    }
    
    // Order ရှာတဲ့ Form ဖြစ်ရင် (Phone number နဲ့)
    if (isset($_POST['phonenumber'])) {
        $phone = $_POST['phonenumber'];
        // Database ကနေ ဆွဲထုတ်ပြမယ့် Logic
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <link rel="icon" href="assets/images/logo.png" type="image/x-icon">
    <title>ピザマック</title>
</head>
<body>
    <header>
        <h1>ピザマックへようこそ！</h1>
    </header>
    <a href="admin/viewdb.php">Admin Dashboard  </a>
        <nav>
            <form action="" method="post" name="postalForm">
                <label>Search for a postal code:</label>
                <input type="text" name="postal_code" placeholder="e.g., 1234567" required>
                <input type="submit" value="Search">
            </form>
        </nav>
        <button onclick="toggleOrderSearch()">Search Order</button>
        <nav id="order-search" style="display: none;">
            <form action="" method="post" name="orderForm">
                <label>Search for order:</label>
                <input type="text" name="phonenumber" placeholder="e.g., 09012345678" required>
                <input type="submit" value="Search">
            </form>
        </nav>
    <script src="./script.js"></script>
</body>
</html>

