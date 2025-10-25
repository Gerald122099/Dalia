<?php
require_once __DIR__ . '/bootstrap.php';
$action = getv('action','');

if ($action==='api_slots'){
  $date = getv('date'); $court_id = getv('court_id');
  if (!$date) json_err('date required');
  $params = [$date.' 00:00:00', $date.' 23:59:59'];
  $sql = 'SELECT s.*, c.name court_name, c.court_code FROM slots s JOIN courts c ON c.id=s.court_id WHERE s.start_time BETWEEN ? AND ? AND s.status IN ("available","pending","confirmed")';
  if ($court_id){ $sql.=' AND s.court_id=?'; $params[]=$court_id; }
  $sql.=' ORDER BY s.start_time';
  $st = db()->prepare($sql); $st->execute($params); json_ok($st->fetchAll());
}

if ($action==='api_create_booking'){
  $slot_id=(int)postv('slot_id'); $name=postv('customer_name'); $contact=postv('contact');
  $players=(int)postv('player_count'); $dur=(float)postv('duration_hours'); $list=postv('players_text');
  if (!$slot_id||!$name||!$contact||$players<=0||$dur<=0) json_err('Missing/invalid fields');
  $pdo=db(); $pdo->beginTransaction();
  $s=$pdo->prepare('SELECT s.*, c.court_code FROM slots s JOIN courts c ON c.id=s.court_id WHERE s.id=? FOR UPDATE'); $s->execute([$slot_id]); $slot=$s->fetch();
  if(!$slot){ $pdo->rollBack(); json_err('Slot not found',404); }
  if($slot['status']!=='available'){ $pdo->rollBack(); json_err('Slot not available',409); }
  $pdo->prepare('INSERT INTO bookings (court_id,slot_id,start_time,end_time,customer_name,contact,player_count,duration_hours,players_text,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,"pending",NOW(),NOW())')
      ->execute([$slot['court_id'],$slot['id'],$slot['start_time'],$slot['end_time'],$name,$contact,$players,$dur,$list]);
  $bid = $pdo->lastInsertId();
  $pdo->prepare('UPDATE slots SET status="pending", booking_id=? WHERE id=?')->execute([$bid,$slot['id']]);
  $pdo->commit();
  if (defined('ADMIN_PHONE') && ADMIN_PHONE){
    $msg = "NEW BOOKING\nCourt: {$slot['court_id']} (#{$slot['court_code']})\nTime: {$slot['start_time']}\nName: {$name}\nPlayers: {$players}\nRef: #{$bid}";
    send_sms(ADMIN_PHONE, $msg);
  }
  json_ok(['booking_id'=>$bid]);
}

json_err('Unknown action',404);
?>