<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function db(){
  static $pdo=null;
  if ($pdo===null){
    $dsn='mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    $pdo=new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}
function json_ok($data=[]){ header('Content-Type: application/json'); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function json_err($msg,$code=400){ http_response_code($code); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$msg]); exit; }
function postv($k,$d=null){ return isset($_POST[$k])?trim($_POST[$k]):$d; }
function getv($k,$d=null){ return isset($_GET[$k])?trim($_GET[$k]):$d; }
function is_admin(){ return !empty($_SESSION['admin_id']); }
function require_admin(){ if (!is_admin()) json_err('Unauthorized',401); }

function normalize_phone_ph($raw){
  $raw = trim((string)$raw);
  $raw = preg_replace('/[\s\-()]/', '', $raw);
  if (strpos($raw, '+63') === 0) $raw = '63'.substr($raw, 3);
  if (strpos($raw, '0') === 0) $raw = '63'.substr($raw, 1);
  return $raw;
}

function send_sms($to, $message){
  if (!SMS_ENABLED) return ['ok'=>false, 'info'=>'SMS disabled'];
  $to = normalize_phone_ph($to);
  if (!$to || !$message) return ['ok'=>false, 'info'=>'Missing number/message'];
  $payload = [
    'api_token'    => SMS_API_KEY,
    'phone_number' => $to,
    'message'      => $message,
  ];
  $url = SMS_API_URL . '?api_token=' . urlencode(SMS_API_KEY) . '&phone_number=' . urlencode($to) . '&message=' . urlencode($message);
  try {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err) return ['ok'=>false,'info'=>'cURL: '.$err];
    return ['ok'=>($code>=200 && $code<300), 'info'=>$resp];
  } catch (Throwable $e){
    return ['ok'=>false,'info'=>$e->getMessage()];
  }
}

date_default_timezone_set('Asia/Manila');
?>