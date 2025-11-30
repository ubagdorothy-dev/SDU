<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unit Director - Dashboard</title>
<!-- Bootstrap & Font Awesome Links -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');

body { 
    font-family: 'Montserrat', sans-serif;
    display: flex; 
    background-color: #f0f2f5;
}
.main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }
.sidebar-lg { transition: width 0.3s ease-in-out; }

@media (min-width: 992px) {
    .sidebar-lg { width: 250px; background-color: #1a237e; color: white; height: 100vh; position: fixed; padding-top: 2rem; }
    .main-content { margin-left: 250px; }
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

.stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.card { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 2rem 1.5rem; text-align: center; border: none; transition: all 0.3s ease; position: relative; overflow: hidden; }
.card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--card-color), var(--card-color-light)); }
.card h3 { margin: 0 0 1rem; color: var(--card-color); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.card p { font-size: 2.5rem; font-weight: 900; margin: 0; color: var(--card-color); }
.card:nth-child(1) { --card-color: #6366f1; --card-color-light: #a5b4fc; }
.card:nth-child(2) { --card-color: #10b981; --card-color-light: #6ee7b7; }
.card:nth-child(3) { --card-color: #f59e0b; --card-color-light: #fbbf24; }

.content-box { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.2); }
.content-box h2 { color: #1e293b; border-bottom: 3px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; font-size: 1.5rem; }

th, td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
th { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); color: #475569; font-weight: 700; }
tr:hover td { background-color: #f8fafc; }

.modal-body .form-label, .modal-body .form-control-plaintext { color: #1e293b !important; }
.modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }
.sidebar-logo { height: 30px; width: auto; margin-right: 8px; }

@media (max-width: 991.98px) { .main-content { margin-left: 0 !important; } }
@media (max-width: 768px) { .main-content { padding: 1rem; } .stats-cards { grid-template-columns: 1fr; } }
</style>
</head>
<body id="body">
<input type="checkbox" id="sidebar-toggle-checkbox" style="display: none;">

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header text-white">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SDU Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
            <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span>Pending Approvals</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i> <span>Profile</span></a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar-lg d-none d-lg-block">
    <div class="d-flex justify-content-between align-items-center px-3 mb-3">
        <div class="d-flex align-items-center">
            <img src="SDU_Logo.png" alt="SDU Logo" class="sidebar-logo">
            <h5 class="m-0 text-white"><span class="logo-text">SDU UNIT DIRECTOR</span></h5>
        </div>
        <label for="sidebar-toggle-checkbox" id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></label>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span>Pending Approvals</span></a></li>
        <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
        <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i> <span>Profile</span></a></li>
        <li class="nav-item mt-auto"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content Area -->
<div class="main-content">
    <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
        <i class="fas fa-bars"></i> Menu
    </button>

    <div class="header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="fw-bold mb-2" style="color: #1e293b;">Welcome John Doe! </h1>
                <p class="mb-0" style="color: #6b7280;">Here's what's happening with your organization today.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="fas fa-user-circle me-2"></i> Profile
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#broadcastModal">
                    <i class="fas fa-bullhorn me-2"></i> Send Notification
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="card"><h3>Total Staff</h3><p>128</p></div>
        <div class="card"><h3>Total Heads</h3><p>15</p></div>
        <div class="card"><h3>Trainings Completed</h3><p>85%</p></div>
    </div>

    <!-- Staff Directory Table -->
    <div class="content-box">
        <h2>Staff Directory & Training Reports</h2>
        <table class="table">
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
            <tbody>
                <tr>
                    <td>Alice Johnson</td>
                    <td>Office Head</td>
                    <td><span title="Planning">PLN</span></td>
                    <td>Leadership Workshop</td>
                    <td>2025-09-15</td>
                    <td>Conference Hall A</td>
                    <td>International</td>
                    <td>Global</td>
                    <td><span class="badge bg-success">Completed</span></td>
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
                    <td><span class="badge bg-primary">Ongoing</span></td>
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
                    <td><span class="badge bg-success">Completed</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="broadcastModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="broadcastForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Audience</label>
                            <select class="form-select" name="audience" required>
                                <option value="all">All Users</option>
                                <option value="staff">Staff Only</option>
                                <option value="heads">Office Heads Only</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Subject (optional)</label>
                            <input type="text" class="form-control" name="subject" placeholder="Optional subject line">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="5" placeholder="Share updates, reminders, or announcements" required></textarea>
                    </div>
                    <div id="broadcastFeedback" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p>Profile content goes here.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
