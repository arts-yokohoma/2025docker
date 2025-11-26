<?php
$conn = new mysqli("localhost", "root", "", "pizza_mach_db");

// Status Update လုပ်တဲ့ Logic
if (isset($_POST['finish_id'])) {
    $id = $_POST['finish_id'];
    // Status ကို 'delivered' လို့ ပြောင်းလိုက်ရင် Capacity ပြန်လွတ်သွားမယ်
    $conn->query("UPDATE orders SET status='delivered' WHERE id=$id");
}

$result = $conn->query("SELECT * FROM orders WHERE status != 'delivered' ORDER BY order_time DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kitchen Display</title>
    <meta http-equiv="refresh" content="10"> <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 15px; text-align: left; }
        .btn-done { background-color: green; color: white; padding: 10px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>👨‍🍳 မီးဖိုချောင် အော်ဒါစာရင်း (မပြီးသေးသည်များ)</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>နာမည် / လိပ်စာ</th>
            <th>Size</th>
            <th>လုပ်ဆောင်ချက်</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $row['id']; ?></td>
            <td>
                <?php echo $row['customer_name']; ?><br>
                <small><?php echo $row['address']; ?></small>
            </td>
            <td style="color:red; font-weight:bold;"><?php echo $row['pizza_size']; ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="finish_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-done">✅ ပို့ဆောင်ပြီးပြီ</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>