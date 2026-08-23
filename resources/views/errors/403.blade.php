<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | FIXPRO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { text-align: center; max-width: 420px; padding: 40px; }
        .code { font-size: 6rem; font-weight: 800; color: #dc2626; line-height: 1; }
        h1 { font-size: 1.3rem; margin: 16px 0 8px; color: #1e293b; }
        p { font-size: .9rem; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        a { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #0d9488; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: .85rem; transition: background .2s; }
        a:hover { background: #0f766e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">403</div>
        <h1>Akses Ditolak</h1>
        <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika ini adalah kesalahan.</p>
        <a href="{{ url('/dashboard') }}"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</body>
</html>
