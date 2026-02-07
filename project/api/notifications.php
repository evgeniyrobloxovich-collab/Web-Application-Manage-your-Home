<?php
// api/notifications.php
// ВКЛЮЧАЕМ ОТЛАДКУ В КОНСОЛЬ, А НЕ В ВЫВОД
error_reporting(E_ALL);
ini_set('display_errors', 0); // ВАЖНО: отключаем вывод ошибок в HTML

// Устанавливаем заголовки ПЕРЕД любым выводом
header('Content-Type: application/json; charset=utf-8');

try {
    // Используем абсолютные пути
    $base_dir = dirname(__DIR__);
    
    // Подключаем файлы
    require_once $base_dir . '/includes/session.php';
    require_once $base_dir . '/config/database.php';
    require_once $base_dir . '/models/Notification.php';
    require_once $base_dir . '/models/User.php';
    
    // Инициализируем сессию
    Session::start();
    
    // Проверяем авторизацию
    if (!Session::isLoggedIn()) {
        throw new Exception('Необходима авторизация', 401);
    }
    
    $user_id = Session::get('user_id');
    if (!$user_id) {
        throw new Exception('Пользователь не найден', 401);
    }
    
    // Создаем модель уведомлений
    $notification = new Notification();
    
    // Определяем метод запроса
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Получение уведомлений
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $unread_only = isset($_GET['unread']) && $_GET['unread'] === 'true';
            
            // Получаем уведомления в зависимости от фильтра
            if ($unread_only) {
                // Проверяем существует ли метод getUnreadByUser
                if (method_exists($notification, 'getUnreadByUser')) {
                    $notifications = $notification->getUnreadByUser($user_id, $limit);
                } else {
                    // Альтернативный метод, если getUnreadByUser не существует
                    $notifications = $notification->getAllByUser($user_id, $limit);
                    $notifications = array_filter($notifications, function($note) {
                        return !$note['is_read'];
                    });
                }
            } else {
                // Проверяем существует ли метод getAllByUser
                if (method_exists($notification, 'getAllByUser')) {
                    $notifications = $notification->getAllByUser($user_id, $limit);
                } else {
                    // Заглушка, если метод не существует
                    $notifications = [];
                }
            }
            
            // Получаем количество непрочитанных
            $unread_count = 0;
            if (method_exists($notification, 'getUnreadCount')) {
                $unread_count = $notification->getUnreadCount($user_id);
            } else {
                // Считаем вручную
                $unread_count = count(array_filter($notifications, function($note) {
                    return !$note['is_read'];
                }));
            }
            
            // Форматируем даты для отображения
            foreach ($notifications as &$note) {
                $note['time_ago'] = $this->getTimeAgo($note['created_at'] ?? date('Y-m-d H:i:s'));
                $note['icon'] = $this->getNotificationIcon($note['type'] ?? 'info');
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => (int)$unread_count,
                'count' => count($notifications)
            ]);
            break;
            
        case 'POST':
            // Получаем входные данные
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $input = $_POST;
            }
            
            if (!isset($input['action'])) {
                throw new Exception('Не указано действие', 400);
            }
            
            switch ($input['action']) {
                case 'mark_as_read':
                    if (!isset($input['notification_id'])) {
                        throw new Exception('Не указан ID уведомления', 400);
                    }
                    
                    // Проверяем существует ли метод markAsRead
                    if (method_exists($notification, 'markAsRead')) {
                        $result = $notification->markAsRead($input['notification_id'], $user_id);
                    } else {
                        $result = false;
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'mark_all_as_read':
                    if (method_exists($notification, 'markAllAsRead')) {
                        $result = $notification->markAllAsRead($user_id);
                    } else {
                        $result = false;
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'delete':
                    if (!isset($input['notification_id'])) {
                        throw new Exception('Не указан ID уведомления', 400);
                    }
                    
                    if (method_exists($notification, 'delete')) {
                        $result = $notification->delete($input['notification_id'], $user_id);
                    } else {
                        $result = false;
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                default:
                    throw new Exception('Неизвестное действие', 400);
            }
            break;
            
        default:
            throw new Exception('Метод не поддерживается', 405);
    }
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}

// Вспомогательные функции как методы класса
private function getTimeAgo($dateString) {
    try {
        $date = new DateTime($dateString);
        $now = new DateTime();
        $diff = $now->diff($date);
        
        if ($diff->days > 0) {
            return $diff->days . ' дней назад';
        } elseif ($diff->h > 0) {
            return $diff->h . ' часов назад';
        } elseif ($diff->i > 0) {
            return $diff->i . ' минут назад';
        } else {
            return 'только что';
        }
    } catch (Exception $e) {
        return 'недавно';
    }
}

private function getNotificationIcon($type) {
    $icons = [
        'info' => 'fas fa-info-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-times-circle'
    ];
    return $icons[$type] ?? 'fas fa-bell';
}
?>