<?php
// employee_houses.php
require_once 'includes/session.php';
require_once 'models/House.php';

Session::start();
Session::requireRole('сотрудник_ук', 'login.php');

$page_title = "Управление домами";

// Инициализируем модель
$houseModel = new House();

// Обработка поиска
$search_term = '';
$houses = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $houses = $houseModel->search($search_term);
} else {
    $houses = $houseModel->getAll();
}

// Обработка удаления дома
if (isset($_POST['delete_house'])) {
    $house_id = $_POST['house_id'];
    if ($houseModel->delete($house_id)) {
        $_SESSION['success_message'] = 'Дом успешно удален';
    } else {
        $_SESSION['error_message'] = 'Ошибка при удалении дома';
    }
    header('Location: employee_houses.php');
    exit();
}

// Обработка добавления/редактирования дома
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_house'])) {
    $data = [
        'address' => trim($_POST['address']),
        'entrances' => intval($_POST['entrances']),
        'apartments' => intval($_POST['apartments']),
        'floors' => intval($_POST['floors']),
        'year_built' => intval($_POST['year_built'])
    ];
    
    // Валидация
    $errors = [];
    if (empty($data['address'])) $errors[] = 'Адрес обязателен';
    if ($data['entrances'] < 1) $errors[] = 'Количество подъездов должно быть больше 0';
    if ($data['apartments'] < 1) $errors[] = 'Количество квартир должно быть больше 0';
    if ($data['floors'] < 1) $errors[] = 'Количество этажей должно быть больше 0';
    if ($data['year_built'] < 1900 || $data['year_built'] > date('Y')) $errors[] = 'Некорректный год постройки';
    
    if (empty($errors)) {
        if (isset($_POST['house_id']) && !empty($_POST['house_id'])) {
            // Редактирование
            $house_id = $_POST['house_id'];
            if ($houseModel->update($house_id, $data)) {
                $_SESSION['success_message'] = 'Дом успешно обновлен';
            } else {
                $_SESSION['error_message'] = 'Ошибка при обновлении дома';
            }
        } else {
            // Добавление
            if ($houseModel->create($data)) {
                $_SESSION['success_message'] = 'Дом успешно добавлен';
            } else {
                $_SESSION['error_message'] = 'Ошибка при добавлении дома';
            }
        }
        header('Location: employee_houses.php');
        exit();
    } else {
        $_SESSION['error_message'] = implode('<br>', $errors);
    }
}

// Получение статистики для отображения
$stats = $houseModel->getStats();

// Получение дома для редактирования (если передан ID)
$edit_house = null;
if (isset($_GET['edit'])) {
    $edit_house = $houseModel->getById($_GET['edit']);
}

include 'includes/employee_header.php';
?>

