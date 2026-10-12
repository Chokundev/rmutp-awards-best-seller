<?php
// ไฟล์: bestseller_overlay.php
session_start();
include '../config.php';

// ==========================================
// 1. API: สำหรับดึงข้อมูลยอดขายแบบ Real-time (JSON)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'fetch_scores') {
    header('Content-Type: application/json');
    try {
        // ดึงยอดขายรวมจากสลิปที่ได้รับการอนุมัติแล้ว
        $stmt = $pdo->query("
            SELECT 
                c.contestant_number, 
                c.nickname, 
                c.category,
                c.profile_image, 
                COALESCE(SUM(s.amount), 0) as total_sales
            FROM rma_contestants c
            LEFT JOIN rma_slips s ON c.id = s.contestant_id AND s.status = 'approved'
            GROUP BY c.id
        ");
        
        $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = ['MISTER', 'MISS', 'MISS SEASON', 'MISS JUMBO QUEEN', 'MISS CONGENIALITY'];
        $grouped_data = array_fill_keys($categories, []);

        // นำข้อมูลเข้าสู่หมวดหมู่
        foreach ($raw_data as $row) {
            $cat = strtoupper($row['category']);
            if (in_array($cat, $categories)) {
                $grouped_data[$cat][] = $row;
            }
        }

        foreach ($categories as $category) {
            if (empty($grouped_data[$category])) continue;
            
            $members = &$grouped_data[$category];
            // หาคนที่มียอดขายสูงสุดในหมวดหมู่นี้ เพื่อทำกราฟ 100%
            $max_sales = max(array_column($members, 'total_sales'));

            foreach ($members as &$m) {
                // คำนวณเปอร์เซ็นต์ความสูงของแท่งกราฟ (ถ้ายอดเท่ากับ max_sales ก็คือ 100%)
                // แต่ถ้าไม่มีใครขายได้เลย (max_sales = 0) ให้สูง 0%
                $m['sales_percent'] = ($max_sales > 0) ? ($m['total_sales'] / $max_sales) * 100 : 0;
                
                // ตกแต่งรูปโปรไฟล์
                $filename = basename($m['profile_image']); 
                $m['img_src'] = !empty($m['profile_image']) 
                    ? 'https://sc-so.rmutp.ac.th/rmutp-awards-seller/assets/uploads/profiles/' . htmlspecialchars($filename) 
                    : 'https://ui-avatars.com/api/?name='.urlencode($m['nickname']).'&background=334155&color=fff';
            }

            // เรียงลำดับคนขายได้มากสุดไว้หน้าสุด
            usort($members, function($a, $b) {
                return $b['total_sales'] <=> $a['total_sales'];
            });

            // เอาแค่ 5 อันดับสำหรับชาย/หญิง และ 3 อันดับสำหรับรางวัลพิเศษ
            if ($category === 'MISTER' || $category === 'MISS') {
                $grouped_data[$category] = array_slice($members, 0, 5);
            } else {
                $grouped_data[$category] = array_slice($members, 0, 3);
            }
        }
        
        $filtered_data = array_filter($grouped_data);
        echo json_encode(['status' => 'success', 'data' => $filtered_data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Seller Overlay - RMUTP AWARDS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@500;700;900&family=Prompt:wght@500;700;900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Google Sans', 'Prompt', 'sans-serif'] },
                    colors: {
                        brand: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 900: '#78350f' } 
                    }
                }
            }
        }
    </script>
    <style>
        
        body { 
            background-color: #00FF00; 
            overflow: hidden; 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh;
            color: #ffffff;
        }
        
        .overlay-container {
            width: 512px;
            height: 640px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .txt-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9), 0px 0px 8px rgba(0,0,0,0.6);
        }

        .slide-fade {
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        }
        .slide-fade.active {
            opacity: 1;
            pointer-events: auto;
            z-index: 10;
        }

        .bar-fill {
            height: 0; 
            width: 100%;
            transition: height 1.5s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .stacked-bar-container {
            width: 40px; 
            height: 250px; 
            background: rgba(255, 255, 255, 0.15); 
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end; 
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 6px rgba(0,0,0,0.5);
        }
        
        /* กราฟยอดขาย (สีทอง) */
        .sales-bar { background: linear-gradient(0deg, #d97706, #fbbf24); }
    </style>
</head>
<body>

    <div class="overlay-container" id="mainContainer">
        <div id="loadingState" class="flex items-center justify-center h-full">
            <p class="text-xl font-bold txt-shadow animate-pulse">กำลังโหลดข้อมูลยอดจำหน่าย...</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('mainContainer');
            
            let currentDataString = ""; 
            let currentIndex = 0;
            let categoriesList = [];
            const displayTime = 10000; 

            // ฟังก์ชันดึงข้อมูลยอดขาย
            function fetchScores() {
                fetch('?action=fetch_scores')
                    .then(res => res.json())
                    .then(response => {
                        if (response.status === 'success') {
                            const newDataString = JSON.stringify(response.data);
                            
                            // อัปเดตเมื่อมีข้อมูลใหม่เข้ามาเท่านั้น
                            if (newDataString !== currentDataString) {
                                currentDataString = newDataString;
                                const newData = response.data;
                                categoriesList = Object.keys(newData);
                                
                                const loader = document.getElementById('loadingState');
                                if(loader) loader.remove();

                                updateUI(newData);
                            }
                        }
                    })
                    .catch(err => console.error("Fetch Error:", err));
            }

            // ฟังก์ชันสร้างหรืออัปเดตหน้าจอ
            function updateUI(newData) {
                categoriesList.forEach((cat, idx) => {
                    let slide = document.getElementById(`slide-${cat}`);
                    
                    if (!slide) {
                        slide = document.createElement('div');
                        slide.id = `slide-${cat}`;
                        slide.className = 'slide-fade category-slide';
                        slide.setAttribute('data-index', idx);
                        container.appendChild(slide);
                    }

                    const isActive = slide.classList.contains('active');
                    slide.innerHTML = generateSlideHTML(cat, newData[cat]);

                    if (isActive) {
                        setTimeout(() => {
                            const bars = slide.querySelectorAll('.bar-fill');
                            bars.forEach(bar => {
                                bar.style.height = bar.getAttribute('data-height');
                            });
                        }, 50);
                    }
                });

                const slides = document.querySelectorAll('.category-slide');
                if (slides.length > 0 && !document.querySelector('.slide-fade.active')) {
                    showSlide(0);
                }
            }

            // ฟังก์ชันสร้างโครงสร้าง HTML กราฟ
            function generateSlideHTML(categoryName, members) {
                const displayTitle = categoryName.replace(/-/g, ' ');
                let html = `
                    <div class="text-center mt-6 mb-8 shrink-0">
                        <h2 class="text-4xl font-black text-brand-400 uppercase tracking-widest txt-shadow mb-1">
                            BEST SELLER <br/ class="mt-2"> LIVE SCORE
                        </h2>
                        <div class="inline-block bg-white text-brand-900 border-2 border-brand-500 font-bold px-5 py-1.5 rounded-full text-lg shadow-[0_4px_10px_rgba(0,0,0,0.5)]">
                            ${displayTitle}
                        </div>
                    </div>
                    <div class="flex-1 flex justify-center items-end gap-3 pb-8 px-2">
                `;

                members.forEach((person, index) => {
                    const rank = index + 1;
                    const rankColorClass = rank === 1 ? 'text-yellow-400' : 'text-white';
                    const rankBadgeColor = rank === 1 ? 'bg-yellow-400 text-yellow-900' : (rank === 2 ? 'bg-slate-300 text-slate-800' : (rank === 3 ? 'bg-amber-600 text-white' : 'bg-slate-600 text-white'));
                    const rankBorderColor = rank === 1 ? 'border-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.5)]' : 'border-white';

                    // ฟอร์แมตยอดขายให้มีลูกน้ำ 
                    const formattedSales = parseFloat(person.total_sales).toLocaleString('en-US');

                    html += `
                        <div class="flex flex-col items-center justify-end w-[90px]">
                            <div class="score-value text-xl font-black mb-2 txt-shadow ${rankColorClass}">
                                ฿${formattedSales}
                            </div>
                            <div class="stacked-bar-container mb-4">
                                <div class="sales-bar bar-fill" data-height="${person.sales_percent}%"></div>
                            </div>
                            <div class="flex flex-col items-center text-center relative w-full">
                                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black z-10 shadow-md border border-white ${rankBadgeColor}">
                                    ${rank}
                                </div>
                                <div class="w-14 h-14 rounded-full overflow-hidden border-2 mb-1.5 ${rankBorderColor} shadow-[0_4px_6px_rgba(0,0,0,0.5)] bg-slate-800">
                                    <img src="${person.img_src}" class="w-full h-full object-cover" alt="profile">
                                </div>
                                <div class="text-[11px] text-yellow-300 font-black txt-shadow">${person.contestant_number}</div>
                                <div class="text-xs font-bold text-white truncate w-full px-1 txt-shadow">น้อง${person.nickname}</div>
                            </div>
                        </div>
                    `;
                });

                html += `
                    </div>
                    <div class="shrink-0 flex justify-center gap-4 mb-4">
                        <div class="flex items-center gap-1.5 txt-shadow">
                            <span class="w-4 h-4 rounded border border-white/50 bg-gradient-to-t from-[#d97706] to-[#fbbf24]"></span>
                            <span class="text-sm font-bold text-white tracking-wider">ยอดจำหน่ายสะสม (บาท)</span>
                        </div>
                    </div>
                `;
                return html;
            }

            // ฟังก์ชันควบคุมการแสดงสไลด์และแท่งกราฟ
            function showSlide(index) {
                const slides = document.querySelectorAll('.category-slide');
                if(slides.length === 0) return;

                slides.forEach(slide => {
                    slide.classList.remove('active');
                    const bars = slide.querySelectorAll('.bar-fill');
                    bars.forEach(bar => { bar.style.height = '0%'; });
                });

                const currentSlide = slides[index];
                if (currentSlide) {
                    currentSlide.classList.add('active');
                    setTimeout(() => {
                        const bars = currentSlide.querySelectorAll('.bar-fill');
                        bars.forEach(bar => {
                            const targetHeight = bar.getAttribute('data-height');
                            bar.style.height = targetHeight;
                        });
                    }, 100);
                }
            }
            
            // เรียกใช้ครั้งแรก
            fetchScores();

            // วนลูปสลับหน้าจอ (ทุกๆ 10 วินาที)
            setInterval(() => {
                if (categoriesList.length > 0) {
                    currentIndex++;
                    if (currentIndex >= categoriesList.length) {
                        currentIndex = 0;
                    }
                    showSlide(currentIndex);
                }
            }, displayTime);

            // วนลูปดึงข้อมูลจาก Server ทุกๆ 3 วินาที (ตรวจสอบอัปเดตใหม่)
            setInterval(() => {
                fetchScores();
            }, 3000);

        });
    </script>
</body>
</html>