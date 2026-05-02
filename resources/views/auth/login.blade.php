<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZYNQ Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <strong class="h3">ZYNQ</strong>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Sign in to manage POS setup</p>
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input id="remember" name="remember" value="1" type="checkbox" class="form-check-input">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button class="btn btn-primary w-100">Sign in</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
