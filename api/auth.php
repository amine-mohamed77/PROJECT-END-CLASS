<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($data->action)) {
        switch ($data->action) {
            case 'login':
                handleLogin($db, $data);
                break;
            case 'register':
                handleRegister($db, $data);
                break;
            case 'logout':
                handleLogout();
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
}

function handleLogin($db, $data) {
    if (!empty($data->email) && !empty($data->password)) {
        $query = "SELECT id, name, email, password, type FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->email]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data->password, $row['password'])) {
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_type'] = $row['type'];
                
                echo json_encode(array(
                    "success" => true,
                    "message" => "Login successful",
                    "user" => array(
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "email" => $row['email'],
                        "type" => $row['type']
                    )
                ));
            } else {
                echo json_encode(array("success" => false, "message" => "Invalid password"));
            }
        } else {
            echo json_encode(array("success" => false, "message" => "User not found"));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Missing required fields"));
    }
}

function handleRegister($db, $data) {
    if (!empty($data->name) && !empty($data->email) && !empty($data->password) && !empty($data->type)) {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(array("success" => false, "message" => "Email already exists"));
            return;
        }
        
        // Insert new user
        $query = "INSERT INTO users (name, email, password, type) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        if ($stmt->execute([$data->name, $data->email, $password_hash, $data->type])) {
            $user_id = $db->lastInsertId();
            
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $data->name;
            $_SESSION['user_email'] = $data->email;
            $_SESSION['user_type'] = $data->type;
            
            echo json_encode(array(
                "success" => true,
                "message" => "Registration successful",
                "user" => array(
                    "id" => $user_id,
                    "name" => $data->name,
                    "email" => $data->email,
                    "type" => $data->type
                )
            ));
        } else {
            echo json_encode(array("success" => false, "message" => "Registration failed"));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Missing required fields"));
    }
}

function handleLogout() {
    session_start();
    session_destroy();
    echo json_encode(array("success" => true, "message" => "Logged out successfully"));
}
?> 