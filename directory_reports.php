<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'unit director') {
  header("Location: login.php");
  exit();
}

// Connect to DB
$pdo = connect_db();

// Fetch pending approvals count
$pending_sql = "SELECT COUNT(user_id) FROM users WHERE is_approved = 0 AND role IN ('staff', 'head')";
$pending_stmt = $pdo->query($pending_sql);
$pending_approvals = $pending_stmt->fetchColumn();

// Filters from GET
$filter_office = $_GET['office'] ?? 'all';
$filter_role = $_GET['role'] ?? 'all';
$filter_period = $_GET['period'] ?? 'all';

// Build SQL query using updated schema
// staff_details now has: position, program, job_function, degree_attained
// training_records now has: nature, scope

$sqlUsers = "SELECT u.user_id AS id, u.email, u.full_name AS name, u.role, COALESCE(u.office_code, '') AS office, s.position AS position, s.program AS program, s.job_function AS job_function, s.degree_attained AS degree_attained, tr.id AS training_id, tr.title, tr.description AS description, tr.start_date AS start_date, tr.end_date AS end_date, tr.venue, tr.status, tr.nature AS nature, tr.scope AS scope FROM users u LEFT JOIN staff_details s ON s.user_id = u.user_id LEFT JOIN training_records tr ON tr.user_id = u.user_id WHERE u.role IN ('staff','head')";
$params = [];
if ($filter_office !== 'all') {
  $sqlUsers .= " AND u.office_code = :office";
  $params[':office'] = $filter_office;
}
if ($filter_role !== 'all') {
  $role_map = ['Staff' => 'staff', 'Head' => 'head'];
  $r = $role_map[$filter_role] ?? $filter_role;
  $sqlUsers .= " AND u.role = :role";
  $params[':role'] = $r;
}
if ($filter_period !== 'all') {
  $year = intval($filter_period);
  if ($year > 0) {
    $sqlUsers .= " AND YEAR(tr.end_date) = :year";
    $params[':year'] = $year;
  }
}
$sqlUsers .= " ORDER BY u.full_name ASC, tr.end_date DESC";

$stmt = $pdo->prepare($sqlUsers);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a flat list of detail rows (one row per training or a row with empty training info when none)
// Build staff list and trainings map: one row per staff in staffList, map of trainings keyed by user_id
$staffMap = [];
$trainingsByUser = [];
foreach ($rows as $r) {
  $id = isset($r['id']) ? (int)$r['id'] : null;
  if ($id === null) continue;
  if (!isset($staffMap[$id])) {
    // derive a username-like string from email if username column not present
    $derivedUsername = '';
    if (!empty($r['email'])) {
      $derivedUsername = preg_replace('/@.*$/', '', $r['email']);
    }
    $staffMap[$id] = [
      'id' => $id,
      'username' => $derivedUsername,
      'email' => $r['email'] ?? '',
      'name' => $r['name'] ?? '',
      'role' => $r['role'] ?? '',
      'office' => $r['office'] ?? '',
      'program' => $r['program'] ?? '',
      'job_function' => $r['job_function'] ?? '',
      'position' => $r['position'] ?? '',
      'degree_attained' => $r['degree_attained'] ?? ''
    ];
  }
  if (!empty($r['title'])) {
    $trainingsByUser[$id][] = [
      'training_id' => isset($r['training_id']) ? (int)$r['training_id'] : null,
      'title' => $r['title'],
      'description' => $r['description'] ?? '',
      'start_date' => $r['start_date'] ?? null,
      'end_date' => $r['end_date'] ?? null,
      'nature' => $r['nature'] ?? '',
      'scope' => $r['scope'] ?? '',
      'venue' => $r['venue'] ?? '',
      'status' => $r['status'] ?? ''
    ];
  }
}
$staffList = array_values($staffMap);

// Export staffList and trainingsByUser to client as JSON
$staffJson = $staffList;
$trainingsJson = $trainingsByUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Directory & Training Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_sidebar.css">
<style>
    
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');
body { font-family: 'Montserrat', sans-serif; }

