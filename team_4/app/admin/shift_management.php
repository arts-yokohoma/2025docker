<?php
session_start();
require_once '../db/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'save_shift') {
    $date = $_POST['date'] ?? '';
    $morningStaff = intval($_POST['morning_staff'] ?? 0);
    $eveningStaff = intval($_POST['evening_staff'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    if (!$date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Date is required']);
        exit;
    }
    
    try {
        // Morning shift
        $stmt = $pdo->prepare("
            INSERT INTO staff_shifts (shift_date, shift_type, staff_count, is_active, notes)
            VALUES (?, 'morning', ?, true, ?)
            ON CONFLICT (shift_date, shift_type) 
            DO UPDATE SET staff_count = ?, is_active = true, notes = ?, updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$date, $morningStaff, $notes, $morningStaff, $notes]);
        
        // Evening shift
        $stmt = $pdo->prepare("
            INSERT INTO staff_shifts (shift_date, shift_type, staff_count, is_active, notes)
            VALUES (?, 'evening', ?, true, ?)
            ON CONFLICT (shift_date, shift_type) 
            DO UPDATE SET staff_count = ?, is_active = true, notes = ?, updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$date, $eveningStaff, $notes, $eveningStaff, $notes]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Shift schedule saved successfully!',
            'date' => $date,
            'morning_staff' => $morningStaff,
            'evening_staff' => $eveningStaff
        ]);
        
    } catch (Exception $e) {
        error_log("Save shift error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_shift') {
    $date = $_GET['date'] ?? '';
    
    if (!$date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Date is required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT shift_type, staff_count, notes
            FROM staff_shifts
            WHERE shift_date = ?
            AND shift_type IN ('morning', 'evening')
            ORDER BY shift_type
        ");
        
        $stmt->execute([$date]);
        $shifts = $stmt->fetchAll();
        
        $result = [
            'success' => true,
            'morning_staff' => 0,
            'evening_staff' => 0,
            'notes' => ''
        ];
        
        foreach ($shifts as $shift) {
            if ($shift['shift_type'] === 'morning') {
                $result['morning_staff'] = $shift['staff_count'];
            } else {
                $result['evening_staff'] = $shift['staff_count'];
            }
            if ($shift['notes']) {
                $result['notes'] = $shift['notes'];
            }
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Get shift error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

if ($action === 'get_shift_history') {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT shift_date, 
                   MAX(CASE WHEN shift_type = 'morning' THEN staff_count ELSE 0 END) as morning_staff,
                   MAX(CASE WHEN shift_type = 'evening' THEN staff_count ELSE 0 END) as evening_staff,
                   MAX(notes) as notes,
                   MAX(created_at) as created_at
            FROM staff_shifts
            WHERE shift_date >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY shift_date
            ORDER BY shift_date DESC
            LIMIT 20
        ");
        
        $stmt->execute();
        $history = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'history' => $history]);
        
    } catch (Exception $e) {
        error_log("Get history error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
