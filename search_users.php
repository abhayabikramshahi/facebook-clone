<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

// Check if search query is provided
if (!isset($_GET['q'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Search query is required']);
    exit;
}

$query = trim($_GET['q']);
$currentUserId = getCurrentUserId();

// Log search attempt
error_log("Search attempt - Query: $query, User ID: $currentUserId");

try {
    // Search users by username with additional user information
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.profile_picture,
            u.verification_status,
            u.bio,
            (
                SELECT COUNT(*) 
                FROM messages 
                WHERE (sender_id = :currentUserId AND receiver_id = u.id) 
                OR (sender_id = u.id AND receiver_id = :currentUserId)
            ) as message_count,
            (
                SELECT created_at 
                FROM messages 
                WHERE (sender_id = :currentUserId AND receiver_id = u.id) 
                OR (sender_id = u.id AND receiver_id = :currentUserId)
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message_time,
            EXISTS(
                SELECT 1 
                FROM follows 
                WHERE follower_id = :currentUserId 
                AND following_id = u.id
            ) as is_following
        FROM users u
        WHERE u.username LIKE :query
        AND u.id != :currentUserId
        AND u.status = 'active'
        ORDER BY 
            CASE 
                WHEN u.username = :exactQuery THEN 1
                WHEN u.username LIKE :startQuery THEN 2
                ELSE 3
            END,
            u.username
        LIMIT 10
    ");
    
    $params = [
        'query' => "%{$query}%",
        'currentUserId' => $currentUserId,
        'exactQuery' => $query,
        'startQuery' => "{$query}%"
    ];
    
    // Log the query parameters
    error_log("Search parameters: " . json_encode($params));
    
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Log the number of results
    error_log("Search results count: " . count($users));
    
    // Format response with additional information
    $response = [
        'status' => 'success',
        'count' => count($users),
        'users' => array_map(function($user) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'profile_picture' => $user['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg',
                'verification_status' => $user['verification_status'],
                'bio' => $user['bio'],
                'message_count' => (int)$user['message_count'],
                'last_message_time' => $user['last_message_time'],
                'has_conversation' => (int)$user['message_count'] > 0,
                'is_following' => (bool)$user['is_following']
            ];
        }, $users)
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} 