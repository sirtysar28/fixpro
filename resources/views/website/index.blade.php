<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FixPro - Sistem Manajemen Servis HP Profesional</title>
    <meta name="description" content="FixPro adalah platform manajemen servis HP profesional. Kelola bisnis servis HP dengan sistem modern, cepat, dan terpercaya.">
    <meta name="keywords" content="service HP, servis handphone, manajemen servis, bengkel HP, toko service, FixPro">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;color:#1e293b;background:#fff;overflow-x:hidden}
        html{scroll-behavior:smooth}
        a{text-decoration:none;color:inherit}
        img{max-width:100%}
        .container{max-width:1200px;margin:0 auto;padding:0 20px}

        /* ========== NAVBAR ========== */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:1000;padding:16px 0;transition:all .3s}
        .navbar.scrolled{background:rgba(255,255,255,.97);box-shadow:0 2px 20px rgba(0,0,0,.08);padding:10px 0}
        .navbar .container{display:flex;align-items:center;justify-content:space-between}
        .nav-brand{display:flex;align-items:center;gap:12px;font-weight:800;font-size:1.3rem;color:#fff;transition:color .3s}
        .navbar.scrolled .nav-brand{color:#0f172a}
        .nav-brand img{width:42px;height:42px;border-radius:10px;object-fit:cover}
        .nav-brand span{color:#2dd4bf}
        .nav-links{display:flex;align-items:center;gap:28px;list-style:none}
        .nav-links a{font-size:.88rem;font-weight:500;color:rgba(255,255,255,.85);transition:color .3s;position:relative}
        .navbar.scrolled .nav-links a{color:#475569}
        .nav-links a:hover{color:#2dd4bf}
        .nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;width:0;height:2px;background:#2dd4bf;transition:width .3s}
        .nav-links a:hover::after{width:100%}
        .nav-cta{display:flex;align-items:center;gap:12px}
        .btn-login{padding:8px 20px;border:1.5px solid rgba(255,255,255,.4);border-radius:8px;color:#fff;font-weight:600;font-size:.84rem;transition:all .3s;background:transparent;cursor:pointer}
        .navbar.scrolled .btn-login{border-color:#0d9488;color:#0d9488}
        .btn-login:hover{background:#0d9488;color:#fff;border-color:#0d9488}
        .btn-register{padding:8px 20px;border:none;border-radius:8px;background:#0d9488;color:#fff;font-weight:700;font-size:.84rem;transition:all .3s;cursor:pointer;box-shadow:0 4px 15px rgba(13,148,136,.3)}
        .btn-register:hover{background:#0f766e;transform:translateY(-1px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .hamburger{display:none;background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer}
        .navbar.scrolled .hamburger{color:#1e293b}

        /* ========== HERO ========== */
        .hero{position:relative;min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,#0a1628 0%,#0d2f2f 40%,#064e3b 100%);overflow:hidden}
        .hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
        .hero .container{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;padding-top:80px;padding-bottom:60px}
        .hero-content{color:#fff}
        .hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:30px;background:rgba(13,148,136,.2);border:1px solid rgba(13,148,136,.3);font-size:.78rem;font-weight:600;color:#2dd4bf;margin-bottom:20px}
        .hero-badge i{font-size:.6rem}
        .hero-title{font-size:3.2rem;font-weight:900;line-height:1.15;margin-bottom:20px;letter-spacing:-1px}
        .hero-title span{color:#2dd4bf;position:relative}
        .hero-subtitle{font-size:1.1rem;line-height:1.7;color:rgba(255,255,255,.7);margin-bottom:32px;max-width:520px}
        .hero-buttons{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:48px}
        .btn-hero-primary{padding:14px 32px;border:none;border-radius:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:700;font-size:1rem;cursor:pointer;transition:all .3s;box-shadow:0 4px 20px rgba(13,148,136,.4);display:inline-flex;align-items:center;gap:8px}
        .btn-hero-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(13,148,136,.5)}
        .btn-hero-secondary{padding:14px 32px;border:1.5px solid rgba(255,255,255,.3);border-radius:12px;background:transparent;color:#fff;font-weight:600;font-size:1rem;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:8px}
        .btn-hero-secondary:hover{border-color:#2dd4bf;color:#2dd4bf;background:rgba(13,148,136,.1)}
        .hero-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding-top:24px;border-top:1px solid rgba(255,255,255,.1)}
        .hero-stat{text-align:center}
        .hero-stat .number{font-size:1.8rem;font-weight:900;color:#2dd4bf}
        .hero-stat .label{font-size:.75rem;color:rgba(255,255,255,.6);margin-top:2px}
        .hero-visual{position:relative;display:flex;align-items:center;justify-content:center}
        .hero-phone-mockup{position:relative;width:300px;height:580px;background:linear-gradient(145deg,#1e293b,#334155);border-radius:40px;border:3px solid #475569;box-shadow:0 30px 80px rgba(0,0,0,.4);overflow:hidden;padding:12px}
        .hero-phone-screen{width:100%;height:100%;background:linear-gradient(180deg,#0f172a,#1e293b);border-radius:30px;overflow:hidden;position:relative}
        .phone-header{padding:16px 20px;background:linear-gradient(135deg,#0d9488,#065f46);display:flex;align-items:center;gap:10px}
        .phone-header-logo{width:28px;height:28px;border-radius:6px;overflow:hidden}
        .phone-header-logo img{width:100%;height:100%;object-fit:cover}
        .phone-header-text{font-weight:700;color:#fff;font-size:.85rem}
        .phone-body{padding:16px}
        .phone-stat-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
        .phone-stat{padding:12px;border-radius:10px;background:rgba(255,255,255,.05);text-align:center}
        .phone-stat .num{font-size:1.1rem;font-weight:800;color:#2dd4bf}
        .phone-stat .lbl{font-size:.6rem;color:#94a3b8;margin-top:2px}
        .phone-list-item{display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,.05);border-radius:8px;margin-bottom:8px}
        .phone-list-icon{width:32px;height:32px;border-radius:8px;background:rgba(13,148,136,.2);display:flex;align-items:center;justify-content:center;color:#2dd4bf;font-size:.75rem}
        .phone-list-text{flex:1}
        .phone-list-text .title{font-size:.72rem;font-weight:600;color:#e2e8f0}
        .phone-list-text .sub{font-size:.6rem;color:#64748b}
        .phone-list-badge{padding:2px 8px;border-radius:10px;font-size:.55rem;font-weight:700}
        .phone-list-badge.green{background:#052e16;color:#4ade80}
        .phone-list-badge.yellow{background:#451a03;color:#fbbf24}
        .phone-float-card{position:absolute;padding:14px 18px;border-radius:14px;background:#fff;box-shadow:0 10px 40px rgba(0,0,0,.15);display:flex;align-items:center;gap:12px}
        .phone-float-card.card-1{top:60px;right:-40px;animation:floatCard 3s ease-in-out infinite}
        .phone-float-card.card-2{bottom:100px;left:-30px;animation:floatCard 3s ease-in-out infinite 1.5s}
        @keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        .float-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem}
        .float-icon.green{background:#dcfce7;color:#16a34a}
        .float-icon.blue{background:#dbeafe;color:#2563eb}
        .float-text .num{font-weight:800;font-size:.9rem;color:#0f172a}
        .float-text .lbl{font-size:.65rem;color:#94a3b8}

        /* Hero Slider (banners) */
        .hero-slider{position:absolute;top:0;left:0;right:0;bottom:0;z-index:1;opacity:.15}
        .hero-slider img{width:100%;height:100%;object-fit:cover}

        /* ========== SECTION COMMON ========== */
        .section{padding:100px 0}
        .section-header{text-align:center;margin-bottom:60px}
        .section-header h2{font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-.5px}
        .section-header p{font-size:1.05rem;color:#64748b;max-width:600px;margin:0 auto;line-height:1.7}
        .section-dark{background:#0f172a;color:#e2e8f0}
        .section-dark .section-header p{color:#94a3b8}
        .section-gray{background:#f8fafc}
        .section-gradient{background:linear-gradient(135deg,#0d9488 0%,#065f46 100%);color:#fff}
        .section-gradient .section-header h2{color:#fff}
        .section-gradient .section-header p{color:rgba(255,255,255,.8)}

        /* ========== FEATURES ========== */
        .features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
        .feature-card{padding:32px 28px;border-radius:16px;border:1px solid #e2e8f0;background:#fff;transition:all .3s;position:relative;overflow:hidden}
        .feature-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,.08);border-color:#0d9488}
        .feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#0d9488,#14b8a6);transform:scaleX(0);transition:transform .3s;transform-origin:left}
        .feature-card:hover::before{transform:scaleX(1)}
        .feature-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(13,148,136,.1),rgba(20,184,166,.1));display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#0d9488;margin-bottom:20px;transition:all .3s}
        .feature-card:hover .feature-icon{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .feature-card h3{font-size:1.1rem;font-weight:700;margin-bottom:10px}
        .feature-card p{font-size:.88rem;color:#64748b;line-height:1.7}

        /* ========== ABOUT ========== */
        .about-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
        .about-image{position:relative;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.1)}
        .about-image img{width:100%;height:400px;object-fit:cover;display:block}
        .about-image-placeholder{width:100%;height:400px;background:linear-gradient(135deg,#0d9488,#065f46);border-radius:20px;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#fff}
        .about-image-placeholder i{font-size:4rem;margin-bottom:16px;opacity:.5}
        .about-image-placeholder span{font-size:1.1rem;font-weight:600;opacity:.6}
        .about-content h2{font-size:2.2rem;font-weight:800;margin-bottom:16px}
        .about-content h2 span{color:#0d9488}
        .about-content p{color:#64748b;line-height:1.8;margin-bottom:20px;font-size:.95rem}
        .about-checks{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px}
        .about-check{display:flex;align-items:center;gap:10px;font-size:.88rem;font-weight:500}
        .about-check i{color:#0d9488;font-size:.8rem}

        /* ========== SERVICES ========== */
        .services-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
        .service-card{padding:32px 24px;border-radius:16px;background:#fff;text-align:center;transition:all .3s;border:1px solid #e2e8f0}
        .service-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px rgba(0,0,0,.08)}
        .service-icon{width:70px;height:70px;border-radius:20px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;margin:0 auto 20px}
        .service-card h3{font-size:1.05rem;font-weight:700;margin-bottom:10px}
        .service-card p{font-size:.84rem;color:#64748b;line-height:1.7}

        /* ========== PRICING ========== */
        .pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;align-items:start}
        .pricing-card{padding:36px 28px;border-radius:16px;background:#fff;border:2px solid #e2e8f0;text-align:center;transition:all .3s;position:relative}
        .pricing-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,.08)}
        .pricing-card.popular{border-color:#0d9488;transform:scale(1.05)}
        .pricing-card.popular:hover{transform:scale(1.05) translateY(-4px)}
        .pricing-popular-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);padding:4px 16px;border-radius:20px;background:#0d9488;color:#fff;font-size:.72rem;font-weight:700}
        .pricing-card h3{font-size:1.2rem;font-weight:700;margin-bottom:8px}
        .pricing-amount{margin:20px 0}
        .pricing-amount .price{font-size:2.5rem;font-weight:900;color:#0d9488}
        .pricing-amount .period{font-size:.88rem;color:#94a3b8}
        .pricing-features{list-style:none;text-align:left;margin:24px 0}
        .pricing-features li{padding:8px 0;font-size:.88rem;color:#475569;display:flex;align-items:center;gap:10px}
        .pricing-features li i{color:#0d9488;font-size:.75rem}
        .pricing-btn{display:block;width:100%;padding:12px;border-radius:10px;border:2px solid #0d9488;background:transparent;color:#0d9488;font-weight:700;font-size:.9rem;cursor:pointer;transition:all .3s}
        .pricing-btn:hover{background:#0d9488;color:#fff}
        .pricing-card.popular .pricing-btn{background:#0d9488;color:#fff}
        .pricing-card.popular .pricing-btn:hover{background:#0f766e}

        /* ========== TESTIMONIALS ========== */
        .testimonials-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
        .testimonial-card{padding:28px;border-radius:16px;background:#fff;border:1px solid #e2e8f0;transition:all .3s}
        .testimonial-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(0,0,0,.06)}
        .testimonial-stars{display:flex;gap:3px;margin-bottom:14px}
        .testimonial-stars i{color:#f59e0b;font-size:.85rem}
        .testimonial-card blockquote{font-size:.9rem;color:#475569;line-height:1.7;margin-bottom:20px;font-style:italic}
        .testimonial-author{display:flex;align-items:center;gap:12px}
        .testimonial-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem}
        .testimonial-name{font-weight:700;font-size:.88rem}
        .testimonial-role{font-size:.75rem;color:#94a3b8}

        /* ========== FAQ ========== */
        .faq-list{max-width:800px;margin:0 auto}
        .faq-item{border:1px solid #e2e8f0;border-radius:12px;margin-bottom:12px;overflow:hidden;transition:all .3s}
        .faq-item:hover{border-color:#0d9488}
        .faq-question{padding:18px 24px;font-weight:600;font-size:.95rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center;background:#fff;transition:background .3s}
        .faq-question:hover{background:#f8fafc}
        .faq-question i{color:#0d9488;transition:transform .3s}
        .faq-item.active .faq-question i{transform:rotate(180deg)}
        .faq-answer{padding:0 24px;max-height:0;overflow:hidden;transition:all .3s}
        .faq-item.active .faq-answer{padding:0 24px 18px;max-height:300px}
        .faq-answer p{font-size:.88rem;color:#64748b;line-height:1.7}

        /* ========== CTA ========== */
        .cta-section{text-align:center}
        .cta-section h2{font-size:2.5rem;font-weight:800;margin-bottom:16px}
        .cta-section p{font-size:1.1rem;max-width:600px;margin:0 auto 32px;line-height:1.7;color:rgba(255,255,255,.85)}
        .btn-cta{padding:16px 40px;border:none;border-radius:12px;background:#fff;color:#0d9488;font-weight:700;font-size:1.05rem;cursor:pointer;transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.1);display:inline-flex;align-items:center;gap:10px}
        .btn-cta:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.2)}

        /* ========== MOBILE APP ========== */
        .app-section{background:#f8fafc;position:relative;overflow:hidden}
        body.dark .app-section{background:#111827}
        .app-content{display:flex;align-items:center;justify-content:center;gap:56px;max-width:900px;margin:0 auto}
        .app-text{flex:1;max-width:460px}
        .app-text h2{font-size:2.2rem;font-weight:800;margin-bottom:14px;color:#0f172a;letter-spacing:-.5px}
        body.dark .app-text h2{color:#f1f5f9}
        .app-text p{font-size:1rem;color:#475569;line-height:1.7;margin-bottom:24px}
        body.dark .app-text p{color:#94a3b8}
        .app-features{display:flex;flex-direction:column;gap:10px;margin-bottom:28px}
        .app-feature{display:flex;align-items:center;gap:10px;font-size:.88rem;color:#334155;font-weight:500}
        body.dark .app-feature{color:#cbd5e1}
        .app-feature i{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;flex-shrink:0}
        .btn-download-apk{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border:none;border-radius:12px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:700;font-size:1rem;cursor:pointer;transition:all .3s;box-shadow:0 6px 24px rgba(34,197,94,.3);text-decoration:none}
        .btn-download-apk:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(34,197,94,.4)}
        .btn-download-apk i{font-size:1.3rem}
        .btn-download-apk .apk-ver{font-size:.75rem;font-weight:500;opacity:.8;margin-left:4px}
        .app-visual{flex-shrink:0;position:relative}
        .app-phone{width:220px;height:420px;background:linear-gradient(145deg,#1e1b4b,#312e81);border-radius:36px;border:3px solid #4338ca;box-shadow:0 24px 60px rgba(99,102,241,.2);padding:10px;position:relative;overflow:hidden}
        .app-phone-screen{width:100%;height:100%;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);border-radius:28px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:20px}
        .app-phone-screen i.fab.fa-android{font-size:3.5rem;color:#22c55e}
        .app-phone-screen .app-phone-title{font-size:.85rem;font-weight:800;color:#f1f5f9}
        .app-phone-screen .app-phone-sub{font-size:.65rem;color:#64748b;text-align:center;line-height:1.4}
        .app-phone-badge{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);padding:6px 16px;border-radius:20px;background:rgba(34,197,94,.2);border:1px solid rgba(34,197,94,.3);font-size:.65rem;font-weight:600;color:#4ade80;white-space:nowrap}
        @media(max-width:768px){
            .app-content{flex-direction:column;text-align:center}
            .app-visual{display:none}
            .app-text h2{font-size:1.8rem}
            .app-features{align-items:center}
        }

        /* ========== WHATSAPP GROUP ========== */
        .wa-group-section{background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 50%,#f0fdfa 100%);position:relative;overflow:hidden}
        .wa-group-section::before{content:'';position:absolute;top:-80px;right:-80px;width:260px;height:260px;border-radius:50%;background:rgba(37,211,102,.08)}
        .wa-group-section::after{content:'';position:absolute;bottom:-60px;left:-60px;width:200px;height:200px;border-radius:50%;background:rgba(13,148,136,.06)}
        .wa-group-content{display:flex;align-items:center;justify-content:center;gap:48px;position:relative;z-index:2}
        .wa-group-text{text-align:center;max-width:560px}
        .wa-group-text h2{font-size:2.2rem;font-weight:800;margin-bottom:14px;color:#0f172a;letter-spacing:-.5px}
        .wa-group-text h2 i{color:#25D366;margin-right:4px}
        .wa-group-text p{font-size:1.05rem;color:#475569;line-height:1.7;margin-bottom:28px}
        .wa-group-text .wa-benefits{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px}
        .wa-group-text .wa-benefit{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;background:rgba(37,211,102,.1);color:#16a34a;font-size:.8rem;font-weight:600}
        .wa-group-text .wa-benefit i{font-size:.7rem}
        .btn-join-wa{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;border:none;border-radius:14px;background:#25D366;color:#fff;font-weight:700;font-size:1.05rem;cursor:pointer;transition:all .3s;box-shadow:0 6px 24px rgba(37,211,102,.35);text-decoration:none}
        .btn-join-wa:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(37,211,102,.45);background:#1da851}
        .btn-join-wa i{font-size:1.2rem}
        .wa-group-visual{flex-shrink:0;position:relative}
        .wa-group-visual .wa-phone-mockup{width:200px;height:200px;border-radius:24px;background:linear-gradient(145deg,#dcfce7,#d1fae5);display:flex;align-items:center;justify-content:center;box-shadow:0 16px 48px rgba(37,211,102,.15)}
        .wa-group-visual .wa-phone-mockup i{font-size:4.5rem;color:#25D366}
        .wa-group-visual .wa-float{position:absolute;padding:8px 14px;border-radius:10px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.08);display:flex;align-items:center;gap:8px;font-size:.75rem;font-weight:600;color:#0f172a}
        .wa-group-visual .wa-float.top-right{top:-10px;right:-20px;animation:floatCard 3s ease-in-out infinite}
        .wa-group-visual .wa-float.bottom-left{bottom:-10px;left:-20px;animation:floatCard 3s ease-in-out infinite 1.5s}
        .wa-float .wa-dot{width:8px;height:8px;border-radius:50%;background:#25D366;flex-shrink:0}
        @media(max-width:768px){
            .wa-group-content{flex-direction:column;text-align:center}
            .wa-group-visual{display:none}
            .wa-group-text h2{font-size:1.8rem}
            .wa-group-text p{font-size:.95rem}
        }

        /* ========== QR MODAL ========== */
        .qr-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);z-index:10000;align-items:center;justify-content:center;padding:20px}
        .qr-modal-overlay.show{display:flex}
        .qr-modal{background:#fff;border-radius:20px;max-width:440px;width:100%;padding:36px 32px 32px;text-align:center;position:relative;animation:modalIn .3s ease;box-shadow:0 24px 64px rgba(0,0,0,.2)}
        @keyframes modalIn{from{opacity:0;transform:scale(.92) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .qr-modal-close{position:absolute;top:14px;right:14px;width:36px;height:36px;border-radius:50%;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:1rem;transition:all .3s}
        .qr-modal-close:hover{background:#e2e8f0;color:#0f172a}
        .qr-modal-icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#25D366,#128C7E);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:1.5rem;color:#fff}
        .qr-modal h3{font-size:1.25rem;font-weight:800;color:#0f172a;margin-bottom:6px}
        .qr-modal .qr-subtitle{font-size:.88rem;color:#64748b;margin-bottom:20px;line-height:1.5}
        .qr-modal .qr-image-wrapper{background:#f8fafc;border:2px dashed #e2e8f0;border-radius:16px;padding:16px;margin-bottom:20px;display:inline-block}
        .qr-modal .qr-image-wrapper img{width:220px;height:220px;object-fit:contain;border-radius:8px}
        .qr-modal .qr-instruction{display:flex;align-items:flex-start;gap:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;text-align:left;margin-bottom:20px}
        .qr-modal .qr-instruction i{color:#f59e0b;font-size:1rem;margin-top:2px;flex-shrink:0}
        .qr-modal .qr-instruction p{font-size:.82rem;color:#92400e;line-height:1.5}
        .qr-modal .qr-instruction p strong{color:#78350f}
        .qr-modal .qr-or{font-size:.82rem;color:#94a3b8;margin-bottom:12px}
        .qr-modal .qr-link{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border:none;border-radius:12px;background:#25D366;color:#fff;font-weight:700;font-size:.92rem;cursor:pointer;transition:all .3s;box-shadow:0 4px 16px rgba(37,211,102,.3);text-decoration:none}
        .qr-modal .qr-link:hover{background:#1da851;transform:translateY(-1px)}

        /* ========== TRACK ========== */
        .track-box{max-width:500px;margin:0 auto;background:rgba(255,255,255,.1);border-radius:14px;padding:24px;backdrop-filter:blur(10px)}
        .track-form{display:flex;gap:10px}
        @media(max-width:520px){.track-form{flex-direction:column}}
        .track-input{flex:1;padding:12px 16px;border:1.5px solid rgba(255,255,255,.2);border-radius:10px;background:rgba(255,255,255,.1);color:#fff;font-family:inherit;font-size:.88rem;outline:none}
        .track-input::placeholder{color:rgba(255,255,255,.5)}
        .track-input:focus{border-color:#2dd4bf}
        .track-btn{padding:12px 24px;border:none;border-radius:10px;background:#2dd4bf;color:#0f172a;font-weight:700;font-size:.88rem;cursor:pointer;transition:all .3s;white-space:nowrap}
        .track-btn:hover{background:#5eead4}
        .track-result{margin-top:16px;padding:16px;border-radius:10px;background:rgba(255,255,255,.1);display:none}
        .track-result.show{display:block}

        /* ========== CONTACT ========== */
        .contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px}
        .contact-info{display:flex;flex-direction:column;gap:24px}
        .contact-item{display:flex;align-items:flex-start;gap:16px}
        .contact-item-icon{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,rgba(13,148,136,.1),rgba(20,184,166,.1));display:flex;align-items:center;justify-content:center;color:#0d9488;font-size:1.1rem;flex-shrink:0}
        .contact-item h4{font-weight:600;font-size:.92rem;margin-bottom:3px}
        .contact-item p{font-size:.84rem;color:#64748b}
        .contact-socials{display:flex;gap:12px;margin-top:16px}
        .contact-socials a{width:44px;height:44px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:1rem;transition:all .3s}
        .contact-socials a:hover{background:#0d9488;color:#fff}

        /* ========== FOOTER ========== */
        .footer{background:#0f172a;color:#94a3b8;padding:60px 0 24px}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
        .footer-brand h3{color:#fff;font-size:1.2rem;font-weight:800;margin-bottom:8px}
        .footer-brand h3 span{color:#2dd4bf}
        .footer-brand p{font-size:.84rem;line-height:1.7;max-width:300px}
        .footer-col h4{color:#e2e8f0;font-weight:700;font-size:.88rem;margin-bottom:16px}
        .footer-col a{display:block;font-size:.84rem;color:#94a3b8;margin-bottom:10px;transition:color .3s}
        .footer-col a:hover{color:#2dd4bf}
        .footer-bottom{border-top:1px solid #1e293b;padding-top:24px;display:flex;justify-content:space-between;align-items:center;font-size:.78rem}
        .footer-bottom-links{display:flex;gap:20px}
        .footer-bottom-links a{color:#94a3b8;transition:color .3s}
        .footer-bottom-links a:hover{color:#2dd4bf}

        /* ========== BACK TO TOP ========== */
        .back-to-top{position:fixed;bottom:30px;right:30px;width:48px;height:48px;border-radius:50%;background:#0d9488;color:#fff;border:none;cursor:pointer;font-size:1.1rem;display:none;align-items:center;justify-content:center;z-index:999;box-shadow:0 4px 15px rgba(13,148,136,.4);transition:all .3s}
        .back-to-top.show{display:flex}
        .back-to-top:hover{background:#0f766e;transform:translateY(-3px)}

        /* ========== MOBILE MENU ========== */
        .mobile-menu{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:flex-start;justify-content:flex-end}
        .mobile-menu.show{display:flex}
        .mobile-menu-content{background:#fff;width:280px;height:100%;padding:24px;box-shadow:-4px 0 20px rgba(0,0,0,.1)}
        .mobile-menu-close{background:none;border:none;font-size:1.3rem;color:#475569;cursor:pointer;float:right;margin-bottom:20px}
        .mobile-menu-links{clear:both;list-style:none}
        .mobile-menu-links li{margin-bottom:4px}
        .mobile-menu-links a{display:block;padding:10px 0;font-size:.9rem;font-weight:500;color:#374151;border-bottom:1px solid #f1f5f9}
        .mobile-menu-links a:hover{color:#0d9488}
        .mobile-menu-btns{margin-top:20px;display:flex;flex-direction:column;gap:10px}
        .mobile-menu-btns .btn-login,.mobile-menu-btns .btn-register{width:100%;text-align:center}

        /* ========== RESPONSIVE ========== */
        @media(max-width:1024px){
            .hero .container{grid-template-columns:1fr;text-align:center}
            .hero-visual{display:none}
            .hero-subtitle{margin:0 auto 32px}
            .hero-buttons{justify-content:center}
            .hero-stats{max-width:500px;margin:0 auto}
            .features-grid{grid-template-columns:repeat(2,1fr)}
            .services-grid{grid-template-columns:repeat(2,1fr)}
            .about-grid{grid-template-columns:1fr}
            .about-image{order:-1}
            .testimonials-grid{grid-template-columns:repeat(2,1fr)}
            .pricing-grid{grid-template-columns:repeat(2,1fr)}
            .pricing-card.popular{transform:none}
            .footer-grid{grid-template-columns:1fr 1fr}
        }
        @media(max-width:768px){
            .nav-links,.nav-cta a{display:none}
            .nav-cta{display:flex;align-items:center;gap:12px}
            .nav-cta .theme-toggle{display:none}
            .hamburger{display:block}
            #themeToggleMobile{display:block}
            .hero-title{font-size:2.2rem}
            .hero-stats{grid-template-columns:repeat(2,1fr)}
            .section{padding:60px 0}
            .section-header h2{font-size:1.8rem}
            .features-grid{grid-template-columns:1fr}
            .services-grid{grid-template-columns:1fr}
            .testimonials-grid{grid-template-columns:1fr}
            .pricing-grid{grid-template-columns:1fr}
            .contact-grid{grid-template-columns:1fr}
            .footer-grid{grid-template-columns:1fr}
            .footer-bottom{flex-direction:column;gap:12px;text-align:center}
        }
        @media(max-width:480px){
            .hero-title{font-size:1.8rem}
            .hero-stat .number{font-size:1.3rem}
            .cta-section h2{font-size:1.8rem}
        }

        /* ========== ANIMATIONS ========== */
        .animate-on-scroll{opacity:0;transform:translateY(30px);transition:all .6s ease}
        .animate-on-scroll.animated{opacity:1;transform:translateY(0)}
        .delay-1{transition-delay:.1s}
        .delay-2{transition-delay:.2s}
        .delay-3{transition-delay:.3s}
        .delay-4{transition-delay:.4s}

        /* ========== DARK MODE TOGGLE ========== */
        .theme-toggle{position:relative;width:48px;height:26px;background:#e2e8f0;border:none;border-radius:13px;cursor:pointer;transition:background .3s;flex-shrink:0;padding:0}
        .theme-toggle .toggle-track{position:absolute;inset:0;border-radius:13px;background:#e2e8f0;transition:background .3s}
        .dark .theme-toggle .toggle-track{background:#334155}
        .theme-toggle .toggle-thumb{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15);transition:all .3s;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#f59e0b}
        .dark .theme-toggle .toggle-thumb{left:25px;color:#60a5fa}
        .navbar.scrolled .theme-toggle{margin-right:4px}

        /* ========== LANGUAGE SWITCHER ========== */
        .lang-switch{position:relative;flex-shrink:0}
        .lang-btn{display:flex;align-items:center;gap:5px;padding:5px 10px;border:1.5px solid rgba(255,255,255,.4);border-radius:8px;background:transparent;color:#fff;font-weight:600;font-size:.78rem;cursor:pointer;transition:all .3s;font-family:inherit}
        .navbar.scrolled .lang-btn{border-color:#0d9488;color:#0d9488}
        .lang-btn:hover{background:#0d9488;color:#fff;border-color:#0d9488}
        .lang-btn .flag{font-size:1rem;line-height:1}
        .lang-btn .caret{font-size:.55rem;opacity:.7}
        .lang-dropdown{position:absolute;top:calc(100% + 8px);right:0;min-width:200px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);padding:6px;z-index:9999;display:none}
        .lang-dropdown.show{display:block}
        .lang-dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:.82rem;color:#475569;transition:background .15s;font-weight:500}
        .lang-dropdown a:hover{background:#f0fdf4;color:#0d9488}
        .lang-dropdown a.active{background:#f0fdf4;color:#0d9488;font-weight:700}
        .lang-dropdown a .flag{font-size:1.2rem}
        .lang-dropdown a .lang-meta{flex:1;line-height:1.3}
        .lang-dropdown a .lang-meta small{display:block;font-size:.66rem;color:#94a3b8;font-weight:400}
        .lang-dropdown a .check{color:#0d9488;font-size:.8rem}
        /* Sembunyikan widget & banner Google Translate */
        .goog-te-banner-frame.skiptranslate,iframe.goog-te-banner-frame{display:none!important}
        .goog-te-gadget,.goog-te-gadget-icon,.goog-logo-link,.goog-te-gadget span{display:none!important;height:0}
        body{top:0!important}
        .skiptranslate{min-height:0!important}
        .goog-tooltip,.goog-tooltip:hover{display:none!important}
        .goog-text-highlight{background:none!important;box-shadow:none!important}
        #google_translate_element{display:none!important}
        body.dark .lang-btn{border-color:rgba(255,255,255,.2);color:#e2e8f0}
        body.dark .navbar.scrolled .lang-btn{border-color:#2dd4bf;color:#2dd4bf}
        body.dark .lang-btn:hover{background:#0d9488;color:#fff;border-color:#0d9488}
        body.dark .lang-dropdown{background:#1e293b;border-color:#334155}
        body.dark .lang-dropdown a{color:#94a3b8}
        body.dark .lang-dropdown a:hover{background:rgba(13,148,136,.15);color:#2dd4bf}
        body.dark .lang-dropdown a.active{background:rgba(13,148,136,.15);color:#2dd4bf}
        @media(max-width:768px){
            .nav-cta .lang-switch{display:none}
            #langSwitchMobile{display:block}
            .lang-btn{padding:4px 8px;font-size:.72rem}
        }

        /* ========== DARK MODE ========== */
        body.dark{background:#0b0f1a;color:#e2e8f0}
        body.dark .section-gray{background:#111827}
        body.dark .section-dark{background:#0a0f1a}
        body.dark .section-gradient{background:linear-gradient(135deg,#0f766e 0%,#064e3b 100%)}
        body.dark .wa-group-section{background:linear-gradient(135deg,#052e16 0%,#0a0f1a 50%,#0f172a 100%)}
        body.dark .wa-group-text h2{color:#f1f5f9}
        body.dark .wa-group-text p{color:#94a3b8}
        body.dark .wa-benefit{background:rgba(34,197,94,.15);color:#4ade80}
        body.dark .wa-group-visual .wa-phone-mockup{background:linear-gradient(145deg,#064e3b,#065f46);box-shadow:0 16px 48px rgba(16,185,129,.1)}
        body.dark .wa-group-visual .wa-float{background:#1e293b;color:#e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,.3)}
        body.dark .feature-card{background:#1e293b;border-color:#334155;color:#e2e8f0}
        body.dark .feature-card:hover{border-color:#0d9488}
        body.dark .feature-card h3{color:#f1f5f9}
        body.dark .feature-card p{color:#94a3b8}
        body.dark .about-content h2{color:#f1f5f9}
        body.dark .about-content h2 span{color:#2dd4bf}
        body.dark .about-content p,body.dark .about-check{color:#94a3b8}
        body.dark .service-card{background:#1e293b;border-color:#334155}
        body.dark .service-card h3{color:#e2e8f0}
        body.dark .service-card p{color:#94a3b8}
        body.dark .pricing-card{background:#1e293b;border-color:#334155}
        body.dark .pricing-card h3{color:#f1f5f9}
        body.dark .pricing-amount .price{color:#2dd4bf}
        body.dark .pricing-amount .period{color:#64748b}
        body.dark .pricing-features li{color:#cbd5e1}
        body.dark .pricing-btn{border-color:#0d9488;color:#0d9488}
        body.dark .pricing-card.popular .pricing-btn{background:#0d9488;color:#fff}
        body.dark .testimonial-card{background:#1e293b;border-color:#334155}
        body.dark .testimonial-card blockquote{color:#cbd5e1}
        body.dark .testimonial-name{color:#f1f5f9}
        body.dark .faq-item{background:#1e293b;border-color:#334155}
        body.dark .faq-question{background:#1e293b;color:#e2e8f0}
        body.dark .faq-question:hover{background:#263044}
        body.dark .faq-answer p{color:#94a3b8}
        body.dark .section-header h2{color:#f1f5f9}
        body.dark .section-header p{color:#94a3b8}
        body.dark .cta-section h2{color:#fff}
        body.dark .btn-cta{background:#0f172a;color:#2dd4bf}
        body.dark .contact-item h4{color:#e2e8f0}
        body.dark .contact-item p{color:#94a3b8}
        body.dark .contact-socials a{background:#1e293b;color:#94a3b8}
        body.dark .contact-socials a:hover{background:#0d9488;color:#fff}
        body.dark .hero-badge{background:rgba(13,148,136,.25);border-color:rgba(13,148,136,.4)}
        body.dark .navbar.scrolled{background:rgba(11,15,26,.97)}
        body.dark .navbar.scrolled .nav-brand{color:#f1f5f9}
        body.dark .navbar.scrolled .nav-links a{color:#94a3b8}
        body.dark .navbar.scrolled .nav-links a:hover{color:#2dd4bf}
        body.dark .navbar.scrolled .hamburger{color:#e2e8f0}
        body.dark .btn-login{border-color:rgba(255,255,255,.2);color:#e2e8f0}
        body.dark .btn-login:hover{background:#0d9488;color:#fff;border-color:#0d9488}
        body.dark .mobile-menu-content{background:#1e293b;box-shadow:-4px 0 20px rgba(0,0,0,.4)}
        body.dark .mobile-menu-close{color:#94a3b8}
        body.dark .mobile-menu-links a{color:#cbd5e1;border-color:#334155}
        body.dark .mobile-menu-links a:hover{color:#2dd4bf}
        body.dark .about-image-placeholder{background:linear-gradient(135deg,#0f766e,#064e3b)}
        body.dark .track-box{background:rgba(255,255,255,.06)}
        body.dark .track-input{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.15)}
        body.dark .track-result{background:rgba(255,255,255,.06)}
        body.dark .qr-modal{background:#1e293b;box-shadow:0 24px 64px rgba(0,0,0,.5)}
        body.dark .qr-modal h3{color:#f1f5f9}
        body.dark .qr-modal .qr-subtitle{color:#94a3b8}
        body.dark .qr-modal .qr-image-wrapper{background:#0f172a;border-color:#334155}
        body.dark .qr-modal .qr-instruction{background:#1c1917;border-color:#44403c}
        body.dark .qr-modal .qr-instruction p{color:#d6d3d1}
        body.dark .qr-modal .qr-instruction p strong{color:#fafaf9}
        body.dark .qr-modal .qr-or{color:#64748b}
        body.dark .qr-modal-close{background:#334155;color:#94a3b8}
        body.dark .qr-modal-close:hover{background:#475569;color:#f1f5f9}
        body.dark [style*="background:#f8fafc"]{background:#1e293b!important;border-color:#334155!important}
        body.dark [style*="background:#f8fafc"] h3{color:#f1f5f9!important}
        body.dark [style*="background:#f8fafc"] input,
        body.dark [style*="background:#f8fafc"] textarea{background:#0f172a!important;border-color:#334155!important;color:#e2e8f0!important}
        body.dark [style*="background:#f8fafc"] input::placeholder,
        body.dark [style*="background:#f8fafc"] textarea::placeholder{color:#64748b!important}
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="/" class="nav-brand">
            <img src="{{ asset('logo-fixpro.jpg') }}" alt="FixPro">
            Fix<span>Pro</span>
        </a>
        <ul class="nav-links">
            <li><a href="#features">Fitur</a></li>
            <li><a href="#about">Tentang</a></li>
            <li><a href="#services">Layanan</a></li>
            <li><a href="#pricing">Harga</a></li>
            <li><a href="#testimonials">Testimoni</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#wa-group" style="color:#25D366;font-weight:600"><i class="fab fa-whatsapp" style="margin-right:4px"></i> {{ $waGroup['nav_label'] ?? 'Komunitas' }}</a></li>
            <li><a href="#contact">Kontak</a></li>
        </ul>
        <div class="nav-cta">
            {{-- Language Switcher (Google Translate) --}}
            @php
                $gtCookie = $_COOKIE['googtrans'] ?? '';
                $gtCurrent = 'id';
                foreach (['en','hi'] as $gtLangCode) {
                    if (str_contains($gtCookie, '/' . $gtLangCode)) { $gtCurrent = $gtLangCode; break; }
                }
                $gtLangs = [
                    'id' => ['flag' => '🇮🇩', 'code' => 'ID', 'native' => 'Bahasa Indonesia', 'name' => 'Indonesian'],
                    'en' => ['flag' => '🇬🇧', 'code' => 'EN', 'native' => 'English',         'name' => 'English'],
                    'hi' => ['flag' => '🇮🇳', 'code' => 'HI', 'native' => 'हिन्दी',           'name' => 'Hindi (India)'],
                ];
                $gtActive = $gtLangs[$gtCurrent];
            @endphp
            <div class="lang-switch" id="langSwitch">
                <button class="lang-btn" onclick="toggleLangSwitch(event)" title="Ganti Bahasa">
                    <span class="flag">{{ $gtActive['flag'] }}</span>
                    <span>{{ $gtActive['code'] }}</span>
                    <i class="fas fa-chevron-down caret"></i>
                </button>
                <div class="lang-dropdown" id="langDropdown" onclick="event.stopPropagation()">
                    @foreach($gtLangs as $code => $lg)
                    <a href="javascript:void(0)" onclick="setGTranslate('{{ $code }}')" class="{{ $code === $gtCurrent ? 'active' : '' }}">
                        <span class="flag">{{ $lg['flag'] }}</span>
                        <span class="lang-meta">{{ $lg['native'] }}<small>{{ $lg['name'] }}</small></span>
                        @if($code === $gtCurrent)<i class="fas fa-check check"></i>@endif
                    </a>
                    @endforeach
                </div>
            </div>

            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode" title="Ganti tema">
                <div class="toggle-track"></div>
                <div class="toggle-thumb"><i class="fas fa-sun"></i></div>
            </button>
            <a href="{{ route('login') }}" class="btn-login">{{ t('landing.login','Login') }}</a>
            <a href="{{ route('login') }}?tab=register" class="btn-register">{{ t('landing.register_free','Daftar Gratis') }}</a>
        </div>
        <button class="hamburger" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
        {{-- Language switcher (mobile) — Google Translate ID & EN --}}
        <div class="lang-switch" id="langSwitchMobile" style="display:none">
            <button class="lang-btn" onclick="toggleLangSwitch(event)" title="Ganti Bahasa">
                <span class="flag">{{ $gtActive['flag'] }}</span>
                <span>{{ $gtActive['code'] }}</span>
            </button>
            <div class="lang-dropdown" id="langDropdownMobile" onclick="event.stopPropagation()">
                @foreach($gtLangs as $code => $lg)
                <a href="javascript:void(0)" onclick="setGTranslate('{{ $code }}')" class="{{ $code === $gtCurrent ? 'active' : '' }}">
                    <span class="flag">{{ $lg['flag'] }}</span>
                    <span class="lang-meta">{{ $lg['native'] }}<small>{{ $lg['name'] }}</small></span>
                </a>
                @endforeach
            </div>
        </div>
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode" title="Ganti tema" style="display:none" id="themeToggleMobile">
            <div class="toggle-track"></div>
            <div class="toggle-thumb"><i class="fas fa-sun"></i></div>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-content">
        <button class="mobile-menu-close" onclick="toggleMobileMenu()"><i class="fas fa-times"></i></button>
        <ul class="mobile-menu-links">
            <li><a href="#features" onclick="toggleMobileMenu()">{{ t('landing.nav_features','Fitur') }}</a></li>
            <li><a href="#about" onclick="toggleMobileMenu()">{{ t('landing.nav_about','Tentang') }}</a></li>
            <li><a href="#services" onclick="toggleMobileMenu()">{{ t('landing.nav_services','Layanan') }}</a></li>
            <li><a href="#pricing" onclick="toggleMobileMenu()">{{ t('landing.nav_pricing','Harga') }}</a></li>
            <li><a href="#testimonials" onclick="toggleMobileMenu()">{{ t('landing.nav_testimonials','Testimoni') }}</a></li>
            <li><a href="#faq" onclick="toggleMobileMenu()">FAQ</a></li>
            <li><a href="#wa-group" onclick="toggleMobileMenu()" style="color:#25D366;font-weight:600"><i class="fab fa-whatsapp" style="margin-right:4px"></i> {{ $waGroup['nav_label'] ?? t('landing.nav_community','Komunitas') }}</a></li>
            <li><a href="#contact" onclick="toggleMobileMenu()">{{ t('landing.nav_contact','Kontak') }}</a></li>
        </ul>
        <div class="mobile-menu-btns">
            <a href="{{ route('login') }}" class="btn-login">{{ t('landing.login','Login') }}</a>
            <a href="{{ route('login') }}?tab=register" class="btn-register">{{ t('landing.register_free','Daftar Gratis') }}</a>
        </div>
    </div>
</div>

<!-- ========== HERO ========== -->
<section class="hero" id="hero">
    @if($banners->count() > 0)
    <div class="hero-slider" id="heroSlider">
        @foreach($banners as $i => $banner)
        <img src="{{ $banner->gambar && !str_starts_with($banner->gambar, 'http') ? Storage::url($banner->gambar) : ($banner->gambar ?? asset('logo-fixpro.jpg')) }}" alt="{{ $banner->judul }}" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;opacity:{{ $loop->first ? 1 : 0 }};transition:opacity 1s">
        @endforeach
    </div>
    @endif

    <div class="container">
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-circle"></i> Platform Servis #1 Indonesia</div>
            <h1 class="hero-title">{{ $hero['title'] ?? 'Solusi Servis HP Profesional' }}</h1>
            <p class="hero-subtitle">{{ $hero['subtitle'] ?? 'Kelola bisnis servis HP Anda dengan sistem yang modern, cepat, dan terpercaya.' }}</p>
            <div class="hero-buttons">
                <a href="{{ $hero['cta_link'] ?? '/login?tab=register' }}" class="btn-hero-primary"><i class="fas fa-rocket"></i> {{ $hero['cta_text'] ?? 'Mulai Sekarang' }}</a>
                <a href="{{ $hero['cta_secondary_link'] ?? '#features' }}" class="btn-hero-secondary"><i class="fas fa-play-circle"></i> {{ $hero['cta_secondary_text'] ?? 'Lihat Fitur' }}</a>
            </div>
            <div class="hero-stats">
                @foreach($heroStats as $stat)
                <div class="hero-stat">
                    <div class="number">{{ $stat['number'] }}</div>
                    <div class="label">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-phone-mockup">
                <div class="hero-phone-screen">
                    <div class="phone-header">
                        <div class="phone-header-logo"><img src="{{ asset('logo-fixpro.jpg') }}" alt="F"></div>
                        <div class="phone-header-text">FixPro</div>
                    </div>
                    <div class="phone-body">
                        <div class="phone-stat-row">
                            <div class="phone-stat"><div class="num">128</div><div class="lbl">Servis</div></div>
                            <div class="phone-stat"><div class="num">Rp 24.5jt</div><div class="lbl">Pendapatan</div></div>
                        </div>
                        <div class="phone-list-item">
                            <div class="phone-list-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="phone-list-text"><div class="title">iPhone 15 Pro</div><div class="sub">Ganti LCD</div></div>
                            <span class="phone-list-badge green">Selesai</span>
                        </div>
                        <div class="phone-list-item">
                            <div class="phone-list-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="phone-list-text"><div class="title">Samsung S24</div><div class="sub">Ganti Baterai</div></div>
                            <span class="phone-list-badge yellow">Proses</span>
                        </div>
                        <div class="phone-list-item">
                            <div class="phone-list-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="phone-list-text"><div class="title">Xiaomi 14</div><div class="sub">IC Charging</div></div>
                            <span class="phone-list-badge green">Selesai</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="phone-float-card card-1">
                <div class="float-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="float-text"><div class="num">+12 Servis</div><div class="lbl">Hari ini</div></div>
            </div>
            <div class="phone-float-card card-2">
                <div class="float-icon blue"><i class="fas fa-chart-line"></i></div>
                <div class="float-text"><div class="num">Rp 5.2jt</div><div class="lbl">Kas Hari Ini</div></div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FEATURES ========== -->
<section class="section section-gray" id="features">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $features['title'] ?? 'Fitur Unggulan' }}</h2>
            <p>{{ $features['subtitle'] ?? 'Semua yang Anda butuhkan dalam satu platform' }}</p>
        </div>
        <div class="features-grid">
            @foreach($featureItems as $i => $feature)
            <div class="feature-card animate-on-scroll delay-{{ ($i % 3) + 1 }}">
                <div class="feature-icon"><i class="{{ $feature['icon'] }}"></i></div>
                <h3>{{ $feature['title'] }}</h3>
                <p>{{ $feature['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ========== ABOUT ========== -->
<section class="section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content animate-on-scroll">
                <h2>{!! ($about['title'] ?? 'Tentang <span>FixPro</span>') !!}</h2>
                @if(isset($about['description']))
                    <div style="color:#64748b;line-height:1.8;font-size:.95rem;margin-bottom:20px">{!! $about['description'] !!}</div>
                @else
                    <p>FixPro adalah platform manajemen servis HP terdepan yang dirancang khusus untuk membantu pemilik bengkel dan toko servis HP.</p>
                @endif
                <div class="about-checks">
                    <div class="about-check"><i class="fas fa-check-circle"></i> Mudah Digunakan</div>
                    <div class="about-check"><i class="fas fa-check-circle"></i> Multi Cabang</div>
                    <div class="about-check"><i class="fas fa-check-circle"></i> Laporan Real-time</div>
                    <div class="about-check"><i class="fas fa-check-circle"></i> Aplikasi Mobile</div>
                    <div class="about-check"><i class="fas fa-check-circle"></i> Keamanan Data</div>
                    <div class="about-check"><i class="fas fa-check-circle"></i> Support 24/7</div>
                </div>
            </div>
            <div class="animate-on-scroll delay-2">
                @if($aboutImage)
                <div class="about-image">
                    <img src="{{ str_starts_with($aboutImage, 'http') ? $aboutImage : \Illuminate\Support\Facades\Storage::url($aboutImage) }}" alt="About FixPro">
                </div>
                @else
                <div class="about-image-placeholder">
                    <i class="fas fa-mobile-alt"></i>
                    <span>FixPro Service</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- ========== SERVICES ========== -->
<section class="section section-dark" id="services">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $services['title'] ?? 'Layanan Kami' }}</h2>
            <p>{{ $services['subtitle'] ?? 'Solusi lengkap untuk setiap kebutuhan bisnis servis Anda' }}</p>
        </div>
        <div class="services-grid">
            @foreach($serviceItems as $i => $service)
            <div class="service-card animate-on-scroll delay-{{ ($i % 3) + 1 }}" style="background:#1e293b;border-color:#334155">
                <div class="service-icon"><i class="{{ $service['icon'] }}"></i></div>
                <h3 style="color:#e2e8f0">{{ $service['title'] }}</h3>
                <p style="color:#94a3b8">{{ $service['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ========== PRICING ========== -->
<section class="section" id="pricing">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $pricing['title'] ?? 'Paket Harga' }}</h2>
            <p>{{ $pricing['subtitle'] ?? 'Pilih paket yang sesuai dengan kebutuhan bisnis Anda' }}</p>
        </div>
        <div class="pricing-grid">
            @foreach($pricingItems as $i => $plan)
            <div class="pricing-card animate-on-scroll delay-{{ ($i % 3) + 1 }} {{ ($plan['popular'] ?? false) ? 'popular' : '' }}">
                @if($plan['popular'] ?? false)
                <div class="pricing-popular-badge">⭐ Populer</div>
                @endif
                <h3>{{ $plan['name'] }}</h3>
                <div class="pricing-amount">
                    <span class="price">{{ $plan['price'] }}</span>
                    <span class="period">{{ $plan['period'] }}</span>
                </div>
                <ul class="pricing-features">
                    @foreach($plan['features'] as $feature)
                    <li><i class="fas fa-check-circle"></i> {{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="{{ $plan['button_link'] ?? '#' }}" class="pricing-btn">{{ $plan['button_text'] ?? 'Pilih Paket' }}</a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ========== TESTIMONIALS ========== -->
<section class="section section-gray" id="testimonials">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $testimonials['title'] ?? 'Apa Kata Mereka' }}</h2>
            <p>{{ $testimonials['subtitle'] ?? 'Testimoni dari pengguna FixPro' }}</p>
        </div>
        <div class="testimonials-grid">
            @foreach($testimonialItems as $i => $t)
            <div class="testimonial-card animate-on-scroll delay-{{ ($i % 3) + 1 }}">
                <div class="testimonial-stars">
                    @for($s = 0; $s < ($t['rating'] ?? 5); $s++)<i class="fas fa-star"></i>@endfor
                </div>
                <blockquote>"{{ $t['content'] }}"</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">{{ strtoupper(substr($t['name'], 0, 1)) }}</div>
                    <div>
                        <div class="testimonial-name">{{ $t['name'] }}</div>
                        <div class="testimonial-role">{{ $t['role'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ========== FAQ ========== -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $faq['title'] ?? 'Pertanyaan Umum' }}</h2>
        </div>
        <div class="faq-list">
            @foreach($faqItems as $i => $faq)
            <div class="faq-item animate-on-scroll">
                <div class="faq-question" onclick="toggleFaq(this)">
                    {{ $faq['question'] }}
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer"><p>{{ $faq['answer'] }}</p></div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ========== TRACK SERVICE ========== -->
<section class="section section-dark" id="track">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>Lacak Status Servis</h2>
            <p>Masukkan kode servis untuk melacak status perangkat Anda secara real-time</p>
        </div>
        <div class="track-box animate-on-scroll">
            <form class="track-form" onsubmit="trackServis(event)">
                <input type="text" id="trackKode" class="track-input" placeholder="Masukkan kode servis (contoh: SVC-001)">
                <button type="submit" class="track-btn"><i class="fas fa-search"></i> Lacak</button>
            </form>
            <div class="track-result" id="trackResult"></div>
        </div>
    </div>
</section>

<!-- ========== CTA ========== -->
<section class="section section-gradient" id="cta">
    <div class="container cta-section animate-on-scroll">
        <h2>{{ $cta['title'] ?? 'Siap Mengembangkan Bisnis Servis Anda?' }}</h2>
        <p>{{ $cta['subtitle'] ?? 'Bergabung dengan ratusan pemilik bengkel yang sudah menggunakan FixPro.' }}</p>
        <a href="{{ $cta['button_link'] ?? '/login?tab=register' }}" class="btn-cta"><i class="fas fa-rocket"></i> {{ $cta['button_text'] ?? 'Daftar Gratis Sekarang' }}</a>
    </div>
</section>

<!-- ========== MOBILE APP ========== -->
@if($apkAvailable)
<section class="section app-section" id="mobile-app">
    <div class="container">
        <div class="app-content animate-on-scroll">
            <div class="app-text">
                <h2><i class="fas fa-mobile-alt" style="color:#6366f1;margin-right:8px"></i> Aplikasi Mobile FixPro</h2>
                <p>Kelola bisnis servis HP Anda langsung dari smartphone. Monitor servis, kelola stok, dan lacak transaksi kapan saja, di mana saja.</p>
                <div class="app-features">
                    <div class="app-feature"><i class="fas fa-bolt"></i> Dashboard real-time</div>
                    <div class="app-feature"><i class="fas fa-barcode"></i> Scan & tracking servis</div>
                    <div class="app-feature"><i class="fas fa-chart-pie"></i> Laporan & keuangan</div>
                    <div class="app-feature"><i class="fas fa-bell"></i> Notifikasi langsung</div>
                </div>
                <a href="{{ route('website.download-apk') }}" class="btn-download-apk">
                    <i class="fab fa-android"></i> Download APK Android
                    <span class="apk-ver">v{{ $mobileApp['apk_version'] ?? '1.0' }}</span>
                </a>
            </div>
            <div class="app-visual">
                <div class="app-phone">
                    <div class="app-phone-screen">
                        <i class="fab fa-android"></i>
                        <div class="app-phone-title">FixPro</div>
                        <div class="app-phone-sub">Sistem Manajemen<br>Servis HP Profesional</div>
                    </div>
                    <div class="app-phone-badge">Android App</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- ========== WHATSAPP GROUP ========== -->
<section class="section wa-group-section" id="wa-group">
    <div class="container">
        <div class="wa-group-content animate-on-scroll">
            <div class="wa-group-text">
                <h2><i class="fab fa-whatsapp"></i> {{ $waGroup['title'] ?? 'Bergabung Grup WhatsApp FixPro' }}</h2>
                <p>{{ $waGroup['subtitle'] ?? 'Dapatkan informasi terbaru, tips servis HP, promo, dan dukungan langsung dari komunitas FixPro dan tim kami.' }}</p>
                <div class="wa-benefits">
                    <span class="wa-benefit"><i class="fas fa-check-circle"></i> Tips & Tutorial</span>
                    <span class="wa-benefit"><i class="fas fa-check-circle"></i> Promo Eksklusif</span>
                    <span class="wa-benefit"><i class="fas fa-check-circle"></i> Support Langsung</span>
                    <span class="wa-benefit"><i class="fas fa-check-circle"></i> Komunitas Teknisi</span>
                </div>
                <a href="{{ $waGroup['link'] ?? 'https://chat.whatsapp.com/G41Mmc3CzWD2CsQGSlEljj' }}" target="_blank" rel="noopener" class="btn-join-wa" id="btnJoinWa">
                    <i class="fab fa-whatsapp"></i> {{ $waGroup['button_text'] ?? 'Bergabung Sekarang' }}
                </a>
            </div>
            <div class="wa-group-visual">
                <div class="wa-phone-mockup">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="wa-float top-right">
                    <span class="wa-dot"></span> {{ $waGroup['stat_members'] ?? '1.2k+' }} Anggota
                </div>
                <div class="wa-float bottom-left">
                    <span class="wa-dot"></span> {{ $waGroup['stat_active'] ?? 'Aktif Setiap Hari' }}
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QR Code Modal (Desktop Only) -->
<div class="qr-modal-overlay" id="qrModal">
    <div class="qr-modal">
        <button class="qr-modal-close" onclick="closeQrModal()"><i class="fas fa-times"></i></button>
        <div class="qr-modal-icon"><i class="fab fa-whatsapp"></i></div>
        <h3>Scan QR Code</h3>
        <p class="qr-subtitle">Buka kamera HP Anda dan arahkan ke QR Code di bawah ini untuk bergabung ke Grup WhatsApp FixPro</p>
        <div class="qr-image-wrapper">
            <img src="{{ $waGroupQrImage ? Storage::url($waGroupQrImage) : asset('Fixpro_Official_Support.png') }}" alt="QR Code Grup WhatsApp FixPro">
        </div>
        <div class="qr-instruction">
            <i class="fas fa-mobile-alt"></i>
            <p><strong>Cara bergabung:</strong> Buka aplikasi WhatsApp di HP Anda → Ketuk ikon kamera di pojok kanan atas pemindai → Arahkan ke QR Code ini → Otomatis terbuka grup.</p>
        </div>
        <div class="qr-or">— atau buka langsung di browser HP Anda —</div>
        <a href="{{ $waGroup['link'] ?? 'https://chat.whatsapp.com/G41Mmc3CzWD2CsQGSlEljj' }}" target="_blank" rel="noopener" class="qr-link">
            <i class="fab fa-whatsapp"></i> Buka di WhatsApp
        </a>
    </div>
</div>

<!-- ========== CONTACT ========== -->
<section class="section" id="contact">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <h2>{{ $contact['title'] ?? 'Hubungi Kami' }}</h2>
            <p>{{ $contact['subtitle'] ?? 'Ada pertanyaan? Tim kami siap membantu Anda' }}</p>
        </div>
        <div class="contact-grid">
            <div class="contact-info animate-on-scroll">
                @if(!empty($contact['whatsapp']))
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fab fa-whatsapp"></i></div>
                    <div>
                        <h4>WhatsApp</h4>
                        <p>+{{ $contact['whatsapp'] }}</p>
                    </div>
                </div>
                @endif
                @if(!empty($contact['email']))
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h4>Email</h4>
                        <p>{{ $contact['email'] }}</p>
                    </div>
                </div>
                @endif
                @if(!empty($contact['address']))
                <div class="contact-item">
                    <div class="contact-item-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h4>Alamat</h4>
                        <p>{{ $contact['address'] }}</p>
                    </div>
                </div>
                @endif
                <div class="contact-socials">
                    @if(!empty($contact['instagram']))
                    <a href="https://instagram.com/{{ $contact['instagram'] }}" target="_blank"><i class="fab fa-instagram"></i></a>
                    @endif
                    @if(!empty($contact['facebook']))
                    <a href="https://facebook.com/{{ $contact['facebook'] }}" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    @endif
                    @if(!empty($contact['youtube']))
                    <a href="https://youtube.com/@{{ $contact['youtube'] }}" target="_blank"><i class="fab fa-youtube"></i></a>
                    @endif
                    <a href="https://wa.me/{{ $contact['whatsapp'] ?? '' }}" target="_blank"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="animate-on-scroll delay-2">
                <div style="background:#f8fafc;border-radius:16px;padding:32px;border:1px solid #e2e8f0">
                    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:20px">Kirim Pesan</h3>
                    <form onsubmit="sendMessage(event)">
                        <div style="margin-bottom:14px">
                            <input type="text" placeholder="Nama Anda" style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.88rem;outline:none" required>
                        </div>
                        <div style="margin-bottom:14px">
                            <input type="email" placeholder="Email Anda" style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.88rem;outline:none" required>
                        </div>
                        <div style="margin-bottom:14px">
                            <textarea placeholder="Pesan Anda" rows="4" style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.88rem;outline:none;resize:vertical" required></textarea>
                        </div>
                        <button type="submit" style="width:100%;padding:12px;border:none;border-radius:10px;background:#0d9488;color:#fff;font-weight:700;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px"><i class="fas fa-paper-plane"></i> Kirim Pesan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>Fix<span>Pro</span> AL2000</h3>
                <p>{{ $footer['tagline'] ?? 'Sistem Manajemen Servis Profesional' }}</p>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <a href="{{ route('login') }}" style="padding:8px 20px;border:1px solid #334155;border-radius:8px;color:#94a3b8;font-size:.8rem;font-weight:600;transition:all .3s">Login</a>
                    <a href="{{ route('login') }}?tab=register" style="padding:8px 20px;border:none;border-radius:8px;background:#0d9488;color:#fff;font-size:.8rem;font-weight:600;transition:all .3s">Daftar Gratis</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Produk</h4>
                <a href="#features">Fitur</a>
                <a href="#pricing">Harga</a>
                <a href="#services">Layanan</a>
                @if($apkAvailable)<a href="{{ route('website.download-apk') }}"><i class="fab fa-android" style="margin-right:4px;color:#22c55e"></i> Download App</a>@else
                <a href="#" style="opacity:.5;pointer-events:none">Download App (Segera Hadir)</a>@endif
            </div>
            <div class="footer-col">
                <h4>Perusahaan</h4>
                <a href="#about">Tentang Kami</a>
                <a href="#testimonials">Testimoni</a>
                <a href="#faq">FAQ</a>
                <a href="#contact">Kontak</a>
            </div>
            <div class="footer-col">
                <h4>Kontak</h4>
                @if(!empty($contact['email']))<a href="mailto:{{ $contact['email'] }}"><i class="fas fa-envelope" style="margin-right:6px"></i> {{ $contact['email'] }}</a>@endif
                @if(!empty($contact['whatsapp']))<a href="https://wa.me/{{ $contact['whatsapp'] }}"><i class="fab fa-whatsapp" style="margin-right:6px"></i> +{{ $contact['whatsapp'] }}</a>@endif
                @if(!empty($contact['address']))<a href="#"><i class="fas fa-map-marker-alt" style="margin-right:6px"></i> {{ $contact['address'] }}</a>@endif
            </div>
        </div>
        <div class="footer-bottom">
            <span>{{ $footer['copyright'] ?? '© 2026 FixPro AL2000. All rights reserved.' }}</span>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fas fa-chevron-up"></i></button>

<script>
// ── Dark/Light Mode ──
(function(){
    const saved=localStorage.getItem('fixpro-theme');
    if(saved==='dark'||(!saved&&window.matchMedia('(prefers-color-scheme:dark)').matches)){
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
    }
    updateToggleIcons();
})();
function toggleTheme(){
    document.documentElement.classList.toggle('dark');
    document.body.classList.toggle('dark');
    const isDark=document.body.classList.contains('dark');
    localStorage.setItem('fixpro-theme',isDark?'dark':'light');
    updateToggleIcons();
}
function updateToggleIcons(){
    const isDark=document.body.classList.contains('dark');
    document.querySelectorAll('.toggle-thumb i').forEach(i=>{
        i.className=isDark?'fas fa-moon':'fas fa-sun';
    });
}

// ========== LANGUAGE SWITCHER TOGGLE ==========
function toggleLangSwitch(e){
    if(e) e.stopPropagation();
    const dd=document.getElementById('langDropdown')||document.getElementById('langDropdownMobile');
    if(dd) dd.classList.toggle('show');
}
document.addEventListener('click',function(e){
    if(!e.target.closest('#langSwitch')&&!e.target.closest('#langSwitchMobile')){
        const d1=document.getElementById('langDropdown');
        const d2=document.getElementById('langDropdownMobile');
        if(d1) d1.classList.remove('show');
        if(d2) d2.classList.remove('show');
    }
});

// Detect mobile device
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || (window.innerWidth <= 768);
}

// WhatsApp Group button behavior: mobile → direct link, desktop → QR modal
const btnJoinWa = document.getElementById('btnJoinWa');
const qrModal = document.getElementById('qrModal');
const waGroupLink = 'https://chat.whatsapp.com/G41Mmc3CzWD2CsQGSlEljj';

if (btnJoinWa) {
    btnJoinWa.addEventListener('click', function(e) {
        if (!isMobileDevice()) {
            e.preventDefault();
            qrModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        // On mobile: default <a> behavior will navigate to the WhatsApp link
    });
}

function closeQrModal() {
    qrModal.classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on overlay click
if (qrModal) {
    qrModal.addEventListener('click', function(e) {
        if (e.target === qrModal) {
            closeQrModal();
        }
    });
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQrModal();
});

// Navbar scroll effect
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
    document.getElementById('backToTop').classList.toggle('show', window.scrollY > 400);
});

// Mobile menu
function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('show');
}

// FAQ toggle
function toggleFaq(el) {
    el.parentElement.classList.toggle('active');
}

// Hero slider
@if($banners->count() > 1)
const sliderImages = document.querySelectorAll('#heroSlider img');
let currentSlide = 0;
setInterval(() => {
    sliderImages[currentSlide].style.opacity = '0';
    currentSlide = (currentSlide + 1) % sliderImages.length;
    sliderImages[currentSlide].style.opacity = '1';
}, 5000);
@endif

// Scroll animations
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animated');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

// Track service
async function trackServis(e) {
    e.preventDefault();
    const kode = document.getElementById('trackKode').value.trim();
    if (!kode) return;
    const result = document.getElementById('trackResult');
    result.innerHTML = '<div style="text-align:center;padding:10px"><i class="fas fa-spinner fa-spin" style="font-size:1.2rem"></i></div>';
    result.classList.add('show');
    try {
        const res = await fetch('/lacak-servis', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'', 'Accept':'application/json'},
            body: JSON.stringify({kode})
        });
        const data = await res.json();
        if (data.error || data.message) {
            result.innerHTML = '<div style="padding:12px;text-align:center;color:#fca5a5"><i class="fas fa-exclamation-circle"></i> ' + (data.error || data.message) + '</div>';
        } else {
            const isSelesai = data.status === 'Selesai';
            const badgeBg = isSelesai ? '#052e16' : '#451a03';
            const badgeColor = isSelesai ? '#4ade80' : '#fbbf24';
            result.innerHTML = '<div style="padding:16px">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px">' +
                '<span style="font-weight:800;color:#2dd4bf;font-size:.95rem">' + (data.kode||'-') + '</span>' +
                '<span style="padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;background:' + badgeBg + ';color:' + badgeColor + '">' + (data.status||'-') + '</span>' +
                '</div>' +
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">' +
                '<div style="font-size:.82rem;color:#94a3b8"><i class="fas fa-mobile-alt" style="margin-right:6px;color:#2dd4bf"></i> ' + (data.perangkat||'-') + '</div>' +
                '<div style="font-size:.82rem;color:#94a3b8"><i class="fas fa-user" style="margin-right:6px;color:#2dd4bf"></i> ' + (data.pelanggan||'-') + '</div>' +
                '<div style="font-size:.82rem;color:#94a3b8"><i class="fas fa-tools" style="margin-right:6px;color:#2dd4bf"></i> ' + (data.teknisi||'-') + '</div>' +
                '<div style="font-size:.82rem;color:#94a3b8"><i class="fas fa-store" style="margin-right:6px;color:#2dd4bf"></i> ' + (data.cabang||'-') + '</div>' +
                '</div>' +
                (data.keluhan ? '<div style="font-size:.8rem;color:#64748b;margin-bottom:10px;padding:8px 10px;background:rgba(255,255,255,.05);border-radius:8px"><i class="fas fa-clipboard-list" style="margin-right:6px;color:#2dd4bf"></i><strong style="color:#94a3b8">Keluhan:</strong> ' + data.keluhan + '</div>' : '') +
                '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;padding-top:10px;border-top:1px solid rgba(255,255,255,.1)">' +
                '<span style="font-size:.78rem;color:#64748b"><i class="fas fa-clock" style="margin-right:4px"></i> Update: ' + (data.updated_at||'-') + '</span>' +
                '<span style="font-size:.85rem;font-weight:800;color:#2dd4bf">' + (data.biaya||'-') + '</span>' +
                '</div>' +
                '</div>';
        }
    } catch(err) {
        result.innerHTML = '<div style="padding:12px;text-align:center;color:#fca5a5"><i class="fas fa-exclamation-circle"></i> Gagal menghubungi server</div>';
    }
}

// Send message (opens WhatsApp)
function sendMessage(e) {
    e.preventDefault();
    const wa = '{{ $contact['whatsapp'] ?? '' }}';
    if (wa) {
        window.open('https://wa.me/' + wa + '?text=Halo%20FixPro%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Anda.', '_blank');
    }
}
</script>

{{-- ===== Google Translate (hanya ID & EN) ===== --}}
<div id="google_translate_element" style="display:none!important"></div>
<script>
function googleTranslateElementInit(){
    new google.translate.TranslateElement({
        pageLanguage: 'id',
        includedLanguages: 'id,en,hi',
        autoDisplay: false
    }, 'google_translate_element');
}
function setGTranslate(lang){
    var domain = '.' + location.hostname.replace(/^www\./, '');
    // hapus cookie lama
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + domain;
    // 'id' = bahasa asli (tidak perlu translate), selain itu set /id/<lang>
    if (lang && lang !== 'id') {
        var val = '/id/' + lang;
        document.cookie = 'googtrans=' + val + '; path=/; domain=' + domain;
        document.cookie = 'googtrans=' + val + '; path=/';
    }
    location.reload();
}
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html>
