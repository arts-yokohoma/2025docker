<?php
// ၁။ Docker အတွက် အချက်အလက်များ
date_default_timezone_set('Asia/Tokyo');
$docker_server = "team_2_mysql";
$docker_user = "team_2";
$docker_pass = "team2pass";
$docker_db = "team_2_db";

// ၂။ Local (XAMPP/VS Code) အတွက် အချက်အလက်များ
$local_server = "localhost";
$local_user = "root";       // XAMPP မှာ ပုံမှန် root ဖြစ်ပါတယ်
$local_pass = "";           // XAMPP မှာ ပုံမှန် password မရှိပါ
$local_db = "team_2_db";    // ဒီ Database နာမည်နဲ့ Local မှာ ဆောက်ထားရပါမယ်

// Exception များကို လက်ခံရန် ပြင်ဆင်ခြင်း
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // ၃။ Docker Server ကို အရင်ကြိုးစားပြီး ချိတ်ပါမယ်
    $conn = @new mysqli($docker_server, $docker_user, $docker_pass, $docker_db);
    //echo "docker db connected :";
    
} catch (mysqli_sql_exception $e) {
    // ၄။ Docker ချိတ်မရလို့ Error တက်သွားရင် ဒီနေရာကို ရောက်လာပါမယ်
    // အခု Localhost ကို ပြောင်းချိတ်ပါမယ်
    try {
        $conn = new mysqli($local_server, $local_user, $local_pass, $local_db);
        echo "connected local-db succesfully:";
    } catch (mysqli_sql_exception $e_local) {
        // ၅။ နှစ်ခုလုံး ချိတ်မရရင်တော့ ဒီစာ ပေါ်ပါမယ်
        die("Connection Failed! <br>" . 
            "Docker Error: " . $e->getMessage() . "<br>" .
            "Local Error: " . $e_local->getMessage());
    }
}

?>