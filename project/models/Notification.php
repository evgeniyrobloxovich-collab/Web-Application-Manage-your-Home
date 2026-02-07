<?php
// models/Notification.php
require_once 'config/database.php';
require_once 'User.php'; // Добавляем зависимость

class Notification {
    private $conn;
    private $table_name = "notifications";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Создание нового уведомления
    public function create($user_id, $title, $message, $type = 'info', $related_type = 'system', $related_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, title, message, type, related_type, related_id) 
                  VALUES (:user_id, :title, :message, :type, :related_type, :related_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":related_type", $related_type);
        $stmt->bindParam(":related_id", $related_id);
        
        return $stmt->execute();
    }

    // Получение непрочитанных уведомлений пользователя
    public function getUnreadByUser($user_id, $limit = 10) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение всех уведомлений пользователя
    public function getAllByUser($user_id, $limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение количества непрочитанных уведомлений
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Отметить уведомление как прочитанное
    public function markAsRead($id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Отметить все уведомления как прочитанные
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Удалить уведомление
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Создание уведомления о новой заявке (для жителя)
    public function createRequestNotification($user_id, $request_id, $title, $message) {
        return $this->create($user_id, $title, $message, 'info', 'request', $request_id);
    }

    // Создание уведомления о новой заявке (для сотрудников)
    public function createNewRequestNotification($request_id, $user_name, $address) {
        // Получаем всех сотрудников УК
        $userModel = new User();
        $employees = $userModel->getUsersByRole('сотрудник_ук');
        
        $success = true;
        foreach ($employees as $employee) {
            $result = $this->create(
                $employee['id'],
                'Новая заявка',
                "Новая заявка от $user_name по адресу: $address",
                'info',
                'request',
                $request_id
            );
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
}
?>