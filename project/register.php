<?php
require_once 'models/User.php';
require_once 'includes/session.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    
    // Валидация данных
    $full_name = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'житель';
    $address = trim($_POST['address'] ?? '');
    
    // Проверка обязательных полей
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Все обязательные поля должны быть заполнены!";
    } elseif ($password !== $confirm_password) {
        $error = "Пароли не совпадают!";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать минимум 6 символов!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Введите корректный email адрес!";
    } else {
        // Проверка, существует ли уже такой email
        $existing_user = $user->findByEmail($email);
        if ($existing_user) {
            $error = "Пользователь с таким email уже зарегистрирован!";
        } else {
            // Создание нового пользователя
            $user->full_name = $full_name;
            $user->email = $email;
            $user->phone = $phone;
            $user->password = $password;
            $user->role = $role;
            $user->address = $address;
            
            if ($user->create()) {
                // Автоматический вход после регистрации
                $new_user = $user->findByEmail($email);
                
                Session::set('user_id', $new_user['id']);
                Session::set('user_name', $new_user['full_name']);
                Session::set('user_email', $new_user['email']);
                Session::set('user_phone', $new_user['phone']);
                Session::set('user_role', $new_user['role']);
                Session::set('user_address', $new_user['address']);
                
                // Редирект в зависимости от роли
                if ($role === 'сотрудник_ук') {
                    header('Location: employee_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = "Ошибка при регистрации. Попробуйте еще раз.";
            }
        }
    }
}

$page_title = "Регистрация";
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
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #c8e6c9;
            font-size: 0.9rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .password-strength.weak { color: #d32f2f; }
        .password-strength.medium { color: #f57c00; }
        .password-strength.strong { color: #2e7d32; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-wrapper">
            <div class="form-header">
                <h1><i class="fas fa-user-plus"></i> Регистрация</h1>
                <p class="form-subtitle">Создайте аккаунт для доступа к системе</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label for="fullname">ФИО *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="fullname" name="fullname" 
                               value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" 
                               placeholder="Иванов Иван Иванович" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="example@mail.ru" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                               placeholder="+7 (999) 123-45-67" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Адрес</label>
                    <div class="input-wrapper">
                        <i class="fas fa-home"></i>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" 
                               placeholder="ул. Ленина, д. 10, кв. 45">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Роль *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card"></i>
                        <select id="role" name="role" required>
                            <option value="житель" <?php echo ($_POST['role'] ?? 'житель') === 'житель' ? 'selected' : ''; ?>>Житель</option>
                            <option value="сотрудник_ук" <?php echo ($_POST['role'] ?? '') === 'сотрудник_ук' ? 'selected' : ''; ?>>Сотрудник УК</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Минимум 6 символов" required 
                               onkeyup="checkPasswordStrength(this.value)">
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Повторите пароль" required 
                               onkeyup="checkPasswordsMatch()">
                    </div>
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit" class="submit-btn">Зарегистрироваться</button>
            </form>
            
            <div class="form-footer">
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                <p class="back-link"><a href="index.php"><i class="fas fa-arrow-left"></i> На главную</a></p>
            </div>
        </div>
    </div>
    
    <script>
    function checkPasswordStrength(password) {
        const strengthElement = document.getElementById('password-strength');
        let strength = 'слабый';
        let color = 'weak';
        
        if (password.length >= 8) {
            strength = 'средний';
            color = 'medium';
        }
        if (password.length >= 10 && /[A-Z]/.test(password) && /\d/.test(password)) {
            strength = 'сильный';
            color = 'strong';
        }
        
        if (password.length > 0) {
            strengthElement.textContent = `Надёжность пароля: ${strength}`;
            strengthElement.className = `password-strength ${color}`;
        } else {
            strengthElement.textContent = '';
        }
    }
    
    function checkPasswordsMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchElement = document.getElementById('password-match');
        
        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                matchElement.textContent = '✓ Пароли совпадают';
                matchElement.className = 'password-strength strong';
            } else {
                matchElement.textContent = '✗ Пароли не совпадают';
                matchElement.className = 'password-strength weak';
            }
        } else {
            matchElement.textContent = '';
        }
    }
    </script>
</body>
</html>