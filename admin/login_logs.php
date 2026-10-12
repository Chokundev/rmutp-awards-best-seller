<?php
// ไฟล์: admin/login_logs.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// 1. รับค่าจากระบบกรองข้อมูล (Filters)
$filter_category = $_GET['category'] ?? '';
$filter_username = $_GET['username'] ?? ''; 
$filter_status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];

// สร้างเงื่อนไข SQL ตามที่แอดมินเลือก
if (!empty($filter_category)) {
    $where_clauses[] = "c.category = :category";
    $params['category'] = $filter_category;
}
if (!empty($filter_username)) {
    $where_clauses[] = "l.attempted_username = :username";
    $params['username'] = $filter_username;
}
if (!empty($filter_status)) {
    $where_clauses[] = "l.status = :status";
    $params['status'] = $filter_status;
}

// นำเงื่อนไขมาต่อกัน
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // 💥 อัปเดต: ดึง nickname มาใช้ใน Dropdown ของระบบกรองด้วย
    $stmt_contestants = $pdo->query("SELECT stdcode, contestant_number, name_th, nickname, category FROM rma_contestants ORDER BY category ASC, contestant_number ASC");
    $all_contestants = $stmt_contestants->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cats = $pdo->query("SELECT DISTINCT category FROM rma_contestants ORDER BY category ASC");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

    // ดึงข้อมูลประวัติการล็อกอิน
    $stmt = $pdo->prepare("
        SELECT l.*, c.name_th, c.nickname, c.profile_image, c.category, c.contestant_number, c.stdcode
        FROM rma_login_logs l
        LEFT JOIN rma_contestants c ON l.attempted_username = c.stdcode
        $where_sql
        ORDER BY l.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// ฟังก์ชันเซนเซอร์ข้อมูลรหัสผ่าน
function maskPassword($password) {
    $len = strlen($password);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($password, 0, 2) . str_repeat('*', $len - 4) . substr($password, -2);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าสู่ระบบ - Admin RMUTP AWARDS</title>
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

    <!-- ================= Sidebar ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- ================= Main Content ================= -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">ประวัติการเข้าสู่ระบบ</h1>
                <p class="text-slate-500 mt-1">ตรวจสอบการใช้งานและข้อผิดพลาดในการล็อกอินของผู้เข้าประกวด</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 text-sm font-medium text-slate-600">
                แสดงผลสูงสุด 100 รายการ
            </div>
        </div>

        <!-- ================= ส่วนกรองข้อมูล (Filters) ================= -->
        <div class="glass-panel rounded-2xl p-4 mb-6 shadow-sm">
            <form method="GET" action="login_logs.php" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">ประเภทการประกวด</label>
                    <select name="category" id="filter_category" onchange="updateContestantDropdown()" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">ผู้เข้าประกวด (รหัสนักศึกษา)</label>
                    <select name="username" id="filter_username" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">สถานะการเข้าสู่ระบบ</label>
                    <select name="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>สำเร็จ (Success)</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>ล้มเหลว (Failed)</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-sm text-sm">ค้นหา</button>
                    <a href="login_logs.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors text-sm">รีเซ็ต</a>
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
                            <th class="p-4">ข้อมูลผู้เข้าประกวด</th>
                            <th class="p-4">รหัสผ่านที่กรอก</th>
                            <th class="p-4 text-center">IP Address</th>
                            <th class="p-4 text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/40">
                        <?php foreach ($logs as $log): 
                            // จัดการรูปภาพโปรไฟล์
                            $p_img = 'https://ui-avatars.com/api/?name=' . urlencode($log['attempted_username']) . '&background=f1f5f9&color=64748b';
                            if (!empty($log['name_th'])) {
                                $p_img = 'https://ui-avatars.com/api/?name='.urlencode($log['name_th']).'&background=f1f5f9&color=64748b';
                            }
                            if (!empty($log['profile_image'])) {
                                $p_img = '../assets/uploads/profiles/' . htmlspecialchars($log['profile_image']);
                            }
                        ?>
                            <tr class="hover:bg-white/80 transition-colors">
                                <td class="p-4 text-center">
                                    <p class="text-sm font-bold text-slate-700"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></p>
                                </td>
                                
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 bg-slate-100 shrink-0">
                                            <img src="<?php echo $p_img; ?>" alt="Profile" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <?php if(!empty($log['name_th'])): ?>
                                                <div class="flex items-center gap-2 mb-0.5">
                                                    <span class="text-[10px] font-bold bg-brand-100 text-brand-600 px-2 py-0.5 rounded border border-brand-200"><?php echo htmlspecialchars($log['contestant_number']); ?></span>
                                                    <span class="text-[10px] text-slate-500 uppercase font-semibold"><?php echo htmlspecialchars($log['category']); ?></span>
                                                </div>
                                                <p class="text-sm font-bold text-slate-800">
                                                    <?php echo htmlspecialchars($log['name_th']); ?> 
                                                    <span class="font-semibold text-brand-600">(น้อง<?php echo htmlspecialchars($log['nickname']); ?>)</span>
                                                    <span class="font-normal text-xs text-slate-500 ml-1"><?php echo htmlspecialchars($log['stdcode']); ?></span>
                                                </p>
                                            <?php else: ?>
                                                <div class="flex items-center gap-2 mb-0.5">
                                                    <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded border border-slate-200">Unknown User</span>
                                                </div>
                                                <p class="text-sm font-bold text-rose-600">Username: <?php echo htmlspecialchars($log['attempted_username']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 font-mono text-sm text-slate-600">
                                    <?php echo maskPassword($log['attempted_password']); ?>
                                </td>
                                <td class="p-4 text-center text-sm font-medium text-slate-500">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> สำเร็จ
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold border border-rose-200" title="ลองผิดลองถูก">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg> ล้มเหลว
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($logs) === 0): ?>
                            <tr><td colspan="5" class="p-10 text-center text-slate-400 font-medium">ไม่พบประวัติการเข้าสู่ระบบที่ตรงกับเงื่อนไข</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Scripts สำหรับจัดการ Dropdown (Filter) -->
    <script>
        // ข้อมูลผู้เข้าประกวดที่ดึงมาจาก PHP ส่งมาเป็น JSON
        const contestantsData = <?php echo json_encode($all_contestants); ?>;
        
        function updateContestantDropdown() {
            const cat = document.getElementById('filter_category').value;
            const usernameSelect = document.getElementById('filter_username');
            const currentSelected = '<?php echo $filter_username; ?>';
            
            // เคลียร์ตัวเลือกเดิม
            usernameSelect.innerHTML = '<option value="">-- ทั้งหมด --</option>';
            
            // วนลูปสร้างตัวเลือกใหม่ตามเงื่อนไข (ใช้ stdcode เป็น value)
            contestantsData.forEach(c => {
                if (!cat || c.category === cat) {
                    const selected = (c.stdcode === currentSelected) ? 'selected' : '';
                    const nicknameStr = c.nickname ? ` (น้อง${c.nickname})` : '';
                    usernameSelect.innerHTML += `<option value="${c.stdcode}" ${selected}>${c.stdcode} - ${c.contestant_number} ${c.name_th}${nicknameStr}</option>`;
                }
            });
        }
        
        updateContestantDropdown();
    </script>
</body>
</html>