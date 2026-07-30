<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Санитизация входных данных
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $phone = preg_replace('/[^\d+]/', '', $_POST['phone'] ?? '');
    $service_type = htmlspecialchars(trim($_POST['form_type'] ?? 'Заявка с сайта'), ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $area = floatval($_POST['area'] ?? 0);
    $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // Валидация
    if (empty($name) || empty($phone)) {
        throw new Exception('Имя и телефон обязательны для заполнения');
    }
    
    // Проверка существующего клиента
    $client = $db->fetchOne(
        "SELECT id FROM clients WHERE phone = ?",
        [$phone]
    );
    
    if ($client) {
        // Обновляем существующего клиента
        $client_id = $client['id'];
        $db->execute(
            "UPDATE clients SET name = ?, updated_at = NOW() WHERE id = ?",
            [$name, $client_id]
        );
    } else {
        // Создаем нового клиента
        $client_id = $db->insert(
            "INSERT INTO clients (name, phone, address, source) VALUES (?, ?, ?, 'website')",
            [$name, $phone, $address]
        );
    }
    
    // Генерируем номер заказа
    $order_number = 'CL' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Создаем заявку
    $order_id = $db->insert(
        "INSERT INTO orders (client_id, order_number, service_type, area, address, notes, status) 
         VALUES (?, ?, ?, ?, ?, ?, 'new')",
        [$client_id, $order_number, $service_type, $area, $address, $message]
    );
    
    // Отправляем уведомление в Telegram
    sendTelegramNotification($order_number, $name, $phone, $service_type);
    
    // Отправляем уведомление на email
    sendEmailNotification($order_number, $name, $phone, $service_type);
    
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно создана! Номер вашего заказа: ' . $order_number,
        'order_number' => $order_number,
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function sendTelegramNotification($order_number, $name, $phone, $service) {
    if (!defined('TELEGRAM_BOT_TOKEN') || !TELEGRAM_BOT_TOKEN) return;
    
    $message = "🔔 <b>Новая заявка!</b>\n";
    $message .= "📋 Номер: {$order_number}\n";
    $message .= "👤 Клиент: {$name}\n";
    $message .= "📞 Телефон: {$phone}\n";
    $message .= "🔧 Услуга: {$service}\n";
    $message .= "🕒 " . date('d.m.Y H:i');
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function sendEmailNotification($order_number, $name, $phone, $service) {
    $to = COMPANY_EMAIL;
    $subject = "Новая заявка №{$order_number}";
    $message = "Поступила новая заявка:\n\n";
    $message .= "Номер: {$order_number}\n";
    $message .= "Клиент: {$name}\n";
    $message .= "Телефон: {$phone}\n";
    $message .= "Услуга: {$service}\n";
    
    mail($to, $subject, $message);
}