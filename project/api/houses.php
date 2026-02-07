<?php
// api/houses.php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../models/House.php';

Session::start();

// Проверяем авторизацию и роль
if (!Session::isLoggedIn() || Session::getUserRole() !== 'сотрудник_ук') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$houseModel = new House();

switch ($method) {
    case 'GET':
        // Получение списка домов
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $houses = $search ? $houseModel->search($search) : $houseModel->getAll();
        echo json_encode(['success' => true, 'data' => $houses]);
        break;
        
    case 'POST':
        // Удаление дома
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID дома не указан']);
            exit();
        }
        
        if ($houseModel->delete($data['id'])) {
            echo json_encode(['success' => true, 'message' => 'Дом успешно удален']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при удалении дома']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
?>