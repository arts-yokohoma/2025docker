<?php
include 'db/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$action     = $_POST['action_type'] ?? '';
$staff_id   = $_POST['staff_id'] ?? '';
$manager_id = $_POST['manager_id'] ?? '';

// ✅ Basic validation
if (empty($manager_id)) die("Manager ID missing.");
if (empty($action)) die("No action specified.");

// ===================================================
// 🔹 EDIT STAFF
// ===================================================
if ($action === 'edit_staff') {

    // Fetch current staff
    $stmt = $db->prepare("SELECT * FROM staff WHERE id = :id");
    $stmt->execute([':id' => $staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) die("Staff not found.");

    // Show edit form
    if (!isset($_POST['save'])) {
        ?>
        <h2>Edit Staff</h2>
        <form method="POST" action="action_handler.php">
            <input type="hidden" name="action_type" value="edit_staff">
            <input type="hidden" name="staff_id" value="<?= htmlspecialchars($staff['id']) ?>">
            <input type="hidden" name="manager_id" value="<?= htmlspecialchars($manager_id) ?>">

            Name:<br>
            <input type="text" name="name" value="<?= htmlspecialchars($staff['name']) ?>"><br>

            Mobile:<br>
            <input type="text" name="mobile" value="<?= htmlspecialchars($staff['mobile']) ?>"><br>

            Post:<br>
            <input type="text" name="post" value="<?= htmlspecialchars($staff['post']) ?>"><br>

            Email:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>"><br><br>

            <button type="submit" name="save">Save Changes</button>
        </form>
        <?php
        exit;
    }

    // Update staff
    $update = $db->prepare("
        UPDATE staff 
        SET name = :name,
            mobile = :mobile,
            post = :post,
            email = :email
        WHERE id = :id
    ");

    $update->execute([
        ':name'   => $_POST['name'],
        ':mobile' => $_POST['mobile'],
        ':post'   => $_POST['post'],
        ':email'  => $_POST['email'],
        ':id'     => $staff_id
    ]);

    echo "<script>
            alert('✅ Staff updated successfully!');
            window.location='staff_management.php';
        </script>";
    exit;
}

// ===================================================
// 🔹 DELETE STAFF
// ===================================================
if ($action === 'delete_staff') {

    $del = $db->prepare("DELETE FROM staff WHERE id = :id");
    $del->execute([':id' => $staff_id]);

    echo "<script>
            alert('🗑️ Staff deleted successfully!');
            window.location='staff_management.php';
          </script>";
    exit;
}

// ===================================================
// 🔹 DEFAULT
// ===================================================
die("Unknown action: " . htmlspecialchars($action));