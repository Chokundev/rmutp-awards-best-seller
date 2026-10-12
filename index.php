<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RMUTP AWARDS 2026 : Best Seller Portal</title>

    <!-- Google Font : Prompt -->
    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

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
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    },

                    keyframes: {
                        blob: {
                            '0%': {
                                transform: 'translate(0px, 0px) scale(1)'
                            },
                            '33%': {
                                transform: 'translate(30px, -50px) scale(1.1)'
                            },
                            '66%': {
                                transform: 'translate(-20px, 20px) scale(0.9)'
                            },
                            '100%': {
                                transform: 'translate(0px, 0px) scale(1)'
                            },
                        }
                    }
                }
            }
        }
    </script>

    <!-- AOS Animation -->
    <link
        href="https://unpkg.com/aos@2.3.1/dist/aos.css"
        rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #f8fafc;
            min-height: 100vh;
        }

        /* Glassmorphism */
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow:
                0 25px 50px -12px rgba(124, 58, 237, 0.15);
        }

        /* Countdown Number */
        .countdown-number {
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.03em;
        }

        /* Countdown Card */
        .countdown-item {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.08);
            transition: all 0.3s ease;
        }

        .countdown-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(124, 58, 237, 0.14);
        }

        /* Smooth button */
        .portal-button {
            transition: all 0.25s ease;
        }

        /* Prevent text selection on countdown */
        #countdown-box {
            user-select: none;
        }

        /* Mobile */
        @media (max-width: 640px) {

            .countdown-number {
                font-size: 1.5rem;
            }

            .countdown-label {
                font-size: 0.65rem;
            }

        }
    </style>

</head>


