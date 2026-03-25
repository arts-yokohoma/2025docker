<?php
session_start();
include '../database/db_conn.php';

// View Mode (Completed or Rejected)
$view = isset($_GET['view']) ? $_GET['view'] : 'completed';

// Date Filter
$date_condition = "";
$selected_date = "";
if (isset($_POST['filter_date']) && !empty($_POST['search_date'])) {
    $selected_date = $_POST['search_date'];
    $date_condition = "AND DATE(order_date) = '$selected_date'";
}

// Query
if ($view == 'rejected') {
    $sql = "SELECT * FROM orders WHERE status = 'Rejected' $date_condition ORDER BY order_date DESC";
    $title = "❌ ပယ်ဖျက်လိုက်သော အော်ဒါများ (Rejected List)";
    $color = "#c0392b";
} else {
    $sql = "SELECT * FROM orders WHERE status = 'Completed' $date_condition ORDER BY order_date DESC";
    $title = "✅ ပြီးစီးသွားသော အော်ဒါများ (Completed List)";
    $color = "#27ae60";
}

$result = $conn->query($sql);
$total_income = 0;
?>