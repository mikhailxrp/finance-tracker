<?php

declare(strict_types=1);

$email = $email ?? null;
$error = $error ?? null;
$notice = $notice ?? null;
$csrf = $csrf ?? '';
$canResend = isset($can_resend) ? (bool) $can_resend : false;
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Подтвердите email</title>
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
          <div class="hero-logo" aria-hidden="true">✉️</div>
          <h1 class="hero-title">Почти готово</h1>
          <p class="hero-subtitle">Осталось подтвердить адрес email</p>
        </div>
      </section>

      <section class="register-side">
        <article class="register-card">
          <h2 class="card-title">Проверьте почту</h2>
          <p class="card-subtitle">
            <?php if (is_string($email) && $email !== ''): ?>
              Мы отправили письмо на
              <strong><?= htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
            <?php else: ?>
              Перейдите по ссылке из письма, чтобы активировать аккаунт.
            <?php endif; ?>
          </p>

          <?php if (is_string($notice) && $notice !== ''): ?>
            <p class="alert" role="status"><?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>

          <?php if (is_string($error) && $error !== ''): ?>
            <p class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>

          <?php if ($canResend): ?>
            <form method="POST" action="/verify-email/resend" data-submit-loading data-submit-overlay="true" data-loading-text="Отправка...">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
              <button class="submit-btn" type="submit" data-loading-text="Отправка...">Отправить письмо ещё раз</button>
            </form>
          <?php endif; ?>

          <div class="divider">или</div>
          <p class="signin">
            <a class="link" href="/login">На страницу входа</a>
          </p>
        </article>
      </section>
    </main>
    <script type="module" src="/assets/js/form-submit.js"></script>
  </body>
</html>
