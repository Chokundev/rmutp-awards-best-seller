<?php
// ไฟล์: contestant/submit_slip.php
session_start();
require_once '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['is_contestant']) || $_SESSION['is_contestant'] !== true) {
    header('Location: login.php');
    exit;
}

$contestant_id = $_SESSION['contestant_id'];

// เพื่อใช้ใน navbar.php
try {
    $stmt = $pdo->prepare("SELECT * FROM rma_contestants WHERE id = :id");
    $stmt->execute(['id' => $contestant_id]);
    $contestant = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}


// ดึงสถานะระบบปัจจุบัน
try {
    $stmt_setting = $pdo->query("SELECT setting_value FROM rma_settings WHERE setting_key = 'system_status'");
    $system_status = $stmt_setting->fetchColumn();

    if ($system_status === 'closed') {
        $_SESSION['alert_message'] = "ขณะนี้ระบบปิดรับการแจ้งหลักฐานการขายแล้ว";
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    // ข้ามการแจ้งเตือนหากไม่มีตาราง
}

$message = '';
$status = '';
$inserted_slip_id = null; // ตัวแปรสำหรับเก็บ ID ของสลิปที่เพิ่งบันทึกสำเร็จ

// จัดการเมื่อมีการส่งฟอร์ม (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_type = trim($_POST['product_type'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    // รับค่าตัวเลือกสินค้า (Checkbox จะส่งมาเป็น Array)
    $product_option_raw = $_POST['product_option'] ?? [];
    // แปลง Array ให้เป็น String คั่นด้วยลูกน้ำ
    $product_option = is_array($product_option_raw) ? implode(', ', $product_option_raw) : trim($product_option_raw);
    
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // รับค่าและจัดฟอร์แมตวันเวลา
    $slip_datetime_raw = trim($_POST['slip_datetime'] ?? '');
    $slip_datetime = str_replace('T', ' ', $slip_datetime_raw);
    if (strlen($slip_datetime) === 16) {
        $slip_datetime .= ':00'; // เติมวินาทีให้ครบ
    }
    
    // ตรวจสอบ Deadline สำหรับ "บัตรวิ่งติดมันส์" (ฝั่ง Backend)
    $is_deadline_passed = false;
    if ($product_type === 'บัตรวิ่งติดมันส์') {
        $deadline_timestamp = strtotime('2026-08-24 23:59:59'); // กำหนดเส้นตาย
        if (time() > $deadline_timestamp) {
            $is_deadline_passed = true;
        }
    }

    // ตรวจสอบความถูกต้อง (ดักเงื่อนไข Deadline ก่อน)
    if ($is_deadline_passed) {
        $status = 'error';
        $message = 'สิ้นสุดระยะเวลาการแจ้งยอด "บัตรวิ่งติดมันส์" แล้ว (หมดเขต 24 ส.ค. 2569 เวลา 23:59 น.)';
    }
    elseif (empty($product_type) || $quantity < 1 || empty($amount) || empty($slip_datetime_raw) || empty($product_option)) {
        $status = 'error';
        $message = 'กรุณากรอกข้อมูล ยอดเงิน วันเวลา และเลือกรายละเอียดสินค้าอย่างน้อย 1 รายการ';
    } 
    elseif (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['slip_image']['tmp_name'];
        $file_name = $_FILES['slip_image']['name'];
        $file_size = $_FILES['slip_image']['size'];
        
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 5242880) { // ไม่เกิน 5MB
                
                // สร้างค่า Hash (MD5) ของไฟล์ภาพที่อัปโหลดมา
                $image_hash = md5_file($file_tmp);
                
                $new_file_name = uniqid('slip_') . '_' . time() . '.' . $file_ext;
                $upload_path = '../assets/uploads/slips/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    try {
                        // ตรวจสอบสลิปซ้ำ (Duplicate Detection) ก่อนบันทึก
                        $stmt_check = $pdo->prepare("SELECT id FROM rma_slips WHERE image_hash = :hash LIMIT 1");
                        $stmt_check->execute(['hash' => $image_hash]);
                        $duplicate_slip = $stmt_check->fetch(PDO::FETCH_ASSOC);
                        
                        $slip_observation = null; // ค่าเริ่มต้น (ไม่มีข้อสังเกต)
                        
                        if ($duplicate_slip) {
                            $dup_id = str_pad($duplicate_slip['id'], 6, '0', STR_PAD_LEFT);
                            $slip_observation = "ระบบตรวจพบความเสี่ยง: สลิปนี้อาจซ้ำกับรายการ #" . $dup_id;
                        }

                        // บันทึกข้อมูลทั้งหมดลงฐานข้อมูล
                        $stmt = $pdo->prepare("INSERT INTO rma_slips (contestant_id, product_type, quantity, product_option, amount, slip_datetime, slip_image, image_hash, slip_observation, status) VALUES (:contestant_id, :product_type, :quantity, :product_option, :amount, :slip_datetime, :slip_image, :image_hash, :slip_observation, 'pending')");
                        $stmt->execute([
                            'contestant_id' => $contestant_id,
                            'product_type' => $product_type,
                            'quantity' => $quantity,
                            'product_option' => $product_option,
                            'amount' => $amount,
                            'slip_datetime' => $slip_datetime,
                            'slip_image' => $new_file_name,
                            'image_hash' => $image_hash,
                            'slip_observation' => $slip_observation
                        ]);
                        
                        $inserted_slip_id = $pdo->lastInsertId(); // เก็บ ID ที่เพิ่งบันทึกไป
                        $status = 'success';
                        $message = 'ส่งหลักฐานสำเร็จ! ระบบกำลังพาท่านไปหน้าตรวจสอบสถานะ...';
                    } catch (PDOException $e) {
                        $status = 'error';
                        $message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                    }
                } else {
                    $status = 'error';
                    $message = 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาลองใหม่อีกครั้ง';
                }
            } else {
                $status = 'error';
                $message = 'ขนาดไฟล์ใหญ่เกินไป (สูงสุด 5MB)';
            }
        } else {
            $status = 'error';
            $message = 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG) เท่านั้น';
        }
    } else {
        $status = 'error';
        $message = 'กรุณาเลือกไฟล์สลิปหลักฐาน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งยอดและแนบสลิป - RMUTP AWARDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } },
                    animation: { 'blob': 'blob 10s infinite alternate' },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '100%': { transform: 'translate(20px, -30px) scale(1.1)' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f8fafc; min-height: 100vh; overflow-x: hidden; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px -10px rgba(124, 58, 237, 0.1);
        }
        
        .fashion-watermark {
            position: fixed;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background-image: url('data:image/svg+xml;utf8,<svg width="400" height="150" xmlns="http://www.w3.org/2000/svg"><text x="0" y="50" font-family="Prompt, sans-serif" font-size="40" font-weight="900" fill="rgba(139, 92, 246, 0.03)" transform="rotate(-20)">RMUTP AWARDS</text></svg>');
            z-index: 0;
            pointer-events: none;
        }

        .upload-area { border: 2px dashed #a78bfa; transition: all 0.3s ease; }
        .upload-area:hover { background-color: #f5f3ff; border-color: #7c3aed; }
        .product-radio:checked + label { border-color: #7c3aed; background-color: #f5f3ff; box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.2); transform: translateY(-2px); }
        .product-radio:checked + label .check-icon { opacity: 1; transform: scale(1); }
        
        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body class="text-slate-800 pt-24 pb-10 relative antialiased">

    <div class="fashion-watermark"></div>
    <div class="fixed top-0 left-[-10%] w-[300px] h-[300px] bg-purple-300/40 rounded-full mix-blend-multiply filter blur-[80px] animate-blob z-0 pointer-events-none"></div>

    <?php include 'navbar.php'; ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="mb-6 flex justify-start" data-aos="fade-right">
            <a href="dashboard.php" class="flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 rounded-full shadow-sm hover:shadow-md transition-all border border-slate-200 font-medium text-sm group">
                <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                กลับหน้าหลัก
            </a>
        </div>

        <div data-aos="fade-up" data-aos-duration="800" class="glass-card rounded-[2.5rem] p-6 md:p-10 shadow-xl relative overflow-hidden">
            
            <div class="text-center mb-8 border-b border-slate-100 pb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-indigo-600 text-white rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-lg shadow-brand-500/30">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">แจ้งยอดและแนบสลิป</h2>
                <p class="text-slate-500 mt-1 font-medium">ระบุผลิตภัณฑ์และแนบหลักฐานการโอนเงินเพื่อสะสมยอด</p>
            </div>

            <form id="slipForm" action="" method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-4 uppercase tracking-wide">1. เลือกประเภทผลิตภัณฑ์หลัก</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <div class="relative h-full">
                            <input type="radio" id="prod_gq" name="product_type" value="CAMPUS BY GQ" class="product-radio hidden" required onchange="handleProductChange()">
                            <label for="prod_gq" class="cursor-pointer flex flex-col items-center p-4 border-2 border-slate-100 rounded-2xl transition-all duration-200 bg-white/60 hover:bg-white hover:border-brand-300 relative h-full">
                                <div class="check-icon absolute top-3 right-3 bg-brand-600 text-white rounded-full p-1 opacity-0 transform scale-50 transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <img src="../assets/images/product-gq.png" onerror="this.src='https://placehold.co/150x150/f8fafc/64748b?text=GQ'" alt="CAMPUS BY GQ" class="w-24 h-24 object-contain mb-3 drop-shadow-md">
                                <span class="font-bold text-slate-700 text-sm text-center leading-tight">ผลิตภัณฑ์<br>CAMPUS BY GQ</span>
                            </label>
                        </div>

                        <div class="relative h-full">
                            <input type="radio" id="prod_water" name="product_type" value="น้ำดื่ม RMUTP" class="product-radio hidden" onchange="handleProductChange()">
                            <label for="prod_water" class="cursor-pointer flex flex-col items-center p-4 border-2 border-slate-100 rounded-2xl transition-all duration-200 bg-white/60 hover:bg-white hover:border-brand-300 relative h-full">
                                <div class="check-icon absolute top-3 right-3 bg-brand-600 text-white rounded-full p-1 opacity-0 transform scale-50 transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <img src="../assets/images/product-water.png" onerror="this.src='https://placehold.co/150x150/f8fafc/64748b?text=Water'" alt="น้ำดื่ม RMUTP" class="w-24 h-24 object-contain mb-3 drop-shadow-md">
                                <span class="font-bold text-slate-700 text-sm text-center leading-tight">น้ำดื่มตรา<br>RMUTP</span>
                            </label>
                        </div>

                        <div class="relative h-full">
                            <input type="radio" id="prod_run" name="product_type" value="บัตรวิ่งติดมันส์" class="product-radio hidden" onchange="handleProductChange()">
                            <label for="prod_run" class="cursor-pointer flex flex-col items-center p-4 border-2 border-slate-100 rounded-2xl transition-all duration-200 bg-white/60 hover:bg-white hover:border-brand-300 relative h-full">
                                <div class="check-icon absolute top-3 right-3 bg-brand-600 text-white rounded-full p-1 opacity-0 transform scale-50 transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <img src="../assets/images/product-run.png" onerror="this.src='https://placehold.co/150x150/f8fafc/64748b?text=Run'" alt="บัตรวิ่งติดมันส์" class="w-24 h-24 object-contain mb-3 drop-shadow-md">
                                <span class="font-bold text-slate-700 text-sm text-center leading-tight">บัตรวิ่ง งานวิ่งติดมันส์<br>รันพระนคร ครั้งที่ 2</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="detailsSection" class="hidden bg-slate-50/80 p-6 rounded-[2rem] border border-slate-200">
                    <div class="flex flex-col gap-6">
                        
                        <div id="optionContainer" class="hidden">
                            <label id="optionLabel" class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wide">2. เลือกรายละเอียดสินค้า</label>
                            <div id="product_option_container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm"></div>
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">ระบุจำนวนรวมทั้งหมด (ชิ้น/ใบ)</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1" required
                                class="w-full md:w-1/2 px-5 py-3.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none font-bold text-slate-700 bg-white shadow-sm transition-all">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-slate-200 pt-8">
                    
                    <div class="space-y-6">
                        <div>
                            <label for="slip_datetime" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">วันและเวลาที่ทำรายการ (ตามสลิป)</label>
                            <input type="datetime-local" id="slip_datetime" name="slip_datetime" required
                                class="w-full px-5 py-3.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none font-medium text-slate-700 shadow-sm transition-all">
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">ยอดเงินในสลิปสุทธิ (บาท)</label>
                            <div class="relative shadow-sm rounded-xl">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <span class="text-brand-600 font-black text-lg">฿</span>
                                </div>
                                <input type="number" step="0.01" min="1" id="amount" name="amount" required placeholder="0.00"
                                    class="w-full pl-12 pr-5 py-3.5 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none font-black text-xl text-brand-700 transition-all">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">อัปโหลดรูปภาพสลิป</label>
                        <div class="upload-area rounded-[1.5rem] p-6 text-center cursor-pointer relative bg-white/80 h-[calc(100%-28px)] flex flex-col justify-center shadow-sm hover:shadow-md" onclick="document.getElementById('slip_image').click()">
                            <input type="file" id="slip_image" name="slip_image" accept="image/jpeg, image/png, image/jpg" required class="hidden" onchange="previewImage(this)">
                            
                            <div id="uploadPrompt" class="flex flex-col items-center justify-center">
                                <div class="w-14 h-14 bg-brand-50 text-brand-500 rounded-full flex items-center justify-center mb-3">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                </div>
                                <p class="text-sm text-slate-700 font-bold">แตะเพื่ออัปโหลดไฟล์รูปภาพ</p>
                                <p class="text-[11px] text-slate-400 mt-1 font-medium">รองรับเฉพาะไฟล์ .JPG, .PNG (สูงสุด 5MB)</p>
                            </div>

                            <div id="imagePreviewContainer" class="hidden h-full flex flex-col items-center justify-center">
                                <img id="imagePreview" src="" alt="Preview" class="mx-auto max-h-36 object-contain rounded-xl shadow-md border border-slate-200">
                                <p class="text-[10px] text-brand-600 mt-3 font-bold bg-brand-50 px-3 py-1.5 rounded-full border border-brand-100">แตะที่นี่เพื่อเปลี่ยนรูปภาพสลิป</p>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="w-full py-4 mt-4 bg-gradient-to-r from-brand-600 to-indigo-600 text-white rounded-2xl font-extrabold text-lg shadow-xl shadow-brand-500/30 hover:shadow-brand-500/50 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center">
                    <span id="btnText">ยืนยันการแจ้งยอดจำหน่าย</span>
                    <svg id="btnSpinner" class="animate-spin ml-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>

        <p class="mt-8 mb-4 text-xs text-slate-400 font-medium text-center uppercase tracking-widest">
            © 2026 RMUTP Student Organization.
        </p>
    </main>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // หมดเขต 24 ส.ค. 2026 เวลา 23:59 น. (ใช้ Timezone ไทย)
        const RUN_TICKET_DEADLINE = new Date('2026-08-24T23:59:59+07:00').getTime();

        // ฟังก์ชันสร้าง UI ของ Checkbox ตามผลิตภัณฑ์ที่เลือก
        function handleProductChange() {
            const selectedProduct = document.querySelector('input[name="product_type"]:checked').value;
            const detailsSection = document.getElementById('detailsSection');
            const optionContainer = document.getElementById('optionContainer');
            const optionDiv = document.getElementById('product_option_container');
            const optionLabel = document.getElementById('optionLabel');
            
            // 💥 อัปเดต: แสดง Alert ให้เห็นตั้งแต่คลิกเลือกเลย
            if (selectedProduct === 'บัตรวิ่งติดมันส์') {
                if (Date.now() > RUN_TICKET_DEADLINE) {
                    Swal.fire({
                        icon: 'error',
                        title: 'หมดเขตรับแจ้งยอด',
                        text: 'ผลิตภัณฑ์ "บัตรวิ่งติดมันส์" ปิดรับการแจ้งยอดแล้ว (ตั้งแต่ 24 ส.ค. 2569 เวลา 23:59 น.)',
                        confirmButtonColor: '#7c3aed',
                        customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5' }
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'แจ้งเตือนกำหนดการ',
                        html: 'ผลิตภัณฑ์ <b>บัตรวิ่งติดมันส์</b><br>สามารถแจ้งยอดได้ถึงวันที่ 24 ส.ค. 2569 เวลา 23:59 น.',
                        confirmButtonColor: '#7c3aed',
                        customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5' }
                    });
                }
            }

            detailsSection.classList.remove('hidden');
            optionContainer.classList.remove('hidden');
            optionDiv.innerHTML = ''; 

            let options = [];

            if (selectedProduct === 'CAMPUS BY GQ') {
                optionLabel.innerHTML = 'ประเภทสินค้า CAMPUS BY GQ <span class="text-[11px] text-brand-600 font-medium ml-1 normal-case">(เลือกได้มากกว่า 1)</span>';
                options = [
                    'MEN LONG SLEEVE SHIRT', 'MEN PANTS', 'MEN SHORT SLEEVE SHIRT',
                    'WOMEN PLEATED SKIRT', 'WOMEN SHORT SLEEVE SHIRT', 'WOMEN PENCIL SHORT'
                ];
            }
            else if (selectedProduct === 'น้ำดื่ม RMUTP') {
                optionLabel.innerHTML = 'ขนาดน้ำดื่ม <span class="text-[11px] text-brand-600 font-medium ml-1 normal-case">(เลือกได้มากกว่า 1)</span>';
                options = ['ขนาด 350 Ml', 'ขนาด 500 Ml'];
            } 
            else if (selectedProduct === 'บัตรวิ่งติดมันส์') {
                optionLabel.innerHTML = 'ประเภทบัตร/ระยะทาง <span class="text-[11px] text-brand-600 font-medium ml-1 normal-case">(เลือกได้มากกว่า 1)</span>';
                options = ['ระยะ 3 Km', 'ระยะ 6 Km','ระยะ 12 Km', 'บัตร VIP', 'บัตร VVIP'];
            }

            let checkboxesHTML = '';
            options.forEach(opt => {
                checkboxesHTML += `
                    <label class="flex items-center space-x-3 cursor-pointer p-3.5 bg-slate-50 hover:bg-brand-50 border border-slate-100 hover:border-brand-200 rounded-xl transition-all">
                        <input type="checkbox" name="product_option[]" value="${opt}" class="w-5 h-5 text-brand-600 rounded border-slate-300 focus:ring-brand-500 cursor-pointer">
                        <span class="text-slate-700 font-bold text-sm">${opt}</span>
                    </label>
                `;
            });
            optionDiv.innerHTML = checkboxesHTML;
        }

        // ฟังก์ชันตรวจสอบก่อนส่งฟอร์ม พร้อมแอนิเมชันปุ่มและแจ้งเตือน Loading
        document.getElementById('slipForm').addEventListener('submit', function(e) {
            const selectedProduct = document.querySelector('input[name="product_type"]:checked').value;
            const checkedOptions = document.querySelectorAll('input[name="product_option[]"]:checked');
            
            // บล็อกการส่งฟอร์มหากเลยเวลา Deadline สำหรับบัตรวิ่ง
            if (selectedProduct === 'บัตรวิ่งติดมันส์' && Date.now() > RUN_TICKET_DEADLINE) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถทำรายการได้',
                    text: 'ไม่สามารถส่งหลักฐาน "บัตรวิ่งติดมันส์" ได้ เนื่องจากเลยเวลาที่กำหนดแล้ว',
                    confirmButtonColor: '#7c3aed',
                    customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5' }
                });
                return;
            }

            // ตรวจสอบความถูกต้องของข้อมูล
            if (checkedOptions.length === 0) {
                e.preventDefault();
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'แจ้งเตือน', 
                    text: 'กรุณาเลือกรายละเอียดสินค้าอย่างน้อย 1 รายการ',
                    confirmButtonColor: '#7c3aed',
                    customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5' }
                });
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            submitBtn.classList.add('btn-loading');
            btnText.innerText = 'กำลังอัปโหลดสลิป...';
            btnSpinner.classList.remove('hidden');

            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                text: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });

        function previewImage(input) {
            const file = input.files[0];
            if (file) {
                if (file.size > 5242880) {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'ไฟล์ใหญ่เกินไป', 
                        text: 'กรุณาเลือกไฟล์ขนาดไม่เกิน 5MB',
                        confirmButtonColor: '#7c3aed',
                        customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5' }
                    });
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('uploadPrompt').classList.add('hidden');
                    const previewContainer = document.getElementById('imagePreviewContainer');
                    const previewImage = document.getElementById('imagePreview');
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        <?php if ($status === 'success' && $inserted_slip_id): ?>
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'กำลังพาท่านไปหน้าตรวจสอบสถานะ...',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = 'slip_status.php?id=<?php echo $inserted_slip_id; ?>';
            });
        <?php elseif ($status === 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#7c3aed',
                customClass: { confirmButton: 'rounded-xl font-bold px-6 py-2.5 bg-brand-600 hover:bg-brand-700' }
            });
        <?php endif; ?>
    </script>
</body>
</html>