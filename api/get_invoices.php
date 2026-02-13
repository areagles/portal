<?php
// api/get_invoices.php
// الإصدار المحدث: V1.1 - إصلاح مشكلة NaN ومعالجة القيم الصفرية

ob_start(); 
ini_set('display_errors', 0); 
error_reporting(E_ALL); 
header("Content-Type: application/json; charset=UTF-8");

// 1. الاتصال بقاعدة البيانات
if (!file_exists(__DIR__ . '/../db_connect.php')) {
    if (file_exists(__DIR__ . '/db_connect.php')) {
        require __DIR__ . '/db_connect.php';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Config Error']); exit;
    }
} else {
    require __DIR__ . '/../db_connect.php';
}

session_start();

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $client_id = $_SESSION['client_id'];
    
    // 2. جلب الفواتير مع إصلاح القيم الفارغة (NaN Fix)
    // نستخدم COALESCE أو IFNULL لتحويل القيم الفارغة إلى 0.00
    $sql = "
        SELECT 
            i.id, 
            COALESCE(i.total_amount, 0) as total_amount, 
            COALESCE(i.paid_amount, 0) as paid_amount, 
            DATE_FORMAT(i.created_at, '%Y-%m-%d') as date, 
            COALESCE(j.job_name, 'فاتورة عامة') as job_name
        FROM invoices i
        LEFT JOIN job_orders j ON i.job_id = j.id
        WHERE i.client_id = ?
        ORDER BY i.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. تأكيد أن الأرقام ترسل كأرقام وليس نصوص (Double Check)
    foreach ($invoices as &$inv) {
        $inv['total_amount'] = (float)$inv['total_amount'];
        $inv['paid_amount']  = (float)$inv['paid_amount'];
    }

    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $invoices]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>