<?php
// ไฟล์: admin/api/contestant_api.php
require_once '../../config.php';

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) throw new Exception('ไม่พบ ID ผู้เข้าประกวด');

        // ดึงชื่อไฟล์รูปเก่ามาลบทิ้ง (ถ้ามี)
        $stmt = $pdo->prepare("SELECT profile_image FROM rma_contestants WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $old_image = $stmt->fetchColumn();
        
        if ($old_image && file_exists("../../assets/uploads/profiles/" . $old_image)) {
            unlink("../../assets/uploads/profiles/" . $old_image);
        }

        // ลบข้อมูลออกจาก DB
        $stmt = $pdo->prepare("DELETE FROM rma_contestants WHERE id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);
        exit;
    } 
    elseif ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? '';
        $stdcode = trim($_POST['stdcode'] ?? ''); // รหัสนักศึกษา
        $number = trim($_POST['contestant_number'] ?? ''); // หมายเลขผู้เข้าประกวด
        $name_th = trim($_POST['name_th'] ?? ''); // ชื่อไทย
        $name_en = trim($_POST['name_en'] ?? ''); // ชื่ออังกฤษ
        $nickname = trim($_POST['nickname'] ?? ''); // ชื่อเล่น
        $category = trim($_POST['category'] ?? ''); // ประเภท

        // ตรวจสอบข้อมูลสำคัญให้ครบถ้วนตามโครงสร้างฐานข้อมูล
        if (empty($stdcode) || empty($number) || empty($name_th) || empty($name_en) || empty($category)) {
            throw new Exception('กรุณากรอกข้อมูลสำคัญ (ที่มีเครื่องหมาย *) ให้ครบถ้วน');
        }

        $image_name = null;

        // จัดการอัปโหลดรูปภาพ
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_ext)) throw new Exception('ไฟล์รูปต้องเป็น JPG หรือ PNG เท่านั้น');
            if ($_FILES['profile_image']['size'] > 2097152) throw new Exception('ไฟล์รูปต้องขนาดไม่เกิน 2MB');

            $image_name = uniqid('prof_') . '_' . time() . '.' . $file_ext;
            $upload_path = '../../assets/uploads/profiles/' . $image_name;
            
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                throw new Exception('อัปโหลดรูปภาพไม่สำเร็จ');
            }
        }

        if ($action === 'add') {
            // เพิ่มข้อมูลใหม่
            $stmt = $pdo->prepare("INSERT INTO rma_contestants (stdcode, contestant_number, name_th, name_en, nickname, category, profile_image) VALUES (:stdcode, :number, :name_th, :name_en, :nickname, :category, :image)");
            $stmt->execute([
                'stdcode' => $stdcode, 'number' => $number, 'name_th' => $name_th, 'name_en' => $name_en, 'nickname' => $nickname, 'category' => $category, 'image' => $image_name
            ]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มผู้เข้าประกวดสำเร็จ']);
        } 
        else {
            // แก้ไขข้อมูล
            if (empty($id)) throw new Exception('ไม่พบ ID ผู้เข้าประกวด');
            
            if ($image_name) {
                $stmt = $pdo->prepare("UPDATE rma_contestants SET stdcode = :stdcode, contestant_number = :number, name_th = :name_th, name_en = :name_en, nickname = :nickname, category = :category, profile_image = :image WHERE id = :id");
                $stmt->execute(['stdcode' => $stdcode, 'number' => $number, 'name_th' => $name_th, 'name_en' => $name_en, 'nickname' => $nickname, 'category' => $category, 'image' => $image_name, 'id' => $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE rma_contestants SET stdcode = :stdcode, contestant_number = :number, name_th = :name_th, name_en = :name_en, nickname = :nickname, category = :category WHERE id = :id");
                $stmt->execute(['stdcode' => $stdcode, 'number' => $number, 'name_th' => $name_th, 'name_en' => $name_en, 'nickname' => $nickname, 'category' => $category, 'id' => $id]);
            }
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลสำเร็จ']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>