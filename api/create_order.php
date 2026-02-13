<?php
// portal/api/create_order.php
// تم تعديل التوجيه ليكون "pending" دائماً ليظهر كتنبيه للإدارة أولاً

// 1. التقاط الأخطاء القاتلة
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        ob_clean(); 
        http_response_code(200); 
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $error['message']]);
        exit;
    }
});

// 2. إعدادات الصفحة
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=UTF-8");

function sendJson($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

// 3. التحقق من الاتصال
if (!file_exists(__DIR__ . '/db_connect.php')) {
    sendJson(['status' => 'error', 'message' => 'ملف db_connect غير موجود']);
}
require __DIR__ . '/db_connect.php';

session_start();

if (!isset($_SESSION['client_id'])) {
    sendJson(['status' => 'error', 'message' => 'يرجى تسجيل الدخول']);
}

try {
    $client_id = $_SESSION['client_id'];
    $client_name = $_SESSION['client_name'] ?? 'Portal User';

    // استقبال البيانات الأساسية
    $job_name = filter_var($_POST['job_name'] ?? 'طلب من البوابة', FILTER_SANITIZE_STRING);
    $raw_service_type = $_POST['service_type'] ?? 'عام';
    $quantity = floatval($_POST['quantity'] ?? 1); 
    $user_notes = filter_var($_POST['details'] ?? '', FILTER_SANITIZE_STRING);
    $design_status = $_POST['design_status'] ?? 'needed';

    // --- تحديد نوع العملية ---
    $job_type = 'print'; // الافتراضي
    if (strpos($raw_service_type, 'علب') !== false || strpos($raw_service_type, 'كرتون') !== false) {
        $job_type = 'carton';
    } elseif (strpos($raw_service_type, 'بلاستيك') !== false || strpos($raw_service_type, 'أكياس') !== false) {
        $job_type = 'plastic';
    } elseif (strpos($raw_service_type, 'تسويق') !== false || strpos($raw_service_type, 'سوشيال') !== false) {
        $job_type = 'social';
    } elseif (strpos($raw_service_type, 'ويب') !== false || strpos($raw_service_type, 'موقع') !== false) {
        $job_type = 'web';
    } elseif (strpos($raw_service_type, 'تصميم') !== false) {
        $job_type = 'design_only';
    }

    // --- بناء تفاصيل العملية ---
    $details = [];
    $details[] = "--- طلب وارد من بوابة العملاء ---";
    $details[] = "الخدمة المختارة: " . $raw_service_type;

    // (نفس منطق تجميع التفاصيل السابق - لم يتم تغييره للحفاظ على الدقة)
    if ($job_type == 'design_only') {
        $qty = intval($_POST['design_items_count'] ?? $quantity);
        $details[] = "عدد البنود: " . $qty;
    } elseif ($job_type == 'print') {
        $qty = floatval($_POST['print_quantity'] ?? $quantity);
        $details[] = "الكمية المطلوبة: " . $qty;
        if(!empty($_POST['paper_type'])) $details[] = "الورق: " . $_POST['paper_type'];
    } elseif ($job_type == 'carton') {
        $qty = floatval($_POST['carton_quantity'] ?? $quantity);
        $details[] = "الكمية المطلوبة: " . $qty;
    } elseif ($job_type == 'plastic') {
        $qty = floatval($_POST['plastic_quantity'] ?? $quantity);
        $details[] = "الكمية: " . $qty;
    } elseif ($job_type == 'social') {
        $qty = intval($_POST['social_items_count'] ?? $quantity);
        $details[] = "عدد البوستات: " . $qty;
    } elseif ($job_type == 'web') {
        $qty = 1;
        if(!empty($_POST['web_type'])) $details[] = "نوع الموقع: " . $_POST['web_type'];
    }

    if (!empty($user_notes)) {
        $details[] = "\n--- ملاحظات العميل ---";
        $details[] = $user_notes;
    }

    $final_job_details = implode("\n", $details);

    // =================================================================================
    // التغيير الحاسم هنا: تثبيت المرحلة على "pending"
    // هذا يضمن ظهور الطلب في صندوق التنبيهات في الداشبورد الإداري للموافقة أولاً
    // =================================================================================
    $current_stage = 'pending'; 

    // --- الإدخال في قاعدة البيانات ---
    $sql = "INSERT INTO job_orders 
            (client_id, job_name, job_type, design_status, status, start_date, delivery_date, created_at, current_stage, quantity, added_by, job_details) 
            VALUES 
            (?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), NOW(), ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$client_id, $job_name, $job_type, $design_status, $current_stage, $qty, $client_name, $final_job_details])) {
        
        $new_job_id = $pdo->lastInsertId();
        $file_status = "";

        // --- معالجة رفع الملفات ---
        if (isset($_FILES['attachment'])) {
            try {
                $file_names = is_array($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : [$_FILES['attachment']['name']];
                $file_tmps  = is_array($_FILES['attachment']['tmp_name']) ? $_FILES['attachment']['tmp_name'] : [$_FILES['attachment']['tmp_name']];

                if (count($file_names) > 0 && !empty($file_names[0])) {
                    $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
                    $targetDir = $root . '/work/uploads/';
                    if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                    if (is_writable($targetDir)) {
                        for ($i = 0; $i < count($file_names); $i++) {
                            $f_name = $file_names[$i];
                            $f_tmp = $file_tmps[$i];
                            if (!empty($f_name)) {
                                $newFileName = time() . "_" . $i . "_" . basename($f_name);
                                if (move_uploaded_file($f_tmp, $targetDir . $newFileName)) {
                                    $dbPath = "uploads/" . $newFileName;
                                    $stmt_f = $pdo->prepare("INSERT INTO job_files (job_id, file_path, stage, description, uploaded_by, created_at) VALUES (?, ?, ?, 'مرفق مبدئي', ?, NOW())");
                                    $stmt_f->execute([$new_job_id, $dbPath, $current_stage, $client_name]);
                                    $file_status = " (وتم رفع الملفات)";
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) { /* Silent fail for files */ }
        }

        sendJson(['status' => 'success', 'message' => "تم إرسال الطلب رقم #$new_job_id للإدارة للمراجعة" . $file_status]);

    } else {
        sendJson(['status' => 'error', 'message' => 'فشل الحفظ في قاعدة البيانات']);
    }

} catch (PDOException $e) {
    sendJson(['status' => 'error', 'message' => 'SQL Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => 'General Error: ' . $e->getMessage()]);
}
?>