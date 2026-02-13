<?php
// api/register.php
require 'db_connect.php'; // استدعاء الاتصال

// قراءة البيانات القادمة
$input = file_get_contents("php://input");
$data = json_decode($input);

// التحقق من أن البيانات وصلت بشكل صحيح
if (is_null($data)) {
    echo json_encode(['status' => 'error', 'message' => 'لم يتم إرسال بيانات (JSON Error)']);
    exit;
}

// التحقق من الحقول الإجبارية
if (empty($data->name) || empty($data->phone) || empty($data->password)) {
    echo json_encode(['status' => 'error', 'message' => 'يرجى ملء كافة البيانات المطلوبة']);
    exit;
}

$name = filter_var($data->name, FILTER_SANITIZE_STRING);
$phone = filter_var($data->phone, FILTER_SANITIZE_STRING);
$email = !empty($data->email) ? filter_var($data->email, FILTER_VALIDATE_EMAIL) : '';
$address = !empty($data->address) ? filter_var($data->address, FILTER_SANITIZE_STRING) : '';
$password = password_hash($data->password, PASSWORD_DEFAULT);
$access_token = bin2hex(random_bytes(16));

try {
    // 1. إصلاح الجدول: إضافة عمود الباسورد إذا لم يكن موجوداً
    // (هذه الخطوة ضرورية لأن جدول clients القديم ليس به باسورد)
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM clients LIKE 'password_hash'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
        }
    } catch (Exception $e) {
        // نتجاهل خطأ التعديل ونحاول الإكمال، ربما العمود موجود
    }

    // 2. التحقق من التكرار
    $checkSql = "SELECT id FROM clients WHERE phone = ?";
    $params = [$phone];
    
    if (!empty($email)) {
        $checkSql .= " OR email = ?";
        $params[] = $email;
    }

    $stmt = $pdo->prepare($checkSql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'رقم الهاتف أو البريد مسجل بالفعل']);
        exit;
    }

    // 3. الإدخال
    $sql = "INSERT INTO clients (name, phone, email, password_hash, address, access_token, created_at, opening_balance) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 0.00)";
    
    $stmt = $pdo->prepare($sql);
    
    // ترتيب المتغيرات مهم جداً
    if ($stmt->execute([$name, $phone, $email, $password, $address, $access_token])) {
        echo json_encode(['status' => 'success', 'message' => 'تم إنشاء الحساب بنجاح']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل حفظ البيانات في الجدول']);
    }

} catch (PDOException $e) {
    // إرسال تفاصيل الخطأ للمساعدة في الحل
    echo json_encode(['status' => 'error', 'message' => 'خطأ برمجي: ' . $e->getMessage()]);
}
?>