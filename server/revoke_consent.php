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
    
    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $phone = preg_replace('/[^\d+]/', '', $_POST['phone'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $reason = htmlspecialchars(trim($_POST['reason'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if (empty($fullname) || empty($phone)) {
        throw new Exception('ФИО и телефон обязательны');
    }
    
    // Находим клиента
    $client = $db->fetchOne(
        "SELECT id FROM clients WHERE phone = ? AND name LIKE ?",
        [$phone, "%{$fullname}%"]
    );
    
    if ($client) {
        // Отмечаем заявки на удаление
        $db->execute(
            "UPDATE orders SET status = 'cancelled', notes = CONCAT(notes, '\nОтзыв согласия на обработку ПД: ', ?) 
             WHERE client_id = ? AND status NOT IN ('completed', 'cancelled')",
            [$reason, $client['id']]
        );
        
        // Логируем запрос на удаление
        $db->insert(
            "INSERT INTO order_history (order_id, user_id, action, new_value) 
             SELECT id, NULL, 'revoke_consent', ? FROM orders WHERE client_id = ?",
            [$reason, $client['id']]
        );
        
        // Отправляем уведомление администратору
        $message = "🔴 Запрос на отзыв согласия на обработку ПД!\n";
        $message .= "👤 Клиент: {$fullname}\n";
        $message .= "📞 Телефон: {$phone}\n";
        $message .= "📧 Email: {$email}\n";
        $message .= "📝 Причина: {$reason}\n";
        $message .= "⚠️ Данные должны быть удалены в течение 30 дней!";
        
        // Отправка в Telegram
        if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN) {
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
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Запрос на отзыв согласия принят. Ваши данные будут удалены в течение 30 дней согласно законодательству РФ.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}