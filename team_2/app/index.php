<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo.png" type="image/x-icon">
    <title>ピザマック</title>
</head>
<body>
    <header>
        <h1>ピザマックへようこそ！</h1>
    </header>

    <main style="padding: 20px;">
        <?php
        echo "<h2>Team 2 - PHP + MySQL Test</h2>";
        echo "<p>Hello from Team 2!</p>";
        echo "<p>Current server time: " . date('Y-m-d H:i:s') . "</p>";

        // Error တွေ အကုန်ပြခိုင်းမယ် (ဗြောင်မဖြစ်အောင်)
      include_once __DIR__ . '/core/db.php';
        ?> 
    </main>
</body>
</html>