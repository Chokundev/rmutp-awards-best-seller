<?php
// ไฟล์: contestant/dashboard.php
session_start();
require_once '../config.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['is_contestant']) || $_SESSION['is_contestant'] !== true) {
    header('Location: login.php');
    exit;
}

$contestant_id = $_SESSION['contestant_id'];

try {
    // 2. ดึงข้อมูลผู้เข้าประกวดปัจจุบัน
    $stmt = $pdo->prepare("SELECT * FROM rma_contestants WHERE id = :id");
    $stmt->execute(['id' => $contestant_id]);
    $contestant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contestant) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // 3. ดึงยอดรวมการขายที่ได้รับการตรวจสอบแล้ว
    $stmt_sales = $pdo->prepare("SELECT SUM(amount) AS total_approved FROM rma_slips WHERE contestant_id = :id AND status = 'approved'");
    $stmt_sales->execute(['id' => $contestant_id]);
    $sales_data = $stmt_sales->fetch(PDO::FETCH_ASSOC);
    $total_approved = $sales_data['total_approved'] ? $sales_data['total_approved'] : 0;

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

$profile_image = !empty($contestant['profile_image']) ? '../assets/uploads/profiles/' . htmlspecialchars($contestant['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($contestant['name_th']) . '&background=8b5cf6&color=fff&size=256';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($contestant['name_th']); ?></title>
    
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
        body { background-color: #f8fafc; overflow-x: hidden; }
        
        /* ลายน้ำแฟชั่น */
        .fashion-watermark {
            position: fixed;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background-image: url('data:image/svg+xml;utf8,<svg width="400" height="150" xmlns="http://www.w3.org/2000/svg"><text x="0" y="50" font-family="Prompt, sans-serif" font-size="40" font-weight="900" fill="rgba(139, 92, 246, 0.03)" transform="rotate(-20)">RMUTP AWARDS</text></svg>');
            z-index: 0;
            pointer-events: none;
        }

        /* สไตล์กล่องกระจกสำหรับยอดขายและปุ่ม */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px -10px rgba(124, 58, 237, 0.1);
        }

        /* สไตล์บัตรดิจิทัล VIP Pass */
        .vip-pass {
            background: linear-gradient(135deg, #1e1b4b 0%, #4c1d95 50%, #7c3aed 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(76, 29, 149, 0.4);
        }
        .vip-pass::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
            animation: rotate 15s linear infinite;
            pointer-events: none;
        }
        @keyframes rotate { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-slate-800 pt-20 pb-10 relative antialiased">

    <!-- ลายน้ำแฟชั่นและแสงออร่า -->
    <div class="fashion-watermark"></div>
    <div class="fixed top-0 left-[-10%] w-[300px] h-[300px] bg-purple-300/40 rounded-full mix-blend-multiply filter blur-[80px] animate-blob z-0 pointer-events-none"></div>

    <!-- เรียกใช้ Navbar -->
    <?php include 'navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 mt-6">
        
        <?php if(isset($_SESSION['alert_message'])): ?>
            <div class="mb-6 p-4 bg-white border-l-4 border-brand-500 rounded-r-2xl shadow-sm flex items-center" data-aos="fade-down">
                <svg class="w-6 h-6 text-brand-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-sm font-medium text-slate-700">
                    <?php echo $_SESSION['alert_message']; unset($_SESSION['alert_message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================= โครงสร้าง Grid ซ้าย-ขวา ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch" data-aos="fade-up" data-aos-duration="800">
            
            <!-- ====== ฝั่งซ้าย: บัตร VIP Pass (พื้นที่ 5 ส่วน) ====== -->
            <div class="lg:col-span-5 h-full">
                <div class="vip-pass rounded-[2.5rem] p-8 flex flex-col text-white justify-between h-full min-h-[450px]">
                    
                    <!-- โลโก้และแถบสถานะ -->
                    <div class="flex justify-between items-start relative z-10 mb-6 shrink-0">
                        <div class="bg-white/20 backdrop-blur-sm px-4 py-1.5 rounded-full border border-white/30 text-[10px] sm:text-xs font-bold tracking-widest uppercase shadow-sm">
                            Official Candidate
                        </div>
                        <img src="../assets/images/logo.png" alt="Logo" class="w-12 h-12 object-contain filter brightness-0 invert opacity-90" onerror="this.style.display='none'">
                    </div>
                    
                    <!-- รูปโปรไฟล์และชื่อ -->
                    <div class="flex flex-col items-center justify-center relative z-10 my-auto flex-1">
                        <div class="w-36 h-36 sm:w-44 sm:h-44 rounded-full border-4 border-white/90 shadow-2xl overflow-hidden bg-slate-100 mb-5 ring-4 ring-brand-400/30 shrink-0">
                            <img src="<?php echo $profile_image; ?>" alt="Profile" class="w-full h-full object-cover">
                        </div>
                        
                        <div class="text-center w-full px-2">
                            <h2 class="text-2xl sm:text-3xl font-black tracking-tight mb-1 drop-shadow-md break-words leading-tight">
                                <?php echo htmlspecialchars($contestant['name_th']); ?>
                            </h2>
                            <p class="text-brand-200 font-medium text-sm sm:text-base tracking-wide">
                                น้อง<?php echo htmlspecialchars($contestant['nickname']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- ส่วนหมวดหมู่และหมายเลข -->
                    <div class="relative z-10 flex items-end justify-between pt-6 border-t border-white/20 shrink-0 mt-6">
                        <div class="flex-1 pr-2">
                            <p class="text-[10px] sm:text-xs text-brand-300 uppercase tracking-widest mb-1">Category</p>
                            <p class="font-bold text-sm sm:text-base leading-tight whitespace-normal break-words"><?php echo htmlspecialchars($contestant['category']); ?></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] sm:text-xs text-brand-300 uppercase tracking-widest mb-1">Candidate No.</p>
                            <p class="font-black text-3xl sm:text-4xl leading-none text-transparent bg-clip-text bg-gradient-to-br from-white to-brand-200"><?php echo htmlspecialchars($contestant['contestant_number']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== ฝั่งขวา: ยอดขาย และ ปุ่มทำรายการ (พื้นที่ 7 ส่วน) ====== -->
            <div class="lg:col-span-7 flex flex-col gap-6 lg:gap-8 h-full justify-between">
                
                <!-- สรุปยอดขายรวม -->
                <div class="glass-card rounded-[2.5rem] p-8 md:p-10 flex flex-col sm:flex-row items-center justify-between text-center sm:text-left shadow-lg border-b-4 border-brand-500 h-auto sm:h-full">
                    <div class="mb-4 sm:mb-0">
                        <div class="inline-flex items-center gap-2 mb-3 bg-brand-50 text-brand-600 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest border border-brand-100">
                            ยอดจำหน่ายสะสมของคุณ
                        </div>
                        <div class="flex items-baseline justify-center sm:justify-start gap-2">
                            <span class="text-3xl font-bold text-slate-400">฿</span>
                            <span class="text-5xl lg:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-600 to-indigo-600 tracking-tighter drop-shadow-sm break-words">
                                <?php echo number_format($total_approved, 2); ?>
                            </span>
                        </div>
                    </div>
                    <!-- โล่รางวัล -->
                    <div class="w-24 h-24 lg:w-32 lg:h-32 rounded-full flex items-center justify-center bg-gradient-to-br <?php echo ($total_approved > 0) ? 'from-yellow-300 to-yellow-500 shadow-yellow-500/30' : 'from-slate-200 to-slate-300 shadow-slate-300/30'; ?> shadow-xl transform rotate-12 shrink-0">
                        <svg class="w-12 h-12 lg:w-16 lg:h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    </div>
                </div>

                <!-- ปุ่มการทำงานหลัก (Original Layout) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 h-auto sm:h-full">
                    
                    <!-- ปุ่มแนบสลิป -->
                    <a href="submit_slip.php" class="glass-card rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center hover:bg-white hover:border-brand-200 transition-all duration-300 transform hover:-translate-y-1 active:scale-95 group shadow-md hover:shadow-xl">
                        <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-indigo-600 text-white rounded-2xl mx-auto flex items-center justify-center mb-5 shadow-lg shadow-brand-500/30 group-hover:scale-110 transition-transform duration-300 shrink-0">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-800 mb-1">แจ้งยอดและแนบสลิป</h4>
                        <p class="text-sm text-slate-500">ส่งหลักฐานเพื่อสะสมยอดแข่งขัน</p>
                    </a>

                    <!-- ปุ่มประวัติการส่ง -->
                    <a href="history.php" class="glass-card rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center hover:bg-white hover:border-blue-200 transition-all duration-300 transform hover:-translate-y-1 active:scale-95 group shadow-md hover:shadow-xl">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-cyan-500 text-white rounded-2xl mx-auto flex items-center justify-center mb-5 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300 shrink-0">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-slate-800 mb-1">ประวัติการส่งสลิป</h4>
                        <p class="text-sm text-slate-500">ตรวจสอบสถานะการอนุมัติของคุณ</p>
                    </a>
                    
                </div>
            </div>

        </div>

    </main>

    <!-- สคริปต์ AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ once: true, offset: 50 });</script>

</body>
</html>