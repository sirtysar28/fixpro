<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Login</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;color:#1e293b}

        /* Main card */
        .login-wrap{width:100%;max-width:920px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.1);display:flex;min-height:580px;animation:fadeUp .35s ease}
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

        /* Brand left */
        .login-brand{flex:0 0 340px;background:linear-gradient(160deg,#0d9488 0%,#065f46 100%);padding:40px 32px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#fff;position:relative;overflow:hidden}
        .login-brand::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%}
        .login-brand::after{content:'';position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;background:rgba(255,255,255,.04);border-radius:50%}
        .login-logo-wrap{width:80px;height:80px;border-radius:18px;overflow:hidden;border:3px solid rgba(255,255,255,.3);box-shadow:0 8px 24px rgba(0,0,0,.2);margin-bottom:18px}
        .login-logo-wrap img{width:100%;height:100%;object-fit:cover;display:block}
        .login-brand h2{font-size:1.6rem;font-weight:800;letter-spacing:.5px;margin-bottom:2px;position:relative}
        .login-brand h2 span{color:#86efac}
        .login-brand p{font-size:.78rem;opacity:.85;line-height:1.5;margin-top:8px;position:relative}
        .db-status{display:inline-flex;align-items:center;gap:5px;font-size:.68rem;font-weight:600;padding:5px 12px;border-radius:20px;margin-top:18px;position:relative}
        .db-ok{background:rgba(255,255,255,.15);color:#86efac}
        .db-err{background:rgba(239,68,68,.2);color:#fca5a5}

        /* Form right */
        .login-form-area{flex:1;padding:36px 40px;display:flex;flex-direction:column;overflow-y:auto}

        /* Welcome */
        .welcome-text h2{font-size:1.2rem;font-weight:800;color:#0f172a}
        .welcome-text .sub{font-size:.8rem;color:#94a3b8;margin-top:3px}

        /* Alert */
        .alert-box{padding:10px 14px;border-radius:10px;font-size:.8rem;display:none;align-items:flex-start;gap:8px;margin-top:16px;line-height:1.4}
        .alert-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
        .alert-ok{background:#f0fdf4;border: 1px solid #bbf7d0;color:#166534}
        .alert-box.show{display:flex}

        /* Tabs */
        .auth-tabs{display:flex;gap:0;margin-top:20px;border-bottom:2px solid #f1f5f9}
        .auth-tab{flex:1;text-align:center;padding:10px;font-size:.84rem;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
        .auth-tab:hover{color:#64748b}
        .auth-tab.on{color:#0d9488;border-bottom-color:#0d9488}
        .auth-tab i{margin-right:4px}

        /* Panels */
        .auth-panel{display:none;margin-top:20px}
        .auth-panel.on{display:block}

        /* Form elements */
        .fg{margin-bottom:16px}
        .fg label{display:block;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:6px}
        .fci{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.84rem;outline:none;background:#f8fafc;color:#1e293b;transition:border-color .2s,box-shadow .2s}
        .fci:focus{border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1);background:#fff}
        .fci::placeholder{color:#b0b8c4}
        .fg-pass{position:relative}
        .fg-pass .fci{padding-right:42px}
        .pass-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.88rem;padding:4px 6px}
        .pass-toggle:hover{color:#64748b}
        .fr{display:grid;grid-template-columns:1fr 1fr;gap:12px}

        /* Buttons */
        .b{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 20px;border:none;border-radius:10px;font-family:inherit;font-size:.86rem;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none}
        .b:hover{opacity:.9}
        .bp{background:#0d9488;color:#fff}
        .bp:hover{background:#0f766e}
        .b-full{width:100%}

        /* Google button */
        .google-login-wrap{margin-top:16px}
        .google-divider{text-align:center;font-size:.74rem;color:#cbd5e1;margin-bottom:12px;position:relative}
        .google-divider::before,.google-divider::after{content:'';position:absolute;top:50%;width:calc(50% - 40px);height:1px;background:#e2e8f0}
        .google-divider::before{left:0}
        .google-divider::after{right:0}
        .btn-google{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;font-family:inherit;font-size:.84rem;font-weight:600;color:#374151;cursor:pointer;transition:all .2s}
        .btn-google:hover{background:#f8fafc;border-color:#cbd5e1}
        .btn-google svg{width:18px;height:18px}

        /* Trial badge */
        .trial-badge{background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fbbf24;padding:10px 14px;border-radius:10px;font-size:.74rem;color:#92400e;font-weight:600;text-align:center}
        .trial-badge i{margin-right:4px}

        /* WhatsApp request button */
        .b-wa{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px 14px;border:none;border-radius:10px;background:#25D366;color:#fff;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none}
        .b-wa:hover{background:#1ebe5d;color:#fff}

        /* Collapsible activation code section */
        .act-code-wrap{margin-bottom:16px;border:1px dashed #e2e8f0;border-radius:10px;overflow:hidden;transition:border-color .2s}
        .act-code-wrap.open{border-color:#0d9488;border-style:solid}
        .act-code-toggle{width:100%;display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f8fafc;border:none;cursor:pointer;font-family:inherit;font-size:.78rem;font-weight:600;color:#64748b;text-align:left;transition:all .2s}
        .act-code-toggle:hover{background:#f1f5f9;color:#475569}
        .act-code-toggle > i:first-child{color:#f59e0b}
        .act-code-arrow{margin-left:auto;transition:transform .2s}
        .act-code-wrap.open .act-code-arrow{transform:rotate(180deg)}
        .act-code-body{display:none;padding:12px 14px;border-top:1px solid #f1f5f9}
        .act-code-wrap.open .act-code-body{display:block}
        .act-code-body .fci{margin-bottom:2px}

        /* Error messages */
        .field-err{font-size:.72rem;color:#dc2626;margin-top:4px}

        /* Responsive */
        @media(max-width:768px){
            .login-wrap{flex-direction:column;max-width:440px}
            .login-brand{flex:none;padding:28px 24px}
            .login-form-area{padding:24px}
            .fr{grid-template-columns:1fr}
        }
        @media(max-width:400px){
            .login-brand{flex:none;padding:24px 20px}
            .login-form-area{padding:20px}
        }
    </style>
</head>
<body>
    <div class="login-wrap" id="loginWrap">
        {{ $slot }}
    </div>

    <script>
        function togglePass(id, btn) {
            var inp = document.getElementById(id);
            var ico = btn.querySelector('i');
            if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
            else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
        }
    </script>
</body>
</html>
