<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';
require_once dirname(__DIR__) . '/Core/Request.php';

use App\Services\MailService;
use PDO;
use Throwable;

final class AuthController
{
  private const CSRF_SESSION_KEY = 'auth_csrf';
  private const CSRF_EXPIRED_MESSAGE = 'Сессия устарела. Обновите страницу и попробуйте снова.';

  private const MIN_PASSWORD_LENGTH = 8;
  private const LOGIN_ERROR_MESSAGE = 'Неверный email или пароль';
  private const VERIFY_TOKEN_TTL_SECONDS = 172800;
  private const RESET_TOKEN_TTL_SECONDS = 3600;
  private const RESET_REQUEST_COOLDOWN_SECONDS = 300;
  private const RESET_NEUTRAL_MESSAGE = 'Если email зарегистрирован, письмо для сброса уже отправлено.';

  public function showLogin(): void
  {
    \redirectIfAuthenticated();

    $error = \getFlash('login_error');
    $notice = \getFlash('login_notice');
    $csrf = $this->ensureCsrfToken();

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/login', ['error' => $error, 'notice' => $notice, 'csrf' => $csrf]);
  }

  public function login(): void
  {
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('login_error', self::CSRF_EXPIRED_MESSAGE);
      \redirect('/login');
    }

    $email = mb_strtolower((string) \input('email', ''));
    $password = (string) \input('password', '');

