<?php
require_once __DIR__ . '/bootstrap.php';
$action = getv('action','');

// Auth endpoints
if ($action==='api_admin_login'){
  $u=postv('username'); $p=postv('password');
  if(!$u||!$p) json_err('Missing credentials');
  $st=db()->prepare('SELECT id,username,password FROM admins WHERE username=? LIMIT 1');
  $st->execute([$u]); $row=$st->fetch();
  if(!$row || $p !== $row['password']) json_err('Invalid login',401);
  $_SESSION['admin_id']=$row['id']; $_SESSION['admin_username']=$row['username'];
  json_ok(['username'=>$row['username']]);
}
if ($action==='api_admin_logout'){ session_destroy(); json_ok(); }
if ($action==='api_admin_whoami'){
  if (!empty($_SESSION['admin_id'])) json_ok(['username'=>$_SESSION['admin_username']]);
  json_err('Unauthorized', 401);
}

// Public read-only: list courts for dropdown
if ($action==='api_admin_courts_list'){
  $r=db()->query('SELECT * FROM courts ORDER BY name')->fetchAll(); json_ok($r);
}

// Everything else requires admin
$open = ['api_admin_login','api_admin_logout','api_admin_whoami','api_admin_courts_list'];
if (!in_array($action,$open,true) && !is_admin()) json_err('Unauthorized',401);

// Courts create/delete
if ($action==='api_admin_court_create'){
  $name=postv('name'); $code=postv('court_code');
  if(!$name) json_err('Name required');
  if(!$code) json_err('Court code required (4-5 digits)');
  if(!preg_match('/^\d{4,5}$/',$code)) json_err('Court code must be 4-5 digits');
  try{
    $st=db()->prepare('INSERT INTO courts(name,court_code) VALUES(?,?)'); $st->execute([$name,$code]);
    json_ok(['id'=>db()->lastInsertId()]);
  }catch(Throwable $e){
    if (strpos($e->getMessage(),'Duplicate')!==false) json_err('Court code already exists');
    json_err('DB error');
  }
}
if ($action==='api_admin_court_delete'){ $id=(int)postv('id'); db()->prepare('DELETE FROM courts WHERE id=?')->execute([$id]); json_ok(); }

// Availability: one slot per day
if ($action==='api_admin_create_slots'){
  $court=(int)postv('court_id'); $from=postv('date_from'); $to=postv('date_to');
  $start24=postv('start_time_24'); $end24=postv('end_time_24');
  if(!$court) json_err('Select a court'); if(!$from||!$to) json_err('Select date range'); if(!$start24||!$end24) json_err('Select start/end time');
  try { $d1=new DateTime($from); $d2=new DateTime($to); } catch(Throwable $e){ json_err('Invalid dates'); }
  if ($d2<$d1) json_err('End date must be >= start date');
  $sh=DateTime::createFromFormat('H:i',$start24); $eh=DateTime::createFromFormat('H:i',$end24);
  if(!$sh || !$eh) json_err('Invalid time format');
  $sh_h=(int)$sh->format('H'); $sh_m=(int)$sh->format('i'); $eh_h=(int)$eh->format('H'); $eh_m=(int)$eh->format('i');
  $pdo=db(); $created=0; $skipped=0;
  for($d=clone $d1; $d<=$d2; $d->modify('+1 day')){
    $st=(clone $d)->setTime($sh_h,$sh_m,0)->format('Y-m-d H:i:s');
    $en=(clone $d)->setTime($eh_h,$eh_m,0)->format('Y-m-d H:i:s');
    if($en <= $st){ $skipped++; continue; }
    $chk=$pdo->prepare('SELECT COUNT(*) n FROM slots WHERE court_id=? AND NOT(end_time <= ? OR start_time >= ?)');
    $chk->execute([$court,$st,$en]); $n=(int)$chk->fetch()['n'];
    if($n>0){ $skipped++; continue; }
    $pdo->prepare('INSERT INTO slots(court_id,start_time,end_time,status) VALUES(?,?,?, "available")')->execute([$court,$st,$en]); $created++;
  }
  json_ok(['created'=>$created,'skipped'=>$skipped]);
}

