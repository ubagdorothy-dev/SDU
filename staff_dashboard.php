<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard (JS Toggle)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

    /* Import Montserrat Font */
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');
    body { 
        font-family: 'Montserrat', sans-serif;
        display: flex; 
        background-color: #f0f2f5; 
    }

    /* Base Layout & Transitions */
    .main-content { 
        flex-grow: 1; 
        padding: 2rem; 
        transition: margin-left 0.3s ease-in-out; 
    }
    .sidebar-lg { 
        transition: width 0.3s ease-in-out; 
        display: flex;
        flex-direction: column;
    }

    /* --- DESKTOP SIDEBAR (Default Open State) --- */
    @media (min-width: 992px) {
        .sidebar-lg {
            width: 250px;
            background-color: #1a237e; /* Dark Indigo */
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
        }
        .main-content {
            margin-left: 250px;
        }
    }

    /* --- JS TOGGLE CLASSES --- */
    /* 1. Collapsed Sidebar State */
    .sidebar-lg.collapsed-sidebar {
        width: 80px; 
        padding-top: 1rem;
    }

    /* 2. Hide long text elements when collapsed */
    .sidebar-lg.collapsed-sidebar .nav-link span,
    .sidebar-lg.collapsed-sidebar .logo-text,
    .sidebar-lg.collapsed-sidebar h5 { 
        display: none;
    }
    
    /* 3. Shifted Main Content */
    .main-content.shifted-main {
        margin-left: 80px; 
    }

    /* 4. Center icon and fix padding in collapsed state */
    .sidebar-lg.collapsed-sidebar .nav-link {
        text-align: center; 
        padding: 12px 0;
    }
    .sidebar-lg.collapsed-sidebar .d-flex.justify-content-between {
        padding-left: 0.25rem !important;
        padding-right: 0.25rem !important;
        margin-bottom: 1rem !important; 
    }
    /* --- END JS TOGGLE CLASSES --- */

    /* --- General Styling --- */
    .sidebar-logo { height: 30px; width: auto; margin-right: 8px; }
    .sidebar-lg .d-flex h5 { font-weight: 700; margin-right: 0 !important; }
    .sidebar-lg .nav-link { 
        color: #ffffff !important; 
        padding: 12px 20px;
        border-radius: 5px;
        margin: 5px 15px;
        transition: background-color 0.2s;
        white-space: nowrap;
        overflow: hidden;
    }
    .sidebar-lg .nav-link:hover, 
    .sidebar-lg .nav-link.active {
        background-color: #3f51b5; 
        color: #ffffff !important;
    }
    .sidebar-lg .btn-toggle {
        background-color: transparent;
        border: none;
        color: #ffffff;
        padding: 6px 10px;
        cursor: pointer; 
    }
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        padding: 2rem 1.5rem;
        text-align: center;
        border: none;
        position: relative;
    }
    .card:nth-child(1) { --card-color: #10b981; }
    .content-box {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        padding: 2rem;
    }
    /* Responsive Styles */
    @media (max-width: 991.98px) {
        .main-content { margin-left: 0 !important; }
    }
</style>
</head>
<body id="body">
    <div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header text-white">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SDU Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-book-open me-2"></i> <span>Training Records</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i> <span>Profile</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
            </ul>
        </div>
    </div>

    <div class="sidebar-lg d-none d-lg-block" id="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <div class="d-flex align-items-center">
                <img src="SDU_Logo.png" alt="SDU Logo" class="sidebar-logo">
                <h5 class="m-0 text-white">
                    <span class="logo-text">SDU STAFF</span>
                </h5>
            </div>
            <button id="sidebar-toggle-btn" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        
        <ul class="nav flex-column flex-grow-1">
            <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-book-open me-2"></i> <span>Training Records</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i> <span>Profile</span></a></li>
            <li class="nav-item mt-auto"><a class="nav-link" href="#"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="main-content" id="main-content">
        <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <i class="fas fa-bars"></i> Menu
        </button>

        <div class="header mb-4">
             <h1 class="fw-bold mb-2" style="color: #1e293b;">Welcome, Jane Doe!</h1>
        </div>
        
        <div class="stats-cards">
            <div class="card">
                <h3>Trainings Completed</h3>
                <p>12</p>
            </div>
            <div class="card">
                <h3>Upcoming Trainings</h3>
                <p>3</p>
            </div>
        </div>

        <div class="content-box">
            <h2>Recent Training Activity</h2>
            <p>Table content goes here...</p>
        </div>
    </div>
    
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p>Profile details...</p></div></div></div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Get references to the elements using their new IDs
        const toggleButton = document.getElementById('sidebar-toggle-btn'); 
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        // Ensure the toggle button exists before trying to attach a listener
        if (toggleButton && sidebar && mainContent) {
            // Add a click listener to the button
            toggleButton.addEventListener('click', () => {
                // Toggle the CSS class on the sidebar
                sidebar.classList.toggle('collapsed-sidebar');
                
                // Toggle the CSS class on the main content area
                mainContent.classList.toggle('shifted-main');
            });
        }
    });
</script>
</body>
</html>