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

        // Error တွေ အကုန်ပြခိုင်းမယ် (ဗြောင်မဖြစ်အောင်)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // Host ကို team_2_mysql လို့ ပြောင်းလိုက်ပါတယ်
            $mysqli = new mysqli("team_2_mysql", "user", "password", "pizza_db");

            echo "<p style='color: green;'>✅ MySQL Connection successful!</p>";
            echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";

        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ MySQL Connection failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </main>
</body>
</html>