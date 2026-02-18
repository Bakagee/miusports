<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo SITE_NAME; ?> — Sports Registration Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=Crimson+Pro:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
    /* ══════════════════════════════════════════════
       DESIGN TOKENS — MIU Official Brand Palette
    ══════════════════════════════════════════════ */
    :root {
        --miu-red:      #A31D24;
        --miu-red-dark: #7B181E;
        --miu-red-deep: #550F13;
        --miu-gold:     #FFCC33;
        --miu-gold-dim: #D4A800;
        --miu-white:    #FFFFFF;
        --miu-off:      #F8F5F0;
        --miu-charcoal: #212121;
        --miu-gray:     #6B6B6B;

        --font-display: 'Bebas Neue', sans-serif;
        --font-body:    'DM Sans', sans-serif;
        --font-serif:   'Crimson Pro', Georgia, serif;
    }

    /* ══════════════════════════════════════════════
       RESET & BASE
    ══════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; }

    body {
        font-family: var(--font-body);
        background-color: var(--miu-charcoal);
        color: var(--miu-white);
        overflow-x: hidden;
    }

    /* ══════════════════════════════════════════════
       HERO SECTION
    ══════════════════════════════════════════════ */
    .hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Hero background: a Unsplash sports stadium image + layered overlays */
    .hero-bg {
        position: absolute;
        inset: 0;
        background-image: url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=1800&auto=format&fit=crop&q=80');
        background-size: cover;
        background-position: center 30%;
        transform: scale(1.04);
        animation: heroZoom 18s ease-in-out infinite alternate;
    }

    @keyframes heroZoom {
        from { transform: scale(1.04); }
        to   { transform: scale(1.10); }
    }

    /* Layered overlay: deep red at bottom, dark at top */
    .hero-overlay {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(to bottom,
                rgba(33,33,33,0.72) 0%,
                rgba(123,24,30,0.55) 50%,
                rgba(85,15,19,0.92) 100%
            );
    }

    /* Diagonal gold accent stripe */
    .hero-stripe {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--miu-red), var(--miu-gold), var(--miu-red));
    }

    /* ══════════════════════════════════════════════
       NAVBAR
    ══════════════════════════════════════════════ */
    .site-nav {
        position: relative;
        z-index: 10;
        padding: 1.4rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255,204,51,0.15);
        background: rgba(33,33,33,0.35);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .nav-logo {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        text-decoration: none;
    }

    .nav-logo-img {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 2px solid var(--miu-gold);
        object-fit: cover;
        background: var(--miu-red-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .nav-logo-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Fallback crest if logo not available */
    .nav-logo-img .crest-placeholder {
        font-size: 1.5rem;
        color: var(--miu-gold);
    }

    .nav-logo-text {
        line-height: 1.15;
    }

    .nav-logo-text .uni-name {
        display: block;
        font-family: var(--font-display);
        font-size: 1.1rem;
        letter-spacing: 0.08em;
        color: var(--miu-white);
    }

    .nav-logo-text .dept-name {
        display: block;
        font-size: 0.7rem;
        font-weight: 500;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: var(--miu-gold);
    }

    .nav-badge {
        font-family: var(--font-body);
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--miu-gold);
        border: 1px solid rgba(255,204,51,0.4);
        padding: 0.3rem 0.8rem;
        border-radius: 100px;
        background: rgba(255,204,51,0.08);
    }

    /* ══════════════════════════════════════════════
       HERO CONTENT
    ══════════════════════════════════════════════ */
    .hero-content {
        position: relative;
        z-index: 5;
        flex: 1;
        display: flex;
        align-items: center;
        padding: 3rem 2rem 5rem;
    }

    .hero-inner {
        max-width: 780px;
    }

    .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--miu-gold);
        margin-bottom: 1.2rem;
        animation: fadeUp 0.6s ease both;
    }

    .hero-eyebrow::before {
        content: '';
        display: block;
        width: 28px;
        height: 2px;
        background: var(--miu-gold);
        border-radius: 2px;
    }

    .hero-title {
        font-family: var(--font-display);
        font-size: clamp(3.2rem, 8vw, 6.5rem);
        line-height: 0.95;
        letter-spacing: 0.02em;
        color: var(--miu-white);
        margin-bottom: 0.3rem;
        animation: fadeUp 0.7s ease 0.1s both;
    }

    .hero-title .accent {
        color: var(--miu-gold);
        display: block;
    }

    .hero-subtitle {
        font-family: var(--font-serif);
        font-size: clamp(1.1rem, 2.5vw, 1.45rem);
        font-weight: 400;
        color: rgba(255,255,255,0.78);
        margin: 1.2rem 0 2.2rem;
        max-width: 520px;
        line-height: 1.6;
        animation: fadeUp 0.7s ease 0.2s both;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        animation: fadeUp 0.7s ease 0.3s both;
    }

    /* PRIMARY CTA */
    .btn-register {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: var(--miu-gold);
        color: var(--miu-charcoal);
        font-family: var(--font-body);
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: none;
        border-radius: 6px;
        padding: 0.9rem 2rem;
        cursor: pointer;
        transition: background 0.2s, transform 0.18s, box-shadow 0.2s;
        box-shadow: 0 4px 24px rgba(255,204,51,0.35);
        text-decoration: none;
    }

    .btn-register:hover {
        background: #ffe066;
        transform: translateY(-2px);
        box-shadow: 0 8px 32px rgba(255,204,51,0.5);
        color: var(--miu-charcoal);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    .btn-register i {
        font-size: 1.05rem;
    }

    /* SECONDARY link */
    .btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: transparent;
        color: rgba(255,255,255,0.75);
        font-size: 0.88rem;
        font-weight: 500;
        letter-spacing: 0.04em;
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 6px;
        padding: 0.9rem 1.5rem;
        text-decoration: none;
        transition: border-color 0.2s, color 0.2s, background 0.2s;
    }

    .btn-ghost:hover {
        border-color: rgba(255,255,255,0.6);
        color: var(--miu-white);
        background: rgba(255,255,255,0.07);
    }

    /* ══════════════════════════════════════════════
       HERO STATS BAR
    ══════════════════════════════════════════════ */
    .hero-stats {
        position: relative;
        z-index: 5;
        padding: 0 2rem 3rem;
        animation: fadeUp 0.7s ease 0.45s both;
    }

    .stats-row {
        display: flex;
        gap: 0;
        border: 1px solid rgba(255,204,51,0.2);
        border-radius: 10px;
        overflow: hidden;
        background: rgba(33,33,33,0.5);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        max-width: 640px;
    }

    .stat-item {
        flex: 1;
        padding: 1rem 1.4rem;
        border-right: 1px solid rgba(255,204,51,0.15);
        text-align: center;
    }

    .stat-item:last-child { border-right: none; }

    .stat-num {
        font-family: var(--font-display);
        font-size: 2rem;
        letter-spacing: 0.04em;
        color: var(--miu-gold);
        line-height: 1;
        display: block;
    }

    .stat-label {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.55);
        margin-top: 0.25rem;
        display: block;
    }

    /* ══════════════════════════════════════════════
       SPORTS SECTION (below hero)
    ══════════════════════════════════════════════ */
    .sports-section {
        background: var(--miu-off);
        padding: 5rem 1.5rem;
    }

    .section-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: var(--miu-red);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(163,29,36,0.2);
        max-width: 80px;
    }

    .section-title {
        font-family: var(--font-display);
        font-size: clamp(2rem, 5vw, 3.2rem);
        letter-spacing: 0.04em;
        color: var(--miu-charcoal);
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .section-desc {
        font-family: var(--font-serif);
        font-size: 1.1rem;
        color: var(--miu-gray);
        max-width: 480px;
        margin-bottom: 3rem;
    }

    /* Sport Cards */
    .sport-card {
        border-radius: 14px;
        overflow: hidden;
        position: relative;
        min-height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        box-shadow: 0 8px 40px rgba(0,0,0,0.18);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .sport-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 56px rgba(0,0,0,0.25);
    }

    .sport-card-bg {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        transition: transform 0.5s ease;
    }

    .sport-card:hover .sport-card-bg {
        transform: scale(1.06);
    }

    .sport-card-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(85,15,19,0.95) 0%, rgba(163,29,36,0.3) 60%, transparent 100%);
    }

    .sport-card-body {
        position: relative;
        z-index: 2;
        padding: 1.8rem;
    }

    .sport-icon {
        font-size: 2.4rem;
        margin-bottom: 0.5rem;
        display: block;
        filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));
    }

    .sport-name {
        font-family: var(--font-display);
        font-size: 2.1rem;
        letter-spacing: 0.06em;
        color: var(--miu-white);
        line-height: 1;
        margin-bottom: 0.4rem;
    }

    .sport-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 0.8rem;
    }

    .sport-pill {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        background: rgba(255,204,51,0.18);
        border: 1px solid rgba(255,204,51,0.4);
        color: var(--miu-gold);
        padding: 0.28rem 0.75rem;
        border-radius: 100px;
    }

    .sport-teams-count {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.6);
        font-weight: 500;
    }

    /* ══════════════════════════════════════════════
       FOOTER
    ══════════════════════════════════════════════ */
    .site-footer {
        background: #111111;
        padding: 2.5rem 2rem;
        border-top: 1px solid rgba(255,204,51,0.12);
    }

    /* ══════════════════════════════════════════════
       FOOTER
    ══════════════════════════════════════════════ */
    .site-footer {
        background: #111111;
        padding: 2.5rem 2rem;
        border-top: 1px solid rgba(255,204,51,0.12);
    }

    .footer-logo-text {
        font-family: var(--font-display);
        font-size: 1.3rem;
        letter-spacing: 0.1em;
        color: var(--miu-white);
    }

    .footer-sub {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.35);
        margin-top: 0.2rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .footer-copy {
        font-size: 0.78rem;
        color: rgba(255,255,255,0.3);
        text-align: right;
    }

    .footer-divider {
        height: 1px;
        background: rgba(255,255,255,0.06);
        margin: 1.5rem 0;
    }

    /* ══════════════════════════════════════════════
       LOGIN MODAL
    ══════════════════════════════════════════════ */
    .modal-backdrop.show { background: rgba(15,10,10,0.75); }

    .modal-content {
        background: var(--miu-white);
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0,0,0,0.5);
    }

    .modal-header-custom {
        background: linear-gradient(135deg, var(--miu-red-deep) 0%, var(--miu-red) 100%);
        padding: 2rem 2rem 1.6rem;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .modal-header-custom::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--miu-gold), transparent);
    }

    .modal-logo-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.2rem;
    }

    .modal-logo-circle {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
        border: 2px solid rgba(255,204,51,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .modal-logo-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .modal-logo-circle .crest-icon {
        font-size: 1.6rem;
        color: var(--miu-gold);
    }

    .modal-uni-name {
        font-family: var(--font-display);
        font-size: 1.05rem;
        letter-spacing: 0.08em;
        color: var(--miu-white);
        line-height: 1.2;
    }

    .modal-dept-tag {
        font-size: 0.65rem;
        letter-spacing: 0.15em;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--miu-gold);
        margin-top: 2px;
    }

    .modal-heading {
        font-family: var(--font-display);
        font-size: 2rem;
        letter-spacing: 0.05em;
        color: var(--miu-white);
        line-height: 1;
    }

    .modal-heading-sub {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.6);
        margin-top: 0.4rem;
        letter-spacing: 0.04em;
    }

    .modal-close-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255,255,255,0.12);
        border: none;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.8);
        cursor: pointer;
        transition: background 0.2s;
        font-size: 1rem;
        line-height: 1;
    }

    .modal-close-btn:hover { background: rgba(255,255,255,0.22); color: white; }

    .modal-body-custom {
        padding: 2rem 2rem 1.5rem;
        background: var(--miu-white);
    }

    .matric-label {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--miu-charcoal);
        margin-bottom: 0.6rem;
        display: block;
    }

    .matric-input-wrap {
        position: relative;
    }

    .matric-input-wrap .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--miu-red);
        font-size: 1rem;
        pointer-events: none;
    }

    .matric-input {
        width: 100%;
        padding: 0.9rem 1rem 0.9rem 2.8rem;
        border: 2px solid var(--miu-gray-light, #ddd);
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        color: var(--miu-charcoal);
        text-transform: none;
        background: #FAFAFA;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
    }

    .matric-input:focus {
        border-color: var(--miu-red);
        box-shadow: 0 0 0 4px rgba(163,29,36,0.1);
        background: var(--miu-white);
    }

    .matric-input::placeholder {
        font-family: var(--font-body);
        font-weight: 400;
        letter-spacing: 0.03em;
        text-transform: none;
        color: #AAAAAA;
        font-size: 0.9rem;
    }

    /* Error message */
    .login-error {
        display: none;
        background: #FFF5F5;
        border: 1px solid rgba(163,29,36,0.3);
        border-left: 4px solid var(--miu-red);
        border-radius: 6px;
        padding: 0.75rem 1rem;
        margin-top: 0.8rem;
        font-size: 0.85rem;
        color: var(--miu-red-dark);
        font-weight: 500;
    }

    .login-error i { margin-right: 0.4rem; }

    /* Submit button */
    .btn-login-submit {
        width: 100%;
        background: var(--miu-red);
        color: var(--miu-white);
        font-family: var(--font-body);
        font-weight: 700;
        font-size: 0.92rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border: none;
        border-radius: 8px;
        padding: 0.95rem;
        margin-top: 1.2rem;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        box-shadow: 0 4px 20px rgba(163,29,36,0.3);
    }

    .btn-login-submit:hover:not(:disabled) {
        background: var(--miu-red-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 28px rgba(163,29,36,0.45);
    }

    .btn-login-submit:active { transform: translateY(0); }

    .btn-login-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .btn-login-submit .spinner {
        display: none;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .modal-footer-note {
        text-align: center;
        font-size: 0.75rem;
        color: var(--miu-gray);
        padding: 0 2rem 1.5rem;
        background: var(--miu-white);
    }

    .modal-footer-note i {
        color: var(--miu-red);
        margin-right: 0.25rem;
    }

    /* ══════════════════════════════════════════════
       ANIMATIONS
    ══════════════════════════════════════════════ */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(22px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ══════════════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════════════ */
    @media (max-width: 576px) {
        .site-nav { padding: 1rem 1.1rem; }
        .hero-content { padding: 2rem 1.1rem 3rem; }
        .hero-stats { padding: 0 1.1rem 2rem; }
        .stats-row { flex-wrap: wrap; }
        .stat-item { flex: 0 0 50%; border-bottom: 1px solid rgba(255,204,51,0.12); }
        .nav-badge { display: none; }
        .sports-section { padding: 3.5rem 1.1rem; }
        .section-desc { font-size: 1rem; }
        .site-footer { padding: 2rem 1.1rem; }
        .footer-copy { text-align: left; margin-top: 0.8rem; }
        .modal-content { border-radius: 12px; }
    }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════════════ -->
<section class="hero">

    <!-- Background image + overlay -->
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="hero-stripe" aria-hidden="true"></div>

    <!-- ── Navbar ── -->
    <nav class="site-nav">
        <a href="#" class="nav-logo">
            <div class="nav-logo-img">
                <!-- Swap src with miulogo.jpg when ready -->
                <img src="miulogo.jpeg"
                     alt="MIU Logo"
                     onerror="this.style.display='none'; this.parentNode.innerHTML='<span class=\'crest-placeholder\'>🏛</span>'">
            </div>
            <div class="nav-logo-text">
                <span class="uni-name">Mewar International University</span>
                <span class="dept-name">Sports Directorate</span>
            </div>
        </a>
        <span class="nav-badge">
            <i class="bi bi-shield-fill-check me-1"></i>Registration Open
        </span>
    </nav>

    <!-- ── Main content ── -->
    <div class="hero-content">
        <div class="hero-inner">

            <div class="hero-eyebrow">
                Sports Directorate &nbsp;·&nbsp; Official Portal
            </div>

            <h1 class="hero-title">
                MIU SPORTS
                <span class="accent">REGISTRATION</span>
            </h1>

            <p class="hero-subtitle">
                Represent your team. Secure your spot. Be part of the MIU sporting legacy —
                register now before your team fills up.
            </p>

            <div class="hero-actions">
                <button class="btn-register"
                        data-bs-toggle="modal"
                        data-bs-target="#loginModal">
                    <i class="bi bi-person-badge-fill"></i>
                    Login to Register
                </button>
                <a href="#sports-info" class="btn-ghost">
                    <i class="bi bi-chevron-down"></i>
                    View Sports
                </a>
            </div>

        </div>
    </div>

    <!-- ── Stats bar ── -->
    <div class="hero-stats">
        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-num">8</span>
                <span class="stat-label">Football Teams</span>
            </div>
            <div class="stat-item">
                <span class="stat-num">4</span>
                <span class="stat-label">Volleyball Teams</span>
            </div>
            <div class="stat-item">
                <span class="stat-num">12</span>
                <span class="stat-label">Max per Football</span>
            </div>
            <div class="stat-item">
                <span class="stat-num">8</span>
                <span class="stat-label">Max per Volleyball</span>
            </div>
        </div>
    </div>

</section>


<!-- ═══════════════════════════════════════════════════════════════
     SPORTS SECTION
═══════════════════════════════════════════════════════════════ -->
<section class="sports-section" id="sports-info">
    <div class="container">

        <div class="section-label">
            <i class="bi bi-trophy-fill me-1"></i> Compete &amp; Win
        </div>
        <h2 class="section-title">TWO SPORTS.<br>YOUR CALL.</h2>
        <p class="section-desc">
            Male players may register for both Football and Volleyball.
            Female players register for Volleyball. One team per sport — first come, first served.
        </p>

        <div class="row g-4">

            <!-- Football Card -->
            <div class="col-md-6">
                <a class="sport-card d-block" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="sport-card-bg"
                         style="background-image:url('miufootball.jpg')">
                    </div>
                    <div class="sport-card-overlay"></div>
                    <div class="sport-card-body">
                        <span class="sport-icon">⚽</span>
                        <div class="sport-name">FOOTBALL</div>
                        <p style="color:rgba(255,255,255,0.65);font-size:.88rem;margin-top:.4rem;">
                            Open to male players only
                        </p>
                        <div class="sport-meta">
                            <span class="sport-pill">8 Teams</span>
                            <span class="sport-pill">12 Slots Each</span>
                            <span class="sport-teams-count">
                                <i class="bi bi-person-fill"></i> Male only
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Volleyball Card -->
            <div class="col-md-6">
                <a class="sport-card d-block" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <div class="sport-card-bg"
                         style="background-image:url('miuvolley.jpeg')">
                    </div>
                    <div class="sport-card-overlay"></div>
                    <div class="sport-card-body">
                        <span class="sport-icon">🏐</span>
                        <div class="sport-name">VOLLEYBALL</div>
                        <p style="color:rgba(255,255,255,0.65);font-size:.88rem;margin-top:.4rem;">
                            Open to all students
                        </p>
                        <div class="sport-meta">
                            <span class="sport-pill">4 Teams</span>
                            <span class="sport-pill">8 Slots Each</span>
                            <span class="sport-teams-count">
                                <i class="bi bi-people-fill"></i> All genders
                            </span>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</section>






<!-- ═══════════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="footer-logo-text">
                    <?php echo SITE_NAME; ?>
                </div>
                <div class="footer-sub">Sports Directorate — Registration Portal</div>
            </div>
            <div class="col-md-6">
                <p class="footer-copy">
                    &copy; <?php echo date('Y'); ?> Mewar International University.<br>
                    All rights reserved. For support, contact the Sports Directorate.
                </p>
            </div>
        </div>
        <div class="footer-divider"></div>
        <p style="font-size:.72rem;color:rgba(255,255,255,.2);text-align:center;margin:0;">
            <i class="bi bi-shield-lock-fill me-1" style="color:var(--miu-gold);opacity:.5;"></i>
            Secure portal — access restricted to registered MIU students
        </p>
    </div>
</footer>


<!-- ═══════════════════════════════════════════════════════════════
     LOGIN MODAL
═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header-custom">
                <button class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>

                <div class="modal-logo-wrap">
                    <div class="modal-logo-circle">
                        <img src="miulogo.jpg"
                             alt="MIU Logo"
                             onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'bi bi-building crest-icon\'></i>'">
                    </div>
                    <div>
                        <div class="modal-uni-name">Mewar International University</div>
                        <div class="modal-dept-tag">Sports Directorate</div>
                    </div>
                </div>

                <h2 class="modal-heading" id="loginModalLabel">PLAYER LOGIN</h2>
                <p class="modal-heading-sub">Enter your Matric number to access the portal</p>
            </div>

            <!-- Modal Body -->
            <div class="modal-body-custom">
                <label for="matricInput" class="matric-label">
                    <i class="bi bi-person-badge" style="color:var(--miu-red);margin-right:.3rem;"></i>
                    Matric Number
                </label>

                <div class="matric-input-wrap">
                    <i class="bi bi-hash input-icon"></i>
                    <input type="text"
                           id="matricInput"
                           class="matric-input"
                           placeholder="e.g. MIUSTD2021008"
                           autocomplete="off"
                           autocorrect="off"
                           spellcheck="false"
                           maxlength="40">
                </div>

                <!-- Error message area -->
                <div class="login-error" id="loginError" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span id="loginErrorText">Matric number not recognised.</span>
                </div>

                <!-- Submit button -->
                <button class="btn-login-submit" id="loginBtn" type="button">
                    <span class="spinner" id="loginSpinner"></span>
                    <i class="bi bi-box-arrow-in-right" id="loginBtnIcon"></i>
                    <span id="loginBtnText">Access Portal</span>
                </button>
            </div>

            <!-- Footer note -->
            <div class="modal-footer-note">
                <i class="bi bi-lock-fill"></i>
                No password required &mdash; Matric number is your key
            </div>

        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    const input      = document.getElementById('matricInput');
    const loginBtn   = document.getElementById('loginBtn');
    const errBox     = document.getElementById('loginError');
    const errText    = document.getElementById('loginErrorText');
    const spinner    = document.getElementById('loginSpinner');
    const btnIcon    = document.getElementById('loginBtnIcon');
    const btnText    = document.getElementById('loginBtnText');

    // Input change handler (no auto-uppercase)
    input.addEventListener('input', function () {
        hideError();
    });

    // Allow Enter key to submit
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') doLogin();
    });

    loginBtn.addEventListener('click', doLogin);

    // Focus input when modal opens
    document.getElementById('loginModal').addEventListener('shown.bs.modal', function () {
        input.focus();
    });

    // Clear state when modal closes
    document.getElementById('loginModal').addEventListener('hidden.bs.modal', function () {
        input.value = '';
        hideError();
        setLoading(false);
    });

    function doLogin() {
        const matric = input.value.trim();
        if (!matric) {
            showError('Please enter your Matric number.');
            input.focus();
            return;
        }

        setLoading(true);
        hideError();

        const body = new URLSearchParams();
        body.append('action', 'login');
        body.append('matric_number', matric);

        fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                // If this is the first login for this player, ask for gender
                if (data.require_gender) {
                    setLoading(false);
                    showGenderChoice(data.redirect);
                } else {
                    // Brief success flash before redirect
                    btnText.textContent = 'Welcome, ' + data.name + '!';
                    btnIcon.className = 'bi bi-check2-circle';
                    loginBtn.style.background = '#1a7a3c';
                    setTimeout(function () {
                        window.location.href = data.redirect;
                    }, 700);
                }
            } else {
                setLoading(false);
                showError(data.message || 'Matric number not recognised. Please check and try again.');
                input.focus();
                input.select();
            }
        })
        .catch(function () {
            setLoading(false);
            showError('A connection error occurred. Please try again.');
        });
    }

    function setLoading(on) {
        loginBtn.disabled = on;
        spinner.style.display = on ? 'block' : 'none';
        btnIcon.style.display = on ? 'none' : 'inline';
        btnText.textContent   = on ? 'Verifying...' : 'Access Portal';
    }

    function showError(msg) {
        errText.textContent = msg;
        errBox.style.display = 'block';
        input.style.borderColor = 'var(--miu-red)';
        input.style.boxShadow = '0 0 0 4px rgba(163,29,36,0.12)';
    }

    function hideError() {
        errBox.style.display = 'none';
        input.style.borderColor = '';
        input.style.boxShadow = '';
    }

    function showGenderChoice(redirectUrl) {
        // Replace modal body with a simple gender choice UI
        const body = document.querySelector('.modal-body-custom');
        if (!body) {
            window.location.href = redirectUrl;
            return;
        }

        body.innerHTML = `
            <h3 style="font-family: 'Bebas Neue', sans-serif; letter-spacing: .12em; font-size: 1.2rem; margin-bottom: .75rem;">
                SELECT YOUR GENDER
            </h3>
            <p style="font-size:.85rem;color:#555;margin-bottom:1rem;">
                This is required once so we can apply the correct eligibility rules
                (Football: male players only · Volleyball: all students).
            </p>
            <div class="d-flex gap-2 mb-2 flex-column">
                <button type="button" class="btn btn-danger w-100" id="genderMaleBtn">
                    <i class="bi bi-gender-male me-1"></i> I am Male
                </button>
                <button type="button" class="btn btn-outline-warning w-100" id="genderFemaleBtn">
                    <i class="bi bi-gender-female me-1"></i> I am Female
                </button>
            </div>
            <div id="genderError" style="display:none;background:#FFF5F5;border:1px solid rgba(163,29,36,0.3);border-left:4px solid #A31D24;border-radius:6px;padding:.6rem .75rem;font-size:.8rem;color:#7B181E;"></div>
        `;

        const genderErr = document.getElementById('genderError');

        function saveGender(value) {
            genderErr.style.display = 'none';
            fetch('update_gender.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'gender=' + encodeURIComponent(value)
            })
            .then(function (res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    window.location.href = redirectUrl;
                } else {
                    genderErr.textContent = data.message || 'Could not save selection. Please try again.';
                    genderErr.style.display = 'block';
                }
            })
            .catch(function () {
                genderErr.textContent = 'A connection error occurred. Please try again.';
                genderErr.style.display = 'block';
            });
        }

        document.getElementById('genderMaleBtn').onclick = function () { saveGender('male'); };
        document.getElementById('genderFemaleBtn').onclick = function () { saveGender('female'); };
    }

})();
</script>

</body>
</html>