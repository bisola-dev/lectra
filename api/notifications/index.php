<?php

require_once __DIR__ . "/../header.php";
require_once "../../conn.php";
require_once "../../jwt.php";

$method = $_SERVER['REQUEST_METHOD'];
$id     = null;

// Get ID from query parameter (e.g., /api/notifications/index.php?id=123)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}

/* =========================
   LOAD MIDDLEWARE (AUTH CHECK)
   ========================= */
try {
    $authUser = require_once __DIR__ . "/../middleware/auth.php";
    
    // =========================
    // ADMIN CHECK
    // =========================
    if ($authUser->role !== 'admin') {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Forbidden - Admin access required"
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized - Invalid or missing token"
    ]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/notifications/:id - Get specific notification
                $stmt = $conn->prepare("SELECT id, title, message, type, target_role, is_active, created_at FROM notifications WHERE id = ?");
                $stmt->execute([$id]);
                $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$notification) {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Notification not found"
                    ]);
                    break;
                }
                
                echo json_encode([
                    "status" => "success",
                    "notification" => $notification
                ]);
            } else {
                // GET /api/notifications - List all notifications (newest first)
                $stmt = $conn->prepare("SELECT id, title, message, type, target_role, is_active, created_at FROM notifications ORDER BY created_at DESC");
                $stmt->execute();
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "status" => "success",
                    "data" => $notifications,
                    "count" => count($notifications)
                ]);
            }
            break;

        case 'POST':
            // POST /api/notifications - Create new notification (admin only)
            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);
            
            $title = trim($data['title'] ?? '');
            $message = trim($data['message'] ?? '');
            $type = $data['type'] ?? 'info';
            $target_role = $data['target_role'] ?? 'all';

            // Validation
            if ($title === '' || $message === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Title and message are required"
                ]);
                break;
            }

            // Validate type
            $validTypes = ['info', 'warning', 'alert'];
            if (!in_array($type, $validTypes)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid type. Must be one of: info, warning, alert"
                ]);
                break;
            }

            // Validate target_role
            $validTargets = ['admin', 'lecturer', 'student', 'all'];
            if (!in_array($target_role, $validTargets)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid target_role. Must be one of: admin, lecturer, student, all"
                ]);
                break;
            }

            // Create notification
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, target_role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $message, $type, $target_role]);
            
            $notificationId = $conn->lastInsertId();
            
            // Get created notification
            $stmt = $conn->prepare("SELECT id, title, message, type, target_role, is_active, created_at FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            $newNotification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Notification created successfully",
                "notification" => $newNotification
            ]);
            break;

        case 'PUT':
            // PUT /api/notifications/:id - Update notification
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required in URL e.g. /api/notifications/123"
                ]);
                break;
            }

            // Find existing notification FIRST
            $find = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
            $find->execute([$id]);
            $notification = $find->fetch(PDO::FETCH_ASSOC);

            if (!$notification) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Notification not found"
                ]);
                break;
            }

            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);

            // Read fields — fall back to existing data when a field is left blank
            $title = trim($data['title'] ?? '');
            $message = trim($data['message'] ?? '');
            $type = $data['type'] ?? null;
            $target_role = $data['target_role'] ?? null;
            $is_active = isset($data['is_active']) ? (($data['is_active'] === true || $data['is_active'] === 1) ? 1 : 0) : null;

            // Apply existing values if fields are empty
            if ($title === '') { $title = $notification['title']; }
            if ($message === '') { $message = $notification['message']; }
            if ($type === null) { $type = $notification['type']; }
            if ($target_role === null) { $target_role = $notification['target_role']; }
            if ($is_active === null) { $is_active = $notification['is_active']; }

            // Validation
            if ($title === '' || $message === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Title and message are required"
                ]);
                break;
            }

            // Validate type if provided
            if ($type !== null) {
                $validTypes = ['info', 'warning', 'alert'];
                if (!in_array($type, $validTypes)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Invalid type. Must be one of: info, warning, alert"
                    ]);
                    break;
                }
            }

            // Validate target_role if provided
            if ($target_role !== null) {
                $validTargets = ['admin', 'lecturer', 'student', 'all'];
                if (!in_array($target_role, $validTargets)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Invalid target_role. Must be one of: admin, lecturer, student, all"
                    ]);
                    break;
                }
            }

            // Update notification
            $stmt = $conn->prepare("UPDATE notifications SET title = ?, message = ?, type = ?, target_role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $message, $type, $target_role, $is_active, $id]);
            
            // Get updated notification
            $stmt = $conn->prepare("SELECT id, title, message, type, target_role, is_active, created_at FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            $updatedNotification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Notification updated successfully",
                "notification" => $updatedNotification
            ]);
            break;

        case 'DELETE':
            // DELETE /api/notifications/:id - Delete notification
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Check if notification exists
            $stmt = $conn->prepare("SELECT id FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Notification not found"
                ]);
                break;
            }

            // Delete notification
            $del = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Notification deleted successfully"
            ]);
            break;

        default:
            echo json_encode([
                "status" => "error",
                "message" => "Method not allowed. Use GET, POST, PUT, or DELETE."
            ]);
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}