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

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $email_lower = strtolower($email);
    $assigned_role = 'unassigned';

    if (str_ends_with($email_lower, OFFICIAL_DOMAIN)) {
        
        $local_part = strstr($email_lower, '@', true);

        if (strpos($local_part, HEAD_IDENTIFIER) !== false) {
            $assigned_role = 'head';
        } elseif (strpos($local_part, STAFF_IDENTIFIER) !== false) {
            $assigned_role = 'staff';
        } else {
            // Email from official domain but no special identifier = assign as staff
            $assigned_role = 'staff';
        }
    }

    $sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$full_name, $email, $password_hash, $assigned_role]);
        
        if ($assigned_role === 'unassigned') {
            $_SESSION['register_message'] = "Registration Successful! Your account is pending role assignment.";
        } else {
            $_SESSION['register_message'] = "Registration Successful! Your role has been automatically set as **" . strtoupper($assigned_role) . "**.";
        }
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
    
    body {
        font-family: 'Montserrat', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
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
        height: 100vh;          
        max-height: 600px;     
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
        padding: 20px;
    }

    .registration-left .register-logo {
        width: 200px;
        height: auto;
        margin-bottom: 5px; 
        display: block;
        margin-left: 0; 
        margin-right: 0; 
    }

    .registration-left h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-align: center; 
        padding-left: 0; 
    }

    .registration-right {
        background-color: white;
        flex: 1;
        display: flex;
        justify-content: center; 
        align-items: center;    
        padding: 20px; 
    }

    .form-content {
        width: 100%;
        max-width: 400px; 
    }

    .form-content h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a237e;
        border-bottom: 3px solid #1a237e;
        padding-bottom: 5px;
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 20px;
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
    }

    .input-with-icon svg {
        margin: 0 10px;
        color: #6c757d;
    }

    .input-with-icon input {
        width: 100%;
        border: none;
        padding: 10px;
        background-color: white;
        outline: none;
    }

    .input-with-icon input::placeholder {
        color: #6c757d;
    }

    .input-with-icon input:focus {
        outline: 2px solid #1a237e;
        outline-offset: -2px;
    }

    .register-btn { 
        width: 100%;
        padding: 12px;
        background-color: #1a237e;
        color: white;
        border: none;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
        margin-top: 10px;
    }

    .register-btn:hover {
        background-color: #141b63;
    }

    .login { 
        text-align: center;
        margin-top: 20px;
    }

    .login a {
        color: #1a237e;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .login a:hover {
        text-decoration: underline;
    }

    .text-danger {
        color: red;
    }

    .message {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    @media (max-width: 992px) {
        .registration-container {
            max-width: 800px;
        }
        .registration-left h1 {
            font-size: 2rem;
        }
    }

    @media (max-width: 768px) {
        .registration-container {
            flex-direction: column-reverse;
            height: auto;
            max-height: none;
            border-radius: 0;
            box-shadow: none;
        }
        .registration-left, .registration-right {
            min-height: 40vh; 
            width: 100%;
            padding: 40px 20px;
        }
        .registration-right {
             min-height: 60vh;
        }
        .form-content-wrapper {
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .registration-left h1 {
            font-size: 1.8rem;
        }
        .form-content-wrapper h2 {
            font-size: 1.5rem;
        }
    }

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
                    echo htmlspecialchars($_SESSION['register_message']);
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
                    
                    <button type="submit" class="register-btn">REGISTER</button>
                </form>
                
                <div class="login">
                    <a href="login.php">Already have an account? Sign In</a>
                </div>
            </div>
        </div>

        <div class="registration-left">
            <img src="SDU_Logo.png" alt="SDU Logo" class="register-logo">
            <h1>SOCIAL DEVELOPMENT UNIT STAFF CAPACITY BUILDING</h1>
        </div>
    </div>
</body>
</html>