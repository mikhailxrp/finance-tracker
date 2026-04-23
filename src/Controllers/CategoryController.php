<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Category;
use PDOException;
use Throwable;

final class CategoryController
{
  private const CSRF_SESSION_KEY = 'profile_csrf';
  private const FLASH_ERROR_KEY = 'profile_category_error';
  private const FLASH_SUCCESS_KEY = 'profile_category_success';
  private const NAME_MAX_LENGTH = 100;
  private const ICON_MAX_LENGTH = 50;
  private const DEFAULT_COLOR = '#6B7280';
  private const ANCHOR_PROFILE_CATEGORIES = '/profile#profile-categories';

  public function create(): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    if (!$this->isValidCsrfToken((string) \input('csrf', ''))) {
      \setFlash(self::FLASH_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    $name = trim((string) \input('name', ''));
    $type = trim((string) \input('type', ''));
    $icon = trim((string) \input('icon', ''));
    $color = trim((string) \input('color', self::DEFAULT_COLOR));

    $validationError = $this->validateCreateInput($name, $type, $icon, $color);
    if ($validationError !== null) {
      \setFlash(self::FLASH_ERROR_KEY, $validationError);
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    try {
      $model = new Category();
      $newId = $model->create(\getPdo(), $userId, $name, $type, $icon !== '' ? $icon : null, $color);
      if ($newId <= 0) {
        \setFlash(self::FLASH_ERROR_KEY, 'Не удалось создать категорию.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'category_create', 'user_id' => $userId]);
      \setFlash(self::FLASH_ERROR_KEY, 'Ошибка сервера при создании категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    \setFlash(self::FLASH_SUCCESS_KEY, 'Категория создана.');
    \redirect(self::ANCHOR_PROFILE_CATEGORIES);
  }

  public function update(): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    if (!$this->isValidCsrfToken((string) \input('csrf', ''))) {
      \setFlash(self::FLASH_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    $categoryId = $this->normalizePositiveInt((string) \input('category_id', ''));
    if ($categoryId === null) {
      \setFlash(self::FLASH_ERROR_KEY, 'Некорректный идентификатор категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    $name = trim((string) \input('name', ''));
    $icon = trim((string) \input('icon', ''));
    $color = trim((string) \input('color', self::DEFAULT_COLOR));

    $validationError = $this->validateUpdateInput($name, $icon, $color);
    if ($validationError !== null) {
      \setFlash(self::FLASH_ERROR_KEY, $validationError);
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    try {
      $pdo = \getPdo();
      $model = new Category();
      $existing = $model->findByIdForUser($pdo, $categoryId, $userId);
      if ($existing === null) {
        http_response_code(404);
        \setFlash(self::FLASH_ERROR_KEY, 'Категория не найдена.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }

      if (($existing['is_system'] ?? false) === true) {
        \setFlash(self::FLASH_ERROR_KEY, 'Системную категорию нельзя изменить.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }

      $updated = $model->update($pdo, $categoryId, $userId, $name, $icon !== '' ? $icon : null, $color);
      if (!$updated) {
        \setFlash(self::FLASH_ERROR_KEY, 'Изменения не были сохранены.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'category_update', 'user_id' => $userId, 'category_id' => $categoryId]);
      \setFlash(self::FLASH_ERROR_KEY, 'Ошибка сервера при обновлении категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    \setFlash(self::FLASH_SUCCESS_KEY, 'Категория обновлена.');
    \redirect(self::ANCHOR_PROFILE_CATEGORIES);
  }

  public function delete(): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    if (!$this->isValidCsrfToken((string) \input('csrf', ''))) {
      \setFlash(self::FLASH_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    $categoryId = $this->normalizePositiveInt((string) \input('category_id', ''));
    if ($categoryId === null) {
      \setFlash(self::FLASH_ERROR_KEY, 'Некорректный идентификатор категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    try {
      $pdo = \getPdo();
      $model = new Category();
      $existing = $model->findByIdForUser($pdo, $categoryId, $userId);
      if ($existing === null) {
        http_response_code(404);
        \setFlash(self::FLASH_ERROR_KEY, 'Категория не найдена.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }

      if (($existing['is_system'] ?? false) === true) {
        \setFlash(self::FLASH_ERROR_KEY, 'Системную категорию нельзя удалить.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }

      $deleted = $model->delete($pdo, $categoryId, $userId);
      if (!$deleted) {
        \setFlash(self::FLASH_ERROR_KEY, 'Категория не была удалена.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }
    } catch (PDOException $e) {
      if ($this->isForeignKeyConstraintError($e)) {
        \setFlash(self::FLASH_ERROR_KEY, 'Нельзя удалить категорию, используемую в транзакциях.');
        \redirect(self::ANCHOR_PROFILE_CATEGORIES);
      }

      log_exception($e, ['action' => 'category_delete_pdo', 'user_id' => $userId, 'category_id' => $categoryId]);
      \setFlash(self::FLASH_ERROR_KEY, 'Ошибка сервера при удалении категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'category_delete', 'user_id' => $userId, 'category_id' => $categoryId]);
      \setFlash(self::FLASH_ERROR_KEY, 'Ошибка сервера при удалении категории.');
      \redirect(self::ANCHOR_PROFILE_CATEGORIES);
    }

    \setFlash(self::FLASH_SUCCESS_KEY, 'Категория удалена.');
    \redirect(self::ANCHOR_PROFILE_CATEGORIES);
  }

  private function isValidCsrfToken(string $token): bool
  {
    \ensureSessionStarted();
    $sessionToken = $_SESSION[self::CSRF_SESSION_KEY] ?? '';

    return $token !== '' && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
  }

  private function normalizePositiveInt(string $value): ?int
  {
    if (!ctype_digit($value)) {
      return null;
    }

    $intValue = (int) $value;
    return $intValue > 0 ? $intValue : null;
  }

  private function validateCreateInput(string $name, string $type, string $icon, string $color): ?string
  {
    $nameError = $this->validateName($name);
    if ($nameError !== null) {
      return $nameError;
    }

    if ($type !== 'income' && $type !== 'expense') {
      return 'Неверный тип категории.';
    }

    $iconError = $this->validateIcon($icon);
    if ($iconError !== null) {
      return $iconError;
    }

    if (!$this->isValidColor($color)) {
      return 'Некорректный цвет категории.';
    }

    return null;
  }

  private function validateUpdateInput(string $name, string $icon, string $color): ?string
  {
    $nameError = $this->validateName($name);
    if ($nameError !== null) {
      return $nameError;
    }

    $iconError = $this->validateIcon($icon);
    if ($iconError !== null) {
      return $iconError;
    }

    if (!$this->isValidColor($color)) {
      return 'Некорректный цвет категории.';
    }

    return null;
  }

  private function validateName(string $name): ?string
  {
    if ($name === '') {
      return 'Название обязательно.';
    }

    if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
      return 'Максимум 100 символов.';
    }

    return null;
  }

  private function validateIcon(string $icon): ?string
  {
    if (mb_strlen($icon) > self::ICON_MAX_LENGTH) {
      return 'Иконка не должна превышать 50 символов.';
    }

    return null;
  }

  private function isValidColor(string $color): bool
  {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
  }

  private function isForeignKeyConstraintError(PDOException $exception): bool
  {
    $sqlState = $exception->getCode();
    $driverCode = isset($exception->errorInfo[1]) ? (int) $exception->errorInfo[1] : 0;

    return $sqlState === '23000' && $driverCode === 1451;
  }
}
