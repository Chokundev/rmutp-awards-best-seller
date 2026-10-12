<?php
// ไฟล์: contestant/slip_status.php
session_start();
require_once '../config.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['is_contestant']) || $_SESSION['is_contestant'] !== true) {
    header('Location: login.php');
    exit;
}

$contestant_id = $_SESSION['contestant_id'];
$slip_id = $_GET['id'] ?? '';

if (empty($slip_id)) {
    // ถ้าไม่มีการส่ง ID มา ให้กลับไปหน้าประวัติ
    header('Location: history.php');
    exit;
}

try {
    // 2. ดึงข้อมูลผู้เข้าประกวด (สำหรับใช้ใน navbar.php)
    $stmt_user = $pdo->prepare("SELECT * FROM rma_contestants WHERE id = :id");
    $stmt_user->execute(['id' => $contestant_id]);
    $contestant = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // 3. ดึงข้อมูลสลิป โดยต้องมั่นใจว่าเป็นสลิปของคนนี้จริงๆ (Security)
    $stmt_slip = $pdo->prepare("SELECT * FROM rma_slips WHERE id = :slip_id AND contestant_id = :contestant_id");
    $stmt_slip->execute([
        'slip_id' => $slip_id,
        'contestant_id' => $contestant_id
    ]);
    $slip = $stmt_slip->fetch(PDO::FETCH_ASSOC);

    if (!$slip) {
        // ไม่พบข้อมูล หรือไม่ใช่สลิปของตัวเอง
        $_SESSION['alert_message'] = "ไม่พบข้อมูลรายการที่ท่านต้องการ";
        header('Location: history.php');
        exit;
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

$profile_image = !empty($contestant['profile_image']) ? '../assets/uploads/profiles/' . htmlspecialchars($contestant['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($contestant['name_th']) . '&background=8b5cf6&color=fff';
$status = $slip['status']; // 'pending', 'approved', 'rejected'
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะรายการ - RMUTP AWARDS</title>
    
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
    
    <style>
        body { background-color: #f8fafc; min-height: 100vh; overflow-x: hidden; }
        
        .fashion-watermark {
            position: fixed;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background-image: url('data:image/svg+xml;utf8,<svg width="400" height="150" xmlns="http://www.w3.org/2000/svg"><text x="0" y="50" font-family="Prompt, sans-serif" font-size="40" font-weight="900" fill="rgba(139, 92, 246, 0.03)" transform="rotate(-20)">RMUTP AWARDS</text></svg>');
            z-index: 0; pointer-events: none;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px -10px rgba(124, 58, 237, 0.1);
        }

        /* เส้นไทม์ไลน์ */
        .timeline-line {
            position: absolute;
            left: 1.25rem; /* กึ่งกลางไอคอน (w-10 = 2.5rem / 2 = 1.25) */
            top: 2.5rem;
            bottom: -1rem;
            width: 2px;
            background-color: #e2e8f0;
            z-index: 0;
        }
        .timeline-line.active { background-color: #10b981; }
    </style>
</head>
<body class="text-slate-800 pt-24 pb-10 relative antialiased">

    <div class="fashion-watermark"></div>
    <div class="fixed top-0 right-[-10%] w-[300px] h-[300px] bg-purple-300/40 rounded-full mix-blend-multiply filter blur-[80px] animate-blob z-0 pointer-events-none"></div>

    <!-- เรียกใช้ Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- เมนูลัดด้านบน -->
        <div class="mb-6 flex justify-between items-center" data-aos="fade-down">
            <a href="history.php" class="flex items-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 rounded-full shadow-sm hover:shadow-md transition-all border border-slate-200 font-medium text-sm group">
                <svg class="w-4 h-4 mr-1.5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                กลับหน้าประวัติ
            </a>
            <a href="submit_slip.php" class="flex items-center px-5 py-2.5 bg-brand-50 hover:bg-brand-100 text-brand-600 rounded-full shadow-sm transition-all font-bold text-sm border border-brand-200">
                แจ้งยอดรายการใหม่
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- ================= ฝั่งซ้าย: ข้อมูลสลิป (พื้นที่ 7 ส่วน) ================= -->
            <div class="lg:col-span-7 glass-card rounded-[2rem] p-6 md:p-8 shadow-xl relative overflow-hidden h-full" data-aos="fade-up" data-aos-duration="800">
                
                <div class="flex items-center gap-4 mb-6 border-b border-slate-100 pb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-brand-500 to-indigo-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-brand-500/30 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">สรุปรายละเอียดการแจ้งยอด</h2>
                        <p class="text-xs text-slate-500 mt-0.5">รายการหมายเลข: #<?php echo str_pad($slip['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- ข้อมูลผลิตภัณฑ์ -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-start gap-4">
                        <div class="w-14 h-14 bg-slate-50 rounded-xl p-2 border border-slate-100 shrink-0">
                            <?php 
                                $prodImg = 'https://placehold.co/150x150/f8fafc/64748b?text=Product';
                                if ($slip['product_type'] === 'CAMPUS BY GQ') $prodImg = '../assets/images/product-gq.png';
                                if ($slip['product_type'] === 'น้ำดื่ม RMUTP') $prodImg = '../assets/images/product-water.png';
                                if ($slip['product_type'] === 'บัตรวิ่งติดมันส์') $prodImg = '../assets/images/product-run.png';
                            ?>
                            <img src="<?php echo $prodImg; ?>" alt="Product" class="w-full h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">ผลิตภัณฑ์หลัก</p>
                            <p class="font-extrabold text-slate-800 text-lg mb-2"><?php echo htmlspecialchars($slip['product_type']); ?></p>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <ul class="space-y-1 text-sm font-medium text-slate-600">
                                    <?php 
                                    if(!empty($slip['product_option'])){
                                        $opts = explode(', ', $slip['product_option']);
                                        foreach($opts as $opt) {
                                            echo '<li class="flex items-center"><svg class="w-4 h-4 mr-2 text-brand-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . htmlspecialchars($opt) . '</li>';
                                        }
                                    } else {
                                        echo '<li>ไม่มีข้อมูลเพิ่มเติม</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ยอดเงินและจำนวน -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">จำนวนรวม</p>
                            <p class="text-2xl font-black text-slate-800"><?php echo $slip['quantity']; ?> <span class="text-sm font-bold text-slate-500">รายการ</span></p>
                        </div>
                        <div class="bg-brand-50 p-5 rounded-2xl border border-brand-100 shadow-sm text-center">
                            <p class="text-[10px] font-bold text-brand-500 uppercase tracking-widest mb-1">ยอดเงินสุทธิ</p>
                            <p class="text-2xl font-black text-brand-700 tracking-tighter">฿<?php echo number_format($slip['amount'], 2); ?></p>
                        </div>
                    </div>

                    <!-- รูปภาพสลิป -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">หลักฐานการโอนเงิน</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <?php echo date('d/m/Y H:i', strtotime($slip['slip_datetime'])); ?> น.
                            </p>
                        </div>
                        <div class="w-full h-48 bg-slate-50 rounded-xl overflow-hidden border border-slate-100 relative group cursor-pointer" onclick="document.getElementById('fullSlipModal').classList.remove('hidden')">
                            <img src="../assets/uploads/slips/<?php echo htmlspecialchars($slip['slip_image']); ?>" class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105" alt="Slip">
                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                                <span class="bg-white text-slate-800 font-bold px-4 py-2 rounded-full text-xs shadow-lg">คลิกเพื่อขยายรูป</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= ฝั่งขวา: Timeline สถานะ (พื้นที่ 5 ส่วน) ================= -->
            <div class="lg:col-span-5 glass-card rounded-[2rem] p-6 md:p-8 shadow-xl relative" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                <h3 class="text-lg font-extrabold text-slate-800 tracking-tight mb-6 uppercase border-b border-slate-100 pb-4">ไทม์ไลน์สถานะ</h3>
                
                <div class="relative pl-2 pt-2">
                    
                    <!-- เส้นเชื่อมตรงกลาง (เปลี่ยนสีตามสถานะ) -->
                    <div class="timeline-line <?php echo ($status !== 'pending') ? 'active' : ''; ?>"></div>

                    <!-- Step 1: สร้างรายการ (เสร็จเสมอ) -->
                    <div class="flex items-start mb-10 relative z-10">
                        <div class="w-10 h-10 rounded-full bg-emerald-500 border-4 border-white shadow-md flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div class="ml-4 mt-1">
                            <p class="font-bold text-slate-800">ส่งข้อมูลเข้าระบบสำเร็จ</p>
                            <p class="text-xs text-slate-500 font-medium"><?php echo date('d/m/Y H:i', strtotime($slip['created_at'])); ?> น.</p>
                        </div>
                    </div>

                    <!-- Step 2: ตรวจสอบ -->
                    <div class="flex items-start mb-10 relative z-10">
                        <?php if ($status === 'pending'): ?>
                            <div class="w-10 h-10 rounded-full bg-amber-400 border-4 border-white shadow-md flex items-center justify-center shrink-0 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-50"></span>
                                <svg class="w-5 h-5 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="ml-4 mt-1">
                                <p class="font-bold text-amber-600">อยู่ระหว่างรอการตรวจสอบ</p>
                                <p class="text-xs text-slate-500 font-medium mt-1">แอดมินกำลังดำเนินการตรวจสอบข้อมูล<br>และหลักฐานการโอนเงินของท่าน</p>
                            </div>
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-emerald-500 border-4 border-white shadow-md flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="ml-4 mt-1">
                                <p class="font-bold text-slate-800">การตรวจสอบเสร็จสิ้น</p>
                                <p class="text-xs text-slate-500 font-medium">ข้อมูลถูกตรวจสอบโดยแอดมินแล้ว</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Step 3: ผลการอนุมัติ -->
                    <div class="flex items-start relative z-10">
                        <?php if ($status === 'pending'): ?>
                            <div class="w-10 h-10 rounded-full bg-slate-200 border-4 border-white flex items-center justify-center shrink-0">
                                <div class="w-2 h-2 bg-slate-400 rounded-full"></div>
                            </div>
                            <div class="ml-4 mt-2">
                                <p class="font-bold text-slate-400">ผลการอนุมัติยอดขาย</p>
                            </div>
                        <?php elseif ($status === 'approved'): ?>
                            <div class="w-10 h-10 rounded-full bg-brand-500 border-4 border-brand-100 shadow-md flex items-center justify-center shrink-0 shadow-brand-500/40">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="ml-4 mt-1">
                                <p class="font-extrabold text-brand-600 text-lg">อนุมัติสำเร็จ 🎉</p>
                                <p class="text-xs text-slate-600 font-medium mt-1">ยอดขายของท่านถูกนับเข้าสู่กระดาน<br>ผู้นำ (Leaderboard) เรียบร้อยแล้ว</p>
                            </div>
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-rose-500 border-4 border-rose-100 shadow-md flex items-center justify-center shrink-0 shadow-rose-500/40">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <div class="ml-4 mt-1">
                                <p class="font-extrabold text-rose-600 text-lg">ไม่ผ่านการอนุมัติ</p>
                                <p class="text-xs text-slate-600 font-medium mt-1">สลิปของท่านถูกปฏิเสธ กรุณาตรวจสอบ<br>ความถูกต้องหรือทำรายการใหม่</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>
        
        <p class="mt-8 mb-4 text-xs text-slate-400 font-medium text-center uppercase tracking-widest">
            © 2026 RMUTP Student Organization.
        </p>
    </main>

    <!-- รูปสลิปขยายเต็มจอ (Modal) -->
    <div id="fullSlipModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[60] hidden flex-col items-center justify-center p-4">
        <button onclick="document.getElementById('fullSlipModal').classList.add('hidden')" class="absolute top-6 right-6 text-white hover:text-rose-400 bg-white/10 rounded-full p-2 transition-colors">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img src="../assets/uploads/slips/<?php echo htmlspecialchars($slip['slip_image']); ?>" class="max-h-[85vh] max-w-full object-contain rounded-xl shadow-2xl border border-white/10" alt="Full Slip">
    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });
    </script>
</body>
</html>