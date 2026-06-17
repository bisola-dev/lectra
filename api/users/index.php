<?php

require_once __DIR__ . "/../header.php";
require_once "../../conn.php";
require_once "../../jwt.php";

$method = $_SERVER['REQUEST_METHOD'];
$id     = null;

// Get ID from query parameter (e.g., /api/users/index.php?id=123)
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
                // GET /api/users/:id - Get specific user
                $stmt = $conn->prepare("SELECT id, name, email, role, department_id, created_at FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error",
                        "message" => "User not found"
                    ]);
                    break;
                }
                
                echo json_encode([
                    "status" => "success",
                    "user" => $user
                ]);
            } else {
                // GET /api/users - List all users
                $stmt = $conn->prepare("SELECT id, name, email, role, department_id, created_at FROM users ORDER BY created_at DESC");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "status" => "success",
                    "data" => $users,
                    "count" => count($users)
                ]);
            }
            break;

        case 'POST':
            // POST /api/users - Create new user
            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);
            
            $name  = trim($data['name']  ?? '');
            $email = trim($data['email'] ?? '');
            $password = trim($data['password'] ?? '');
            $role = $data['role'] ?? 'student';
            $department_id = $data['department_id'] ?? null;

            // Validation
            if ($name === '' || $email === '' || $password === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Name, email and password are required"
                ]);
                break;
            }

            if (!in_array($role, ['admin', 'lecturer', 'student'])) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid role. Must be admin, lecturer, or student"
                ]);
                break;
            }

            // Check if email already exists
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Email already exists"
                ]);
                break;
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Create user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, department_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $role, $department_id]);
            
            $userId = $conn->lastInsertId();
            
            // Get created user (without password)
            $stmt = $conn->prepare("SELECT id, name, email, role, department_id, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "User created successfully",
                "user" => $newUser
            ]);
            break;

        case 'PUT':
            // PUT /api/users/:id - Update user (Simplified for beginners)
            $data = json_decode(file_get_contents("php://input"), true);

            if (!$id) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Find existing user FIRST
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode([
                    "status" => "error",
                    "message" => "User not found"
                ]);
                break;
            }

            // Read fields — fall back to existing data when a field is left blank
            $name  = trim($data['name']  ?? $user['name']);
            $email = trim($data['email'] ?? $user['email']);
            $role  = $data['role'] ?? $user['role'];
            $department_id = $data['department_id'] ?? $user['department_id'];
            $password = trim($data['password'] ?? '');

            // Basic validation
            if ($name === '' || $email === '' || $role === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Name, email and role are required"
                ]);
                break;
            }

            // Validate role
            if (!in_array($role, ['admin', 'lecturer', 'student'])) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid role. Must be admin, lecturer, or student"
                ]);
                break;
            }

            // Check if email is being updated and if it already exists (for another user)
            if ($email !== $user['email']) {
                $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk->execute([$email, $id]);
                if ($chk->fetch()) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Email already exists"
                    ]);
                    break;
                }
            }

            // Handle password update (only if provided)
            if ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, department_id=?, password_hash=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $department_id, $password_hash, $id]);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, department_id=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $department_id, $id]);
            }

            echo json_encode([
                "status" => "success",
                "message" => "User updated successfully"
            ]);
            break;

        case 'DELETE':
            // DELETE /api/users/:id - Delete user
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Prevent admin from deleting themselves
            if ($authUser->id == $id) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Cannot delete your own account"
                ]);
                break;
            }

            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "User not found"
                ]);
                break;
            }

            // Delete user
            $del = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode([
                "status" => "success",
                "message" => "User deleted successfully"
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