if ($action==='api_admin_slots_day'){
  $date=getv('date'); if(!$date) json_err('date required');
  $from=$date+' 00:00:00'; $to=$date+' 23:59:59';
  $st=db()->prepare('SELECT s.*, c.name court_name, c.court_code, b.customer_name FROM slots s JOIN courts c ON c.id=s.court_id LEFT JOIN bookings b ON b.id=s.booking_id WHERE s.start_time BETWEEN ? AND ? ORDER BY s.start_time');
  $st->execute([$date.' 00:00:00',$date.' 23:59:59']); json_ok($st->fetchAll());
}

if ($action==='api_admin_mark_available'){
  $slot=(int)postv('slot_id');
  $s=db()->prepare('SELECT booking_id FROM slots WHERE id=?'); $s->execute([$slot]); $r=$s->fetch();
  if($r && $r['booking_id']) db()->prepare('UPDATE bookings SET status="cancelled", updated_at=NOW() WHERE id=?')->execute([$r['booking_id']]);
  db()->prepare('UPDATE slots SET status="available", booking_id=NULL WHERE id=?')->execute([$slot]); json_ok();
}

// Booking lists
if ($action==='api_admin_bookings'){
  $status=getv('status'); 
  $sql='SELECT b.*, c.name court_name, c.court_code FROM bookings b JOIN courts c ON c.id=b.court_id';
  $p=[];
  if($status && $status!=='all'){ $sql.=' WHERE b.status=?'; $p[]=$status; }
  $sql.=' ORDER BY b.created_at DESC LIMIT 500'; $st=db()->prepare($sql); $st->execute($p); json_ok($st->fetchAll());
}
if ($action==='api_admin_confirm_booking'){
  $bid=(int)postv('booking_id'); $pdo=db(); $pdo->beginTransaction();
  $b=$pdo->prepare('SELECT b.*, c.court_code FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.id=? FOR UPDATE'); 
  $b->execute([$bid]); 
  $bk=$b->fetch();

  if(!$bk){ $pdo->rollBack(); json_err('Booking not found',404); }
  if($bk['status']!=='pending'){ $pdo->rollBack(); json_err('Not pending',409); }

  $pdo->prepare('UPDATE bookings SET status="confirmed", updated_at=NOW(), confirmed_at=NOW() WHERE id=?')->execute([$bid]);
  $pdo->prepare('UPDATE slots SET status="confirmed" WHERE id=?')->execute([$bk['slot_id']]);
  $pdo->commit();

  if (!empty($bk['contact']) && preg_match('/^[0-9+][0-9\s\-()]*$/', $bk['contact'])){

    // ----- Fancy date & time formatting -----
    $st = new DateTime($bk['start_time']);
    $en = new DateTime($bk['end_time']);

    $dateTxt  = $st->format('F j, Y (l)');        // e.g., October 8, 2025 (Sunday)
    $startTxt = strtoupper($st->format('g:ia'));  // e.g., 4:00PM
    $endTxt   = strtoupper($en->format('g:ia'));  // e.g., 5:00PM

    // Remove ":00" when on-the-hour -> 4PM-5PM
    $startTxt = preg_replace('/:00(?=AM|PM)/', '', $startTxt);
    $endTxt   = preg_replace('/:00(?=AM|PM)/',   '', $endTxt);

    $timeTxt  = $startTxt . '-' . $endTxt;

    $msg = "BOOKING CONFIRMED\nDate: {$dateTxt}\nTime: {$timeTxt}\nCourt: {$bk['court_id']} (#{$bk['court_code']})\nRef: #{$bk['id']}";

    send_sms($bk['contact'], $msg);
  }

  json_ok();
}


if ($action==='api_admin_cancel_booking'){
  $bid=(int)postv('booking_id'); $pdo=db(); $pdo->beginTransaction();
  $b=$pdo->prepare('SELECT * FROM bookings WHERE id=? FOR UPDATE'); $b->execute([$bid]); $bk=$b->fetch();
  if(!$bk){ $pdo->rollBack(); json_err('Booking not found',404); }
  if($bk['status']==='cancelled'){ $pdo->rollBack(); json_err('Already cancelled',409); }
  $pdo->prepare('UPDATE bookings SET status="cancelled", updated_at=NOW() WHERE id=?')->execute([$bid]);
  $pdo->prepare('UPDATE slots SET status="available", booking_id=NULL WHERE id=?')->execute([$bk['slot_id']]);
  $pdo->commit(); json_ok();
}

json_err('Unknown action',404);
?>