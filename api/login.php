<?php
// portal/api/login.php
// النسخة المرنة (تقبل JSON و FormData)

ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// التأكد من ملف الاتصال
if (!file_exists(__DIR__ . '/db_connect.php')) {
    echo json_encode(['status' => 'error', 'message' => 'ملف db_connect.php مفقود']);
    exit;
}
require __DIR__ . '/db_connect.php';

session_start();

// --- 1. محاولة قراءة البيانات (JSON) ---
$input = file_get_contents("php://input");
$data = json_decode($input, true); // تحويل لـ Array

// --- 2. محاولة قراءة البيانات (Standard POST) ---
// إذا لم تكن JSON، نستخدم $_POST العادية
if (empty($data)) {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
} else {
    $phone = $data['phone'] ?? '';
    $password = $data['password'] ?? '';
}

// --- 3. التحقق النهائي ---
if (empty($phone) || empty($password)) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => 'بيانات غير مكتملة',
        'debug' => ['received_phone' => $phone ? 'yes' : 'no'] // للمساعدة في التشخيص
    ]);
    exit;
}

$phone = filter_var($phone, FILTER_SANITIZE_STRING);

try {
    // البحث عن العميل
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // تسجيل الدخول بنجاح
        $_SESSION['client_id'] = $user['id'];
        $_SESSION['client_name'] = $user['name'];
        $_SESSION['client_phone'] = $user['phone'];

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'تم تسجيل الدخول بنجاح', 'redirect' => 'dashboard.html']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة']);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>