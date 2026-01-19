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
        <h1>ピザマックへようこそ！ </h1>
    </header>

    <main style="padding: 20px;">
        <?php
        echo "<h2>Team 2 - PHP + MySQL Test</h2>";
        echo "<p>Hello from Team 2!</p>";
        echo "<p>Current server time: " . date('Y-m-d H:i:s') . "</p>";

        // DB ချိတ်ဆက်မှု စစ်ဆေးခြင်း
        
            // Docker Compose မှာ သတ်မှတ်ထားတဲ့ အချက်အလက်များ
            $host = "team_2_mysql"; // container_name ကို host အဖြစ်သုံးရပါမယ်
            $user = "team_2";       // MYSQL_USER
            $pass = "team2pass";    // MYSQL_PASSWORD
            $dbname = "team_2_db";  // MYSQL_DATABASE

            // Connection ဆောက်ခြင်း
            $conn = new mysqli($host, $user, $pass, $dbname);

            // Connection စစ်ဆေးခြင်း
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }else {
                // echo "Connected successfully";
                echo"connected db successfully";
            }

            // စာသားတွေ မှန်အောင် UTF-8 သတ်မှတ်ခြင်း
            $conn->set_charset("utf8mb4");
            ?>
    </main>
</body>
</html>