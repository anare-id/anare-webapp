<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Admin — Anare Clinic</title>
    <link rel="icon" href="{{ asset('images/logo-biru.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/admin.js') }}" defer></script>
</head>
<body class="admin-login">
    @include('admin.icons')
    <main class="login-shell">
        <a class="login-brand" href="{{ route('home') }}" aria-label="Kembali ke website Anare Clinic"><img src="{{ asset('images/logo-biru.png') }}" alt="Anare Clinic" width="1080" height="1080"></a>
        <div class="login-column">
            <section class="login-card" aria-labelledby="login-title">
                <h1 id="login-title">Masuk ke Akun</h1>
                @if(session('admin_message'))<p class="login-message" role="status">{{ session('admin_message') }}</p>@endif
                @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
                <form action="{{ route('admin.authenticate') }}" method="POST" class="login-form">
                    @csrf
                    <label for="username">Username</label>
                    <input id="username" name="username" placeholder="Masukkan username" value="{{ old('username') }}" autocomplete="username" required maxlength="100" @if($errors->has('username')) aria-invalid="true" @endif>
                    <div class="password-label"><label for="password">Kata Sandi</label><button type="button" class="text-link" data-login-help>Lupa kata sandi?</button></div>
                    <div class="password-input"><input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required maxlength="100"><button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false"><svg class="icon"><use href="#a-eye"/></svg></button></div>
                    <button type="submit" class="button button-dark login-submit">Masuk <svg class="icon"><use href="#a-arrow"/></svg></button>
                </form>
            </section>
            <div class="demo-credentials"><span class="demo-label"><span class="status-dot"></span> AKUN DEMO</span><p>Username <button type="button" data-fill-login title="Isi akun demo"><strong>admin</strong></button><span>·</span> Kata sandi <strong>anare123</strong></p><small>Gunakan akun ini untuk menjelajahi tampilan CMS.</small></div>
            <p class="login-help" data-login-help-text role="status" hidden>Ini akun demo. Gunakan username <strong>admin</strong> dan kata sandi <strong>anare123</strong>, atau klik username untuk mengisi form.</p>
        </div>
    </main>
    <footer class="login-footer">ANARE CLINIC <span>Content Management System</span><a href="{{ route('home') }}">Kembali ke website <svg class="icon"><use href="#a-external"/></svg></a></footer>
</body>
</html>
