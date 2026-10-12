<?php
// ไฟล์: admin/sidebar.php

// 1. ตรวจสอบชื่อไฟล์ปัจจุบันที่กำลังเปิดอยู่ เพื่อนำไปทำ (Active Menu)
$current_page = basename($_SERVER['PHP_SELF']);

// 2. ดึงจำนวนสลิปที่รอตรวจสอบ เพื่อนำไปแสดงเป็นตัวเลขแจ้งเตือน (Badge) ในเมนู
try {
    $stmt_sidebar_pending = $pdo->query("SELECT COUNT(*) FROM rma_slips WHERE status = 'pending'");
    $sidebar_total_pending = $stmt_sidebar_pending->fetchColumn();
} catch (PDOException $e) {
    $sidebar_total_pending = 0;
}

// 3. จัดเตรียมข้อมูลโปรไฟล์แอดมิน
$admin_name = $_SESSION['admin_name'] ?? 'ผู้ดูแลระบบ';
$admin_email = $_SESSION['admin_email'] ?? 'admin@rmutp.ac.th';
$admin_profile = $_SESSION['admin_profile'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($admin_name) . '&background=8b5cf6&color=fff';
?>

<!-- ================= Sidebar (เมนูด้านซ้ายกลาง) ================= -->
<aside class="w-72 bg-white/90 backdrop-blur-xl border-r border-slate-200 shadow-xl hidden md:flex flex-col z-20 h-full no-print">
    
    <!-- โลโก้และชื่อระบบ -->
    <div class="p-6 text-center flex flex-col items-center">
        <img src="../assets/images/logo.png" alt="Logo" class="w-20 h-20 object-contain mb-2 drop-shadow-sm" onerror="this.style.display='none'">
        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Admin <span class="text-brand-600">Portal</span></h2>
        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-widest">RMUTP AWARDS 2026</p>
    </div>

    <!-- ข้อมูลโปรไฟล์แอดมิน -->
    <div class="px-6 pb-4 border-b border-slate-100">
        <div class="bg-slate-50 rounded-2xl p-3 flex items-center gap-3 border border-slate-100 shadow-inner">
            <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm shrink-0 bg-brand-100">
                <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Admin Profile" class="w-full h-full object-cover">
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-slate-700 truncate" title="<?php echo htmlspecialchars($admin_name); ?>">
                    <?php echo htmlspecialchars($admin_name); ?>
                </p>
                <p class="text-[10px] font-medium text-slate-400 truncate" title="<?php echo htmlspecialchars($admin_email); ?>">
                    <?php echo htmlspecialchars($admin_email); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- รายการเมนูนำทาง -->
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        
        <a href="dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            แดชบอร์ดภาพรวม
        </a>

        <a href="login_analytics.php" class="sidebar-link <?php echo ($current_page == 'login_analytics.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
            สถิติการใช้งานระบบ
        </a>
        
        <a href="verify_slips.php" class="sidebar-link <?php echo ($current_page == 'verify_slips.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all relative">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            ตรวจสอบสลิปโอนเงิน
            <?php if($sidebar_total_pending > 0): ?>
                <span class="absolute right-4 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse"><?php echo $sidebar_total_pending; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="manage_contestants.php" class="sidebar-link <?php echo ($current_page == 'manage_contestants.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            จัดการผู้เข้าประกวด
        </a>
        
        <a href="reports.php" class="sidebar-link <?php echo ($current_page == 'reports.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            รายงานจัดอันดับ Top 10
        </a>

        <a href="report_individual.php" class="sidebar-link <?php echo ($current_page == 'report_individual.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            รายงานรายบุคคล
        </a>

        <a href="report_products.php" class="sidebar-link <?php echo ($current_page == 'report_products.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            รายงานตามผลิตภัณฑ์
        </a>

        <a href="login_logs.php" class="sidebar-link <?php echo ($current_page == 'login_logs.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            ประวัติล็อกอินผู้เข้าประกวด
        </a>

        <a href="admin_login_logs.php" class="sidebar-link <?php echo ($current_page == 'admin_login_logs.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
            ความปลอดภัยแอดมิน
        </a>
        
        <a href="settings.php" class="sidebar-link <?php echo ($current_page == 'settings.php') ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-600'; ?> flex items-center px-4 py-3.5 rounded-xl text-sm font-medium transition-all">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            ตั้งค่าระบบ
        </a>
        
    </nav>
    
    <!-- ปุ่มออกจากระบบ -->
    <div class="p-6 border-t border-slate-100">
        <a href="logout.php" class="flex items-center justify-center w-full px-4 py-3 bg-slate-50 text-slate-600 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-colors font-semibold text-sm border border-slate-200 hover:border-rose-200 shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            ออกจากระบบ
        </a>
    </div>
</aside>