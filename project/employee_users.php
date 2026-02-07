<?php
// employee_users.php - Управление пользователями для сотрудника УК
require_once 'includes/session.php';
require_once 'models/User.php';

Session::start();
Session::requireLogin('login.php');
Session::requireRole('сотрудник_ук', 'login.php');

$userModel = new User();
$current_user_id = Session::get('user_id');

// Обработка удаления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = (int)$_POST['user_id'];
    
    // Проверяем, что не удаляем себя
    if ($user_id_to_delete != $current_user_id) {
        if ($userModel->delete($user_id_to_delete)) {
            $_SESSION['success_message'] = 'Пользователь успешно удален';
        } else {
            $_SESSION['error_message'] = 'Ошибка при удалении пользователя';
        }
    } else {
        $_SESSION['error_message'] = 'Нельзя удалить свой собственный аккаунт';
    }
    
    // Перенаправляем обратно на страницу
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Получаем параметры фильтрации
$search_query = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';

// Получаем всех пользователей
$users = $userModel->getAll();

// Фильтруем по поисковому запросу
if (!empty($search_query)) {
    $search_lower = mb_strtolower($search_query);
    
    $users = array_filter($users, function($user) use ($search_lower) {
        $full_name = mb_strtolower($user['full_name'] ?? '');
        $email = mb_strtolower($user['email'] ?? '');
        $phone = mb_strtolower($user['phone'] ?? '');
        $address = mb_strtolower($user['address'] ?? '');
        $role = mb_strtolower($user['role'] ?? '');
        
        return strpos($full_name, $search_lower) !== false ||
               strpos($email, $search_lower) !== false ||
               strpos($phone, $search_lower) !== false ||
               strpos($address, $search_lower) !== false ||
               strpos($role, $search_lower) !== false;
    });
}

// Фильтруем по роли
if ($role_filter !== 'all') {
    $users = array_filter($users, function($user) use ($role_filter) {
        return $user['role'] === $role_filter;
    });
}

$page_title = "Управление пользователями";
?>
<?php include 'includes/employee_header.php'; ?>

<div class="uk-page-container">
    <!-- Заголовок -->
    <div class="uk-page-header">
        <div>
            <h1><i class="fas fa-users"></i> Управление пользователями</h1>
            <p class="uk-page-subtitle">Все зарегистрированные пользователи системы</p>
        </div>
    </div>

    <!-- Сообщения об успехе/ошибке -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="uk-alert uk-alert-success" style="margin-bottom: 20px; padding: 15px; background-color: #d4edda; color: #155724; border-radius: 6px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="uk-alert uk-alert-error" style="margin-bottom: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 6px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Панель фильтрации -->
    <div class="uk-filter-panel">
        <form method="GET" action="" class="uk-filter-form">
            <div class="uk-search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Поиск по имени, телефону или email..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="uk-filter-controls">
                <select class="uk-filter-select" name="role" onchange="this.form.submit()">
                    <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>Все роли</option>
                    <option value="житель" <?php echo $role_filter === 'житель' ? 'selected' : ''; ?>>Жители</option>
                    <option value="сотрудник_ук" <?php echo $role_filter === 'сотрудник_ук' ? 'selected' : ''; ?>>Сотрудники УК</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Список пользователей -->
    <div class="uk-list-container">
        <?php if (empty($users)): ?>
            <div class="uk-empty-state">
                <i class="fas fa-users"></i>
                <h2>Пользователи не найдены</h2>
                <p>Нет пользователей, соответствующих критериям поиска</p>
            </div>
        <?php else: ?>
            <div class="uk-users-list">
                <?php foreach ($users as $user): ?>
                    <div class="uk-user-item" data-id="<?php echo $user['id']; ?>">
                        <div class="uk-user-header">
                            <div class="uk-user-avatar <?php echo $user['role'] === 'сотрудник_ук' ? 'admin' : ''; ?>">
                                <?php if ($user['role'] === 'сотрудник_ук'): ?>
                                    <i class="fas fa-user-tie"></i>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="uk-user-info">
                                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                                <?php if (!empty($user['address'])): ?>
                                    <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($user['address']); ?></p>
                                <?php endif; ?>
                                <span class="uk-user-role-badge <?php echo $user['role']; ?>">
                                    <?php 
                                    $role_labels = [
                                        'житель' => 'Житель',
                                        'сотрудник_ук' => 'Сотрудник УК'
                                    ];
                                    echo $role_labels[$user['role']] ?? $user['role'];
                                    ?>
                                </span>
                                <p class="uk-user-registered">
                                    <small><i class="fas fa-calendar-alt"></i> Зарегистрирован: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Кнопка удаления (не показываем для текущего пользователя) -->
                        <?php if ($user['id'] != $current_user_id): ?>
                            <div class="uk-user-actions">
                                <form method="POST" action="" class="delete-user-form" onsubmit="return confirmDelete(this);">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" class="uk-btn uk-btn-secondary uk-btn-small delete-user-btn">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Employee users загружен, инициализация...');
    
    // Реализация поиска на стороне клиента
    const searchInput = document.querySelector('.uk-search-box input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const userItems = document.querySelectorAll('.uk-user-item');
            
            userItems.forEach(item => {
                const userName = item.querySelector('h3')?.textContent.toLowerCase() || '';
                const email = item.querySelector('p:nth-child(2)')?.textContent.toLowerCase() || '';
                const phone = item.querySelector('p:nth-child(3)')?.textContent.toLowerCase() || '';
                const address = item.querySelector('p:nth-child(4)')?.textContent.toLowerCase() || '';
                const role = item.querySelector('.uk-user-role-badge')?.textContent.toLowerCase() || '';
                
                const matches = userName.includes(searchTerm) || 
                               email.includes(searchTerm) || 
                               phone.includes(searchTerm) || 
                               address.includes(searchTerm) || 
                               role.includes(searchTerm);
                
                item.style.display = matches || searchTerm === '' ? 'block' : 'none';
            });
        });
    }
    
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
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                this.style.display = 'none';
            }
        });
        
        searchBox.appendChild(clearButton);
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearButton.style.display = this.value ? 'block' : 'none';
            });
        }
    }
});

// Функция подтверждения удаления
function confirmDelete(form) {
    const userName = form.closest('.uk-user-item').querySelector('h3').textContent;
    return confirm('Вы уверены, что хотите удалить пользователя "' + userName + '"?\n\nЭто действие невозможно отменить.');
}

// Обработка удаления с индикатором загрузки
document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('delete-user-form')) {
        const submitBtn = e.target.querySelector('.delete-user-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Удаление...';
            submitBtn.disabled = true;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>