<?php
session_start();
require_once '../server/config.php';
require_once '../server/database.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Статистика
$stats = [
    'total_orders' => $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'],
    'new_orders' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'new'")['count'],
    'in_progress' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status IN ('processing', 'in_progress')")['count'],
    'completed_today' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'completed' AND DATE(updated_at) = CURDATE()")['count'],
    'total_revenue' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM orders WHERE payment_status = 'paid'")['total'],
    'pending_payments' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'pending' AND status != 'cancelled'")['count']
];

// Последние заявки
$recent_orders = $db->fetchAll(
    "SELECT o.*, c.name as client_name, c.phone 
     FROM orders o 
     LEFT JOIN clients c ON o.client_id = c.id 
     ORDER BY o.created_at DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Панель управления | Спецтехнолоджи</title>
    <link rel="stylesheet" href="assets/crm-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="crm-layout">
        <!-- Боковое меню -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-spray-can-sparkles"></i>
                <span>CRM Панель</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i> Дашборд
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i> Заявки
                    <?php if ($stats['new_orders'] > 0): ?>
                        <span class="badge"><?php echo $stats['new_orders']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="clients.php" class="nav-item">
                    <i class="fas fa-users"></i> Клиенты
                </a>
                <a href="export.php" class="nav-item">
                    <i class="fas fa-download"></i> Экспорт
                </a>
                <hr>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </nav>
        </aside>
        
        <!-- Основной контент -->
        <main class="main-content">
            <header class="crm-header">
                <h1>Панель управления</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </header>
            
            <!-- Карточки статистики -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #00BCD4;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $stats['total_orders']; ?></span>
                        <span class="stat-label">Всего заявок</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $stats['new_orders']; ?></span>
                        <span class="stat-label">Новых заявок</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $stats['in_progress']; ?></span>
                        <span class="stat-label">В работе</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-ruble-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo number_format($stats['total_revenue'], 0, '', ' '); ?> ₽</span>
                        <span class="stat-label">Выручка</span>
                    </div>
                </div>
            </div>
            
            <!-- Последние заявки -->
            <div class="card">
                <div class="card-header">
                    <h2>Последние заявки</h2>
                    <a href="orders.php" class="btn btn-small">Все заявки</a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th>Услуга</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><a href="orders.php?view=<?php echo $order['id']; ?>"><?php echo $order['order_number']; ?></a></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td><?php echo htmlspecialchars($order['service_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $statuses = [
                                            'new' => 'Новая',
                                            'processing' => 'Обработка',
                                            'confirmed' => 'Подтверждена',
                                            'in_progress' => 'В работе',
                                            'completed' => 'Выполнена',
                                            'cancelled' => 'Отменена'
                                        ];
                                        echo $statuses[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $order['amount'] ? number_format($order['amount'], 0, '', ' ') . ' ₽' : '-'; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>