<?php
// portal/api/request_quote.php
// مخصص لاستقبال طلبات التسعير من البوابة

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
    echo json_encode(['status' => 'error', 'message' => 'يرجى تسجيل الدخول']);
    exit;
}

try {
    $client_id = $_SESSION['client_id'];
    
    // استقبال البيانات
    $job_name = filter_var($_POST['job_name'] ?? 'طلب تسعير', FILTER_SANITIZE_STRING);
    $qty = floatval($_POST['quantity'] ?? 1);
    $details = filter_var($_POST['details'] ?? '', FILTER_SANITIZE_STRING);
    $service = $_POST['service_type'] ?? 'عام';

    // تجميع الملاحظات للموظف المختص
    $full_notes = "نوع الخدمة: $service\nاسم المشروع: $job_name\nالكمية: $qty\nالتفاصيل: $details";
    
    // إنشاء توكن للعرض
    $token = bin2hex(random_bytes(16));

    // =================================================================================
    // النقطة الأهم: الحالة 'pending' والمبلغ 0.00
    // هذا يضمن ظهور الطلب في قسم التنبيهات في لوحة التحكم
    // =================================================================================
    $sql = "INSERT INTO quotes (client_id, created_at, valid_until, total_amount, status, notes, access_token) 
            VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 0.00, 'pending', ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$client_id, $full_notes, $token])) {
        
        // محاولة رفع ملفات (اختياري لعرض السعر)
        // ... (كود رفع الملفات إذا لزم الأمر، مشابه لـ create_order) ...

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'تم إرسال طلب التسعير بنجاح، سيقوم الفريق بمراجعته والرد قريباً.']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'فشل حفظ الطلب']);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'خطأ قاعدة بيانات: ' . $e->getMessage()]);
}
?>