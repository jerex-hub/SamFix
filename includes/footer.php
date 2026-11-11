</main> <!-- Closes the <main> tag opened in header.php -->

    <!-- Footer Section -->
    <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-colors duration-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                
                <!-- Brand Info -->
                <div class="md:col-span-2 space-y-4">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-sm">
                            <i class="fa-solid fa-wrench text-xs"></i>
                        </div>
                        <span class="font-bold text-lg tracking-tight text-slate-900 dark:text-white"><?= e(APP_NAME) ?><span class="text-blue-600">.</span></span>
                    </a>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        Professional and reliable hardware diagnostics, device repair tracking, and tech support services.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="<?= BASE_URL ?>/track.php" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Track repair</a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/index.php#services" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Our Services</a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/request.php" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Book a Repair</a>
                        </li>
                    </ul>
                </div>

                <!-- Support & Admin -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="<?= BASE_URL ?>/index.php#visit" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Visit Workshop</a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/admin/login.php" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Admin Portal</a>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Copyright Bar -->
            <div class="border-t border-slate-100 dark:border-slate-800/60 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400 dark:text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <span>Reliable Device Diagnostics</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>