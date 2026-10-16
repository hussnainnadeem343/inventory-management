<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - DenFin ERP</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', ui-sans-serif, system-ui, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            min-height: 100vh;
        }

        /* Ultra-clean canvas with single non-repeating 3-object illustration (Cashier, Invoice, Finance Chart) */
        .auth-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background-color: #f8fafc;
            background-image: url("{{ asset('images/patterns/minimal-cashier-finance-bg.jpg') }}");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center bottom;
            background-attachment: fixed;
            padding: 24px 32px;
        }

        /* Top Brand Navigation */
        .auth-brand-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            width: fit-content;
        }

        .auth-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            object-fit: cover;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .auth-brand-text {
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .auth-brand-text span {
            color: #2563eb;
        }

        /* Hero Content Container (Spacious Gluon ERP Style) */
        .auth-hero-container {
            max-width: 1160px;
            width: 100%;
            margin: auto;
            padding: 20px 0 40px;
        }

        /* Floating Clean Login Card */
        .auth-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 4px solid rgba(226, 232, 240, 0.95);
            border-radius: 20px;
            box-shadow: 
                0 20px 45px -10px rgba(15, 23, 42, 0.07),
                0 2px 10px rgba(0, 0, 0, 0.02);
            padding: 38px 34px;
            max-width: 440px;
            width: 100%;
            margin-left: auto;
        }

        .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 5px;
        }

        .form-control {
            height: 44px;
            font-size: 13.5px;
            border-color: #cbd5e1;
            border-radius: 9px;
            background-color: #ffffff;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .input-group-text {
            background-color: #ffffff;
            border-color: #cbd5e1;
            border-radius: 9px;
            color: #64748b;
        }

        .toggle-password-btn {
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .toggle-password-btn:hover i {
            color: #2563eb !important;
        }

        .btn-signin {
            height: 44px;
            background: #2563eb;
            border-color: #2563eb;
            font-size: 14px;
            font-weight: 600;
            border-radius: 9px;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.28);
            transition: all 0.18s ease;
        }

        .btn-signin:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.38);
            transform: translateY(-1px);
        }

        /* Left Side Points List (From User Sketch) */
        .points-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 14px;
        }

        .point-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14.5px;
            color: #0f172a;
            font-weight: 600;
        }

        .point-bullet {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .auth-page-footer {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            padding-top: 10px;
        }

        @media (max-width: 991.98px) {
            .auth-page {
                padding: 20px 16px;
                background-image: none;
                background-color: #f8fafc;
            }
            .auth-hero-container {
                padding: 16px 0;
            }
            .auth-login-card {
                margin: 24px auto 0;
                padding: 30px 22px;
            }
            .auth-hero-left {
                text-align: center;
            }
            .points-list {
                max-width: 380px;
                margin: 0 auto;
                text-align: left;
            }
        }
    </style>
</head>
<body>
<div class="auth-page">
    <!-- <header>
        <a href="{{ route('login') }}" class="auth-brand-nav">
            <img src="{{ asset('images/branding/denfin-icon.png') }}" alt="DenFin" class="auth-brand-logo">
            <span class="auth-brand-text">DenFin <span>ERP</span></span>
        </a>
    </header> -->

    <!-- Main Spacious Hero Section -->
    <main class="auth-hero-container">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Side: Welcome Heading & 4 Points (From Sketch) -->
            <div class="col-lg-6 auth-hero-left">
                <div class="mb-4">
                    <span class="fw-semibold fs-5 d-block mb-1" style="color: #475569;">Welcome back to</span>
                    <h1 class="display-5 fw-bold text-dark mb-3" style="letter-spacing: -0.03em;">
                        DenFin <span class="text-primary">ERP</span>
                    </h1>
                    <p class="fs-6 mb-0" style="color: #475569; font-weight: 500; max-width: 460px; line-height: 1.55;">
                        Everything your business needs
                    </p>
                </div>

                <!-- 4 Points (Directly matching user's paper drawing) -->
                <ul class="points-list">
                    <li class="point-item">
                        <span class="point-bullet text-primary" style="background: #e0edff;">
                            <i class="bi bi-boxes"></i>
                        </span>
                        <span>Multi-Branch Inventory & Warehouses</span>
                    </li>
                    <li class="point-item">
                        <span class="point-bullet text-success" style="background: #dcfce7;">
                            <i class="bi bi-cart-check"></i>
                        </span>
                        <span>Enterprise Procurement Pipeline</span>
                    </li>
                    <li class="point-item">
                        <span class="point-bullet text-info" style="color: #0284c7 !important; background: #e0f2fe;">
                            <i class="bi bi-journal-bookmark"></i>
                        </span>
                        <span>Finance & General Ledger</span>
                    </li>
                    <li class="point-item">
                        <span class="point-bullet text-warning" style="color: #d97706 !important; background: #fef3c7;">
                            <i class="bi bi-receipt-cutoff"></i>
                        </span>
                        <span>Point of Sale (POS) & HR</span>
                    </li>
                </ul>
            </div>

            <!-- Right Side: Floating High-Contrast Login Card -->
            <div class="col-lg-6">
                <div class="auth-login-card">
                    <div class="mb-4">
                        <h2 class="h4 fw-bold text-dark mb-1">Sign In</h2>
                        <p class="text-muted small mb-0">Enter your credentials to access your ERP portal</p>
                    </div>

                    <form method="post" action="{{ route('login.store') }}">
                        @csrf

                        <!-- Username Field -->
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input 
                                    id="username" 
                                    autofocus 
                                    name="username" 
                                    value="{{ old('username') }}" 
                                    autocomplete="username" 
                                    class="form-control border-start-0 @error('username') is-invalid @enderror" 
                                    placeholder="Enter your username"
                                    required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Field with Toggle -->
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input 
                                    id="password" 
                                    name="password" 
                                    type="password" 
                                    autocomplete="current-password" 
                                    class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                    placeholder="Enter your password"
                                    required>
                                <button 
                                    type="button" 
                                    class="input-group-text toggle-password-btn" 
                                    id="togglePasswordBtn" 
                                    title="Show or hide password"
                                    tabindex="-1">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label text-secondary small user-select-none" for="remember">
                                    Remember Me
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button class="btn btn-primary btn-signin w-100" type="submit">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <!-- <div class="text-center mt-4 pt-3 border-top text-muted" style="font-size: 11px;">
                        <i class="bi bi-shield-check text-success me-1"></i> Role-Based Access Control &bull; DenFin ERP
                    </div> -->
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="auth-page-footer">
        &copy; {{ date('Y') }} <strong>DenFin ERP</strong> &bull; All rights reserved.
    </footer>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.className = isPassword ? 'bi bi-eye-slash text-primary' : 'bi bi-eye text-secondary';
            });
        }
    });
</script>
</body>
</html>
