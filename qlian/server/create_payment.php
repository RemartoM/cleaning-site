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
    
    $order_id = intval($_POST['order_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    
    if (!$order_id || !$amount) {
        throw new Exception('Не указаны обязательные параметры');
    }
    
    // Получаем информацию о заказе
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) {
        throw new Exception('Заказ не найден');
    }
    
    // Создаем платеж в ЮKassa
    $payment_data = createYooKassaPayment($order, $amount);
    
    // Сохраняем информацию о платеже в БД
    $db->insert(
        "INSERT INTO payments (order_id, payment_id, amount, status) VALUES (?, ?, ?, 'pending')",
        [$order_id, $payment_data['id'], $amount]
    );
    
    // Обновляем статус заказа
    $db->execute(
        "UPDATE orders SET payment_status = 'pending', payment_id = ?, amount = ? WHERE id = ?",
        [$payment_data['id'], $amount, $order_id]
    );
    
    echo json_encode([
        'success' => true,
        'payment_url' => $payment_data['confirmation']['confirmation_url'],
        'payment_id' => $payment_data['id']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function createYooKassaPayment($order, $amount) {
    $idempotence_key = uniqid('', true);
    
    $data = [
        'amount' => [
            'value' => number_format($amount, 2, '.', ''),
            'currency' => 'RUB'
        ],
        'capture' => true,
        'confirmation' => [
            'type' => 'redirect',
            'return_url' => SITE_URL . '/payment-success.html?order=' . $order['order_number']
        ],
        'description' => "Оплата заказа №{$order['order_number']} - " . COMPANY_NAME,
        'metadata' => [
            'order_id' => $order['id'],
            'order_number' => $order['order_number']
        ]
    ];
    
    $ch = curl_init('https://api.yookassa.ru/v3/payments');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Idempotence-Key: ' . $idempotence_key
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, YOOKASSA_SHOP_ID . ':' . YOOKASSA_SECRET_KEY);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Ошибка создания платежа: ' . $response);
    }
    
    return json_decode($response, true);
}