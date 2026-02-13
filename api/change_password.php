<?php
// portal/api/change_password.php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!file_exists(__DIR__ . '/db_connect.php')) {
    echo json_encode(['status' => 'error', 'message' => 'Configuration Error']);
    exit;
}
require __DIR__ . '/db_connect.php';

session_start();

if (!isset($_SESSION['client_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'يرجى تسجيل الدخول أولاً']);
    exit;
}

$client_id = $_SESSION['client_id'];
$current_pass = $_POST['current_password'] ?? '';
$new_pass = $_POST['new_password'] ?? '';

// التحقق من صحة المدخلات
if (empty($current_pass) || empty($new_pass)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'جميع الحقول مطلوبة']);
    exit;
}

if (strlen($new_pass) < 6) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل']);
    exit;
}

try {
    // 1. جلب كلمة المرور الحالية من قاعدة البيانات
    $stmt = $pdo->prepare("SELECT password_hash FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $user = $stmt->fetch();

    if (!$user) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود']);
        exit;
    }

    // 2. التحقق من تطابق كلمة المرور الحالية
    if (!password_verify($current_pass, $user['password_hash'])) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'كلمة المرور الحالية غير صحيحة']);
        exit;
    }

    // 3. تحديث كلمة المرور (تشفير الجديدة)
    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    
    $update = $pdo->prepare("UPDATE clients SET password_hash = ? WHERE id = ?");
    if ($update->execute([$new_hash, $client_id])) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'تم تغيير كلمة المرور بنجاح']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'فشل التحديث، حاول مرة أخرى']);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات']);
}
?>