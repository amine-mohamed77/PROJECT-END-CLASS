<?php
// Include database connection
require_once 'db.php';

// Function to get all demands with optional filtering
function getDemands($filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT d.*, u.name as student_name, u.profile_image as student_image, u.university 
            FROM demands d 
            JOIN users u ON d.student_id = u.id 
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if (!empty($filters)) {
        // Filter by university
        if (isset($filters['university']) && $filters['university']) {
            $sql .= " AND u.university LIKE ?";
            $params[] = '%' . $filters['university'] . '%';
        }
        
        // Filter by budget range
        if (isset($filters['min_budget']) && $filters['min_budget']) {
            $sql .= " AND d.budget_max >= ?";
            $params[] = $filters['min_budget'];
        }
        
        if (isset($filters['max_budget']) && $filters['max_budget']) {
            $sql .= " AND d.budget_min <= ?";
            $params[] = $filters['max_budget'];
        }
        
        // Filter by room type
        if (isset($filters['room_type']) && $filters['room_type']) {
            $sql .= " AND d.room_type = ?";
            $params[] = $filters['room_type'];
        }
        
        // Filter by move-in date
        if (isset($filters['move_in_date']) && $filters['move_in_date']) {
            $sql .= " AND d.move_in_date >= ?";
            $params[] = $filters['move_in_date'];
        }
    }
    
    // Add sorting
    if (isset($filters['sort'])) {
        switch ($filters['sort']) {
            case 'budget_low':
                $sql .= " ORDER BY d.budget_min ASC";
                break;
            case 'budget_high':
                $sql .= " ORDER BY d.budget_max DESC";
                break;
            case 'move_in':
                $sql .= " ORDER BY d.move_in_date ASC";
                break;
            case 'newest':
                $sql .= " ORDER BY d.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY d.created_at DESC";
        }
    } else {
        $sql .= " ORDER BY d.created_at DESC";
    }
    
    // Add limit and offset
    if ($limit !== null) {
        $sql .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
    }
    
    return fetchAll($sql, $params);
}

// Function to get a single demand by ID
function getDemandById($id) {
    $sql = "SELECT d.*, u.name as student_name, u.email as student_email, 
            u.profile_image as student_image, u.university 
            FROM demands d 
            JOIN users u ON d.student_id = u.id 
            WHERE d.id = ?";
    
    return fetchOne($sql, [$id]);
}

// Function to add a new demand
function addDemand($data) {
    // Validate required fields
    $requiredFields = ['student_id', 'budget_min', 'budget_max', 'room_type', 'move_in_date', 'duration', 'description'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'message' => "Field '$field' is required"
            ];
        }
    }
    
    // Insert demand into database
    $demandId = insert('demands', $data);
    
    if ($demandId) {
        return [
            'success' => true,
            'demand_id' => $demandId,
            'message' => 'Demand added successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add demand'
        ];
    }
}

// Function to update a demand
function updateDemand($demandId, $data) {
    // Check if demand exists
    $demand = getDemandById($demandId);
    
    if (!$demand) {
        return [
            'success' => false,
            'message' => 'Demand not found'
        ];
    }
    
    // Update demand data
    $updated = update('demands', $data, 'id = ?', [$demandId]);
    
    if ($updated !== false) {
        return [
            'success' => true,
            'message' => 'Demand updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update demand'
        ];
    }
}

// Function to delete a demand
function deleteDemand($demandId) {
    // Check if demand exists
    $demand = getDemandById($demandId);
    
    if (!$demand) {
        return [
            'success' => false,
            'message' => 'Demand not found'
        ];
    }
    
    // Delete demand
    $deleted = delete('demands', 'id = ?', [$demandId]);
    
    if ($deleted) {
        return [
            'success' => true,
            'message' => 'Demand deleted successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete demand'
        ];
    }
}
?>