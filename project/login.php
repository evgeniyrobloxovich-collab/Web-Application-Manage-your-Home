<?php
require_once 'models/User.php';
require_once 'includes/session.php';

// Если пользователь уже авторизован, редирект на соответствующую страницу
if (Session::isLoggedIn()) {
    $role = Session::getUserRole();
    if ($role === 'сотрудник_ук') {
        header('Location: employee_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Введите email и пароль!";
    } else {
        // Поиск пользователя по email
        $user_data = $user->findByEmail($email);
        
        if ($user_data && $user->verifyPassword($password, $user_data['password'])) {
            if ($user_data['is_active']) {
                // Сохраняем данные в сессии
                Session::set('user_id', $user_data['id']);
                Session::set('user_name', $user_data['full_name']);
                Session::set('user_email', $user_data['email']);
                Session::set('user_phone', $user_data['phone']);
                Session::set('user_role', $user_data['role']);
                Session::set('user_address', $user_data['address']);
                
                // Редирект в зависимости от роли
                if ($user_data['role'] === 'сотрудник_ук') {
                    header('Location: employee_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = "Ваш аккаунт заблокирован. Обратитесь к администратору.";
            }
        } else {
            $error = "Неверный email или пароль!";
        }
    }
}

$page_title = "Вход в систему";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — Управляй домом</title>
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .error-message {
            background-color: #ffeaea;
            color: #d32f2f;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #ffcdd2;
            font-size: 0.9rem;
        }
        
        .demo-credentials {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            font-size: 0.9rem;
        }
        
        .demo-credentials h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-user {
            background-color: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .demo-user:last-child {
            margin-bottom: 0;
        }
        
        .demo-user h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #000;
            font-size: 0.95rem;
        }
        
        .demo-user p {
            margin: 0.3rem 0;
            color: #666;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-wrapper">
            <div class="form-header">
                <h1><i class="fas fa-sign-in-alt"></i> Вход в систему</h1>
                <p class="form-subtitle">Введите email и пароль для входа</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="example@mail.ru" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Введите пароль" required>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Войти</button>
            </form>

            <div class="form-footer">
                <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                <p class="back-link"><a href="index.php"><i class="fas fa-arrow-left"></i> На главную</a></p>
            </div>
        </div>
    </div>
</body>
</html>