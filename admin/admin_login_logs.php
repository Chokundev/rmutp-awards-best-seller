<?php
// ไฟล์: admin/admin_login_logs.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// 1. รับค่าจากตัวกรอง (Filters)
$filter_email = $_GET['email'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];


if (!empty($filter_email)) {
    $where_clauses[] = "l.attempted_email = :email";
    $params['email'] = $filter_email;
}
if (!empty($filter_status)) {
    $where_clauses[] = "l.status = :status";
    $params['status'] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // ดึงรายชื่ออีเมลทั้งหมดที่มีในประวัติ (ยกเว้น master admin)
    $stmt_emails = $pdo->query("SELECT DISTINCT attempted_email FROM rma_admin_login_logs WHERE attempted_email != 'teerachanon6644@gmail.com' ORDER BY attempted_email ASC");
    $all_emails = $stmt_emails->fetchAll(PDO::FETCH_COLUMN);

    // ดึงข้อมูลประวัติการล็อกอิน + เชื่อมตาราง rma_admins
    $stmt = $pdo->prepare("
        SELECT l.*, a.name_th, a.profile_image 
        FROM rma_admin_login_logs l
        LEFT JOIN rma_admins a ON l.attempted_email = a.email
        $where_sql
        ORDER BY l.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าสู่ระบบ (แอดมิน) - RMUTP AWARDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } } }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">ระบบรักษาความปลอดภัย (แอดมิน)</h1>
                <p class="text-slate-500 mt-1">ประวัติการพยายามเข้าสู่ระบบหลังบ้านด้วยบัญชี Google</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 text-sm font-medium text-slate-600">
                แสดงผลสูงสุด 100 รายการ
            </div>
        </div>

        <!-- ================= ส่วนกรองข้อมูล (Filters) ================= -->
        <div class="glass-panel rounded-2xl p-4 mb-6 shadow-sm">
            <form method="GET" action="admin_login_logs.php" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-2/5">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">บัญชีอีเมล (Google Email)</label>
                    <select name="email" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($all_emails as $em): ?>
                            <option value="<?php echo htmlspecialchars($em); ?>" <?php echo $filter_email === $em ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($em); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">สถานะการเข้าสู่ระบบ</label>
                    <select name="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>อนุญาตสิทธิ์ (Success)</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>ปฏิเสธการเข้าถึง (Failed)</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-sm text-sm">ค้นหา</button>
                    <a href="admin_login_logs.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors text-sm">รีเซ็ต</a>
                </div>
            </form>
        </div>

        <!-- ================= ตารางแสดงข้อมูล ================= -->
        <div class="glass-panel rounded-[2rem] overflow-hidden flex flex-col flex-1 shadow-xl">
            <div class="overflow-x-auto flex-1 p-2">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="p-4 text-center">วัน-เวลา</th>
                            <th class="p-4">ข้อมูลบัญชีแอดมิน</th>
                            <th class="p-4 text-center">IP Address</th>
                            <th class="p-4 text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/40">
                        <?php foreach ($logs as $log): 
                            
                            // จัดการรูปโปรไฟล์ (กรณีแอดมินเคยเข้าระบบแล้ว จะดึงรูป Google มาแสดง)
                            // ถ้าไม่เคยมีรูป จะใช้ชื่อขึ้นต้นจาก Email มาสร้างรูปภาพแบบ Placeholder
                            $p_img = 'https://ui-avatars.com/api/?name=' . urlencode(substr($log['attempted_email'], 0, 2)) . '&background=f1f5f9&color=64748b';
                            
                            // ถ้าในฐานข้อมูลอัปเดตชื่อไว้แล้ว ให้ใช้ชื่อ
                            if (!empty($log['name_th'])) {
                                $p_img = 'https://ui-avatars.com/api/?name='.urlencode($log['name_th']).'&background=f1f5f9&color=64748b';
                            }
                            
                            // ถ้าในฐานข้อมูลเซฟ URL รูปภาพจาก Google ไว้แล้ว ให้ดึงมาใช้โดยตรง
                            if (!empty($log['profile_image'])) {
                                $p_img = htmlspecialchars($log['profile_image']); // ของ Google จะเป็น URL เต็ม (http...)
                            }
                        ?>
                            <tr class="hover:bg-white/80 transition-colors">
                                <td class="p-4 text-center">
                                    <p class="text-sm font-bold text-slate-700"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?> น.</p>
                                </td>
                                
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 bg-slate-100 shrink-0">
                                            <img src="<?php echo $p_img; ?>" alt="Profile" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <?php if(!empty($log['name_th'])): ?>
                                                <p class="text-sm font-bold text-slate-800 mb-0.5"><?php echo htmlspecialchars($log['name_th']); ?></p>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 font-mono"><?php echo htmlspecialchars($log['attempted_email']); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <!-- กรณีอีเมลนี้ไม่มีในตาราง rma_admins หรือยังไม่ได้ล็อกอินใหม่เพื่ออัปเดตข้อมูล -->
                                                <p class="text-sm font-bold <?php echo ($log['status'] === 'success') ? 'text-slate-800' : 'text-rose-600'; ?> mb-0.5">
                                                    <?php echo ($log['status'] === 'success') ? 'Admin Account' : 'Unknown User'; ?>
                                                </p>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] <?php echo ($log['status'] === 'success') ? 'text-slate-500 bg-slate-100 border-slate-200' : 'text-rose-500 bg-rose-50 border-rose-200'; ?> px-2 py-0.5 rounded border font-mono">
                                                        <?php echo htmlspecialchars($log['attempted_email']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="p-4 text-center text-sm font-medium text-slate-500">
                                    <span class="bg-slate-50 px-2 py-1 rounded border border-slate-100 font-mono">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </span>
                                </td>
                                
                                <td class="p-4 text-center">
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> อนุญาตสิทธิ์
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold border border-rose-200" title="อีเมลไม่ได้รับสิทธิ์">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg> ปฏิเสธการเข้าถึง
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($logs) === 0): ?>
                            <tr><td colspan="4" class="p-10 text-center text-slate-400 font-medium">ไม่พบประวัติการเข้าสู่ระบบที่ตรงกับเงื่อนไข</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>