<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Восстановление пароля</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/typography.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/form-submit.css">
  </head>
  <body class="auth-page auth-page--login">
    <main class="login-layout">
      <section class="login-hero" aria-label="Информационный блок">
        <div class="hero-content">
          <div class="hero-logo" aria-hidden="true">🔐</div>
          <h1 class="hero-title">FinanceTracker</h1>
          <p class="hero-subtitle">Восстановление доступа к аккаунту</p>
        </div>
      </section>

      <section class="login-side">
        <article class="login-card">
          <h2 class="card-title">Забыли пароль?</h2>
          <p class="card-subtitle">Введите email, и мы отправим ссылку для сброса</p>
          <?php if (isset($error) && is_string($error) && $error !== ''): ?>
            <p class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>
          <?php if (isset($notice) && is_string($notice) && $notice !== ''): ?>
            <p class="alert" role="status"><?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>

          <form method="POST" action="/forgot-password" data-submit-loading data-submit-overlay="true" data-loading-text="Отправка...">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-field" type="email" name="email" placeholder="alex@example.com" required>

            <button class="submit-btn" type="submit" data-loading-text="Отправка...">Отправить ссылку</button>
          </form>

          <div class="divider">или</div>
          <p class="signup">Вспомнили пароль? <a class="link" href="/login">Вернуться ко входу</a></p>
        </article>
      </section>
    </main>
    <script type="module" src="/assets/js/form-submit.js"></script>
  </body>
</html>
