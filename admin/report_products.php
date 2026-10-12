<?php
// ไฟล์: admin/report_products.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// 1. รับค่าจากระบบกรองข้อมูล (Filters)
$filter_product_type = $_GET['product_type'] ?? '';
$filter_product_option = $_GET['product_option'] ?? '';

// ตั้งค่าเริ่มต้น ต้องเป็นสลิปที่ "อนุมัติแล้ว" เท่านั้น
$where_clauses = ["s.status = 'approved'"];
$params = [];

// ถ้ามีการเลือกประเภทหลัก
if (!empty($filter_product_type)) {
    $where_clauses[] = "s.product_type = :p_type";
    $params['p_type'] = $filter_product_type;
}

// ถ้ามีการเลือกประเภทย่อย (ใช้ LIKE เพราะข้อมูลถูกเก็บแบบคั่นด้วยลูกน้ำ)
if (!empty($filter_product_option)) {
    $where_clauses[] = "s.product_option LIKE :p_opt";
    $params['p_opt'] = '%' . $filter_product_option . '%';
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

try {
    // 2. ดึงข้อมูลและรวมยอดจำหน่ายตามเงื่อนไขที่ถูกกรอง
    $stmt = $pdo->prepare("
        SELECT 
            s.product_type,
            c.contestant_number,
            c.name_th,
            c.nickname,
            c.category,
            SUM(s.quantity) as total_quantity,
            SUM(s.amount) as total_amount
        FROM rma_slips s
        JOIN rma_contestants c ON s.contestant_id = c.id
        $where_sql
        GROUP BY s.product_type, c.id
        ORDER BY s.product_type ASC, total_quantity DESC
    ");
    $stmt->execute($params);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มข้อมูล (Group Data) ให้อยู่ภายใต้ชื่อผลิตภัณฑ์หลัก
    $product_reports = [];
    foreach ($raw_data as $row) {
        $product_type = $row['product_type'];
        if (!isset($product_reports[$product_type])) {
            $product_reports[$product_type] = [];
        }
        $product_reports[$product_type][] = $row;
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานยอดจำหน่ายตามผลิตภัณฑ์ - RMUTP AWARDS</title>
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
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">รายงานยอดจำหน่ายรายผลิตภัณฑ์</h1>
                <p class="text-slate-500 mt-1">สรุปยอดขายแยกตามประเภทและประเภทย่อย (เฉพาะที่อนุมัติแล้ว)</p>
            </div>
            <button onclick="window.print()" class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-600 font-bold rounded-xl shadow-sm border border-slate-200 transition-colors flex items-center gap-2 no-print">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                พิมพ์รายงาน
            </button>
        </div>

        <!-- ================= แถบตัวกรองข้อมูล (Filter) ================= -->
        <div class="glass-panel rounded-2xl p-4 mb-8 shadow-sm no-print">
            <form method="GET" action="report_products.php" class="flex flex-col md:flex-row gap-4 items-end">
                
                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">ประเภทผลิตภัณฑ์หลัก</label>
                    <select name="product_type" id="filter_product_type" onchange="updateProductOptionDropdown()" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="CAMPUS BY GQ" <?php echo $filter_product_type == 'CAMPUS BY GQ' ? 'selected' : ''; ?>>CAMPUS BY GQ</option>
                        <option value="น้ำดื่ม RMUTP" <?php echo $filter_product_type == 'น้ำดื่ม RMUTP' ? 'selected' : ''; ?>>น้ำดื่ม RMUTP</option>
                        <option value="บัตรวิ่งติดมันส์" <?php echo $filter_product_type == 'บัตรวิ่งติดมันส์' ? 'selected' : ''; ?>>บัตรวิ่งติดมันส์</option>
                    </select>
                </div>

                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">ประเภทย่อย (รายละเอียด)</label>
                    <select name="product_option" id="filter_product_option" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium bg-white">
                        <option value="">-- ทั้งหมด --</option>
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition-colors shadow-sm text-sm">กรองข้อมูล</button>
                    <a href="report_products.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors text-sm">รีเซ็ต</a>
                </div>
            </form>
        </div>
        <!-- ========================================================= -->

        <?php if (empty($product_reports)): ?>
            <div class="glass-panel rounded-[2rem] p-10 text-center">
                <p class="text-slate-500 font-medium text-lg">ไม่พบข้อมูลยอดจำหน่ายที่ตรงกับเงื่อนไข</p>
                <p class="text-sm text-slate-400 mt-2">กรุณาลองปรับเปลี่ยนตัวกรองค้นหาอีกครั้ง</p>
            </div>
        <?php else: ?>
            
            <div class="space-y-8">
                <?php foreach ($product_reports as $product_name => $contestants): 
                    $sum_qty = array_sum(array_column($contestants, 'total_quantity'));
                    $sum_amt = array_sum(array_column($contestants, 'total_amount'));
                ?>
                
                <div class="glass-panel rounded-[2rem] overflow-hidden shadow-xl">
                    <div class="bg-gradient-to-r from-brand-600 to-indigo-600 px-6 py-4 flex flex-col sm:flex-row justify-between items-center text-white gap-2">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <div>
                                <h2 class="text-xl font-bold leading-none"><?php echo htmlspecialchars($product_name); ?></h2>
                                <!-- แสดงแท็กหากมีการกรองด้วยประเภทย่อย -->
                                <?php if(!empty($filter_product_option)): ?>
                                    <span class="inline-block mt-1 text-[10px] bg-white/20 px-2 py-0.5 rounded backdrop-blur-sm font-semibold tracking-wide">
                                        เฉพาะ: <?php echo htmlspecialchars($filter_product_option); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-left sm:text-right w-full sm:w-auto">
                            <p class="text-[10px] uppercase tracking-widest opacity-80 font-medium">ยอดรวมจากผลลัพธ์นี้</p>
                            <p class="text-lg font-black"><?php echo number_format($sum_qty); ?> ชิ้น <span class="font-normal text-sm opacity-90 mx-1">|</span> ฿<?php echo number_format($sum_amt, 2); ?></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-100">
                                    <th class="p-4 w-16 text-center">อันดับ</th>
                                    <th class="p-4">ผู้เข้าประกวด</th>
                                    <th class="p-4 text-center">ประเภทการประกวด</th>
                                    <th class="p-4 text-center">จำนวน (ชิ้น/ใบ)</th>
                                    <th class="p-4 text-right">ยอดเงิน (บาท)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white/40">
                                <?php 
                                $rank = 1;
                                foreach ($contestants as $person): 
                                ?>
                                    <tr class="hover:bg-white/80 transition-colors">
                                        <td class="p-4 text-center text-slate-400 font-bold">
                                            <?php if($rank <= 3): ?>
                                                <span class="inline-block w-6 h-6 rounded-full <?php echo $rank == 1 ? 'bg-amber-100 text-amber-600' : ($rank == 2 ? 'bg-slate-200 text-slate-600' : 'bg-orange-100 text-orange-600'); ?> flex items-center justify-center text-xs">
                                                    <?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php echo $rank; ?>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="p-4">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="text-[10px] font-bold bg-brand-100 text-brand-600 px-2 py-0.5 rounded border border-brand-200"><?php echo htmlspecialchars($person['contestant_number']); ?></span>
                                            </div>
                                            <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($person['name_th']); ?> <span class="font-normal text-xs text-slate-500">(น้อง<?php echo htmlspecialchars($person['nickname']); ?>)</span></p>
                                        </td>

                                        <td class="p-4 text-center">
                                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo htmlspecialchars($person['category']); ?></span>
                                        </td>
                                        
                                        <td class="p-4 text-center">
                                            <span class="text-lg font-black text-brand-600"><?php echo number_format($person['total_quantity']); ?></span>
                                        </td>
                                        
                                        <td class="p-4 text-right">
                                            <span class="font-bold text-slate-700">฿<?php echo number_format($person['total_amount'], 2); ?></span>
                                        </td>
                                    </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
        <?php endif; ?>

    </main>

    <!-- Script จัดการ Dynamic Dropdown -->
    <script>
        // กำหนดข้อมูลประเภทย่อย
        const optionsMap = {
            'CAMPUS BY GQ': [
                'MEN LONG SLEEVE SHIRT', 'MEN PANTS', 'MEN SHORT SLEEVE SHIRT',
                'WOMEN PLEATED SKIRT', 'WOMEN SHORT SLEEVE SHIRT', 'WOMEN PENCIL SHORT'
            ],
            'น้ำดื่ม RMUTP': ['ขนาด 350 Ml', 'ขนาด 500 Ml'],
            'บัตรวิ่งติดมันส์': ['ระยะ 3 Km', 'ระยะ 6 Km','ระยะ 12 Km', 'บัตร VIP', 'บัตร VVIP']
        };

        // ค่าปัจจุบันที่แอดมินเลือกไว้ (รับมาจาก PHP)
        const currentOption = '<?php echo htmlspecialchars($filter_product_option); ?>';

        function updateProductOptionDropdown() {
            const typeSelect = document.getElementById('filter_product_type').value;
            const optSelect = document.getElementById('filter_product_option');
            
            // รีเซ็ตประเภทย่อย
            optSelect.innerHTML = '<option value="">-- ทั้งหมด --</option>';
            
            // ถ้ามีการเลือกประเภทหลัก ให้ใส่ประเภทย่อยลงไป
            if (optionsMap[typeSelect]) {
                optionsMap[typeSelect].forEach(opt => {
                    const selected = (opt === currentOption) ? 'selected' : '';
                    optSelect.innerHTML += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
            }
        }
        
        // ให้ทำงานทันทีตอนโหลดหน้าเว็บ เพื่อแสดงตัวเลือกที่เคยค้นหาค้างไว้
        updateProductOptionDropdown();
    </script>

    <style>
        /* จัดการหน้าตาตอนสั่งพิมพ์ (Ctrl+P) */
        @media print {
            body { background: white; }
            .no-print, aside, button { display: none !important; }
            .glass-panel { box-shadow: none; border: 1px solid #e2e8f0; }
            main { padding: 0 !important; overflow: visible !important; }
            .fixed { display: none; }
        }
    </style>
</body>
</html>