<?php
// ไฟล์: contestant/auth_process.php
session_start();
require_once '../config.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

// กำหนด Header ให้ส่งค่ากลับเป็น JSON
header('Content-Type: application/json');

// =========================================================================
// ฟังก์ชันสำหรับบันทึกประวัติการเข้าสู่ระบบลงตาราง rma_login_logs
// =========================================================================
function logLoginAttempt($pdo, $username, $password, $ip, $status) {
    try {
        $log_stmt = $pdo->prepare("INSERT INTO rma_login_logs (attempted_username, attempted_password, ip_address, status) VALUES (:user, :pass, :ip, :status)");
        $log_stmt->execute([
            'user' => $username,
            'pass' => $password,
            'ip' => $ip,
            'status' => $status
        ]);
    } catch (PDOException $e) {
        // ใช้ try-catch ครอบไว้ เพื่อไม่ให้การล็อกอินหลักพัง หากตาราง log มีปัญหา
    }
}
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $stdcode = trim($_POST['stdcode'] ?? '');
    $citizenid = trim($_POST['citizenid'] ?? '');
    
    // ดึงค่า IP Address ของผู้ใช้งาน
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
    if (empty($stdcode) || empty($citizenid)) {
        logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'failed');
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    try {
        // 1. ตรวจสอบข้อมูลจากตาราง stdall
        $stmt = $pdo->prepare("SELECT * FROM stdall WHERE stdcode = :stdcode AND CITIZENID = :citizenid");
        $stmt->execute(['stdcode' => $stdcode, 'citizenid' => $citizenid]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            // บันทึก Log กรณีรหัสผิด
            logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'failed');
            echo json_encode(['status' => 'error', 'message' => 'รหัสนักศึกษาหรือเลขประจำตัวประชาชนไม่ถูกต้อง']);
            exit;
        }

        // 2. ตรวจสอบสถานะนักศึกษา (n_stu ต้องอยู่ระหว่าง 10 ถึง 15)
        $n_stu = (int)$student['n_stu'];
        if ($n_stu < 10 || $n_stu > 15) {
            // บันทึก Log กรณีสถานะนักศึกษาไม่ปกติ
            logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'failed');
            echo json_encode(['status' => 'error', 'message' => 'ไม่อนุญาตให้เข้าสู่ระบบ: สถานะนักศึกษาของท่านไม่ปกติ']);
            exit;
        }

        // 3. ตรวจสอบว่าเป็นผู้เข้าประกวดหรือไม่จากตาราง rma_contestants
        $stmt2 = $pdo->prepare("SELECT * FROM rma_contestants WHERE stdcode = :stdcode");
        $stmt2->execute(['stdcode' => $stdcode]);
        $contestant = $stmt2->fetch(PDO::FETCH_ASSOC);

        if (!$contestant) {
            // บันทึก Log กรณีไม่ใช่ผู้เข้าประกวด
            logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'failed');
            echo json_encode(['status' => 'error', 'message' => 'คุณไม่ใช่ผู้เข้าประกวด ห้ามเข้าระบบเด็ดขาด!']);
            exit;
        }

        // หากผ่านเงื่อนไขทั้งหมด ให้บันทึกข้อมูลลง Session
        $_SESSION['is_contestant'] = true;
        $_SESSION['contestant_id'] = $contestant['id'];
        $_SESSION['stdcode'] = $contestant['stdcode'];
        
        logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'success');
        
        // ส่งผลลัพธ์ว่าสำเร็จ
        echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ กำลังพาท่านไปยังหน้าแดชบอร์ด...']);

    } catch (PDOException $e) {
        // จัดการกรณีเกิดข้อผิดพลาดกับ Database
        logLoginAttempt($pdo, $stdcode, $citizenid, $ip_address, 'failed');
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบ: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>