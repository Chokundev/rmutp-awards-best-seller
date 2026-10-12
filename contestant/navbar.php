<?php
// ไฟล์: contestant/navbar.php
// รูปโปรไฟล์สำหรับ Navbar (ถ้าไม่มีใช้ Avatar สมมติ)
$nav_profile_image = !empty($contestant['profile_image']) ? '../assets/uploads/profiles/' . htmlspecialchars($contestant['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($contestant['name_th']) . '&background=8b5cf6&color=fff';
?>
<!-- Navbar ด้านบน -->
<nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/60 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            
            <!-- โลโก้ และ ชื่อระบบ -->
            <div class="flex items-center gap-3">
                <img src="../assets/images/logo.png" alt="Logo" class="w-10 h-10 sm:w-12 sm:h-12 object-contain" onerror="this.style.display='none'">
                <div class="hidden sm:block">
                    <h1 class="text-lg sm:text-xl font-extrabold text-slate-800 tracking-tight leading-tight">RMUTP AWARDS</h1>
                    <p class="text-[10px] sm:text-xs font-bold text-brand-600 tracking-widest uppercase">Best Seller Portal</p>
                </div>
            </div>

            <!-- เมนูฝั่งขวา (โปรไฟล์ & ออกจากระบบ) -->
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-slate-700">น้อง<?php echo htmlspecialchars($contestant['nickname']); ?></p>
                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($contestant['contestant_number']); ?></p>
                </div>
                
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full border-2 border-brand-100 overflow-hidden shadow-sm">
                    <img src="<?php echo $nav_profile_image; ?>" alt="Profile" class="w-full h-full object-cover">
                </div>

                <div class="h-6 w-px bg-slate-200 mx-1"></div>

                <a href="logout.php" class="flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-rose-50 text-rose-600 rounded-full hover:bg-rose-500 hover:text-white transition-colors duration-300 font-medium text-xs sm:text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="hidden sm:inline">ออกจากระบบ</span>
                </a>
            </div>
            
        </div>
    </div>
</nav>