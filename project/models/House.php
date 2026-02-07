<?php
// models/House.php
require_once 'config/database.php';

class House {
    private $conn;
    private $table_name = "houses";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Получение всех домов
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение дома по ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Создание нового дома
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (address, entrances, apartments, floors, year_built) 
                  VALUES (:address, :entrances, :apartments, :floors, :year_built)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":entrances", $data['entrances'], PDO::PARAM_INT);
        $stmt->bindParam(":apartments", $data['apartments'], PDO::PARAM_INT);
        $stmt->bindParam(":floors", $data['floors'], PDO::PARAM_INT);
        $stmt->bindParam(":year_built", $data['year_built'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Обновление дома
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET address = :address, 
                      entrances = :entrances, 
                      apartments = :apartments, 
                      floors = :floors, 
                      year_built = :year_built,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":entrances", $data['entrances'], PDO::PARAM_INT);
        $stmt->bindParam(":apartments", $data['apartments'], PDO::PARAM_INT);
        $stmt->bindParam(":floors", $data['floors'], PDO::PARAM_INT);
        $stmt->bindParam(":year_built", $data['year_built'], PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Удаление дома
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Поиск домов по адресу
    public function search($search_term) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE address LIKE :search_term 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%" . $search_term . "%";
        $stmt->bindParam(":search_term", $search_term);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение статистики домов
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_houses,
                    SUM(apartments) as total_apartments,
                    SUM(entrances) as total_entrances,
                    AVG(year_built) as avg_year_built
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Получение последних домов (для дашборда)
    public function getLatest($limit = 3) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение статистики за последний месяц
    public function getLastMonthStats() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // ========== ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ДЛЯ DASHBOARD ==========
    
    // Получение общего количества домов
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Получение количества домов за последний месяц
    public function getCountLastMonth() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Получение последних домов (для виджета)
    public function getLatestHouses($limit = 3) {
        return $this->getLatest($limit); // Используем существующий метод
    }

    // Получение статистики по домам
    public function getHouseStats() {
        return $this->getStats(); // Используем существующий метод
    }
    
    // Получение распределения домов по годам постройки
    public function getYearDistribution() {
        $query = "SELECT 
                    year_built,
                    COUNT(*) as count,
                    SUM(apartments) as total_apartments
                  FROM " . $this->table_name . " 
                  GROUP BY year_built
                  ORDER BY year_built DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получение статистики по количеству этажей
    public function getFloorsStats() {
        $query = "SELECT 
                    floors,
                    COUNT(*) as count
                  FROM " . $this->table_name . " 
                  GROUP BY floors
                  ORDER BY floors";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>