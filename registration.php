<?php
session_start();
require_once 'db.php'; 

$pdo = connect_db();

if (!defined('OFFICIAL_DOMAIN')) {
    die("Configuration Error: Role constants are missing.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']); 
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $office_code = isset($_POST['office_code']) && $_POST['office_code'] !== '' ? trim($_POST['office_code']) : NULL;

    // Basic Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $_SESSION['register_message'] = "Error: Please fill all required fields.";
        $_SESSION['register_type'] = "error";
        header("Location: registration.php");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_message'] = "Error: Invalid email format.";
        $_SESSION['register_type'] = "error";
        header("Location: registration.php");
        exit();
    }

    if ($office_code === NULL) {
        $_SESSION['register_message'] = "Error: Please select an office/center.";
        $_SESSION['register_type'] = "error";
        header("Location: registration.php");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $email_lower = strtolower($email);
    $assigned_role = 'unassigned';

    // STRICT EMAIL VALIDATION: Must use official domain with staff. or head. prefix
    if (str_ends_with($email_lower, OFFICIAL_DOMAIN)) {
        
        $local_part = strstr($email_lower, '@', true);

        if (strpos($local_part, HEAD_IDENTIFIER) !== false) {
            $assigned_role = 'head';
        } elseif (strpos($local_part, STAFF_IDENTIFIER) !== false) {
            $assigned_role = 'staff';
        } else {
            // Email from official domain but no staff.*/head.* prefix = REJECT
            $_SESSION['register_message'] = "Error: Email must start with 'staff.' or 'head.' prefix (e.g., staff.yourname@sdu.edu.ph).";
            $_SESSION['register_type'] = "error";
            header("Location: registration.php");
            exit();
        }
    } else {
        // Non-official domain = REJECT
        $_SESSION['register_message'] = "Error: Email must be from @sdu.edu.ph domain with 'staff.' or 'head.' prefix.";
        $_SESSION['register_type'] = "error";
        header("Location: registration.php");
        exit();
    }

    // All new registrations are INACTIVE by default
    $is_approved = false;

    $sql = "INSERT INTO users (full_name, email, password_hash, role, office_code, is_approved) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$full_name, $email, $password_hash, $assigned_role, $office_code, $is_approved]);
        
        $_SESSION['register_message'] = "Registration Successful!<br>Your account has been created as a <strong>" . strtoupper($assigned_role) . "</strong>.
        <br><br>Your account is <strong>pending approval</strong> by your Unit Director.";
        $_SESSION['register_type'] = "success";
        
        // Redirect to prevent form resubmission
        header("Location: registration.php");
        exit();

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            $_SESSION['register_message'] = "Error: This email is already registered.";
        } else {
            $_SESSION['register_message'] = "Registration failed: " . $e->getMessage();
        }
        $_SESSION['register_type'] = "error";
        header("Location: registration.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDU - Register</title>
    <style> 

    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Montserrat', sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f0f2f5;
        background-image: url(BG.jpg); 
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .registration-container {
        display: flex;
        width: 100%;
        max-width: 1000px;
        height: auto;
        min-height: 650px;
        flex-direction: row-reverse;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .registration-left {
        background-color: #1a237e;
        color: white;
        flex: 1;
        display: flex;
        flex-direction: column; 
        justify-content: center; 
        align-items: center; 
        padding: 30px 20px;
    }

    .registration-left .register-logo {
        width: 200px;
        height: auto;
        margin-bottom: 15px; 
        display: block;
    }

    .registration-left h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-align: center; 
        line-height: 1.4;
    }

    .registration-right {
        background-color: white;
        flex: 1;
        display: flex;
        justify-content: center; 
        align-items: center;    
        padding: 30px 20px;
        overflow-y: auto;
    }

    .form-content {
        width: 100%;
        max-width: 380px; 
    }

    .form-content h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a237e;
        border-bottom: 3px solid #1a237e;
        padding-bottom: 5px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #495057;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .input-with-icon {
        display: flex;
        align-items: center;
        border: 1px solid #ced4da;
        border-radius: 0;
        background-color: #e9ecef;
        transition: all 0.3s ease;
    }

    .input-with-icon:focus-within {
        background-color: white;
        border-color: #1a237e;
    }

    .input-with-icon svg {
        margin: 0 10px;
        color: #6c757d;
        flex-shrink: 0;
    }

    .input-with-icon input,
    .input-with-icon select {
        width: 100%;
        border: none;
        padding: 10px 0;
        background-color: white;
        outline: none;
        font-family: 'Montserrat', sans-serif;
        font-size: 0.95rem;
        color: #2c3e50;
    }

    .input-with-icon input::placeholder {
        color: #6c757d;
    }

    .input-with-icon input:focus,
    .input-with-icon select:focus {
        outline: none;
    }

    .input-with-icon select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 18px;
        padding-right: 30px;
    }

    .register-btn { 
        width: 100%;
        padding: 12px;
        background-color: #1a237e;
        color: white;
        border: none;
        font-size: 1.1rem;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
        margin-top: 10px;
    }

    .register-btn:hover {
        background-color: #141b63;
    }

    .register-btn:active {
        background-color: #0d1149;
    }

    .login { 
        text-align: center;
        margin-top: 18px;
        font-size: 0.9rem;
    }

    .login a {
        color: #1a237e;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .login a:hover {
        color: #141b63;
        text-decoration: underline;
    }

    .text-danger {
        color: #e74c3c;
        font-weight: 600;
    }

    .message {
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
        font-size: 0.9rem;
        border-left: 4px solid;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .error {
        background-color: #fff3cd;
        color: #856404;
        border-color: #ffc107;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border-color: #28a745;
    }

    @media (max-width: 992px) {
        .registration-container {
            max-width: 800px;
        }
        .registration-left h1 {
            font-size: 1.4rem;
        }
    }

    @media (max-width: 768px) {
        .registration-container {
            flex-direction: column-reverse;
            height: auto;
            min-height: auto;
            border-radius: 0;
            box-shadow: none;
        }
        .registration-left, .registration-right {
            min-height: 40vh; 
            width: 100%;
            padding: 30px 20px;
        }
        .registration-right {
             min-height: 60vh;
        }
        .form-content {
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .registration-left h1 {
            font-size: 1.2rem;
        }
        .form-content h2 {
            font-size: 1.5rem;
        }
        .registration-left .register-logo {
            width: 150px;
        }
    }

    </style>

    </style> 
</head>
<body>
    <div class="registration-container">
        <div class="registration-right">
            <div class="form-content"> 
                <h2>Create an Account</h2>
                
                <?php
                if (isset($_SESSION['register_message'])) {
                    $type = ($_SESSION['register_type'] == 'success') ? 'success' : 'error';
                    echo '<div class="message ' . $type . '">';
                    echo $_SESSION['register_message'];
                    echo '</div>';
                    unset($_SESSION['register_message']);
                    unset($_SESSION['register_type']);
                }
                ?>
                
                <form action="registration.php" method="POST"> 
                    <div class="form-group">
                        <label for="full_name">FULL NAME <span class="text-danger">*</span></label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.685 10.567 10 8 10s-3.516.685-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                            </svg>
                            <input type="text" id="full_name" name="full_name" placeholder="Type your full name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">EMAIL <span class="text-danger">*</span></label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 1v.76L8.14 9.172a.5.5 0 0 1-.284 0L1 4.76V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="Type your Email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">PASSWORD <span class="text-danger">*</span></label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 1 0-6 0v4a1 1 0 0 0-1 1v2a2 2 0 0 0 2 2v2a.5.5 0 0 0 1 0v-2a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1M5.5 8.5a.5.5 0 0 1 1 0v2a.5.5 0 0 1-1 0z"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Type your password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="office_code">OFFICE/CENTER <span class="text-danger">*</span></label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3zM7 9a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V9zM13 1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h2z"/>
                            </svg>
                            <select id="office_code" name="office_code" required>
                                <option value="">-- Select Your Office --</option>
                                <?php
                                // Fetch offices from database
                                try {
                                    $offices_stmt = $pdo->prepare("SELECT code, name FROM offices ORDER BY name ASC");
                                    $offices_stmt->execute();
                                    $offices = $offices_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($offices as $office) {
                                        echo '<option value="' . htmlspecialchars($office['code']) . '">' . htmlspecialchars($office['name']) . '</option>';
                                    }
                                } catch (Exception $e) {
                                    echo '<option value="">-- Unable to load offices --</option>';
                                    error_log('Office fetch error: ' . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="register-btn">REGISTER</button>
                </form>
                
                <div class="login">
                    <a href="login.php">Already have an account? Sign In</a>
                </div>
            </div>
        </div>

        <div class="registration-left">
            <img src="SDU_Logo.png" alt="SDU Logo" class="register-logo">
            <h1>Social Development Unit Staff Capacity Building Management System</h1>
        </div>
    </div>
</body>
</html>