<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้เข้าประกวด - RMUTP AWARDS</title>
    
    <!-- ฟอนต์ Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            900: '#4c1d95',
                        }
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
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
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 25px 50px -12px rgba(124, 58, 237, 0.15);
        }
    </style>
</head>
<body class="flex items-center justify-center relative overflow-hidden text-slate-800 p-4">

    <!-- แอนิเมชันพื้นหลัง -->
    <div class="absolute top-[10%] left-[10%] w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob z-0"></div>
    <div class="absolute bottom-[10%] right-[10%] w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000 z-0"></div>

    <main class="container mx-auto z-10 flex justify-center w-full">
        <!-- ฟอร์มล็อกอินสไตล์ Premium Glassmorphism -->
        <div data-aos="zoom-in" data-aos-duration="800" class="glass-panel rounded-[2rem] p-8 md:p-12 w-full max-w-md relative">
            
            <!-- ส่วน Header (โลโก้ + ข้อความ) -->
            <div class="text-center mb-8">
                <!-- โลโก้ (ดึงภาพมาจากโฟลเดอร์หลัก) -->
                <img src="../assets/images/logo.png" alt="RMUTP AWARDS Logo" class="w-48 md:w-48 h-auto object-contain mx-auto mb-4 drop-shadow-sm">
                
                <h1 class="text-xl md:text-3xl font-bold text-slate-800 tracking-tight mb-1">เข้าสู่ระบบ</h1>
                <p class="text-brand-600 font-medium text-sm">Best Seller Portal</p>
            </div>

            <!-- ฟอร์มเข้าสู่ระบบ -->
            <form id="loginForm" class="space-y-5">
                <!-- Input รหัสนักศึกษา -->
                <div>
                    <label for="stdcode" class="block text-sm font-medium text-slate-600 mb-1.5 ml-1">รหัสนักศึกษา (13 หลัก)</label>
                    <input type="text" id="stdcode" name="stdcode" required placeholder="กรอกรหัสประจำตัวนักศึกษา 13 หลักพร้อมขีด"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white/80 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all duration-200 outline-none shadow-sm text-slate-700">
                </div>

                <!-- Input เลขบัตรประชาชน -->
                <div>
                    <label for="citizenid" class="block text-sm font-medium text-slate-600 mb-1.5 ml-1">เลขประจำตัวประชาชน (13 หลัก)</label>
                    <input type="password" id="citizenid" name="citizenid" required placeholder="•••••••••••••"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white/80 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all duration-200 outline-none shadow-sm text-slate-700">
                </div>

                <!-- ปุ่ม Submit -->
                <button type="submit" id="btnSubmit" class="w-full mt-2 py-3.5 bg-gradient-to-r from-brand-600 to-indigo-600 text-white rounded-xl font-semibold shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 transform hover:-translate-y-1 active:scale-95 transition-all duration-200 flex justify-center items-center">
                    เข้าสู่ระบบ
                </button>
                
                <div class="text-center mt-6">
                    <a href="../index.php" class="text-sm text-slate-400 hover:text-brand-600 transition-colors font-medium flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        กลับสู่หน้าหลัก
                    </a>
                </div>
            </form>
        </div>
    </main>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // จัดการเมื่อกดปุ่ม Submit ฟอร์ม
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btnSubmit = document.getElementById('btnSubmit');
            const formData = new FormData(this);

            // เปลี่ยนสถานะปุ่มเป็นกำลังโหลด
            btnSubmit.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...';
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-90', 'cursor-not-allowed');
            btnSubmit.classList.remove('hover:-translate-y-1', 'active:scale-95');

            // ส่งข้อมูลไปที่ auth_process.php
            fetch('auth_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ยินดีต้อนรับ!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ไม่สามารถเข้าสู่ระบบได้',
                        text: data.message,
                        confirmButtonColor: '#7c3aed'
                    });
                    resetButton();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาดระบบ',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง',
                    confirmButtonColor: '#7c3aed'
                });
                resetButton();
            });

            function resetButton() {
                btnSubmit.innerHTML = 'เข้าสู่ระบบ';
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-90', 'cursor-not-allowed');
                btnSubmit.classList.add('hover:-translate-y-1', 'active:scale-95');
            }
        });
    </script>
</body>
</html>