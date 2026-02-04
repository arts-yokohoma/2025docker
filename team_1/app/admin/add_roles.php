<?php
/**
 * Script to add missing roles (admin, manager, driver, kitchen) to the database
 * Run this once if roles are missing
 * Safe to run multiple times - uses INSERT IGNORE
 */

require_once __DIR__ . '/../config/db.php';

$roles = ['admin', 'manager', 'driver', 'kitchen'];

echo "<pre>";
echo "Adding roles to database...\n\n";

foreach ($roles as $role) {
    $stmt = $mysqli->prepare("INSERT IGNORE INTO roles (name) VALUES (?)");
    $stmt->bind_param("s", $role);
    
    if ($stmt->execute()) {
        if ($mysqli->affected_rows > 0) {
            echo "✅ Added role: $role\n";
        } else {
            echo "ℹ️  Role already exists: $role\n";
        }
    } else {
        echo "❌ Error adding role $role: " . $mysqli->error . "\n";
    }
    $stmt->close();
}

echo "\n🎉 Done!\n";
echo "\nAvailable roles:\n";
$result = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['name']} (ID: {$row['id']})\n";
    }
}
echo "</pre>";
