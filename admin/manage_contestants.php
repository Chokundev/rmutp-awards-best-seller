<?php
// ไฟล์: admin/manage_contestants.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM rma_contestants ORDER BY category ASC, contestant_number ASC");
    $contestants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้เข้าประกวด - Admin RMUTP AWARDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { brand: { 50: '#f5f3ff', 100: '#ede9fe', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 900: '#4c1d95' } },
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.1); }
        .sidebar-link { transition: all 0.2s ease-in-out; }
        .sidebar-link:hover { background-color: #f5f3ff; color: #7c3aed; padding-left: 1.5rem; }
        .sidebar-link.active { background-color: #7c3aed; color: white; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3); }
        .modal-active { display: flex !important; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>
    <div class="fixed bottom-[-10%] right-[20%] w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 z-0"></div>

    <!-- ================= Sidebar ================= -->
    <?php include 'sidebar.php'; ?>

    <!-- ================= Main Content ================= -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto p-6 md:p-10 relative z-10">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4" data-aos="fade-down">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">รายชื่อผู้เข้าประกวด</h1>
                <p class="text-slate-500 mt-1">จัดการข้อมูล เพิ่ม แก้ไข และลบผู้เข้าประกวด</p>
            </div>
            <button onclick="openFormModal('add')" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                เพิ่มผู้เข้าประกวดใหม่
            </button>
        </div>

        <div class="glass-panel rounded-[2rem] overflow-hidden flex flex-col flex-1 shadow-xl" data-aos="fade-up">
            <div class="overflow-x-auto flex-1 p-2">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-500 text-xs uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="p-4">โปรไฟล์ & หมายเลข</th>
                            <th class="p-4">รหัสนักศึกษา</th>
                            <th class="p-4">ชื่อ - นามสกุล</th>
                            <th class="p-4">ประเภทประกวด</th>
                            <th class="p-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/40">
                        <?php foreach ($contestants as $c): 
                            $img_src = !empty($c['profile_image']) ? '../assets/uploads/profiles/' . htmlspecialchars($c['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($c['name_th'] ?? 'User') . '&background=8b5cf6&color=fff';
                            $json_data = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="hover:bg-white/80 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-brand-200 shadow-sm shrink-0">
                                            <img src="<?php echo $img_src; ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <span class="inline-block px-2.5 py-1 bg-brand-100 text-brand-700 font-bold rounded-lg text-xs mb-1">
                                                 <?php echo htmlspecialchars($c['contestant_number']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <!-- แสดง stdcode ในตาราง -->
                                    <p class="font-medium text-slate-700 text-sm"><?php echo htmlspecialchars($c['stdcode']); ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($c['name_th']); ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5">น้อง<?php echo htmlspecialchars($c['nickname'] ?? '-'); ?></p>
                                </td>
                                <td class="p-4">
                                    <span class="font-medium text-slate-700 text-sm"><?php echo htmlspecialchars($c['category']); ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="viewContestant(<?php echo $json_data; ?>)" class="p-2 bg-slate-100 hover:bg-brand-100 text-slate-600 hover:text-brand-600 rounded-lg transition-colors border border-slate-200" title="ดูข้อมูล">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                        <button onclick="openFormModal('edit', <?php echo $json_data; ?>)" class="p-2 bg-slate-100 hover:bg-amber-100 text-slate-600 hover:text-amber-600 rounded-lg transition-colors border border-slate-200" title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button onclick="deleteContestant(<?php echo $c['id']; ?>)" class="p-2 bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-600 rounded-lg transition-colors border border-slate-200" title="ลบข้อมูล">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ================= Modal Form (เพิ่ม/แก้ไข) ================= -->
    <div id="formModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex-col items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-[2rem] w-full max-w-xl shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden" id="modalContent">
            
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">เพิ่มผู้เข้าประกวดใหม่</h3>
                <button onclick="closeFormModal()" class="text-slate-400 hover:text-red-500 p-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>

            <form id="contestantForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="form_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">หมายเลข (No.) <span class="text-red-500">*</span></label>
                        <input type="text" name="contestant_number" id="form_number" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">รหัสนักศึกษา (stdcode) <span class="text-red-500">*</span></label>
                        <input type="text" name="stdcode" id="form_stdcode" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">ประเภทประกวด <span class="text-red-500">*</span></label>
                    <select name="category" id="form_category" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none bg-white">
                        <option value="">- เลือกประเภท -</option>
                        <option value="MISTER">MISTER</option>
                        <option value="MISS">MISS</option>
                        <option value="MISS SEASON">MISS SEASON</option>
                        <option value="MISS JUMBO QUEEN">MISS JUMBO QUEEN</option>
                        <option value="MISS CONGENIALITY">MISS CONGENIALITY</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">ชื่อ-สกุล (ไทย) <span class="text-red-500">*</span></label>
                        <input type="text" name="name_th" id="form_name_th" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">ชื่อ-สกุล (อังกฤษ) <span class="text-red-500">*</span></label>
                        <input type="text" name="name_en" id="form_name_en" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">ชื่อเล่น <span class="text-red-500">*</span></label>
                    <input type="text" name="nickname" id="form_nickname" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">รูปภาพโปรไฟล์ (ไม่เกิน 2MB)</label>
                    <input type="file" name="profile_image" accept="image/jpeg, image/png" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                    <p class="text-xs text-slate-400 mt-1" id="imgHelpText">รองรับไฟล์ JPG, PNG</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeFormModal()" class="px-5 py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200">ยกเลิก</button>
                    <button type="submit" id="btnSave" class="px-5 py-2.5 bg-brand-600 text-white font-semibold rounded-xl hover:bg-brand-700">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        function openFormModal(action, data = null) {
            const modal = document.getElementById('formModal');
            const content = document.getElementById('modalContent');
            const form = document.getElementById('contestantForm');
            
            form.reset();
            document.getElementById('form_action').value = action;

            if (action === 'edit' && data) {
                document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลผู้เข้าประกวด';
                document.getElementById('form_id').value = data.id;
                document.getElementById('form_number').value = data.contestant_number;
                document.getElementById('form_stdcode').value = data.stdcode;
                document.getElementById('form_name_th').value = data.name_th;
                document.getElementById('form_name_en').value = data.name_en;
                document.getElementById('form_nickname').value = data.nickname;
                document.getElementById('form_category').value = data.category;
                document.getElementById('imgHelpText').innerText = 'ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรูป';
            } else {
                document.getElementById('modalTitle').innerText = 'เพิ่มผู้เข้าประกวดใหม่';
                document.getElementById('form_id').value = '';
                document.getElementById('imgHelpText').innerText = 'รองรับไฟล์ JPG, PNG';
            }

            modal.classList.add('modal-active');
            setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
        }

        function closeFormModal() {
            const modal = document.getElementById('formModal');
            const content = document.getElementById('modalContent');
            modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
            setTimeout(() => { modal.classList.remove('modal-active'); }, 300);
        }

        function viewContestant(data) {
            const profileImg = data.profile_image ? `../assets/uploads/profiles/${data.profile_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name_th)}&background=8b5cf6&color=fff`;
            Swal.fire({
                title: 'ข้อมูลผู้เข้าประกวด',
                html: `
                    <div class="text-left mt-4">
                        <div class="flex justify-center mb-4"><img src="${profileImg}" class="w-24 h-24 rounded-full object-cover border-4 border-brand-100"></div>
                        <div class="bg-slate-50 p-4 rounded-xl space-y-2 text-sm">
                            <p><b>ประเภท :</b> ${data.category}</p>
                            <p><b>หมายเลข :</b> ${data.contestant_number}</p>
                            <p><b>รหัสนักศึกษา :</b> ${data.stdcode}</p>
                            <p><b>ชื่อ-สกุล (TH) :</b> ${data.name_th}</p>
                            <p><b>ชื่อ-สกุล (EN) :</b> ${data.name_en}</p>
                            <p><b>ชื่อเล่น :</b> น้อง${data.nickname}</p>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: { popup: 'rounded-[1.5rem]' }
            });
        }

        function deleteContestant(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ข้อมูลและรูปโปรไฟล์จะถูกลบออกจากระบบอย่างถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ใช่, ลบเลย',
                cancelButtonText: 'ยกเลิก',
                customClass: { popup: 'rounded-[1.5rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    fetch('api/contestant_api.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', showConfirmButton: false, timer: 1500 }).then(() => location.reload());
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    });
                }
            });
        }

        document.getElementById('contestantForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSave');
            btn.innerHTML = 'กำลังบันทึก...'; btn.disabled = true;
            
            const formData = new FormData(this);
            fetch('api/contestant_api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, showConfirmButton: false, timer: 1500 }).then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    btn.innerHTML = 'บันทึกข้อมูล'; btn.disabled = false;
                }
            })
            .catch(err => {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
                btn.innerHTML = 'บันทึกข้อมูล'; btn.disabled = false;
            });
        });
    </script>
</body>
</html>