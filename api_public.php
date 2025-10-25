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
  $slot_id=(int)postv('slot_id'); 
  $name=postv('customer_name'); 
  $contact=postv('contact');
  $players=(int)postv('player_count'); 
  $list=postv('players_text');

  if(!$slot_id || !$name || !$contact || $players<=0){
    json_err('Missing/invalid fields', 400);
  }

  $pdo=db(); 
  $pdo->beginTransaction();

  // Lock the slot and get court code for SMS
  $s=$pdo->prepare('SELECT s.*, c.court_code FROM slots s JOIN courts c ON c.id=s.court_id WHERE s.id=? FOR UPDATE'); 
  $s->execute([$slot_id]); 
  $slot=$s->fetch();

  if(!$slot){ $pdo->rollBack(); json_err('Slot not found',404); }
  if($slot['status']!=='available'){ $pdo->rollBack(); json_err('Slot not available',409); }

  // Auto-compute duration from slot times (hours)
  $dur = (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 3600.0;
  if($dur<=0){ $dur = 1; } // safety net

  // Create booking
  $ins = $pdo->prepare('INSERT INTO bookings (court_id,slot_id,start_time,end_time,customer_name,contact,player_count,duration_hours,players_text,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,"pending",NOW(),NOW())');
  $ins->execute([$slot['court_id'],$slot['id'],$slot['start_time'],$slot['end_time'],$name,$contact,$players,$dur,$list]);
  $bid = $pdo->lastInsertId();

  // Mark slot pending & link booking
  $pdo->prepare('UPDATE slots SET status="pending", booking_id=? WHERE id=?')->execute([$bid,$slot['id']]);
  $pdo->commit();

  // ---- SMS to Admin with pretty Date/Time ----
  if (defined('ADMIN_PHONE') && ADMIN_PHONE){
    $st = new DateTime($slot['start_time']);
    $en = new DateTime($slot['end_time']);

    $dateTxt  = $st->format('F j, Y (l)');        // e.g., October 8, 2025 (Sunday)
    $startTxt = strtoupper($st->format('g:ia'));  // e.g., 4:00PM
    $endTxt   = strtoupper($en->format('g:ia'));  // e.g., 5:00PM
    // Remove ":00" when on-the-hour -> 4PM-5PM
    $startTxt = preg_replace('/:00(?=AM|PM)/', '', $startTxt);
    $endTxt   = preg_replace('/:00(?=AM|PM)/',   '', $endTxt);
    $timeTxt  = $startTxt . '-' . $endTxt;

    $msg = "NEW BOOKING\nDate: {$dateTxt}\nTime: {$timeTxt}\nName: {$name}\nPlayers: {$players}\nCourt: {$slot['court_id']} (#{$slot['court_code']})\nRef: #{$bid}";
    send_sms(ADMIN_PHONE, $msg);
  }

  json_ok(['booking_id'=>$bid]);
}


json_err('Unknown action',404);
?>