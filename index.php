<?php require_once __DIR__ . '/header.php'; ?>
<section class="grid grid-2">
  <div class="card">
    <h3>Find Available</h3>
    <div class="row">
      <div>
        <label>Date</label>
        <div class="row" style="align-items:center">
          <input type="date" id="date">
          <div class="cal-icon" id="pickToday" style="max-width:180px;justify-content:center"><span>📅</span> Today</div>
        </div>
      </div>
      <div><label>Court</label><select id="court"><option value="">All Courts</option></select></div>
    </div>
    <hr class="sep">
    <div id="slots" style="display:grid;gap:8px"></div>
  </div>

  <div class="card">
       <ul style="margin:0 0 8px 18px; line-height:1.6">
        <li><strong>Entrance Fee:</strong> Whole Day <strong>₱100</strong></li>
        <li><strong>Morning (AM):</strong> <strong>₱50</strong> per player</li>
        <li><strong>Afternoon/Evening (PM):</strong> <strong>₱50</strong> per player</li>
        <li><strong>After 6:00 PM (dark):</strong> Court lights <strong>₱50/hour</strong>, shared by all players</li>
        <li><strong>No reservation/booking fee.</strong></li>
        <li style="color:#ffe27a"><strong>Beware of cancellations.</strong></li>
      </ul>
    <h3>Status</h3>
    <div class="mini" id="status">Auto-refreshing every <strong>10s</strong>. Tap a green slot to book.</div>
    <div id="lastUpdate" class="mini" style="opacity:.8;margin-top:6px"></div>
  </div>
</section>

<script>
const $ = s=>document.querySelector(s);
const today = new Date().toISOString().slice(0,10);
$('#date').value = today;
$('#pickToday').onclick = () => { $('#date').value = today; };

async function loadCourts(){
  const r=await fetch('api_admin.php?action=api_admin_courts_list'); const j=await r.json();
  if(j.ok) $('#court').innerHTML=['<option value="">All Courts</option>'].concat(j.data.map(c=>`<option value="${c.id}">${c.name} ${c.court_code?`(#${c.court_code})`:''}</option>`)).join('');
}

async function renderSlots(){
  const date=$('#date').value; const court=$('#court').value;
  const r=await fetch(`api_public.php?action=api_slots&date=${date}${court?`&court_id=${court}`:''}`); const j=await r.json();
  const box=$('#slots'); box.innerHTML='';
  if(!j.ok){ box.textContent=j.error||'Failed to load.'; return; }
  if(j.data.length===0){ box.innerHTML='<div class="mini">No availability.</div>'; return; }
  j.data.forEach(s=>{
    const st=new Date(s.start_time), en=new Date(s.end_time);
    const div=document.createElement('div'); div.className='slot';
    const label = s.status==='confirmed' ? `reserved${s.court_code?` (#${s.court_code})`:''}` : s.status;
    const badge=`<span class='chip ${s.status==='available'?'ok':s.status}'>${label}</span>`;
    const btn=s.status==='available'?`<button class='primary' data-slot='${JSON.stringify(s)}'>Book</button>`:'';
    div.innerHTML=`<div><div><strong>${s.court_name} ${s.court_code?`<span class='mini'>#${s.court_code}</span>`:''}</strong></div><div class='mini'>${st.toLocaleTimeString()} – ${en.toLocaleTimeString()}</div></div><div style='display:flex;gap:8px;align-items:center'>${badge}${btn}</div>`;
    if(btn) div.querySelector('button').onclick=()=>openBooking(JSON.parse(div.querySelector('button').dataset.slot));
    box.appendChild(div);
  });
  $('#lastUpdate').textContent = 'Last update: '+new Date().toLocaleTimeString();
}

function openBooking(slot){
  Swal.fire({
    title: 'Book this slot',
    backdrop: `rgba(0,0,0,.45)`,
    html: `
      <div style="text-align:left">
        <div class="mini" style="margin-bottom:6px"><strong>${slot.court_name} ${slot.court_code?`<span class='mini'>#${slot.court_code}</span>`:''}</strong> • ${new Date(slot.start_time).toLocaleString()} – ${new Date(slot.end_time).toLocaleString()}</div>
        <label>Name</label><input id="bf_name" class="swal2-input" placeholder="Your name">
        <label>Contact (mobile for SMS)</label><input id="bf_contact" class="swal2-input" placeholder="09xx...">
        <div style="display:flex;gap:8px">
          <input type="number" id="bf_players" class="swal2-input" min="1" value="2" placeholder="Players">
      
        </div>
        <label>Players (names & numbers)</label>
        <textarea id="bf_list" class="swal2-textarea" placeholder="1) Name — #\n2) Name — #"></textarea>
      </div>
    `,
    showCancelButton:true,
    confirmButtonText:'Submit Booking',
    cancelButtonText:'Cancel',
    customClass:{confirmButton:'swal2-confirm neon', cancelButton:'swal2-cancel'},
    didOpen:()=>{ document.querySelector('.swal2-confirm').style.boxShadow='0 0 18px rgba(139,61,255,.6)'; },
    preConfirm: async () => {
      const name = document.getElementById('bf_name').value.trim();
      const contact = document.getElementById('bf_contact').value.trim();
      const players = document.getElementById('bf_players').value;
      const duration = document.getElementById('bf_duration').value;
      const list = document.getElementById('bf_list').value;
      if(!name || !contact){ Swal.showValidationMessage('Name and contact are required'); return false; }
      const payload = new URLSearchParams({ slot_id:slot.id, customer_name:name, contact, player_count:players, duration_hours:duration, players_text:list });
      const r = await fetch('api_public.php?action=api_create_booking', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:payload});
      const j = await r.json();
      if(!j.ok){ Swal.showValidationMessage(j.error || 'Booking failed'); return false; }
      return j.data.booking_id;
    }
  }).then(res=>{ if(res.isConfirmed){ Swal.fire({icon:'success', title:'Booking submitted!', text:'Ref #'+res.value}); renderSlots(); } });
}

$('#date').addEventListener('change', renderSlots);
$('#court').addEventListener('change', renderSlots);
loadCourts().then(renderSlots);
setInterval(renderSlots, 10000);
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
