<?php
// ไฟล์: admin/api/slip_api.php
require_once '../../config.php';

header('Content-Type: application/json');

// เช็กสิทธิ์แอดมิน (สังเกตว่าเราไม่ต้องใส่ session_start() แล้วตามที่เราแก้ไข config.php ไปครับ)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$action = $_POST['action'] ?? '';
$slip_id = $_POST['id'] ?? '';

// ตรวจสอบความถูกต้องของข้อมูลที่ส่งมา
if (empty($slip_id) || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    // 1. กำหนดสถานะใหม่ตามที่แอดมินกด
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // 2. ดึงอีเมลแอดมินจาก Session (เพื่อใช้บันทึกว่าใครเป็นผู้อนุมัติ)
    // หากไม่มีข้อมูลใน Session จะใช้คำว่า 'Unknown Admin' แทน
    $admin_email = $_SESSION['admin_email'] ?? 'Unknown Admin';

    // 3. อัปเดตข้อมูลสถานะและชื่อผู้ตรวจสอบลงในฐานข้อมูล
    $stmt = $pdo->prepare("UPDATE rma_slips SET status = :status, verified_by = :admin WHERE id = :id");
    $stmt->execute([
        'status' => $new_status,
        'admin'  => $admin_email,
        'id'     => $slip_id
    ]);

    // 4. ส่งข้อความตอบกลับ
    $message = ($action === 'approve') ? 'อนุมัติสลิปเรียบร้อยแล้ว' : 'ปฏิเสธสลิปเรียบร้อยแล้ว';
    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (PDOException $e) {
    // จัดการข้อผิดพลาด
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()]);
}
?>