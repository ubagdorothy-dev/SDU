<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'unit director') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Directory & Training Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');
body { font-family: 'Montserrat', sans-serif; display:flex; background:#f0f2f5; }
.main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }
.sidebar-lg { transition: width 0.3s ease-in-out; }
@media (min-width:992px){
  .sidebar-lg{ width:250px; background:#1a237e; color:#fff; height:100vh; position:fixed; padding-top:2rem; display:flex; flex-direction:column; justify-content:center; }
  .sidebar-lg .d-flex.justify-content-between { padding: 0.75rem 1rem; }
  .main-content{ margin-left:250px; }
}
/* Sidebar collapse toggle */
#sidebar-toggle-checkbox:checked ~ .sidebar-lg { width: 80px; padding-top: 1rem; }
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .nav-link span,
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .logo-text,
#sidebar-toggle-checkbox:checked ~ .sidebar-lg h5 { display: none; }
#sidebar-toggle-checkbox:checked ~ .main-content { margin-left: 80px; }
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .nav-link { text-align: center; padding: 12px 0; }
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .d-flex.justify-content-between { padding-left: 0.25rem !important; padding-right: 0.25rem !important; margin-bottom: 1rem !important; }

.sidebar-lg .d-flex h5 { font-weight: 700; margin-right: 0 !important; }
.sidebar-lg .nav-link { color: #ffffff !important; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; white-space: nowrap; overflow: hidden; }
.sidebar-lg .nav-link:hover, .sidebar-lg .nav-link.active { background-color: #3f51b5; color: #ffffff !important; }
.sidebar-lg .btn-toggle { background-color: transparent; border: none; color: #ffffff; padding: 6px 10px; cursor: pointer; }
.sidebar-lg .btn-toggle:focus { box-shadow: none; }


.content-box{background:rgba(255,255,255,.98);border-radius:12px;padding:2rem;box-shadow:0 8px 32px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.03)}
.content-box h2{font-weight:800;color:#2b3742;margin-bottom:1rem}
.table thead th{background:#f1f5f9;color:#374151;font-weight:700}
.table tbody td{vertical-align:middle}
.badge-complete{background:#16a34a;color:#fff}
.badge-ongoing{background:#2563eb;color:#fff}

.modal-body .form-label, .modal-body .form-control-plaintext { color: #1e293b !important; }
.modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }
.sidebar-logo { height: 30px; width: auto; margin-right: 8px; }

@media (max-width:991.98px){ .main-content{ margin-left:0!important } }
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
    <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span> Pending Approvals</span></a></li>
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
          <option value="ACCA">Ateneo Center for Culture & the Arts (ACCA)</option>
          <option value="ACES">Ateneo Center for Environment & Sustainability (ACES)</option>
          <option value="SDU">SDU</option>
        </select>
      </div>
      <div class="me-2">
        <label class="form-label small text-muted mb-1">Role</label>
        <select id="filter-role" class="form-select form-select-sm">
          <option value="all">All</option>
          <option value="Staff">Staff</option>
          <option value="Head">Head</option>
        </select>
      </div>
      <div class="me-2">
        <label class="form-label small text-muted mb-1">Period</label>
        <select id="filter-period" class="form-select form-select-sm">
          <option value="all">All</option>
          <option value="2025">2025</option>
          <option value="2024">2024</option>
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
      <table class="table align-middle" id="reportsTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Office</th>
            <th>Training</th>
            <th>Completion Date</th>
            <th>Venue</th>
            <th>Nature</th>
            <th>Scope</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="recordsBody">
          <tr>
            <td>Alice Johnson</td>
            <td>Office Head</td>
            <td><span title="Planning">PLN</span></td>
            <td>Leadership Workshop</td>
            <td>2025-09-15</td>
            <td>Conference Hall A</td>
            <td>International</td>
            <td>Global</td>
            <td><span class="badge badge-complete">Completed</span></td>
          </tr>
          <tr>
            <td>Bob Smith</td>
            <td>Staff</td>
            <td><span title="Finance">FIN</span></td>
            <td>Financial Compliance</td>
            <td>2025-08-30</td>
            <td>Training Room 2</td>
            <td>National</td>
            <td>Local</td>
            <td><span class="badge badge-ongoing">Ongoing</span></td>
          </tr>
          <tr>
            <td>Charlie Brown</td>
            <td>Staff</td>
            <td><span title="IT Support">IT</span></td>
            <td>Cybersecurity Basics</td>
            <td>2025-07-20</td>
            <td>Online</td>
            <td>Local</td>
            <td>Regional</td>
            <td><span class="badge badge-complete">Completed</span></td>
          </tr>
        </tbody>
      </table>
    </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// CSV export helper
function tableToCSV(filename) {
  const rows = document.querySelectorAll('#reportsTable tr');
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
  const office = document.getElementById('filter-office').value;
  const role = document.getElementById('filter-role').value;
  const period = document.getElementById('filter-period').value;
  const rows = document.querySelectorAll('#recordsBody tr');
  rows.forEach(r => {
    const cells = r.querySelectorAll('td');
    const rowOffice = cells[5].innerText || '';
    const rowRole = cells[1].innerText || '';
    const dateText = cells[4].innerText || '';
    let show = true;
    if (office !== 'all' && !rowOffice.includes(office)) show = false;
    if (role !== 'all' && rowRole !== role) show = false;
    if (period !== 'all' && !dateText.includes(period)) show = false;
    r.style.display = show ? '' : 'none';
  });
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
</script>
</body>
</html>