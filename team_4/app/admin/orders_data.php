<?php
// admin/orders_data.php
session_start();
require_once '../db/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get action parameter
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_orders':
        getOrders();
        break;
    case 'update_status':
        updateOrderStatus();
        break;
    case 'export_excel':
        exportOrdersToExcel();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getOrders() {
    global $pdo;
    
    try {
        // Get filter parameters
        $status = $_GET['status'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Build query
        $query = "SELECT * FROM orders WHERE 1=1";
        $params = [];
        
        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        if ($dateFrom) {
            $query .= " AND DATE(order_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $query .= " AND DATE(order_date) <= ?";
            $params[] = $dateTo;
        }
        
        if ($search) {
            $query .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR order_number LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY order_date DESC LIMIT 100";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // Get summary statistics
        $summary = getOrdersSummary();
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'summary' => $summary
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getOrdersSummary() {
    global $pdo;
    
    try {
        $today = date('Y-m-d');
        
        // Today's orders count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = ?");
        $stmt->execute([$today]);
        $todayOrders = $stmt->fetch()['count'];
        
        // Pending orders count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'confirmed', 'preparing')");
        $stmt->execute();
        $pendingOrders = $stmt->fetch()['count'];
        
        // Today's revenue
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(order_date) = ?");
        $stmt->execute([$today]);
        $todayRevenue = $stmt->fetch()['revenue'] ?? 0;
        
        // Average order value
        $stmt = $pdo->prepare("SELECT AVG(total_amount) as avg_value FROM orders WHERE DATE(order_date) = ?");
        $stmt->execute([$today]);
        $avgOrderValue = $stmt->fetch()['avg_value'] ?? 0;
        
        return [
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'today_revenue' => $todayRevenue,
            'avg_order_value' => $avgOrderValue
        ];
        
    } catch (Exception $e) {
        return [
            'today_orders' => 0,
            'pending_orders' => 0,
            'today_revenue' => 0,
            'avg_order_value' => 0
        ];
    }
}

function updateOrderStatus() {
    global $pdo;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = $data['order_id'] ?? 0;
        $newStatus = $data['status'] ?? '';
        
        if (!$orderId || !$newStatus) {
            echo json_encode(['error' => 'Missing parameters']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
    }
}

function exportOrdersToExcel() {
    global $pdo;
    
    try {
        // Get filter parameters
        $status = $_GET['status'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Build query
        $query = "SELECT * FROM orders WHERE 1=1";
        $params = [];
        
        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        if ($dateFrom) {
            $query .= " AND DATE(order_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $query .= " AND DATE(order_date) <= ?";
            $params[] = $dateTo;
        }
        
        if ($search) {
            $query .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR order_number LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY order_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // Generate Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.xls"');
        
        echo "Order Number\tCustomer Name\tPhone\tEmail\tAddress\tSmall Qty\tMedium Qty\tLarge Qty\tTotal Amount\tStatus\tOrder Date\tInstructions\n";
        
        foreach ($orders as $order) {
            echo $order['order_number'] . "\t";
            echo $order['customer_name'] . "\t";
            echo $order['customer_phone'] . "\t";
            echo $order['customer_email'] . "\t";
            echo str_replace(["\t", "\n", "\r"], ' ', $order['customer_address']) . "\t";
            echo $order['small_quantity'] . "\t";
            echo $order['medium_quantity'] . "\t";
            echo $order['large_quantity'] . "\t";
            echo $order['total_amount'] . "\t";
            echo $order['status'] . "\t";
            echo $order['order_date'] . "\t";
            echo str_replace(["\t", "\n", "\r"], ' ', $order['special_instructions'] ?? '') . "\n";
        }
        
        exit;
        
    } catch (Exception $e) {
        echo "Error generating Excel: " . $e->getMessage();
        exit;
    }
}
?>