<div class="uk-page-container">
    <!-- Заголовок и кнопка -->
    <div class="uk-page-header">
        <div>
            <h1><i class="fas fa-building"></i> Управление домами</h1>
            <p class="uk-page-subtitle">Структура объектов недвижимости</p>
            <div class="uk-house-stats">
                <div class="uk-house-stat">
                    <i class="fas fa-home"></i> Всего домов: <?php echo $stats['total_houses'] ?? 0; ?>
                </div>
                <div class="uk-house-stat">
                    <i class="fas fa-door-open"></i> Подъездов: <?php echo $stats['total_entrances'] ?? 0; ?>
                </div>
                <div class="uk-house-stat">
                    <i class="fas fa-layer-group"></i> Квартир: <?php echo $stats['total_apartments'] ?? 0; ?>
                </div>
            </div>
        </div>
        <button class="uk-btn uk-btn-primary" id="ukAddHousePageBtn">
            <i class="fas fa-plus"></i> Добавить дом
        </button>
    </div>

    <!-- Сообщения об успехе/ошибке -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="uk-alert uk-alert-success">
            <i class="fas fa-check-circle"></i>
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="uk-alert uk-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Панель поиска -->
    <div class="uk-filter-panel">
        <form method="GET" action="" class="uk-search-form">
            <div class="uk-search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Поиск по адресу..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="uk-filter-controls">
                <button type="submit" class="uk-btn uk-btn-secondary">
                    <i class="fas fa-search"></i> Найти
                </button>
                <?php if (!empty($search_term)): ?>
                    <a href="employee_houses.php" class="uk-btn uk-btn-secondary">
                        <i class="fas fa-times"></i> Сброс
                    </a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!empty($search_term)): ?>
            <div class="search-stats">
                <i class="fas fa-info-circle"></i> 
                Найдено домов: <?php echo count($houses); ?>
                <?php if (!empty($search_term)): ?>
                    по запросу: "<?php echo htmlspecialchars($search_term); ?>"
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Список домов -->
    <?php if (empty($houses)): ?>
        <div class="uk-list-container" style="text-align: center; padding: 3rem;">
            <i class="fas fa-building" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h3 style="color: #666;">Дома не найдены</h3>
            <p style="color: #999;">Добавьте первый дом или измените критерии поиска</p>
        </div>
    <?php else: ?>
        <div class="uk-houses-grid">
            <?php foreach ($houses as $house): ?>
                <div class="uk-house-card" id="house-<?php echo $house['id']; ?>">
                    <div class="uk-house-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="uk-house-address"><?php echo htmlspecialchars($house['address']); ?></div>
                    <p class="uk-house-year">Построен в <?php echo $house['year_built']; ?> году</p>
                    <div class="uk-house-stats">
                        <div class="uk-house-stat">
                            <i class="fas fa-door-open"></i> <?php echo $house['entrances']; ?> подъездов
                        </div>
                        <div class="uk-house-stat">
                            <i class="fas fa-layer-group"></i> <?php echo $house['floors']; ?> этажей
                        </div>
                        <div class="uk-house-stat">
                            <i class="fas fa-home"></i> <?php echo $house['apartments']; ?> квартир
                        </div>
                    </div>
                    <div class="uk-user-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                        <div style="display: flex; gap: 10px; width: 100%;">
                            <a href="?edit=<?php echo $house['id']; ?>" class="uk-btn uk-btn-secondary" 
                               style="flex: 1; text-align: center; padding: 8px; font-size: 0.9rem;">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
                            <form method="POST" action="" class="delete-house-form" style="flex: 1; margin: 0;">
                                <input type="hidden" name="house_id" value="<?php echo $house['id']; ?>">
                                <button type="submit" name="delete_house" class="delete-house-btn" 
                                        onclick="return confirm('Вы уверены, что хотите удалить этот дом?');"
                                        style="width: 100%; padding: 8px; font-size: 0.9rem; background-color: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно добавления/редактирования дома -->
