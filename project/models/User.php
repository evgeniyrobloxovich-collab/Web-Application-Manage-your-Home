<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $full_name;
    public $email;
    public $phone;
    public $password;
    public $role;
    public $address;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Создание нового пользователя
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (full_name, email, phone, password, role, address) 
                  VALUES (:full_name, :email, :phone, :password, :role, :address)";
        
        $stmt = $this->conn->prepare($query);
        
        // Хэширование пароля
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":address", $this->address);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Получение всех пользователей
    public function getAll() {
        $query = "SELECT id, full_name, email, phone, address, role, created_at 
                  FROM " . $this->table_name . " 
                  WHERE is_active = TRUE 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Удаление пользователя (мягкое удаление - деактивация)
    public function delete($user_id) {
        // Проверяем, что пользователь существует и активен
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE id = :id AND is_active = TRUE";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":id", $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            return false; // Пользователь не найден или уже удален
        }
        
        // Мягкое удаление - устанавливаем is_active = FALSE
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = FALSE, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        
        return $stmt->execute();
    }

    // Поиск пользователя по email
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Получение пользователя по ID
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Проверка пароля
    public function verifyPassword($input_password, $hashed_password) {
        return password_verify($input_password, $hashed_password);
    }

    // Обновление профиля пользователя
    public function updateProfile($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = :full_name, 
                      phone = :phone, 
                      address = :address,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":full_name", $data['full_name']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Получение всех пользователей по роли
    public function getUsersByRole($role) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE role = :role AND is_active = TRUE ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $role);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение статистики пользователей
    public function getUserStats() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(role = 'житель' AND is_active = TRUE) as residents,
                    SUM(role = 'сотрудник_ук' AND is_active = TRUE) as employees
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ========== ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ДЛЯ DASHBOARD ==========
    
    // Получение количества новых пользователей за последний месяц
    public function getNewUsersLastMonth() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                  AND is_active = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    // Получение количества новых сотрудников за последний месяц
    public function getNewEmployeesLastMonth() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                  AND role = 'сотрудник_ук' 
                  AND is_active = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    // Получение количества активных пользователей
    public function getActiveUsersCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE is_active = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    // Получение последних зарегистрированных пользователей
    public function getLatestUsers($limit = 5) {
        $query = "SELECT id, full_name, email, role, created_at 
                  FROM " . $this->table_name . " 
                  WHERE is_active = TRUE
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение распределения пользователей по ролям
    public function getRoleDistribution() {
        $query = "SELECT 
                    role,
                    COUNT(*) as count
                  FROM " . $this->table_name . " 
                  WHERE is_active = TRUE
                  GROUP BY role";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение статистики регистраций по дням
    public function getRegistrationStats($days = 30) {
        $query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(role = 'житель') as residents,
                    SUM(role = 'сотрудник_ук') as employees
                  FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  AND is_active = TRUE
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>