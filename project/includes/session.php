<?php
session_start();

class Session {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }
    
    public static function delete($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function requireLogin($redirect = 'login.php') {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect);
            exit();
        }
    }
    
    public static function requireRole($role, $redirect = 'login.php') {
        self::requireLogin($redirect);
        
        if (self::getUserRole() !== $role) {
            header('Location: ' . $redirect);
            exit();
        }
    }
    
    // Новый метод для установки всех данных пользователя
    public static function setUserData($user_data) {
        self::set('user_id', $user_data['id']);
        self::set('user_name', $user_data['full_name']);
        self::set('user_email', $user_data['email']);
        self::set('user_phone', $user_data['phone']);
        self::set('user_role', $user_data['role']);
        self::set('user_address', $user_data['address']);
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>