<?php

require_once __DIR__ . "/../header.php";
require_once "../../conn.php";
require_once "../../jwt.php";

$method = $_SERVER['REQUEST_METHOD'];
$id     = null;

// Get ID from query parameter (e.g., /api/announcements/index.php?id=123)
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
                // GET /api/announcements/:id - Get specific announcement
                $stmt = $conn->prepare("SELECT id, title, body, is_emergency, created_at FROM announcements WHERE id = ?");
                $stmt->execute([$id]);
                $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$announcement) {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Announcement not found"
                    ]);
                    break;
                }
                
                echo json_encode([
                    "status" => "success",
                    "announcement" => $announcement
                ]);
            } else {
                // GET /api/announcements - List all announcements (newest first)
                $stmt = $conn->prepare("SELECT id, title, body, is_emergency, created_at FROM announcements ORDER BY created_at DESC");
                $stmt->execute();
                $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "status" => "success",
                    "data" => $announcements,
                    "count" => count($announcements)
                ]);
            }
            break;

        case 'POST':
            // POST /api/announcements - Create new announcement (admin only)
            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);
            
            $title = trim($data['title'] ?? '');
            $body = trim($data['body'] ?? '');
            $is_emergency = isset($data['is_emergency']) && $data['is_emergency'] === true ? 1 : 0;

            // Validation
            if ($title === '' || $body === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Title and body are required"
                ]);
                break;
            }

            // Create announcement
            $stmt = $conn->prepare("INSERT INTO announcements (title, body, is_emergency) VALUES (?, ?, ?)");
            $stmt->execute([$title, $body, $is_emergency]);
            
            $announcementId = $conn->lastInsertId();
            
            // Get created announcement
            $stmt = $conn->prepare("SELECT id, title, body, is_emergency, created_at FROM announcements WHERE id = ?");
            $stmt->execute([$announcementId]);
            $newAnnouncement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Announcement created successfully",
                "announcement" => $newAnnouncement
            ]);
            break;

        case 'PUT':
            // PUT /api/announcements/:id - Update announcement
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required in URL e.g. /api/announcements/123"
                ]);
                break;
            }

            // Find existing announcement FIRST
            $find = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
            $find->execute([$id]);
            $announcement = $find->fetch(PDO::FETCH_ASSOC);

            if (!$announcement) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Announcement not found"
                ]);
                break;
            }

            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);

            // Read fields — fall back to existing data when a field is left blank
            $title = trim($data['title'] ?? '');
            $body = trim($data['body'] ?? '');
            $is_emergency = isset($data['is_emergency']) ? (($data['is_emergency'] === true) ? 1 : 0) : null;

            // Apply existing values if fields are empty
            if ($title === '') { $title = $announcement['title']; }
            if ($body === '') { $body = $announcement['body']; }
            if ($is_emergency === null) { $is_emergency = $announcement['is_emergency']; }

            // Validation
            if ($title === '' || $body === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Title and body are required"
                ]);
                break;
            }

            // Update announcement
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, body = ?, is_emergency = ? WHERE id = ?");
            $stmt->execute([$title, $body, $is_emergency, $id]);
            
            // Get updated announcement
            $stmt = $conn->prepare("SELECT id, title, body, is_emergency, created_at FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $updatedAnnouncement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Announcement updated successfully",
                "announcement" => $updatedAnnouncement
            ]);
            break;

        case 'DELETE':
            // DELETE /api/announcements/:id - Delete announcement
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Check if announcement exists
            $stmt = $conn->prepare("SELECT id FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Announcement not found"
                ]);
                break;
            }

            // Delete announcement
            $del = $conn->prepare("DELETE FROM announcements WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Announcement deleted successfully"
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