    if ($email === '' || $password === '') {
      \setFlash('login_error', self::LOGIN_ERROR_MESSAGE);
      \redirect('/login');
    }

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare(
        'SELECT id, password_hash, email_verified_at FROM users WHERE email = ? LIMIT 1'
      );
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'login']);
      \setFlash('login_error', 'Ошибка сервера, попробуйте позже.');
      \redirect('/login');
    }

    $userId = \normalizeUserId(is_array($user) ? ($user['id'] ?? null) : null);
    $hash = is_array($user) ? ($user['password_hash'] ?? null) : null;
    $isValid = $userId !== null && is_string($hash) && password_verify($password, $hash);

    if (!$isValid) {
      \setFlash('login_error', self::LOGIN_ERROR_MESSAGE);
      \redirect('/login');
    }

    $verifiedAt = is_array($user) ? ($user['email_verified_at'] ?? null) : null;
    if ($verifiedAt === null || $verifiedAt === '') {
      \ensureSessionStarted();
      $_SESSION['pending_verification_user_id'] = $userId;
      \setFlash(
        'verify_notice',
        'Сначала подтвердите email. Мы отправили письмо на ваш адрес — проверьте почту или нажмите «Отправить ещё раз».'
      );
      \redirect('/verify-email');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    \redirect('/dashboard');
  }

  public function showRegister(): void
  {
    \redirectIfAuthenticated();

    $error = \getFlash('register_error');
    $csrf = $this->ensureCsrfToken();

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/register', ['error' => $error, 'csrf' => $csrf]);
  }

  public function showForgotPassword(): void
  {
    \redirectIfAuthenticated();

    $error = \getFlash('forgot_password_error');
    $notice = \getFlash('forgot_password_notice');
    $csrf = $this->ensureCsrfToken();

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/forgot-password', [
      'error' => $error,
      'notice' => $notice,
      'csrf' => $csrf,
    ]);
  }

  public function sendResetLink(): void
  {
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('forgot_password_error', self::CSRF_EXPIRED_MESSAGE);
      \redirect('/forgot-password');
    }

    $email = mb_strtolower((string) \input('email', ''));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
      \setFlash('forgot_password_error', 'Укажите корректный email.');
      \redirect('/forgot-password');
    }

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (is_array($user)) {
        $userId = \normalizeUserId($user['id'] ?? null);
        if ($userId !== null && !$this->isResetRateLimited($pdo, $userId)) {
          $plainToken = bin2hex(random_bytes(32));
          $tokenHash = hash('sha256', $plainToken);
          $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL_SECONDS);

          $insert = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
          );
          $insert->execute([$userId, $tokenHash, $expiresAt]);

          $name = is_string($user['name'] ?? null) ? (string) $user['name'] : '';
          $userEmail = is_string($user['email'] ?? null) ? (string) $user['email'] : '';
          if ($userEmail !== '') {
            $this->sendPasswordResetEmail($userEmail, $name, $plainToken);
          }
        }
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'send_reset_link']);
      // Нейтральный ответ сохраняем всегда, чтобы не раскрывать существование email.
    }

    \setFlash('forgot_password_notice', self::RESET_NEUTRAL_MESSAGE);
    \redirect('/forgot-password');
  }

  public function showResetPassword(string $token): void
  {
    \redirectIfAuthenticated();

    $token = rawurldecode($token);
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
      \setFlash('forgot_password_error', 'Ссылка недействительна или истекла.');
      \redirect('/forgot-password');
    }

    $tokenHash = hash('sha256', $token);

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare(
        'SELECT id FROM password_resets WHERE token = ? AND expires_at > CURRENT_TIMESTAMP LIMIT 1'
      );
      $stmt->execute([$tokenHash]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!is_array($row)) {
        \setFlash('forgot_password_error', 'Ссылка недействительна или истекла.');
        \redirect('/forgot-password');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'show_reset_password']);
      \setFlash('forgot_password_error', 'Ошибка сервера. Попробуйте позже.');
      \redirect('/forgot-password');
    }

    $error = \getFlash('reset_password_error');
    $csrf = $this->ensureCsrfToken();

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/reset-password', [
      'token' => $token,
      'error' => $error,
      'csrf' => $csrf,
    ]);
  }

  public function resetPassword(): void
  {
    $token = (string) \input('token', '');

    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('forgot_password_error', self::CSRF_EXPIRED_MESSAGE);
      \redirect('/forgot-password');
    }

    $password = (string) \input('password', '');
    $passwordConfirm = (string) \input('password_confirm', '');

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
      \setFlash('forgot_password_error', 'Ссылка недействительна или истекла.');
      \redirect('/forgot-password');
    }

    if ($password === '' || $passwordConfirm === '') {
      \setFlash('reset_password_error', 'Все поля обязательны для заполнения.');
      \redirect('/reset-password/' . rawurlencode($token));
    }

    if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
      \setFlash('reset_password_error', 'Пароль должен быть не короче 8 символов.');
      \redirect('/reset-password/' . rawurlencode($token));
    }

    if ($password !== $passwordConfirm) {
      \setFlash('reset_password_error', 'Пароли не совпадают.');
      \redirect('/reset-password/' . rawurlencode($token));
    }

    $tokenHash = hash('sha256', $token);

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare(
        'SELECT id, user_id FROM password_resets WHERE token = ? AND expires_at > CURRENT_TIMESTAMP LIMIT 1'
      );
      $stmt->execute([$tokenHash]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($row)) {
        \setFlash('forgot_password_error', 'Ссылка недействительна или истекла.');
        \redirect('/forgot-password');
      }

      $userId = \normalizeUserId($row['user_id'] ?? null);
      $resetId = \normalizeUserId($row['id'] ?? null);
      if ($userId === null || $resetId === null) {
        \setFlash('forgot_password_error', 'Ссылка недействительна или истекла.');
        \redirect('/forgot-password');
      }

      $passwordHash = password_hash($password, PASSWORD_DEFAULT);

      $pdo->beginTransaction();
      $updatePassword = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
      $updatePassword->execute([$passwordHash, $userId]);

      $deleteToken = $pdo->prepare('DELETE FROM password_resets WHERE id = ?');
      $deleteToken->execute([$resetId]);
      $pdo->commit();
    } catch (Throwable $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      log_exception($e, ['action' => 'reset_password']);
      \setFlash('reset_password_error', 'Ошибка сервера, попробуйте позже.');
      \redirect('/reset-password/' . rawurlencode($token));
    }

    \setFlash('login_notice', 'Пароль успешно изменен. Теперь войдите с новым паролем.');
    \redirect('/login');
  }

  public function register(): void
  {
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('register_error', self::CSRF_EXPIRED_MESSAGE);
      \redirect('/register');
    }

    $name = (string) \input('name', '');
    $email = mb_strtolower((string) \input('email', ''));
    $password = (string) \input('password', '');
    $passwordConfirm = (string) \input('password_confirm', '');

    $validationError = $this->validateRegistration($name, $email, $password, $passwordConfirm);
    if ($validationError !== null) {
      \setFlash('register_error', $validationError);
      \redirect('/register');
    }

    try {
      $pdo = \getPdo();
      $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $existsStmt->execute([$email]);
      $emailExists = $existsStmt->fetch(PDO::FETCH_ASSOC) !== false;

      if ($emailExists) {
        \setFlash('register_error', 'Email уже занят');
        \redirect('/register');
      }

      $pdo->beginTransaction();

      $passwordHash = password_hash($password, PASSWORD_DEFAULT);

      $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, email_verified_at, email_token) VALUES (?, ?, ?, NULL, NULL)'
      )->execute([$name, $email, $passwordHash]);
      $newUserId = (int) $pdo->lastInsertId();

      $plainToken = $this->buildVerificationPlainToken($newUserId);
      $tokenHash = hash('sha256', $plainToken);
      $updToken = $pdo->prepare('UPDATE users SET email_token = ? WHERE id = ?');
      $updToken->execute([$tokenHash, $newUserId]);

      $pdo->commit();

      \ensureSessionStarted();
      $_SESSION['pending_verification_user_id'] = $newUserId;
      $_SESSION['verify_csrf'] = bin2hex(random_bytes(32));

      $mailOk = $this->sendVerificationEmail($email, $name, $plainToken);
      if ($mailOk) {
        \setFlash('verify_notice', 'Мы отправили письмо с ссылкой для подтверждения.');
      } else {
        \setFlash(
          'verify_notice',
          'Аккаунт создан, но письмо не удалось отправить. Нажмите «Отправить ещё раз» или проверьте настройки SMTP.'
        );
      }

      \redirect('/verify-email');
    } catch (Throwable $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      log_exception($e, ['action' => 'register']);
      \setFlash('register_error', 'Ошибка сервера, попробуйте позже.');
      \redirect('/register');
    }
  }

  public function showVerifyEmail(): void
  {
    \ensureSessionStarted();

    $error = \getFlash('verify_error');
    $notice = \getFlash('verify_notice');
    $pendingId = \normalizeUserId($_SESSION['pending_verification_user_id'] ?? null);

    if ($error === null && $notice === null && $pendingId === null) {
      \redirect('/login');
    }

    $emailDisplay = null;
    if ($pendingId !== null) {
      try {
        $pdo = \getPdo();
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$pendingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $emailDisplay = is_array($row) ? ($row['email'] ?? null) : null;
      } catch (Throwable $e) {
        log_exception($e, ['action' => 'show_verify_email', 'user_id' => $pendingId]);
        $emailDisplay = null;
      }
    }

    if (!isset($_SESSION['verify_csrf']) || !is_string($_SESSION['verify_csrf']) || $_SESSION['verify_csrf'] === '') {
      $_SESSION['verify_csrf'] = bin2hex(random_bytes(32));
    }

    $csrf = $_SESSION['verify_csrf'];

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/verify-email', [
      'email' => is_string($emailDisplay) ? $emailDisplay : null,
      'error' => $error,
      'notice' => $notice,
      'csrf' => $csrf,
      'can_resend' => $pendingId !== null,
    ]);
  }

  public function verifyEmail(string $token): void
  {
    if ($token === 'resend') {
      \redirect('/verify-email');
    }

    $token = rawurldecode($token);
    if (
      !preg_match('/^(\d+)_(\d{10})_([a-f0-9]{32})$/', $token, $matches)
    ) {
      \setFlash('verify_error', 'Ссылка подтверждения недействительна. Запросите новое письмо со страницы входа или регистрации.');
      \redirect('/verify-email');
    }

    $userId = (int) $matches[1];
    $expiresAt = (int) $matches[2];

    if (time() > $expiresAt) {
      \setFlash('verify_error', 'Срок действия ссылки истёк. Войдите с паролем и запросите повторную отправку письма.');
      \redirect('/verify-email');
    }

    $expectedHash = hash('sha256', $token);

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare(
        'SELECT id, email_verified_at, email_token FROM users WHERE id = ? LIMIT 1'
      );
      $stmt->execute([$userId]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'verify_email_fetch', 'user_id' => $userId]);
      \setFlash('verify_error', 'Ошибка сервера. Попробуйте позже.');
      \redirect('/verify-email');
    }

    if (!is_array($row)) {
      \setFlash('verify_error', 'Ссылка подтверждения недействительна.');
      \redirect('/verify-email');
    }

    $storedHash = $row['email_token'] ?? null;
    if (!is_string($storedHash) || !hash_equals($storedHash, $expectedHash)) {
      \setFlash('verify_error', 'Ссылка подтверждения недействительна или устарела.');
      \redirect('/verify-email');
    }

    $already = $row['email_verified_at'] ?? null;
    if ($already !== null && $already !== '') {
      header('Content-Type: text/html; charset=utf-8');
      \render('auth/verified', ['already' => true]);
      return;
    }

    try {
      $upd = $pdo->prepare(
        'UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, email_token = NULL WHERE id = ?'
      );
      $upd->execute([$userId]);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'verify_email_confirm', 'user_id' => $userId]);
      \setFlash('verify_error', 'Не удалось подтвердить email. Попробуйте позже.');
      \redirect('/verify-email');
    }

    \ensureSessionStarted();
    unset($_SESSION['pending_verification_user_id'], $_SESSION['verify_csrf']);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    header('Content-Type: text/html; charset=utf-8');
    \render('auth/verified', ['already' => false]);
  }

  public function resendVerification(): void
  {
    $csrfPost = (string) \input('csrf', '');
    \ensureSessionStarted();
    $csrfSession = $_SESSION['verify_csrf'] ?? '';

    if ($csrfPost === '' || $csrfSession === '' || !hash_equals($csrfSession, $csrfPost)) {
      \setFlash('verify_error', 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/verify-email');
    }

    $pendingId = \normalizeUserId($_SESSION['pending_verification_user_id'] ?? null);
    if ($pendingId === null) {
      \setFlash('verify_error', 'Войдите с паролем — мы отправим письмо на ваш email.');
      \redirect('/login');
    }

    try {
      $pdo = \getPdo();
      $stmt = $pdo->prepare(
        'SELECT id, email, name, email_verified_at FROM users WHERE id = ? LIMIT 1'
      );
      $stmt->execute([$pendingId]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'resend_verification_fetch', 'user_id' => $pendingId]);
      \setFlash('verify_error', 'Ошибка сервера, попробуйте позже.');
      \redirect('/verify-email');
    }

    if (!is_array($row)) {
      \setFlash('verify_error', 'Пользователь не найден.');
      \redirect('/login');
    }

    $verifiedAt = $row['email_verified_at'] ?? null;
    if ($verifiedAt !== null && $verifiedAt !== '') {
      \redirect('/dashboard');
    }

    $email = isset($row['email']) && is_string($row['email']) ? $row['email'] : '';
    $name = isset($row['name']) && is_string($row['name']) ? $row['name'] : '';

    if ($email === '') {
      \setFlash('verify_error', 'Не удалось определить email.');
      \redirect('/verify-email');
    }

    $plainToken = $this->buildVerificationPlainToken($pendingId);
    $tokenHash = hash('sha256', $plainToken);

    try {
      $upd = $pdo->prepare('UPDATE users SET email_token = ? WHERE id = ?');
      $upd->execute([$tokenHash, $pendingId]);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'resend_verification_save_token', 'user_id' => $pendingId]);
      \setFlash('verify_error', 'Не удалось сохранить токен. Попробуйте позже.');
      \redirect('/verify-email');
    }

    $mailOk = $this->sendVerificationEmail($email, $name, $plainToken);
    if ($mailOk) {
      \setFlash('verify_notice', 'Письмо отправлено повторно.');
    } else {
      \setFlash(
        'verify_notice',
        'Не удалось отправить письмо. Проверьте SMTP в .env или попробуйте позже.'
      );
    }

    \redirect('/verify-email');
  }

  public function logout(): void
  {
    \ensureSessionStarted();
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('login_error', self::CSRF_EXPIRED_MESSAGE);
      \redirect('/login');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
      ]);
    }

    session_destroy();
    \redirect('/login');
  }

  private function ensureCsrfToken(): string
  {
    \ensureSessionStarted();
    $token = $_SESSION[self::CSRF_SESSION_KEY] ?? null;
    if (!is_string($token) || $token === '') {
      $token = bin2hex(random_bytes(32));
      $_SESSION[self::CSRF_SESSION_KEY] = $token;
    }

    return $token;
  }

  private function isValidCsrfToken(string $token): bool
  {
    \ensureSessionStarted();
    $sessionToken = $_SESSION[self::CSRF_SESSION_KEY] ?? '';

    return $token !== '' && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
  }

  private function validateRegistration(string $name, string $email, string $password, string $passwordConfirm): ?string
  {
    if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
      return 'Все поля обязательны для заполнения';
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
      return 'Некорректный формат email';
    }

    if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
      return 'Пароль должен быть не короче 8 символов';
    }

    if ($password !== $passwordConfirm) {
      return 'Пароли не совпадают';
    }

    return null;
  }

  private function buildVerificationPlainToken(int $userId): string
  {
    $expiresAt = time() + self::VERIFY_TOKEN_TTL_SECONDS;

    return sprintf('%d_%010d_%s', $userId, $expiresAt, bin2hex(random_bytes(16)));
  }

  private function sendVerificationEmail(string $email, string $name, string $plainToken): bool
  {
    $base = rtrim(\env('APP_URL'), '/');
    $link = $base . '/verify-email/' . rawurlencode($plainToken);

    $subject = 'Подтвердите email — FinanceTracker';
    $safeName = $name !== '' ? $name : 'пользователь';
    $body = "Здравствуйте, {$safeName}!\r\n\r\n";
    $body .= "Подтвердите адрес email, перейдя по ссылке:\r\n{$link}\r\n\r\n";
    $body .= "Ссылка действует 48 часов. Если вы не регистрировались, проигнорируйте это письмо.\r\n";

    $mailer = new MailService();

    return $mailer->send($email, $subject, $body);
  }

  private function sendPasswordResetEmail(string $email, string $name, string $plainToken): bool
  {
    $base = rtrim(\env('APP_URL'), '/');
    $link = $base . '/reset-password/' . rawurlencode($plainToken);

    $subject = 'Восстановление пароля — FinanceTracker';
    $safeName = $name !== '' ? $name : 'пользователь';
    $body = "Здравствуйте, {$safeName}!\r\n\r\n";
    $body .= "Вы запросили восстановление пароля. Перейдите по ссылке:\r\n{$link}\r\n\r\n";
    $body .= "Ссылка действует 1 час. Если вы не запрашивали сброс, просто проигнорируйте это письмо.\r\n";

    $mailer = new MailService();

    return $mailer->send($email, $subject, $body);
  }

  private function isResetRateLimited(PDO $pdo, int $userId): bool
  {
    $stmt = $pdo->prepare(
      'SELECT created_at FROM password_resets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!is_array($row)) {
      return false;
    }

    $createdAtRaw = $row['created_at'] ?? null;
    if (!is_string($createdAtRaw) || $createdAtRaw === '') {
      return false;
    }

    $createdAt = strtotime($createdAtRaw);
    if ($createdAt === false) {
      return false;
    }

    return (time() - $createdAt) < self::RESET_REQUEST_COOLDOWN_SECONDS;
  }
}
