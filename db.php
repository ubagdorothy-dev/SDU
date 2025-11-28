<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sdu');

define('OFFICIAL_DOMAIN', '@sdu.edu.ph');
define('HEAD_IDENTIFIER', 'head.');
define('STAFF_IDENTIFIER', 'staff.');


function connect_db() {
    
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $user = DB_USER;
    $password = DB_PASS;

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {

        error_log("Database Connection Failed: " . $e->getMessage());
        die("System is currently unavailable. Please try again later. (DB Error)");
    }
}

?>