.content-box{background:rgba(255,255,255,.98);border-radius:12px;padding:2rem;box-shadow:0 8px 32px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.03)}
.content-box h2{font-weight:800;color:#2b3742;margin-bottom:1rem}
.table thead th{background:#f1f5f9;color:#374151;font-weight:700}
.table tbody td{vertical-align:middle}
.badge-complete{background:#16a34a;color:#fff}
.badge-ongoing{background:#2563eb;color:#fff}

.modal-body .form-label, .modal-body .form-control-plaintext { color: #1e293b !important; }
.modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }

@media (max-width:991.98px){ .main-content{ margin-left:0!important } }
/* Master-detail table styles (lightweight) */
.master-table{ width:100%; border-collapse:collapse; min-width:720px; }
.master-table thead th{ text-align:left; font-size:0.85rem; padding:12px 10px; color:#6b7280; border-bottom:1px solid #eef2f7; font-weight:700; }
.master-table td{ padding:12px 10px; vertical-align:middle; border-bottom:1px solid #f1f5f9; font-size:0.95rem; }
.col-toggle{ width:44px; text-align:center; }
.toggle-indicator{ display:inline-block; width:18px; height:18px; transition:transform 160ms ease; font-size:12px; }
.toggle-indicator.open{ transform:rotate(90deg); }
.detail-row{ background:#fbfdff; }
.detail-cell{ padding:10px 12px; }
.detail-table{ width:100%; border-collapse:collapse; margin-top:6px; }
.detail-table th{ font-size:0.8rem; padding:8px 10px; text-align:left; color:#6b7280; border-bottom:1px solid #eef2f7; font-weight:700; }
.detail-table td{ padding:8px 10px; border-bottom:1px dashed #f1f5f9; font-size:0.9rem; }
.badge{ display:inline-block; padding:4px 8px; font-size:0.78rem; border-radius:999px; color:#fff; font-weight:700; }
.badge.completed{ background:#10b981; }
.badge.ongoing{ background:#1f6feb; }
.badge.cancelled{ background:#ef4444; }
</style>
</head>
<body>
    
<input type="checkbox" id="sidebar-toggle-checkbox" style="display:none;">

<!-- Offcanvas Mobile -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar">
  <div class="offcanvas-header text-white">
    <h5 class="offcanvas-title">SDU Menu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i>Dashboard</a></li>
      <li class="nav-item"><a class="nav-link active" href="directory_reports.php"><i class="fas fa-users me-2"></i>Directory & Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i>Pending Approvals <span class="badge bg-danger"><?php echo $pending_approvals; ?></span></a></li>
      <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar-lg d-none d-lg-block">
  <div class="d-flex justify-content-between align-items-center px-3 mb-3">
    <div class="d-flex align-items-center">
      <img src="SDU_Logo.png" class="sidebar-logo" alt="SDU">
      <h5 class="m-0 text-white">SDU UNIT DIRECTOR</h5>
    </div>
    <label for="sidebar-toggle-checkbox" class="btn btn-toggle" style="color:#fff;border:none;background:transparent"><i class="fas fa-bars"></i></label>
  </div>
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php" ><i class="fas fa-chart-line me-2"></i><span> Dashboard</span></a></li>
    <li class="nav-item"><a class="nav-link active" href="directory_reports.php" ><i class="fas fa-users me-2"></i><span> Directory & Reports</span></a></li>
    <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span> Pending Approvals <span class="badge bg-danger"><?php echo $pending_approvals; ?></span></span></a></li>
    <li class="nav-item mt-auto"><a class="nav-link" href="logout.php" ><i class="fas fa-sign-out-alt me-2"></i><span> Logout</span></a></li>
  </ul>
</div>

<!-- Main -->
<div class="main-content">
  <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"><i class="fas fa-bars"></i> Menu</button>

  <div class="content-box">
    <h2>Staff Directory & Training Reports</h2>
    <hr style="border-color:#e6edf3;margin-top:-6px;margin-bottom:1.5rem">

    <!-- Filters & Actions -->
    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
      <div class="me-2">
        <label class="form-label small text-muted mb-1">Offices</label>
        <select id="filter-office" class="form-select form-select-sm">
          <option value="all">All</option>
          <?php
            // Populate offices from DB
            $offStmt = $pdo->query("SELECT code, name FROM offices ORDER BY name");
            $offices = $offStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($offices as $off) {
                $sel = ($filter_office === $off['code']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($off['code']) . '" ' . $sel . '>' . htmlspecialchars($off['name']) . ' (' . htmlspecialchars($off['code']) . ')</option>';
            }
          ?>
        </select>
      </div>
      <div class="me-2">
        <label class="form-label small text-muted mb-1">Role</label>
        <select id="filter-role" class="form-select form-select-sm">
          <option value="all">All</option>
          <option value="Staff" <?php if($filter_role==='Staff') echo 'selected';?>>Staff</option>
          <option value="Head" <?php if($filter_role==='Head') echo 'selected';?>>Head</option>
        </select>
      </div>
      <div class="me-2">
        <label class="form-label small text-muted mb-1">Period</label>
        <select id="filter-period" class="form-select form-select-sm">
          <option value="all">All</option>
          <?php
            // generate a few recent years
            $currentYear = date('Y');
            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
              $sel = ($filter_period == (string)$y) ? 'selected' : '';
              echo '<option value="' . $y . '" ' . $sel . '>' . $y . '</option>';
            }
          ?>
        </select>
      </div>
      <div class="me-2">
        <label class="d-block small text-muted mb-1">&nbsp;</label>
        <button id="applyFilters" class="btn btn-primary btn-sm">Apply Filters</button>
      </div>

      <div class="ms-auto d-flex gap-2">
        <button id="printBtn" class="btn btn-outline-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        <div class="btn-group">
          <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" id="exportCsv">Export CSV</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table id="staffTable" class="table table-hover">
        <thead>
          <tr>
            <th scope="col">Full Name</th>
            <th scope="col">Role</th>
            <th scope="col">Email</th>
            <th scope="col">Office</th>
            <th scope="col">Position</th>
            <th scope="col">Program</th>
            <th scope="col">Job Function</th>
            <th scope="col">Degree Attained</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody id="staffBody">
          <!-- Server-provided staff list will render here -->
        </tbody>
      </table>
    </div>

    <script>
      // server-provided staff list and trainings map
      const staffList = <?php echo json_encode($staffJson, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
      const trainingsByUser = <?php echo json_encode($trainingsJson, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    </script>
  </div>
</div>

    <!-- Modals -->
    <div class="modal fade" id="profileModal" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">User Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">Profile content</div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div>
    </div>

    <!-- Inbox Modal -->
    <div class="modal fade" id="inboxModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-inbox me-2"></i>Training Requests Inbox</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="inboxList">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

          <!-- Trainings Modal -->
          <div class="modal fade" id="trainingsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Staff Trainings</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <h4 id="trainingsModalTitle">Training Records</h4>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="table-dark">
                        <tr>
                          <th>Training Title</th>
                          <th>Description</th>
                          <th>Dates</th>
                          <th>Venue</th>
                          <th>Nature</th>
                          <th>Scope</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="trainingsModalBody">
                        <!-- populated dynamically -->
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// CSV export helper
function tableToCSV(filename) {
  const rows = document.querySelectorAll('#staffTable tr');
  const csv = [];
  rows.forEach(row => {
    const cols = row.querySelectorAll('th, td');
    const rowData = [];
    cols.forEach(col => {
      let txt = col.innerText.replace(/\n/g,' ').replace(/\s+/g,' ').trim();
      txt = '"' + txt.replace(/"/g,'""') + '"';
      rowData.push(txt);
    });
    csv.push(rowData.join(','));
  });
  const csvStr = csv.join('\n');
  const blob = new Blob([csvStr], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

document.getElementById('exportCsv').addEventListener('click', function(e){
  e.preventDefault();
  tableToCSV('staff-training-reports.csv');
});

document.getElementById('printBtn').addEventListener('click', function(){
  window.print();
});

// Filtering logic (client-side simple)
document.getElementById('applyFilters').addEventListener('click', function(){
  const office = encodeURIComponent(document.getElementById('filter-office').value || 'all');
  const role = encodeURIComponent(document.getElementById('filter-role').value || 'all');
  const period = encodeURIComponent(document.getElementById('filter-period').value || 'all');
  // Redirect to same page with query parameters so results come from SQL
  const qs = `?office=${office}&role=${role}&period=${period}`;
  window.location.href = window.location.pathname.replace(/[^\/]+$/, 'directory_reports.php') + qs;
});
// Inbox handling: fetch count and list, allow approve/reject
async function fetchInboxCount() {
  try {
    const res = await fetch('get_requests_api.php');
    if (!res.ok) throw new Error('Network');
    const data = await res.json();
    const count = Array.isArray(data) ? data.length : 0;
    // update all badge elements if present
    document.querySelectorAll('#inboxCount').forEach(el => el.textContent = count);
  } catch (e) {
    console.error('Inbox count error', e);
  }
}

async function loadInboxList() {
  const listEl = document.getElementById('inboxList');
  listEl.innerHTML = 'Loading...';
  try {
    const res = await fetch('get_requests_api.php');
    if (!res.ok) throw new Error('Network');
    const data = await res.json();
    if (!Array.isArray(data) || data.length === 0) {
      listEl.innerHTML = '<p class="text-muted">No pending requests.</p>';
      fetchInboxCount();
      return;
    }

    const container = document.createElement('div');
    container.className = 'list-group';
    data.forEach(req => {
      const item = document.createElement('div');
      item.className = 'list-group-item d-flex justify-content-between align-items-start';
      item.innerHTML = `
        <div class="ms-2 me-auto">
          <div class="fw-bold">${escapeHtml(req.requester_name)} <small class="text-muted">(${escapeHtml(req.role)} - ${escapeHtml(req.office)})</small></div>
          <div>${escapeHtml(req.training_title)}</div>
          <div class="small text-muted">Requested: ${escapeHtml(req.requested_date)}</div>
        </div>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-success approve-btn" data-id="${req.id}">Approve</button>
          <button class="btn btn-outline-danger reject-btn" data-id="${req.id}">Reject</button>
        </div>
      `;
      container.appendChild(item);
    });
    listEl.innerHTML = '';
    listEl.appendChild(container);

    // attach handlers
    listEl.querySelectorAll('.approve-btn').forEach(b => b.addEventListener('click', onUpdateRequest));
    listEl.querySelectorAll('.reject-btn').forEach(b => b.addEventListener('click', onUpdateRequest));

    fetchInboxCount();
  } catch (e) {
    console.error('Inbox load error', e);
    listEl.innerHTML = '<p class="text-danger">Failed to load requests.</p>';
  }
}

function escapeHtml(str){
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function onUpdateRequest(e){
  const id = this.getAttribute('data-id');
  const status = this.classList.contains('approve-btn') ? 'approved' : 'rejected';
  if (!confirm(`Mark request #${id} as ${status}?`)) return;
  try {
    const res = await fetch('update_request_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id, status: status })
    });
    const json = await res.json();
    if (json.success) {
      loadInboxList();
    } else {
      alert('Update failed: ' + (json.message || 'unknown'));
    }
  } catch (err) {
    console.error(err);
    alert('Request failed');
  }
}

// load count on page load and when modal opens
document.addEventListener('DOMContentLoaded', function(){
  fetchInboxCount();
  var inboxModal = document.getElementById('inboxModal');
  if (inboxModal) inboxModal.addEventListener('show.bs.modal', loadInboxList);
});

/* --- Detail table rendering --- */
function formatDateClient(dStr){ if(!dStr) return '-'; const d=new Date(dStr); if(isNaN(d)) return dStr; return d.toLocaleDateString(); }

function renderStatusBadgeClient(status){ const s=(status||'').toLowerCase(); if(s==='completed') return '<span class="badge completed">Completed</span>'; if(s==='ongoing') return '<span class="badge ongoing">Ongoing</span>'; if(s==='cancelled') return '<span class="badge cancelled">Cancelled</span>'; return '<span class="badge ongoing">'+escapeHtml(status||'Unknown')+'</span>'; }

function escapeHtml(unsafe){ if(unsafe===null||unsafe===undefined) return ''; return String(unsafe).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

function renderStaffTable(){
  const tbody = document.getElementById('staffBody'); if(!tbody) return; tbody.innerHTML = '';
  (staffList || []).forEach(staff => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(staff.name || '')}</td>
      <td>${escapeHtml(staff.role || '')}</td>
      <td>${escapeHtml(staff.email || '')}</td>
      <td>${escapeHtml(staff.office || '')}</td>
      <td>${escapeHtml(staff.position || '')}</td>
      <td>${escapeHtml(staff.program || '')}</td>
      <td>${escapeHtml(staff.job_function || '')}</td>
      <td>${escapeHtml(staff.degree_attained || '')}</td>
      <td><button class="btn btn-sm btn-info btn-view-trainings" data-user-id="${staff.id}" data-user-name="${escapeHtml(staff.name||'')}">View Trainings</button></td>
    `;
    tbody.appendChild(tr);
  });

  // delegate view trainings clicks
  const body = document.getElementById('staffBody');
  body.removeEventListener('click', body._handler);
  body._handler = function(e){
    const btn = e.target.closest('.btn-view-trainings'); if(!btn) return;
    const uid = btn.getAttribute('data-user-id');
    const uname = btn.getAttribute('data-user-name');
    showTrainingsFor(uid, uname);
  };
  body.addEventListener('click', body._handler);
}

document.addEventListener('DOMContentLoaded', function(){ renderStaffTable(); });

/* --- Action handlers --- */
function onViewTraining(trainingId, btn) {
  // find row and show a minimal detail dialog (alert for now)
  const row = btn.closest('tr');
  if (!row) return;
  // columns: 0=name,1=role,2=office,3=title,4=date,5=venue,6=status,7=actions
  const title = row.children[3] ? row.children[3].innerText : '';
  const date = row.children[4] ? row.children[4].innerText : '';
  const venue = row.children[5] ? row.children[5].innerText : '';
  const status = row.children[6] ? row.children[6].innerText : '';
  alert(`Training:
${title}
Date: ${date}
Venue: ${venue}
Status: ${status}`);
}

function onUploadProof(trainingId, btn) {
  if (!trainingId) { alert('No training ID available for upload.'); return; }
  // create a temporary file input
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.pdf,image/*';
  input.addEventListener('change', function(){
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('training_id', trainingId);
    fd.append('proof', file);
    btn.disabled = true;
    fetch('training_api.php?action=upload_proof', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (j.success) {
          alert('Proof uploaded successfully.');
        } else {
          alert('Upload failed: ' + (j.error || 'Unknown'));
        }
      })
      .catch(err => { console.error(err); alert('Upload failed.'); })
      .finally(() => { btn.disabled = false; input.remove(); });
  });
  // trigger click
  input.click();
}

function onMarkCompleted(trainingId, btn) {
  if (!trainingId) { alert('No training ID available.'); return; }
  if (!confirm('Mark this training as completed?')) return;
  btn.disabled = true;
  fetch('update_training_status.php?id=' + encodeURIComponent(trainingId) + '&status=completed', { method: 'GET', credentials: 'same-origin' })
    .then(r => r.json())
    .then(j => {
      if (j.success) {
        // update status cell in the same row
        const row = btn.closest('tr');
        if (row) {
          const statusCell = row.querySelector('.status-cell');
          if (statusCell) statusCell.innerHTML = '<span class="badge completed">Completed</span>';
          // remove the complete button
          const compBtn = row.querySelector('.btn-complete'); if (compBtn) compBtn.remove();
        }
      } else {
        alert('Failed to update status: ' + (j.error || j.message || 'Unknown'));
      }
    })
    .catch(err => { console.error(err); alert('Request failed.'); })
    .finally(() => { btn.disabled = false; });
}

// showTrainingsFor: populate modal from trainingsByUser and show
function showTrainingsFor(userId, userName){
  const modalTitle = document.getElementById('trainingsModalTitle');
  const body = document.getElementById('trainingsModalBody');
  modalTitle.innerHTML = `Training Records for ${escapeHtml(userName)}`;
  body.innerHTML = '';
  const arr = trainingsByUser[userId] || [];
  if (arr.length === 0) {
    body.innerHTML = '<tr><td colspan="8" class="text-muted">No training records found.</td></tr>';
  } else {
    arr.forEach(t => {
      const tr = document.createElement('tr');
      const statusHtml = renderStatusBadgeClient(t.status || '');
      const actions = t.training_id ? `<button class="btn btn-sm btn-outline-primary" onclick="onViewTraining(${t.training_id}, this)">View</button> <button class="btn btn-sm btn-outline-secondary" onclick="onUploadProof(${t.training_id}, this)">Upload Proof</button>` + (( (t.status||'').toLowerCase() !== 'completed') ? ` <button class="btn btn-sm btn-success" onclick="onMarkCompleted(${t.training_id}, this)">Mark Completed</button>` : '') : '<span class="text-muted">No actions</span>';
      const dates = (t.start_date || t.end_date) ? `${t.start_date ? formatDateClient(t.start_date) : ''}${t.start_date && t.end_date ? ' - ' : ''}${t.end_date ? formatDateClient(t.end_date) : ''}` : '-';
      const nature = t.nature || '';
      const scope = t.scope || '';
      tr.innerHTML = `<td>${escapeHtml(t.title||'')}</td><td>${escapeHtml(t.description||'')}</td><td>${escapeHtml(dates)}</td><td>${escapeHtml(t.venue||'')}</td><td>${escapeHtml(nature)}</td><td>${escapeHtml(scope)}</td><td>${statusHtml}</td><td>${actions}</td>`;
      body.appendChild(tr);
    });
  }
  const mdl = new bootstrap.Modal(document.getElementById('trainingsModal'));
  mdl.show();
}
</script>
</body>
</html>