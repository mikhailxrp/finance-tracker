<?php

declare(strict_types=1);

$already = $already ?? false;
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Email подтверждён</title>
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
  </head>
  <body class="auth-page auth-page--register">
    <main class="register-layout">
      <section class="register-hero" aria-label="Информация">
        <div class="hero-content">
          <div class="hero-logo" aria-hidden="true">✅</div>
          <h1 class="hero-title">Готово</h1>
          <p class="hero-subtitle">Ваш email подтверждён</p>
        </div>
      </section>

      <section class="register-side">
        <article class="register-card">
          <h2 class="card-title"><?= $already ? 'Уже подтверждено' : 'Email подтверждён' ?></h2>
          <p class="card-subtitle">
            <?php if ($already): ?>
              Этот адрес уже был подтверждён ранее. Можете пользоваться аккаунтом.
            <?php else: ?>
              Вы вошли в систему. Перейдите в личный кабинет.
            <?php endif; ?>
          </p>

          <p class="signin">
            <?php if ($already): ?>
              <a class="link" href="/login">Войти</a>
            <?php else: ?>
              <a class="link" href="/dashboard">Перейти к дашборду</a>
            <?php endif; ?>
          </p>
        </article>
      </section>
    </main>
  </body>
</html>
