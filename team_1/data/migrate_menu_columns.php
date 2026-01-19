<?php
/**
 * Migration: Rename menu table columns from create_date/update_date to create_time/update_time
 * This ensures consistency across all tables in the database.
 * 
 * Run this file once to migrate existing database.
 */

require __DIR__ . '/../config/db.php';

echo "<pre>";
echo "Starting migration: menu table columns...\n\n";

try {
    // Check if old columns exist
    $result = $mysqli->query("SHOW COLUMNS FROM menu LIKE 'create_date'");
    $hasOldColumns = $result && $result->num_rows > 0;
    
    if ($hasOldColumns) {
        echo "Old columns found. Renaming...\n";
        
        // Rename create_date to create_time
        $mysqli->query("ALTER TABLE menu CHANGE COLUMN create_date create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        if ($mysqli->error) {
            throw new Exception("Error renaming create_date: " . $mysqli->error);
        }
        echo "✅ Renamed create_date → create_time\n";
        
        // Rename update_date to update_time
        $mysqli->query("ALTER TABLE menu CHANGE COLUMN update_date update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        if ($mysqli->error) {
            throw new Exception("Error renaming update_date: " . $mysqli->error);
        }
        echo "✅ Renamed update_date → update_time\n";
        
        echo "\n🎉 Migration completed successfully!\n";
    } else {
        echo "ℹ️  Migration already applied or using new schema. No changes needed.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>";
