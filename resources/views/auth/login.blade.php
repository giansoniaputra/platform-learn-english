<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Masuk — Sepuluh</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Space+Mono:wght@400;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>

<body>
  <div class="phone">
    <form class="login-card" method="POST" action="{{ route('login') }}">
      @csrf

      <div class="login-head">
        <div class="login-icon">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
          </svg>
        </div>
        <div class="brandmark" style="justify-content:center"><span></span>Sepuluh</div>
        <h1>Masuk ke akunmu</h1>
        <p>Lanjutkan kosakata dan latihan percakapan harianmu.</p>
      </div>

      <div>
        @if ($errors->any())
          <p class="form-error" style="margin-bottom:14px">{{ $errors->first() }}</p>
        @endif

        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
            placeholder="nama@email.com" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="field password-field">
          <label for="password">Kata sandi</label>
          <input id="password" name="password" type="password" autocomplete="current-password"
            placeholder="••••••••" required>
          <button class="password-toggle" type="button" id="toggle-password" aria-label="Tampilkan kata sandi">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>

        <label class="checkbox-row" style="margin-bottom:22px">
          <input class="checkbox-input" type="checkbox" name="remember" value="1">
          <span class="checkbox-box"></span>
          Ingat saya
        </label>

        <button class="btn" type="submit">Masuk</button>
      </div>
    </form>
  </div>

  <script>
    const pwd = document.getElementById("password");
    document.getElementById("toggle-password").addEventListener("click", () => {
      pwd.type = pwd.type === "password" ? "text" : "password";
    });
  </script>
</body>

</html>
