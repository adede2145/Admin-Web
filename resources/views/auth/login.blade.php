<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AUTO AUDIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background: #1a1a1a;
            min-height: 100vh;
        }
        body.loaded {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('{{ asset("login-bg.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { box-shadow: 0 8px 32px 0 rgba(0,0,0,0.25), 0 1.5rem 3rem rgba(0,0,0,0.12); border-radius: 1.5rem; overflow: visible; max-width: 1100px; width: 100%; background: none; min-height: 600px; animation: fadeInUp 0.8s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-right { background: #fff; display: flex; align-items: center; justify-content: center; padding: 3.5rem 2.5rem; border-radius: 0 1.5rem 1.5rem 0; }
        .login-left {
            background: linear-gradient(0deg,#560000 0%,#ff2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 600px;
            padding: 0;
            overflow: hidden;
            border-radius: 1.5rem 0 0 1.5rem;
        }
        .login-left img {
            max-width: 150%;
            max-height: 150%;
            width: 150%;
            height: auto;
            object-fit: contain;
            position: absolute;
            left: -25%;
            top: 65%;
            transform: translateY(-50%);
            z-index: 2;
            background: transparent !important;
        }
        .login-right { background: #fff; display: flex; align-items: center; justify-content: center; padding: 3.5rem 2.5rem; }
        .login-form-wrap { width: 100%; max-width: 400px; }
        .login-form .input-group-text { background: #fff; border-right: 0; border-radius: 0.5rem 0 0 0.5rem; }
        .login-form .form-control { border-left: 0; border-radius: 0 0.5rem 0.5rem 0; }
        .login-form .form-control:focus { box-shadow: none; border-color: #d90000; }
        .login-form .btn-login { background: linear-gradient(90deg,#d90000,#560000); color: #fff; font-weight: 600; font-size: 1.1rem; border-radius: .5rem; box-shadow: 0 2px 8px rgba(220,0,0,0.08); padding: .7rem 0; }
        .login-form .btn-login:hover { background: linear-gradient(90deg,#a80000,#560000); }
        .login-form .form-check-label { font-size: .97rem; }
        .login-form .text-danger { color: #d90000 !important; }
        @media (max-width: 767px) {
            .login-left { display: none; }
            .login-card { max-width: 100%; border-radius: 0; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="row login-card w-100">
        <!-- Left: Illustration -->
        <div class="col-md-6 login-left p-0 d-none d-md-flex">
            <img src="/login-illustration.png" alt="Login Illustration" />
        </div>
        <!-- Right: Login Form -->
        <div class="col-md-6 login-right">
            <div class="login-form-wrap">
                <h2 class="fw-bold mb-1" style="font-size:2.1rem;">Welcome Back!</h2>
                <div class="mb-4 text-muted" style="font-size:1.05rem;">Please enter your details below</div>
                @if(session('status'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}" class="login-form" autocomplete="off">
                    @csrf
                    <div class="mb-3 position-relative">
                        <label for="username" class="form-label visually-hidden">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person fs-5 text-secondary"></i></span>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" placeholder="Enter Username" required autofocus autocomplete="username">
                        </div>
                        @error('username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label visually-hidden">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock fs-5 text-secondary"></i></span>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter Password" required autocomplete="current-password">
                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()"><i class="bi bi-eye text-secondary" id="passwordToggleIcon"></i></span>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
                        <label class="form-check-label" for="remember_me">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-login w-100">Sign in</button>
                    @if (Route::has('password.request'))
                        <div class="text-end mt-2">
                            <a class="text-decoration-none text-danger small fw-semibold" href="{{ route('password.request') }}">
                                Forgot your password?
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Prevent gray background flash by preloading the background image
document.addEventListener('DOMContentLoaded', function() {
    const img = new Image();
    img.onload = function() {
        document.body.classList.add('loaded');
    };
    img.src = '{{ asset("login-bg.jpg") }}';
    
    // Fallback: add loaded class after 1 second even if image doesn't load
    setTimeout(function() {
        document.body.classList.add('loaded');
    }, 1000);
});

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash text-secondary';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye text-secondary';
    }
}
</script>
</body>
</html>
