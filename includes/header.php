<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - SamFix' : 'SamFix - Customer Portal' ?></title>
    
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles embedded for Play CDN (No npm build needed) -->
    <style type="text/tailwindcss">
        @layer components {
            .card {
                @apply rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl transition-colors text-slate-900 dark:text-slate-100;
            }
            .icon-box {
                @apply grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-300;
            }
            .pill {
                @apply inline-flex items-center gap-2 rounded-full border border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-500/10 px-4 py-1.5 text-xs font-semibold tracking-widest text-blue-600 dark:text-blue-300;
            }
            .eyebrow {
                @apply text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500;
            }
            .field-label {
                @apply mb-2 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300;
            }
            .field {
                @apply w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors;
            }
            .btn {
                @apply inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500;
            }
            .btn-primary {
                @apply bg-blue-600 dark:bg-blue-500 text-white hover:bg-blue-500 dark:hover:bg-blue-400 shadow-lg shadow-blue-600/20;
            }
            .btn-outline {
                @apply border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800;
            }
            .link-row {
                @apply flex items-center justify-between rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-800;
            }
            .info-title {
                @apply mb-1 block text-xs font-semibold text-slate-400 dark:text-slate-500;
            }
        }
    </style>
</head>
<body class="bg-[#F8FAFC] dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between transition-colors duration-200">

<!-- Top Navigation Bar -->
<nav class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-50 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center gap-4">
            
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="index.php" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
                        <i class="fa-solid fa-wrench text-sm"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">SamFix<span class="text-blue-600">.</span></span>
                </a>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden md:flex items-center gap-1 text-sm font-medium">
                <a href="track.php" class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Track repair</a>
                <a href="index.php#services" class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Services</a>
                <a href="#booking" class="px-3.5 py-2 rounded-xl text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition font-semibold">Book a repair</a>
                <a href="index.php#visit" class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">Visit us</a>
            </div>

            <!-- Right Actions (Dark Mode Toggle & Help & Support) -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Dark Mode Toggle Button -->
                <button id="darkModeToggle" type="button" class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition shadow-sm" title="Toggle Dark Mode">
                    <i id="darkModeIcon" class="fa-regular fa-sun text-sm"></i>
                </button>

                <!-- Help & Support Button -->
                <button id="helpSupportBtn" type="button" class="hidden sm:flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition px-3 py-2 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-800">
                    <i class="fa-regular fa-circle-question text-slate-400 dark:text-slate-500"></i>
                    <span>Help & support</span>
                </button>
            </div>

        </div>
    </div>
</nav>

<!-- Dark Mode Initialization Script -->
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const htmlElement = document.documentElement;

    if (!localStorage.getItem('theme')) {
        localStorage.setItem('theme', 'dark');
    }

    if (localStorage.getItem('theme') === 'dark') {
        htmlElement.classList.add('dark');
        darkModeIcon.classList.remove('fa-moon');
        darkModeIcon.classList.add('fa-sun');
    } else {
        htmlElement.classList.remove('dark');
        darkModeIcon.classList.remove('fa-sun');
        darkModeIcon.classList.add('fa-moon');
    }

    darkModeToggle.addEventListener('click', () => {
        if (htmlElement.classList.contains('dark')) {
            htmlElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            darkModeIcon.classList.remove('fa-sun');
            darkModeIcon.classList.add('fa-moon');
        } else {
            htmlElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            darkModeIcon.classList.remove('fa-moon');
            darkModeIcon.classList.add('fa-sun');
        }
    });
</script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full flex-1">