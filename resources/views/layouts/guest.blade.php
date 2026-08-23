<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Login</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></noscript>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: linear-gradient(160deg, #0a1628 0%, #0d2f2f 50%, #0a1a1a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { width: 100%; max-width: 440px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,.4); animation: fadeIn .5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .login-header { background: linear-gradient(135deg, #0d9488, #065f46); padding: 30px; text-align: center; color: #fff; }
        .login-header .logo { width: 80px; height: 80px; border-radius: 50%; overflow: hidden; margin: 0 auto 14px; border: 3px solid rgba(255,255,255,.4); box-shadow: 0 4px 15px rgba(0,0,0,.2); }
        .login-header .logo img { width: 100%; height: 100%; object-fit: cover; }
        .login-header h1 { font-size: 1.5rem; letter-spacing: 1px; }
        .login-header h1 span { opacity: .7; font-weight: 400; font-size: .85rem; display: block; margin-top: 2px; }
        .login-body { padding: 28px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: .8rem; font-weight: 600; color: #374151; }
        .form-input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: .85rem; outline: none; transition: border-color .2s, box-shadow .2s; }
        .form-input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .text-muted { color: #64748b; }
        .alert { padding: 10px 14px; border-radius: 8px; font-size: .82rem; margin-bottom: 12px; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        code { background: #dbeafe; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: .72rem; }
        @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo"><img src="{{ asset('logo-fixpro.jpg') }}" alt="FIXPRO"></div>
            <h1>FIX<span>PRO</span></h1>
        </div>
        <div class="login-body">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
