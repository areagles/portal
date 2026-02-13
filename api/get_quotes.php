<?php
// portal/api/get_quotes.php
header("Content-Type: application/json; charset=UTF-8");
require 'db_connect.php';
session_start();

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $client_id = $_SESSION['client_id'];
    
    // جلب عروض الأسعار مع التوكن للعرض
    $stmt = $pdo->prepare("SELECT id, created_at, valid_until, total_amount, status, access_token 
                           FROM quotes 
                           WHERE client_id = ? 
                           ORDER BY id DESC");
    $stmt->execute([$client_id]);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $quotes]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error']);
}
?>