<div class="uk-modal" id="ukAddHouseModalPage">
    <div class="uk-modal-content">
        <div class="uk-modal-header">
            <h2>
                <i class="fas <?php echo $edit_house ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> 
                <?php echo $edit_house ? 'Редактировать дом' : 'Добавить новый дом'; ?>
            </h2>
            <button class="uk-close-modal" id="ukCloseHouseModalPage">&times;</button>
        </div>
        <div class="uk-modal-body">
            <p class="uk-modal-subtitle">
                <?php echo $edit_house ? 'Внесите изменения в информацию о доме' : 'Заполните информацию о здании'; ?>
            </p>
            
            <form class="uk-modal-form" method="POST" action="" id="ukAddHouseFormPage">
                <?php if ($edit_house): ?>
                    <input type="hidden" name="house_id" value="<?php echo $edit_house['id']; ?>">
                <?php endif; ?>
                
                <div class="uk-form-group">
                    <label for="uk-house-address-page">Адрес *</label>
                    <div class="uk-input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="uk-house-address-page" name="address" 
                               placeholder="ул. Ленина, д. 10" required
                               value="<?php echo $edit_house ? htmlspecialchars($edit_house['address']) : ''; ?>">
                    </div>
                </div>
                
                <div class="uk-form-group">
                    <label for="uk-house-entrances-page">Количество подъездов *</label>
                    <div class="uk-input-wrapper">
                        <i class="fas fa-door-open"></i>
                        <input type="number" id="uk-house-entrances-page" name="entrances" 
                               placeholder="4" min="1" required
                               value="<?php echo $edit_house ? $edit_house['entrances'] : ''; ?>">
                    </div>
                </div>
                
                <div class="uk-form-group">
                    <label for="uk-house-apartments-page">Количество квартир *</label>
                    <div class="uk-input-wrapper">
                        <i class="fas fa-home"></i>
                        <input type="number" id="uk-house-apartments-page" name="apartments" 
                               placeholder="144" min="1" required
                               value="<?php echo $edit_house ? $edit_house['apartments'] : ''; ?>">
                    </div>
                </div>
                
                <div class="uk-form-group">
                    <label for="uk-house-floors-page">Количество этажей *</label>
                    <div class="uk-input-wrapper">
                        <i class="fas fa-layer-group"></i>
                        <input type="number" id="uk-house-floors-page" name="floors" 
                               placeholder="9" min="1" required
                               value="<?php echo $edit_house ? $edit_house['floors'] : ''; ?>">
                    </div>
                </div>
                
                <div class="uk-form-group">
                    <label for="uk-house-year-page">Год постройки *</label>
                    <div class="uk-input-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="number" id="uk-house-year-page" name="year_built" 
                               placeholder="1985" min="1900" max="<?php echo date('Y'); ?>" required
                               value="<?php echo $edit_house ? $edit_house['year_built'] : ''; ?>">
                    </div>
                </div>
                
                <div class="uk-form-actions">
                    <button type="button" class="uk-btn uk-btn-secondary" id="ukCancelHousePage">Отмена</button>
                    <button type="submit" name="save_house" class="uk-btn uk-btn-primary">
                        <?php echo $edit_house ? 'Сохранить изменения' : 'Добавить дом'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('uk-page-title').textContent = 'Управление домами';
    
    const ukAddHousePageBtn = document.getElementById('ukAddHousePageBtn');
    const ukAddHouseModalPage = document.getElementById('ukAddHouseModalPage');
    const ukCloseHouseModalPage = document.getElementById('ukCloseHouseModalPage');
    const ukCancelHousePage = document.getElementById('ukCancelHousePage');
    const ukAddHouseFormPage = document.getElementById('ukAddHouseFormPage');
    
    // Показать модальное окно при клике на кнопку добавления
    if (ukAddHousePageBtn) {
        ukAddHousePageBtn.addEventListener('click', function() {
            ukAddHouseModalPage.style.display = 'flex';
        });
    }
    
    // Показать модальное окно если редактируем дом
    <?php if ($edit_house): ?>
        ukAddHouseModalPage.style.display = 'flex';
    <?php endif; ?>
    
    // Закрытие модального окна
    if (ukCloseHouseModalPage) {
        ukCloseHouseModalPage.addEventListener('click', function() {
            ukAddHouseModalPage.style.display = 'none';
            window.location.href = 'employee_houses.php';
        });
    }
    
    if (ukCancelHousePage) {
        ukCancelHousePage.addEventListener('click', function() {
            ukAddHouseModalPage.style.display = 'none';
            window.location.href = 'employee_houses.php';
        });
    }
    
    // Закрытие при клике вне модального окна
    window.addEventListener('click', function(e) {
        if (e.target === ukAddHouseModalPage) {
            ukAddHouseModalPage.style.display = 'none';
            window.location.href = 'employee_houses.php';
        }
    });
    
    // Валидация формы
    ukAddHouseFormPage.addEventListener('submit', function(e) {
        const address = document.getElementById('uk-house-address-page').value.trim();
        const entrances = document.getElementById('uk-house-entrances-page').value;
        const apartments = document.getElementById('uk-house-apartments-page').value;
        const floors = document.getElementById('uk-house-floors-page').value;
        const year = document.getElementById('uk-house-year-page').value;
        
        if (!address || !entrances || !apartments || !floors || !year) {
            e.preventDefault();
            alert('Пожалуйста, заполните все обязательные поля');
            return false;
        }
        
        if (entrances < 1) {
            e.preventDefault();
            alert('Количество подъездов должно быть больше 0');
            return false;
        }
        
        if (apartments < 1) {
            e.preventDefault();
            alert('Количество квартир должно быть больше 0');
            return false;
        }
        
        if (floors < 1) {
            e.preventDefault();
            alert('Количество этажей должно быть больше 0');
            return false;
        }
        
        const currentYear = new Date().getFullYear();
        if (year < 1900 || year > currentYear) {
            e.preventDefault();
            alert('Год постройки должен быть между 1900 и ' + currentYear);
            return false;
        }
    });
});
</script>