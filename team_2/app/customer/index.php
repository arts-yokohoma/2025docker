<?php
include '../database/db_conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ၁။ Postal Code Search အတွက် စစ်ဆေးခြင်း
    if (isset($_POST['postal_code'])) {
        $postal_code = $_POST['postal_code'];
        $stmt = $conn->prepare("SELECT * FROM locations WHERE zip_code = ?");
        $stmt->bind_param("s", $postal_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0){
            $found_data = $result->fetch_assoc();
            include 'order_form.php';
        } else {
            echo "<h2>No address found for postal code: " . htmlspecialchars($postal_code) . "</h2>";
        }
    }

    // ၂။ Phone Number Check အတွက် စစ်ဆေးခြင်း (isset နဲ့ အရင်စစ်ရပါမယ်)
    if (isset($_POST['checkphonenumber'])) {
        $chkod = $_POST['checkphonenumber'];
        $stmt = $conn->prepare("SELECT * FROM orders WHERE phonenumber = ?");
        $stmt->bind_param("s", $chkod);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $order_data = $result->fetch_assoc();
            echo "<h2>Order Found:</h2>";
            echo "Order ID: " . htmlspecialchars($order_data['id']) . "<br>";
            echo "Status: " . htmlspecialchars($order_data['status']) . "<br>";
        } else {
            echo "<h2>No order found for phone number: " . htmlspecialchars($chkod) . "</h2>";
        }
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
        <h1>ピザマックへようこそ！
        </h1>
    </header>
        <nav>
            <form action="" method="post" name="postalForm" id="zipform">
                <label>Search for a postal code:</label>
                <input type="text" name="postal_code" placeholder="e.g., 1234567" required>
                <input type="submit" value="Search">
            </form>
        </nav>
        <button onclick="showform()" id="chkbtn" type="submit" name=checkod>check order</button>
        <form id="chkform" action="check_order.php" method="post" hidden>
            <label for="phone">phone</label>
            <input type="text" name="checkphonenumber" required>
            <input type="submit" value="check">
        </form>
        
    <main>
        <script>
        
        // စာမျက်နှာပွင့်တာနဲ့ အလုပ်လုပ်မယ်
        window.onload = function() {
            getLocation();
        };

        function getLocation() {
            // Browser က Geolocation ထောက်ပံ့လား စစ်တာ
            if (navigator.geolocation) {
                // Permission တောင်းမယ်
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else { 
                alert("Geolocation is not supported by this browser.");
            }
        }

        // Permission ပေးလိုက်ရင် ဒီ function အလုပ်လုပ်မယ်
        function showPosition(position) {
            let lat = position.coords.latitude;
            let long = position.coords.longitude;
            
            // လက်ရှိ Latitude နဲ့ Longitude ကို ရပြီ
            document.getElementById("status-msg").innerHTML = 
                "✅ Location ရရှိပါသည်: " + lat + ", " + long;

            // ဒီအဆင့်မှာ Lat/Long ကိုသုံးပြီး မြေပုံပြတာ (သို့) Address ရှာတာ ဆက်လုပ်လို့ရပြီ
            console.log("Lat: " + lat + ", Long: " + long);
        }

        // Permission မပေးဘဲ Block လိုက်ရင် (သို့) Error တက်ရင်
        function showError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    document.getElementById("status-msg").innerHTML = "❌ User denied the request for Geolocation.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    document.getElementById("status-msg").innerHTML = "❌ Location information is unavailable.";
                    break;
                case error.TIMEOUT:
                    document.getElementById("status-msg").innerHTML = "❌ The request to get user location timed out.";
                    break;
                case error.UNKNOWN_ERROR:
                    document.getElementById("status-msg").innerHTML = "❌ An unknown error occurred.";
                    break;
            }
        }
    

            let zip = document.getElementById("zipform");
            let fo = document.getElementById("chkbtn");
            let chkform = document.getElementById("chkform");
            fo.addEventListener("click", function() {
                if (chkform.hidden) {
                    chkform.hidden = false;
                    fo.innerHTML = 'Back'; // Fixed typo from 'changetext.innterHTML' to 'innerHTML'
                    zip.hidden = true;
                } else {
                    zip.hidden = false;
                    chkform.hidden = true;
                }
            });
        </script>
</body>
</html>
<?php
//echo "<h2>Database Locations Table</h2>";
//include '../database/show_tb.php';
?>
