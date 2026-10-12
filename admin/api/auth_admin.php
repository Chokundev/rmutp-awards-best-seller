<?php
// ไฟล์: admin/api/auth_admin.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// รับค่าที่ส่งมาจาก JavaScript (Fetch API) ผ่าน Google Sign-In
$data = json_decode(file_get_contents('php://input'), true);

// 1. ดึงข้อมูลอีเมล ชื่อ และรูปโปรไฟล์จาก Payload ของ Google
$email = trim($data['email'] ?? '');
$name = trim($data['name'] ?? 'ผู้ดูแลระบบ'); // กำหนดค่าเริ่มต้นหากไม่มีชื่อส่งมา
$picture = trim($data['picture'] ?? ''); // เก็บ URL ของรูปโปรไฟล์

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลอีเมล']);
    exit;
}

// ---------------------------------------------------------
// ฟังก์ชันตัวช่วยสำหรับบันทึก Log แอดมิน
// ---------------------------------------------------------
function logAdminLogin($pdo, $email, $ip, $status) {
    try {
        $stmt = $pdo->prepare("INSERT INTO rma_admin_login_logs (attempted_email, ip_address, status) VALUES (:email, :ip, :status)");
        $stmt->execute([
            'email' => $email,
            'ip' => $ip,
            'status' => $status
        ]);
    } catch (PDOException $e) {
        // ข้ามไปหากบันทึก log ไม่สำเร็จ (ไม่ให้ระบบล็อกอินหลักพัง)
    }
}
// ---------------------------------------------------------

try {
    // ตรวจสอบว่าอีเมลนี้มีสิทธิ์เป็นแอดมินหรือไม่จากตาราง rma_admins
    $stmt = $pdo->prepare("SELECT * FROM rma_admins WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // 💥 [เพิ่มใหม่] อัปเดตชื่อและรูปภาพล่าสุดจาก Google ลงในตาราง rma_admins
        $updateStmt = $pdo->prepare("UPDATE rma_admins SET name_th = :name, profile_image = :picture WHERE email = :email");
        $updateStmt->execute([
            'name' => $name,
            'picture' => $picture,
            'email' => $email
        ]);

        // ให้สิทธิ์เข้าสู่ระบบ
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_email'] = $admin['email'];
        
        // บันทึกข้อมูลชื่อและรูปลงใน Session เพื่อนำไปแสดงที่ Sidebar
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_profile'] = $picture;
        
        // บันทึก Log สำเร็จ
        logAdminLogin($pdo, $email, $ip_address, 'success');
        
        echo json_encode(['status' => 'success', 'message' => 'ยืนยันตัวตนสำเร็จ กำลังพาท่านเข้าระบบ']);
    } else {
        // บันทึก Log ล้มเหลว (อีเมลไม่ได้รับอนุญาต)
        logAdminLogin($pdo, $email, $ip_address, 'failed');
        
        echo json_encode(['status' => 'error', 'message' => 'ปฏิเสธการเข้าถึง: อีเมลนี้ไม่ได้รับสิทธิ์แอดมิน']);
    }

} catch (PDOException $e) {
    logAdminLogin($pdo, $email, $ip_address, 'failed');
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบ: ' . $e->getMessage()]);
}
?>