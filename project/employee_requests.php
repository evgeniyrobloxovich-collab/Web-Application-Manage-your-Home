<?php
// employee_requests.php - Страница заявок для сотрудника УК
require_once 'includes/session.php';
require_once 'models/Request.php';

Session::start();
Session::requireLogin('login.php');
Session::requireRole('сотрудник_ук', 'login.php');

$requestModel = new Request();

// Получаем статус фильтра и поисковый запрос
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Получаем все заявки
$requests = $requestModel->getAll($status_filter);

// Фильтруем по поисковому запросу
if (!empty($search_query)) {
    $search_lower = mb_strtolower($search_query);
    
    // Маппинг категорий для поиска
    $category_map = [
        'водопровод' => ['водопровод', 'водоснабжение', 'вода', 'кран', 'труба', 'смеситель', 'протечка', 'утечка'],
        'отопление' => ['отопление', 'батарея', 'радиатор', 'тепло', 'холодно', 'отопительный', 'отопит', 'температура'],
        'электроснабжение' => ['электроснабжение', 'электричество', 'свет', 'розетка', 'выключатель', 'проводка', 'электро', 'лампа', 'пробки'],
        'другое' => ['другое', 'иное', 'прочее', 'остальное']
    ];
    
    // Маппинг статусов для поиска
    $status_map = [
        'новая' => ['новая', 'новый', 'создана'],
        'в работе' => ['в работе', 'работа', 'выполняется', 'обработке'],
        'выполнена' => ['выполнена', 'выполнено', 'завершена', 'завершено'],
        'отклонена' => ['отклонена', 'отклонено', 'отказ']
    ];
    
    $requests = array_filter($requests, function($request) use ($search_lower, $category_map, $status_map) {
        // Приводим все к нижнему регистру
        $description = mb_strtolower($request['description'] ?? '');
        $full_name = mb_strtolower($request['full_name'] ?? '');
        $address = mb_strtolower($request['address'] ?? '');
        $phone = mb_strtolower($request['phone'] ?? '');
        $category = mb_strtolower($request['category'] ?? '');
        $status = mb_strtolower($request['status'] ?? '');
        $apartment = mb_strtolower($request['apartment_number'] ?? '');
        
        // Проверяем прямое совпадение в текстовых полях
        if (strpos($description, $search_lower) !== false ||
            strpos($full_name, $search_lower) !== false ||
            strpos($address, $search_lower) !== false ||
            strpos($phone, $search_lower) !== false ||
            strpos($category, $search_lower) !== false ||
            strpos($status, $search_lower) !== false ||
            strpos($apartment, $search_lower) !== false) {
            return true;
        }
        
        // Проверяем совпадение по категории через маппинг
        if (isset($category_map[$request['category']])) {
            foreach ($category_map[$request['category']] as $keyword) {
                if (strpos($search_lower, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        // Проверяем совпадение по статусу через маппинг
        if (isset($status_map[$request['status']])) {
            foreach ($status_map[$request['status']] as $keyword) {
                if (strpos($search_lower, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    });
}

$page_title = "Все заявки";
?>
<?php include 'includes/employee_header.php'; ?>

<div class="uk-page-container">
    <!-- Заголовок -->
    <div class="uk-page-header">
        <div>
            <h1><i class="fas fa-clipboard-list"></i> Все заявки</h1>
            <p class="uk-page-subtitle">Полный список заявок в системе</p>
        </div>
    </div>

    <!-- Панель фильтрации -->
    <div class="uk-filter-panel">
        <form method="GET" action="" class="uk-filter-form">
            <div class="uk-search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Поиск по описанию, типу или жителю..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="uk-filter-controls">
                <select class="uk-filter-select" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все статусы</option>
                    <option value="новая" <?php echo $status_filter === 'новая' ? 'selected' : ''; ?>>Новые</option>
                    <option value="в работе" <?php echo $status_filter === 'в работе' ? 'selected' : ''; ?>>В работе</option>
                    <option value="выполнена" <?php echo $status_filter === 'выполнена' ? 'selected' : ''; ?>>Выполнено</option>
                    <option value="отклонена" <?php echo $status_filter === 'отклонена' ? 'selected' : ''; ?>>Отклонено</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Список заявок -->
    <div class="uk-list-container">
        <?php if (empty($requests)): ?>
            <div class="uk-empty-state">
                <i class="fas fa-clipboard"></i>
                <h2>Заявки не найдены</h2>
                <p>Нет заявок, соответствующих критериям поиска</p>
            </div>
        <?php else: ?>
            <div class="uk-requests-list">
                <?php foreach ($requests as $request): ?>
                    <div class="uk-request-item" data-id="<?php echo $request['id']; ?>">
                        <div class="uk-request-header">
                            <div>
                                <span class="uk-request-id">Заявка №<?php echo $request['id']; ?></span>
                                <span class="uk-request-category <?php echo htmlspecialchars($request['category']); ?>">
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
                            <span class="uk-request-status <?php echo htmlspecialchars($request['status']); ?>">
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
                        
                        <p class="uk-request-description"><?php echo htmlspecialchars($request['description']); ?></p>
                        
                        <div class="uk-request-details">
                            <p><strong>Житель:</strong> <?php echo htmlspecialchars($request['full_name']); ?></p>
                            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                            <p><strong>Адрес:</strong> <?php echo htmlspecialchars($request['address']); ?></p>
                            <?php if (!empty($request['apartment_number'])): ?>
                                <p><strong>Квартира:</strong> <?php echo htmlspecialchars($request['apartment_number']); ?></p>
                            <?php endif; ?>
                            <p><strong>Создана:</strong> <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></p>
                            <?php if ($request['updated_at'] && $request['updated_at'] !== $request['created_at']): ?>
                                <p><strong>Обновлена:</strong> <?php echo date('d.m.Y H:i', strtotime($request['updated_at'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($request['status'] === 'в работе' && !empty($request['assigned_to'])): ?>
                                <?php 
                                $assigned_text = ($request['assigned_to'] == Session::get('user_id')) ? 
                                    'Назначена вам' : 'Назначена другому сотруднику';
                                ?>
                                <p><strong>Назначена:</strong> <?php echo $assigned_text; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="uk-request-actions">
                            <?php if ($request['status'] === 'новая'): ?>
                                <button class="uk-btn uk-btn-outline uk-btn-small start-request" 
                                        data-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-play"></i> Взять в работу
                                </button>
                            <?php elseif ($request['status'] === 'в работе' && $request['assigned_to'] == Session::get('user_id')): ?>
                                <button class="uk-btn uk-btn-outline uk-btn-small complete-request" 
                                        data-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-check"></i> Выполнить
                                </button>
                                <button class="uk-btn uk-btn-outline uk-btn-small reject-request" 
                                        data-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-times"></i> Отклонить
                                </button>
                            <?php elseif ($request['status'] === 'в работе'): ?>
                                <span class="uk-request-assigned">
                                    <i class="fas fa-user-tie"></i> В работе у другого сотрудника
                                </span>
                            <?php endif; ?>
                            
                            <button class="uk-btn uk-btn-secondary uk-btn-small delete-request" 
                                    data-id="<?php echo $request['id']; ?>">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Employee requests загружен, инициализация...');
    
    // Функция для создания обработчика кнопок с индикатором загрузки
    function createButtonHandler(button, actionFunction, confirmMessage = null) {
        return async function() {
            const requestId = this.getAttribute('data-id');
            const originalHTML = this.innerHTML;
            const originalClasses = this.className;
            
            // Подтверждение действия
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            // Показываем индикатор загрузки
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
            this.className = originalClasses + ' btn-disabled';
            this.disabled = true;
            
            try {
                const result = await actionFunction(requestId);
                
                if (result.success) {
                    // Успех - перезагружаем страницу
                    window.location.reload();
                } else {
                    // Ошибка - восстанавливаем кнопку и показываем сообщение
                    this.innerHTML = originalHTML;
                    this.className = originalClasses;
                    this.disabled = false;
                    
                    alert('Ошибка: ' + (result.message || 'Неизвестная ошибка сервера'));
                }
            } catch (error) {
                console.error('Ошибка:', error);
                
                // Восстанавливаем кнопку
                this.innerHTML = originalHTML;
                this.className = originalClasses;
                this.disabled = false;
                
                alert('Произошла ошибка: ' + error.message);
            }
        };
    }
    
    // Взять заявку в работу
    document.querySelectorAll('.start-request').forEach(btn => {
        btn.addEventListener('click', createButtonHandler(
            btn,
            async (requestId) => await updateRequestStatus(requestId, 'в работе'),
            'Вы хотите взять эту заявку в работу?'
        ));
    });
    
    // Завершить заявку
    document.querySelectorAll('.complete-request').forEach(btn => {
        btn.addEventListener('click', createButtonHandler(
            btn,
            async (requestId) => await updateRequestStatus(requestId, 'выполнена'),
            'Вы хотите отметить заявку как выполненную?'
        ));
    });
    
    // Отклонить заявку (если есть кнопки отклонения)
    document.querySelectorAll('.reject-request').forEach(btn => {
        if (btn) {
            btn.addEventListener('click', createButtonHandler(
                btn,
                async (requestId) => await updateRequestStatus(requestId, 'отклонена'),
                'Вы хотите отклонить эту заявку?'
            ));
        }
    });
    
    // Удалить заявку
    document.querySelectorAll('.delete-request').forEach(btn => {
        btn.addEventListener('click', createButtonHandler(
            btn,
            async (requestId) => await deleteRequest(requestId),
            'Вы уверены, что хотите удалить эту заявку?'
        ));
    });
    
    // РЕАЛИЗАЦИЯ ПОИСКА ПО ТИПУ (ОТОПЛЕНИЕ, ВОДОПРОВОД, ЭЛЕКТРИЧЕСТВО)
    const searchInput = document.querySelector('.uk-search-box input[name="search"]');
    if (searchInput) {
        // Карта поиска по категориям
        const searchMap = {
            'водопровод': ['водопровод', 'водоснабжение', 'вода', 'кран', 'труба', 'смеситель', 'протечка', 'утечка'],
            'отопление': ['отопление', 'батарея', 'радиатор', 'тепло', 'холодно', 'отопительный', 'отопит', 'температура'],
            'электроснабжение': ['электроснабжение', 'электричество', 'свет', 'розетка', 'выключатель', 'проводка', 'электро', 'лампа', 'пробки'],
            'другое': ['другое', 'иное', 'прочее', 'остальное']
        };
        
        // Карта поиска по статусам
        const statusMap = {
            'новая': ['новая', 'новый', 'создана'],
            'в работе': ['в работе', 'работа', 'выполняется', 'обработке'],
            'выполнена': ['выполнена', 'выполнено', 'завершена', 'завершено'],
            'отклонена': ['отклонена', 'отклонено', 'отказ']
        };
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const requestItems = document.querySelectorAll('.uk-request-item');
            let hasVisibleItems = false;
            
            requestItems.forEach(item => {
                // Получаем все данные карточки
                const description = item.querySelector('.uk-request-description')?.textContent.toLowerCase() || '';
                const categoryElement = item.querySelector('.uk-request-category');
                const category = categoryElement ? categoryElement.textContent.toLowerCase() : '';
                const categoryClass = categoryElement ? categoryElement.className : '';
                const userName = item.querySelector('.uk-request-details p:nth-child(1)')?.textContent.toLowerCase() || '';
                const phone = item.querySelector('.uk-request-details p:nth-child(2)')?.textContent.toLowerCase() || '';
                const address = item.querySelector('.uk-request-details p:nth-child(3)')?.textContent.toLowerCase() || '';
                const apartmentElement = item.querySelector('.uk-request-details p:nth-child(4)');
                const apartment = apartmentElement ? apartmentElement.textContent.toLowerCase() : '';
                const statusElement = item.querySelector('.uk-request-status');
                const status = statusElement ? statusElement.textContent.toLowerCase() : '';
                const statusClass = statusElement ? statusElement.className : '';
                
                // Проверяем прямое совпадение в текстовых полях
                let directMatch = description.includes(searchTerm) || 
                                 userName.includes(searchTerm) || 
                                 phone.includes(searchTerm) || 
                                 address.includes(searchTerm) || 
                                 apartment.includes(searchTerm);
                
                // Проверяем по категории (название и класс)
                let categoryMatch = false;
                if (searchTerm !== '') {
                    // Проверяем прямые названия категорий
                    if (category.includes(searchTerm)) {
                        categoryMatch = true;
                    }
                    
                    // Проверяем по классу (например, класс "водопровод")
                    if (categoryClass.includes(searchTerm)) {
                        categoryMatch = true;
                    }
                    
                    // Проверяем по карте поиска
                    Object.keys(searchMap).forEach(catKey => {
                        if (searchMap[catKey].some(keyword => searchTerm.includes(keyword))) {
                            // Проверяем, содержит ли карточка эту категорию
                            if (category.includes(catKey) || categoryClass.includes(catKey)) {
                                categoryMatch = true;
                            }
                        }
                    });
                }
                
                // Проверяем по статусу
                let statusMatch = false;
                if (searchTerm !== '') {
                    // Проверяем прямые названия статусов
                    if (status.includes(searchTerm)) {
                        statusMatch = true;
                    }
                    
                    // Проверяем по классу статуса
                    if (statusClass.includes(searchTerm)) {
                        statusMatch = true;
                    }
                    
                    // Проверяем по карте статусов
                    Object.keys(statusMap).forEach(statusKey => {
                        if (statusMap[statusKey].some(keyword => searchTerm.includes(keyword))) {
                            // Проверяем, содержит ли карточка этот статус
                            if (status.includes(statusKey) || statusClass.includes(statusKey)) {
                                statusMatch = true;
                            }
                        }
                    });
                }
                
                // Проверяем по номеру заявки
                const requestIdElement = item.querySelector('.uk-request-id');
                let idMatch = false;
                if (requestIdElement && searchTerm !== '') {
                    const requestId = requestIdElement.textContent.toLowerCase();
                    if (requestId.includes(searchTerm) || requestId.includes('заявка №' + searchTerm)) {
                        idMatch = true;
                    }
                }
                
                // Показываем элемент, если есть любое совпадение или поиск пустой
                const shouldShow = directMatch || categoryMatch || statusMatch || idMatch || searchTerm === '';
                item.style.display = shouldShow ? 'block' : 'none';
                
                if (shouldShow) {
                    hasVisibleItems = true;
                }
            });
            
            // Показываем сообщение, если нет результатов
            const emptyState = document.querySelector('.uk-empty-state');
            const listContainer = document.querySelector('.uk-list-container');
            
            if (!hasVisibleItems && searchTerm !== '') {
                if (!emptyState) {
                    const emptyHTML = `
                        <div class="uk-empty-state">
                            <i class="fas fa-search"></i>
                            <h2>Заявки не найдены</h2>
                            <p>Нет заявок, соответствующих запросу: "${searchTerm}"</p>
                            <p class="uk-search-tips">
                                <strong>Подсказка:</strong> Ищите по словам: вода, отопление, электричество, 
                                новая, в работе, выполненная
                            </p>
                            <button class="uk-btn uk-btn-secondary" onclick="document.querySelector('.uk-search-box input').value=''; document.querySelector('.uk-search-box input').dispatchEvent(new Event('input'));">
                                <i class="fas fa-times"></i> Очистить поиск
                            </button>
                        </div>
                    `;
                    
                    if (listContainer) {
                        const requestsList = listContainer.querySelector('.uk-requests-list');
                        if (requestsList) {
                            listContainer.insertAdjacentHTML('beforeend', emptyHTML);
                        }
                    }
                }
            } else if (emptyState && searchTerm === '') {
                emptyState.remove();
            }
        });
        
        // Добавляем кнопку очистки поиска
        const searchBox = document.querySelector('.uk-search-box');
        if (searchBox) {
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'uk-search-clear';
            clearButton.innerHTML = '<i class="fas fa-times"></i>';
            clearButton.style.cssText = `
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                display: none;
                padding: 5px;
            `;
            
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                this.style.display = 'none';
            });
            
            searchBox.appendChild(clearButton);
            
            // Показываем кнопку очистки при вводе текста
            searchInput.addEventListener('input', function() {
                clearButton.style.display = this.value ? 'block' : 'none';
            });
        }
    }
    
    // УПРОЩЕННЫЕ API ФУНКЦИИ
    async function updateRequestStatus(requestId, status) {
        try {
            const response = await fetch('api/requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_status',
                    request_id: requestId,
                    status: status
                })
            });
            
            const text = await response.text();
            
            try {
                return JSON.parse(text);
            } catch {
                console.warn('Сервер вернул не JSON:', text.substring(0, 200));
                return { 
                    success: false, 
                    message: 'Ошибка сервера: неверный формат ответа' 
                };
            }
        } catch (error) {
            console.error('Ошибка обновления статуса:', error);
            return { 
                success: false, 
                message: 'Ошибка сети: ' + error.message 
            };
        }
    }
    
    async function deleteRequest(requestId) {
        try {
            const response = await fetch('api/requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    request_id: requestId
                })
            });
            
            const text = await response.text();
            
            try {
                return JSON.parse(text);
            } catch {
                console.warn('Сервер вернул не JSON:', text.substring(0, 200));
                return { 
                    success: false, 
                    message: 'Ошибка сервера: неверный формат ответа' 
                };
            }
        } catch (error) {
            console.error('Ошибка удаления:', error);
            return { 
                success: false, 
                message: 'Ошибка сети: ' + error.message 
            };
        }
    }
    
    // УПРОЩЕННАЯ ФУНКЦИЯ ДЛЯ ЗАГРУЗКИ УВЕДОМЛЕНИЙ
    async function loadEmployeeNotifications() {
        try {
            console.log('Загрузка уведомлений для сотрудника...');
            
            const response = await fetch('api/notifications.php?limit=5&unread=true');
            
            console.log('Employee Notifications API response status:', response.status);
            
            // Получаем текст ответа
            const text = await response.text();
            
            // Пытаемся распарсить JSON
            try {
                const data = JSON.parse(text);
                
                if (data.success && data.notifications && data.notifications.length > 0) {
                    console.log('Получены уведомления:', data.notifications.length, 'шт.');
                    
                    // Обновляем счетчик уведомлений
                    const notificationCount = document.querySelector('.uk-notif-count');
                    if (notificationCount && data.unread_count > 0) {
                        notificationCount.textContent = data.unread_count;
                        notificationCount.style.display = 'inline-block';
                    }
                } else {
                    console.log('Нет уведомлений или ошибка API');
                }
            } catch (jsonError) {
                console.error('Ошибка парсинга JSON:', jsonError);
                console.warn('Ответ сервера:', text.substring(0, 500));
                
                // Тестовые уведомления для отладки
                showTestNotifications();
            }
        } catch (error) {
            console.error('Error loading employee notifications:', error);
            showTestNotifications();
        }
    }
    
    // Функция для показа тестовых уведомлений
    function showTestNotifications() {
        console.log('Показ тестовых уведомлений');
        
        const notificationCount = document.querySelector('.uk-notif-count');
        if (notificationCount) {
            notificationCount.textContent = '2';
            notificationCount.style.display = 'inline-block';
        }
    }
    
    // Тестовая функция для проверки API
    window.testEmployeeApi = async function() {
        console.log('Тестирование API для сотрудника...');
        
        try {
            // Тест уведомлений
            console.log('Тест 1: Проверка уведомлений...');
            const notifResponse = await fetch('api/notifications.php?limit=1');
            const notifText = await notifResponse.text();
            console.log('Уведомления статус:', notifResponse.status);
            console.log('Уведомления текст (первые 300 символов):', notifText.substring(0, 300));
            
            // Тест заявок
            console.log('Тест 2: Проверка заявок...');
            const reqResponse = await fetch('api/requests.php?status=all&limit=1');
            const reqText = await reqResponse.text();
            console.log('Заявки статус:', reqResponse.status);
            console.log('Заявки текст (первые 300 символов):', reqText.substring(0, 300));
            
            alert('Тестирование завершено. Проверьте консоль для результатов.');
            
        } catch (error) {
            console.error('Ошибка тестирования:', error);
            alert('Ошибка тестирования: ' + error.message);
        }
    };
    
    // Загружаем уведомления для сотрудника
    loadEmployeeNotifications();
    
    console.log('Employee requests инициализирован. Для теста вызовите testEmployeeApi() в консоли');
});
</script>

<?php include 'includes/footer.php'; ?>