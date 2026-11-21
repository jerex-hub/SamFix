</main>

    <!-- Footer: three steps + bottom bar -->
    <footer class="mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">

            <!-- Divider + steps -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-12 sm:pt-16 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Simple from start to finish</p>
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-10 sm:mb-12">Back to connected, in three steps.</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-8">
                    <?php foreach ([
                        [1, 'smartphone',   'Drop it off',        'Bring in your device. We’ll take care of the diagnosis and give you a unique ticket.'],
                        [2, 'wrench',       'Stay in the loop',   'Check your repair status and estimated cost online, anytime you need an update.'],
                        [3, 'check-circle', 'Pick up & power on', 'Once your device is ready, stop by your service center and get back to your day.'],
                    ] as [$n, $ic, $title, $text]): ?>
                    <div class="flex flex-col items-center">
                        <div class="relative mb-5">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/70 text-slate-600 dark:text-slate-300">
                                <?= icon($ic, 'w-6 h-6') ?>
                            </span>
                            <span class="absolute -top-1.5 -right-1.5 grid h-5 w-5 place-items-center rounded-full bg-blue-600 text-white text-[10px] font-semibold ring-2 ring-[#F8FAFC] dark:ring-slate-950"><?= $n ?></span>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-2"><?= e($title) ?></h3>
                        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400 max-w-xs"><?= e($text) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-12 sm:mt-16 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-400 dark:text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built for better repairs.</p>
                <div class="flex items-center gap-4">
                    <a href="/admin/login.php" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Admin</a>
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Your device is in good hands</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>