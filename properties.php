<?php
// Include database connection
require_once 'db.php';

// Function to get all properties with optional filtering
function getProperties($filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT p.*, u.name as owner_name, u.rating as owner_rating 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if (!empty($filters)) {
        // Filter by location or university
        if (isset($filters['location']) && $filters['location']) {
            $sql .= " AND (p.location LIKE ? OR p.nearby_university LIKE ?)";
            $searchTerm = '%' . $filters['location'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filter by property type
        if (isset($filters['property_type']) && $filters['property_type']) {
            $sql .= " AND p.property_type = ?";
            $params[] = $filters['property_type'];
        }
        
        // Filter by price range
        if (isset($filters['min_price']) && $filters['min_price']) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (isset($filters['max_price']) && $filters['max_price']) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        // Filter by number of beds
        if (isset($filters['beds']) && $filters['beds']) {
            $sql .= " AND p.beds >= ?";
            $params[] = $filters['beds'];
        }
        
        // Filter by number of baths
        if (isset($filters['baths']) && $filters['baths']) {
            $sql .= " AND p.baths >= ?";
            $params[] = $filters['baths'];
        }
        
        // Filter by availability
        if (isset($filters['available']) && $filters['available']) {
            $sql .= " AND p.is_available = 1";
        }
    }
    
    // Add sorting
    if (isset($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_low':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }
    } else {
        $sql .= " ORDER BY p.created_at DESC";
    }
    
    // Add limit and offset
    if ($limit !== null) {
        $sql .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
    }
    
    return fetchAll($sql, $params);
}

// Function to get a single property by ID
function getPropertyById($id) {
    $sql = "SELECT p.*, u.name as owner_name, u.email as owner_email, 
            u.profile_image as owner_image, u.rating as owner_rating 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.id = ?";
    
    return fetchOne($sql, [$id]);
}

// Function to get property images
function getPropertyImages($propertyId) {
    $sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC";
    return fetchAll($sql, [$propertyId]);
}

// Function to add a new property
function addProperty($data) {
    // Validate required fields
    $requiredFields = ['title', 'description', 'price', 'location', 'beds', 'baths', 'area', 'owner_id'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'message' => "Field '$field' is required"
            ];
        }
    }
    
    // Insert property into database
    $propertyId = insert('properties', $data);
    
    if ($propertyId) {
        return [
            'success' => true,
            'property_id' => $propertyId,
            'message' => 'Property added successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add property'
        ];
    }
}

// Function to update a property
function updateProperty($propertyId, $data) {
    // Check if property exists
    $property = getPropertyById($propertyId);
    
    if (!$property) {
        return [
            'success' => false,
            'message' => 'Property not found'
        ];
    }
    
    // Update property data
    $updated = update('properties', $data, 'id = ?', [$propertyId]);
    
    if ($updated !== false) {
        return [
            'success' => true,
            'message' => 'Property updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update property'
        ];
    }
}

// Function to delete a property
function deleteProperty($propertyId) {
    // Check if property exists
    $property = getPropertyById($propertyId);
    
    if (!$property) {
        return [
            'success' => false,
            'message' => 'Property not found'
        ];
    }
    
    // Delete property
    $deleted = delete('properties', 'id = ?', [$propertyId]);
    
    if ($deleted) {
        return [
            'success' => true,
            'message' => 'Property deleted successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete property'
        ];
    }
}

// Function to add property to favorites
function addToFavorites($userId, $propertyId) {
    // Check if already in favorites
    $existing = fetchOne("SELECT * FROM favorites WHERE user_id = ? AND property_id = ?", [$userId, $propertyId]);
    
    if ($existing) {
        return [
            'success' => false,
            'message' => 'Property already in favorites'
        ];
    }
    
    // Add to favorites
    $data = [
        'user_id' => $userId,
        'property_id' => $propertyId,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $id = insert('favorites', $data);
    
    if ($id) {
        return [
            'success' => true,
            'message' => 'Property added to favorites'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add property to favorites'
        ];
    }
}

// Function to remove property from favorites
function removeFromFavorites($userId, $propertyId) {
    $deleted = delete('favorites', 'user_id = ? AND property_id = ?', [$userId, $propertyId]);
    
    if ($deleted) {
        return [
            'success' => true,
            'message' => 'Property removed from favorites'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to remove property from favorites'
        ];
    }
}

// Function to get user's favorite properties
function getFavoriteProperties($userId) {
    $sql = "SELECT p.*, f.created_at as favorited_at 
            FROM properties p 
            JOIN favorites f ON p.id = f.property_id 
            WHERE f.user_id = ? 
            ORDER BY f.created_at DESC";
    
    return fetchAll($sql, [$userId]);
}
?>