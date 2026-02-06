<?php
/**
 * Seed test users into the database
 * 
 * Creates test users for all roles:
 * - 3 Admin users
 * - 4 Manager users
 * - 5 Kitchen users
 * - 6 Driver users
 * 
 * All test users have password: "password123"
 * 
 * Usage: Run this file in browser or CLI: php seed_users.php
 */

require __DIR__ . '/../config/db.php';

// Ensure UTF-8 encoding
$mysqli->set_charset('utf8mb4');

echo "<pre>";
echo "========================================\n";
echo "  Seeding Test Users\n";
echo "========================================\n\n";

// Read the SQL file
$sqlFile = __DIR__ . '/seed_test_users.sql';
if (!file_exists($sqlFile)) {
    die("❌ SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Split SQL by semicolons (simple approach)
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        // Remove comments and empty statements
        $stmt = preg_replace('/^--.*$/m', '', $stmt);
        $stmt = trim($stmt);
        return !empty($stmt) && strpos($stmt, '/*') !== 0;
    }
);

$successCount = 0;
$errorCount = 0;
$errors = [];

// Execute each statement
foreach ($statements as $i => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Show what we're executing (truncate long statements)
    $preview = strlen($statement) > 80 ? substr($statement, 0, 77) . '...' : $statement;
    echo "Executing: $preview\n";
    
    $result = $mysqli->query($statement);
    
    if ($result === false) {
        $errorCount++;
        $error = "Error: " . $mysqli->error;
        $errors[] = [
            'statement' => $statement,
            'error' => $mysqli->error
        ];
        echo "  ❌ $error\n\n";
    } else {
        $successCount++;
        
        // If it's a SELECT, show results
        if (is_object($result) && $result instanceof mysqli_result) {
            if ($result->num_rows > 0) {
                echo "  ✅ Success! Results:\n";
                echo "  " . str_repeat("-", 70) . "\n";
                
                // Show headers
                $fields = $result->fetch_fields();
                $headers = [];
                foreach ($fields as $field) {
                    $headers[] = $field->name;
                }
                echo "  " . implode(" | ", $headers) . "\n";
                echo "  " . str_repeat("-", 70) . "\n";
                
                // Show data
                while ($row = $result->fetch_assoc()) {
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value ?? 'NULL';
                    }
                    echo "  " . implode(" | ", $values) . "\n";
                }
                echo "  " . str_repeat("-", 70) . "\n";
            } else {
                echo "  ✅ Success! (no results)\n";
            }
        } else {
            $affected = $mysqli->affected_rows;
            if ($affected > 0) {
                echo "  ✅ Success! ($affected rows affected)\n";
            } else {
                echo "  ✅ Success!\n";
            }
        }
        echo "\n";
    }
}

// Summary
echo "\n========================================\n";
echo "  Summary\n";
echo "========================================\n";
echo "✅ Successful statements: $successCount\n";
echo "❌ Failed statements: $errorCount\n";

if ($errorCount > 0) {
    echo "\n⚠️  Errors encountered:\n";
    foreach ($errors as $i => $error) {
        echo "\nError #" . ($i + 1) . ":\n";
        echo "  Statement: " . substr($error['statement'], 0, 100) . "...\n";
        echo "  Error: " . $error['error'] . "\n";
    }
}

echo "\n========================================\n";
echo "🎉 Done!\n";
echo "========================================\n\n";

echo "Test Users Created:\n";
echo "  👑 Admins: 3 users (test_admin1, test_admin2, test_admin3)\n";
echo "  👔 Managers: 4 users (test_manager1-4)\n";
echo "  👨‍🍳 Kitchen: 5 users (test_kitchen1-5)\n";
echo "  🚗 Drivers: 6 users (test_driver1-6)\n\n";
echo "All passwords: password123\n\n";

echo "Login Examples:\n";
echo "  Username: test_admin1    | Password: password123 | Role: Admin\n";
echo "  Username: test_manager1  | Password: password123 | Role: Manager\n";
echo "  Username: test_kitchen1  | Password: password123 | Role: Kitchen\n";
echo "  Username: test_driver1   | Password: password123 | Role: Driver\n";

echo "</pre>";
