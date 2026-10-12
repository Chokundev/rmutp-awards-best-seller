<?php
// ไฟล์: admin/settings.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    // 1. ดึงจำนวนสลิปที่รอตรวจสอบ เพื่อนำไปแสดงใน Badge
    $stmt_pending = $pdo->query("SELECT COUNT(*) FROM rma_slips WHERE status = 'pending'");
    $total_pending = $stmt_pending->fetchColumn();

    // 2. ดึงสถานะระบบปัจจุบัน
    $stmt = $pdo->query("SELECT setting_value FROM rma_settings WHERE setting_key = 'system_status'");
    $system_status = $stmt->fetchColumn() ?: 'closed'; // ค่าเริ่มต้นเป็น closed หากหาไม่พบ

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - Admin RMUTP AWARDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' }
                    },
                    animation: { 'blob': 'blob 7s infinite' },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
        .sidebar-link { transition: all 0.2s ease-in-out; }
        .sidebar-link:hover { background-color: #f5f3ff; color: #7c3aed; padding-left: 1.5rem; }
        .sidebar-link.active { background-color: #7c3aed; color: white; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <!-- พื้นหลังตกแต่ง -->
    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0 animate-blob"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0 animate-blob animation-delay-2000"></div>

    <!-- ================= Sidebar ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- ================= Main Content ================= -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4" data-aos="fade-down">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">ตั้งค่าระบบ</h1>
                <p class="text-slate-500 mt-1">ควบคุมการเปิด-ปิดระบบรับแจ้งหลักฐานการจำหน่ายผลิตภัณฑ์</p>
            </div>
        </div>

        <!-- การ์ดตั้งค่าหลัก -->
        <div class="glass-panel rounded-[2rem] overflow-hidden shadow-xl max-w-4xl" data-aos="fade-up" data-aos-delay="100">
            
            <div class="p-8 md:p-10">
                <div class="flex items-center gap-4 mb-8 pb-6 border-b border-slate-100">
                    <div class="w-12 h-12 bg-brand-100 text-brand-600 rounded-xl flex items-center justify-center shadow-sm shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">สถานะการรับแจ้งยอดจำหน่าย</h2>
                        <p class="text-sm text-slate-500">จัดการช่วงเวลาการส่งหลักฐานการจำหน่ายของผู้เข้าประกวด</p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-8 items-center bg-slate-50/50 p-6 rounded-2xl border border-slate-200">
                    
                    <!-- ส่วนแสดงสถานะปัจจุบัน -->
                    <div class="flex-1 text-center md:text-left">
                        <p class="text-sm font-semibold text-slate-500 mb-2 uppercase tracking-wider">สถานะระบบปัจจุบัน</p>
                        
                        <?php if($system_status === 'open'): ?>
                            <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-2 rounded-xl border border-emerald-200 shadow-sm mb-3">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                <span class="font-bold text-lg">เปิดรับสลิปหลักฐาน</span>
                            </div>
                            <p class="text-sm text-slate-600">ผู้เข้าประกวดสามารถล็อกอินและส่งหลักฐานการจำหน่ายได้ตามปกติ</p>
                        <?php else: ?>
                            <div class="inline-flex items-center gap-2 bg-rose-100 text-rose-700 px-4 py-2 rounded-xl border border-rose-200 shadow-sm mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span class="font-bold text-lg">ปิดรับสลิปหลักฐาน</span>
                            </div>
                            <p class="text-sm text-slate-600">ผู้เข้าประกวดจะไม่สามารถส่งหลักฐานใหม่ได้ แต่ยังคงเข้าสู่ระบบเพื่อดูประวัติย้อนหลังได้</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ปุ่มจัดการเปิด-ปิด -->
                    <div class="flex flex-col gap-3 w-full md:w-auto">
                        <button onclick="updateSystemStatus('open')" 
                                class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold transition-all transform hover:-translate-y-0.5 <?php echo $system_status === 'open' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 cursor-default' : 'bg-white text-slate-600 border border-slate-200 hover:border-emerald-300 hover:text-emerald-600 hover:bg-emerald-50 shadow-sm'; ?>"
                                <?php echo $system_status === 'open' ? 'disabled' : ''; ?>>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                            เปิดระบบรับข้อมูล
                        </button>
                        
                        <button onclick="updateSystemStatus('closed')" 
                                class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold transition-all transform hover:-translate-y-0.5 <?php echo $system_status === 'closed' ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30 cursor-default' : 'bg-white text-slate-600 border border-slate-200 hover:border-rose-300 hover:text-rose-600 hover:bg-rose-50 shadow-sm'; ?>"
                                <?php echo $system_status === 'closed' ? 'disabled' : ''; ?>>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            ปิดระบบรับข้อมูล
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-slate-50 border-t border-slate-100 p-6 text-center">
                <p class="text-sm text-slate-500">
                    บริหารและจัดการระบบโดย <strong>องค์การนักศึกษา มหาวิทยาลัยเทคโนโลยีราชมงคลพระนคร</strong> ปีบริหาร 2569
                </p>
            </div>
        </div>

    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // ฟังก์ชันยืนยันและอัปเดตสถานะผ่าน API
        function updateSystemStatus(status) {
            const isOpening = status === 'open';
            const actionText = isOpening ? 'เปิดระบบรับสลิป' : 'ปิดระบบรับสลิป';
            const confirmColor = isOpening ? '#10b981' : '#f43f5e';
            const textDesc = isOpening 
                ? 'ผู้เข้าประกวดจะสามารถกรอกฟอร์มแจ้งยอดและแนบสลิปใหม่ได้ทันที' 
                : 'ผู้เข้าประกวดจะไม่สามารถส่งหลักฐานการจำหน่ายได้อีก (ใช้เมื่อสิ้นสุดระยะเวลาแคมเปญ)';
            
            Swal.fire({
                title: 'ยืนยันการเปลี่ยนสถานะ?',
                text: textDesc,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: `ยืนยันการ${actionText}`,
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    popup: 'rounded-[1.5rem]',
                    confirmButton: 'rounded-xl font-bold px-5',
                    cancelButton: 'rounded-xl font-bold px-5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('status', status);

                    fetch('api/settings_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ดำเนินการสำเร็จ',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { popup: 'rounded-[1.5rem]' }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: data.message,
                                customClass: { popup: 'rounded-[1.5rem]' }
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>