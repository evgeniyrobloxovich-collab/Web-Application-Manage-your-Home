<?php
require_once 'includes/session.php';

// Проверка авторизации
Session::requireLogin('login.php');
Session::requireRole('житель', 'login.php');

// Получаем данные пользователя из сессии
$user_name = Session::get('user_name');
$user_email = Session::get('user_email');
$user_phone = Session::get('user_phone');
$user_role = Session::get('user_role');
$user_address = Session::get('user_address');
$user_id = Session::get('user_id'); // Добавляем получение ID

// Если данные отсутствуют, используем демо
if (empty($user_name)) {
    $user_name = "Жека Алигатор";
    $user_email = "zhitel@demo.ru";
    $user_phone = "+79512488211";
    $user_role = "Житель";
    $user_address = "ул. Первомайская, д.23, кв. 23";
    $user_id = 1; // Демо ID
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — Управляй домом</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
    /* Стили для уведомлений жителя */
    .notification-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ff4757;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .notifications-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        width: 350px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 1000;
        margin-top: 10px;
    }
    
    .notifications-dropdown.show {
        display: block;
    }
    
    .notifications-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notifications-header h3 {
        margin: 0;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .notifications-count {
        background: #000000;
        color: white;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    
    .notifications-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f5f5f5;
        display: flex;
        gap: 10px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .notification-item:hover {
        background-color: #f9f9f9;
    }
    
    .notification-item.new {
        background-color: #f0f7ff;
    }
    
    .notification-item i {
        color: #000000;
        font-size: 18px;
        margin-top: 3px;
    }
    
    .notification-text {
        font-weight: 600;
        margin: 0 0 5px 0;
        font-size: 14px;
    }
    
    .notification-message {
        margin: 0 0 5px 0;
        font-size: 13px;
        color: #666;
    }
    
    .notification-time {
        margin: 0;
        font-size: 11px;
        color: #999;
    }
    
    .notification-empty {
        padding: 30px 20px;
        text-align: center;
        color: #999;
    }
    
    .notification-empty i {
        font-size: 40px;
        margin-bottom: 10px;
        color: #ddd;
    }
    
    .notification-empty p {
        margin: 0;
    }
    
    .notification-loading {
        padding: 30px 20px;
        text-align: center;
        color: #999;
    }
    
    .notification-loading i {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .notifications-footer {
        padding: 10px 15px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .mark-all-read {
        background: none;
        border: none;
        color: #000000;
        font-size: 12px;
        cursor: pointer;
        padding: 5px;
    }
    
    .mark-all-read:hover {
        text-decoration: underline;
    }
    
    .view-all-notifications {
        font-size: 12px;
        color: #666;
    }
    
    /* Стили для модального окна профиля жителя */
    .profile-modal {
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
    
    .profile-modal-content {
        background-color: #fff;
        border-radius: 12px;
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .profile-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .profile-modal-header h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #000;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }
    
    .profile-close-modal {
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
    
    .profile-close-modal:hover {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .profile-modal-body {
        padding: 1.5rem;
    }
    
    .profile-modal-subtitle {
        color: #666;
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }
    
    .profile-form-section {
        margin-bottom: 2rem;
    }
    
    .profile-form-section h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #000;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .profile-section-description {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    
    .profile-form-group {
        margin-bottom: 1.5rem;
    }
    
    .profile-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }
    
    .profile-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .profile-input-wrapper i {
        position: absolute;
        left: 15px;
        color: #777;
        font-size: 1rem;
        z-index: 1;
    }
    
    .profile-input-wrapper input {
        width: 100%;
        padding: 12px 12px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        background-color: #fff;
        color: #000;
        transition: all 0.3s ease;
    }
    
    .profile-input-wrapper input:focus {
        outline: none;
        border-color: #000000;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    .profile-form-hint {
        font-size: 0.8rem;
        color: #999;
        margin-top: 0.5rem;
    }
    
    .profile-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f0f0f0;
    }
    
    .profile-btn {
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
    
    .profile-btn-primary {
        background-color: #000000;
        color: #fff;
        border-color: #000000;
    }
    
    .profile-btn-primary:hover {
        background-color: #000000;
        border-color: #000000;
    }
    
    .profile-btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border-color: #ddd;
    }
    
    .profile-btn-secondary:hover {
        background-color: #e0e0e0;
        border-color: #ccc;
    }
    </style>
</head>
<body>
    <!-- Шапка профиля -->
    <header class="profile-header">
        <div class="header-container">
            <div class="profile-nav">
                <div class="page-title">
                    <h1 id="page-title">Личный кабинет</h1>
                </div>
                <div class="profile-controls">
                    <!-- Уведомления -->
                    <div class="notifications-wrapper">
                        <button class="notifications-btn" id="notificationsBtn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count" id="notificationCount">0</span>
                        </button>
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notifications-header">
                                <h3><i class="fas fa-bell"></i> Уведомления</h3>
                                <span class="notifications-count" id="notificationsUnreadCount">0 новых</span>
                            </div>
                            <div class="notifications-list" id="notificationsList">
                                <!-- Уведомления будут загружаться динамически -->
                                <div class="notification-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Загрузка уведомлений...</p>
                                </div>
                            </div>
                            <div class="notifications-footer">
                                <button class="mark-all-read" id="markAllReadBtn">Отметить все как прочитанные</button>
                                <a href="#" class="view-all-notifications">Показать все</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Профиль пользователя -->
                    <div class="profile-wrapper">
                        <button class="profile-btn" id="profileBtn">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="user-name-short"><?php echo explode(' ', $user_name)[0]; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-info">
                                <div class="dropdown-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="dropdown-user-info">
                                    <h4><?php echo $user_name; ?></h4>
                                    <p><?php echo $user_email; ?></p>
                                    <p class="user-role"><?php echo $user_role; ?></p>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="dashboard.php" class="dropdown-item">
                                <i class="fas fa-home"></i> Главная
                            </a>
                            <a href="requests.php" class="dropdown-item">
                                <i class="fas fa-list"></i> Мои заявки
                            </a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item edit-profile-btn" id="editProfileBtn">
                                <i class="fas fa-user-edit"></i> Редактировать профиль
                            </button>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Выйти
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Модальное окно редактирования профиля -->
    <div class="profile-modal" id="profileModal">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <h2><i class="fas fa-user-edit"></i> Профиль пользователя</h2>
                <button class="profile-close-modal" id="closeProfileModal">&times;</button>
            </div>
            <div class="profile-modal-body">
                <p class="profile-modal-subtitle">Редактирование личной информации</p>
                
                <form class="profile-form" id="profileForm">
                    <div class="profile-form-section">
                        <h3><i class="fas fa-info-circle"></i> Личная информация</h3>
                        <p class="profile-section-description">Обновить ваши данные</p>
                        
                        <div class="profile-form-group">
                            <label for="profile-email">Email</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="profile-email" value="<?php echo $user_email; ?>" readonly>
                            </div>
                            <p class="profile-form-hint">Email нельзя изменить</p>
                        </div>
                        
                        <div class="profile-form-group">
                            <label for="profile-name">ФИО</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="profile-name" value="<?php echo $user_name; ?>">
                            </div>
                        </div>
                        
                        <div class="profile-form-group">
                            <label for="profile-phone">Телефон</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="profile-phone" value="<?php echo $user_phone; ?>">
                            </div>
                        </div>
                        
                        <div class="profile-form-group">
                            <label for="profile-address">Адрес</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-home"></i>
                                <input type="text" id="profile-address" value="<?php echo $user_address; ?>">
                            </div>
                        </div>
                        
                        <div class="profile-form-group">
                            <label for="profile-role">Роль</label>
                            <div class="profile-input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="profile-role" value="<?php echo $user_role; ?>" readonly>
                            </div>
                            <p class="profile-form-hint">Роль изменяется администратором</p>
                        </div>
                    </div>
                    
                    <div class="profile-form-actions">
                        <button type="button" class="profile-btn profile-btn-secondary" id="cancelProfileEdit">Отмена</button>
                        <button type="submit" class="profile-btn profile-btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main class="profile-main">

    <script>
    // Для отладки
    console.log('Profile Header: User ID = <?php echo $user_id ?? "null"; ?>');
    
    // Глобальные функции для уведомлений
    window.headerFunctions = {
        loadNotifications: function() {
            console.log('Loading notifications for resident...');
            
            // Пробуем разные пути для API
            const apiPaths = [
                'api/notifications.php',
                '../api/notifications.php',
                '/api/notifications.php',
                './api/notifications.php'
            ];
            
            let apiUrl = 'api/notifications.php?unread=true&limit=5';
            
            fetch(apiUrl)
                .then(response => {
                    console.log('Notifications API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Notifications data received:', data);
                    if (data.success) {
                        this.updateNotificationsUI(data.notifications, data.unread_count);
                    } else {
                        console.error('Notifications API error:', data.message);
                        // Показываем тестовые данные при ошибке
                        this.showTestNotifications();
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    // Показываем тестовые данные при ошибке
                    this.showTestNotifications();
                });
        },
        
        showTestNotifications: function() {
            console.log('Showing test notifications');
            const testNotifications = [
                {
                    id: 1,
                    title: 'Тестовое уведомление',
                    message: 'Это тестовое уведомление для демонстрации',
                    type: 'info',
                    created_at: new Date().toISOString(),
                    is_read: 0
                },
                {
                    id: 2,
                    title: 'Системное сообщение',
                    message: 'Добро пожаловать в личный кабинет!',
                    type: 'success',
                    created_at: new Date(Date.now() - 3600000).toISOString(),
                    is_read: 0
                }
            ];
            this.updateNotificationsUI(testNotifications, 2);
        },
        
        updateNotificationsUI: function(notifications, unreadCount) {
            const notificationCount = document.getElementById('notificationCount');
            const notificationsUnreadCount = document.getElementById('notificationsUnreadCount');
            const notificationsList = document.getElementById('notificationsList');
            
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
                        <div class="notification-empty">
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
                            <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                                <i class="${icon}"></i>
                                <div>
                                    <p class="notification-text">${notification.title || 'Уведомление'}</p>
                                    <p class="notification-message">${notification.message || 'Нет описания'}</p>
                                    <p class="notification-time">${timeAgo}</p>
                                </div>
                            </div>
                        `;
                    });
                    notificationsList.innerHTML = html;
                    
                    // Добавляем обработчики кликов на уведомления
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const notificationId = this.getAttribute('data-id');
                            window.headerFunctions.markAsRead(notificationId);
                            this.classList.remove('new');
                        });
                    });
                }
            }
        },
        
        markAsRead: function(notificationId) {
            fetch('api/notifications.php', {
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
                    console.log('Notification marked as read');
                    this.loadNotifications();
                }
            })
            .catch(error => console.error('Error marking as read:', error));
        },
        
        markAllAsRead: function() {
            fetch('api/notifications.php', {
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
                    console.log('All notifications marked as read');
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

    // Функция инициализации шапки
    function initProfileHeader() {
        console.log('Initializing profile header...');
        
        // Загружаем уведомления
        window.headerFunctions.loadNotifications();
        
        // Элементы для профиля
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const profileModal = document.getElementById('profileModal');
        const closeProfileModal = document.getElementById('closeProfileModal');
        const cancelProfileEdit = document.getElementById('cancelProfileEdit');
        const profileForm = document.getElementById('profileForm');
        
        // Элементы для уведомлений
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        
        // Переключение выпадающего меню уведомлений
        if (notificationsBtn && notificationsDropdown) {
            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationsDropdown.classList.toggle('show');
                if (profileDropdown) profileDropdown.classList.remove('show');
            });
        }
        
        // Переключение выпадающего меню профиля
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
                if (notificationsDropdown) notificationsDropdown.classList.remove('show');
            });
        }
        
        // Открытие модального окна редактирования профиля
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', function() {
                if (profileModal) {
                    profileModal.style.display = 'flex';
                }
                if (profileDropdown) profileDropdown.classList.remove('show');
                if (notificationsDropdown) notificationsDropdown.classList.remove('show');
            });
        }
        
        // Закрытие модального окна профиля
        if (closeProfileModal) {
            closeProfileModal.addEventListener('click', function() {
                if (profileModal) {
                    profileModal.style.display = 'none';
                }
            });
        }
        
        if (cancelProfileEdit) {
            cancelProfileEdit.addEventListener('click', function() {
                if (profileModal) {
                    profileModal.style.display = 'none';
                }
            });
        }
        
        // Обработка формы профиля
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    full_name: document.getElementById('profile-name').value,
                    phone: document.getElementById('profile-phone').value,
                    address: document.getElementById('profile-address').value
                };
                
                fetch('update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const userNameElement = document.querySelector('.dropdown-user-info h4');
                        const userNameShort = document.querySelector('.user-name-short');
                        
                        if (userNameElement) userNameElement.textContent = data.user.full_name;
                        if (userNameShort) userNameShort.textContent = data.user.full_name.split(' ')[0];
                        
                        alert('Данные профиля успешно обновлены!');
                        if (profileModal) {
                            profileModal.style.display = 'none';
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
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.headerFunctions.markAllAsRead();
            });
        }
        
        // Закрытие выпадающих меню при клике вне их
        document.addEventListener('click', function() {
            if (notificationsDropdown) notificationsDropdown.classList.remove('show');
            if (profileDropdown) profileDropdown.classList.remove('show');
        });
        
        // Предотвращение закрытия при клике внутри выпадающего меню
        if (notificationsDropdown) {
            notificationsDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        if (profileDropdown) {
            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Закрытие модального окна при клике на фон
        window.addEventListener('click', function(e) {
            if (profileModal && e.target === profileModal) {
                profileModal.style.display = 'none';
            }
        });
        
        // Периодически обновляем уведомления (каждые 60 секунд)
        setInterval(function() {
            window.headerFunctions.loadNotifications();
        }, 60000);
        
        console.log('Profile header initialized successfully');
    }

    // Инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfileHeader);
    } else {
        initProfileHeader();
    }
    </script>