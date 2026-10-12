<?php
// ไฟล์: admin/report_individual.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// 1. รับค่าตัวกรองจาก URL
$selected_category = $_GET['category'] ?? '';
$selected_contestant_id = $_GET['contestant_id'] ?? '';

try {
    // 2. ดึงประเภทการประกวดทั้งหมดมาแสดงใน Dropdown
    $stmt_cats = $pdo->query("SELECT DISTINCT category FROM rma_contestants ORDER BY category ASC");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

    $contestants_list = [];
    $report_data = [];
    $contestant_info = null;
    $total_amount = 0;

    // 3. หากมีการเลือกประเภท ให้ดึงรายชื่อผู้เข้าประกวดในประเภทนั้นมาแสดง
    if (!empty($selected_category)) {
        $stmt_list = $pdo->prepare("SELECT id, contestant_number, name_th FROM rma_contestants WHERE category = :cat ORDER BY contestant_number ASC");
        $stmt_list->execute(['cat' => $selected_category]);
        $contestants_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. หากมีการเลือกผู้เข้าประกวด ให้ดึงข้อมูลรายงาน
    if (!empty($selected_contestant_id)) {
        // ดึงข้อมูลส่วนตัว
        $stmt_info = $pdo->prepare("SELECT * FROM rma_contestants WHERE id = :id");
        $stmt_info->execute(['id' => $selected_contestant_id]);
        $contestant_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        // ดึงรายการยอดขายที่ "อนุมัติแล้ว"
        if ($contestant_info) {
            $stmt_slips = $pdo->prepare("
                SELECT slip_datetime, product_type, product_option, quantity, amount 
                FROM rma_slips 
                WHERE contestant_id = :id AND status = 'approved' 
                ORDER BY slip_datetime ASC, created_at ASC
            ");
            $stmt_slips->execute(['id' => $selected_contestant_id]);
            $report_data = $stmt_slips->fetchAll(PDO::FETCH_ASSOC);

            // คำนวณยอดรวม
            foreach ($report_data as $row) {
                $total_amount += $row['amount'];
            }
        }
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
    <title>รายงานรายบุคคล - Admin RMUTP AWARDS</title>
    
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
    
    <style>
        body { background-color: #f8fafc; }
        .sidebar-link { transition: all 0.2s ease-in-out; }
        .sidebar-link:hover { background-color: #f5f3ff; color: #7c3aed; padding-left: 1.5rem; }
        .sidebar-link.active { background-color: #7c3aed; color: white; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3); }
        
        /* สไตล์จำลองกระดาษ A4 บนหน้าเว็บ */
        .a4-preview {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 0 auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 20mm 15mm;
            position: relative;
        }

        /* ================= การตั้งค่าสำหรับการพิมพ์ (Print Styles) ================= */
        @media print {
            @page {
                size: A4 portrait; /* บังคับ A4 แนวตั้ง */
                margin: 15mm; /* ตั้งระยะขอบกระดาษจริง */
            }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            
            /* ลบเงาและ margin จำลองออกตอนพิมพ์จริง */
            .a4-preview { 
                width: 100% !important; 
                min-height: auto !important;
                padding: 0 !important; 
                margin: 0 !important; 
                box-shadow: none !important; 
                border: none !important; 
            }

            /* ควบคุมการตัดหน้ากระดาษของตาราง */
            table { page-break-inside: auto; width: 100%; border-collapse: collapse; }
            tr { break-inside: avoid; page-break-inside: avoid; page-break-after: auto; } /* ห้ามตัดแถวขาดครึ่ง */
            thead { display: table-header-group; } /* ให้แสดงหัวตารางซ้ำทุกหน้าถ้าล้น */
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <!-- พื้นหลังตกแต่ง (ซ่อนตอนพิมพ์) -->
    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0 no-print"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0 no-print"></div>

    <!-- ================= Sidebar (ซ่อนตอนพิมพ์) ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- ================= Main Content ================= -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto relative z-10">
        
        <!-- ส่วนควบคุมและตัวกรองข้อมูล (ซ่อนตอนพิมพ์) -->
        <div class="p-6 md:p-10 no-print bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">รายงานการจำหน่ายรายบุคคล</h1>
                    <p class="text-slate-500 text-sm mt-1">เอกสารทางการ A4 แนวตั้ง (พร้อมพิมพ์)</p>
                </div>
                <?php if ($contestant_info): ?>
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        สั่งพิมพ์รายงาน
                    </button>
                <?php endif; ?>
            </div>

            <!-- ฟอร์มค้นหาแบบ 2 ชั้น -->
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 shadow-inner">
                
                <!-- ชั้นที่ 1: เลือกประเภท -->
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">1. เลือกประเภทการประกวด</label>
                    <select name="category" onchange="this.form.submit()" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium">
                        <option value="">-- กรุณาเลือกประเภท --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ชั้นที่ 2: เลือกผู้เข้าประกวด (แสดงเฉพาะที่อยู่ในประเภทที่เลือก) -->
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">2. เลือกผู้เข้าประกวด</label>
                    <div class="flex gap-2">
                        <select name="contestant_id" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium" <?php echo empty($contestants_list) ? 'disabled' : ''; ?>>
                            <option value="">-- เลือกผู้เข้าประกวด --</option>
                            <?php foreach ($contestants_list as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $selected_contestant_id == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['contestant_number']); ?> - <?php echo htmlspecialchars($c['name_th']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-sm transition-colors text-sm shrink-0" <?php echo empty($contestants_list) ? 'disabled' : ''; ?>>
                            ดูรายงาน
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ================= พื้นที่แสดงเอกสาร A4 ================= -->
        <div class="p-6 md:p-10 flex-1 bg-slate-200/50 print:bg-white print:p-0">
            
            <?php if ($contestant_info): ?>
                
                <!-- กระดาษ A4 -->
                <div class="a4-preview text-black">
                    
                    <!-- ส่วนหัวเอกสาร (Header) -->
                    <div class="flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                        <div class="flex items-center gap-4">
                            <img src="../assets/images/logo.png" alt="Logo" class="w-20 h-20 object-contain" onerror="this.style.display='none'">
                            <div>
                                <h2 class="text-xl font-extrabold tracking-wide uppercase">RMUTP AWARDS 2026</h2>
                                <p class="text-sm font-semibold text-gray-600">Student Council & Organization RMUTP</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h1 class="text-xl font-bold bg-black text-white px-4 py-1 inline-block uppercase tracking-widest">Official Report</h1>
                            <p class="text-sm mt-2 font-medium">วันที่พิมพ์: <?php echo date('d/m/Y'); ?></p>
                        </div>
                    </div>

                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold underline mb-1">รายงานสรุปหลักฐานการจำหน่ายผลิตภัณฑ์ (รายบุคคล)</h3>
                        <p class="text-sm">แคมเปญส่งเสริมการขาย BEST SELLER ประจำปีการศึกษา 2569</p>
                    </div>

                    <!-- ข้อมูลผู้เข้าประกวด -->
                    <div class="bg-gray-50 border border-gray-300 p-4 rounded mb-8">
                        <div class="grid grid-cols-2 gap-y-2 text-sm">
                            <div><span class="font-bold w-32 inline-block">รหัสนักศึกษา :</span> <?php echo htmlspecialchars($contestant_info['stdcode'] ?? '-'); ?></div>
                            <div><span class="font-bold w-32 inline-block">ประเภทการประกวด :</span> <?php echo htmlspecialchars($contestant_info['category']); ?></div>
                            <div><span class="font-bold w-32 inline-block">ชื่อ-สกุล :</span> <?php echo htmlspecialchars($contestant_info['name_th']); ?></div>
                            <div><span class="font-bold w-32 inline-block">หมายเลข :</span> <span class="text-lg font-extrabold"><?php echo htmlspecialchars($contestant_info['contestant_number']); ?></span></div>
                        </div>
                    </div>

                    <!-- ตารางรายการ -->
                    <table class="w-full text-sm border-collapse mb-8">
                        <thead>
                            <tr class="bg-gray-100 border-y-2 border-black">
                                <th class="py-3 px-2 text-center font-bold w-12">ลำดับ</th>
                                <th class="py-3 px-2 text-left font-bold w-32">วันที่/เวลา (สลิป)</th>
                                <th class="py-3 px-2 text-left font-bold">ผลิตภัณฑ์ที่จำหน่าย</th>
                                <th class="py-3 px-2 text-center font-bold w-16">จำนวน</th>
                                <th class="py-3 px-2 text-right font-bold w-24">ยอดเงิน (฿)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($report_data) > 0): ?>
                                <?php 
                                $count = 1;
                                foreach ($report_data as $row): 
                                    $slip_date = !empty($row['slip_datetime']) ? date('d/m/Y H:i', strtotime($row['slip_datetime'])) : '-';
                                ?>
                                    <!-- break-inside: avoid ป้องกันการตัดขาดครึ่งเวลาปริ้นท์ตกหน้า -->
                                    <tr class="border-b border-gray-200">
                                        <td class="py-2 px-2 text-center text-gray-600"><?php echo $count++; ?></td>
                                        <td class="py-2 px-2"><?php echo $slip_date; ?></td>
                                        <td class="py-2 px-2">
                                            <p class="font-bold"><?php echo htmlspecialchars($row['product_type'] ?: '-'); ?></p>
                                            <?php if (!empty($row['product_option'])): ?>
                                                <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($row['product_option']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-2 text-center font-medium"><?php echo $row['quantity']; ?></td>
                                        <td class="py-2 px-2 text-right font-bold"><?php echo number_format($row['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500 italic">-- ไม่พบรายการแจ้งยอดที่ได้รับการอนุมัติ --</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <!-- ส่วนสรุปยอด (ติดกับท้ายตารางเสมอ) -->
                        <tfoot>
                            <tr class="border-y-2 border-black bg-gray-50">
                                <td colspan="4" class="py-3 px-4 text-right font-bold uppercase text-gray-700">ยอดรวมสุทธิ (Total Amount)</td>
                                <td class="py-3 px-2 text-right font-extrabold text-lg">฿<?php echo number_format($total_amount, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- ส่วนลายเซ็น (อยู่ด้านล่างสุด) -->
                    <!-- <div class="mt-16 flex justify-between px-10 text-sm break-inside-avoid">
                        <div class="text-center">
                            <p class="mb-12">ลงชื่อ ....................................................... ผู้จัดทำ</p>
                            <p class="font-medium">(.......................................................)</p>
                            <p class="text-gray-500 mt-1">คณะกรรมการฝ่ายประมวลผล</p>
                        </div>
                        <div class="text-center">
                            <p class="mb-12">ลงชื่อ ....................................................... ผู้รับรอง</p>
                            <p class="font-medium">(.......................................................)</p>
                            <p class="text-gray-500 mt-1">ประธานคณะกรรมการจัดงาน</p>
                        </div>
                    </div> -->

                </div> <!-- End A4 Preview -->

            <?php elseif (!empty($selected_category)): ?>
                <!-- แสดงข้อความเมื่อเลือกประเภทแล้วแต่ยังไม่เลือกคน -->
                <div class="flex flex-col items-center justify-center h-full opacity-50 no-print">
                    <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                    <p class="text-xl font-bold text-slate-500">กรุณาเลือกผู้เข้าประกวดเพื่อดูรายงาน</p>
                </div>
            <?php else: ?>
                <!-- แสดงข้อความหน้าเริ่มต้น -->
                <div class="flex flex-col items-center justify-center h-full opacity-50 no-print">
                    <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-xl font-bold text-slate-500">เลือกประเภทและผู้เข้าประกวดด้านบนเพื่อสร้างรายงาน</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>