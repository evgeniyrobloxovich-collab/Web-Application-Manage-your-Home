<?php
// employee_dashboard.php
require_once 'includes/session.php';
require_once 'models/House.php';
require_once 'models/User.php';
require_once 'models/Request.php';

Session::start();
Session::requireRole('сотрудник_ук', 'login.php');

$page_title = "Панель администратора";

// Функция для правильного склонения слов
function pluralForm($number, $titles) {
    $cases = array(2, 0, 1, 1, 1, 2);
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

// Инициализируем модели
$houseModel = new House();
$userModel = new User();
$requestModel = new Request();

// Получаем статистику
$houseStats = $houseModel->getStats();
$userStats = $userModel->getUserStats();

// Получаем статистику за последний месяц
$housesLastMonth = $houseModel->getLastMonthStats();
$usersLastMonth = $userModel->getNewUsersLastMonth();
$employeesLastMonth = $userModel->getNewEmployeesLastMonth();

// Получаем данные о заявках
$totalRequests = $requestModel->getTotalCount();
$requestsLastMonth = $requestModel->getCountLastMonth();
$activeRequests = $requestModel->getActiveCount();
$completedRequests = $requestModel->getCompletedCount();

// Получаем сотрудников УК
$employees = $userModel->getUsersByRole('сотрудник_ук');
$employeeCount = count($employees);

// Получаем последние дома
$latestHouses = $houseModel->getLatest(3);
?>

<?php include 'includes/employee_header.php'; ?>

<div class="uk-dashboard-container">
    <!-- Заголовок -->
    <div class="uk-page-header">
        <div>
            <h1><i class="fas fa-tachometer-alt"></i> Панель администратора</h1>
            <p class="uk-page-subtitle">Управление системой и структурой объектов</p>
            <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                <i class="fas fa-calendar-alt"></i> Обновлено: <?php echo date('d.m.Y H:i'); ?>
            </p>
        </div>
    </div>

    <!-- Статистика -->
    <div class="uk-stats-grid">
        <div class="uk-stat-card">
            <div class="uk-stat-header">
                <div>
                    <p class="uk-stat-title">Всего домов</p>
                    <p class="uk-stat-number"><?php echo $houseStats['total_houses'] ?? 0; ?></p>
                    <?php if ($housesLastMonth > 0): ?>
                        <span class="uk-stat-change"><i class="fas fa-arrow-up"></i> +<?php echo $housesLastMonth; ?> за месяц</span>
                    <?php else: ?>
                        <span class="uk-stat-change" style="color: #999;"><i class="fas fa-minus"></i> без изменений</span>
                    <?php endif; ?>
                </div>
                <div class="uk-stat-icon houses">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                <i class="fas fa-home"></i> <?php echo $houseStats['total_apartments'] ?? 0; ?> <?php echo pluralForm($houseStats['total_apartments'] ?? 0, ['квартира', 'квартиры', 'квартир']); ?>
                <span style="margin: 0 8px;">•</span>
                <i class="fas fa-door-open"></i> <?php echo $houseStats['total_entrances'] ?? 0; ?> <?php echo pluralForm($houseStats['total_entrances'] ?? 0, ['подъезд', 'подъезда', 'подъездов']); ?>
            </div>
        </div>
        
        <div class="uk-stat-card">
            <div class="uk-stat-header">
                <div>
                    <p class="uk-stat-title">Пользователей</p>
                    <p class="uk-stat-number"><?php echo $userStats['total_users'] ?? 0; ?></p>
                    <?php if ($usersLastMonth > 0): ?>
                        <span class="uk-stat-change"><i class="fas fa-arrow-up"></i> +<?php echo $usersLastMonth; ?> за месяц</span>
                    <?php else: ?>
                        <span class="uk-stat-change" style="color: #999;"><i class="fas fa-minus"></i> без изменений</span>
                    <?php endif; ?>
                </div>
                <div class="uk-stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                <i class="fas fa-home"></i> <?php echo $userStats['residents'] ?? 0; ?> <?php echo pluralForm($userStats['residents'] ?? 0, ['житель', 'жителя', 'жителей']); ?>
                <span style="margin: 0 8px;">•</span>
                <i class="fas fa-user-tie"></i> <?php echo $userStats['employees'] ?? 0; ?> <?php echo pluralForm($userStats['employees'] ?? 0, ['сотрудник', 'сотрудника', 'сотрудников']); ?>
            </div>
        </div>
        
        <div class="uk-stat-card">
            <div class="uk-stat-header">
                <div>
                    <p class="uk-stat-title">Всего заявок</p>
                    <p class="uk-stat-number"><?php echo $totalRequests; ?></p>
                    <?php if ($requestsLastMonth > 0): ?>
                        <span class="uk-stat-change"><i class="fas fa-arrow-up"></i> +<?php echo $requestsLastMonth; ?> за месяц</span>
                    <?php else: ?>
                        <span class="uk-stat-change" style="color: #999;"><i class="fas fa-minus"></i> без изменений</span>
                    <?php endif; ?>
                </div>
                <div class="uk-stat-icon requests">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                <i class="fas fa-clock" style="color: #ff9800;"></i> Активных: <?php echo $activeRequests; ?>
                <span style="margin: 0 8px;">•</span>
                <i class="fas fa-check-circle" style="color: #4caf50;"></i> Выполнено: <?php echo $completedRequests; ?>
            </div>
        </div>
        
        <div class="uk-stat-card">
            <div class="uk-stat-header">
                <div>
                    <p class="uk-stat-title">Сотрудников УК</p>
                    <p class="uk-stat-number"><?php echo $employeeCount; ?></p>
                    <?php if ($employeesLastMonth > 0): ?>
                        <span class="uk-stat-change"><i class="fas fa-arrow-up"></i> +<?php echo $employeesLastMonth; ?> за месяц</span>
                    <?php else: ?>
                        <span class="uk-stat-change" style="color: #999;"><i class="fas fa-minus"></i> без изменений</span>
                    <?php endif; ?>
                </div>
                <div class="uk-stat-icon employees">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                <i class="fas fa-user-check" style="color: #2196f3;"></i> Активных: <?php echo $employeeCount; ?>
                <span style="margin: 0 8px;">•</span>
                <i class="fas fa-calendar" style="color: #9c27b0;"></i> Зарегистрировано сегодня: 0
            </div>
        </div>
    </div>

    <!-- Основной контент в две колонки -->
    <div class="uk-dashboard-columns">
        <!-- Левая колонка - Быстрые действия -->
        <div class="uk-quick-actions">
            <h2><i class="fas fa-bolt"></i> Быстрые действия</h2>
            <div class="uk-actions-list">
                <a href="employee_houses.php" class="uk-action-btn">
                    <i class="fas fa-plus-circle" style="color: #4caf50;"></i>
                    <div>
                        <strong>Добавить новый дом</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Добавление нового здания в систему</p>
                    </div>
                </a>
                
                <a href="employee_users.php" class="uk-action-btn">
                    <i class="fas fa-user-cog" style="color: #2196f3;"></i>
                    <div>
                        <strong>Управление пользователями</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Просмотр и редактирование пользователей</p>
                    </div>
                </a>
                
                <a href="employee_requests.php" class="uk-action-btn">
                    <i class="fas fa-tasks" style="color: #ff9800;"></i>
                    <div>
                        <strong>Просмотр всех заявок</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Все обращения жильцов</p>
                    </div>
                </a>
                
                <a href="employee_houses.php" class="uk-action-btn">
                    <i class="fas fa-building" style="color: #795548;"></i>
                    <div>
                        <strong>Управление домами</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Редактирование информации о домах</p>
                    </div>
                </a>
                
                <a href="update_employee_profile.php" class="uk-action-btn">
                    <i class="fas fa-user-edit" style="color: #9c27b0;"></i>
                    <div>
                        <strong>Редактировать профиль</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Обновить личные данные</p>
                    </div>
                </a>
                
                <a href="#" class="uk-action-btn" onclick="location.reload(); return false;">
                    <i class="fas fa-sync-alt" style="color: #607d8b;"></i>
                    <div>
                        <strong>Обновить статистику</strong>
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.2rem;">Обновить данные на странице</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Правая колонка - Последние дома -->
        <div class="uk-recent-houses">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-history"></i> Последние дома
                    <span style="background-color: #e8f5e9; color: #2e7d32; padding: 2px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                        <?php echo count($latestHouses); ?>
                    </span>
                </h2>
                <a href="employee_houses.php" style="font-size: 0.9rem; color: #000; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-external-link-alt"></i> Все дома
                </a>
            </div>
            
            <?php if (empty($latestHouses)): ?>
                <div style="text-align: center; padding: 3rem; color: #999; background-color: #f8f9fa; border-radius: 8px;">
                    <i class="fas fa-building" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3 style="color: #666; margin-bottom: 0.5rem;">Дома еще не добавлены</h3>
                    <p style="margin-bottom: 1.5rem;">Добавьте первый дом для начала работы с системой</p>
                    <a href="employee_houses.php" class="uk-btn uk-btn-primary" style="padding: 10px 25px;">
                        <i class="fas fa-plus"></i> Добавить первый дом
                    </a>
                </div>
            <?php else: ?>
                <div class="uk-house-list">
                    <?php foreach ($latestHouses as $house): ?>
                        <div class="uk-house-item" style="border: 1px solid #f0f0f0; border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; background-color: #fff; transition: all 0.3s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem;">
                                <div class="uk-house-address" style="font-size: 1.1rem; font-weight: 600; color: #000;">
                                    <?php echo htmlspecialchars($house['address']); ?>
                                </div>
                                <span style="font-size: 0.8rem; color: #999; background-color: #f5f5f5; padding: 3px 8px; border-radius: 12px;">
                                    ID: <?php echo $house['id']; ?>
                                </span>
                            </div>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                                <i class="fas fa-calendar-alt"></i> Построен в <?php echo $house['year_built']; ?> году
                                <span style="margin-left: 15px; font-size: 0.85rem; color: #999;">
                                    <i class="fas fa-clock"></i> Добавлен: <?php echo date('d.m.Y', strtotime($house['created_at'])); ?>
                                </span>
                            </p>
                            <div class="uk-house-details">
                                <div class="uk-detail-item" style="display: flex; align-items: center; gap: 8px; padding: 8px; background-color: #f8f9fa; border-radius: 6px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background-color: #e3f2fd; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-door-open" style="color: #1976d2;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #000;"><?php echo $house['entrances']; ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo pluralForm($house['entrances'], ['подъезд', 'подъезда', 'подъездов']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="uk-detail-item" style="display: flex; align-items: center; gap: 8px; padding: 8px; background-color: #f8f9fa; border-radius: 6px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background-color: #e8f5e9; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-layer-group" style="color: #2e7d32;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #000;"><?php echo $house['floors']; ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo pluralForm($house['floors'], ['этаж', 'этажа', 'этажей']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="uk-detail-item" style="display: flex; align-items: center; gap: 8px; padding: 8px; background-color: #f8f9fa; border-radius: 6px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background-color: #fff3e0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-home" style="color: #f57c00;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #000;"><?php echo $house['apartments']; ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo pluralForm($house['apartments'], ['квартира', 'квартиры', 'квартир']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 1rem; display: flex; gap: 10px;">
                                <a href="employee_houses.php?edit=<?php echo $house['id']; ?>" 
                                   class="uk-btn uk-btn-secondary" 
                                   style="flex: 1; padding: 8px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                                <a href="employee_houses.php?view=<?php echo $house['id']; ?>" 
                                   class="uk-btn uk-btn-primary" 
                                   style="flex: 1; padding: 8px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <i class="fas fa-eye"></i> Просмотр
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($latestHouses) < count($houseModel->getAll())): ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="employee_houses.php" class="uk-btn uk-btn-secondary" style="width: 100%; padding: 12px; font-size: 0.9rem;">
                                <i class="fas fa-arrow-right"></i> Показать все дома (<?php echo count($houseModel->getAll()) - count($latestHouses); ?> еще)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Дополнительная статистика -->
    <div style="margin-top: 2rem; background-color: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #f0f0f0;">
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-chart-line"></i> Общая статистика системы
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; border-radius: 8px; background-color: #f8f9fa;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Средний год постройки</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #000;"><?php echo round($houseStats['avg_year_built'] ?? 0); ?></div>
            </div>
            <div style="padding: 1rem; border-radius: 8px; background-color: #f8f9fa;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Среднее квартир в доме</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #000;">
                    <?php 
                    if (($houseStats['total_houses'] ?? 0) > 0) {
                        echo round(($houseStats['total_apartments'] ?? 0) / ($houseStats['total_houses'] ?? 1));
                    } else {
                        echo 0;
                    }
                    ?>
                </div>
            </div>
            <div style="padding: 1rem; border-radius: 8px; background-color: #f8f9fa;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Процент выполненных заявок</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #000;">
                    <?php 
                    if ($totalRequests > 0) {
                        echo round(($completedRequests / $totalRequests) * 100);
                    } else {
                        echo 0;
                    }
                    ?>%
                </div>
            </div>
            <div style="padding: 1rem; border-radius: 8px; background-color: #f8f9fa;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Соотношение жителей/сотрудников</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #000;">
                    <?php 
                    if (($userStats['employees'] ?? 0) > 0) {
                        echo round(($userStats['residents'] ?? 0) / ($userStats['employees'] ?? 1));
                    } else {
                        echo ($userStats['residents'] ?? 0);
                    }
                    ?>:1
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обновляем заголовок страницы
    document.getElementById('uk-page-title').textContent = 'Панель администратора';
    
    // Автоматическое обновление статистики каждые 5 минут
    setTimeout(function() {
        location.reload();
    }, 5 * 60 * 1000); // 5 минут
    
    // Анимация при наведении на карточки статистики
    const statCards = document.querySelectorAll('.uk-stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.05)';
        });
    });
    
    // Анимация для кнопок быстрых действий
    const actionBtns = document.querySelectorAll('.uk-action-btn');
    actionBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Анимация для карточек домов
    const houseItems = document.querySelectorAll('.uk-house-item');
    houseItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.08)';
            this.style.borderColor = '#000';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
            this.style.borderColor = '#f0f0f0';
        });
    });
});
</script>

<style>
/* Дополнительные стили для красивого отображения */
.uk-house-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .uk-house-details {
        grid-template-columns: 1fr;
    }
    
    .uk-dashboard-columns {
        grid-template-columns: 1fr;
    }
    
    .uk-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .uk-stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Анимации для загрузки */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.uk-stat-card, .uk-quick-actions, .uk-recent-houses {
    animation: fadeIn 0.5s ease-out;
}

.uk-stat-card:nth-child(1) { animation-delay: 0.1s; }
.uk-stat-card:nth-child(2) { animation-delay: 0.2s; }
.uk-stat-card:nth-child(3) { animation-delay: 0.3s; }
.uk-stat-card:nth-child(4) { animation-delay: 0.4s; }
.uk-quick-actions { animation-delay: 0.5s; }
.uk-recent-houses { animation-delay: 0.6s; }
</style>