<body class="relative flex flex-col items-center justify-center overflow-x-hidden text-slate-800 p-4">

    <!-- ========================================= -->
    <!-- Background Decoration -->
    <!-- ========================================= -->

    <div
        class="absolute top-[-10%] -left-10 w-96 h-96
               bg-purple-300 rounded-full
               mix-blend-multiply filter blur-3xl
               opacity-60 animate-blob z-0">
    </div>

    <div
        class="absolute top-[20%] -right-10 w-96 h-96
               bg-indigo-300 rounded-full
               mix-blend-multiply filter blur-3xl
               opacity-60 animate-blob
               z-0">
    </div>

    <div
        class="absolute -bottom-10 left-[20%] w-96 h-96
               bg-pink-200 rounded-full
               mix-blend-multiply filter blur-3xl
               opacity-60 animate-blob
               z-0">
    </div>


    <!-- ========================================= -->
    <!-- Main -->
    <!-- ========================================= -->

    <main
        class="container mx-auto z-10
               flex flex-col items-center justify-center
               min-h-[90vh] py-6">

        <!-- ========================================= -->
        <!-- Main Card -->
        <!-- ========================================= -->

        <div
            data-aos="zoom-in"
            data-aos-duration="1000"
            class="glass-panel rounded-[2rem]
                   p-6 md:p-10
                   max-w-3xl w-full
                   relative flex flex-col">

            <!-- ========================================= -->
            <!-- Header / Logo -->
            <!-- ========================================= -->

            <div
                class="flex flex-col items-center w-full
                       mb-5 pb-5
                       border-b border-white/60">

                <img
                    src="assets/images/logo.png"
                    alt="RMUTP AWARDS Logo"
                    class="w-48 sm:w-56 md:w-60
                           h-auto object-contain
                           drop-shadow-sm
                           mb-3
                           transform hover:scale-105
                           transition duration-300">

            </div>


            <!-- ========================================= -->
            <!-- Main Content -->
            <!-- ========================================= -->

            <div class="text-center">

                <!-- Badge -->

                <div
                    class="inline-flex items-center gap-2
                           px-4 py-1.5
                           rounded-full
                           bg-brand-50
                           text-brand-600
                           font-semibold
                           text-sm
                           mb-5
                           border border-brand-100
                           shadow-sm">

                    <span
                        class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute
                                   inline-flex h-full w-full
                                   rounded-full
                                   bg-brand-400 opacity-75">
                        </span>

                        <span
                            class="relative inline-flex
                                   rounded-full
                                   h-2.5 w-2.5
                                   bg-brand-600">
                        </span>
                    </span>

                    BEST SELLER CAMPAIGN

                </div>


                <!-- Title -->

                <h1
                    class="text-4xl md:text-5xl
                           font-extrabold
                           text-slate-800
                           mb-5
                           tracking-tight
                           leading-tight">

                    Best Seller

                    <span
                        class="text-transparent
                               bg-clip-text
                               bg-gradient-to-r
                               from-brand-600
                               to-indigo-500">
                        Portal
                    </span>

                </h1>


                <!-- Description -->

                <p
                    class="text-base md:text-lg
                           text-slate-500
                           mb-7
                           font-light
                           max-w-xl
                           mx-auto
                           leading-relaxed">
                    ระบบจัดการและยืนยันยอดจำหน่ายสินค้า
                    สำหรับผู้เข้าประกวด RMUTP AWARDS
                    สะดวก รวดเร็ว และตรวจสอบได้แบบเรียลไทม์
                </p>


                <!-- ========================================= -->
                <!-- Countdown -->
                <!-- ========================================= -->

                <div
                    id="countdown-box"
                    class="mb-8">

                    <div
                        class="glass-panel
                               rounded-3xl
                               p-5
                               border border-brand-100
                               bg-white/50">

                        <!-- Countdown Heading -->

                        <div
                            class="flex items-center
                                   justify-center gap-2
                                   text-sm
                                   text-brand-600
                                   font-semibold
                                   mb-4">

                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            ระบบจะปิดรับการส่งยอดจำหน่ายใน

                        </div>


                        <!-- Countdown Grid -->

                        <div
                            class="grid grid-cols-4
                                   gap-2 sm:gap-3">

                            <!-- Days -->

                            <div class="countdown-item rounded-2xl p-3">

                                <div
                                    id="days"
                                    class="countdown-number
                                           text-2xl md:text-3xl
                                           font-bold
                                           text-brand-700">
                                    00
                                </div>

                                <div
                                    class="countdown-label
                                           text-xs
                                           text-slate-500
                                           mt-1">
                                    วัน
                                </div>

                            </div>


                            <!-- Hours -->

                            <div class="countdown-item rounded-2xl p-3">

                                <div
                                    id="hours"
                                    class="countdown-number
                                           text-2xl md:text-3xl
                                           font-bold
                                           text-brand-700">
                                    00
                                </div>

                                <div
                                    class="countdown-label
                                           text-xs
                                           text-slate-500
                                           mt-1">
                                    ชั่วโมง
                                </div>

                            </div>


                            <!-- Minutes -->

                            <div class="countdown-item rounded-2xl p-3">

                                <div
                                    id="minutes"
                                    class="countdown-number
                                           text-2xl md:text-3xl
                                           font-bold
                                           text-brand-700">
                                    00
                                </div>

                                <div
                                    class="countdown-label
                                           text-xs
                                           text-slate-500
                                           mt-1">
                                    นาที
                                </div>

                            </div>


                            <!-- Seconds -->

                            <div class="countdown-item rounded-2xl p-3">

                                <div
                                    id="seconds"
                                    class="countdown-number
                                           text-2xl md:text-3xl
                                           font-bold
                                           text-brand-700">
                                    00
                                </div>

                                <div
                                    class="countdown-label
                                           text-xs
                                           text-slate-500
                                           mt-1">
                                    วินาที
                                </div>

                            </div>

                        </div>


                        <!-- Closing Date -->

                        <div
                            class="mt-4
                                   text-xs sm:text-sm
                                   text-slate-500">

                            ปิดระบบวันที่

                            <span
                                class="font-semibold
                                       text-slate-700">
                                18 กันยายน 2569
                                เวลา 12:00 น.
                            </span>

                        </div>

                    </div>

                </div>


                <!-- ========================================= -->
                <!-- Portal Buttons -->
                <!-- ========================================= -->

                <div
                    id="portal-buttons"
                    class="flex flex-col
                           sm:flex-row
                           gap-4
                           justify-center
                           items-center
                           mt-2">

                    <!-- Contestant Login -->

                    <a
                        href="contestant/login.php"
                        class="portal-button
                               w-full sm:w-auto
                               px-8 py-3.5
                               bg-gradient-to-r
                               from-brand-600
                               to-indigo-600
                               text-white
                               rounded-2xl
                               font-semibold
                               shadow-lg
                               shadow-brand-500/30
                               hover:shadow-brand-500/50
                               transform
                               hover:-translate-y-1
                               active:scale-95
                               flex items-center
                               justify-center
                               group">

                        เข้าสู่ระบบผู้เข้าประกวด

                        <svg
                            class="w-5 h-5 ml-2
                                   group-hover:translate-x-1
                                   transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>

                    </a>


                    <!-- Rules -->

                    <a
                        href="rules.php"
                        class="portal-button
                               w-full sm:w-auto
                               px-8 py-3.5
                               flex items-center
                               justify-center
                               gap-2
                               bg-white/60
                               hover:bg-white
                               text-slate-600
                               hover:text-brand-600
                               rounded-2xl
                               font-semibold
                               shadow-sm
                               border border-white
                               hover:border-brand-200
                               transform
                               hover:-translate-y-1
                               active:scale-95">

                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>

                        ข้อปฏิบัติและกฎเกณฑ์

                    </a>

                </div>


                <!-- ========================================= -->
                <!-- Closed System -->
                <!-- ========================================= -->

                <div
                    id="closed-box"
                    class="hidden mt-6">

                    <div
                        class="bg-red-50
                               border border-red-200
                               text-red-600
                               rounded-2xl
                               p-5
                               text-center">

                        <div
                            class="flex justify-center mb-2">

                            <div
                                class="w-12 h-12
                                       rounded-full
                                       bg-red-100
                                       flex items-center
                                       justify-center">

                                <svg
                                    class="w-6 h-6"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m0-8v4m9 3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                            </div>

                        </div>

                        <div
                            class="font-bold
                                   text-lg">
                            ระบบปิดการส่งยอดจำหน่ายแล้ว
                        </div>

                        <div
                            class="text-sm
                                   mt-1
                                   text-red-500">
                            หมดระยะเวลาการส่งยอดจำหน่าย
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ========================================= -->
        <!-- Copyright -->
        <!-- ========================================= -->

        <p
            class="mt-8
                   text-sm
                   text-slate-400
                   font-light
                   z-10
                   text-center">
            © 2026 RMUTP Student Organization.
            All rights reserved.
        </p>

    </main>


    <!-- ========================================= -->
    <!-- Admin Button -->
    <!-- ========================================= -->

    <a
        href="admin/index.php"
        class="fixed
               bottom-6 right-6
               w-10 h-10
               opacity-20
               hover:opacity-100
               transition-opacity
               duration-300
               bg-white/50
               backdrop-blur-md
               border border-white/50
               rounded-full
               flex items-center
               justify-center
               cursor-pointer
               shadow-sm
               text-slate-400
               hover:text-brand-600
               z-50"
        title="Admin Login">

        <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24">

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31.826-2.37 2.37a1.724 1.724 0 001.065 2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066 2.573c-.94 1.543.826 3.31 2.37 2.37.996-.608 2.296-.07 2.572-1.065z" />

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

        </svg>

    </a>


    <!-- ========================================= -->
    <!-- AOS -->
    <!-- ========================================= -->

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        AOS.init({
            once: true
        });
    </script>


    <!-- ========================================= -->
    <!-- Countdown System -->
    <!-- ========================================= -->

    <script>
        /*
         * =====================================================
         * RMUTP AWARDS 2026
         * Best Seller Portal
         *
         * System Closing:
         * 18 September 2026
         * 12:00 PM (GMT+7)
         * =====================================================
         */

        const closeDate =
            new Date("2026-09-18T12:00:00+07:00").getTime();


        const daysElement =
            document.getElementById("days");

        const hoursElement =
            document.getElementById("hours");

        const minutesElement =
            document.getElementById("minutes");

        const secondsElement =
            document.getElementById("seconds");

        const portalButtons =
            document.getElementById("portal-buttons");

        const closedBox =
            document.getElementById("closed-box");

        const countdownBox =
            document.getElementById("countdown-box");


        function updateCountdown() {

            const now =
                new Date().getTime();

            const distance =
                closeDate - now;


            /*
             * ==========================================
             * System Closed
             * ==========================================
             */

            if (distance <= 0) {

                daysElement.textContent = "00";
                hoursElement.textContent = "00";
                minutesElement.textContent = "00";
                secondsElement.textContent = "00";


                /*
                 * Hide login buttons
                 */

                portalButtons.classList.add("hidden");


                /*
                 * Show closed message
                 */

                closedBox.classList.remove("hidden");


                /*
                 * Stop countdown
                 */

                clearInterval(countdownTimer);

                return;
            }


            /*
             * ==========================================
             * Calculate Time
             * ==========================================
             */

            const days =
                Math.floor(
                    distance /
                    (1000 * 60 * 60 * 24)
                );


            const hours =
                Math.floor(
                    (distance %
                        (1000 * 60 * 60 * 24)
                    ) /
                    (1000 * 60 * 60)
                );


            const minutes =
                Math.floor(
                    (distance %
                        (1000 * 60 * 60)
                    ) /
                    (1000 * 60)
                );


            const seconds =
                Math.floor(
                    (distance %
                        (1000 * 60)
                    ) /
                    1000
                );


            /*
             * ==========================================
             * Update UI
             * ==========================================
             */

            daysElement.textContent =
                String(days).padStart(2, "0");

            hoursElement.textContent =
                String(hours).padStart(2, "0");

            minutesElement.textContent =
                String(minutes).padStart(2, "0");

            secondsElement.textContent =
                String(seconds).padStart(2, "0");

        }


        /*
         * Initial Update
         */

        updateCountdown();


        /*
         * Update every 1 second
         */

        const countdownTimer =
            setInterval(updateCountdown, 1000);
    </script>

</body>

</html>