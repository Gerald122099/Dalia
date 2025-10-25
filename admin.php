<?php require_once __DIR__ . '/header.php'; ?>
<section class="grid">
  <!-- Login Card (hidden after login) -->
  <div class="card" id="loginCard">
    <h3>Admin Login</h3>
    <div class="row"><input id="ad_user" placeholder="Username"><input id="ad_pass" type="password" placeholder="Password"></div>
    <div class="row"><button class="primary" id="btnLogin">Login</button></div>
    <div id="who" class="mini" style="margin-top:6px"></div>
  </div>

  <!-- Admin Panels (visible after login) -->
  <div id="adminPanels" style="display:none" class="grid">
    <!-- Bookings FIRST (Top) -->
    <div class="card" style="grid-column:1/-1">
      <h3>Bookings <span class="mini">(auto-refresh 10s)</span></h3>
      <div class="row"><select id="bk_filter"><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option><option value="all">All</option></select></div>
      <div id="bookingsList" class="mini" style="margin-top:8px"></div>
    </div>

    <!-- Courts -->
    <div class="card">
      <h3>Courts</h3>
      <div class="row">
        <input id="court_new" placeholder="Court name">
        <input id="court_code" placeholder="Court code (4-5 digits)">
        <button id="btnCourtAdd" class="primary">Add</button>
      </div>
      <div id="courtsList" class="mini" style="margin-top:8px;opacity:.9"></div>
    </div>

    <!-- Availability -->
    <div class="card">
      <h3>Create Availability</h3>
      <div class="row"><select id="av_court"></select></div>
      <div class="row">
        <div><label>Date From</label><input type="date" id="av_from"></div>
        <div><label>Date To</label><input type="date" id="av_to"></div>
      </div>
      <div class="row">
        <div><label>Start Time</label><div class="row">
          <select id="av_sh"><?php for($i=1;$i<=12;$i++){ echo "<option>$i</option>"; } ?></select>
          <select id="av_sm"><option>00</option><option>15</option><option>30</option><option>45</option></select>
          <select id="av_samp"><option>AM</option><option>PM</option></select>
        </div></div>
        <div><label>End Time</label><div class="row">
          <select id="av_eh"><?php for($i=1;$i<=12;$i++){ echo "<option>$i</option>"; } ?></select>
          <select id="av_em"><option>00</option><option>15</option><option>30</option><option>45</option></select>
          <select id="av_eamp"><option>AM</option><option>PM</option></select>
        </div></div>
      </div>
      <div class="row">
        <button class="primary" id="btnCreateSlots">Create Availability</button>
        <button class="ghost" id="btnLoadDay">View Day Availability</button>
      </div>
      <div id="av_msg" class="mini" style="margin-top:8px"></div>
    </div>
  </div>

  <!-- Bottom bar with Logout (appears only when logged in) -->
  <div id="bottomBar" style="display:none; grid-column:1/-1; margin-top:8px;">
    <div class="card" style="background:transparent;border-style:dashed">
      <div class="row" style="justify-content:flex-end;">
        <button class="ghost" id="btnLogout">Logout</button>
      </div>
    </div>
  </div>
</section>

<script>
const $=s=>document.querySelector(s);
function genCode(){
  const len = Math.random() < 0.5 ? 4 : 5;
  const min = len===4 ? 1000 : 10000;
  const max = len===4 ? 9999 : 99999;
  return String(Math.floor(Math.random()*(max-min+1))+min);
}

async function checkSession(){
  try { const r=await fetch('api_admin.php?action=api_admin_whoami'); const j=await r.json();
    if(j.ok){
      $('#who').textContent='Hello, '+j.data.username;
      // Hide login card, show panels + bottom bar
      $('#loginCard').style.display='none';
      $('#adminPanels').style.display='grid';
      $('#bottomBar').style.display='block';
      refreshCourts(); refreshBookings();
    } else {
      $('#loginCard').style.display='block';
      $('#adminPanels').style.display='none';
      $('#bottomBar').style.display='none';
    }
  } catch(e){
    $('#loginCard').style.display='block';
    $('#adminPanels').style.display='none';
    $('#bottomBar').style.display='none';
  }
}
checkSession();

