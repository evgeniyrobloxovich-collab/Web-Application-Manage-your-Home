<?php
// models/Request.php
// Используем абсолютные пути
$base_dir = dirname(__DIR__);
require_once $base_dir . '/config/database.php';

class Request {
    private $conn;
    private $table_name = "requests";

    public $id;
    public $user_id;
    public $apartment_number;
    public $category;
    public $description;
    public $status;
    public $assigned_to;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Создание новой заявки
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, apartment_number, category, description, status) 
                  VALUES (:user_id, :apartment_number, :category, :description, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->status = $this->status ?: 'новая';
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":apartment_number", $this->apartment_number);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Получение всех заявок пользователя
    public function getByUserId($user_id, $status = null, $limit = null, $offset = 0) {
        $query = "SELECT r.*, u.full_name, u.email, u.phone 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.user_id = :user_id";
        
        if ($status && $status !== 'all') {
            $query .= " AND r.status = :status";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($status && $status !== 'all') {
            $stmt->bindParam(":status", $status);
        }
        
        if ($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение всех заявок (для сотрудников УК)
    public function getAll($status = null, $limit = null, $offset = 0) {
        $query = "SELECT r.*, u.full_name, u.email, u.phone, u.address 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE 1=1";
        
        if ($status && $status !== 'all') {
            $query .= " AND r.status = :status";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($status && $status !== 'all') {
            $stmt->bindParam(":status", $status);
        }
        
        if ($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение заявки по ID
    public function getById($id) {
        $query = "SELECT r.*, u.full_name, u.email, u.phone, u.address 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Обновление статуса заявки
    public function updateStatus($id, $status, $assigned_to = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, 
                      assigned_to = :assigned_to,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":assigned_to", $assigned_to);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Удаление заявки
    public function delete($id, $user_id = null) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        if ($user_id) {
            $query .= " AND user_id = :user_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($user_id) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        return $stmt->execute();
    }

    // Получение статистики заявок
    public function getStats($user_id = null) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(status = 'новая') as new,
                    SUM(status = 'в работе') as in_progress,
                    SUM(status = 'выполнена') as completed,
                    SUM(status = 'отклонена') as rejected
                  FROM " . $this->table_name;
        
        if ($user_id) {
            $query .= " WHERE user_id = :user_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_id) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ========== НОВЫЕ МЕТОДЫ ДЛЯ DASHBOARD ==========
    
    // Получение общего количества заявок
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Получение количества заявок за последний месяц
    public function getCountLastMonth() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Получение количества активных заявок (новая + в работе)
    public function getActiveCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE status IN ('новая', 'в работе')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Получение количества выполненных заявок
    public function getCompletedCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE status = 'выполнена'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    // Получение количества заявок по статусам для графика
    public function getStatusDistribution() {
        $query = "SELECT 
                    status,
                    COUNT(*) as count
                  FROM " . $this->table_name . " 
                  GROUP BY status
                  ORDER BY 
                    CASE status
                        WHEN 'новая' THEN 1
                        WHEN 'в работе' THEN 2
                        WHEN 'выполнена' THEN 3
                        WHEN 'отклонена' THEN 4
                        ELSE 5
                    END";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение количества заявок по категориям
    public function getCategoryDistribution() {
        $query = "SELECT 
                    category,
                    COUNT(*) as count
                  FROM " . $this->table_name . " 
                  GROUP BY category
                  ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение последних заявок (для виджета на dashboard)
    public function getLatestRequests($limit = 5) {
        $query = "SELECT 
                    r.*,
                    u.full_name as user_name,
                    u.address as user_address
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  ORDER BY r.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение заявок по дням для графика
    public function getDailyStats($days = 30) {
        $query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(status = 'выполнена') as completed
                  FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>