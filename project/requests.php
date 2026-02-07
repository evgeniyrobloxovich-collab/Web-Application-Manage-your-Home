<?php
// requests.php - Страница заявок для жителя
require_once 'includes/session.php';
require_once 'models/Request.php';

Session::start();
Session::requireLogin('login.php');
Session::requireRole('житель', 'login.php');

$user_id = Session::get('user_id');
$requestModel = new Request();

// Получаем статус фильтра и поисковый запрос
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Получаем заявки пользователя
$requests = $requestModel->getByUserId($user_id, $status_filter);

// Фильтруем по поисковому запросу
if (!empty($search_query)) {
    $search_lower = mb_strtolower($search_query);
    
    // Маппинг категорий для поиска
    $category_map = [
        'водопровод' => ['водопровод', 'водоснабжение', 'вода', 'кран', 'труба', 'смеситель', 'протечка'],
        'отопление' => ['отопление', 'батарея', 'радиатор', 'тепло', 'холодно', 'отопительный'],
        'электроснабжение' => ['электроснабжение', 'электричество', 'свет', 'розетка', 'выключатель', 'проводка', 'электро'],
        'другое' => ['другое', 'иное', 'прочее']
    ];
    
    // Маппинг статусов для поиска
    $status_map = [
        'новая' => ['новая', 'новый'],
        'в работе' => ['в работе', 'работа', 'выполняется'],
        'выполнена' => ['выполнена', 'выполнено', 'завершена'],
        'отклонена' => ['отклонена', 'отклонено']
    ];
    
    $requests = array_filter($requests, function($request) use ($search_lower, $category_map, $status_map) {
        $description = mb_strtolower($request['description'] ?? '');
        $category = mb_strtolower($request['category'] ?? '');
        $status = mb_strtolower($request['status'] ?? '');
        $apartment = mb_strtolower($request['apartment_number'] ?? '');
        
        // Проверяем прямое совпадение
        if (strpos($description, $search_lower) !== false ||
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

// Получаем статистику
$stats = $requestModel->getStats($user_id);

$page_title = "Мои заявки";
?>
<?php include 'includes/profile_header.php'; ?>

<div class="requests-page-container">
    <!-- Заголовок и кнопка создания -->
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> Мои заявки</h1>
        <p class="page-subtitle">Все ваши обращения</p>
        <button class="btn btn-primary" id="createRequestPageBtn">
            <i class="fas fa-plus"></i> Создать заявку
        </button>
    </div>

    <!-- Панель поиска и фильтрации -->
    <div class="filter-panel">
        <form method="GET" action="" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Поиск по описанию, типу или статусу..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="filter-controls">
                <select class="status-filter" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Все статусы</option>
                    <option value="новая" <?php echo $status_filter === 'новая' ? 'selected' : ''; ?>>Новые</option>
                    <option value="в работе" <?php echo $status_filter === 'в работе' ? 'selected' : ''; ?>>В работе</option>
                    <option value="выполнена" <?php echo $status_filter === 'выполнена' ? 'selected' : ''; ?>>Выполнено</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Статистика -->
    <div class="stats-section" style="margin-bottom: 2rem;">
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

    <!-- Список заявок -->
    <div class="requests-list-container">
        <?php if (empty($requests)): ?>
            <div class="requests-empty-state">
                <i class="fas fa-clipboard"></i>
                <h2>Заявки не найдены</h2>
                <p><?php echo empty($search_query) ? 'У вас пока нет созданных заявок' : 'Нет заявок, соответствующих критериям поиска'; ?></p>
                <?php if (empty($search_query)): ?>
                    <button class="btn btn-primary" id="createRequestPageBtn2">
                        <i class="fas fa-plus"></i> Создать заявку
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'">
                        <i class="fas fa-times"></i> Очистить поиск
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="requests-list">
                <?php foreach ($requests as $request): ?>
                    <div class="request-item" data-id="<?php echo $request['id']; ?>">
                        <div class="request-header">
                            <div>
                                <span class="request-id">Заявка №<?php echo $request['id']; ?></span>
                                <span class="request-category <?php echo htmlspecialchars($request['category']); ?>">
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
                            <span class="request-status <?php echo htmlspecialchars($request['status']); ?>">
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
                        
                        <p class="request-description"><?php echo htmlspecialchars($request['description']); ?></p>
                        
                        <div class="request-details">
                            <?php if (!empty($request['apartment_number'])): ?>
                                <p><strong>Квартира:</strong> <?php echo htmlspecialchars($request['apartment_number']); ?></p>
                            <?php endif; ?>
                            <p><strong>Создана:</strong> <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></p>
                            <?php if ($request['updated_at'] && $request['updated_at'] !== $request['created_at']): ?>
                                <p><strong>Обновлена:</strong> <?php echo date('d.m.Y H:i', strtotime($request['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($request['status'] === 'новая'): ?>
                            <div class="request-actions">
                                <button class="btn btn-outline btn-small delete-request" 
                                        data-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно создания заявки -->
<div class="modal" id="requestModalPage">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Создать заявку</h2>
            <button class="close-modal" id="closeRequestModalPage">&times;</button>
        </div>
        <div class="modal-body">
            <form class="request-form" id="requestFormPage">
                <div class="form-group">
                    <label for="request-apartment-page">Номер квартиры</label>
                    <div class="input-wrapper">
                        <i class="fas fa-home"></i>
                        <input type="text" id="request-apartment-page" 
                               value="<?php echo Session::get('user_address'); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-name-page">Имя и фамилия</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="request-name-page" 
                               value="<?php echo Session::get('user_name'); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-category-page">Категория проблемы *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-tags"></i>
                        <select id="request-category-page" required>
                            <option value="">Выберите категорию</option>
                            <option value="водопровод">Водопровод</option>
                            <option value="отопление">Отопление</option>
                            <option value="электроснабжение">Электроснабжение</option>
                            <option value="другое">Другое</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="request-description-page">Описание проблемы *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-comment-alt"></i>
                        <textarea id="request-description-page" rows="4" 
                                  placeholder="Опишите конкретную проблему в квартире..." required></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelRequestPage">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="submitRequestPageBtn">
                        <span id="submitPageBtnText">Создать заявку</span>
                        <span id="loadingPageSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Обработка...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Элементы для страницы заявок
    const createRequestPageBtns = document.querySelectorAll('#createRequestPageBtn, #createRequestPageBtn2');
    const requestModalPage = document.getElementById('requestModalPage');
    const closeRequestModalPage = document.getElementById('closeRequestModalPage');
    const cancelRequestPage = document.getElementById('cancelRequestPage');
    const requestFormPage = document.getElementById('requestFormPage');
    const submitRequestPageBtn = document.getElementById('submitRequestPageBtn');
    const submitPageBtnText = document.getElementById('submitPageBtnText');
    const loadingPageSpinner = document.getElementById('loadingPageSpinner');
    
    // Открытие модального окна создания заявки
    createRequestPageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            requestModalPage.style.display = 'flex';
        });
    });
    
    // Закрытие модального окна заявки
    closeRequestModalPage.addEventListener('click', function() {
        requestModalPage.style.display = 'none';
        requestFormPage.reset();
    });
    
    cancelRequestPage.addEventListener('click', function() {
        requestModalPage.style.display = 'none';
        requestFormPage.reset();
    });
    
    // Реализация поиска на стороне клиента
    const searchInput = document.querySelector('.search-box input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const requestItems = document.querySelectorAll('.request-item');
            
            // Маппинг категорий для поиска
            const categoryMap = {
                'водоснабжение': ['водопровод', 'водоснабжение', 'вода', 'кран', 'труба', 'смеситель'],
                'отопление': ['отопление', 'батарея', 'радиатор', 'тепло', 'холодно'],
                'электричество': ['электроснабжение', 'электричество', 'свет', 'розетка', 'выключатель'],
                'другое': ['другое', 'иное', 'прочее']
            };
            
            // Маппинг статусов для поиска
            const statusMap = {
                'новая': ['новая', 'новый'],
                'в работе': ['в работе', 'работа'],
                'выполнена': ['выполнена', 'выполнено', 'завершена']
            };
            
            requestItems.forEach(item => {
                const description = item.querySelector('.request-description').textContent.toLowerCase();
                const category = item.querySelector('.request-category').textContent.toLowerCase();
                const status = item.querySelector('.request-status').textContent.toLowerCase();
                const apartment = item.querySelector('.request-details p:first-child')?.textContent.toLowerCase() || '';
                
                // Прямое совпадение
                const directMatch = description.includes(searchTerm) || 
                                   category.includes(searchTerm) || 
                                   status.includes(searchTerm) || 
                                   apartment.includes(searchTerm);
                
                // Совпадение по категории
                let categoryMatch = false;
                Object.keys(categoryMap).forEach(catKey => {
                    if (categoryMap[catKey].some(keyword => searchTerm.includes(keyword))) {
                        if (category.includes(catKey.toLowerCase())) {
                            categoryMatch = true;
                        }
                    }
                });
                
                // Совпадение по статусу
                let statusMatch = false;
                Object.keys(statusMap).forEach(statusKey => {
                    if (statusMap[statusKey].some(keyword => searchTerm.includes(keyword))) {
                        if (status.includes(statusKey.toLowerCase())) {
                            statusMatch = true;
                        }
                    }
                });
                
                item.style.display = (directMatch || categoryMatch || statusMatch || searchTerm === '') ? 'block' : 'none';
            });
            
            // Показываем/скрываем сообщение о пустом результате
            const visibleItems = Array.from(requestItems).filter(item => 
                item.style.display !== 'none' && item.style.display !== ''
            );
            
            const emptyState = document.querySelector('.requests-empty-state');
            if (visibleItems.length === 0 && searchTerm !== '') {
                if (!emptyState) {
                    const container = document.querySelector('.requests-list-container');
                    const emptyStateHTML = `
                        <div class="requests-empty-state">
                            <i class="fas fa-search"></i>
                            <h2>Заявки не найдены</h2>
                            <p>Нет заявок, соответствующих критериям поиска</p>
                            <button class="btn btn-secondary" onclick="document.querySelector('.search-box input').value=''; document.querySelector('.search-box input').dispatchEvent(new Event('input'));">
                                <i class="fas fa-times"></i> Очистить поиск
                            </button>
                        </div>
                    `;
                    container.innerHTML = emptyStateHTML;
                }
            }
        });
    }
    
    // Обработка формы создания заявки
    requestFormPage.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Показываем индикатор загрузки
        if (submitPageBtnText && loadingPageSpinner) {
            submitPageBtnText.style.display = 'none';
            loadingPageSpinner.style.display = 'inline-block';
            submitRequestPageBtn.disabled = true;
        }
        
        const category = document.getElementById('request-category-page').value;
        const description = document.getElementById('request-description-page').value;
        const apartment = document.getElementById('request-apartment-page').value;
        
        // Извлекаем номер квартиры из адреса (простая логика)
        const apartmentMatch = apartment.match(/кв\.\s*(\d+)/i);
        const apartmentNumber = apartmentMatch ? apartmentMatch[1] : '';
        
        try {
            // Пробуем разные пути API
            const apiPaths = [
                'api/requests.php',
                '../api/requests.php',
                '/api/requests.php',
                './api/requests.php'
            ];
            
            let response;
            let success = false;
            
            // Пробуем все пути пока не получим успешный ответ
            for (const apiPath of apiPaths) {
                try {
                    response = await fetch(apiPath, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'create',
                            category: category,
                            description: description,
                            apartment_number: apartmentNumber
                        })
                    });
                    
                    if (response.ok) {
                        success = true;
                        break;
                    }
                } catch (fetchError) {
                    console.log(`Попытка ${apiPath} не удалась:`, fetchError);
                    continue;
                }
            }
            
            if (!success || !response) {
                throw new Error('Не удалось подключиться к серверу');
            }
            
            const data = await response.json();
            
            // Скрываем индикатор загрузки
            if (submitPageBtnText && loadingPageSpinner) {
                submitPageBtnText.style.display = 'inline-block';
                loadingPageSpinner.style.display = 'none';
                submitRequestPageBtn.disabled = false;
            }
            
            if (data.success) {
                alert('Заявка успешно создана!');
                requestModalPage.style.display = 'none';
                requestFormPage.reset();
                // Перезагружаем страницу для обновления списка
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error:', error);
            
            // Скрываем индикатор загрузки
            if (submitPageBtnText && loadingPageSpinner) {
                submitPageBtnText.style.display = 'inline-block';
                loadingPageSpinner.style.display = 'none';
                submitRequestPageBtn.disabled = false;
            }
            
            alert('Произошла ошибка при создании заявки. Проверьте подключение к серверу и попробуйте еще раз.');
        }
    });
    
    // Удаление заявки
    document.querySelectorAll('.delete-request').forEach(btn => {
        btn.addEventListener('click', async function() {
            const requestId = this.getAttribute('data-id');
            
            if (!confirm('Вы уверены, что хотите удалить эту заявку?')) {
                return;
            }
            
            try {
                // Пробуем разные пути API
                const apiPaths = [
                    'api/requests.php',
                    '../api/requests.php',
                    '/api/requests.php',
                    './api/requests.php'
                ];
                
                let response;
                let success = false;
                
                for (const apiPath of apiPaths) {
                    try {
                        response = await fetch(apiPath, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                request_id: requestId
                            })
                        });
                        
                        if (response.ok) {
                            success = true;
                            break;
                        }
                    } catch (fetchError) {
                        continue;
                    }
                }
                
                if (!success || !response) {
                    throw new Error('Не удалось подключиться к серверу');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Заявка успешно удалена!');
                    // Удаляем элемент со страницы
                    this.closest('.request-item').remove();
                    
                    // Если заявок больше нет, показываем пустое состояние
                    if (document.querySelectorAll('.request-item').length === 0) {
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }
                } else {
                    alert('Ошибка: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Произошла ошибка при удалении заявки');
            }
        });
    });
    
    // Закрытие модального окна при клике на фон
    window.addEventListener('click', function(e) {
        if (e.target === requestModalPage) {
            requestModalPage.style.display = 'none';
            requestFormPage.reset();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>