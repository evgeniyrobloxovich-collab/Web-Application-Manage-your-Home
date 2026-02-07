<?php
require_once 'includes/session.php';

// Проверка авторизации
Session::requireLogin('login.php');
Session::requireRole('сотрудник_ук', 'login.php');

// Получаем данные пользователя из сессии
$user_name = Session::get('user_name');
$user_email = Session::get('user_email');
$user_phone = Session::get('user_phone');
$user_role = Session::get('user_role');
$user_address = Session::get('user_address');
$user_id = Session::get('user_id'); // Добавляем получение ID

// Если данные отсутствуют, используем демо
if (empty($user_name)) {
    $user_name = "Алигатор Жека";
    $user_email = "admin@uk.ru";
    $user_phone = "+79512488211";
    $user_role = "Сотрудник УК";
    $user_address = "Офис УК";
    $user_id = 2; // Демо ID для сотрудника
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления УК — Управляй домом</title>
    <link rel="stylesheet" href="../css/employee.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Стили для модального окна профиля -->
    <style>
    .uk-profile-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    
    .uk-profile-modal-content {
        background-color: #fff;
        border-radius: 12px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .uk-profile-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .uk-profile-modal-header h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #000;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }
    
    .uk-profile-close-modal {
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: #999;
        line-height: 1;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s ease;
    }
    
    .uk-profile-close-modal:hover {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .uk-profile-modal-body {
        padding: 1.5rem;
    }
    
    .uk-profile-modal-subtitle {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }
    
    .uk-profile-form-section {
        margin-bottom: 2rem;
    }
    
    .uk-profile-form-section h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #000;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .uk-profile-section-description {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    
    .uk-profile-form-group {
        margin-bottom: 1.5rem;
    }
    
    .uk-profile-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }
    
    .uk-profile-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .uk-profile-input-wrapper i {
        position: absolute;
        left: 15px;
        color: #777;
        font-size: 1rem;
        z-index: 1;
    }
    
    .uk-profile-input-wrapper input {
        width: 100%;
        padding: 12px 12px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        background-color: #fff;
        color: #000;
        transition: all 0.3s ease;
    }
    
    .uk-profile-input-wrapper input:focus {
        outline: none;
        border-color: #000;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    }
    
    .uk-profile-form-hint {
        font-size: 0.8rem;
        color: #999;
        margin-top: 0.5rem;
    }
    
    .uk-profile-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .uk-profile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        text-decoration: none;
        min-width: 120px;
    }
    
    .uk-profile-btn-primary {
        background-color: #000;
        color: #fff;
        border-color: #000;
    }
    
    .uk-profile-btn-primary:hover {
        background-color: #333;
        border-color: #333;
    }
    
    .uk-profile-btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border-color: #ddd;
    }
    
    .uk-profile-btn-secondary:hover {
        background-color: #e0e0e0;
        border-color: #ccc;
    }
    
    /* Стили для уведомлений сотрудника */
    .uk-notif-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #ff4757;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .uk-notif-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        width: 380px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        z-index: 1000;
        margin-top: 10px;
        border: 1px solid #e0e0e0;
    }
    
    .uk-notif-dropdown.show {
        display: block;
    }
    
    .uk-notif-header {
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
        border-radius: 10px 10px 0 0;
    }
    
    .uk-notif-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .uk-notif-total {
        background: #000;
        color: white;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .uk-notif-list {
        max-height: 350px;
        overflow-y: auto;
    }
    
    .uk-notif-item {
        padding: 14px 20px;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .uk-notif-item:hover {
        background-color: #f9f9f9;
    }
    
    .uk-notif-item.new {
        background-color: #f0f7ff;
        border-left: 3px solid #080808;
    }
    
    .uk-notif-item i {
        color: #000;
        font-size: 18px;
        margin-top: 3px;
    }
    
    .uk-notif-text {
        font-weight: 600;
        margin: 0 0 6px 0;
        font-size: 14px;
        color: #333;
    }
    
    .uk-notif-message {
        margin: 0 0 6px 0;
        font-size: 13px;
        color: #666;
        line-height: 1.4;
    }
    
    .uk-notif-time {
        margin: 0;
        font-size: 11px;
        color: #999;
        font-weight: 500;
    }
    
    .uk-notif-empty {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }
    
    .uk-notif-empty i {
        font-size: 42px;
        margin-bottom: 12px;
        color: #e0e0e0;
    }
    
    .uk-notif-empty p {
        margin: 0;
        font-size: 14px;
    }
    
    .uk-notif-loading {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }
    
    .uk-notif-loading i {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .uk-notif-footer {
        padding: 12px 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
        border-radius: 0 0 10px 10px;
    }
    
    .uk-mark-all-read {
        background: none;
        border: none;
        color: #000;
        font-size: 13px;
        cursor: pointer;
        padding: 5px 10px;
        font-weight: 500;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .uk-mark-all-read:hover {
        background-color: #e0e0e0;
    }
    
    .view-all {
        font-size: 13px;
        color: #666;
        font-weight: 500;
    }
    </style>
</head>
<body>
    <!-- Шапка сотрудника УК -->
    <header class="uk-header">
        <div class="uk-container">
            <div class="uk-top-bar">
                <div class="uk-page-title">
                    <h1 id="uk-page-title">Панель администратора</h1>
                </div>
                <div class="uk-user-controls">
                    <!-- Уведомления -->
                    <div class="uk-notif-wrapper">
                        <button class="uk-notif-btn" id="ukNotifBtn">
                            <i class="fas fa-bell"></i>
                            <span class="uk-notif-count" id="ukNotificationCount">0</span>
                        </button>
                        <div class="uk-notif-dropdown" id="ukNotifDropdown">
                            <div class="uk-notif-header">
                                <h3><i class="fas fa-bell"></i> Уведомления</h3>
                                <span class="uk-notif-total" id="ukNotificationsUnreadCount">0 новых</span>
                            </div>
                            <div class="uk-notif-list" id="ukNotificationsList">
                                <!-- Уведомления будут загружаться динамически -->
                                <div class="uk-notif-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Загрузка уведомлений...</p>
                                </div>
                            </div>
                            <div class="uk-notif-footer">
                                <button class="uk-mark-all-read" id="ukMarkAllReadBtn">Отметить все как прочитанные</button>
                                <a href="#" class="view-all">Показать все</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Профиль сотрудника -->
                    <div class="uk-profile-wrapper">
                        <button class="uk-profile-btn" id="ukProfileBtn">
                            <div class="uk-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <span class="uk-user-name">
                                <?php echo explode(' ', $user_name)[0]; ?>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="uk-profile-dropdown" id="ukProfileDropdown">
                            <div class="uk-profile-info">
                                <div class="uk-dropdown-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="uk-dropdown-info">
                                    <h4><?php echo $user_name; ?></h4>
                                    <p><?php echo $user_email; ?></p>
                                    <p class="uk-user-role"><?php echo $user_role; ?></p>
                                </div>
                            </div>
                            <div class="uk-dropdown-divider"></div>
                            <a href="employee_dashboard.php" class="uk-dropdown-link">
                                <i class="fas fa-tachometer-alt"></i> Панель управления
                            </a>
                            <a href="employee_houses.php" class="uk-dropdown-link">
                                <i class="fas fa-building"></i> Управление домами
                            </a>
                            <a href="employee_users.php" class="uk-dropdown-link">
                                <i class="fas fa-users"></i> Управление пользователями
                            </a>
                            <a href="employee_requests.php" class="uk-dropdown-link">
                                <i class="fas fa-clipboard-list"></i> Все заявки
                            </a>
                            <div class="uk-dropdown-divider"></div>
                            <!-- Кнопка редактирования профиля -->
                            <button class="uk-dropdown-link" id="ukEditProfileBtn" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
                                <i class="fas fa-user-edit"></i> Редактировать профиль
                            </button>
                            <a href="index.php" class="uk-dropdown-link logout">
                                <i class="fas fa-sign-out-alt"></i> На главную
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Навигационное меню -->
            <nav class="uk-main-menu">
                <a href="employee_dashboard.php" class="uk-menu-link">
                    <i class="fas fa-tachometer-alt"></i> Панель
                </a>
                <a href="employee_houses.php" class="uk-menu-link">
                    <i class="fas fa-building"></i> Дома
                </a>
                <a href="employee_requests.php" class="uk-menu-link">
                    <i class="fas fa-clipboard-list"></i> Заявки
                </a>
                <a href="employee_users.php" class="uk-menu-link">
                    <i class="fas fa-users"></i> Пользователи
                </a>
            </nav>
        </div>
    </header>

    <!-- Модальное окно редактирования профиля -->
    <div class="uk-profile-modal" id="ukProfileModal">
        <div class="uk-profile-modal-content">
            <div class="uk-profile-modal-header">
                <h2><i class="fas fa-user-edit"></i> Редактирование профиля</h2>
                <button class="uk-profile-close-modal" id="ukCloseProfileModal">&times;</button>
            </div>
            <div class="uk-profile-modal-body">
                <p class="uk-profile-modal-subtitle">Обновите ваши личные данные</p>
                
                <form class="uk-profile-form" id="ukProfileForm">
                    <div class="uk-profile-form-section">
                        <h3><i class="fas fa-info-circle"></i> Личная информация</h3>
                        <p class="uk-profile-section-description">Измените данные, которые будут отображаться в системе</p>
                        
                        <div class="uk-profile-form-group">
                            <label for="uk-profile-email">Email</label>
                            <div class="uk-profile-input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="uk-profile-email" value="<?php echo $user_email; ?>" readonly>
                            </div>
                            <p class="uk-profile-form-hint">Email нельзя изменить</p>
                        </div>
                        
                        <div class="uk-profile-form-group">
                            <label for="uk-profile-name">ФИО</label>
                            <div class="uk-profile-input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="uk-profile-name" value="<?php echo $user_name; ?>">
                            </div>
                        </div>
                        
                        <div class="uk-profile-form-group">
                            <label for="uk-profile-phone">Телефон</label>
                            <div class="uk-profile-input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="uk-profile-phone" value="<?php echo $user_phone; ?>">
                            </div>
                        </div>
                        
                        <div class="uk-profile-form-group">
                            <label for="uk-profile-address">Адрес</label>
                            <div class="uk-profile-input-wrapper">
                                <i class="fas fa-home"></i>
                                <input type="text" id="uk-profile-address" value="<?php echo $user_address; ?>">
                            </div>
                        </div>
                        
                        <div class="uk-profile-form-group">
                            <label for="uk-profile-role">Роль</label>
                            <div class="uk-profile-input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="uk-profile-role" value="<?php echo $user_role; ?>" readonly>
                            </div>
                            <p class="uk-profile-form-hint">Роль изменяется администратором</p>
                        </div>
                    </div>
                    
                    <div class="uk-profile-form-actions">
                        <button type="button" class="uk-profile-btn uk-profile-btn-secondary" id="ukCancelProfileEdit">Отмена</button>
                        <button type="submit" class="uk-profile-btn uk-profile-btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main class="uk-main-content">

    <!-- JavaScript для шапки -->
    <script>
    // Для отладки
    console.log('Employee Header: User ID = <?php echo $user_id ?? "null"; ?>');
    
    // Глобальные функции для уведомлений сотрудника
    window.employeeHeaderFunctions = {
        loadNotifications: function() {
            console.log('Loading notifications for employee...');
            
            // Пробуем разные пути для API
            const apiPaths = [
                '../api/notifications.php',
                'api/notifications.php',
                '/api/notifications.php',
                './api/notifications.php',
                '../../api/notifications.php'
            ];
            
            // Пробуем первый путь
            let apiUrl = '../api/notifications.php?unread=true&limit=5';
            
            fetch(apiUrl)
                .then(response => {
                    console.log('Employee Notifications API response status:', response.status);
                    if (!response.ok) {
                        // Пробуем другой путь
                        return fetch('api/notifications.php?unread=true&limit=5');
                    }
                    return response.json();
                })
                .then(response => {
                    if (response instanceof Response) {
                        console.log('Trying alternative API path...');
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    }
                    return response;
                })
                .then(data => {
                    console.log('Employee notifications data received:', data);
                    if (data && data.success) {
                        this.updateNotificationsUI(data.notifications, data.unread_count);
                    } else {
                        console.error('Employee notifications API error:', data ? data.message : 'No data');
                        // Показываем тестовые данные при ошибке
                        this.showTestNotifications();
                    }
                })
                .catch(error => {
                    console.error('Error loading employee notifications:', error);
                    // Показываем тестовые данные при ошибке
                    this.showTestNotifications();
                });
        },
        
        showTestNotifications: function() {
            console.log('Showing test notifications for employee');
            const testNotifications = [
                {
                    id: 1,
                    title: 'Новая заявка',
                    message: 'Поступила новая заявка от Иванова И.И.',
                    type: 'info',
                    created_at: new Date().toISOString(),
                    is_read: 0
                },
                {
                    id: 2,
                    title: 'Заявка в работе',
                    message: 'Заявка №15 переведена в статус "В работе"',
                    type: 'warning',
                    created_at: new Date(Date.now() - 1800000).toISOString(),
                    is_read: 0
                },
                {
                    id: 3,
                    title: 'Новый пользователь',
                    message: 'Зарегистрирован новый пользователь Петрова А.С.',
                    type: 'success',
                    created_at: new Date(Date.now() - 7200000).toISOString(),
                    is_read: 1
                }
            ];
            this.updateNotificationsUI(testNotifications, 2);
        },
        
        updateNotificationsUI: function(notifications, unreadCount) {
            const notificationCount = document.getElementById('ukNotificationCount');
            const notificationsUnreadCount = document.getElementById('ukNotificationsUnreadCount');
            const notificationsList = document.getElementById('ukNotificationsList');
            
            // Обновляем счетчики
            if (notificationCount) {
                notificationCount.textContent = unreadCount || 0;
                notificationCount.style.display = (unreadCount > 0) ? 'inline-block' : 'none';
            }
            
            if (notificationsUnreadCount) {
                notificationsUnreadCount.textContent = (unreadCount || 0) + ' новых';
            }
            
            // Обновляем список уведомлений
            if (notificationsList) {
                if (!notifications || notifications.length === 0) {
                    notificationsList.innerHTML = `
                        <div class="uk-notif-empty">
                            <i class="fas fa-inbox"></i>
                            <p>Нет уведомлений</p>
                        </div>
                    `;
                } else {
                    let html = '';
                    notifications.forEach(notification => {
                        const timeAgo = this.getTimeAgo(notification.created_at);
                        const icon = this.getNotificationIcon(notification.type);
                        const unreadClass = notification.is_read == 0 ? 'new' : '';
                        
                        html += `
                            <div class="uk-notif-item ${unreadClass}" data-id="${notification.id}">
                                <i class="${icon}"></i>
                                <div>
                                    <p class="uk-notif-text">${notification.title || 'Уведомление'}</p>
                                    <p class="uk-notif-message">${notification.message || 'Нет описания'}</p>
                                    <p class="uk-notif-time">${timeAgo}</p>
                                </div>
                            </div>
                        `;
                    });
                    notificationsList.innerHTML = html;
                    
                    // Добавляем обработчики кликов на уведомления
                    document.querySelectorAll('.uk-notif-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const notificationId = this.getAttribute('data-id');
                            window.employeeHeaderFunctions.markAsRead(notificationId);
                            this.classList.remove('new');
                        });
                    });
                }
            }
        },
        
        markAsRead: function(notificationId) {
            fetch('../api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Employee notification marked as read');
                    this.loadNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking as read, trying alternative path:', error);
                // Пробуем другой путь
                return fetch('api/notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_as_read',
                        notification_id: notificationId
                    })
                });
            })
            .then(response => {
                if (response && response.json) {
                    return response.json();
                }
                return { success: false };
            })
            .then(data => {
                if (data.success) {
                    console.log('Employee notification marked as read (alternative path)');
                    this.loadNotifications();
                }
            })
            .catch(error => console.error('Error marking as read (final):', error));
        },
        
        markAllAsRead: function() {
            fetch('../api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_all_as_read'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('All employee notifications marked as read');
                    this.loadNotifications();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        },
        
        getTimeAgo: function(dateString) {
            if (!dateString) return 'недавно';
            
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'только что';
            if (diffMins < 60) return `${diffMins} мин. назад`;
            if (diffHours < 24) return `${diffHours} ч. назад`;
            return `${diffDays} дн. назад`;
        },
        
        getNotificationIcon: function(type) {
            const icons = {
                'info': 'fas fa-info-circle',
                'warning': 'fas fa-exclamation-triangle',
                'success': 'fas fa-check-circle',
                'error': 'fas fa-times-circle'
            };
            return icons[type] || 'fas fa-bell';
        }
    };

    // Функция инициализации шапки сотрудника
    function initEmployeeHeader() {
        console.log('Initializing employee header...');
        
        // Загружаем уведомления
        window.employeeHeaderFunctions.loadNotifications();
        
        // Элементы для уведомлений
        const ukNotifBtn = document.getElementById('ukNotifBtn');
        const ukNotifDropdown = document.getElementById('ukNotifDropdown');
        const ukMarkAllReadBtn = document.getElementById('ukMarkAllReadBtn');
        
        // Элементы для профиля
        const ukProfileBtn = document.getElementById('ukProfileBtn');
        const ukProfileDropdown = document.getElementById('ukProfileDropdown');
        const ukEditProfileBtn = document.getElementById('ukEditProfileBtn');
        const ukProfileModal = document.getElementById('ukProfileModal');
        const ukCloseProfileModal = document.getElementById('ukCloseProfileModal');
        const ukCancelProfileEdit = document.getElementById('ukCancelProfileEdit');
        const ukProfileForm = document.getElementById('ukProfileForm');
        
        // Переключение выпадающего меню уведомлений
        if (ukNotifBtn && ukNotifDropdown) {
            ukNotifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                ukNotifDropdown.classList.toggle('show');
                if (ukProfileDropdown) ukProfileDropdown.classList.remove('show');
            });
        }
        
        // Переключение выпадающего меню профиля
        if (ukProfileBtn && ukProfileDropdown) {
            ukProfileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                ukProfileDropdown.classList.toggle('show');
                if (ukNotifDropdown) ukNotifDropdown.classList.remove('show');
            });
        }
        
        // Открытие модального окна редактирования профиля
        if (ukEditProfileBtn) {
            ukEditProfileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (ukProfileModal) {
                    ukProfileModal.style.display = 'flex';
                }
                if (ukProfileDropdown) ukProfileDropdown.classList.remove('show');
                if (ukNotifDropdown) ukNotifDropdown.classList.remove('show');
            });
        }
        
        // Также можно открывать по двойному клику на аватар
        const ukAvatar = document.querySelector('.uk-avatar');
        if (ukAvatar) {
            ukAvatar.addEventListener('dblclick', function() {
                if (ukProfileModal) {
                    ukProfileModal.style.display = 'flex';
                }
                if (ukProfileDropdown) ukProfileDropdown.classList.remove('show');
                if (ukNotifDropdown) ukNotifDropdown.classList.remove('show');
            });
        }
        
        // Закрытие модального окна профиля
        if (ukCloseProfileModal) {
            ukCloseProfileModal.addEventListener('click', function() {
                if (ukProfileModal) {
                    ukProfileModal.style.display = 'none';
                }
            });
        }
        
        if (ukCancelProfileEdit) {
            ukCancelProfileEdit.addEventListener('click', function() {
                if (ukProfileModal) {
                    ukProfileModal.style.display = 'none';
                }
            });
        }
        
        // Обработка формы профиля
        if (ukProfileForm) {
            ukProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    full_name: document.getElementById('uk-profile-name').value,
                    phone: document.getElementById('uk-profile-phone').value,
                    address: document.getElementById('uk-profile-address').value
                };
                
                fetch('update_employee_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const userNameElement = document.querySelector('.uk-dropdown-info h4');
                        const userNameShort = document.querySelector('.uk-user-name');
                        
                        if (userNameElement) userNameElement.textContent = data.user.full_name;
                        if (userNameShort) userNameShort.textContent = data.user.full_name.split(' ')[0];
                        
                        alert('Данные профиля успешно обновлены!');
                        if (ukProfileModal) {
                            ukProfileModal.style.display = 'none';
                        }
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при обновлении профиля');
                });
            });
        }
        
        // Обработчик для кнопки "Отметить все как прочитанные"
        if (ukMarkAllReadBtn) {
            ukMarkAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.employeeHeaderFunctions.markAllAsRead();
            });
        }
        
        // Закрытие выпадающих меню при клике вне их
        document.addEventListener('click', function() {
            if (ukNotifDropdown) ukNotifDropdown.classList.remove('show');
            if (ukProfileDropdown) ukProfileDropdown.classList.remove('show');
        });
        
        // Предотвращение закрытия при клике внутри выпадающего меню
        if (ukNotifDropdown) {
            ukNotifDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        if (ukProfileDropdown) {
            ukProfileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Закрытие модального окна при клике на фон
        window.addEventListener('click', function(e) {
            if (ukProfileModal && e.target === ukProfileModal) {
                ukProfileModal.style.display = 'none';
            }
        });
        
        // Активация текущего пункта меню
        const currentPage = window.location.pathname.split('/').pop();
        const menuLinks = document.querySelectorAll('.uk-menu-link');
        
        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            }
        });
        
        // Обновляем заголовок страницы в шапке
        const pageTitleMap = {
            'employee_dashboard.php': 'Панель администратора',
            'employee_houses.php': 'Управление домами',
            'employee_users.php': 'Управление пользователями',
            'employee_requests.php': 'Все заявки'
        };
        
        if (pageTitleMap[currentPage]) {
            const pageTitleElement = document.getElementById('uk-page-title');
            if (pageTitleElement) {
                pageTitleElement.textContent = pageTitleMap[currentPage];
            }
        }
        
        // Добавляем стиль для активного пункта меню, если его нет в CSS
        const style = document.createElement('style');
        style.textContent = `
            .uk-menu-link.active {
                color: #000 !important;
                border-bottom: 3px solid #000 !important;
                font-weight: 600 !important;
            }
            
            .uk-notif-dropdown.show,
            .uk-profile-dropdown.show {
                display: block !important;
            }
        `;
        document.head.appendChild(style);
        
        // Периодически обновляем уведомления (каждые 60 секунд)
        setInterval(function() {
            window.employeeHeaderFunctions.loadNotifications();
        }, 60000);
        
        console.log('Employee header initialized successfully');
    }

    // Инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeHeader);
    } else {
        initEmployeeHeader();
    }
    </script>