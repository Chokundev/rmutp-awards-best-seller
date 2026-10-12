<?php
// ไฟล์: admin/index.php
session_start();

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RMUTP AWARDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' }
                    },
                    animation: { 'blob': 'blob 7s infinite' },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- AOS Animation & SweetAlert2 -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { 
            background-color: #f8fafc; 
            min-height: 100vh; 
        }
        .glass-panel { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.9); 
            box-shadow: 0 25px 50px -12px rgba(124, 58, 237, 0.2); 
        }
        .pattern-dots {
            background-image: radial-gradient(rgba(255, 255, 255, 0.2) 2px, transparent 2px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="flex items-center justify-center relative overflow-hidden text-slate-800 p-4 sm:p-8">

    <div class="absolute top-[10%] left-[10%] w-72 md:w-96 h-72 md:h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob z-0"></div>
    <div class="absolute bottom-[10%] right-[10%] w-72 md:w-96 h-72 md:h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000 z-0"></div>

    <main class="container mx-auto z-10 flex justify-center items-center w-full min-h-[90vh]">
        
        <div data-aos="zoom-in" data-aos-duration="800" class="glass-panel rounded-[2.5rem] w-full max-w-4xl relative overflow-hidden flex flex-col md:flex-row shadow-2xl">
            
            <!-- ================= คอลัมน์ซ้าย: ภาพลักษณ์แบรนด์ ================= -->
            <div class="w-full md:w-5/12 bg-gradient-to-br from-brand-600 to-indigo-900 p-10 md:p-12 text-white flex flex-col justify-center relative overflow-hidden min-h-[300px]">
                
                <!-- ลาย Pattern และวงกลมตกแต่งภายในคอลัมน์ซ้าย -->
                <div class="absolute inset-0 pattern-dots opacity-30"></div>
                <div class="absolute -top-16 -left-16 w-48 h-48 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-brand-400 opacity-20 rounded-full blur-3xl"></div>
                
                <!-- เนื้อหาฝั่งซ้าย (Logo & Text) -->
                <div class="relative z-10">
                    <div class="w-48 h-48 p-4 mb-6">
                        <img src="../assets/images/logo.png" alt="Logo" class="w-48 h-auto object-contain filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-2">Admin<br>Portal</h1>
                    <p class="text-brand-100 font-light text-sm md:text-base leading-relaxed">
                        ระบบจัดการและตรวจสอบหลักฐานการจำหน่ายผลิตภัณฑ์ RMUTP AWARDS 2026
                    </p>
                </div>
            </div>
            
            <!-- ================= คอลัมน์ขวา: ฟอร์มเข้าสู่ระบบ ================= -->
            <div class="w-full md:w-7/12 p-10 md:p-14 lg:p-16 flex flex-col justify-center bg-white/50 backdrop-blur-md">
                
                <div class="mb-10 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-slate-800 mb-1">ยินดีต้อนรับกลับมา</h2>
                    <p class="text-slate-500 text-sm">กรุณาลงชื่อเข้าใช้ด้วยบัญชี Google ที่ได้รับสิทธิ์</p>
                </div>

                <!-- กล่องปุ่มกด Google Sign-In -->
                <div class="space-y-6">
                    <button id="googleSignInBtn" class="w-full flex items-center justify-center gap-3 bg-white border border-slate-200 text-slate-700 py-4 px-6 rounded-2xl font-semibold shadow-sm hover:shadow-md hover:border-brand-300 transform hover:-translate-y-1 active:scale-95 transition-all duration-200 group">
                        <svg class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        ลงชื่อเข้าใช้ด้วย Google
                    </button>
                </div>
                
                <div class="mt-12 text-center md:text-left border-t border-slate-100 pt-6">
                    <a href="../index.php" class="text-sm text-slate-400 hover:text-brand-600 transition-colors font-medium flex items-center justify-center md:justify-start gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        กลับสู่หน้าหลักระบบ
                    </a>
                </div>
            </div>
            
        </div>
    </main>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ once: true });</script>

    <!-- โค้ด Firebase -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

        // *** เปลี่ยนตรงนี้เป็นค่า Firebase Config ของคุณ ***
        const firebaseConfig = {
            ///////
            };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        const btn = document.getElementById('googleSignInBtn');

        btn.addEventListener('click', () => {
            // UI แจ้งเตือนขณะโหลด
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-slate-500 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังเชื่อมต่อ...';
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');

            signInWithPopup(auth, provider)
                .then((result) => {
                    const user = result.user;
                    const userData = {
                        email: user.email,
                        name: user.displayName,
                        picture: user.photoURL
                    };
                    verifyAdminWithBackend(userData);
                }).catch((error) => {
                    console.error("Firebase Error: ", error);
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อบัญชี Google ได้', 'error');
                    resetButton();
                });
        });

        //  อัปเดต: ฟังก์ชันรับพารามิเตอร์เป็น Object (userData)
        function verifyAdminWithBackend(userData) {
            fetch('api/auth_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData) // ส่งไปทั้งก้อน
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ปฏิเสธการเข้าถึง',
                        text: data.message,
                        confirmButtonColor: '#7c3aed'
                    });
                    resetButton();
                    auth.signOut(); // ล็อกเอาท์ออกจาก Google หากไม่มีสิทธิ์
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้',
                    confirmButtonColor: '#7c3aed'
                });
                resetButton();
            });
        }

        // คืนค่าปุ่มให้กลับมาเหมือนเดิมหากเกิด Error
        function resetButton() {
            btn.innerHTML = `<svg class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg> ลงชื่อเข้าใช้ด้วย Google`;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    </script>
</body>
</html>
