<?php
// Enable CORS and handle OPTIONS
require_once __DIR__ . '/../header.php';


// Include database connection
require_once __DIR__ . '/../../conn.php';

// Include auth middleware (returns $authUser)
$authUser = require_once __DIR__ . '/../middleware/auth.php';

// Get query parameters
$role = $_GET['role'] ?? null;
$lastChecked = $_GET['last_checked'] ?? null;

// Validate required parameters
if ($role === null) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "role is required"
    ]);
    exit;
}

// Validate role (optional but good practice)
$validRoles = ['admin', 'lecturer', 'student'];
if (!in_array($role, $validRoles)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid role. Must be one of: admin, lecturer, student"
    ]);
    exit;
}

if ($lastChecked === null) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "last_checked is required"
    ]);
    exit;
}

// We'll use the last_checked as a string in the query. We assume it's in a format compatible with MySQL datetime.
// For safety, we could validate it's a string, but we'll rely on prepared statements to prevent SQL injection.

try {
    $stmt = $conn->prepare("
        SELECT id, title, message, type, target_role, is_active, created_at
        FROM notifications
        WHERE (target_role = ? OR target_role = 'all')
          AND is_active = 1
          AND created_at >= ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$role, $lastChecked]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "data" => $notifications,
        "debug" => [
            "role" => $role,
            "lastChecked" => $lastChecked,
            "lastCheckedType" => gettype($lastChecked)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>