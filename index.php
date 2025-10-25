<?php require_once __DIR__ . '/header.php'; ?>
<style>
  /* Booking Modal Styles */
.booking-modal-container .swal2-popup {
  border-radius: 12px;
  overflow: hidden;
}

.booking-popup {
  padding: 0;
}

.booking-header {
  padding: 20px 24px 0;
}

.booking-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.booking-html-container {
  padding: 0;
  margin: 0;
}

/* Court Header */
.court-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 24px;
  margin-bottom: 0;
}

.court-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.court-title h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.court-code {
  background: rgba(255, 255, 255, 0.2);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.time-slot {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
  opacity: 0.9;
}

.time-icon {
  font-size: 0.8rem;
}

/* Booking Form */
.booking-form {
  padding: 24px;
}

.form-section {
  margin-bottom: 24px;
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.form-row {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.dual-inputs {
  gap: 16px;
}

.dual-inputs .form-group {
  flex: 1;
}

.form-group {
  flex: 1;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
}

.label-icon {
  font-size: 0.8rem;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
  min-height: 80px;
  box-sizing: border-box;
  transition: all 0.2s ease;
}

.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.helper-text {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 4px;
}

/* Buttons */
.booking-confirm-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  padding: 10px 24px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.booking-confirm-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.booking-cancel-btn {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 10px 24px;
  color: #374151;
  transition: all 0.2s ease;
}

.booking-cancel-btn:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}

/* Responsive */
@media (max-width: 520px) {
  .form-row.dual-inputs {
    flex-direction: column;
    gap: 12px;
  }
  
  .swal2-container {
    padding: 0 12px;
  }
}
  </style>
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
    
      <div class="booking-modal">
        <!-- Court Info Header -->
        <div class="court-header">
          <div class="court-title">
            <h3>${slot.court_name}</h3>
            ${slot.court_code ? `<span class="court-code">#${slot.court_code}</span>` : ''}
          </div>
          <div class="time-slot">
            <i class="time-icon">⏱</i>
            <span>${new Date(slot.start_time).toLocaleString()} – ${new Date(slot.end_time).toLocaleString()}</span>
          </div>
        </div>

        <!-- Booking Form -->
        <div class="booking-form">
          <!-- Personal Information -->
          <div class="form-section">
            <div class="form-row">
              <div class="form-group">
                <label for="bf_name" class="form-label">
                  <i class="label-icon">👤</i>
                  Full Name
                </label>
                <input type="text" id="bf_name" class="form-input" placeholder="Enter your full name">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="bf_contact" class="form-label">
                  <i class="label-icon">📱</i>
                  Mobile Number
                </label>
                <input type="tel" id="bf_contact" class="form-input" placeholder="09xx-xxx-xxxx">
                <small class="helper-text">For SMS confirmation</small>
              </div>
            </div>
          </div>

          <!-- Session Details -->
          <div class="form-section">
            <h4 class="section-title">Session Details</h4>
            <div class="form-row dual-inputs">
              <div class="form-group">
                <label for="bf_players" class="form-label">
                  <i class="label-icon">👥</i>
                  Number of Players
                </label>
                <input type="number" id="bf_players" class="form-input" min="1" max="10" value="2">
              </div>
              <div class="form-group">
                <label for="bf_duration" class="form-label">
                  <i class="label-icon">⏰</i>
                  Duration (Hours)
                </label>
                <input type="number" id="bf_duration" class="form-input" min="0.5" max="4" step="0.5" value="1">
              </div>
            </div>
          </div>

          <!-- Players List -->
          <div class="form-section">
            <div class="form-group">
              <label for="bf_list" class="form-label">
                <i class="label-icon">📝</i>
                Players Information
              </label>
              <textarea id="bf_list" class="form-textarea" rows="4" 
                        placeholder="Enter player details (one per line):&#10;1) Player Name &#10;2) Player Name "></textarea>
              <small class="helper-text">Format: Name — (one per line)</small>
            </div>
          </div>
        </div>
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
