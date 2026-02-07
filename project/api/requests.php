<?php
// api/requests.php - Упрощенный и исправленный API
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
    // Абсолютные пути
    $base_dir = dirname(__DIR__);
    
    // Подключаем файлы с абсолютными путями
    require_once $base_dir . '/includes/session.php';
    require_once $base_dir . '/config/database.php';
    require_once $base_dir . '/models/Request.php';
    
    // Инициализируем сессию
    Session::start();
    
    // Проверяем авторизацию
    if (!Session::isLoggedIn()) {
        throw new Exception('Требуется авторизация', 401);
    }
    
    $user_id = Session::get('user_id');
    $user_role = Session::get('user_role');
    
    if (!$user_id || !$user_role) {
        throw new Exception('Данные пользователя не найдены', 401);
    }
    
    // Создаем модель запросов
    $requestModel = new Request();
    
    // Определяем метод запроса
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Обработка GET запросов
    if ($method === 'GET') {
        $status = $_GET['status'] ?? 'all';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        
        if ($user_role === 'сотрудник_ук') {
            $requests = $requestModel->getAll($status, $limit);
        } else {
            $requests = $requestModel->getByUserId($user_id, $status, $limit);
        }
        
        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'count' => count($requests)
        ]);
        exit;
    }
    
    // Обработка POST запросов
    if ($method === 'POST') {
        // Получаем входные данные
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $input = $_POST;
        }
        
        if (!isset($input['action'])) {
            throw new Exception('Не указано действие', 400);
        }
        
        $action = $input['action'];
        
        switch ($action) {
            case 'create':
                if ($user_role !== 'житель') {
                    throw new Exception('Только жители могут создавать заявки', 403);
                }
                
                // Проверяем обязательные поля
                if (empty($input['category']) || empty($input['description'])) {
                    throw new Exception('Заполните все обязательные поля', 400);
                }
                
                // Создаем заявку
                $requestModel->user_id = $user_id;
                $requestModel->apartment_number = $input['apartment_number'] ?? '';
                $requestModel->category = $input['category'];
                $requestModel->description = htmlspecialchars($input['description']);
                $requestModel->status = 'новая';
                
                if ($requestModel->create()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Заявка успешно создана',
                        'request_id' => $requestModel->id
                    ]);
                } else {
                    throw new Exception('Ошибка при создании заявки', 500);
                }
                break;
                
            case 'update_status':
                if (empty($input['request_id']) || empty($input['status'])) {
                    throw new Exception('Не указаны обязательные параметры', 400);
                }
                
                $request_id = (int)$input['request_id'];
                $status = $input['status'];
                $assigned_to = ($status === 'в работе') ? $user_id : null;
                
                if ($requestModel->updateStatus($request_id, $status, $assigned_to)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Статус обновлен'
                    ]);
                } else {
                    throw new Exception('Ошибка при обновлении статуса', 500);
                }
                break;
                
            case 'delete':
                if (empty($input['request_id'])) {
                    throw new Exception('Не указан ID заявки', 400);
                }
                
                $request_id = (int)$input['request_id'];
                
                // Проверяем права
                if ($user_role === 'житель') {
                    $request = $requestModel->getById($request_id);
                    if (!$request || $request['user_id'] != $user_id) {
                        throw new Exception('Нет прав на удаление', 403);
                    }
                    $success = $requestModel->delete($request_id, $user_id);
                } else {
                    $success = $requestModel->delete($request_id);
                }
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Заявка удалена'
                    ]);
                } else {
                    throw new Exception('Ошибка при удалении заявки', 500);
                }
                break;
                
            default:
                throw new Exception('Неизвестное действие', 400);
        }
        exit;
    }
    
    throw new Exception('Метод не поддерживается', 405);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>