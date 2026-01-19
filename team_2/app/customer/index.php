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
        <h1>ピザマックへようこそ！
        </h1>
    </header>
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