$('#btnLogin').onclick=async()=>{
  const p=new URLSearchParams({username:$('#ad_user').value,password:$('#ad_pass').value});
  const r=await fetch('api_admin.php?action=api_admin_login',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
  const j=await r.json();
  if(j.ok){
    Swal.fire('Logged in','Welcome, '+j.data.username,'success');
    // Hide login, show panels + bottom bar
    $('#loginCard').style.display='none';
    $('#adminPanels').style.display='grid';
    $('#bottomBar').style.display='block';
    refreshCourts(); refreshBookings();
  } else {
    Swal.fire('Login failed', j.error||'Invalid login', 'error');
  }
};

$('#btnLogout').onclick=async()=>{
  await fetch('api_admin.php?action=api_admin_logout');
  Swal.fire('Logged out','','success');
  // Show login again, hide panels
  $('#loginCard').style.display='block';
  $('#adminPanels').style.display='none';
  $('#bottomBar').style.display='none';
};

async function refreshCourts(){
  const r=await fetch('api_admin.php?action=api_admin_courts_list'); const j=await r.json();
  if(!j.ok){ $('#courtsList').textContent='Login required'; return; }
  $('#av_court').innerHTML=j.data.map(c=>`<option value="${c.id}">${c.name} ${c.court_code?`(#${c.court_code})`:''}</option>`).join('');
  $('#courtsList').innerHTML=j.data.map(c=>`<div class="slot" style="background:transparent"><div>${c.name} <span class=mini>#${c.court_code||''}</span></div><div><button data-del="${c.id}">Delete</button></div></div>`).join('');
  document.querySelectorAll('[data-del]').forEach(btn=>{
    btn.onclick=async()=>{
      const p=new URLSearchParams({id:btn.getAttribute('data-del')});
      const r2=await fetch('api_admin.php?action=api_admin_court_delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
      const j2=await r2.json(); if(j2.ok){ refreshCourts(); Swal.fire('Deleted','','success'); }
    };
  });
}

function to24h(h,m,ap){ h=parseInt(h||'0'); m=parseInt(m||'0'); if(ap==='PM'&&h<12)h+=12; if(ap==='AM'&&h===12)h=0; return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0'); }

document.querySelector('#btnCourtAdd').onclick=async()=>{
  const name=$('#court_new').value.trim(); let code=$('#court_code').value.trim();
  if(!name) return Swal.fire('Enter court name','','info');
  if(!/^\d{4,5}$/.test(code)){ code = genCode(); $('#court_code').value = code; }
  const p=new URLSearchParams({name, court_code: code});
  const r=await fetch('api_admin.php?action=api_admin_court_create',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
  const j=await r.json();
  if(j.ok){ $('#court_new').value=''; $('#court_code').value=''; refreshCourts(); Swal.fire('Added','Court created','success'); }
  else { Swal.fire('Error', j.error || 'Failed', 'error'); }
};

document.querySelector('#btnCreateSlots').onclick=async()=>{
  const court_id=$('#av_court').value, d1=$('#av_from').value, d2=$('#av_to').value;
  const sh=to24h($('#av_sh').value,$('#av_sm').value,$('#av_samp').value);
  const eh=to24h($('#av_eh').value,$('#av_em').value,$('#av_eamp').value);
  const p=new URLSearchParams({court_id, date_from:d1, date_to:d2, start_time_24:sh, end_time_24:eh});
  const r=await fetch('api_admin.php?action=api_admin_create_slots',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
  const j=await r.json(); if(j.ok){ $('#av_msg').textContent=`Created: ${j.data.created}, Skipped: ${j.data.skipped}`; Swal.fire('Availability created',`Created: ${j.data.created} • Skipped: ${j.data.skipped}`,'success'); } else { Swal.fire('Error', j.error||'Failed','error'); }
};

document.querySelector('#btnLoadDay').onclick=async()=>{
  const d=$('#av_from').value || new Date().toISOString().slice(0,10);
  const r=await fetch('api_admin.php?action=api_admin_slots_day&date='+d); const j=await r.json();
  if(!j.ok) return Swal.fire('Error', j.error||'Login required', 'error');
  const html=j.data.map(s=>`
    <div class="slot" style="margin:6px 0">
      <div><strong>${s.court_name} <span class="mini">#${s.court_code||''}</span></strong><div class="mini">${new Date(s.start_time).toLocaleTimeString()} – ${new Date(s.end_time).toLocaleTimeString()}</div></div>
      <div><span class="chip ${s.status}">${s.status==='confirmed'?'reserved':s.status}</span>
      ${s.status!=='available'?`<button data-free="${s.id}" style="margin-left:8px">Mark Available</button>`:''}</div>
    </div>`).join('');
  Swal.fire({title:'Availability on '+d, html:`<div style="text-align:left;max-height:60vh;overflow:auto">${html||'<div class="mini">No availability</div>'}</div>`, width:700});
  document.querySelectorAll('[data-free]').forEach(b=>{
    b.onclick=async()=>{ const p=new URLSearchParams({slot_id:b.getAttribute('data-free')});
      const r2=await fetch('api_admin.php?action=api_admin_mark_available',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
      const j2=await r2.json(); if(j2.ok){ Swal.fire('Marked available','','success'); } else { Swal.fire('Error', j2.error||'Failed','error'); } };
  });
};

async function refreshBookings(){
  const status=$('#bk_filter').value||'pending';
  const r=await fetch('api_admin.php?action=api_admin_bookings&status='+status); const j=await r.json();
  if(!j.ok){ $('#bookingsList').textContent='Login required'; return; }
  if(j.data.length===0){ $('#bookingsList').textContent='No records'; return; }
  $('#bookingsList').innerHTML=j.data.map(b=>`
    <div class="slot" style="margin:6px 0">
      <div><strong>#${b.id} • ${b.court_name} <span class="mini">#${b.court_code||''}</span></strong>
        <div class="mini">${new Date(b.start_time).toLocaleString()} – ${new Date(b.end_time).toLocaleString()}</div>
        <div class="mini">${b.customer_name} • ${b.contact||''}</div>
      </div>
      <div>
        <span class="chip ${b.status}">${b.status}</span>
        ${b.status==='pending'?`<button data-confirm="${b.id}" class="primary" style="margin-left:8px">Confirm</button>`:''}
        ${b.status!=='cancelled'?`<button data-cancel="${b.id}" style="margin-left:8px">Cancel</button>`:''}
      </div>
    </div>`).join('');
  document.querySelectorAll('[data-confirm]').forEach(btn=>{
    btn.onclick=async()=>{ const p=new URLSearchParams({booking_id:btn.getAttribute('data-confirm')});
      const r2=await fetch('api_admin.php?action=api_admin_confirm_booking',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
      const j2=await r2.json(); if(j2.ok){ Swal.fire('Confirmed','User will be notified via SMS (if enabled)','success'); refreshBookings(); } else Swal.fire('Error', j2.error||'Failed','error'); };
  });
  document.querySelectorAll('[data-cancel]').forEach(btn=>{
    btn.onclick=async()=>{ const p=new URLSearchParams({booking_id:btn.getAttribute('data-cancel')});
      const r2=await fetch('api_admin.php?action=api_admin_cancel_booking',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});
      const j2=await r2.json(); if(j2.ok){ Swal.fire('Cancelled','','success'); refreshBookings(); } else Swal.fire('Error', j2.error||'Failed','error'); };
  });
}
setInterval(()=>{ if($('#adminPanels').style.display!=='none') refreshBookings(); }, 10000);
$('#bk_filter').addEventListener('change', refreshBookings);
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
