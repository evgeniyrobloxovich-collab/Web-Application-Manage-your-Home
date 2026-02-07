<?php
// dashboard.php
require_once 'includes/session.php';
require_once 'models/Request.php';

Session::start();
Session::requireLogin('login.php');
Session::requireRole('житель', 'login.php');

// Получаем данные пользователя
$user_id = Session::get('user_id');
$user_name = Session::get('user_name');
$user_address = Session::get('user_address');

// Получаем статистику заявок
$requestModel = new Request();
$stats = $requestModel->getStats($user_id);

// Получаем последние заявки пользователя (5 шт)
$recent_requests = $requestModel->getByUserId($user_id, 'all', 5);

$page_title = "Личный кабинет";
?>
<?php include 'includes/profile_header.php'; ?>

<div class="dashboard-container">
    <!-- Блок с адресом -->
    <div class="address-card">
        <i class="fas fa-map-marker-alt"></i>
        <div>
            <h3>Адрес</h3>
            <p><?php echo htmlspecialchars($user_address); ?></p>
        </div>
    </div>

    <!-- Статистика заявок -->
    <div class="stats-section">
        <h2><i class="fas fa-chart-bar"></i> Статистика заявок</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Всего заявок</h3>
                <p class="stat-number"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon new">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Новые</h3>
                <p class="stat-number"><?php echo $stats['new'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon in-progress">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>В работе</h3>
                <p class="stat-number"><?php echo $stats['in_progress'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Выполнено</h3>
                <p class="stat-number"><?php echo $stats['completed'] ?? 0; ?></p>
            </div>
        </div>
    </div>

    <!-- Блок с уведомлениями -->
    <div class="notifications-section">
        <div class="section-header">
            <h2><i class="fas fa-bell"></i> Уведомления</h2>
            <span class="section-badge">Важные сообщения и обновления</span>
        </div>
        
        <!-- Уведомления будут загружаться динамически через JavaScript -->
        <div id="dashboard-notifications-container">
            <div class="notifications-empty" id="notifications-placeholder">
                <i class="fas fa-inbox"></i>
                <h3>Нет уведомлений</h3>
                <p>Здесь будут появляться важные сообщения от управляющей компании</p>
            </div>
        </div>
    </div>

    <!-- Блок "Мои заявки" -->
    <div class="requests-section">
        <div class="section-header">
            <h2><i class="fas fa-list"></i> Мои заявки</h2>
            <button class="btn btn-primary" id="createRequestBtn">
                <i class="fas fa-plus"></i> Создать заявку
            </button>
        </div>
        
        <?php if (empty($recent_requests)): ?>
            <div class="requests-empty">
                <i class="fas fa-clipboard"></i>
                <h3>Заявок пока нет</h3>
                <p>Создайте первую заявку на обслуживание</p>
                <button class="btn btn-primary" id="createRequestBtn2">
                    <i class="fas fa-plus"></i> Создать заявку
                </button>
            </div>
        <?php else: ?>
            <div class="requests-preview-list">
                <?php foreach (array_slice($recent_requests, 0, 3) as $request): ?>
                    <div class="request-preview-item">
                        <div class="request-preview-header">
                            <span class="request-preview-category <?php echo htmlspecialchars($request['category']); ?>">
                                <?php 
                                $categories = [
                                    'водопровод' => 'Водоснабжение',
                                    'отопление' => 'Отопление',
                                    'электроснабжение' => 'Электричество',
                                    'другое' => 'Другое'
                                ];
                                echo $categories[$request['category']] ?? $request['category'];
                                ?>
                            </span>
                            <span class="request-preview-status <?php echo htmlspecialchars($request['status']); ?>">
                                <?php 
                                $status_labels = [
                                    'новая' => 'Новая',
                                    'в работе' => 'В работе',
                                    'выполнена' => 'Выполнена',
                                    'отклонена' => 'Отклонена'
                                ];
                                echo $status_labels[$request['status']] ?? $request['status'];
                                ?>
                            </span>
                        </div>
                        <p class="request-preview-description">
                            <?php 
                            $description = htmlspecialchars($request['description']);
                            echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                            ?>
                        </p>
                        <div class="request-preview-details">
                            <span><i class="far fa-calendar"></i> <?php echo date('d.m.Y', strtotime($request['created_at'])); ?></span>
                            <a href="requests.php?id=<?php echo $request['id']; ?>" class="view-request-link">Подробнее →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($recent_requests) > 3): ?>
                    <div class="view-all-container">
                        <a href="requests.php" class="view-all-link">Показать все заявки (<?php echo count($recent_requests); ?>) →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Блок "Последние заявки" -->
    <div class="recent-requests">
        <div class="section-header">
            <h2><i class="fas fa-history"></i> Последние заявки</h2>
            <a href="requests.php" class="view-all">Все заявки <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <?php if (empty($recent_requests)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>У вас пока нет заявок</p>
                <button class="btn btn-outline" id="createRequestBtn3">
                    Создать первую заявку
                </button>
            </div>
        <?php else: ?>
            <div class="recent-requests-list">
                <?php foreach ($recent_requests as $request): ?>
                    <div class="recent-request-item">
                        <div class="recent-request-info">
                            <div class="recent-request-title">
                                <span class="recent-request-id">#<?php echo $request['id']; ?></span>
                                <span class="recent-request-category <?php echo htmlspecialchars($request['category']); ?>">
                                    <?php 
                                    $categories = [
                                        'водопровод' => 'Водоснабжение',
                                        'отопление' => 'Отопление',
                                        'электроснабжение' => 'Электричество',
                                        'другое' => 'Другое'
                                    ];
                                    echo $categories[$request['category']] ?? $request['category'];
                                    ?>
                                </span>
                            </div>
                            <p class="recent-request-description">
                                <?php 
                                $description = htmlspecialchars($request['description']);
                                echo strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description;
                                ?>
                            </p>
                            <div class="recent-request-meta">
                                <span class="recent-request-date">
                                    <i class="far fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?>
                                </span>
                                <span class="recent-request-status <?php echo htmlspecialchars($request['status']); ?>">
                                    <?php 
                                    $status_labels = [
                                        'новая' => 'Новая',
                                        'в работе' => 'В работе',
                                        'выполнена' => 'Выполнена',
                                        'отклонена' => 'Отклонена'
                                    ];
                                    echo $status_labels[$request['status']] ?? $request['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        <a href="requests.php?id=<?php echo $request['id']; ?>" class="recent-request-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно создания заявки -->
<div class="modal" id="requestModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Создать заявку</h2>
            <button class="close-modal" id="closeRequestModal">&times;</button>
        </div>
        <div class="modal-body">
            <form class="request-form" id="requestForm">
                <div class="form-group">
                    <label for="request-apartment">Номер квартиры</label>
                    <div class="input-wrapper">
                        <i class="fas fa-home"></i>
                        <input type="text" id="request-apartment" 
                               value="<?php echo htmlspecialchars($user_address); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-name">Имя и фамилия</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="request-name" 
                               value="<?php echo htmlspecialchars($user_name); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-category">Категория проблемы *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-tags"></i>
                        <select id="request-category" required>
                            <option value="">Выберите категорию</option>
                            <option value="водопровод">Водопровод</option>
                            <option value="отопление">Отопление</option>
                            <option value="электроснабжение">Электроснабжение</option>
                            <option value="другое">Другое</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-description">Описание проблемы *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-comment-alt"></i>
                        <textarea id="request-description" rows="4" 
                                  placeholder="Опишите конкретную проблему в квартире..." required></textarea>
                    </div>
                    <p class="form-hint">Опишите проблему максимально подробно</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelRequest">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="submitRequestBtn">
                        <span id="submitBtnText">Создать заявку</span>
                        <span id="loadingSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Обработка...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
/* Стили для предварительного просмотра заявок */
.requests-preview-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-preview-item {
    background-color: #fff;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.request-preview-item:hover {
    border-color: #000;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.request-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.request-preview-category {
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.request-preview-category.водопровод {
    background-color: #e3f2fd;
    color: #1976d2;
}

.request-preview-category.отопление {
    background-color: #ffebee;
    color: #d32f2f;
}

.request-preview-category.электроснабжение {
    background-color: #fff3e0;
    color: #f57c00;
}

.request-preview-category.другое {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.request-preview-status {
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.request-preview-status.новая {
    background-color: #ffebee;
    color: #d32f2f;
}

.request-preview-status.в\ работе {
    background-color: #fff3e0;
    color: #f57c00;
}

.request-preview-status.выполнена {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.request-preview-status.отклонена {
    background-color: #f5f5f5;
    color: #757575;
}

.request-preview-description {
    color: #333;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.request-preview-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #666;
}

.view-request-link {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
}

.view-request-link:hover {
    text-decoration: underline;
}

.view-all-container {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #f0f0f0;
    margin-top: 0.5rem;
}

.view-all-link {
    color: #000;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Стили для последних заявок */
.recent-requests-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recent-request-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #fff;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.recent-request-item:hover {
    border-color: #000;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.recent-request-info {
    flex: 1;
}

.recent-request-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.3rem;
}

.recent-request-id {
    font-weight: 600;
    color: #000;
    font-size: 0.9rem;
}

.recent-request-category {
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.recent-request-category.водопровод {
    background-color: #e3f2fd;
    color: #1976d2;
}

.recent-request-category.отопление {
    background-color: #ffebee;
    color: #d32f2f;
}

.recent-request-category.электроснабжение {
    background-color: #fff3e0;
    color: #f57c00;
}

.recent-request-category.другое {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.recent-request-description {
    color: #333;
    font-size: 0.85rem;
    line-height: 1.3;
    margin-bottom: 0.5rem;
}

.recent-request-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #666;
}

.recent-request-date {
    display: flex;
    align-items: center;
    gap: 4px;
}

.recent-request-status {
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.recent-request-status.новая {
    background-color: #ffebee;
    color: #d32f2f;
}

.recent-request-status.в\ работе {
    background-color: #fff3e0;
    color: #f57c00;
}

.recent-request-status.выполнена {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.recent-request-status.отклонена {
    background-color: #f5f5f5;
    color: #757575;
}

.recent-request-link {
    color: #000;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.recent-request-link:hover {
    background-color: #f5f5f5;
}

/* Стили для уведомлений на дашборде */
.dashboard-notifications-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dashboard-notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background-color: #fff;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.dashboard-notification-item:hover {
    border-color: #000;
    background-color: #fafafa;
}

.dashboard-notification-item.new {
    background-color: #f0f7ff;
    border-left: 3px solid #1976d2;
}

.dashboard-notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #666;
    flex-shrink: 0;
}

.dashboard-notification-item.info .dashboard-notification-icon {
    background-color: #e3f2fd;
    color: #1976d2;
}

.dashboard-notification-item.success .dashboard-notification-icon {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.dashboard-notification-item.warning .dashboard-notification-icon {
    background-color: #fff3e0;
    color: #f57c00;
}

.dashboard-notification-item.error .dashboard-notification-icon {
    background-color: #ffebee;
    color: #d32f2f;
}

.dashboard-notification-content {
    flex: 1;
}

.dashboard-notification-title {
    font-weight: 600;
    color: #000;
    margin-bottom: 0.2rem;
    font-size: 0.95rem;
}

.dashboard-notification-message {
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
    line-height: 1.3;
}

.dashboard-notification-time {
    font-size: 0.75rem;
    color: #999;
}

/* Стили для загрузки */
#loadingSpinner {
    display: none;
}

#loadingSpinner.show {
    display: inline-block;
}

#submitBtnText {
    display: inline-block;
}

#submitBtnText.hide {
    display: none;
}

.form-hint {
    font-size: 0.8rem;
    color: #999;
    margin-top: 0.3rem;
    font-style: italic;
}
</style>

<script>
// ==================== ОБЩИЕ API ФУНКЦИИ ====================

// Общая функция для работы с API
async function sendApiRequest(action, data = {}) {
    const requestData = { ...data, action: action };
    
    // Список возможных путей к API
    const apiPaths = [
        'api/requests.php',
        'api/test_api.php',
        '../api/requests.php',
        '../api/test_api.php'
    ];
    
    let lastError = null;
    
    for (const path of apiPaths) {
        try {
            console.log(`Пробуем API путь: ${path}`, requestData);
            
            const response = await fetch(path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            // Проверяем статус ответа
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 200)}`);
            }
            
            const text = await response.text();
            console.log(`Ответ от ${path} (первые 500 символов):`, text.substring(0, 500));
            
            try {
                const result = JSON.parse(text);
                console.log(`Успешный JSON от ${path}:`, result);
                return result;
            } catch (jsonError) {
                console.error(`Ошибка парсинга JSON:`, jsonError);
                
                // Если не JSON, но путь к тестовому API
                if (path.includes('test_api.php')) {
                    return {
                        success: true,
                        message: 'Тестовый режим: ' + text.substring(0, 100),
                        test_mode: true
                    };
                }
                
                lastError = new Error(`Сервер вернул не JSON: ${text.substring(0, 200)}`);
                continue;
            }
            
        } catch (fetchError) {
            console.error(`Ошибка fetch для ${path}:`, fetchError);
            lastError = fetchError;
            continue;
        }
    }
    
    throw lastError || new Error('Не удалось подключиться к API');
}

// Функция для создания заявки
async function createRequest(category, description, apartment) {
    const apartmentMatch = apartment.match(/кв\.\s*(\d+)/i);
    const apartmentNumber = apartmentMatch ? apartmentMatch[1] : '';
    
    return await sendApiRequest('create', {
        category: category,
        description: description,
        apartment_number: apartmentNumber
    });
}

// Функция для обновления статуса
async function updateRequestStatus(requestId, status) {
    return await sendApiRequest('update_status', {
        request_id: requestId,
        status: status
    });
}

// Функция для удаления заявки
async function deleteRequest(requestId) {
    return await sendApiRequest('delete', {
        request_id: requestId
    });
}

// Экспорт функций в глобальную область видимости
window.sendApiRequest = sendApiRequest;
window.createRequest = createRequest;
window.updateRequestStatus = updateRequestStatus;
window.deleteRequest = deleteRequest;

// Тестовая функция
window.testApiConnection = async function() {
    console.log('Тестирование подключения к API...');
    try {
        const result = await sendApiRequest('test', { test: true });
        console.log('Результат теста:', result);
        return result;
    } catch (error) {
        console.error('Ошибка теста:', error);
        return { success: false, error: error.message };
    }
};

// ==================== ОСНОВНОЙ КОД DASHBOARD ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard загружен, инициализация API...');
    
    // Элементы для создания заявки
    const createRequestBtns = document.querySelectorAll('#createRequestBtn, #createRequestBtn2, #createRequestBtn3');
    const requestModal = document.getElementById('requestModal');
    const closeRequestModal = document.getElementById('closeRequestModal');
    const cancelRequest = document.getElementById('cancelRequest');
    const requestForm = document.getElementById('requestForm');
    const submitRequestBtn = document.getElementById('submitRequestBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    // Загружаем уведомления для дашборда
    loadDashboardNotifications();
    
    // Функция загрузки уведомлений для дашборда
    async function loadDashboardNotifications() {
        try {
            const response = await fetch('api/notifications.php?limit=3');
            const data = await response.json();
            
            if (data.success && data.notifications && data.notifications.length > 0) {
                updateDashboardNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Error loading dashboard notifications:', error);
        }
    }
    
    // Функция обновления уведомлений на дашборде
    function updateDashboardNotifications(notifications) {
        const container = document.getElementById('dashboard-notifications-container');
        
        if (!container) return;
        
        let html = '<div class="dashboard-notifications-list">';
        
        notifications.forEach(notification => {
            const timeAgo = getTimeAgo(notification.created_at);
            const icon = getNotificationIcon(notification.type);
            const unreadClass = notification.is_read == 0 ? 'new' : '';
            
            html += `
                <div class="dashboard-notification-item ${unreadClass} ${notification.type}" data-id="${notification.id}">
                    <div class="dashboard-notification-icon">
                        <i class="${icon}"></i>
                    </div>
                    <div class="dashboard-notification-content">
                        <div class="dashboard-notification-title">${notification.title || 'Уведомление'}</div>
                        <div class="dashboard-notification-message">${notification.message || 'Нет описания'}</div>
                        <div class="dashboard-notification-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Добавляем обработчики кликов на уведомления
        container.querySelectorAll('.dashboard-notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                markNotificationAsRead(notificationId);
            });
        });
    }
    
    // Функция для отметки уведомления как прочитанного
    async function markNotificationAsRead(notificationId) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            if (data.success) {
                // Обновляем уведомления на дашборде
                loadDashboardNotifications();
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }
    
    // Вспомогательная функция для форматирования времени
    function getTimeAgo(dateString) {
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
        if (diffDays === 1) return 'вчера';
        return `${diffDays} дн. назад`;
    }
    
    // Вспомогательная функция для получения иконки уведомления
    function getNotificationIcon(type) {
        const icons = {
            'info': 'fas fa-info-circle',
            'warning': 'fas fa-exclamation-triangle',
            'success': 'fas fa-check-circle',
            'error': 'fas fa-times-circle'
        };
        return icons[type] || 'fas fa-bell';
    }
    
    // Открытие модального окна создания заявки
    createRequestBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (requestModal) {
                requestModal.style.display = 'flex';
            }
        });
    });
    
    // Закрытие модального окна заявки
    if (closeRequestModal) {
        closeRequestModal.addEventListener('click', function() {
            if (requestModal) {
                requestModal.style.display = 'none';
                requestForm.reset();
            }
        });
    }
    
    if (cancelRequest) {
        cancelRequest.addEventListener('click', function() {
            if (requestModal) {
                requestModal.style.display = 'none';
                requestForm.reset();
            }
        });
    }
    
    // Обработка формы создания заявки
    if (requestForm) {
        requestForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Показываем индикатор загрузки
            if (submitBtnText && loadingSpinner) {
                submitBtnText.style.display = 'none';
                loadingSpinner.style.display = 'inline-block';
                submitRequestBtn.disabled = true;
            }
            
            const category = document.getElementById('request-category').value;
            const description = document.getElementById('request-description').value;
            const apartment = document.getElementById('request-apartment').value;
            
            try {
                // Используем нашу универсальную функцию
                const result = await createRequest(category, description, apartment);
                
                // Скрываем индикатор загрузки
                if (submitBtnText && loadingSpinner) {
                    submitBtnText.style.display = 'inline-block';
                    loadingSpinner.style.display = 'none';
                    submitRequestBtn.disabled = false;
                }
                
                if (result.success) {
                    alert('Заявка успешно создана! Вы получите уведомление о ее статусе.');
                    if (requestModal) {
                        requestModal.style.display = 'none';
                    }
                    requestForm.reset();
                    
                    // Перезагружаем страницу для обновления данных
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    const errorMsg = result.message || result.error || 'Неизвестная ошибка сервера';
                    alert('Ошибка: ' + errorMsg);
                    console.error('Ошибка API:', result);
                }
            } catch (error) {
                console.error('Критическая ошибка:', error);
                
                // Скрываем индикатор загрузки
                if (submitBtnText && loadingSpinner) {
                    submitBtnText.style.display = 'inline-block';
                    loadingSpinner.style.display = 'none';
                    submitRequestBtn.disabled = false;
                }
                
                alert('Произошла ошибка при создании заявки:\n\n' + error.message);
            }
        });
    }
    
    // Закрытие модального окна при клике на фон
    window.addEventListener('click', function(e) {
        if (requestModal && e.target === requestModal) {
            requestModal.style.display = 'none';
            if (requestForm) requestForm.reset();
        }
    });
    
    // Автоматически тестируем подключение при загрузке
    setTimeout(async () => {
        const testResult = await testApiConnection();
        console.log('Результат теста подключения:', testResult);
    }, 1000);
    
    console.log('Dashboard инициализирован. API функции доступны.');
});
</script>