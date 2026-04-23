<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\User;
use App\Models\Category;
use Throwable;

final class ProfileController
{
  private const CSRF_SESSION_KEY = 'profile_csrf';
  private const NAME_ERROR_KEY = 'profile_name_error';
  private const NAME_SUCCESS_KEY = 'profile_name_success';
  private const NAME_OLD_INPUT_KEY = 'profile_name_old_input';
  private const PASSWORD_ERROR_KEY = 'profile_password_error';
  private const PASSWORD_SUCCESS_KEY = 'profile_password_success';
  private const CATEGORY_ERROR_KEY = 'profile_category_error';
  private const CATEGORY_SUCCESS_KEY = 'profile_category_success';
  private const NAME_MAX_LENGTH = 100;
  private const PASSWORD_MIN_LENGTH = 8;

  public function index(): void
  {
    \requireAuth();

    $sessionUserId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($sessionUserId === null) {
      \redirect('/login');
    }

    $profile = \getAuthenticatedUserProfile();
    if ($profile === null) {
      \redirect('/login');
    }

    $user = null;
    $stats = [
      'transactions' => 0,
      'active_goals' => 0,
      'ai_strategies' => 0,
    ];
    $nameError = \getFlash(self::NAME_ERROR_KEY);
    $nameSuccess = \getFlash(self::NAME_SUCCESS_KEY);
    $passwordError = \getFlash(self::PASSWORD_ERROR_KEY);
    $passwordSuccess = \getFlash(self::PASSWORD_SUCCESS_KEY);
    $categoryError = \getFlash(self::CATEGORY_ERROR_KEY);
    $categorySuccess = \getFlash(self::CATEGORY_SUCCESS_KEY);
    $oldNameInput = $this->takeOldNameInput();
    $csrf = $this->ensureCsrfToken();
    $categories = [
      'income' => ['system' => [], 'custom' => []],
      'expense' => ['system' => [], 'custom' => []],
    ];

    try {
      $pdo = \getPdo();
      $model = new User();
      $user = $model->findById($pdo, $sessionUserId);
      if ($user === null) {
        \redirect('/login');
      }
      $stats = $model->getStats($pdo, $sessionUserId);
      $categoryModel = new Category();
      $categories = $categoryModel->getAllForUser($pdo, $sessionUserId);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'profile_index', 'user_id' => $sessionUserId]);
      if ($nameError === null || $nameError === '') {
        $nameError = 'Не удалось загрузить профиль. Попробуйте позже.';
      }
    }

    $userName = is_array($user) && isset($user['name']) ? (string) $user['name'] : $profile['name'];
    $userEmail = is_array($user) && isset($user['email']) ? (string) $user['email'] : $profile['email'];
    $userCreatedAt = is_array($user) && isset($user['created_at']) ? (string) $user['created_at'] : '';

    header('Content-Type: text/html; charset=utf-8');
    \render('profile', [
      'userName' => $userName,
      'userEmail' => $userEmail,
      'currentPath' => '/profile',
      'selectedPeriod' => 'month',
      'csrf' => $csrf,
      'nameError' => $nameError,
      'nameSuccess' => $nameSuccess,
      'passwordError' => $passwordError,
      'passwordSuccess' => $passwordSuccess,
      'categoryError' => $categoryError,
      'categorySuccess' => $categorySuccess,
      'nameInput' => $oldNameInput !== '' ? $oldNameInput : $userName,
      'createdAt' => $this->formatMemberSince($userCreatedAt),
      'stats' => $stats,
      'categories' => $categories,
    ]);
  }

  public function updateName(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::NAME_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/profile');
    }

    $name = trim((string) \input('name', ''));
    $this->storeOldNameInput($name);

    $nameError = $this->validateName($name);
    if ($nameError !== null) {
      \setFlash(self::NAME_ERROR_KEY, $nameError);
      \redirect('/profile');
    }

    try {
      $pdo = \getPdo();
      $model = new User();
      $updated = $model->updateName($pdo, $userId, $name);
      if (!$updated) {
        \setFlash(self::NAME_ERROR_KEY, 'Изменения не были сохранены.');
        \redirect('/profile');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'profile_update_name', 'user_id' => $userId]);
      \setFlash(self::NAME_ERROR_KEY, 'Не удалось обновить имя. Попробуйте позже.');
      \redirect('/profile');
    }

    $this->clearOldNameInput();
    \setFlash(self::NAME_SUCCESS_KEY, 'Имя обновлено.');
    \redirect('/profile');
  }

  public function changePassword(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::PASSWORD_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/profile');
    }

    $oldPassword = (string) \input('old_password', '');
    $newPassword = (string) \input('new_password', '');
    $newPasswordConfirm = (string) \input('new_password_confirm', '');

    $validationError = $this->validatePasswordInput($newPassword, $newPasswordConfirm);
    if ($validationError !== null) {
      \setFlash(self::PASSWORD_ERROR_KEY, $validationError);
      \redirect('/profile');
    }

    try {
      $pdo = \getPdo();
      $model = new User();
      $user = $model->findById($pdo, $userId);
      if ($user === null) {
        \redirect('/login');
      }

      $hash = isset($user['password_hash']) ? (string) $user['password_hash'] : '';
      if ($hash === '' || !password_verify($oldPassword, $hash)) {
        \setFlash(self::PASSWORD_ERROR_KEY, 'Неверный текущий пароль.');
        \redirect('/profile');
      }

      $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
      $changed = $model->changePassword($pdo, $userId, $newHash);
      if (!$changed) {
        \setFlash(self::PASSWORD_ERROR_KEY, 'Пароль не был изменён.');
        \redirect('/profile');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'profile_change_password', 'user_id' => $userId]);
      \setFlash(self::PASSWORD_ERROR_KEY, 'Не удалось изменить пароль. Попробуйте позже.');
      \redirect('/profile');
    }

    \setFlash(self::PASSWORD_SUCCESS_KEY, 'Пароль изменён.');
    \redirect('/profile');
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

  private function validateName(string $name): ?string
  {
    if ($name === '') {
      return 'Имя обязательно.';
    }

    if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
      return 'Максимум 100 символов.';
    }

    return null;
  }

  private function validatePasswordInput(string $newPassword, string $newPasswordConfirm): ?string
  {
    if (mb_strlen($newPassword) < self::PASSWORD_MIN_LENGTH) {
      return 'Минимум 8 символов.';
    }

    if ($newPassword !== $newPasswordConfirm) {
      return 'Пароли не совпадают.';
    }

    return null;
  }

  private function storeOldNameInput(string $name): void
  {
    \ensureSessionStarted();
    $_SESSION[self::NAME_OLD_INPUT_KEY] = $name;
  }

  private function clearOldNameInput(): void
  {
    \ensureSessionStarted();
    unset($_SESSION[self::NAME_OLD_INPUT_KEY]);
  }

  private function takeOldNameInput(): string
  {
    \ensureSessionStarted();
    $value = $_SESSION[self::NAME_OLD_INPUT_KEY] ?? '';
    unset($_SESSION[self::NAME_OLD_INPUT_KEY]);

    return is_string($value) ? $value : '';
  }

  private function formatMemberSince(string $createdAt): string
  {
    if ($createdAt === '') {
      return '';
    }

    $timestamp = strtotime($createdAt);
    if ($timestamp === false) {
      return '';
    }

    $monthMap = [
      '01' => 'января',
      '02' => 'февраля',
      '03' => 'марта',
      '04' => 'апреля',
      '05' => 'мая',
      '06' => 'июня',
      '07' => 'июля',
      '08' => 'августа',
      '09' => 'сентября',
      '10' => 'октября',
      '11' => 'ноября',
      '12' => 'декабря',
    ];

    $monthKey = date('m', $timestamp);
    $monthLabel = $monthMap[$monthKey] ?? '';
    if ($monthLabel === '') {
      return '';
    }

    return date('j', $timestamp) . ' ' . $monthLabel . ' ' . date('Y', $timestamp);
  }
}
