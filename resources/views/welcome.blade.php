<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | AUTO AUDIT</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Critical: Prevent white flash - loads BEFORE anything else -->
    <style>
        html, body { 
            background-color: #1a1a1a !important;
            margin: 0;
            padding: 0;
        }
    </style>
    
    <!-- DNS Prefetch for faster CDN loading -->
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Preload critical images -->
    <link rel="preload" as="image" href="{{ asset('login-bg.jpg') }}">
    <link rel="preload" as="image" href="{{ asset('login-illustration.png') }}">
    
    <!-- Preload critical CSS -->
    <link rel="preload" as="style" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="preload" as="style" href="{{ asset('css/bootstrap-icons-full.css') }}">
    
    <!-- Critical CSS only - LOCAL FILES -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons-full.css') }}">
    <style>
        body { 
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('{{ asset("login-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .login-container { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 15px;
        }
        .login-card { 
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.25), 0 1.5rem 3rem rgba(0,0,0,0.12); 
            border-radius: 1.5rem; 
            overflow: hidden; 
            max-width: 1100px; 
            width: 100%; 
            background: none; 
            min-height: 600px; 
            animation: fadeInUp 0.8s ease-out; 
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-left {
            background: linear-gradient(0deg,#560000 0%,#ff2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 600px;
            padding: 0;
            overflow: hidden;
        }
        .login-left img {
            width: 140%;
            height: auto;
            object-fit: contain;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            background: transparent !important;
        }
        .login-right { 
            background: #fff; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 3.5rem 2.5rem; 
            min-height: 600px;
        }
        .welcome-content { width: 100%; max-width: 450px; text-align: center; }
        .btn-option { 
            background: linear-gradient(90deg,#d90000,#560000); 
            color: #fff; 
            font-weight: 600; 
            font-size: 1.1rem; 
            border-radius: .5rem; 
            box-shadow: 0 2px 8px rgba(220,0,0,0.08); 
            padding: 1rem 2rem; 
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .btn-option:hover { 
            background: linear-gradient(90deg,#a80000,#560000); 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,0,0,0.15);
            color: #fff;
        }
        .btn-option i {
            font-size: 1.3rem;
        }

        /* Responsive Typography */
        h2.fw-bold { font-size: clamp(1.5rem, 4vw, 2.1rem) !important; }
        .text-muted { font-size: clamp(0.9rem, 2vw, 1.05rem) !important; }

        @media (max-width: 991px) {
            .login-right { padding: 2rem; }
        }

        @media (max-width: 767px) {
            .login-card { 
                max-width: 500px; 
                min-height: auto; 
            }
            .login-right { 
                padding: 2rem 1.5rem; 
                min-height: auto;
            }
            .login-left { display: none; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="row login-card w-100">
        <!-- Left: Illustration -->
        <div class="col-md-6 login-left p-0 d-none d-md-flex">
            <img src="/login-illustration.png" alt="Welcome Illustration" />
        </div>
        <!-- Right: Welcome Options -->
        <div class="col-md-6 login-right">
            <div class="welcome-content">
                <h2 class="fw-bold mb-1">Welcome to AUTO AUDIT</h2>
                <div class="mb-4 text-muted">Please select an option below</div>
                
                <a href="{{ route('login') }}" class="btn btn-option">
                    <i class="bi bi-person-circle"></i>
                    <span>Login as Admin</span>
                </a>
                
                <a href="{{ route('leave.form.download') }}" class="btn btn-option">
                    <i class="bi bi-download"></i>
                    <span>Download Leave Form</span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Defer Bootstrap JS - LOCAL FILE -->
<script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
</body>
</html>
