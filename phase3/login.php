<?php
session_start();


$users = [
    "student1@stu.uob.edu.bh" => ["password"=>"student123", "role"=>"student"],
    "admin1@uob.edu.bh" => ["password"=>"admin123", "role"=>"admin"]
];

$email = strtolower($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

if(isset($users[$email]) && $users[$email]['password'] === $pass){
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $users[$email]['role'];
    header("Location: Task1.html");
    exit;
} else {
    $error = "Invalid email or password!";
    header("Location: login.html?error=" . urlencode($error));
    exit;
}
?>
