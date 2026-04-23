<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Регистрация</title>
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
      <section class="register-hero" aria-label="Промо-блок">
        <div class="hero-content">
          <div class="hero-logo" aria-hidden="true">💰</div>
          <h1 class="hero-title">Начните прямо сейчас</h1>
          <p class="hero-subtitle">Присоединяйтесь к тысячам пользователей</p>

          <ul class="feature-list" aria-label="Преимущества">
            <li class="feature-item"><span class="feature-icon">📊</span>Автоматическая аналитика расходов</li>
            <li class="feature-item"><span class="feature-icon">🎯</span>Умное планирование целей</li>
            <li class="feature-item"><span class="feature-icon">🤖</span>AI-советник для оптимизации бюджета</li>
          </ul>
        </div>
      </section>

      <section class="register-side">
        <article class="register-card">
          <h2 class="card-title">Создать аккаунт</h2>
          <p class="card-subtitle">Заполните данные для регистрации</p>
          <?php if (isset($error) && is_string($error) && $error !== ''): ?>
            <p class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php endif; ?>

          <form method="POST" action="/register" data-submit-loading data-submit-overlay="true" data-loading-text="Регистрация...">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <label class="form-label" for="name">Имя</label>
            <input id="name" class="form-field" type="text" name="name" placeholder="Алексей Петров" required>

            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-field" type="email" name="email" placeholder="alex@example.com" required>

            <label class="form-label" for="password">Пароль</label>
            <div class="password-wrap">
              <input id="password" class="form-field" type="password" name="password" placeholder="••••••••" required>
              <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
            </div>

            <label class="form-label" for="password_confirm">Подтвердите пароль</label>
            <div class="password-wrap">
              <input
                id="password_confirm"
                class="form-field"
                type="password"
                name="password_confirm"
                placeholder="••••••••"
                required
              >
              <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
            </div>

            <p class="terms">
              Создавая аккаунт, вы соглашаетесь с
              <a class="link" href="#">условиями использования</a>
              и
              <a class="link" href="#">политикой конфиденциальности</a>
            </p>

            <button class="submit-btn" type="submit" data-loading-text="Регистрация...">Создать аккаунт</button>
          </form>

          <div class="divider">или</div>
          <p class="signin">Уже есть аккаунт? <a class="link" href="/login">Войти</a></p>
        </article>
      </section>
    </main>
    <script type="module" src="/assets/js/form-submit.js"></script>
    <script type="module" src="/assets/js/password-toggle.js"></script>
  </body>
</html>
