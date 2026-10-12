<?php
// ไฟล์: contestant/history.php
session_start();
require_once '../config.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['is_contestant']) || $_SESSION['is_contestant'] !== true) {
    header('Location: login.php');
    exit;
}

$contestant_id = $_SESSION['contestant_id'];

try {
    // ดึงข้อมูลผู้เข้าประกวดปัจจุบัน (สำหรับแสดงใน navbar.php)
    $stmt_user = $pdo->prepare("SELECT * FROM rma_contestants WHERE id = :id");
    $stmt_user->execute(['id' => $contestant_id]);
    $contestant = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // 2. ดึงข้อมูลประวัติการส่งสลิป
    $stmt = $pdo->prepare("SELECT id, product_type, quantity, product_option, amount, slip_datetime, slip_image, status, created_at FROM rma_slips WHERE contestant_id = :id ORDER BY created_at DESC");
    $stmt->execute(['id' => $contestant_id]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันสำหรับป้ายสถานะ
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-xs font-bold shadow-sm border border-amber-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> รอตรวจสอบ</span>';
        case 'approved':
            return '<span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold shadow-sm border border-emerald-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> อนุมัติแล้ว</span>';
        case 'rejected':
            return '<span class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-full text-xs font-bold shadow-sm border border-rose-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ไม่ผ่าน</span>';
        default:
            return '<span class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-full text-xs font-bold shadow-sm border border-slate-200 w-fit mx-auto">ไม่ทราบสถานะ</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการแจ้งยอด - RMUTP AWARDS</title>
    
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
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; min-height: 100vh; overflow-x: hidden; }
        
        /* ลายน้ำแฟชั่น */
        .fashion-watermark {
            position: fixed;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background-image: url('data:image/svg+xml;utf8,<svg width="400" height="150" xmlns="http://www.w3.org/2000/svg"><text x="0" y="50" font-family="Prompt, sans-serif" font-size="40" font-weight="900" fill="rgba(139, 92, 246, 0.03)" transform="rotate(-20)">RMUTP AWARDS</text></svg>');
            z-index: 0;
            pointer-events: none;
        }

        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 40px -10px rgba(124, 58, 237, 0.1); }
        .modal-active { display: flex !important; }
    </style>
