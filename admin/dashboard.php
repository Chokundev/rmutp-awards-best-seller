<?php
// ไฟล์: admin/dashboard.php
session_start();
require_once '../config.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    // =========================================================
    // ส่วนที่ 1: ดึงข้อมูลสถิติภาพรวม
    // =========================================================
    $total_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM rma_slips WHERE status = 'approved'")->fetchColumn();
    $total_pending = $pdo->query("SELECT COUNT(*) FROM rma_slips WHERE status = 'pending'")->fetchColumn();
    $total_contestants = $pdo->query("SELECT COUNT(*) FROM rma_contestants")->fetchColumn();
    $total_approved_slips = $pdo->query("SELECT COUNT(*) FROM rma_slips WHERE status = 'approved'")->fetchColumn();

    // =========================================================
    // ส่วนที่ 2: ดึงข้อมูล Live Feed แบบละเอียด (อัปเดตใหม่)
    // =========================================================
    $stmt_feed = $pdo->query("
        (
            SELECT 'login' AS activity_type, l.created_at, l.status, 
                   COALESCE(c.name_th, l.attempted_username) AS user_name, 
                   c.contestant_number, c.stdcode, l.ip_address,
                   'เข้าสู่ระบบ' AS action_detail,
                   NULL AS extra_info
            FROM rma_login_logs l
            LEFT JOIN rma_contestants c ON l.attempted_username = c.stdcode
        )
        UNION ALL
        (
            SELECT 'slip' AS activity_type, s.created_at, s.status, 
                   c.name_th AS user_name, 
                   c.contestant_number, c.stdcode, NULL AS ip_address,
                   CASE 
                        WHEN s.status = 'pending' THEN 'แจ้งยอด/ส่งหลักฐาน' 
                        WHEN s.status = 'approved' THEN 'ได้รับการอนุมัติยอด' 
                        ELSE 'สลิปถูกปฏิเสธ' 
                   END AS action_detail,
                   CONCAT(s.product_type, ' (ยอด ', s.amount, ' บาท)') AS extra_info
            FROM rma_slips s
            JOIN rma_contestants c ON s.contestant_id = c.id
        )
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $live_feed_data = $stmt_feed->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // ส่วนที่ 3: ข้อมูลสำหรับกราฟ (Charts Data)
    // =========================================================
    // กราฟรายได้ 7 วันย้อนหลัง
    $stmt_daily_sales = $pdo->query("SELECT DATE(created_at) as sale_date, SUM(amount) as daily_revenue FROM rma_slips WHERE status = 'approved' AND created_at >= DATE(NOW()) - INTERVAL 15 DAY GROUP BY DATE(created_at) ORDER BY sale_date ASC");
    $daily_sales_data = $stmt_daily_sales->fetchAll(PDO::FETCH_ASSOC);

    // กราฟสัดส่วนสินค้าหลัก (Product Type)
    $stmt_chart1 = $pdo->query("SELECT product_type, SUM(quantity) as total_qty FROM rma_slips WHERE status = 'approved' AND product_type != '' GROUP BY product_type");
    $chart1_data = $stmt_chart1->fetchAll(PDO::FETCH_ASSOC);

    // กราฟสัดส่วนสินค้าย่อย (Product Options)
    $stmt_opts = $pdo->query("SELECT product_option, quantity FROM rma_slips WHERE status = 'approved'");
    $detailed_options = [];
    while($r = $stmt_opts->fetch(PDO::FETCH_ASSOC)) {
        $opts = explode(', ', $r['product_option']);
        $q = (int)$r['quantity'];
        foreach($opts as $o) {
            $o = trim($o);
            if(empty($o)) continue;
            if(!isset($detailed_options[$o])) $detailed_options[$o] = 0;
            $detailed_options[$o] += $q;
        }
    }
    arsort($detailed_options); // เรียงจากมากไปน้อย

    // กราฟจำนวนคนขายได้แยกตามหมวดหมู่
    $stmt_chart2 = $pdo->query("SELECT c.category, COUNT(DISTINCT s.contestant_id) as contestant_count FROM rma_slips s JOIN rma_contestants c ON s.contestant_id = c.id WHERE s.status = 'approved' GROUP BY c.category");
    $chart2_data = $stmt_chart2->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // ส่วนที่ 4: Leaderboard
    // =========================================================
    $top5_data = ['MISTER' => [], 'MISS' => [], 'MISS SEASON' => [], 'MISS JUMBO QUEEN' => [], 'MISS CONGENIALITY' => []];
    $stmt_top = $pdo->query("SELECT c.category, c.contestant_number, c.stdcode, c.name_th, c.nickname, c.profile_image, SUM(s.amount) as total_sales FROM rma_contestants c JOIN rma_slips s ON c.id = s.contestant_id WHERE s.status = 'approved' GROUP BY c.id ORDER BY total_sales DESC");
    while($row = $stmt_top->fetch(PDO::FETCH_ASSOC)) {
        if(isset($top5_data[$row['category']]) && count($top5_data[$row['category']]) < 5) {
            $top5_data[$row['category']][] = $row;
        }
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดรวม - Admin RMUTP AWARDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } } }
            }
        }
    </script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
        .feed-scroll::-webkit-scrollbar { width: 6px; }
        .feed-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 6px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-8 relative z-10">
        
        <div class="flex justify-between items-end mb-6" data-aos="fade-down">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">แดชบอร์ดภาพรวมระบบ</h1>
                <p class="text-slate-500 mt-1">สถิติยอดจำหน่ายและฟีดความเคลื่อนไหว (Real-time)</p>
            </div>
            <div class="hidden sm:block text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold border border-emerald-200 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> ระบบออนไลน์
                </span>
            </div>
        </div>

        <!-- ================= Tier 1: Summary Cards ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6" data-aos="fade-up">
            <div class="glass-panel rounded-2xl p-5 flex flex-col relative overflow-hidden">
                <p class="text-xs font-bold text-slate-500 mb-1 uppercase">ยอดขายรวม (อนุมัติแล้ว)</p>
                <h3 class="text-2xl font-extrabold text-brand-700 tracking-tight">฿<?php echo number_format($total_sales, 2); ?></h3>
            </div>
            <div class="glass-panel rounded-2xl p-5 flex flex-col relative overflow-hidden">
                <p class="text-xs font-bold text-slate-500 mb-1 uppercase">รายการรอตรวจสอบ</p>
                <h3 class="text-2xl font-extrabold text-amber-600 tracking-tight"><?php echo number_format($total_pending); ?> <span class="text-xs font-medium text-slate-400">รายการ</span></h3>
            </div>
            <div class="glass-panel rounded-2xl p-5 flex flex-col relative overflow-hidden">
                <p class="text-xs font-bold text-slate-500 mb-1 uppercase">รายการที่อนุมัติแล้ว</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 tracking-tight"><?php echo number_format($total_approved_slips); ?> <span class="text-xs font-medium text-slate-400">รายการ</span></h3>
            </div>
            <div class="glass-panel rounded-2xl p-5 flex flex-col relative overflow-hidden">
                <p class="text-xs font-bold text-slate-500 mb-1 uppercase">ผู้เข้าประกวดทั้งหมด</p>
                <h3 class="text-2xl font-extrabold text-blue-600 tracking-tight"><?php echo number_format($total_contestants); ?> <span class="text-xs font-medium text-slate-400">คน</span></h3>
            </div>
        </div>

        <!-- ================= Tier 2: Live Activity Feed (ขยายใหญ่เต็มตา) ================= -->
        <div class="glass-panel rounded-[2rem] p-6 shadow-sm mb-6 flex flex-col" data-aos="fade-up">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                บันทึกความเคลื่อนไหวล่าสุด (Live Activity Feed)
            </h3>
            
            <div class="h-[300px] overflow-y-auto feed-scroll pr-2 space-y-3">
                <?php if(count($live_feed_data) > 0): ?>
                    <?php foreach($live_feed_data as $feed): 
                        
                        // จัดการข้อมูลเพื่อนำไปแสดงผล
                        $title = ''; $details = ''; $icon = ''; $color_class = ''; $bg_class = '';
                        
                        if ($feed['activity_type'] == 'login') {
                            if ($feed['contestant_number']) {
                                $title = "ผู้เข้าประกวด {$feed['contestant_number']} ({$feed['user_name']})";
                                $details = "รหัสนักศึกษา: {$feed['stdcode']} | จาก IP: {$feed['ip_address']}";
                            } else {
                                $title = "แอดมิน ({$feed['user_name']})";
                                $details = "จาก IP: {$feed['ip_address']}";
                            }
                            
                            $action_text = $feed['status'] == 'success' ? 'เข้าสู่ระบบสำเร็จ' : 'ความพยายามเข้าสู่ระบบ (ล้มเหลว)';
                            
                            $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>';
                            $color_class = $feed['status'] == 'success' ? 'text-blue-600' : 'text-rose-600';
                            $bg_class = $feed['status'] == 'success' ? 'bg-blue-100' : 'bg-rose-100';
                            
                        } else {
                            // กรณีจัดการสลิป
                            $title = "ผู้เข้าประกวด {$feed['contestant_number']} ({$feed['user_name']})";
                            
                            if ($feed['status'] == 'pending') {
                                $action_text = "แจ้งยอดใหม่: {$feed['extra_info']}";
                                $details = "สถานะ: <span class='text-amber-600 font-bold'>รอการตรวจสอบจากแอดมิน</span>";
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>';
                                $color_class = 'text-amber-600'; $bg_class = 'bg-amber-100';
                            } elseif ($feed['status'] == 'approved') {
                                $action_text = "อนุมัติยอด: {$feed['extra_info']}";
                                $details = "สถานะ: <span class='text-emerald-600 font-bold'>ตรวจสอบและอนุมัติโดย ผู้ดูแลระบบ</span>";
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
                                $color_class = 'text-emerald-600'; $bg_class = 'bg-emerald-100';
                            } else {
                                $action_text = "ปฏิเสธยอด: {$feed['extra_info']}";
                                $details = "สถานะ: <span class='text-rose-600 font-bold'>ตรวจสอบและปฏิเสธโดย ผู้ดูแลระบบ</span>";
                                $icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                                $color_class = 'text-rose-600'; $bg_class = 'bg-rose-100';
                            }
                        }
                    ?>
                        <div class="flex items-center gap-4 p-3.5 rounded-2xl bg-white border border-slate-100 hover:shadow-md transition-shadow group">
                            
                            <!-- ไอคอนบอกประเภท -->
                            <div class="w-12 h-12 rounded-xl <?php echo $bg_class; ?> <?php echo $color_class; ?> flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $icon; ?></svg>
                            </div>
                            
                            <!-- รายละเอียดหลัก -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-extrabold text-slate-800 truncate"><?php echo $action_text; ?></p>
                                <p class="text-xs font-medium text-slate-600 truncate mt-0.5"><?php echo $title; ?></p>
                                <p class="text-[11px] text-slate-400 mt-1"><?php echo $details; ?></p>
                            </div>
                            
                            <!-- เวลา -->
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-slate-700"><?php echo date('H:i', strtotime($feed['created_at'])); ?> น.</p>
                                <p class="text-[10px] font-medium text-slate-400"><?php echo date('d/m/Y', strtotime($feed['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-sm text-slate-400 py-8">ยังไม่มีความเคลื่อนไหวในระบบ</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= Tier 3: กราฟรายได้ 15 วัน ================= -->
        <div class="glass-panel rounded-[2rem] p-6 shadow-sm mb-6" data-aos="fade-up">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">แนวโน้มรายได้ 15 วันล่าสุด</h3>
            </div>
            <div class="w-full h-72 relative"><canvas id="revenueChart"></canvas></div>
        </div>

        <!-- ================= Tier 4: เจาะลึกผลิตภัณฑ์ (กราฟวงกลม) ================= -->
        <h2 class="text-lg font-bold text-slate-700 mb-4 flex items-center gap-2" data-aos="fade-up">
            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
            สัดส่วนการจำหน่ายผลิตภัณฑ์อย่างละเอียด
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" data-aos="fade-up">
            
            <!-- กราฟแบรนด์หลัก (Doughnut) -->
            <div class="glass-panel rounded-[2rem] p-6 shadow-sm flex flex-col">
                <h3 class="text-base font-bold text-slate-800 mb-1 text-center">ผลิตภัณฑ์หลัก (ชิ้น)</h3>
                <p class="text-xs text-slate-400 mb-4 text-center">ยอดขายแยกรวมตามหมวดหมู่หลัก</p>
                <div class="w-full flex-1 min-h-[260px] relative flex justify-center"><canvas id="productMainChart"></canvas></div>
            </div>

            <!-- กราฟรุ่นย่อย (Pie) -->
            <div class="glass-panel rounded-[2rem] p-6 shadow-sm flex flex-col">
                <h3 class="text-base font-bold text-slate-800 mb-1 text-center">ประเภทผลิตภัณฑ์ย่อย (ชิ้น)</h3>
                <p class="text-xs text-slate-400 mb-4 text-center">แสดงสินค้าย่อยที่ได้รับความนิยมสูงสุด</p>
                <div class="w-full flex-1 min-h-[260px] relative flex justify-center"><canvas id="productDetailChart"></canvas></div>
            </div>

        </div>

        <!-- ================= Tier 5: Performance & Leaderboard ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8" data-aos="fade-up">
            
            <!-- กราฟแท่งคนเข้าประกวด (1/3) -->
            <div class="glass-panel rounded-[2rem] p-6 shadow-sm flex flex-col">
                <h3 class="text-base font-bold text-slate-800 mb-4">จำนวนผู้เข้าประกวดที่ขายได้ (คน)</h3>
                <div class="w-full flex-1 min-h-[220px] relative"><canvas id="categoryChart"></canvas></div>
            </div>

            <!-- Leaderboard (2/3) -->
            <div class="lg:col-span-2 glass-panel rounded-[2rem] p-6 shadow-sm flex flex-col">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                        Top 5 Leaderboard (ตัวอย่าง 3 หมวด)
                    </h3>
                    <a href="reports.php" class="text-xs font-bold text-brand-600 hover:text-brand-700 bg-brand-50 px-3 py-1.5 rounded-lg transition-colors">ดูทั้งหมด</a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                    <?php 
                    $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    $colors = ['text-yellow-500', 'text-slate-400', 'text-orange-500', 'text-slate-600', 'text-slate-600'];
                    $display_count = 0;
                    foreach ($top5_data as $category => $top5): 
                        if($display_count >= 3) break; 
                        $display_count++;
                    ?>
                        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col">
                            <div class="text-center pb-2 mb-3 border-b border-slate-100">
                                <span class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wide"><?php echo htmlspecialchars($category); ?></span>
                            </div>
                            <div class="space-y-2 flex-1">
                                <?php if(count($top5) > 0): ?>
                                    <?php foreach($top5 as $index => $person): ?>
                                        <div class="flex items-center gap-2 p-1 hover:bg-slate-50 rounded-lg transition-colors">
                                            <div class="text-sm w-5 text-center <?php echo $colors[$index]; ?> drop-shadow-sm"><?php echo $medals[$index]; ?></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-bold text-slate-700 truncate"><?php echo htmlspecialchars($person['name_th']); ?></p>
                                            </div>
                                            <p class="text-[11px] font-black text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded">฿<?php echo number_format($person['total_sales']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="h-full flex items-center justify-center">
                                        <p class="text-[11px] font-medium text-slate-400">ยังไม่มียอดจำหน่าย</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </main>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ once: true });</script>

    <script>
        // ชุดสีมาตรฐานสำหรับกราฟ
        const brandColors = ['#7c3aed', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#14b8a6', '#f43f5e', '#8b5cf6'];
        const sharedOptions = { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { tooltip: { titleFont: { family: 'Prompt' }, bodyFont: { family: 'Prompt' } } } 
        };

        // 1. กราฟรายได้ 
        const rawDailySales = <?php echo json_encode($daily_sales_data); ?>;
        if(rawDailySales.length > 0) {
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            let gradient = ctxRev.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(124, 58, 237, 0.4)');
            gradient.addColorStop(1, 'rgba(124, 58, 237, 0.0)');

            new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: rawDailySales.map(i => new Date(i.sale_date).toLocaleDateString('th-TH', {day:'numeric', month:'short'})),
                    datasets: [{
                        label: 'รายได้ (บาท)',
                        data: rawDailySales.map(i => i.daily_revenue),
                        borderColor: '#7c3aed', backgroundColor: gradient,
                        borderWidth: 3, tension: 0.4, fill: true,
                        pointBackgroundColor: '#fff', pointBorderColor: '#7c3aed', pointRadius: 4
                    }]
                },
                options: { ...sharedOptions, plugins: { ...sharedOptions.plugins, legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { font: { family: 'Prompt' } } }, x: { grid: { display: false }, ticks: { font: { family: 'Prompt' } } } }
                }
            });
        }

        // 2. กราฟสัดส่วนผลิตภัณฑ์หลัก 
        const rawChart1 = <?php echo json_encode($chart1_data); ?>;
        if(rawChart1.length > 0) {
            new Chart(document.getElementById('productMainChart'), {
                type: 'doughnut',
                data: {
                    labels: rawChart1.map(i => i.product_type),
                    datasets: [{ data: rawChart1.map(i => i.total_qty), backgroundColor: brandColors, borderWidth: 2, hoverOffset: 6 }]
                },
                options: { ...sharedOptions, cutout: '65%', plugins: { ...sharedOptions.plugins, legend: { position: 'right', labels: {font: {family: 'Prompt', size: 12}} } } }
            });
        }

        // 3. กราฟรุ่นย่อย
        const rawOptions = <?php echo json_encode(array_keys($detailed_options)); ?>;
        const rawOptionVals = <?php echo json_encode(array_values($detailed_options)); ?>;
        if(rawOptions.length > 0) {
            new Chart(document.getElementById('productDetailChart'), {
                type: 'pie',
                data: {
                    labels: rawOptions,
                    datasets: [{ data: rawOptionVals, backgroundColor: brandColors, borderWidth: 1, hoverOffset: 6 }]
                },
                options: { ...sharedOptions, plugins: { ...sharedOptions.plugins, legend: { position: 'right', labels: {font: {family: 'Prompt', size: 11}} } } }
            });
        }

        // 4. กราฟแท่ง หมวดหมู่คนขาย
        const rawChart2 = <?php echo json_encode($chart2_data); ?>;
        if(rawChart2.length > 0) {
            new Chart(document.getElementById('categoryChart'), {
                type: 'bar',
                data: {
                    labels: rawChart2.map(i => i.category),
                    datasets: [{ label: 'ผู้เข้าประกวด (คน)', data: rawChart2.map(i => i.contestant_count), backgroundColor: '#8b5cf6', borderRadius: 4 }]
                },
                options: { ...sharedOptions, plugins: { ...sharedOptions.plugins, legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Prompt' } } }, x: { grid: { display: false }, ticks: { font: { family: 'Prompt', size: 10 } } } }
                }
            });
        }
    </script>
</body>
</html>