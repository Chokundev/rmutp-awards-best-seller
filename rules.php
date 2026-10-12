<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อปฏิบัติและกฎเกณฑ์ - RMUTP AWARDS 2026</title>
    
    <!-- ฟอนต์ Prompt จาก Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        rmutp: {
                            light: '#a78bfa',
                            DEFAULT: '#7c3aed',
                            dark: '#4c1d95',
                        }
                    }
                }
            }
        }
    </script>

    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* สไตล์ Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #ede9fe 50%, #ddd6fe 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="py-12 px-4 relative overflow-x-hidden text-gray-800">

    <!-- วงกลมตกแต่งฉากหลัง -->
    <div class="fixed top-[-10%] left-[-10%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-50 z-0"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-50 z-0"></div>

    <main class="container mx-auto max-w-3xl relative z-10">
        
        <div data-aos="fade-down" class="mb-6">
            <a href="index.php" class="inline-flex items-center text-rmutp hover:text-rmutp-dark transition font-medium">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                กลับหน้าหลัก
            </a>
        </div>

        <div data-aos="fade-up" data-aos-duration="800" class="glass rounded-3xl p-8 md:p-12 shadow-xl">
            
            <div class="text-center mb-8 border-b pb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">ข้อปฏิบัติและกฎเกณฑ์</h1>
                <p class="text-rmutp font-medium text-lg">การประกวด RMUTP AWARDS 2026 - BEST SELLER</p>
            </div>

            <!-- เนื้อหากฎเกณฑ์คร่าวๆ (สามารถแก้ไขข้อความได้ตามต้องการ) -->
            <div class="space-y-4 text-gray-600 leading-relaxed mb-10">
                <h3 class="text-xl font-bold text-gray-800">📌 เงื่อนไขการเข้าร่วม</h3>
                <ul class="list-disc pl-5 space-y-2">
                    <li>ผู้เข้าประกวดต้องเป็นนักศึกษาปัจจุบัน สถานะปกติ (รหัสสถานะ 10-15)</li>
                    <li>ผู้เข้าประกวดต้องลงทะเบียนในประเภทที่กำหนดไว้อย่างถูกต้อง</li>
                    <li>สลิปที่นำมาแนบต้องเป็นยอดการขายที่เกิดขึ้นจริงในช่วงเวลาที่กำหนดเท่านั้น</li>
                    <li>หากตรวจพบการทุจริต คณะกรรมการขอสงวนสิทธิ์ในการตัดสิทธิ์ทันที</li>
                </ul>
            </div>

            <!-- ปุ่มดาวน์โหลด PDF และ ปุ่มกลับหน้าหลัก -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center bg-gray-50 p-6 rounded-2xl border border-gray-100">
                
                <!-- หมายเหตุ: แก้ไขลิงก์ href ให้ตรงกับตำแหน่งไฟล์ PDF ของคุณ -->
                <a href="assets/pdf/rules_rmutp_awards.pdf" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-rmutp to-rmutp-dark text-white rounded-xl font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    ดาวน์โหลดเอกสาร (PDF)
                </a>

            </div>

        </div>
    </main>

    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
        });
    </script>
</body>
</html>