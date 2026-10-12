<?php
// ไฟล์: admin/verify_slips.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// รับค่าจากระบบกรองข้อมูล (Filters)
$filter_category = $_GET['category'] ?? '';
$filter_c_number = $_GET['c_number'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($filter_category)) {
    $where_clauses[] = "c.category = :category";
    $params['category'] = $filter_category;
}
if (!empty($filter_c_number)) {
    $where_clauses[] = "c.contestant_number = :c_number";
    $params['c_number'] = $filter_c_number;
}
if (!empty($filter_status)) {
    $where_clauses[] = "s.status = :status";
    $params['status'] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // ดึงรายชื่อผู้เข้าประกวดทั้งหมดเพื่อใช้ใน Dropdown กรองข้อมูล
    $stmt_contestants = $pdo->query("SELECT contestant_number, name_th, category FROM rma_contestants ORDER BY category ASC, contestant_number ASC");
    $all_contestants = $stmt_contestants->fetchAll(PDO::FETCH_ASSOC);

    // ดึงหมวดหมู่แบบไม่ซ้ำ
    $stmt_cats = $pdo->query("SELECT DISTINCT category FROM rma_contestants ORDER BY category ASC");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

    // ดึงข้อมูลสลิป (เพิ่ม profile_image, verified_by, slip_observation)
    $stmt = $pdo->prepare("
        SELECT s.id, s.amount, s.slip_image, s.status, s.created_at, 
               s.product_type, s.quantity, s.product_option, s.slip_datetime,
               s.verified_by, s.slip_observation,
               c.contestant_number, c.name_th, c.category, c.nickname, c.profile_image
        FROM rma_slips s
        JOIN rma_contestants c ON s.contestant_id = c.id
        $where_sql
        ORDER BY 
            CASE WHEN s.status = 'pending' THEN 1 ELSE 2 END,
            s.created_at DESC
    ");
    $stmt->execute($params);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // หาก Error เพราะยังไม่ได้เพิ่มคอลัมน์ ให้แสดงข้อความแจ้งเตือน
    die("<div style='padding:20px; font-family:sans-serif;'><b>แจ้งเตือน:</b> กรุณารันคำสั่ง SQL เพื่อเพิ่มคอลัมน์ verified_by และ slip_observation ใน phpMyAdmin ก่อนครับ<br>Error: " . $e->getMessage() . "</div>");
}

// ฟังก์ชันสร้าง Badge สี
function getStatusBadge($status) {
    if ($status === 'pending') return '<span class="px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-xs font-bold shadow-sm border border-amber-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> รอตรวจสอบ</span>';
    if ($status === 'approved') return '<span class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold shadow-sm border border-emerald-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> อนุมัติแล้ว</span>';
    return '<span class="px-3 py-1.5 bg-rose-100 text-rose-700 rounded-full text-xs font-bold shadow-sm border border-rose-200 flex items-center justify-center gap-1 w-fit mx-auto"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ไม่ผ่าน</span>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบสลิปโอนเงิน - Admin RMUTP AWARDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
        .modal-active { display: flex !important; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="flex justify-between items-end mb-6" data-aos="fade-down">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">ตรวจสอบสลิปโอนเงิน</h1>
                <p class="text-slate-500 mt-1">พิจารณาอนุมัติหรือปฏิเสธหลักฐาน พร้อมระบบแจ้งเตือนสลิปซ้ำ</p>
            </div>
        </div>

        <!-- ================= ส่วนกรองข้อมูล (Filters) ================= -->
        <div class="glass-panel rounded-2xl p-4 mb-6 shadow-sm" data-aos="fade-down" data-aos-delay="100">
            <form method="GET" action="verify_slips.php" class="flex flex-col md:flex-row gap-4 items-end">
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
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">หมายเลขผู้เข้าประกวด</label>
                    <select name="c_number" id="filter_c_number" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">สถานะสลิป</label>
                    <select name="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>รอตรวจสอบ</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>อนุมัติแล้ว</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>ไม่ผ่าน</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-sm text-sm">ค้นหา</button>
                    <a href="verify_slips.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors text-sm">รีเซ็ต</a>
                </div>
            </form>
        </div>

        <!-- ================= ตารางแสดงข้อมูล ================= -->
        <div class="glass-panel rounded-[2rem] overflow-hidden flex flex-col flex-1 shadow-xl" data-aos="fade-up" data-aos-delay="200">
            <div class="overflow-x-auto flex-1 p-2">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="p-4 text-center">No.</th>
                            <th class="p-4">ผู้เข้าประกวด</th>
                            <th class="p-4">รายละเอียดรายการ (ID / วันที่)</th> <!-- ปรับหัวข้อ -->
                            <th class="p-4 text-right">ยอดเงิน</th>
                            <th class="p-4 text-center">สถานะ</th>
                            <th class="p-4 text-center">ข้อสังเกต</th>
                            <th class="p-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/40">
                        <?php 
                        $count = 1;
                        foreach ($slips as $s): 
                            $json_data = htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="transition-colors <?php echo ($s['status'] == 'pending') ? 'bg-amber-50/30 hover:bg-amber-50/60' : 'hover:bg-white/80'; ?>">
                                <td class="p-4 text-center text-slate-400 font-medium"><?php echo $count++; ?></td>
                                
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-brand-100 text-brand-600 font-bold rounded-lg flex items-center justify-center text-xs shadow-sm shrink-0">
                                            <?php echo htmlspecialchars($s['contestant_number']); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-700 text-sm"><?php echo htmlspecialchars($s['name_th']); ?></p>
                                            <p class="text-[10px] text-slate-500"><?php echo htmlspecialchars($s['category']); ?></p>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="bg-slate-200 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold font-mono border border-slate-300">
                                            ID: #<?php echo str_pad($s['id'], 6, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo date('H:i', strtotime($s['created_at'])); ?> น.</p>
                                </td>

                                <td class="p-4 text-right">
                                    <span class="font-extrabold text-slate-800 text-base">฿<?php echo number_format($s['amount'], 2); ?></span>
                                </td>

                                <td class="p-4 text-center">
                                    <?php echo getStatusBadge($s['status']); ?>
                                </td>

                                <td class="p-4 text-center">
                                    <?php if(!empty($s['slip_observation'])): ?>
                                        <span class="inline-flex items-center justify-center px-2 py-1 bg-rose-50 text-rose-600 border border-rose-200 rounded-md text-[10px] font-bold" title="<?php echo htmlspecialchars($s['slip_observation']); ?>">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            มีข้อสังเกต
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-center">
                                    <button onclick="openVerifyModal(<?php echo $json_data; ?>)" class="px-4 py-2 bg-white hover:bg-brand-50 text-brand-600 border border-brand-200 hover:border-brand-300 rounded-xl text-xs font-bold shadow-sm transition-all transform hover:-translate-y-0.5 flex items-center justify-center mx-auto gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                        ตรวจสลิป
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($slips) === 0): ?>
                            <tr><td colspan="7" class="p-10 text-center text-slate-400 font-medium">ไม่มีรายการส่งสลิปตรงตามเงื่อนไข</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ================= Verify Modal ================= -->
    <div id="verifyModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex-col items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-[2rem] w-full max-w-5xl shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden flex flex-col max-h-[95vh]" id="modalContent">
            
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">ตรวจสอบรายการ <span id="m_header_id" class="text-brand-600 font-mono"></span></h3>
                </div>
                <button onclick="closeVerifyModal()" class="text-slate-400 hover:text-red-500 p-2 bg-white rounded-full shadow-sm hover:shadow transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 bg-slate-50/50">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- ฝั่งซ้าย: ข้อมูลทั้งหมด -->
                    <div class="space-y-6">
                        
                        <!-- 1. ข้อมูลผู้เข้าประกวด -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full border-2 border-slate-200 overflow-hidden shrink-0 bg-slate-100 shadow-inner">
                                <img id="m_profile_img" src="" alt="Profile" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ข้อมูลผู้เข้าประกวด</h4>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="bg-brand-600 text-white font-bold px-2 py-0.5 rounded text-xs" id="m_no"></span>
                                    <span class="font-extrabold text-slate-800 text-lg leading-none" id="m_name"></span>
                                </div>
                                <p class="text-xs text-brand-600 font-bold bg-brand-50 inline-block px-2 py-0.5 rounded border border-brand-100" id="m_cat"></p>
                            </div>
                        </div>

                        <!-- 2. ข้อมูลผลิตภัณฑ์ -->
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-100 pb-2">รายละเอียดผลิตภัณฑ์ที่แจ้งยอด</h4>
                            
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-14 h-14 bg-slate-50 rounded-xl p-2 border border-slate-100 shrink-0">
                                    <img id="m_prod_img" src="" alt="Product" class="w-full h-full object-contain">
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800 text-base mb-1" id="m_prod_type"></p>
                                    <ul id="m_prod_option" class="list-disc list-inside text-sm text-slate-600 space-y-1"></ul>
                                </div>
                            </div>

                            <div class="flex justify-between items-end border-t border-slate-100 pt-4 bg-slate-50 rounded-xl p-4">
                                <div>
                                    <p class="text-xs text-slate-500 font-medium">จำนวนรวม</p>
                                    <p class="text-lg font-bold text-slate-800"><span id="m_qty"></span> รายการ</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-brand-500 font-bold uppercase tracking-wider">ยอดเงินสุทธิ</p>
                                    <span class="text-3xl font-black text-brand-700 tracking-tighter" id="m_amount"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. ข้อมูลเวลา -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">แจ้งยอดเมื่อ</p>
                                <p class="text-sm font-semibold text-slate-700" id="m_created"></p>
                            </div>
                            <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 shadow-sm">
                                <p class="text-[10px] font-bold text-amber-600 uppercase mb-1">วันเวลาตามสลิป</p>
                                <p class="text-sm font-semibold text-amber-800" id="m_slip_date"></p>
                            </div>
                        </div>
                    </div>

                    <!-- ฝั่งขวา: รูปภาพสลิป พร้อม Badge แจ้งเตือน -->
                    <div class="flex flex-col items-center bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden h-full min-h-[400px]">
                        <p class="text-sm font-bold text-slate-600 mb-4 w-full text-center border-b border-slate-100 pb-3">หลักฐานการโอนเงิน</p>
                        
                        <div class="flex-1 w-full relative flex items-center justify-center bg-slate-50 rounded-xl border border-slate-200 overflow-hidden cursor-zoom-in group" onclick="zoomImage(document.getElementById('m_img').src)">
                            
                            <!-- ป้ายแจ้งเตือน -->
                            <div id="m_observation_badge" class="absolute top-4 right-4 bg-rose-600/90 backdrop-blur-md text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg border border-rose-400 z-10 hidden flex items-center gap-1.5 animate-pulse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span id="m_observation_text">พบความผิดปกติ</span>
                            </div>

                            <img id="m_img" src="" alt="Slip" class="max-h-[500px] object-contain transition-transform duration-300 group-hover:scale-[1.02]">
                        </div>
                        <p class="text-[10px] font-bold text-brand-600 mt-4 bg-brand-50 px-3 py-1.5 rounded-full uppercase tracking-widest">แตะที่รูปเพื่อขยายใหญ่</p>
                    </div>
                </div>
            </div>

            <!-- Footer (ปุ่มจัดการ อนุมัติ/ปฏิเสธ/ยกเลิก) -->
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-between items-center" id="modalFooter">
                <!-- เนื้อหาตรงนี้จะถูกสร้างด้วย JavaScript -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // นำข้อมูลผู้เข้าประกวดทั้งหมดมาแปลงเป็นตัวแปร JS สำหรับกรอง Dropdown
        const contestantsData = <?php echo json_encode($all_contestants); ?>;
        
        // ฟังก์ชันอัปเดต Dropdown หมายเลขตามหมวดหมู่ที่เลือก
        function updateContestantDropdown() {
            const cat = document.getElementById('filter_category').value;
            const cNumSelect = document.getElementById('filter_c_number');
            const currentSelected = '<?php echo $filter_c_number; ?>';
            
            cNumSelect.innerHTML = '<option value="">-- ทั้งหมด --</option>';
            
            contestantsData.forEach(c => {
                if (!cat || c.category === cat) {
                    const selected = (c.contestant_number === currentSelected) ? 'selected' : '';
                    cNumSelect.innerHTML += `<option value="${c.contestant_number}" ${selected}>${c.contestant_number} - ${c.name_th}</option>`;
                }
            });
        }
        updateContestantDropdown();

        // -------------------------------------------------------------

        const modal = document.getElementById('verifyModal');
        const modalContent = document.getElementById('modalContent');
        let currentSlipId = null;

        function formatDate(dateString) {
            if (!dateString) return '-';
            const dt = new Date(dateString);
            return dt.toLocaleDateString('th-TH') + ' ' + dt.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'}) + ' น.';
        }

        // ฟังก์ชันเปิด Modal และจัดเรียงข้อมูล
        function openVerifyModal(data) {
            currentSlipId = data.id;

            const formattedId = String(data.id).padStart(6, '0');
            document.getElementById('m_header_id').innerText = '#' + formattedId;

            // 1. ข้อมูลผู้เข้าประกวด
            document.getElementById('m_no').innerText = data.contestant_number;
            document.getElementById('m_name').innerText = data.name_th + ' (น้อง' + (data.nickname || '-') + ')';
            document.getElementById('m_cat').innerText = 'ประเภท : ' + data.category;
            
            let profileUrl = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.name_th) + '&background=f1f5f9&color=64748b';
            if(data.profile_image) profileUrl = '../assets/uploads/profiles/' + data.profile_image;
            document.getElementById('m_profile_img').src = profileUrl;
            
            // 2. ข้อมูลผลิตภัณฑ์
            document.getElementById('m_prod_type').innerText = data.product_type || '-';
            document.getElementById('m_qty').innerText = data.quantity;
            document.getElementById('m_amount').innerText = '฿' + parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // เลือกรุปผลิตภัณฑ์
            let prodImg = 'https://placehold.co/150x150/f8fafc/64748b?text=Product';
            if (data.product_type === 'CAMPUS BY GQ') prodImg = '../assets/images/product-gq.png';
            if (data.product_type === 'น้ำดื่ม RMUTP') prodImg = '../assets/images/product-water.png';
            if (data.product_type === 'บัตรวิ่งติดมันส์') prodImg = '../assets/images/product-run.png';
            document.getElementById('m_prod_img').src = prodImg;

            const optList = document.getElementById('m_prod_option');
            optList.innerHTML = '';
            if (data.product_option) {
                data.product_option.split(', ').forEach(opt => {
                    optList.innerHTML += `<li class="font-medium">${opt}</li>`;
                });
            } else {
                optList.innerHTML = '<li class="text-slate-400 list-none">ไม่มีตัวเลือกเสริม</li>';
            }

            // 3. เวลา
            document.getElementById('m_created').innerText = formatDate(data.created_at);
            document.getElementById('m_slip_date').innerText = formatDate(data.slip_datetime);
            
            // 4. สลิป และ Badge ข้อสังเกต
            document.getElementById('m_img').src = '../assets/uploads/slips/' + data.slip_image;
            const obsBadge = document.getElementById('m_observation_badge');
            const obsText = document.getElementById('m_observation_text');
            if(data.slip_observation) {
                obsText.innerText = data.slip_observation;
                obsBadge.classList.remove('hidden');
            } else {
                obsBadge.classList.add('hidden');
            }

            // 5. จัดการ Footer (ปุ่มและชื่อคนอนุมัติ)
            const footer = document.getElementById('modalFooter');
            const verifierText = data.verified_by ? `ตรวจสอบโดย: ${data.verified_by}` : 'ตรวจสอบโดย: ระบบ (อัตโนมัติ)';

            if (data.status === 'pending') {
                footer.innerHTML = `
                    <p class="text-sm font-bold text-amber-600 flex items-center bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> สถานะ: รอการตรวจสอบ</p>
                    <div class="flex gap-2">
                        <button onclick="updateStatus('reject')" class="px-5 py-2.5 bg-white border-2 border-rose-200 hover:border-rose-400 hover:bg-rose-50 text-rose-600 font-bold rounded-xl transition-colors shadow-sm">ปฏิเสธสลิปนี้</button>
                        <button onclick="updateStatus('approve')" class="px-8 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-md shadow-brand-500/30 transform hover:-translate-y-0.5">อนุมัติยอดเงิน</button>
                    </div>
                `;
            } else if (data.status === 'approved') {
                footer.innerHTML = `
                    <div class="flex flex-col">
                        <p class="text-sm font-bold text-emerald-600 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> อนุมัติเรียบร้อยแล้ว</p>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">${verifierText}</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="updateStatus('reject')" class="px-4 py-2 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-bold rounded-xl transition-colors shadow-sm" title="คลิกเพื่อยกเลิกการอนุมัติ">เปลี่ยนเป็นปฏิเสธ</button>
                        <button onclick="closeVerifyModal()" class="px-6 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-colors">ปิดหน้าต่าง</button>
                    </div>
                `;
            } else {
                footer.innerHTML = `
                    <div class="flex flex-col">
                        <p class="text-sm font-bold text-rose-600 flex items-center"><svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ปฏิเสธรายการนี้แล้ว</p>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">${verifierText}</p>
                    </div>
                    <button onclick="closeVerifyModal()" class="px-6 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-colors">ปิดหน้าต่าง</button>
                `;
            }

            // แสดง Modal
            modal.classList.add('modal-active');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function closeVerifyModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => { modal.classList.remove('modal-active'); }, 300);
        }

        // ซูมรูปภาพ
        function zoomImage(src) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Slip',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: { image: 'max-h-[85vh] object-contain rounded-xl', popup: 'bg-transparent shadow-none' },
                backdrop: 'rgba(15, 23, 42, 0.95)'
            });
        }

        // ฟังก์ชันบันทึกการอนุมัติ/ปฏิเสธ
        function updateStatus(action) {
            const isReversing = (action === 'reject' && document.getElementById('modalFooter').innerText.includes('อนุมัติเรียบร้อย'));
            
            let actionText = (action === 'approve') ? 'อนุมัติยอดเงินนี้' : 'ปฏิเสธหลักฐานนี้';
            let titleText = 'ยืนยันการดำเนินการ?';
            
            if(isReversing) {
                actionText = 'ยกเลิกการอนุมัติ (เปลี่ยนเป็นปฏิเสธ)';
                titleText = 'ต้องการแก้ไขคำตัดสินใช่หรือไม่?';
            }

            const confirmColor = (action === 'approve') ? '#10b981' : '#f43f5e';
            const iconType = (action === 'approve') ? 'success' : 'warning';

            Swal.fire({
                title: titleText,
                text: `คุณต้องการ "${actionText}" ใช่หรือไม่?`,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                customClass: { popup: 'rounded-[1.5rem]', confirmButton: 'rounded-xl font-bold px-6', cancelButton: 'rounded-xl font-bold px-6' }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', currentSlipId);

                    fetch('api/slip_api.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, showConfirmButton: false, timer: 1500, customClass: { popup: 'rounded-[1.5rem]' } })
                            .then(() => window.location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: data.message, customClass: { popup: 'rounded-[1.5rem]' } });
                        }
                    })
                    .catch(err => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error'));
                }
            });
        }
    </script>
</body>
</html>