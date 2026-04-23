<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Сброс пароля</title>
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
  <body class="auth-page auth-page--register">
    <main class="register-layout">
      <section class="register-hero" aria-label="Информация">
        <div class="hero-content">
          <div class="hero-logo" aria-hidden="true">🛡️</div>
          <h1 class="hero-title">Новый пароль</h1>
          <p class="hero-subtitle">Введите и подтвердите новый пароль</p>
        </div>
      </section>

      <section class="register-side">
        <article class="register-card">
          <h2 class="card-title">Сброс пароля</h2>
          <p class="card-subtitle">После сохранения используйте новый пароль для входа</p>
          <?php if (isset($error) && is_string($error) && $error !== ''): ?>
            <p class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>

          <form method="POST" action="/reset-password" data-submit-loading data-submit-overlay="true" data-loading-text="Сохранение...">
            <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($token ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

            <label class="form-label" for="password">Новый пароль</label>
            <div class="password-wrap">
              <input id="password" class="form-field" type="password" name="password" placeholder="••••••••" required>
              <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
            </div>

            <label class="form-label" for="password_confirm">Подтвердите пароль</label>
            <div class="password-wrap">
              <input id="password_confirm" class="form-field" type="password" name="password_confirm" placeholder="••••••••" required>
              <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
            </div>

            <button class="submit-btn" type="submit" data-loading-text="Сохранение...">Сменить пароль</button>
          </form>
        </article>
      </section>
    </main>
    <script type="module" src="/assets/js/form-submit.js"></script>
    <script type="module" src="/assets/js/password-toggle.js"></script>
  </body>
</html>
