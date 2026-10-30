<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DenFin ERP - The All-in-One Cloud ERP for Modern Businesses & Auto Lube Stations</title>
    <meta name="description" content="All-in-one Cloud ERP for modern businesses. Manage Purchase, Sales, High-Speed POS, Multi-Shop Inventory, HR & Payroll, Financial Accounting, and dedicated Auto Lube Station workflows with drum decanting and oil stickers.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.8 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-primary-dark: #1d4ed8;
            --brand-primary-light: #3b82f6;
            --brand-navy: #0f1f3d;
            --brand-midnight: #0b132b;
            --brand-dark: #0a1128;
            --brand-slate: #1e293b;
            --brand-emerald: #10b981;
            --brand-amber: #f59e0b;
            --brand-purple: #8b5cf6;
            --brand-rose: #f43f5e;
            --brand-cyan: #06b6d4;
            --surface-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-body);
            background-color: #f8fafc;
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-heading);
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Top Announcement Bar */
        .top-banner {
            background: linear-gradient(90deg, #0b132b 0%, #1e3a8a 50%, #0b132b 100%);
            color: #e2e8f0;
            font-size: 13px;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .top-banner a {
            color: #93c5fd;
            text-decoration: underline;
            font-weight: 600;
        }

        /* Sticky Glass Navbar */
        .landing-nav {
            position: sticky;
            top: 0;
            z-index: 1040;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            transition: all 0.25s ease;
        }

        .landing-nav .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 21px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }

        .brand-logo-img {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            object-fit: cover;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
        }

        .nav-link {
            font-size: 14.5px;
            font-weight: 600;
            color: #334155 !important;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.18s ease;
        }

        .nav-link:hover {
            color: var(--brand-primary) !important;
            background: rgba(37, 99, 235, 0.06);
        }

        .btn-portal-login {
            background: linear-gradient(135deg, #0f1f3d 0%, #1e3a8a 100%);
            color: #ffffff;
            border: 1px solid #1e3a8a;
            font-size: 13.5px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 4px 14px rgba(15, 31, 61, 0.2);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-portal-login:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
        }

        .btn-demo-cta {
            background-color: var(--brand-primary);
            color: #ffffff;
            border: 1px solid var(--brand-primary);
            font-size: 13.5px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 9px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-demo-cta:hover {
            background-color: var(--brand-primary-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Hero Section with Rich Gradient Depth */
        .hero-section {
            position: relative;
            padding: 70px 0 80px;
            background: 
                radial-gradient(at 10% 20%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
                radial-gradient(at 90% 10%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
                radial-gradient(at 50% 80%, rgba(139, 92, 246, 0.06) 0px, transparent 50%),
                linear-gradient(180deg, #edf4ff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--border-color);
            overflow: hidden;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
            margin-bottom: 22px;
        }

        .hero-badge .ping-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        .hero-title {
            font-size: 50px;
            line-height: 1.15;
            letter-spacing: -0.035em;
            font-weight: 800;
            color: #0b132b;
            margin-bottom: 20px;
        }

        .hero-title span.highlight {
            color: var(--brand-primary);
            position: relative;
            display: inline-block;
        }

        .hero-subtitle {
            font-size: 18px;
            line-height: 1.6;
            color: #475569;
            max-width: 780px;
            margin: 0 auto 34px;
        }

        .hero-btn-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 42px;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-size: 15.5px;
            font-weight: 700;
            padding: 13px 28px;
            border-radius: 11px;
            border: 1px solid #1d4ed8;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
        }

        .btn-hero-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #173db3 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.45);
        }

        .btn-hero-secondary {
            background: #ffffff;
            color: #1e293b;
            font-size: 15.5px;
            font-weight: 600;
            padding: 13px 26px;
            border-radius: 11px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
        }

        .btn-hero-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            transform: translateY(-2px);
        }

        .btn-hero-whatsapp {
            background: #25d366;
            color: #ffffff;
            font-size: 15.5px;
            font-weight: 600;
            padding: 13px 24px;
            border-radius: 11px;
            border: 1px solid #20ba5a;
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.28);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
        }

        .btn-hero-whatsapp:hover {
            background: #1eb857;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.38);
        }

        /* Hero App Mockup Frame */
        .hero-mockup-wrapper {
            position: relative;
            max-width: 1140px;
            margin: 0 auto;
            border-radius: 18px;
            padding: 8px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.3) 0%, rgba(15, 31, 61, 0.25) 100%);
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(226, 232, 240, 0.8);
        }

        .hero-mockup-window {
            background: #0f1f3d;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .mockup-window-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            background: #0a152a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .window-dots {
            display: flex;
            gap: 7px;
        }

        .window-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }

        .window-dot.dot-red { background: #ef4444; }
        .window-dot.dot-yellow { background: #f59e0b; }
        .window-dot.dot-green { background: #10b981; }

        .window-title {
            color: #94a3b8;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            background: rgba(255, 255, 255, 0.06);
            padding: 3px 14px;
            border-radius: 6px;
        }

        .mockup-body {
            background: #f1f5f9;
            padding: 20px;
        }

        /* Mockup Internal UI Preview */
        .preview-kpi-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .preview-kpi-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .preview-kpi-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .preview-kpi-val {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .preview-content-grid {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 16px;
        }

        .preview-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        /* Trust Strip with Rich Indigo / Slate Theme */
        .trust-strip {
            padding: 36px 0;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }

        .trust-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
        }

        .trust-box-blue { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .trust-box-green { background: #ecfdf5; color: #10b981; border: 1px solid #d1fae5; }
        .trust-box-amber { background: #fffbeb; color: #f59e0b; border: 1px solid #fef3c7; }
        .trust-box-purple { background: #f5f3ff; color: #8b5cf6; border: 1px solid #ede9fe; }
        .trust-box-rose { background: #fff1f2; color: #f43f5e; border: 1px solid #ffe4e6; }
        .trust-box-cyan { background: #ecfeff; color: #06b6d4; border: 1px solid #cffafe; }

        /* Section Styling */
        .section-padding {
            padding: 90px 0;
        }

        .section-header {
            max-width: 740px;
            margin: 0 auto 52px;
            text-align: center;
        }

        .section-tag {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--brand-primary);
            background: #eff6ff;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 14px;
            border: 1px solid #dbeafe;
        }

        .section-heading {
            font-size: 38px;
            font-weight: 800;
            line-height: 1.22;
            color: #0f172a;
            margin-bottom: 14px;
        }

        .section-description {
            font-size: 17px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Core Module Cards with Rich Colored Gradient Top Borders */
        .module-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px 24px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .module-card.card-blue::before { background: linear-gradient(90deg, #2563eb, #3b82f6); }
        .module-card.card-green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .module-card.card-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .module-card.card-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .module-card.card-rose::before { background: linear-gradient(90deg, #f43f5e, #fb7185); }
        .module-card.card-cyan::before { background: linear-gradient(90deg, #06b6d4, #22d3ee); }

        .module-card:hover {
            transform: translateY(-5px);
            border-color: #cbd5e1;
            box-shadow: 0 16px 36px -10px rgba(15, 23, 42, 0.1);
        }

        .module-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 24px;
            margin-bottom: 18px;
        }

        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-amber { background: #fffbeb; color: #f59e0b; }
        .icon-purple { background: #f5f3ff; color: #8b5cf6; }
        .icon-cyan { background: #ecfeff; color: #0891b2; }
        .icon-rose { background: #fff1f2; color: #e11d48; }

        .module-card h4 {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .module-card p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .module-checklist {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 13px;
            color: #334155;
            display: grid;
            gap: 8px;
            margin-top: auto;
        }

        .module-checklist li {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .module-checklist li i {
            font-size: 15px;
            flex-shrink: 0;
        }

        /* ============================================================== */
        /* DEDICATED AUTO LUBE STATION SECTION (Vibrant Dark Midnight Navy) */
        /* ============================================================== */
        .lube-station-section {
            background: radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 40%),
                        radial-gradient(circle at 10% 80%, rgba(37, 99, 235, 0.15) 0%, transparent 45%),
                        #0b132b;
            color: #ffffff;
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .lube-station-section h2, .lube-station-section h3, .lube-station-section h4 {
            color: #ffffff;
        }

        .lube-badge {
            background: rgba(245, 158, 11, 0.18);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .lube-feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 26px 22px;
            height: 100%;
            transition: all 0.25s ease;
        }

        .lube-feature-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(245, 158, 11, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35);
        }

        .lube-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .lube-icon-amber { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .lube-icon-green { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .lube-icon-blue { background: rgba(37, 99, 235, 0.18); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.3); }
        .lube-icon-purple { background: rgba(139, 92, 246, 0.18); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3); }

        .lube-showcase-box {
            background: rgba(15, 31, 61, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.4);
        }

        /* Industry Card */
        .industry-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 28px 24px;
            height: 100%;
            transition: all 0.22s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }

        .industry-card:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 12px 28px -5px rgba(37, 99, 235, 0.1);
            transform: translateY(-3px);
        }

        .industry-tag {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 14px;
        }

        .tag-blue { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .tag-green { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        .tag-amber { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
        .tag-purple { background: #f5f3ff; color: #5b21b6; border: 1px solid #ede9fe; }

        /* Interactive Tour Tabs */
        .nav-tour {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 14px;
            padding: 6px;
            display: inline-flex;
            gap: 4px;
            margin-bottom: 35px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-tour .nav-link {
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b !important;
            padding: 9px 18px !important;
            border: none;
            transition: all 0.2s ease;
        }

        .nav-tour .nav-link.active {
            background: var(--brand-primary);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .tab-showcase-card {
            background: #0f1f3d;
            border-radius: 18px;
            padding: 32px;
            color: #ffffff;
            box-shadow: 0 20px 45px -10px rgba(15, 31, 61, 0.25);
        }

        /* Pricing Section */
        .pricing-section {
            background: linear-gradient(180deg, #f8fafc 0%, #edf2f7 100%);
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .pricing-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            padding: 36px 28px;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .pricing-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
        }

        .pricing-card.featured {
            border: 2px solid var(--brand-primary);
            box-shadow: 0 16px 36px -10px rgba(37, 99, 235, 0.2);
        }

        .pricing-popular-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(90deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        .pricing-tier-name {
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .pricing-tier-desc {
            font-size: 13.5px;
            color: #64748b;
            min-height: 40px;
            margin-bottom: 18px;
        }

        .pricing-price {
            font-size: 38px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
            margin-bottom: 22px;
        }

        .pricing-price small {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }

        .pricing-features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 28px;
            display: grid;
            gap: 11px;
            flex-grow: 1;
        }

        .pricing-features-list li {
            font-size: 13.5px;
            color: #334155;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .pricing-features-list li i {
            color: #10b981;
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .btn-pricing-cta {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        /* Demo Request Form */
        .demo-section {
            background: linear-gradient(180deg, #ffffff 0%, #edf2f7 100%);
        }

        .demo-card-form {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.1);
            padding: 40px 34px;
        }

        .form-label-custom {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control-custom, .form-select-custom {
            min-height: 46px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            color: #0f172a;
            background-color: #ffffff;
            transition: all 0.16s ease;
        }

        .form-control-custom:focus, .form-select-custom:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .direct-whatsapp-card {
            background: linear-gradient(135deg, #0b132b 0%, #1e3a8a 100%);
            border-radius: 18px;
            padding: 34px 28px;
            color: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 16px 36px -10px rgba(11, 19, 43, 0.4);
        }

        /* FAQ */
        .faq-accordion .accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px !important;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .faq-accordion .accordion-button {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            padding: 18px 22px;
            background: #ffffff;
        }

        .faq-accordion .accordion-button:not(.collapsed) {
            color: var(--brand-primary);
            background: #f8fafc;
            box-shadow: none;
        }

        .faq-accordion .accordion-body {
            font-size: 14.5px;
            color: #475569;
            line-height: 1.65;
            padding: 20px 22px;
            background: #ffffff;
        }

        /* Footer */
        .landing-footer {
            background: #070d1e;
            color: #94a3b8;
            padding: 70px 0 35px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 14px;
        }

        .landing-footer h5 {
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.01em;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 10px;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.16s ease;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        .footer-bottom {
            padding-top: 35px;
            margin-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 13px;
        }

        /* Floating WhatsApp */
        .floating-wa-btn {
            position: fixed;
            bottom: 26px;
            right: 26px;
            z-index: 1050;
            background: #25d366;
            color: #ffffff;
            border-radius: 50px;
            padding: 10px 18px 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.45);
            text-decoration: none;
            transition: all 0.22s ease;
        }

        .floating-wa-btn:hover {
            background: #1eb857;
            color: #ffffff;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(37, 211, 102, 0.6);
        }

        .floating-wa-icon {
            font-size: 24px;
            line-height: 1;
        }

        @media (max-width: 991.98px) {
            .hero-title { font-size: 38px; }
            .hero-subtitle { font-size: 16.5px; }
            .preview-kpi-bar { grid-template-columns: repeat(2, 1fr); }
            .preview-content-grid { grid-template-columns: 1fr; }
            .demo-card-form { padding: 30px 20px; }
        }

        @media (max-width: 575.98px) {
            .hero-title { font-size: 30px; }
            .section-heading { font-size: 28px; }
            .preview-kpi-bar { grid-template-columns: 1fr; }
            .floating-wa-btn span { display: none; }
            .floating-wa-btn { padding: 14px; border-radius: 50%; }
        }
    </style>
</head>
<body>

    <!-- Top Announcement Bar -->
    <div class="top-banner text-center d-none d-md-block">
        <span>🚀 <strong>DenFin ERP:</strong> All-in-One Cloud System for Purchase, Sales, Barcode POS, Multi-Shop Inventory, HR &amp; Auto Lube Stations!</span>
        <a href="#core-modules" class="ms-2">Explore Modules &rarr;</a>
    </div>

    <!-- Sticky Navigation Bar -->
    <nav class="navbar navbar-expand-lg landing-nav">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="{{ route('landing') }}">
                <img src="{{ asset('images/branding/denfin-icon.png') }}" alt="DenFin" class="brand-logo-img">
                <span>DenFin <span class="text-primary">ERP</span></span>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <!-- Nav Links & CTA Actions -->
            <div class="collapse navbar-collapse" id="landingNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="#core-modules">Core Modules</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#industries">Business Types</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#live-tour">Live Preview</a>
                    </li>
                    {{-- Pricing hidden for now per user request --}}
                    <li class="nav-item">
                        <a class="nav-link" href="#faq">FAQ</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <a href="#demo-section" class="btn-demo-cta d-none d-sm-inline-flex">
                        <i class="bi bi-calendar2-check"></i> Book Demo
                    </a>

                    <!-- User Request: Prominent Login Button pointing to DenFin Portal -->
                    @if(auth()->check())
                        <a href="{{ route('dashboard') }}" class="btn-portal-login">
                            <i class="bi bi-speedometer2 text-primary-light"></i>
                            <span>Go to Dashboard</span>
                            <i class="bi bi-arrow-right small ms-1"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-portal-login" id="headerLoginBtn">
                            <i class="bi bi-box-arrow-in-right text-primary-light"></i>
                            <span>Login to Portal</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section (Universal Business Focus with Rich Gradient) -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="hero-badge">
                <span class="ping-dot"></span>
                <span>Smart &amp; Simple Cloud ERP for Growing Businesses</span>
            </div>

            <h1 class="hero-title">
                The Easy Software to Run Your Entire Business<br>
                <span class="highlight">Purchase • Sales • POS • Inventory • HR</span>
            </h1>

            <p class="hero-subtitle">
                Fast billing, real-time inventory, supplier tracking, and staff payroll in one easy-to-use platform.
            </p>

            <div class="hero-btn-group">
                <a href="#demo-section" class="btn-hero-primary">
                    <i class="bi bi-calendar2-check"></i> Book Free Demo
                </a>
                <a href="#core-modules" class="btn-hero-secondary">
                    <i class="bi bi-grid-3x3-gap"></i> Explore Modules
                </a>
                <a href="https://wa.me/923114670195?text={{ urlencode('Hello DenFin team! I am interested in DenFin ERP for my business.') }}" target="_blank" class="btn-hero-whatsapp">
                    <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                </a>
            </div>

            <!-- Hero Interactive Dashboard Mockup -->
            <div class="hero-mockup-wrapper">
                <div class="hero-mockup-window">
                    <div class="mockup-window-header">
                        <div class="window-dots">
                            <span class="window-dot dot-red"></span>
                            <span class="window-dot dot-yellow"></span>
                            <span class="window-dot dot-green"></span>
                        </div>
                        <div class="window-title">
                            <i class="bi bi-speedometer2 text-primary me-1"></i> denfin-erp.cloud / dashboard
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white font-mono" style="font-size: 11px;">🏢 Multi-Shop: Main Store &amp; Warehouse</span>
                        </div>
                    </div>

                    <div class="mockup-body text-start">
                        <!-- KPI Strip (Core Business Overview) -->
                        <div class="preview-kpi-bar">
                            <div class="preview-kpi-card">
                                <div class="preview-kpi-label">Today's Sales</div>
                                <div class="preview-kpi-val text-primary">Rs. 48,500 <small class="text-success fs-6"><i class="bi bi-arrow-up-right"></i> +14%</small></div>
                            </div>
                            <div class="preview-kpi-card">
                                <div class="preview-kpi-label">Supplier Bills to Pay</div>
                                <div class="preview-kpi-val text-dark font-mono">Rs. 14,200</div>
                            </div>
                            <div class="preview-kpi-card">
                                <div class="preview-kpi-label">Stock on Hand</div>
                                <div class="preview-kpi-val text-success">3,850 Items in Stock</div>
                            </div>
                            <div class="preview-kpi-card">
                                <div class="preview-kpi-label">Staff Present Today</div>
                                <div class="preview-kpi-val text-info">8 Staff on Duty</div>
                            </div>
                        </div>

                        <!-- Split Preview Grid -->
                        <div class="preview-content-grid">
                            <!-- Left: POS & Sales Invoicing Counter -->
                            <div class="preview-card">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary-subtle text-primary p-1 px-2 rounded font-mono fw-bold">POS BILL # INV-2026-9041</span>
                                        <span class="small text-muted">Customer: Walk-in Cash</span>
                                    </div>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-upc-scan me-1"></i> Barcode Ready</span>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 13px;">
                                        <thead>
                                            <tr class="text-muted border-bottom" style="font-size: 11px;">
                                                <th>ITEM / CODE</th>
                                                <th class="text-center">QTY</th>
                                                <th class="text-end">UNIT PRICE</th>
                                                <th class="text-end">SUBTOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <strong>Synthetic Engine Oil 5W-30 (4L)</strong>
                                                    <div class="small text-muted">SKU: OIL-SYN-04L</div>
                                                </td>
                                                <td class="text-center font-mono fw-bold">2 Pcs</td>
                                                <td class="text-end font-mono">Rs. 6,200</td>
                                                <td class="text-end font-mono fw-bold">Rs. 12,400</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <strong>Air Filter (Standard)</strong>
                                                    <div class="small text-muted">SKU: FLT-AF-882</div>
                                                </td>
                                                <td class="text-center font-mono">1 Pc</td>
                                                <td class="text-end font-mono">Rs. 2,150</td>
                                                <td class="text-end font-mono fw-bold">Rs. 2,150</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <strong>Car Wiper Blade Set</strong>
                                                    <div class="small text-muted">SKU: ACC-WP-22</div>
                                                </td>
                                                <td class="text-center font-mono">1 Set</td>
                                                <td class="text-end font-mono">Rs. 1,400</td>
                                                <td class="text-end font-mono fw-bold">Rs. 1,400</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        <i class="bi bi-printer text-primary"></i> Thermal Receipt (80mm) Printed
                                    </div>
                                    <div class="font-mono fs-5 fw-bold text-primary">Total: Rs. 15,950</div>
                                </div>
                            </div>

                            <!-- Right: Purchase Pipeline & Multi-Shop Inventory -->
                            <div class="preview-card bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-check text-primary me-1"></i> Stock Received from Supplier</h6>
                                    <span class="badge bg-success-subtle text-success">Added to Stock</span>
                                </div>

                                <div class="p-3 rounded mb-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span>Supplier Name:</span>
                                        <strong class="text-dark">Atlas Traders Ltd.</strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span>Purchase Order:</span>
                                        <span class="badge bg-secondary font-mono">PO-2026-0819</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-success fw-bold mb-1">
                                        <span>Delivery Status:</span>
                                        <span>Items Verified &amp; Added</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-primary fw-bold">
                                        <span>Branch Stocked:</span>
                                        <span>Main Shop + Warehouse</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-primary-subtle text-primary small fw-semibold">
                                    <span>Vendor Balance to Pay:</span>
                                    <span class="font-mono fs-6 fw-bold">Rs. 42,000</span>
                                </div>

                                <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="bi bi-people text-primary me-1"></i> Cashier on Duty</span>
                                    <span class="badge bg-info-subtle text-info font-mono">Zubair Khan (Cashier 1)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Strip (Richly Colored Icon Boxes) -->
    <div class="trust-strip">
        <div class="container">
            <div class="row g-4 align-items-center justify-content-center">
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-blue">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">Purchase</div>
                            <div class="small text-muted">Buy from Suppliers</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-green">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">Sales</div>
                            <div class="small text-muted">Bills &amp; Invoices</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-amber">
                            <i class="bi bi-upc-scan"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">POS</div>
                            <div class="small text-muted">Fast Barcode Billing</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-purple">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">Inventory</div>
                            <div class="small text-muted">Real-Time Stock</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-rose">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">HR</div>
                            <div class="small text-muted">Staff &amp; Salaries</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="trust-item">
                        <div class="trust-icon-box trust-box-cyan">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold">Accounting</div>
                            <div class="small text-muted">Daily Cash &amp; Profit</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: The Core Business Pillars (Rich Colorful Cards) -->
    <section id="core-modules" class="section-padding">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Core Modules</span>
                <h2 class="section-heading">Everything You Need to Run Your Shop</h2>
                <p class="section-description">
                    Simple tools designed for everyday shopkeeping. No complicated menus or technical training required.
                </p>
            </div>

            <div class="row g-4">
                <!-- 1. Purchase & Procurement -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-blue">
                        <div class="module-icon-wrap icon-blue">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                        <h4>1. Purchases &amp; Suppliers</h4>
                        <p>Order from suppliers, record delivered stock, and track vendor payments.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-primary"></i> Create Purchase Orders &amp; GRN receipts</li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> Automatic stock increase on delivery</li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> Supplier dues ledger &amp; payment tracking</li>
                        </ul>
                    </div>
                </div>

                <!-- 2. Sales & Invoicing -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-green">
                        <div class="module-icon-wrap icon-green">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <h4>2. Sales &amp; Customer Bills</h4>
                        <p>Create customer bills, manage credit (khata) accounts, and print invoices.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Fast retail and wholesale customer billing</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Customer credit (khata) ledger &amp; limits</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Sales returns and payment adjustments</li>
                        </ul>
                    </div>
                </div>

                <!-- 3. High-Speed POS Counter -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-amber">
                        <div class="module-icon-wrap icon-amber">
                            <i class="bi bi-upc-scan"></i>
                        </div>
                        <h4>3. High-Speed POS Counter</h4>
                        <p>Fast counter checkout with barcode scanning, cash drawer, and thermal receipts.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-warning"></i> Barcode scanner support (USB / Wireless)</li>
                            <li><i class="bi bi-check-circle-fill text-warning"></i> Accept Cash, Card, or Customer Credit</li>
                            <li><i class="bi bi-check-circle-fill text-warning"></i> Instant 80mm &amp; 58mm thermal receipt printing</li>
                        </ul>
                    </div>
                </div>

                <!-- 4. Multi-Shop Inventory Management -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-purple">
                        <div class="module-icon-wrap icon-purple">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h4>4. Inventory &amp; Stock Control</h4>
                        <p>Always know your exact stock count, avoid stockouts, and transfer goods.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-purple" style="color: #8b5cf6;"></i> Real-time stock counts across all shops</li>
                            <li><i class="bi bi-check-circle-fill text-purple" style="color: #8b5cf6;"></i> Stock transfers between shops &amp; branches</li>
                            <li><i class="bi bi-check-circle-fill text-purple" style="color: #8b5cf6;"></i> Low stock alerts before items run out</li>
                        </ul>
                    </div>
                </div>

                <!-- 5. HR & Payroll Management -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-rose">
                        <div class="module-icon-wrap icon-rose">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h4>5. Staff &amp; HR Payroll</h4>
                        <p>Record daily staff attendance, advances, and generate monthly salary slips.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-danger"></i> Quick daily attendance (Present / Absent)</li>
                            <li><i class="bi bi-check-circle-fill text-danger"></i> 1-Click monthly salary calculation</li>
                            <li><i class="bi bi-check-circle-fill text-danger"></i> Employee advance loans &amp; auto deductions</li>
                        </ul>
                    </div>
                </div>

                <!-- 6. Finance & Double-Entry Accounting -->
                <div class="col-lg-4 col-md-6">
                    <div class="module-card card-cyan">
                        <div class="module-icon-wrap icon-cyan">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h4>6. Daily Cash &amp; Profit</h4>
                        <p>Track daily shop expenses, cash in hand, and clear profit &amp; loss reports.</p>
                        <ul class="module-checklist">
                            <li><i class="bi bi-check-circle-fill text-info"></i> Daily cash register opening &amp; closing</li>
                            <li><i class="bi bi-check-circle-fill text-info"></i> Track daily shop expenses (rent, bills, tea)</li>
                            <li><i class="bi bi-check-circle-fill text-info"></i> Clear real-time Profit &amp; Loss report</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Adapts to Every Business Type (Industry Solutions) -->
    <section id="industries" class="section-padding">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Universal Flexibility</span>
                <h2 class="section-heading">Built For Your Business Type</h2>
                <p class="section-description">
                    Whether you run retail stores, wholesale trading, auto lube stations, or spare parts shops, DenFin adapts to your operations.
                </p>
            </div>

            <div class="row g-4">
                <!-- Retail Stores & Supermarkets -->
                <div class="col-lg-3 col-md-6">
                    <div class="industry-card">
                        <span class="industry-tag tag-blue">Retail Stores</span>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shop fs-4 text-primary"></i>
                            <h5 class="mb-0 fw-bold">Retail &amp; Marts</h5>
                        </div>
                        <p class="small text-muted mb-3">
                            Fast barcode checkout, customer credit khata, thermal bill printing, and daily cash closing.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li><i class="bi bi-check2 text-primary me-1"></i> Barcode scanner support</li>
                            <li><i class="bi bi-check2 text-primary me-1"></i> Thermal bill printing</li>
                            <li><i class="bi bi-check2 text-primary me-1"></i> Fast item search</li>
                        </ul>
                    </div>
                </div>

                <!-- Wholesale & Trading -->
                <div class="col-lg-3 col-md-6">
                    <div class="industry-card">
                        <span class="industry-tag tag-green">Wholesale &amp; Distribution</span>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-truck fs-4 text-success"></i>
                            <h5 class="mb-0 fw-bold">Wholesale Traders</h5>
                        </div>
                        <p class="small text-muted mb-3">
                            Bulk wholesale pricing, customer balance limits, supplier receipts, and delivery challans.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li><i class="bi bi-check2 text-success me-1"></i> Customer credit limits</li>
                            <li><i class="bi bi-check2 text-success me-1"></i> Bulk carton pricing</li>
                            <li><i class="bi bi-check2 text-success me-1"></i> Delivery challan prints</li>
                        </ul>
                    </div>
                </div>

                <!-- Auto Lube Station & Service Bays -->
                <div class="col-lg-3 col-md-6" id="lube-station">
                    <div class="industry-card">
                        <span class="industry-tag tag-amber">Auto Services</span>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-droplet-half fs-4 text-warning"></i>
                            <h5 class="mb-0 fw-bold">Auto Lube Station</h5>
                        </div>
                        <p class="small text-muted mb-3">
                            Drum Decanting math (track 280L drum to loose liters with shrinkage), 12-point Pit Inspection checklist, and reminder stickers.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li><i class="bi bi-check2 text-warning me-1"></i> Drum Decanting math</li>
                            <li><i class="bi bi-check2 text-warning me-1"></i> Pit Inspection checklist</li>
                            <li><i class="bi bi-check2 text-warning me-1"></i> 50&times;30mm reminder tags</li>
                        </ul>
                    </div>
                </div>

                <!-- Auto Spare Parts & Hardware -->
                <div class="col-lg-3 col-md-6">
                    <div class="industry-card">
                        <span class="industry-tag tag-purple">Spare Parts &amp; Hardware</span>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-wrench-adjustable fs-4 text-purple" style="color: #8b5cf6;"></i>
                            <h5 class="mb-0 fw-bold">Hardware &amp; Parts</h5>
                        </div>
                        <p class="small text-muted mb-3">
                            Part numbers search, piece and box packing units, low-stock warnings, and simple stock tracking.
                        </p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li><i class="bi bi-check2 text-purple me-1" style="color: #8b5cf6;"></i> Part number search</li>
                            <li><i class="bi bi-check2 text-purple me-1" style="color: #8b5cf6;"></i> Multiple units (pcs/box)</li>
                            <li><i class="bi bi-check2 text-purple me-1" style="color: #8b5cf6;"></i> Low stock reorder alert</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Live Preview / Interactive Tour -->
    <section id="live-tour" class="section-padding bg-light">
        <div class="container text-center">
            <div class="section-header">
                <span class="section-tag">Interactive Tour</span>
                <h2 class="section-heading">Explore DenFin ERP In Action</h2>
                <p class="section-description">
                    Take a guided tour through the core interfaces that power daily purchasing, sales, inventory, and staff operations.
                </p>
            </div>

            <!-- Tour Navigation Tabs -->
            <ul class="nav nav-tour justify-content-center" id="tourTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pos-tour-tab" data-bs-toggle="tab" data-bs-target="#pos-tour-pane" type="button" role="tab" aria-controls="pos-tour-pane" aria-selected="true">
                        <i class="bi bi-upc-scan me-1"></i> POS Counter
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="purchase-tour-tab" data-bs-toggle="tab" data-bs-target="#purchase-tour-pane" type="button" role="tab" aria-controls="purchase-tour-pane" aria-selected="false">
                        <i class="bi bi-cart-check me-1"></i> Purchases &amp; Suppliers
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="inventory-tour-tab" data-bs-toggle="tab" data-bs-target="#inventory-tour-pane" type="button" role="tab" aria-controls="inventory-tour-pane" aria-selected="false">
                        <i class="bi bi-boxes me-1"></i> Stock &amp; Inventory
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hr-tour-tab" data-bs-toggle="tab" data-bs-target="#hr-tour-pane" type="button" role="tab" aria-controls="hr-tour-pane" aria-selected="false">
                        <i class="bi bi-people me-1"></i> Staff &amp; Payroll
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finance-tour-tab" data-bs-toggle="tab" data-bs-target="#finance-tour-pane" type="button" role="tab" aria-controls="finance-tour-pane" aria-selected="false">
                        <i class="bi bi-journal-text me-1"></i> Daily Cash &amp; Profit
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lube-tour-tab" data-bs-toggle="tab" data-bs-target="#lube-tour-pane" type="button" role="tab" aria-controls="lube-tour-pane" aria-selected="false">
                        <i class="bi bi-droplet-half me-1"></i> Auto Lube Counter
                    </button>
                </li>
            </ul>

            <!-- Tab Content Panes -->
            <div class="tab-content text-start" id="tourTabContent">
                <!-- Tab 1: POS & Sales Invoicing -->
                <div class="tab-pane fade show active" id="pos-tour-pane" role="tabpanel" aria-labelledby="pos-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-primary-subtle text-primary mb-2">High Speed</span>
                                <h3 class="text-white fw-bold mb-3">Fast POS Counter &amp; Bills</h3>
                                <p style="color: #cbd5e1;">
                                    Fast checkout for walk-in customers. Scan barcodes, pick items, split payments, and print 80mm/58mm thermal receipts.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Barcode scan &amp; automatic stock reduction</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Customer credit balance &amp; payment history</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Daily cash drawer opening &amp; closing balance</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Thermal ESC/POS receipt printing</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-primary fw-bold">Request a Live Walkthrough &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                        <strong class="text-primary"><i class="bi bi-upc-scan me-1"></i> POS Counter Terminal</strong>
                                        <span class="badge bg-success">Counter 1 Active</span>
                                    </div>
                                    <div class="row g-2 mb-2 small">
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <span class="text-muted d-block">Customer</span>
                                                <strong class="text-dark">Walk-in Customer</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <span class="text-muted d-block">Cashier</span>
                                                <strong class="text-primary">Bilal Ahmed (Staff)</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-2 border rounded bg-light mb-2 font-mono small">
                                        <div class="d-flex justify-content-between">
                                            <span>Premium Motor Oil (4L Bottle) &times; 2</span>
                                            <strong>Rs. 11,200</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted">
                                            <span>Universal Cabin Air Filter &times; 1</span>
                                            <strong>Rs. 1,650</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted">
                                            <span>Labor / Service &times; 1</span>
                                            <strong>Rs. 500</strong>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between text-primary fw-bold fs-6">
                                            <span>Net Total Payable:</span>
                                            <span>Rs. 13,350</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-printer me-1"></i> Print Receipt (80mm)</button>
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-cash me-1"></i> Cash Tendered</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Purchase & Procurement -->
                <div class="tab-pane fade" id="purchase-tour-pane" role="tabpanel" aria-labelledby="purchase-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-warning-subtle text-warning mb-2">Zero Stock Loss</span>
                                <h3 class="text-white fw-bold mb-3">Purchases &amp; Supplier Dues</h3>
                                <p style="color: #cbd5e1;">
                                    Keep clean records of everything you buy. Confirm received stock (GRN) and always know how much money you owe your vendors.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Quick purchase orders &amp; received goods check (GRN)</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Stock increases automatically upon delivery</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Supplier dues ledger with pending bill alerts</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Accurate average cost of your inventory</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-warning text-dark fw-bold">See Procurement Flow &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                        <strong class="text-dark"><i class="bi bi-cart-check-fill text-warning me-1"></i> Goods Receipt Note (GRN-2026-118)</strong>
                                        <span class="badge bg-success">Received &amp; Stocked</span>
                                    </div>
                                    <div class="row g-2 mb-3 font-mono small">
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block">Supplier</small>
                                                <strong class="text-dark">Universal Auto Distributors</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <small class="text-muted d-block">Total Invoice Value</small>
                                                <strong class="text-primary">Rs. 385,000</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-2 border rounded mb-2 font-mono small bg-light">
                                        <div class="d-flex justify-content-between">
                                            <span>Verified Items Received:</span>
                                            <strong>24 Cartons (144 Units)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-success">
                                            <span>Destination Warehouse:</span>
                                            <strong>Central Hub Warehouse</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-primary fw-bold">
                                            <span>Vendor Payable Added:</span>
                                            <strong>Rs. 385,000 (Due in 30 Days)</strong>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success p-2"><i class="bi bi-shield-check me-1"></i> Inventory Ledger Balanced</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Multi-Shop Inventory -->
                <div class="tab-pane fade" id="inventory-tour-pane" role="tabpanel" aria-labelledby="inventory-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-info-subtle text-info mb-2">Multi-Shop Setup</span>
                                <h3 class="text-white fw-bold mb-3">Multi-Shop Stock Control</h3>
                                <p style="color: #cbd5e1;">
                                    Run Shop A, Shop B, or multiple locations without mix-ups. Each shop keeps its own inventory, prices, and staff.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Shop A &amp; Shop B have separate products &amp; prices</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Easy stock transfers between branches</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Low stock alerts before you run out</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Combined owner dashboard showing total stock &amp; profit</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-info text-white fw-bold">Explore Multi-Shop ERP &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                        <strong class="text-dark"><i class="bi bi-buildings text-info me-1"></i> Branch Inventory Balance Comparison</strong>
                                        <span class="badge bg-primary">Consolidated View</span>
                                    </div>
                                    <div class="row g-2 mb-3 text-center font-mono">
                                        <div class="col-6">
                                            <div class="p-3 border rounded bg-light">
                                                <div class="badge bg-primary mb-2">Shop A (Branch 1)</div>
                                                <div class="text-muted small">Items: <strong>1,420 SKUs</strong></div>
                                                <div class="text-muted small">Stock Value: <strong>Rs. 3.2M</strong></div>
                                                <div class="text-success small fw-bold mt-1">Status: Normal</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 border rounded bg-light">
                                                <div class="badge bg-secondary mb-2">Shop B (Branch 2)</div>
                                                <div class="text-muted small">Items: <strong>1,890 SKUs</strong></div>
                                                <div class="text-muted small">Stock Value: <strong>Rs. 4.6M</strong></div>
                                                <div class="text-success small fw-bold mt-1">Status: Normal</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-2 border rounded bg-light small text-center text-muted">
                                        <i class="bi bi-shield-check text-success me-1"></i> Role-Based Boundaries: Shop A staff cannot access Shop B financials.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: HR & Payroll -->
                <div class="tab-pane fade" id="hr-tour-pane" role="tabpanel" aria-labelledby="hr-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-danger-subtle text-danger mb-2">Staff &amp; Payroll</span>
                                <h3 class="text-white fw-bold mb-3">Staff Attendance &amp; Monthly Salaries</h3>
                                <p style="color: #cbd5e1;">
                                    Stop using paper diaries or messy spreadsheets. Record daily attendance, track salary advance loans, and generate payslips in one click.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Employee profiles &amp; emergency phone contacts</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 1-click attendance (Present, Absent, Leave)</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Automatic monthly salary calculation</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Advance salary loans deducted automatically</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-danger fw-bold">Explore HR &amp; Payroll &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                        <strong class="text-dark"><i class="bi bi-people-fill text-danger me-1"></i> Monthly Payroll Sheet (October 2026)</strong>
                                        <span class="badge bg-success">18 Staff Active</span>
                                    </div>
                                    <div class="p-2 border rounded mb-2 font-mono small bg-light">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Tariq Mehmood (POS Manager):</span>
                                            <strong class="text-dark">Rs. 65,000 (Net)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 text-muted">
                                            <span>Asim Riaz (Inventory Officer):</span>
                                            <strong class="text-dark">Rs. 48,000 (Net)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 text-muted">
                                            <span>Rashid Ali (Cashier - Shop A):</span>
                                            <strong class="text-dark">Rs. 38,000 (Net)</strong>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between text-danger fw-bold fs-6">
                                            <span>Total Payroll Disbursement:</span>
                                            <span>Rs. 742,000</span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center small text-muted pt-1">
                                        <span><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Salary Slips Generated</span>
                                        <span class="badge bg-secondary">Bank Transfer Sheet Ready</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 6: Financial Accounting -->
                <div class="tab-pane fade" id="finance-tour-pane" role="tabpanel" aria-labelledby="finance-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-success-subtle text-success mb-2">Accounting</span>
                                <h3 class="text-white fw-bold mb-3">Daily Cash, Expenses &amp; Profit</h3>
                                <p style="color: #cbd5e1;">
                                    Every sale, purchase, expense, and salary automatically updates your accounts. Know your true profit at the end of every day without hiring an accountant.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Real-time Profit &amp; Loss (P&amp;L) statement</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Cash in hand and bank balance tracking</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Daily shop expenses (bills, tea, fuel, rent)</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Clean, simple reports for business owners</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-success fw-bold">View Financial Reports &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                                        <strong class="text-dark"><i class="bi bi-journal-check text-success me-1"></i> Profit &amp; Loss Statement (Current Month)</strong>
                                        <span class="badge bg-success">Real-Time</span>
                                    </div>
                                    <div class="p-2 border rounded mb-2 font-mono small bg-light">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Total Sales Revenue:</span>
                                            <strong class="text-success">Rs. 4,850,200</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 text-muted">
                                            <span>Cost of Goods Sold (COGS):</span>
                                            <span>(Rs. 3,120,000)</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 fw-bold text-dark">
                                            <span>Gross Profit:</span>
                                            <span>Rs. 1,730,200 (35.6%)</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 text-muted">
                                            <span>Operating Expenses &amp; Payroll:</span>
                                            <span>(Rs. 890,000)</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between text-primary fw-bold fs-6">
                                            <span>Net Operating Profit:</span>
                                            <span>Rs. 840,200</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary-subtle text-primary p-2"><i class="bi bi-check2-all me-1"></i> Fully Audited Double-Entry Balance</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 6: Auto Lube Counter -->
                <div class="tab-pane fade" id="lube-tour-pane" role="tabpanel" aria-labelledby="lube-tour-tab" tabindex="0">
                    <div class="tab-showcase-card">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-5">
                                <span class="badge bg-warning text-dark mb-2">Automotive Lube Bay</span>
                                <h3 class="text-white fw-bold mb-3">Auto Lube Bay &amp; Pit Inspection</h3>
                                <p style="color: #cbd5e1;">
                                    Enter car plate, choose loose liters or packaged oil, add filters, and print windscreen reminder stickers in one easy screen.
                                </p>
                                <ul class="list-unstyled mb-4" style="color: #94a3b8; font-size: 14px;">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Simple choice for Packaged vs Loose Liters</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 1-Click Pit Checklist (Pass / Warning / Fail)</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Auto-calculates next oil service (+5,000 km)</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Prints customer bill and windscreen reminder sticker</li>
                                </ul>
                                <a href="#demo-section" class="btn btn-warning text-dark fw-bold">Request Lube Station Demo &rarr;</a>
                            </div>
                            <div class="col-lg-7">
                                <div class="bg-white rounded-3 p-3 text-dark shadow">
                                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                        <strong class="text-dark"><i class="bi bi-droplet-half text-warning me-1"></i> Lube POS Terminal</strong>
                                        <span class="badge bg-success">Pit Bay 1 Active</span>
                                    </div>
                                    <div class="row g-2 mb-2 small">
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <span class="text-muted d-block">Vehicle Plate</span>
                                                <strong class="font-mono text-dark">LEA-22-9481 (Altis 1.6)</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 border rounded bg-light">
                                                <span class="text-muted d-block">Current Odometer</span>
                                                <strong class="font-mono text-primary">82,400 KM</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-2 border rounded bg-light mb-2 font-mono small">
                                        <div class="d-flex justify-content-between">
                                            <span>Total Quartz 9000 5W-30 (Loose) &times; 3.8L</span>
                                            <strong>Rs. 5,510</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted">
                                            <span>Genuine Oil Filter &times; 1</span>
                                            <strong>Rs. 1,450</strong>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted">
                                            <span>Pit Inspection &amp; Labor Svc &times; 1</span>
                                            <strong>Rs. 500</strong>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between text-primary fw-bold fs-6">
                                            <span>Net Total Payable:</span>
                                            <span>Rs. 7,460</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-printer me-1"></i> Thermal Bill</button>
                                        <button class="btn btn-sm btn-warning text-dark fw-bold"><i class="bi bi-tag me-1"></i> Thermal Sticker</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Section 4: Transparent Pricing Plans (Hidden for now as requested) --}}
    @if(false)
    <section id="pricing" class="pricing-section section-padding">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Simple &amp; Scalable</span>
                <h2 class="section-heading">Flexible Pricing for Every Business Size</h2>
                <p class="section-description">
                    Transparent, value-packed plans that scale as your shops, counters, and employees grow.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Plan 1: Starter Retail POS -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card">
                        <div class="pricing-tier-name">Starter POS &amp; Inventory</div>
                        <div class="pricing-tier-desc">Ideal for single retail counters, auto spare parts shops, and growing retail stores.</div>
                        <div class="pricing-price">Rs. 4,999 <small>/ month</small></div>

                        <ul class="pricing-features-list">
                            <li><i class="bi bi-check-circle-fill"></i> 1 Location / Shop Counter</li>
                            <li><i class="bi bi-check-circle-fill"></i> High-Speed Barcode POS Counter</li>
                            <li><i class="bi bi-check-circle-fill"></i> Sales Invoicing &amp; Customer Ledger</li>
                            <li><i class="bi bi-check-circle-fill"></i> Basic Purchase &amp; Stock Ledger</li>
                            <li><i class="bi bi-check-circle-fill"></i> Thermal Receipt Printing (80mm &amp; 58mm)</li>
                            <li><i class="bi bi-check-circle-fill"></i> Daily Cash Register Closing</li>
                            <li class="text-muted"><i class="bi bi-dash text-secondary"></i> Multi-Shop Branch Isolation</li>
                            <li class="text-muted"><i class="bi bi-dash text-secondary"></i> Full HR &amp; Employee Payroll</li>
                        </ul>

                        <a href="#demo-section" class="btn btn-outline-dark btn-pricing-cta" onclick="selectPlan('Starter POS & Inventory')">
                            Choose Starter Plan
                        </a>
                    </div>
                </div>

                <!-- Plan 2: Business Pro (Most Popular) -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card featured">
                        <span class="pricing-popular-badge"><i class="bi bi-star-fill me-1"></i> Most Popular</span>
                        <div class="pricing-tier-name text-primary">Business Standard</div>
                        <div class="pricing-tier-desc">The complete operating system: Purchase, Sales, POS, Stock, and HR Payroll.</div>
                        <div class="pricing-price text-primary">Rs. 9,999 <small class="text-muted">/ month</small></div>

                        <ul class="pricing-features-list">
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>Everything in Starter Plan</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>Complete Procurement (PO &rarr; GRN &rarr; Vendor Dues)</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>HR &amp; Staff Attendance + Monthly Payroll</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>Multi-Warehouse Stock Balances &amp; Transfers</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>Customer Credit Accounts &amp; Due Limits</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> <strong>Full Auto Lube Station Module Included</strong></li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> Multiple Cashier &amp; Staff User Roles</li>
                            <li><i class="bi bi-check-circle-fill text-primary"></i> Priority WhatsApp &amp; Technical Support</li>
                        </ul>

                        <a href="#demo-section" class="btn btn-primary btn-pricing-cta" onclick="selectPlan('Business Standard')">
                            Book Business Standard Demo
                        </a>
                    </div>
                </div>

                <!-- Plan 3: Multi-Branch Enterprise -->
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card">
                        <div class="pricing-tier-name">Multi-Branch Enterprise</div>
                        <div class="pricing-tier-desc">For chains, franchises, central warehouses, and advanced double-entry accounting.</div>
                        <div class="pricing-price">Rs. 18,999 <small>/ month</small></div>

                        <ul class="pricing-features-list">
                            <li><i class="bi bi-check-circle-fill"></i> <strong>Everything in Business Standard</strong></li>
                            <li><i class="bi bi-check-circle-fill"></i> Multiple Isolated Shops (Shop A, Shop B, Branches)</li>
                            <li><i class="bi bi-check-circle-fill"></i> Central Warehouse Logistics &amp; Transfers</li>
                            <li><i class="bi bi-check-circle-fill"></i> Full Double-Entry Accounting &amp; General Ledger</li>
                            <li><i class="bi bi-check-circle-fill"></i> Chart of Accounts, P&amp;L and Balance Sheet</li>
                            <li><i class="bi bi-check-circle-fill"></i> Multi-Shop Scoped Categories &amp; Pricing</li>
                            <li><i class="bi bi-check-circle-fill"></i> Unlimited Staff Users &amp; Granular Permissions</li>
                            <li><i class="bi bi-check-circle-fill"></i> Dedicated Account Manager &amp; On-Site Training</li>
                        </ul>

                        <a href="#demo-section" class="btn btn-outline-dark btn-pricing-cta" onclick="selectPlan('Multi-Branch Enterprise')">
                            Request Enterprise Proposal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Section 5: Demo Request Booking & WhatsApp Lead Capture -->
    <section id="demo-section" class="section-padding demo-section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Free Live Demo</span>
                <h2 class="section-heading">Try DenFin ERP Free For Your Business</h2>
                <p class="section-description">
                    See how easily DenFin fits your shop — whether retail store, oil station, wholesale, or multi-branch business.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Demo Request Form -->
                <div class="col-lg-8">
                    <div class="demo-card-form">
                        <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                            <div>
                                <h4 class="mb-1 text-dark fw-bold">Book a Free 1-on-1 Demo</h4>
                                <small class="text-muted">Free demo, no commitment. We will show you the exact screens your staff will use.</small>
                            </div>
                            <span class="badge bg-success-subtle text-success p-2 px-3 rounded-pill fw-semibold">
                                <i class="bi bi-shield-check me-1"></i> Quick Response
                            </span>
                        </div>

                        <!-- Flash Alerts -->
                        @if(session('demo_success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div>
                                    <strong>{{ session('demo_success') }}</strong>
                                    @if(session('whatsapp_url'))
                                        <div class="mt-2">
                                            <a href="{{ session('whatsapp_url') }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                                                <i class="bi bi-whatsapp me-1"></i> Open Direct WhatsApp Chat With Us &rarr;
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('demo.request') }}" method="POST" id="demoRequestForm">
                            @csrf

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-custom" placeholder="e.g. Tariq Mehmood" required value="{{ old('name') }}">
                                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label-custom">Business / Company Name <span class="text-danger">*</span></label>
                                    <input type="text" name="business_name" class="form-control form-control-custom" placeholder="e.g. Al-Madina Traders / Auto Care" required value="{{ old('business_name') }}">
                                    @error('business_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom">Business Type</label>
                                    <select name="business_type" id="businessTypeSelect" class="form-select form-select-custom">
                                        <option value="Retail Store & Supermarket">Retail Store &amp; Supermarket</option>
                                        <option value="Auto Lube Station">Auto Lube Station &amp; Service Bay</option>
                                        <option value="Wholesale & Distribution">Wholesale &amp; Distribution</option>
                                        <option value="Auto Spare Parts & Hardware">Auto Spare Parts &amp; Hardware</option>
                                        <option value="Multi-Branch Chain">Multi-Branch Chain / Franchise</option>
                                        <option value="General Enterprise">General Trading &amp; Commerce</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label-custom">WhatsApp / Mobile Number <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control form-control-custom font-mono" placeholder="e.g. 0300-1234567" required value="{{ old('phone') }}">
                                    @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom">City</label>
                                    <input type="text" name="city" class="form-control form-control-custom" placeholder="e.g. Lahore, Karachi, Islamabad" value="{{ old('city') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label-custom">Interested Plan</label>
                                    <select name="preferred_plan" id="preferredPlanSelect" class="form-select form-select-custom">
                                        <option value="Business Standard">Business Standard (Recommended)</option>
                                        <option value="Starter POS & Inventory">Starter POS &amp; Inventory</option>
                                        <option value="Multi-Branch Enterprise">Multi-Branch Enterprise</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label-custom">What are your primary business requirements? (Optional)</label>
                                <textarea name="message" class="form-control form-control-custom" rows="3" placeholder="Tell us about your needs (e.g. purchase orders, fast barcode POS, multi-shop stock, auto lube drum decanting, staff payroll)...">{{ old('message') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-6 shadow-sm">
                                <i class="bi bi-calendar2-check-fill me-2"></i> Submit Demo Request &amp; Schedule Session
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Direct WhatsApp Contact Box -->
                <div class="col-lg-4">
                    <div class="direct-whatsapp-card">
                        <div>
                            <span class="badge bg-success text-white mb-3 px-3 py-1 rounded-pill">
                                <i class="bi bi-lightning-charge-fill me-1"></i> Quick WhatsApp
                            </span>
                            <h4 class="text-white fw-bold mb-3">Want Direct Answers?</h4>
                            <p class="text-slate-200" style="color: #cbd5e1; font-size: 14.5px;">
                                Chat directly with our team on WhatsApp. We can answer questions about barcode scanners, thermal printers, and how DenFin works for your shop.
                            </p>

                            <div class="p-3 rounded-3 bg-white bg-opacity-10 mb-4 border border-white border-opacity-10">
                                <div class="small text-slate-300" style="color: #cbd5e1;">Available Hours:</div>
                                <div class="fw-bold text-white fs-6">Monday – Saturday: 9:00 AM – 9:00 PM (PKT)</div>
                            </div>
                        </div>

                        <div>
                            <a href="https://wa.me/923114670195?text={{ urlencode('Hello DenFin team! I want to consult regarding the ERP system for my business.') }}" target="_blank" class="btn btn-success w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow">
                                <i class="bi bi-whatsapp fs-5"></i> Chat With Us on WhatsApp
                            </a>

                            <!-- Portal Login reminder link for existing clients -->
                            <div class="text-center mt-3 pt-3 border-top border-white border-opacity-10">
                                <small class="text-slate-300" style="color: #cbd5e1;">Already a client?</small>
                                <a href="{{ route('login') }}" class="text-white fw-bold text-decoration-underline ms-1">
                                    Login to your DenFin Portal &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: FAQ Accordion -->
    <section id="faq" class="section-padding bg-light">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Common Questions</span>
                <h2 class="section-heading">Frequently Asked Questions</h2>
                <p class="section-description">
                    Simple answers to everyday questions about running your business on DenFin ERP.
                </p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion faq-accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Can DenFin ERP manage Purchase, Sales, POS, Inventory, and HR in one system?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! Everything connects together in one place. When you buy goods, stock increases; when you sell at the POS counter, stock reduces and cash increases. Staff attendance, supplier dues, and daily profit are all handled together.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Does DenFin also support specialized businesses like Auto Lube Stations?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! For oil and service centers, DenFin includes built-in Auto Lube Station features: bulk Drum Decanting (tracking oil shrinkage when opening a 280L drum), 12-point Pit Inspection checklists, and thermal reminder sticker printing.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How does Multi-Shop Isolation work between Shop A and Shop B?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Each branch has its own isolated stock and prices. Shop A and Shop B can each have categories like "Oil" or "Filters" without database conflicts. Staff users are restricted to their assigned shop, while business owners have a full bird's-eye view.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What hardware, barcode scanners, and printers are supported?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    DenFin ERP works with any standard USB or wireless barcode scanner gun, standard 80mm and 58mm thermal receipt printers, 50&times;30mm sticker printers for Pit Inspection windscreen tags, and standard office laser printers.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    How does the HR &amp; Payroll module calculate employee salaries?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can set employee salaries, mark daily attendance, and record cash advances given during the month. At month-end, click one button to generate payslips with advance loans deducted automatically.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset('images/branding/denfin-icon.png') }}" alt="DenFin" class="brand-logo-img">
                        <span class="fs-5 fw-bold text-white">DenFin <span class="text-primary">ERP</span></span>
                    </div>
                    <p class="small text-slate-400" style="color: #94a3b8; max-width: 320px;">
                        The All-in-One Cloud ERP for Retail, Wholesale, Auto Services, and Growing Businesses.
                    </p>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">
                            <i class="bi bi-circle-fill text-success" style="font-size: 8px;"></i> All Systems Operational
                        </span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Core Modules</h5>
                    <ul class="footer-links">
                        <li><a href="#core-modules">Purchase &amp; GRN</a></li>
                        <li><a href="#core-modules">Sales Invoicing</a></li>
                        <li><a href="#core-modules">Barcode POS</a></li>
                        <li><a href="#core-modules">Multi-Shop Inventory</a></li>
                        <li><a href="#core-modules">HR &amp; Payroll</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Business Types</h5>
                    <ul class="footer-links">
                        <li><a href="#industries">Retail &amp; Marts</a></li>
                        <li><a href="#industries">Wholesale Traders</a></li>
                        <li><a href="#lube-station">Auto Lube Station</a></li>
                        <li><a href="#industries">Hardware &amp; Parts</a></li>
                        <li><a href="#demo-section">Book Free Demo</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h5>Portal Access</h5>
                    <p class="small" style="color: #94a3b8;">
                        Staff, Shop Admins, and Super Administrators can access the system dashboard here:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        @if(auth()->check())
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm text-start py-2 px-3 fw-bold">
                                <i class="bi bi-speedometer2 text-primary me-2"></i> Go to ERP Dashboard &rarr;
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm text-start py-2 px-3 fw-bold">
                                <i class="bi bi-box-arrow-in-right text-primary me-2"></i> Login to DenFin Portal &rarr;
                            </a>
                        @endif
                        <a href="https://wa.me/923114670195" target="_blank" class="btn btn-outline-success btn-sm text-start py-2 px-3 fw-bold">
                            <i class="bi bi-whatsapp me-2"></i> Technical Support Hotline
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div>
                    &copy; {{ date('Y') }} DenFin ERP. All rights reserved.
                </div>
                <div class="d-flex gap-3 text-muted small">
                    <span>Privacy Policy</span>
                    <span>&bull;</span>
                    <span>Terms of Service</span>
                    <span>&bull;</span>
                    <span>Security</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Action Widget -->
    <a href="https://wa.me/923114670195?text={{ urlencode('Hello DenFin team! I am interested in getting a demo of DenFin ERP for my business.') }}" target="_blank" class="floating-wa-btn" id="floatingWhatsAppBtn" title="Chat with us on WhatsApp">
        <i class="bi bi-whatsapp floating-wa-icon"></i>
        <span>Chat on WhatsApp</span>
    </a>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth Plan Selector helper
        function selectPlan(planName) {
            const planSelect = document.getElementById('preferredPlanSelect');
            if (planSelect) {
                planSelect.value = planName;
            }
            const demoSection = document.getElementById('demo-section');
            if (demoSection) {
                demoSection.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
