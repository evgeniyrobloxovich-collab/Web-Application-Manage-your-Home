<?php
// update_profile.php
require_once 'includes/session.php';
require_once 'models/User.php';

Session::start();

// Проверяем авторизацию
if (!Session::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit();
}

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

// Если данные пришли в обычном POST
if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
}

// Проверяем обязательные поля
if (!isset($data['full_name']) || !isset($data['phone']) || !isset($data['address'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не все поля заполнены']);
    exit();
}

// Получаем ID пользователя из сессии
$user_id = Session::get('user_id');
if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    exit();
}

// Создаем экземпляр пользователя
$userModel = new User();

// Получаем текущего пользователя
$current_user = $userModel->findById($user_id);
if (!$current_user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Пользователь не найден в базе']);
    exit();
}

// Подготавливаем данные для обновления
$update_data = [
    'full_name' => htmlspecialchars(strip_tags($data['full_name'])),
    'phone' => htmlspecialchars(strip_tags($data['phone'])),
    'address' => htmlspecialchars(strip_tags($data['address']))
];

// Обновляем профиль
if ($userModel->updateProfile($user_id, $update_data)) {
    // Обновляем данные в сессии
    Session::set('user_name', $update_data['full_name']);
    Session::set('user_phone', $update_data['phone']);
    Session::set('user_address', $update_data['address']);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Профиль успешно обновлен',
        'user' => [
            'full_name' => $update_data['full_name'],
            'phone' => $update_data['phone'],
            'address' => $update_data['address'],
            'email' => $current_user['email'],
            'role' => $current_user['role']
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении профиля']);
}
?>