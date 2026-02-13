<?php
// portal/api/get_orders.php
// الإصدار المحدث: يقرأ current_stage و status لضمان الدقة

ob_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!file_exists(__DIR__ . '/db_connect.php')) {
    echo json_encode(['status' => 'error', 'message' => 'Config Error']); exit;
}
require __DIR__ . '/db_connect.php';

session_start();

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

try {
    $client_id = $_SESSION['client_id'];
    
    // جلب current_stage بالإضافة لـ status
    $sql = "SELECT id, job_name, quantity, price, status, current_stage, job_type, created_at 
            FROM job_orders 
            WHERE client_id = ? 
            ORDER BY id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as &$order) {
        // تنظيف القيم
        $st = strtolower(trim($order['status']));
        $stage = strtolower(trim($order['current_stage'] ?? ''));
        $type = $order['job_type'];
        
        $status_text = 'قيد المراجعة';
        $is_closed = false;

        // منطق تحديد الحالة (الأولوية للإغلاق)
        $closure_statuses = ['completed', 'delivered', 'done', 'closed', 'shipped', 'archived'];
        
        // التحقق من الحقلين لضمان الإغلاق
        if (in_array($st, $closure_statuses) || in_array($stage, $closure_statuses)) {
            $status_text = '✅ تم التسليم';
            $is_closed = true;
        }
        elseif (in_array($st, ['canceled', 'cancelled', 'rejected'])) {
            $status_text = '❌ ملغاة';
            $is_closed = true;
        }
        // باقي الحالات
        elseif (in_array($st, ['pending', 'new'])) {
            $status_text = '⏳ قيد المراجعة';
        }
        elseif ($st == 'design' || strpos($stage, 'design') !== false) {
            $status_text = '🎨 مرحلة التصميم';
        }
        elseif (in_array($st, ['proof_sent', 'waiting_approval']) || strpos($stage, 'review') !== false) {
            $status_text = '✋ بانتظار موافقتك';
        }
        elseif ($st == 'approved') {
            $status_text = '✅ تمت الموافقة';
        }
        elseif (in_array($st, ['processing', 'in_progress', 'production'])) {
            // تخصيص النص حسب النوع
            switch ($type) {
                case 'print':   $status_text = '🖨️ جاري الطباعة'; break;
                case 'web':     $status_text = '💻 جاري البرمجة'; break;
                default:        $status_text = '⚙️ جاري التنفيذ'; break;
            }
        }

        $order['status_text'] = $status_text;
        $order['is_closed'] = $is_closed;
        
        $order['price_formatted'] = ($order['price'] > 0) ? number_format((float)$order['price'], 2) . ' ج.م' : '---';
        $order['date_formatted'] = date('Y/m/d', strtotime($order['created_at']));

        unset($order['job_details'], $order['notes']);
    }

    echo json_encode(['status' => 'success', 'data' => $orders]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
ob_end_flush();
?>