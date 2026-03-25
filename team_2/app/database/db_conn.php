<?php
date_default_timezone_set('Asia/Tokyo');

// Error Reporting (Local မှာပဲ Error ပြမယ်၊ Server ပေါ်မှာ မပြဘူး)
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    error_reporting(0); // Server ပေါ်ရောက်ရင် Error စာတွေ ဖျောက်မယ်
}

// ၁။ Docker အတွက်
$docker_server = "team_2_mysql";
$docker_user = "team_2";
$docker_pass = "team2pass";
$docker_db = "team_2_db";

// ၂။ Local (XAMPP) အတွက်
$local_server = "localhost";
$local_user = "root";
$local_pass = "";
$local_db = "team_2_db";

// ၃။ Real Server (Hosting) အတွက် (နောင်တစ်ချိန်သုံးရန်)
$real_server = "localhost"; // Hosting အများစုသည် localhost ဟုထားရသည်
$real_user = "u123_admin";  // Hosting က ပေးမည့် Username
$real_pass = "password123"; // Hosting က ပေးမည့် Password
$real_db = "u123_pizza_db";

try {
    // (က) Docker ကို အရင်စမ်းမယ်
    $conn = @new mysqli($docker_server, $docker_user, $docker_pass, $docker_db);

} catch (mysqli_sql_exception $e1) {
    try {
        // (ခ) Docker မရရင် Localhost (XAMPP) ကို စမ်းမယ်
        $conn = new mysqli($local_server, $local_user, $local_pass, $local_db);

    } catch (mysqli_sql_exception $e2) {
        try {
            // (ဂ) Local လည်း မရရင် Real Hosting Credentials နဲ့ စမ်းမယ်
            // (Hosting ပေါ်ရောက်မှ ဒီအဆင့်ကို ရောက်လာမှာပါ)
            $conn = new mysqli($real_server, $real_user, $real_pass, $real_db);
            
        } catch (mysqli_sql_exception $e3) {
            // (ဃ) ဘာမှ ချိတ်မရတော့ရင် Error ပြမယ်
            
            // Local ဆိုရင် Error အတိအကျပြမယ်
            if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
                 die("Connection Failed! <br>" . 
                    "Docker: " . $e1->getMessage() . "<br>" .
                    "Local: " . $e2->getMessage() . "<br>" . 
                    "Real Server: " . $e3->getMessage());
            } else {
                // Server ပေါ်ရောက်နေရင် "System Error" လို့ပဲ ပြမယ် (Hack မခံရအောင်)
                die("<h1>System Maintenance</h1><p>We are currently experiencing database issues. Please try again later.</p>");
            }
        }
    }
}

// Unicode (မြန်မာစာ) အတွက်
$conn->set_charset("utf8mb4");

?>