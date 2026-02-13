<?php
// api/cron_update_cache.php
// هذا الملف يتم استدعاؤه تلقائياً بواسطة السيرفر

// 1. منع الوصول المباشر من المتصفح (حماية اختيارية)
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    // يمكنك وضع مفتاح سري هنا لزيادة الأمان
    // die('Access Denied');
}

require __DIR__ . '/../db_connect.php'; 

try {
    // تحديث توقيت الكاش للوقت الحالي
    // هذا سيجبر جميع المستخدمين (تطبيقات ومتصفحات) على التحديث فوراً عند فحصهم التالي
    $sql = "UPDATE system_settings 
            SET setting_value = UNIX_TIMESTAMP() 
            WHERE setting_key = 'last_cache_clear'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // (اختياري) تنظيف بيانات مؤقتة أخرى لتقليل حجم الداتا بيز
    // مثلاً: حذف رموز التوكن القديمة التي مر عليها أكثر من 30 يوم
    // $pdo->query("DELETE FROM clients WHERE access_token IS NOT NULL AND last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)");

    echo "Cache Timestamp Updated Successfully at " . date('Y-m-d H:i:s');

} catch (Exception $e) {
    // تسجيل الخطأ في ملف log بدلاً من عرضه
    error_log("Cron Error: " . $e->getMessage());
}
?>