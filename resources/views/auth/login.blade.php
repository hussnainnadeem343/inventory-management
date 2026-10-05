<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ERP Inventory Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background-color: #0b1528;
            margin: 0;
            overflow-x: hidden;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 40px 20px;
            background: radial-gradient(circle at 50% 20%, #15274d 0%, #0d1a33 55%, #070e1c 100%);
            overflow: hidden;
        }

        /* Ambient glowing flares on dark canvas */
        .dark-ambient-flare-1 {
            position: absolute;
            top: -100px;
            left: -100px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.28) 0%, rgba(30, 64, 175, 0.1) 50%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
        }

        .dark-ambient-flare-2 {
            position: absolute;
            bottom: -100px;
            right: -100px;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.22) 0%, rgba(37, 99, 235, 0.08) 50%, transparent 70%);
            filter: blur(90px);
            pointer-events: none;
        }

        /* Subtle dark grid texture */
        .auth-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* Brand Header above card */
        .brand-header {
            position: relative;
            z-index: 2;
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-icon-box {
            width: 56px;
            height: 56px;
            display: inline-grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-size: 26px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
            margin-bottom: 12px;
        }

        .brand-title {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .brand-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.18);
            color: #93c5fd;
            border: 1px solid rgba(147, 197, 253, 0.25);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 6px;
        }

        /* Crisp High-Contrast White Login Card */
        .auth-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 20px !important;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
            padding: 38px 34px;
        }

        .form-label {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-control {
            height: 46px;
            border-color: #cbd5e1;
            font-size: 13.5px;
            color: #0f172a;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .input-group-text {
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }

        .form-control:focus + .toggle-password-btn {
            border-color: #3b82f6;
        }

        .toggle-password-btn {
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .toggle-password-btn:hover i {
            color: #2563eb !important;
        }

        .btn-signin {
            height: 46px;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14.5px;
            letter-spacing: 0.01em;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
        }

        .btn-signin:hover {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
        }

        .auth-footer-text {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 24px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
<main class="auth-wrapper">
    <!-- Ambient dark background glowing effects -->
    <div class="dark-ambient-flare-1"></div>
    <div class="dark-ambient-flare-2"></div>

    <!-- Header Branding -->
    <div class="brand-header">
        <div class="brand-icon-box">
            <i class="bi bi-boxes"></i>
        </div>
        <h1 class="brand-title">Inventory & ERP System</h1>
        <span class="brand-badge">Secure Management Portal</span>
    </div>

    <!-- High-Contrast Card -->
    <section class="card auth-card" aria-labelledby="login-title">
        <div class="mb-4">
            <h2 class="h5 fw-bold text-dark mb-1" id="login-title">Sign In to Your Account</h2>
            <p class="text-secondary small mb-0">Enter your credentials to access the ERP dashboard</p>
        </div>

        <form method="post" action="{{ route('login.store') }}">
            @csrf

            <!-- Username Field -->
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary border-end-0">
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

            <!-- Password Field with Visibility Toggle -->
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text text-secondary border-end-0">
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
                        class="input-group-text border-start-0 text-secondary toggle-password-btn" 
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label text-secondary small" for="remember">Remember me</label>
                </div>
            </div>

            <button class="btn btn-primary btn-signin w-100" type="submit">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <span class="text-secondary small">
                <i class="bi bi-shield-lock-fill text-primary me-1"></i> Protected by Role-Based Access Control
            </span>
        </div>
    </section>

    <!-- Footer Copyright on Dark Canvas -->
    <div class="auth-footer-text">
        &copy; {{ date('Y') }} ERP Inventory Management System. All rights reserved.
    </div>
</main>

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
