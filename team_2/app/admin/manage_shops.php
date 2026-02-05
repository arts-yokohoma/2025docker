<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
include '../database/db_conn.php';

$edit_mode = false;
$edit_data = ['shop_name' => '', 'latitude' => '', 'longitude' => '', 'website_url' => '', 'id' => ''];

// (၁) အသစ်ထည့်ခြင်း (ADD)
if (isset($_POST['add_shop'])) {
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $url = $_POST['url'];

    $stmt = $conn->prepare("INSERT INTO partner_shops (shop_name, latitude, longitude, website_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdds", $name, $lat, $lng, $url);
    $stmt->execute();
    header("Location: manage_shops.php"); exit();
}

// (၂) ပြင်ဆင်ခြင်း (UPDATE)
if (isset($_POST['update_shop'])) {
    $id = $_POST['shop_id'];
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $url = $_POST['url'];

    $stmt = $conn->prepare("UPDATE partner_shops SET shop_name=?, latitude=?, longitude=?, website_url=? WHERE id=?");
    $stmt->bind_param("sddsi", $name, $lat, $lng, $url, $id);
    $stmt->execute();
    header("Location: manage_shops.php"); exit(); // Update ပြီးရင် မူလနေရာပြန်သွားမယ်
}

// (၃) ဖျက်ခြင်း (DELETE)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM partner_shops WHERE id=$id");
    header("Location: manage_shops.php"); exit();
}

// (၄) ပြင်ရန် ဒေတာလှမ်းယူခြင်း (FETCH FOR EDIT)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM partner_shops WHERE id=$id");
    $edit_data = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Manage Partner Shops</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f6f9; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        /* Form Style */
        .form-box { background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        input { padding: 12px; margin: 5px; width: 48%; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        
        /* Buttons */
        .btn { padding: 10px 15px; border: none; cursor: pointer; border-radius: 4px; color: white; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-green { background: #28a745; }
        .btn-blue { background: #007bff; }
        .btn-red { background: #dc3545; }
        .btn-grey { background: #6c757d; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" style="text-decoration:none; color:#555;">⬅ Dashboard သို့ပြန်သွားရန်</a>
        <h2 style="border-bottom:2px solid #eee; padding-bottom:10px;">
            📍 Partner Shops စီမံခန့်ခွဲရန်
        </h2>

        <div class="form-box">
            <h3 style="margin-top:0;">
                <?php echo $edit_mode ? "✏️ ဆိုင်အချက်အလက် ပြင်ဆင်ရန်" : "➕ ဆိုင်အသစ် ထည့်ရန်"; ?>
            </h3>
            
            <form method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="shop_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <input type="text" name="name" placeholder="ဆိုင်အမည် (ဥပမာ- Tokyo Branch)" required value="<?php echo htmlspecialchars($edit_data['shop_name']); ?>">
                <input type="text" name="url" placeholder="Website Link (URL)" required value="<?php echo htmlspecialchars($edit_data['website_url']); ?>">
                <input type="text" name="lat" placeholder="Latitude (35.xxx)" required value="<?php echo htmlspecialchars($edit_data['latitude']); ?>">
                <input type="text" name="lng" placeholder="Longitude (139.xxx)" required value="<?php echo htmlspecialchars($edit_data['longitude']); ?>">
                
                <div style="margin-top: 15px;">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_shop" class="btn btn-blue">Update Shop</button>
                        <a href="manage_shops.php" class="btn btn-grey">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_shop" class="btn btn-green">Add New Shop</button>
                    <?php endif; ?>
                </div>
            </form>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">* Google Maps တွင် Right Click နှိပ်၍ Lat/Lng ကို ယူပါ။</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="30%">ဆိုင်အမည် & Link</th>
                    <th width="40%">တည်နေရာ (Lat, Lng)</th>
                    <th width="30%">လုပ်ဆောင်ချက်</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $res = $conn->query("SELECT * FROM partner_shops ORDER BY id DESC");
                while($row = $res->fetch_assoc()): 
                ?>
                <tr>
                    <td>
                        <b><?php echo htmlspecialchars($row['shop_name']); ?></b><br>
                        <small><a href="<?php echo $row['website_url']; ?>" target="_blank">Link စမ်းရန်</a></small>
                    </td>
                    <td>
                        <?php echo $row['latitude'] . ", " . $row['longitude']; ?>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-blue">Edit</a>
                        
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-red" onclick="return confirm('ဖျက်မှာ သေချာလား?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>