</head>
<body class="text-slate-800 pt-24 pb-10 relative antialiased">

    <!-- ลายน้ำแฟชั่นและแอนิเมชันพื้นหลัง -->
    <div class="fashion-watermark"></div>
    <div class="fixed top-0 right-[-10%] w-[300px] h-[300px] bg-purple-300/40 rounded-full mix-blend-multiply filter blur-[80px] animate-blob z-0 pointer-events-none"></div>
    <div class="fixed bottom-[-10%] left-[-10%] w-[300px] h-[300px] bg-indigo-300/40 rounded-full mix-blend-multiply filter blur-[80px] animate-blob z-0 pointer-events-none" style="animation-delay: 2s;"></div>

    <!-- เรียกใช้ Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- เมนูลัดด้านบน -->
        <div class="flex justify-between items-center mb-6" data-aos="fade-down">
            <a href="dashboard.php" class="flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 rounded-full shadow-sm hover:shadow-md transition-all border border-slate-200 font-medium text-sm group">
                <svg class="w-4 h-4 mr-1.5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                กลับหน้าหลัก
            </a>
            <a href="submit_slip.php" class="flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white rounded-full shadow-md hover:shadow-lg transition-all font-bold text-sm transform hover:-translate-y-0.5">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                แจ้งยอดใหม่
            </a>
        </div>

        <!-- กล่องตารางข้อมูล -->
        <div data-aos="fade-up" data-aos-duration="800" class="glass-panel rounded-[2rem] p-6 md:p-8 shadow-xl relative overflow-hidden">
            <div class="mb-6 border-b border-slate-100 pb-4 flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 text-brand-600 rounded-xl flex items-center justify-center shadow-sm shrink-0 border border-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">ประวัติการแจ้งยอดจำหน่าย</h2>
                    <p class="text-xs md:text-sm text-slate-500 mt-1 font-medium">คลิก "ดูรายละเอียด" ที่รายการเพื่อตรวจสอบข้อมูลสลิปที่แนบไว้</p>
                </div>
            </div>

            <?php if (count($slips) > 0): ?>
                <div class="overflow-x-auto rounded-[1.5rem] border border-slate-100 shadow-sm">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 text-[11px] md:text-xs uppercase tracking-wider font-bold">
                                <th class="p-4 rounded-tl-[1.5rem] text-center w-16">ลำดับ</th>
                                <th class="p-4">วันและเวลาที่ทำรายการ (ตามสลิป)</th>
                                <th class="p-4">ผลิตภัณฑ์หลัก</th>
                                <th class="p-4 text-right">ยอดเงิน (฿)</th>
                                <th class="p-4 text-center">สถานะ</th>
                                <th class="p-4 text-center rounded-tr-[1.5rem] w-32">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white/60">
                            <?php 
                            $count = 1;
                            foreach ($slips as $slip): 
                                // จัดเตรียมข้อมูลเป็น JSON สำหรับส่งให้ JavaScript
                                $slipJson = htmlspecialchars(json_encode($slip), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr class="hover:bg-brand-50/50 transition-colors duration-200 group">
                                    <td class="p-4 text-center text-slate-400 font-bold"><?php echo $count++; ?></td>
                                    <td class="p-4">
                                        <p class="text-sm font-bold text-slate-800"><?php echo date('d/m/Y', strtotime($slip['slip_datetime'])); ?></p>
                                        <p class="text-xs text-brand-600 font-semibold"><?php echo date('H:i', strtotime($slip['slip_datetime'])); ?> น.</p>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-700 text-sm">
                                            <?php echo !empty($slip['product_type']) ? htmlspecialchars($slip['product_type']) : '-'; ?>
                                        </p>
                                    </td>
                                    <td class="p-4 text-right">
                                        <span class="font-black text-brand-700 text-lg">฿<?php echo number_format($slip['amount'], 2); ?></span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php echo getStatusBadge($slip['status']); ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button onclick="openDetailsModal(<?php echo $slipJson; ?>)" class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-brand-600 text-brand-600 hover:text-white text-xs font-bold rounded-xl transition-all border border-brand-200 shadow-sm transform hover:-translate-y-0.5 hover:shadow-md">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            ดูรายละเอียด
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- กรณีที่ยังไม่เคยส่งสลิปเลย -->
                <div class="text-center py-16 bg-white/40 rounded-[1.5rem] border-2 border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <p class="text-slate-700 font-extrabold text-xl">คุณยังไม่มีประวัติการแจ้งยอด</p>
                    <p class="text-slate-500 text-sm mt-2 mb-8">ส่งหลักฐานเพื่อสะสมยอดจำหน่ายและลุ้นรับรางวัล BEST SELLER</p>
                    <a href="submit_slip.php" class="inline-flex items-center px-8 py-3.5 bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold rounded-full transition-all shadow-lg shadow-brand-500/30 transform hover:-translate-y-1">
                        แจ้งยอดจำหน่ายครั้งแรก
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <p class="mt-8 mb-4 text-xs text-slate-400 font-medium text-center uppercase tracking-widest">
            © 2026 RMUTP Student Organization.
        </p>
    </main>

    <!-- Modal สำหรับแสดงรายละเอียด (ซ่อนไว้เป็นค่าเริ่มต้น) -->
    <div id="detailsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex-col items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-[2rem] w-full max-w-3xl shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden flex flex-col max-h-[90vh] border border-slate-100" id="modalContent">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-brand-100 text-brand-600 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-800">รายละเอียดการแจ้งยอด</h3>
                </div>
                <button onclick="closeDetailsModal()" class="text-slate-400 hover:text-rose-500 transition-colors p-2 bg-white rounded-full shadow-sm hover:shadow border border-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body (เลื่อนได้) -->
            <div class="p-6 md:p-8 overflow-y-auto flex-1 bg-slate-50/30">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- ฝั่งซ้าย: ข้อมูลสินค้า -->
                    <div class="space-y-6">
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">ผลิตภัณฑ์หลัก</p>
                            <p class="text-lg font-black text-slate-800" id="modalProductType"></p>
                        </div>
                        
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 mb-3 uppercase tracking-widest">รายละเอียดรายการที่เลือก</p>
                            <ul id="modalProductOptionList" class="space-y-2 text-sm font-bold text-brand-700"></ul>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                                <p class="text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">จำนวนรวม</p>
                                <p class="text-xl font-black text-slate-800"><span id="modalQuantity"></span> <span class="text-sm font-medium text-slate-500">รายการ</span></p>
                            </div>
                            <div class="bg-brand-50 p-4 rounded-2xl border border-brand-100 shadow-sm text-center">
                                <p class="text-xs font-bold text-brand-400 mb-1 uppercase tracking-widest">ยอดเงินสุทธิ</p>
                                <p class="text-xl font-black text-brand-700" id="modalAmount"></p>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">วันและเวลาที่โอนเงิน</p>
                            <p class="text-sm font-bold text-slate-700" id="modalSlipDatetime"></p>
                        </div>
                    </div>

                    <!-- ฝั่งขวา: รูปสลิป -->
                    <div class="flex flex-col items-center justify-start bg-white rounded-2xl p-5 border border-slate-100 shadow-sm h-full">
                        <p class="text-xs font-bold text-slate-400 mb-4 text-center w-full border-b border-slate-100 pb-3 uppercase tracking-widest">หลักฐานการโอนเงิน</p>
                        <div class="flex-1 w-full flex items-center justify-center bg-slate-50 rounded-xl overflow-hidden cursor-zoom-in group" onclick="zoomImage(document.getElementById('modalSlipImage').src)">
                            <img id="modalSlipImage" src="" alt="Slip Image" class="max-h-72 object-contain group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <p class="text-[10px] font-bold text-brand-600 mt-4 bg-brand-50 px-3 py-1.5 rounded-full uppercase tracking-widest">แตะที่รูปเพื่อขยาย</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-5 border-t border-slate-100 bg-white flex justify-between items-center">
                <div id="modalStatusContainer"></div>
                <button onclick="closeDetailsModal()" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-colors shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm">
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50 });

        const modal = document.getElementById('detailsModal');
        const modalContent = document.getElementById('modalContent');

        // ฟังก์ชันเปิด Modal พร้อมดึงข้อมูลมาแสดง
        function openDetailsModal(data) {
            document.getElementById('modalProductType').innerText = data.product_type || 'ไม่ระบุ';
            document.getElementById('modalQuantity').innerText = data.quantity;
            document.getElementById('modalAmount').innerText = '฿' + parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            const dt = new Date(data.slip_datetime);
            document.getElementById('modalSlipDatetime').innerText = dt.toLocaleDateString('th-TH') + ' เวลา ' + dt.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'}) + ' น.';
            
            document.getElementById('modalSlipImage').src = '../assets/uploads/slips/' + data.slip_image;

            const optionList = document.getElementById('modalProductOptionList');
            optionList.innerHTML = ''; 
            if (data.product_option) {
                const options = data.product_option.split(', ');
                options.forEach(opt => {
                    optionList.innerHTML += `<li class="flex items-center bg-slate-50 px-3 py-2 rounded-lg border border-slate-100"><svg class="w-4 h-4 mr-2 text-brand-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>${opt}</li>`;
                });
            } else {
                optionList.innerHTML = '<li class="text-slate-400 italic text-sm">ไม่มีตัวเลือกเสริม</li>';
            }

            const statusContainer = document.getElementById('modalStatusContainer');
            if(data.status === 'pending') {
                statusContainer.innerHTML = '<span class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg text-sm font-bold border border-amber-200 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> รอตรวจสอบ</span>';
            } else if(data.status === 'approved') {
                statusContainer.innerHTML = '<span class="px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-bold border border-emerald-200 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> อนุมัติสำเร็จ</span>';
            } else {
                statusContainer.innerHTML = '<span class="px-4 py-2 bg-rose-100 text-rose-700 rounded-lg text-sm font-bold border border-rose-200 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ไม่ผ่านการอนุมัติ</span>';
            }

            modal.classList.add('modal-active');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function closeDetailsModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.remove('modal-active');
            }, 300);
        }

        modal.addEventListener('click', function(e) {
            if (e.target === modal) { closeDetailsModal(); }
        });

        // ฟังก์ชันขยายรูป
        function zoomImage(src) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Slip Image',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    image: 'max-h-[80vh] object-contain rounded-xl',
                    popup: 'bg-transparent shadow-none'
                },
                backdrop: 'rgba(15,23,42,0.9)' // พื้นหลังสีเข้มเวลาซูม
            });
        }
    </script>
</body>
</html>