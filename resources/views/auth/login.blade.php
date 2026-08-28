<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-sm border-0 p-4" style="max-width:420px;width:100%">
            <h1 class="h3 text-center">Inventory Management</h1>
            <p class="text-secondary text-center">Sign in to continue</p>
            <form method="post" action="{{route('login.store')}}">@csrf<div class="mb-3"><label class="form-label">Username</label><input autofocus name="username" value="{{old('username')}}" autocomplete="username" class="form-control @error('username') is-invalid @enderror" required>@error('username')<div class="invalid-feedback">{{$message}}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" required>@error('password')<div class="invalid-feedback">{{$message}}</div>@enderror</div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Remember me</label></div><button class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </main>
</body>

</html>