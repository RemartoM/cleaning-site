<?php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'cleaning_crm');
define('DB_USER', 'root');  // Измените на свои данные
define('DB_PASS', '');      // Измените на свои данные

// Настройки ЮKassa
define('YOOKASSA_SHOP_ID', 'your_shop_id');     // Получите в личном кабинете ЮKassa
define('YOOKASSA_SECRET_KEY', 'your_secret_key'); // Секретный ключ из ЮKassa

// Настройки Telegram для уведомлений
define('TELEGRAM_BOT_TOKEN', 'your_bot_token');
define('TELEGRAM_CHAT_ID', 'your_chat_id');

// Настройки сайта
define('SITE_URL', 'http://localhost:5500'); // Измените на ваш домен
define('COMPANY_NAME', 'Спецтехнолоджи');
define('COMPANY_PHONE', '+7 (999) 123-45-67');
define('COMPANY_EMAIL', 'info@spectechnology.ru');

// Настройки безопасности
define('CSRF_TOKEN_SECRET', 'your-secret-key-here');
define('SESSION_LIFETIME', 3600); // 1 час

// Настройки почты для отправки уведомлений клиентам
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');