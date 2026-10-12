<?php
// ไฟล์: admin/api/settings_api.php
require_once '../../config.php';

header('Content-Type: application/json');

// เช็กสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$status = $_POST['status'] ?? '';

if (!in_array($status, ['open', 'closed'])) {
    echo json_encode(['status' => 'error', 'message' => 'ค่าสถานะไม่ถูกต้อง']);
    exit;
}

try {
    // อัปเดตสถานะในตาราง rma_settings
    $stmt = $pdo->prepare("UPDATE rma_settings SET setting_value = :status WHERE setting_key = 'system_status'");
    $stmt->execute(['status' => $status]);

    echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะระบบเรียบร้อยแล้ว']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()]);
}
?>