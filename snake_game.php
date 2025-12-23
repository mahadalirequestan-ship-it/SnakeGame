<?php
session_start();
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_score') {
        $score = (int)($_POST['score'] ?? 0);
        $name = htmlspecialchars($_POST['name'] ?? 'Anonim');
        
        // Validate input
        if ($score < 0 || $score > 10000) {
            echo json_encode(['success' => false, 'error' => 'Invalid score']);
            exit;
        }
        
        if (strlen($name) > 20) {
            $name = substr($name, 0, 20);
        }
        
        if (!isset($_SESSION['high_scores'])) {
            $_SESSION['high_scores'] = [];
        }
        
        $_SESSION['high_scores'][] = [
            'name' => $name,
            'score' => $score,
            'date' => date('Y-m-d H:i:s'),
            'id' => uniqid()
        ];
        
        // Sort by score (descending)
        usort($_SESSION['high_scores'], function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Keep only top 10
        $_SESSION['high_scores'] = array_slice($_SESSION['high_scores'], 0, 10);
        
        echo json_encode([
            'success' => true, 
            'scores' => $_SESSION['high_scores'],
            'message' => 'Rekord saqlandi!'
        ]);
        exit;
    }
    
    if ($action === 'get_scores') {
        echo json_encode([
            'success' => true,
            'scores' => $_SESSION['high_scores'] ?? []
        ]);
        exit;
    }
    
    if ($action === 'clear_scores') {
        $_SESSION['high_scores'] = [];
        echo json_encode([
            'success' => true,
            'message' => 'Barcha rekordlar o\'chirildi'
        ]);
        exit;
    }
}

// Default response for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'scores' => $_SESSION['high_scores'] ?? [],
        'total_scores' => count($_SESSION['high_scores'] ?? [])
    ]);
    exit;
}

// Invalid request
echo json_encode([
    'success' => false,
    'error' => 'Invalid request method'
]);
?>