<?php
// ไฟล์: admin/reports.php
session_start();
require_once '../config.php';

// ตรวจสอบสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

// 1. รับค่าตัวกรองประเภทการประกวด
$selected_category = $_GET['category'] ?? '';

try {
    // 2. ดึงประเภทการประกวดทั้งหมดมาแสดงใน Dropdown
    $stmt_cats = $pdo->query("SELECT DISTINCT category FROM rma_contestants ORDER BY category ASC");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

    $top10_contestants = [];

    // 3. หากมีการเลือกประเภท ให้ดึงข้อมูล Top 10 เฉพาะประเภทนั้น
    if (!empty($selected_category)) {
        $sql = "
            SELECT c.id, c.stdcode, c.contestant_number, c.name_th, 
                   COALESCE(SUM(s.amount), 0) AS total_sales
            FROM rma_contestants c
            LEFT JOIN rma_slips s ON c.id = s.contestant_id AND s.status = 'approved'
            WHERE c.category = :category
            GROUP BY c.id
            ORDER BY total_sales DESC, c.contestant_number ASC
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['category' => $selected_category]);
        $top10_contestants = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>รายงานจัดอันดับ Top 10 (แยกประเภท) - Admin RMUTP AWARDS</title>
    
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
        
        /* สไตล์จำลองกระดาษ A4 แนวนอน บนหน้าเว็บ */
        .a4-landscape-preview {
            width: 297mm;
            min-height: 210mm;
            background: white;
            margin: 0 auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 15mm 20mm;
            color: #000;
            display: flex;
            flex-direction: column;
        }

        /* ================= การตั้งค่าสำหรับการพิมพ์ (Print Styles) ================= */
        @media print {
            @page {
                size: A4 landscape; /* บังคับกระดาษ A4 แนวนอน */
                margin: 15mm; 
            }
            body { 
                background: white !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
            .no-print { display: none !important; }
            
            /* ลบเงาและปรับขนาดให้พอดีกระดาษจริง */
            .a4-landscape-preview { 
                width: 100% !important; 
                min-height: 100vh !important;
                padding: 0 !important; 
                margin: 0 !important; 
                box-shadow: none !important; 
            }
            
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000 !important; } /* บังคับเส้นขอบตารางสีดำเข้มตอนพิมพ์ */
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
        
        <!-- ส่วนควบคุมบนหน้าเว็บ (ซ่อนตอนพิมพ์) -->
        <div class="p-6 md:p-10 no-print bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">รายงานสรุปอันดับ Top 10 (แยกประเภท)</h1>
                    <p class="text-slate-500 text-sm mt-1">รูปแบบเอกสาร A4 แนวนอน (Landscape)</p>
                </div>
                <?php if (!empty($selected_category)): ?>
                    <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        สั่งพิมพ์รายงาน
                    </button>
                <?php endif; ?>
            </div>

            <!-- ฟอร์มเลือกประเภท -->
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 shadow-inner w-full md:w-1/2 lg:w-1/3">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">เลือกประเภทการประกวด</label>
                    <select name="category" onchange="this.form.submit()" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm font-medium">
                        <option value="">-- กรุณาเลือกประเภท --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected_category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- ================= พื้นที่แสดงเอกสาร A4 แนวนอน ================= -->
        <div class="p-6 md:p-10 flex-1 bg-slate-200/50 print:bg-white print:p-0 overflow-x-auto">
            
            <?php if (!empty($selected_category)): ?>
                <div class="a4-landscape-preview">
                    
                    <!-- ส่วนหัวเอกสาร (Header) -->
                    <div class="flex items-center justify-between border-b-4 border-black pb-4 mb-6 shrink-0">
                        <div class="flex items-center gap-6">
                            <!-- โลโก้ -->
                            <img src="../assets/images/logo.png" alt="Logo" class="w-24 h-24 object-contain" onerror="this.style.display='none'">
                            <div>
                                <h2 class="text-3xl font-extrabold uppercase tracking-widest text-black">RMUTP AWARDS 2026</h2>
                                <p class="text-lg font-semibold text-gray-800">Student Council & Organization RMUTP</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h1 class="text-2xl font-bold bg-black text-white px-6 py-2 inline-block uppercase tracking-wider rounded-sm">Official Top 10 Ranking</h1>
                            <p class="text-base mt-2 font-bold text-gray-800">ประเภทการประกวด: <span class="underline text-brand-700 print:text-black"><?php echo htmlspecialchars($selected_category); ?></span></p>
                            <p class="text-sm mt-1 font-medium text-gray-600">ข้อมูลอัปเดต ณ วันที่: <?php echo date('d/m/Y H:i'); ?> น.</p>
                        </div>
                    </div>

                    <!-- ตารางข้อมูล Top 10 -->
                    <div class="flex-1">
                        <table class="w-full text-base border-2 border-black">
                            <thead>
                                <tr class="bg-gray-200 border-b-2 border-black">
                                    <th class="py-3 px-4 text-center font-extrabold w-20 border-r border-gray-400 border-black">อันดับ</th>
                                    <th class="py-3 px-4 text-center font-extrabold w-32 border-r border-gray-400 border-black">หมายเลข</th>
                                    <th class="py-3 px-4 text-center font-extrabold w-48 border-r border-gray-400 border-black">รหัสนักศึกษา</th>
                                    <th class="py-3 px-6 text-left font-extrabold border-r border-gray-400 border-black">ชื่อ - นามสกุล</th>
                                    <th class="py-3 px-6 text-right font-extrabold w-48">ยอดขายรวม (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($top10_contestants) > 0): ?>
                                    <?php 
                                    $rank = 1;
                                    foreach ($top10_contestants as $c): 
                                        // ไฮไลต์ 3 อันดับแรก
                                        $rowClass = "bg-white";
                                        if ($rank == 1) $rowClass = "bg-yellow-100 font-bold";
                                        elseif ($rank == 2) $rowClass = "bg-gray-100 font-semibold";
                                        elseif ($rank == 3) $rowClass = "bg-orange-50 font-semibold";
                                    ?>
                                        <tr class="border-b border-gray-300 print:border-black <?php echo $rowClass; ?>">
                                            <td class="py-3 px-4 text-center border-r border-gray-300 print:border-black text-lg"><?php echo $rank++; ?></td>
                                            <td class="py-3 px-4 text-center font-bold border-r border-gray-300 print:border-black text-lg"><?php echo htmlspecialchars($c['contestant_number']); ?></td>
                                            <td class="py-3 px-4 text-center border-r border-gray-300 print:border-black"><?php echo htmlspecialchars($c['stdcode']); ?></td>
                                            <td class="py-3 px-6 text-left border-r border-gray-300 print:border-black text-lg"><?php echo htmlspecialchars($c['name_th']); ?></td>
                                            <td class="py-3 px-6 text-right font-extrabold text-black text-xl"><?php echo number_format($c['total_sales'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- เติมบรรทัดว่างให้ครบ 10 แถว เพื่อให้กระดาษเต็มและสวยงาม -->
                                    <?php for ($i = $rank; $i <= 10; $i++): ?>
                                        <tr class="border-b border-gray-200 print:border-black">
                                            <td class="py-3 px-4 text-center border-r border-gray-200 print:border-black text-gray-400"><?php echo $i; ?></td>
                                            <td class="py-3 px-4 border-r border-gray-200 print:border-black"></td>
                                            <td class="py-3 px-4 border-r border-gray-200 print:border-black"></td>
                                            <td class="py-3 px-6 border-r border-gray-200 print:border-black"></td>
                                            <td class="py-3 px-6"></td>
                                        </tr>
                                    <?php endfor; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500 italic text-lg">-- ยังไม่มียอดจำหน่ายในประเภทนี้ --</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>


                </div> <!-- สิ้นสุด A4 Preview -->
            
            <?php else: ?>
                <!-- แสดงเมื่อยังไม่ได้เลือกประเภท -->
                <div class="flex flex-col items-center justify-center h-full opacity-50 no-print">
                    <svg class="w-20 h-20 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <p class="text-2xl font-bold text-slate-500">กรุณาเลือกประเภทการประกวดด้านบน</p>
                    <p class="text-slate-400 mt-2">เพื่อแสดงรายงานสรุปยอดขาย 10 อันดับแรก</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>