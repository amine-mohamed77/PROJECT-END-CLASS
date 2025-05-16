<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($data->action)) {
        switch ($data->action) {
            case 'send':
                sendMessage($db, $data);
                break;
            case 'mark_read':
                markMessageAsRead($db, $data);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'conversations':
                getConversations($db);
                break;
            case 'messages':
                getMessages($db, $_GET);
                break;
            case 'unread_count':
                getUnreadCount($db);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
}

function sendMessage($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Verify that the user is either the owner of the offer/demand or the student
    if (isset($data->offer_id)) {
        $query = "SELECT owner_id FROM offers WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->offer_id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($offer['owner_id'] !== $_SESSION['user_id']) {
            // If not the owner, verify that the user is a student with a demand
            $query = "SELECT id FROM demands WHERE student_id = ? AND status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(array("success" => false, "message" => "Unauthorized"));
                return;
            }
        }
    } else if (isset($data->demand_id)) {
        $query = "SELECT student_id FROM demands WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$data->demand_id]);
        $demand = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($demand['student_id'] !== $_SESSION['user_id']) {
            // If not the student, verify that the user is an owner with an offer
            $query = "SELECT id FROM offers WHERE owner_id = ? AND status = 'available'";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(array("success" => false, "message" => "Unauthorized"));
                return;
            }
        }
    }

    $query = "INSERT INTO messages (sender_id, receiver_id, offer_id, demand_id, message) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        $_SESSION['user_id'],
        $data->receiver_id,
        $data->offer_id ?? null,
        $data->demand_id ?? null,
        $data->message
    ])) {
        echo json_encode(array(
            "success" => true,
            "message" => "Message sent successfully",
            "message_id" => $db->lastInsertId()
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to send message"));
    }
}

function markMessageAsRead($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "UPDATE messages SET is_read = TRUE 
              WHERE id = ? AND receiver_id = ?";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$data->message_id, $_SESSION['user_id']])) {
        echo json_encode(array("success" => true, "message" => "Message marked as read"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to mark message as read"));
    }
}

function getConversations($db) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as user_id,
                u.name as user_name,
                u.avatar as user_avatar,
                o.id as offer_id,
                o.title as offer_title,
                d.id as demand_id,
                d.title as demand_title,
                (
                    SELECT message 
                    FROM messages 
                    WHERE (sender_id = ? AND receiver_id = user_id) 
                    OR (sender_id = user_id AND receiver_id = ?)
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message,
                (
                    SELECT created_at 
                    FROM messages 
                    WHERE (sender_id = ? AND receiver_id = user_id) 
                    OR (sender_id = user_id AND receiver_id = ?)
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message_time,
                (
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE sender_id = user_id 
                    AND receiver_id = ? 
                    AND is_read = FALSE
                ) as unread_count
              FROM messages m
              JOIN users u ON (
                  CASE 
                      WHEN m.sender_id = ? THEN m.receiver_id
                      ELSE m.sender_id
                  END = u.id
              )
              LEFT JOIN offers o ON m.offer_id = o.id
              LEFT JOIN demands d ON m.demand_id = d.id
              WHERE m.sender_id = ? OR m.receiver_id = ?
              ORDER BY last_message_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
    ]);
    
    $conversations = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conversations[] = $row;
    }
    
    echo json_encode(array("success" => true, "conversations" => $conversations));
}

function getMessages($db, $params) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "SELECT m.*, 
              s.name as sender_name, s.avatar as sender_avatar,
              r.name as receiver_name, r.avatar as receiver_avatar
              FROM messages m
              JOIN users s ON m.sender_id = s.id
              JOIN users r ON m.receiver_id = r.id
              WHERE (m.sender_id = ? AND m.receiver_id = ?)
              OR (m.sender_id = ? AND m.receiver_id = ?)";
    
    $values = array(
        $_SESSION['user_id'], $params['user_id'],
        $params['user_id'], $_SESSION['user_id']
    );
    
    if (isset($params['offer_id'])) {
        $query .= " AND m.offer_id = ?";
        $values[] = $params['offer_id'];
    } else if (isset($params['demand_id'])) {
        $query .= " AND m.demand_id = ?";
        $values[] = $params['demand_id'];
    }
    
    $query .= " ORDER BY m.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($values);
    
    $messages = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row;
    }
    
    // Mark messages as read
    if (count($messages) > 0) {
        $query = "UPDATE messages SET is_read = TRUE 
                  WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $params['user_id']]);
    }
    
    echo json_encode(array("success" => true, "messages" => $messages));
}

function getUnreadCount($db) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "SELECT COUNT(*) as unread_count 
              FROM messages 
              WHERE receiver_id = ? AND is_read = FALSE";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(array("success" => true, "unread_count" => $result['unread_count']));
}
?> 