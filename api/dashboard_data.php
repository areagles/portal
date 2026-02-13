<?php
// api/dashboard_data.php
// الإصدار المحدث: V2.8 - Reason for Rejection Support

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
    echo json_encode(['status' => 'error', 'message' => 'unauthorized']); exit;
}

$client_id = $_SESSION['client_id'];

try {
    // 2. بيانات العميل + التوكن
    $stmt = $pdo->prepare("SELECT name, phone, access_token FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        session_destroy();
        echo json_encode(['status' => 'error', 'message' => 'Client not found']); exit;
    }

    $client_token = $client['access_token'];
    if (empty($client_token)) {
        $client_token = bin2hex(random_bytes(16));
        $upd = $pdo->prepare("UPDATE clients SET access_token = ? WHERE id = ?");
        $upd->execute([$client_token, $client_id]);
    }

    // 3. الرصيد (المعادلة الدقيقة)
    $sql_balance = "
        SELECT 
            (
                IFNULL((SELECT opening_balance FROM clients WHERE id = ?), 0)
                +
                IFNULL((SELECT SUM(total_amount) FROM invoices WHERE client_id = ?), 0)
                - 
                IFNULL((SELECT SUM(amount) FROM financial_receipts WHERE client_id = ? AND type='in'), 0)
            ) as final_balance
    ";
    
    $stmt_bal = $pdo->prepare($sql_balance);
    $stmt_bal->execute([$client_id, $client_id, $client_id]);
    $total_balance = $stmt_bal->fetchColumn() ?: 0;

    // 4. العدادات
    $active_orders = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE client_id = ? AND status NOT IN ('completed', 'cancelled')");
    $active_orders->execute([$client_id]);
    $active_count = $active_orders->fetchColumn();
    
    // العروض المعلقة
    try {
        $pending_quotes = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE client_id = ? AND status = 'pending'");
        $pending_quotes->execute([$client_id]);
        $quotes_count = $pending_quotes->fetchColumn();
    } catch (Exception $e) { $quotes_count = 0; }

    // عداد الفواتير
    try {
        $stmt_inv = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE client_id = ? AND (total_amount - paid_amount) > 1"); 
        $stmt_inv->execute([$client_id]);
        $invoices_count = $stmt_inv->fetchColumn();
    } catch (Exception $e) { $invoices_count = 0; }

    // 5. التنبيهات
    $pending_review = null;
    $stmt_rev = $pdo->prepare("
        SELECT job_name, access_token 
        FROM job_orders 
        WHERE client_id = ? 
        AND current_stage IN ('idea_review', 'content_review', 'design_review', 'client_rev') 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt_rev->execute([$client_id]);
    $rev_row = $stmt_rev->fetch(PDO::FETCH_ASSOC);
    
    if ($rev_row) {
        $pending_review = [
            'job_name' => $rev_row['job_name'],
            'url'      => "https://work.areagles.com/client_review.php?token=" . $rev_row['access_token']
        ];
    }

    // 6. آخر النشاطات (مع معالجة سبب الرفض)
    // [MODIFICATION]: Added 'notes' to query to extract reason
    $stmt_recent = $pdo->prepare("SELECT id, job_name, current_stage as status, notes, created_at FROM job_orders WHERE client_id = ? ORDER BY id DESC LIMIT 5");
    $stmt_recent->execute([$client_id]);
    $recent_orders = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    // معالجة المصفوفة لاستخراج سبب الرفض إذا وجد
    foreach ($recent_orders as &$order) {
        $order['rejection_reason'] = ''; // Default empty
        if ($order['status'] == 'cancelled') {
            // البحث عن النص داخل الأقواس [سبب الرفض: ...]
            if (preg_match('/\[سبب الرفض: (.*?)\]/u', $order['notes'], $matches)) {
                $order['rejection_reason'] = $matches[1];
            }
        }
        unset($order['notes']); // إزالة الملاحظات الأصلية لتقليل حجم البيانات
    }

    // 7. عبارة ترحيبية
    $quotes_txt = [
        "شراكتكم معنا وسام نعتز به.. دمت شريكاً للنجاح ✨",
        "خطوة بخطوة نصنع التميز معاً.. 🦅",
        "كل تفصيلة في طلبكم تنفذ بشغف..",
    ];
    $random_quote = $quotes_txt[array_rand($quotes_txt)];

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'data' => [
            'client_id' => $client_id,
            'client_token' => $client_token,
            'name' => $client['name'],
            'balance' => $total_balance,
            'quote' => $random_quote,
            'active_orders' => $active_count,
            'pending_quotes' => $quotes_count,
            'invoices_count' => $invoices_count,
            'pending_review' => $pending_review,
            'recent_orders' => $recent_orders
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>