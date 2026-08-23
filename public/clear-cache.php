<?php
/**
 * CLEAR CACHE - Upload ke public/ lalu akses: https://fixpro.id/clear-cache.php
 * SETELAH SELESAI, HAPUS FILE INI dari server!
 */

echo "<h2>🔄 Clearing All Cache...</h2>\n";
flush();

// 1. Hapus file cache Laravel
$cachePaths = [
    __DIR__.'/../bootstrap/cache/.route-cache.php',
    __DIR__.'/../bootstrap/cache/cache-v2.php',
    __DIR__.'/../bootstrap/cache/config-v2.php',
    __DIR__.'/../bootstrap/cache/services-v2.php',
    __DIR__.'/../bootstrap/cache/packages-v2.php',
    __DIR__.'/../bootstrap/cache/routes-v7.php',
    __DIR__.'/../bootstrap/cache/events-v7.php',
    __DIR__.'/../storage/framework/cache/',
    __DIR__.'/../storage/framework/views/',
    __DIR__.'/../storage/framework/sessions/',
];

foreach ($cachePaths as $path) {
    if (is_file($path)) {
        if (unlink($path)) {
            echo "✅ Deleted: " . basename($path) . "\n";
        } else {
            echo "⚠️ Failed: " . basename($path) . "\n";
        }
    } elseif (is_dir($path)) {
        $count = 0;
        foreach (glob($path . '*') as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }
        echo "✅ Cleaned: " . basename($path) . " ($count files)\n";
    } else {
        echo "⏭️  Skip: " . basename($path) . " (not found)\n";
    }
    flush();
}

// 2. Hapus semua file di bootstrap/cache
$bootstrapCache = __DIR__.'/../bootstrap/cache/';
if (is_dir($bootstrapCache)) {
    foreach (glob($bootstrapCache . '*') as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            unlink($file);
            echo "✅ Deleted: bootstrap/cache/" . basename($file) . "\n";
        }
    }
    flush();
}

// 3. Coba artisan (kalau available)
echo "\n<h3>Artisan Commands:</h3>\n";
$commands = ['route:clear', 'cache:clear', 'config:clear', 'view:clear', 'compiled:clear'];
foreach ($commands as $cmd) {
    $output = shell_exec('cd ' . escapeshellarg(__DIR__.'/..') . ' && php artisan ' . $cmd . ' 2>&1');
    echo "<pre>$output</pre>\n";
    flush();
}

echo "<hr>\n";
echo "<h2>✅ Done! Semua cache sudah di-clear.</h2>\n";
echo "<p><strong>SEKARANG HAPUS file ini dari server!</strong></p>\n";
echo "<p>File: <code>public/clear-cache.php</code></p>\n";
