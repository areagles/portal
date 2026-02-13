<?php
// api/db_connect.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

$h = "localhost"; 
$u = "u159629331_work"; 
$p = "AllahAkbar@1986"; 
$d = "u159629331_wo"; 

$dsn = "mysql:host=$h;dbname=$d;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $u, $p, $options);
} catch (\PDOException $e) {
    // في حالة الخطأ نرجع JSON بدلاً من كود HTML
    echo json_encode(["status" => "error", "message" => "Database Connection Failed"]);
    exit;
}
?>