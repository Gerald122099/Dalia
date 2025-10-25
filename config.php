<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'court_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

// === SMS via iProgTech ===
define('SMS_ENABLED', true);
define('SMS_API_URL', 'https://sms.iprogtech.com/api/v1/sms_messages'); // POST JSON supported
define('SMS_API_KEY', 'f9c0a2f1cf06cc6cb76079f75ab7db1ab1563ceb');     // Your API TOKEN
define('SMS_SENDER',  'DINKDALIA'); // not used by this API but kept
define('ADMIN_PHONE', '09167039459'); // Admin mobile for new booking alerts

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
?>