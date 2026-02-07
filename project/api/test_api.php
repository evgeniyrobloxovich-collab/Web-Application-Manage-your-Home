<?php
// api/test_api.php - Упрощенный тестовый API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Проверяем сессию
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Требуется авторизация', 401);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'] ?? 'житель';
    
    // Получаем входные данные
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
    }
    
    if ($method === 'GET') {
        // Простой тестовый ответ
        echo json_encode([
            'success' => true,
            'message' => 'API работает',
            'user_id' => $user_id,
            'user_role' => $user_role,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    if ($method === 'POST') {
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'test':
                echo json_encode([
                    'success' => true,
                    'message' => 'Тестовый запрос принят',
                    'data' => $input
                ]);
                break;
                
            case 'create':
                // Имитация создания заявки
                echo json_encode([
                    'success' => true,
                    'message' => 'Заявка создана (тестовый режим)',
                    'request_id' => rand(100, 999),
                    'data' => $input
                ]);
                break;
                
            case 'update_status':
                // Имитация обновления статуса
                echo json_encode([
                    'success' => true,
                    'message' => 'Статус обновлен (тестовый режим)',
                    'data' => $input
                ]);
                break;
                
            case 'delete':
                // Имитация удаления
                echo json_encode([
                    'success' => true,
                    'message' => 'Заявка удалена (тестовый режим)',
                    'data' => $input
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Неизвестное действие'
                ]);
        }
        exit;
    }
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>