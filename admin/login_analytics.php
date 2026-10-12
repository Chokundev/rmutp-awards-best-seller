<?php
// ไฟล์: admin/login_analytics.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    // 1. ดึงสถิติภาพรวมเป็นตัวเลข (Summary Cards)
    $stat_total = $pdo->query("SELECT COUNT(*) FROM rma_login_logs")->fetchColumn();
    $stat_success = $pdo->query("SELECT COUNT(*) FROM rma_login_logs WHERE status = 'success'")->fetchColumn();
    $stat_failed = $pdo->query("SELECT COUNT(*) FROM rma_login_logs WHERE status = 'failed'")->fetchColumn();

    // 2. ดึงข้อมูลสำหรับกราฟ: ผู้ใช้ที่เข้าสู่ระบบมากที่สุด (Top 5 Users)
    $stmt_top_users = $pdo->query("
        SELECT 
            COALESCE(c.name_th, l.attempted_username) as user_name, 
            COUNT(l.id) as login_count
        FROM rma_login_logs l
        LEFT JOIN rma_contestants c ON l.attempted_username = c.stdcode
        WHERE l.status = 'success'
        GROUP BY l.attempted_username
        ORDER BY login_count DESC
        LIMIT 5
    ");
    $top_users_data = $stmt_top_users->fetchAll(PDO::FETCH_ASSOC);

    // 3. ดึงข้อมูลสำหรับกราฟ: สถิติการเข้าสู่ระบบรายวัน (ย้อนหลัง 7 วัน)
    $stmt_daily = $pdo->query("
        SELECT 
            DATE(created_at) as login_date, 
            COUNT(*) as total_logins
        FROM rma_login_logs
        WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY
        GROUP BY DATE(created_at)
        ORDER BY login_date ASC
    ");
    $daily_logins_data = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// เตรียมข้อมูลให้อยู่ในรูปแบบ JSON เพื่อส่งให้ JavaScript (Chart.js)
$top_users_json = json_encode($top_users_data);
$daily_logins_json = json_encode($daily_logins_data);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติการเข้าสู่ระบบ - Admin RMUTP AWARDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } } }
            }
        }
    </script>
    
    <!-- นำเข้าไลบรารี Chart.js สำหรับวาดกราฟ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>

    <!-- ================= Sidebar ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- ================= Main Content ================= -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">สถิติการใช้งานระบบ</h1>
            <p class="text-slate-500 mt-1">ภาพรวมการเข้าสู่ระบบของผู้เข้าประกวดทั้งหมด</p>
        </div>

        <!-- ================= ส่วนที่ 1: การ์ดสรุปตัวเลข (Summary Cards) ================= -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <!-- การ์ดรวมทั้งหมด -->
            <div class="glass-panel rounded-2xl p-6 flex items-center gap-4 border-l-4 border-l-brand-500">
                <div class="w-14 h-14 bg-brand-100 text-brand-600 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">ล็อกอินทั้งหมด</p>
                    <h3 class="text-3xl font-black text-slate-800"><?php echo number_format($stat_total); ?> <span class="text-sm font-medium text-slate-400">ครั้ง</span></h3>
                </div>
            </div>

            <!-- การ์ดสำเร็จ -->
            <div class="glass-panel rounded-2xl p-6 flex items-center gap-4 border-l-4 border-l-emerald-500">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">เข้าสู่ระบบสำเร็จ</p>
                    <h3 class="text-3xl font-black text-slate-800"><?php echo number_format($stat_success); ?> <span class="text-sm font-medium text-slate-400">ครั้ง</span></h3>
                </div>
            </div>

            <!-- การ์ดล้มเหลว -->
            <div class="glass-panel rounded-2xl p-6 flex items-center gap-4 border-l-4 border-l-rose-500">
                <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">เข้าสู่ระบบล้มเหลว</p>
                    <h3 class="text-3xl font-black text-rose-600"><?php echo number_format($stat_failed); ?> <span class="text-sm font-medium text-rose-400">ครั้ง</span></h3>
                </div>
            </div>

        </div>

        <!-- ================= ส่วนที่ 2: กราฟสถิติ (Charts) ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- กราฟที่ 1: แนวโน้มการใช้งานรายวัน (Line Chart) -->
            <div class="glass-panel rounded-[2rem] p-6 shadow-md">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">แนวโน้มการเข้าสู่ระบบ 7 วันล่าสุด</h3>
                <div class="relative h-72 w-full">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- กราฟที่ 2: ผู้ใช้งานที่ล็อกอินบ่อยสุด (Bar Chart) -->
            <div class="glass-panel rounded-[2rem] p-6 shadow-md">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">ผู้เข้าประกวดที่เข้าสู่ระบบบ่อยที่สุด (Top 5)</h3>
                <div class="relative h-72 w-full">
                    <canvas id="topUsersChart"></canvas>
                </div>
            </div>

        </div>

    </main>

    <!-- ================= Script จัดการ Chart.js ================= -->
    <script>
        // รับค่า JSON จาก PHP มาไว้ในตัวแปร JavaScript
        const rawTopUsers = <?php echo $top_users_json; ?>;
        const rawDailyLogins = <?php echo $daily_logins_json; ?>;

        // 1. ตั้งค่ากราฟแนวโน้มรายวัน (Line Chart)
        // จัดเตรียมข้อมูลแกน X (วันที่) และแกน Y (จำนวน)
        const dailyLabels = rawDailyLogins.map(item => {
            // แปลงวันที่ให้อ่านง่าย เช่น '2026-08-23' -> '23 ส.ค.'
            const dateObj = new Date(item.login_date);
            return dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
        });
        const dailyData = rawDailyLogins.map(item => item.total_logins);

        const ctxDaily = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'จำนวนการเข้าสู่ระบบ (ครั้ง)',
                    data: dailyData,
                    borderColor: '#8b5cf6', // สีม่วง Brand
                    backgroundColor: 'rgba(139, 92, 246, 0.2)', // สีม่วงโปร่งใส
                    borderWidth: 3,
                    tension: 0.4, // ทำให้เส้นกราฟโค้งมน
                    fill: true,
                    pointBackgroundColor: '#8b5cf6',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 2. ตั้งค่ากราฟผู้ใช้ที่เข้าบ่อยสุด (Bar Chart)
        const topUserLabels = rawTopUsers.map(item => item.user_name);
        const topUserData = rawTopUsers.map(item => item.login_count);

        const ctxTopUsers = document.getElementById('topUsersChart').getContext('2d');
        new Chart(ctxTopUsers, {
            type: 'bar',
            data: {
                labels: topUserLabels,
                datasets: [{
                    label: 'จำนวนครั้งที่ล็อกอิน',
                    data: topUserData,
                    backgroundColor: [
                        '#8b5cf6', // ม่วง
                        '#a78bfa', // ม่วงอ่อน
                        '#34d399', // เขียว
                        '#60a5fa', // ฟ้า
                        '#f472b6'  // ชมพู
                    ],
                    borderRadius: 8 // ทำให้มุมของแท่งกราฟโค้งมน
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    </script>
</body>
</html>