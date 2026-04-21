<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Category;
use App\Models\Transaction;
use Throwable;

final class TransactionController
{
  private const CSRF_SESSION_KEY = 'transactions_csrf';
  private const MAX_COMMENT_LENGTH = 255;
  private const INCOME_TYPE = 'income';
  private const EXPENSE_TYPE = 'expense';
  private const DEFAULT_RETURN_PATH = '/income';
  private const DEFAULT_PERIOD = 'month';
  private const EMPTY_TOP_SOURCE = '—';
  private const EMPTY_TOP_CATEGORY = '—';

  public function index(): void
  {
    \redirect(self::DEFAULT_RETURN_PATH);
  }

  public function income(): void
  {
    $this->renderByType(self::INCOME_TYPE);
  }

  public function expenses(): void
  {
    $this->renderByType(self::EXPENSE_TYPE);
  }

  public function store(): void
  {
    $this->storeByType($this->resolveTypeFromInputPath(self::DEFAULT_RETURN_PATH));
  }

  public function storeIncome(): void
  {
    $this->storeByType(self::INCOME_TYPE);
  }

  public function storeExpense(): void
  {
    $this->storeByType(self::EXPENSE_TYPE);
  }

  public function show(string $id): void
  {
    \requireAuth();
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Transaction not found: {$id}";
  }

  public function edit(string $id): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $transactionId = $this->normalizePositiveIntId($id);
    if ($transactionId === null) {
      \setFlash('transactions_error', 'Некорректный идентификатор транзакции.');
      \redirect(self::DEFAULT_RETURN_PATH);
    }

    $transactionModel = new Transaction();
    $returnPath = $this->resolveReturnPath((string) (\query('return', '') ?? ''));

    try {
      $pdo = \getPdo();
      $transaction = $transactionModel->findByIdForUser($pdo, $transactionId, $userId);
      if ($transaction === null) {
        \setFlash('transactions_error', 'Транзакция не найдена или недоступна.');
        \redirect($returnPath);
      }

      $transactionType = isset($transaction['category_type']) ? (string) $transaction['category_type'] : '';
      $expectedType = $this->resolveTypeFromInputPath($returnPath);
      if ($transactionType !== $expectedType) {
        $returnPath = $this->pathByType($transactionType);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'edit', 'user_id' => $userId, 'transaction_id' => $transactionId]);
      \setFlash('transactions_error', 'Не удалось открыть транзакцию для редактирования.');
      \redirect($returnPath);
    }

    \redirect($returnPath . '?edit=' . $transactionId);
  }

  public function update(string $id): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $returnPath = $this->resolveReturnPath((string) \input('return_to', ''));
    $expectedType = $this->resolveTypeFromInputPath($returnPath);
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('transactions_error', 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect($returnPath);
    }

    $transactionId = $this->normalizePositiveIntId($id);
    if ($transactionId === null) {
      \setFlash('transactions_error', 'Некорректный идентификатор транзакции.');
      \redirect($returnPath);
    }

    $amountRaw = (string) \input('amount', '');
    $categoryIdRaw = (string) \input('category_id', '');
    $date = (string) \input('date', '');
    $comment = (string) \input('comment', '');

    $validationError = $this->validateInput($amountRaw, $categoryIdRaw, $date, $comment);
    if ($validationError !== null) {
      \setFlash('transactions_error', $validationError);
      \redirect($returnPath . '?edit=' . $transactionId);
    }

    $categoryId = (int) $categoryIdRaw;
    $amount = number_format((float) $amountRaw, 2, '.', '');

    $categoryModel = new Category();
    $transactionModel = new Transaction();

    try {
      $pdo = \getPdo();
      $categories = $categoryModel->getForUserByType($pdo, $userId, $expectedType);
      $categoryIds = array_map(
        static fn(array $category): int => (int) ($category['id'] ?? 0),
        $categories
      );

      if (!in_array($categoryId, $categoryIds, true)) {
        \setFlash('transactions_error', 'Выбрана недоступная категория.');
        \redirect($returnPath . '?edit=' . $transactionId);
      }

      $exists = $transactionModel->findByIdForUser($pdo, $transactionId, $userId);
      if ($exists === null) {
        \setFlash('transactions_error', 'Транзакция не найдена или недоступна.');
        \redirect($returnPath);
      }

      $existingType = isset($exists['category_type']) ? (string) $exists['category_type'] : '';
      if ($existingType !== $expectedType) {
        \setFlash('transactions_error', 'Нельзя изменить транзакцию другого типа на этой странице.');
        \redirect($this->pathByType($existingType));
      }

      $updated = $transactionModel->updateForUser(
        $pdo,
        $transactionId,
        $userId,
        $categoryId,
        $amount,
        $date,
        $comment
      );
      if (!$updated) {
        \setFlash('transactions_error', 'Не удалось обновить транзакцию.');
        \redirect($returnPath . '?edit=' . $transactionId);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'update', 'user_id' => $userId, 'transaction_id' => $transactionId]);
      \setFlash('transactions_error', 'Ошибка сервера при обновлении транзакции.');
      \redirect($returnPath . '?edit=' . $transactionId);
    }

    \setFlash('transactions_notice', 'Транзакция успешно обновлена.');
    \redirect($returnPath);
  }

  public function delete(string $id): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $returnPath = $this->resolveReturnPath((string) \input('return_to', ''));
    $expectedType = $this->resolveTypeFromInputPath($returnPath);
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('transactions_error', 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect($returnPath);
    }

    $transactionId = $this->normalizePositiveIntId($id);
    if ($transactionId === null) {
      \setFlash('transactions_error', 'Некорректный идентификатор транзакции.');
      \redirect($returnPath);
    }

    $transactionModel = new Transaction();

    try {
      $pdo = \getPdo();
      $transaction = $transactionModel->findByIdForUser($pdo, $transactionId, $userId);
      if ($transaction === null) {
        \setFlash('transactions_error', 'Транзакция не найдена или недоступна.');
        \redirect($returnPath);
      }

      $existingType = isset($transaction['category_type']) ? (string) $transaction['category_type'] : '';
      if ($existingType !== $expectedType) {
        \setFlash('transactions_error', 'Нельзя удалить транзакцию другого типа на этой странице.');
        \redirect($this->pathByType($existingType));
      }

      $deleted = $transactionModel->deleteForUser($pdo, $transactionId, $userId);
      if (!$deleted) {
        \setFlash('transactions_error', 'Транзакция не найдена или недоступна.');
        \redirect($returnPath);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'delete', 'user_id' => $userId, 'transaction_id' => $transactionId]);
      \setFlash('transactions_error', 'Ошибка сервера при удалении транзакции.');
      \redirect($returnPath);
    }

    \setFlash('transactions_notice', 'Транзакция успешно удалена.');
    \redirect($returnPath);
  }

  private function renderByType(string $type): void
  {
    \requireAuth();

    $profile = \getAuthenticatedUserProfile();
    if ($profile === null) {
      \redirect('/login');
    }

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $path = $this->pathByType($type);
    $csrf = $this->ensureCsrfToken();
    $notice = \getFlash('transactions_notice');
    $error = \getFlash('transactions_error');

    $categoryModel = new Category();
    $transactionModel = new Transaction();
    $editingTransaction = null;
    $period = $transactionModel->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $statsData = [];
    $chartData = [];
    $transactionsByMonth = [];
    $topCategories = [];

    try {
      $pdo = \getPdo();
      $categories = $categoryModel->getForUserByType($pdo, $userId, $type);
      $transactions = $transactionModel->getRecentForUserByType($pdo, $userId, $type, 50, $period);
      $editingTransaction = $this->resolveEditingTransaction($pdo, $transactionModel, $userId, $type, $path);

      if ($type === self::INCOME_TYPE) {
        $statsData = $transactionModel->getIncomeStatsForUser($pdo, $userId, $period);
        $rawChartData = $transactionModel->getIncomeByCategoryMonthlyForUser($pdo, $userId, $period);
        $transactionsByMonth = $transactionModel->getIncomeGroupedByMonthForUser($pdo, $userId, $period);
        $chartData = $this->buildIncomeChartData($rawChartData);
      } elseif ($type === self::EXPENSE_TYPE) {
        $statsData = $transactionModel->getExpenseStatsForUser($pdo, $userId, $period);
        $topCategories = $transactionModel->getExpensesByCategoryForPeriod($pdo, $userId, 5, $period);
        $transactionsByMonth = $transactionModel->getExpensesGroupedByMonthForUser($pdo, $userId, $period);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'index_by_type', 'user_id' => $userId, 'type' => $type]);
      $categories = [];
      $transactions = [];
      $error = 'Не удалось загрузить транзакции. Попробуйте позже.';
      $statsData = [];
      $chartData = [];
      $transactionsByMonth = [];
      $topCategories = [];
    }

    $formAction = $type === self::INCOME_TYPE ? '/income' : '/expenses';
    $viewName = $type === self::INCOME_TYPE ? 'income' : 'expenses';
    $pageTitle = $type === self::INCOME_TYPE ? 'Доходы' : 'Расходы';
    $formTitle = $type === self::INCOME_TYPE ? 'Добавить доход' : 'Добавить расход';

    header('Content-Type: text/html; charset=utf-8');
    \render($viewName, [
      'userName' => $profile['name'],
      'userEmail' => $profile['email'],
      'csrf' => $csrf,
      'categories' => $categories,
      'transactions' => $transactions,
      'editingTransaction' => $editingTransaction,
      'notice' => $notice,
      'error' => $error,
      'formAction' => $formAction,
      'pageTitle' => $pageTitle,
      'formTitle' => $formTitle,
      'currentType' => $type,
      'currentPath' => $path,
      'selectedPeriod' => $period,
      'statsData' => $type === self::INCOME_TYPE
        ? $this->normalizeIncomeStats($statsData)
        : $this->normalizeExpenseStats($statsData),
      'chartData' => $chartData,
      'topCategories' => $topCategories,
      'transactionsByMonth' => $this->buildMonthGroupsForView($transactionsByMonth),
    ]);
  }

  /**
   * @param array{months?:array<int,string>,series?:array<int,array{name?:string,data?:array<int,float|int|string>}>} $rawChartData
   * @return array{months:array<int,string>,series:array<int,array{name:string,data:array<int,float>}>}
   */
  private function buildIncomeChartData(array $rawChartData): array
  {
    $months = isset($rawChartData['months']) && is_array($rawChartData['months']) ? $rawChartData['months'] : [];
    $seriesRaw = isset($rawChartData['series']) && is_array($rawChartData['series']) ? $rawChartData['series'] : [];
    $series = [];

    foreach ($seriesRaw as $item) {
      if (!is_array($item)) {
        continue;
      }

      $name = isset($item['name']) ? trim((string) $item['name']) : '';
      if ($name === '') {
        continue;
      }

      $pointsRaw = isset($item['data']) && is_array($item['data']) ? $item['data'] : [];
      $points = [];
      foreach ($pointsRaw as $point) {
        $points[] = (float) $point;
      }

      $series[] = [
        'name' => $name,
        'data' => $points,
      ];
    }

    return [
      'months' => array_map([$this, 'formatMonthKeyShort'], $months),
      'series' => $series,
    ];
  }

  /**
   * @param array<string, array<int, array<string, mixed>>> $transactionsByMonth
   * @return array<int, array{month_key:string, month_label:string, transactions:array<int, array<string, mixed>>}>
   */
  private function buildMonthGroupsForView(array $transactionsByMonth): array
  {
    if ($transactionsByMonth === []) {
      return [];
    }

    krsort($transactionsByMonth);
    $groups = [];

    foreach ($transactionsByMonth as $monthKey => $items) {
      $groups[] = [
        'month_key' => (string) $monthKey,
        'month_label' => $this->formatMonthKey((string) $monthKey),
        'transactions' => is_array($items) ? $items : [],
      ];
    }

    return $groups;
  }

  /**
   * @param array{total?:string, top_source_name?:string, avg_per_month?:string} $statsData
   * @return array{total:string, top_source_name:string, avg_per_month:string}
   */
  private function normalizeIncomeStats(array $statsData): array
  {
    $topSource = isset($statsData['top_source_name']) ? trim((string) $statsData['top_source_name']) : '';

    return [
      'total' => isset($statsData['total']) ? (string) $statsData['total'] : '0.00',
      'top_source_name' => $topSource !== '' ? $topSource : self::EMPTY_TOP_SOURCE,
      'avg_per_month' => isset($statsData['avg_per_month']) ? (string) $statsData['avg_per_month'] : '0',
    ];
  }

  /**
   * @param array{total?:string, top_category_name?:string, avg_per_month?:string} $statsData
   * @return array{total:string, top_category_name:string, avg_per_month:string}
   */
  private function normalizeExpenseStats(array $statsData): array
  {
    $topCategory = isset($statsData['top_category_name']) ? trim((string) $statsData['top_category_name']) : '';

    return [
      'total' => isset($statsData['total']) ? (string) $statsData['total'] : '0.00',
      'top_category_name' => $topCategory !== '' ? $topCategory : self::EMPTY_TOP_CATEGORY,
      'avg_per_month' => isset($statsData['avg_per_month']) ? (string) $statsData['avg_per_month'] : '0',
    ];
  }

  private function formatMonthKey(string $monthKey): string
  {
    $date = \DateTimeImmutable::createFromFormat('Y-m', $monthKey);
    if ($date === false) {
      return $monthKey;
    }

    $months = [
      1 => 'Январь',
      2 => 'Февраль',
      3 => 'Март',
      4 => 'Апрель',
      5 => 'Май',
      6 => 'Июнь',
      7 => 'Июль',
      8 => 'Август',
      9 => 'Сентябрь',
      10 => 'Октябрь',
      11 => 'Ноябрь',
      12 => 'Декабрь',
    ];
    $monthNumber = (int) $date->format('n');
    $monthName = $months[$monthNumber] ?? $monthKey;

    return $monthName . ' ' . $date->format('Y');
  }

  private function formatMonthKeyShort(string $monthKey): string
  {
    $date = \DateTimeImmutable::createFromFormat('Y-m', $monthKey);
    if ($date === false) {
      return $monthKey;
    }

    $months = [
      1 => 'Янв',
      2 => 'Фев',
      3 => 'Мар',
      4 => 'Апр',
      5 => 'Май',
      6 => 'Июн',
      7 => 'Июл',
      8 => 'Авг',
      9 => 'Сен',
      10 => 'Окт',
      11 => 'Ноя',
      12 => 'Дек',
    ];
    $monthNumber = (int) $date->format('n');

    return (string) ($months[$monthNumber] ?? $monthKey);
  }

  private function storeByType(string $type): void
  {
    \requireAuth();

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $path = $this->pathByType($type);
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash('transactions_error', 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect($path);
    }

    $amountRaw = (string) \input('amount', '');
    $categoryIdRaw = (string) \input('category_id', '');
    $date = (string) \input('date', '');
    $comment = (string) \input('comment', '');

    $validationError = $this->validateInput($amountRaw, $categoryIdRaw, $date, $comment);
    if ($validationError !== null) {
      \setFlash('transactions_error', $validationError);
      \redirect($path);
    }

    $categoryId = (int) $categoryIdRaw;
    $amount = number_format((float) $amountRaw, 2, '.', '');

    $categoryModel = new Category();
    $transactionModel = new Transaction();

    try {
      $pdo = \getPdo();
      $categories = $categoryModel->getForUserByType($pdo, $userId, $type);
      $categoryIds = array_map(
        static fn(array $category): int => (int) ($category['id'] ?? 0),
        $categories
      );

      if (!in_array($categoryId, $categoryIds, true)) {
        \setFlash('transactions_error', 'Выбрана недоступная категория.');
        \redirect($path);
      }

      $saved = $transactionModel->create(
        $pdo,
        $userId,
        $categoryId,
        $amount,
        $date,
        $comment
      );
      if (!$saved) {
        \setFlash('transactions_error', 'Не удалось сохранить транзакцию.');
        \redirect($path);
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'store', 'user_id' => $userId, 'type' => $type]);
      \setFlash('transactions_error', 'Ошибка сервера при сохранении транзакции.');
      \redirect($path);
    }

    \setFlash('transactions_notice', 'Транзакция успешно добавлена.');
    \redirect($path);
  }

  private function resolveEditingTransaction(
    \PDO $pdo,
    Transaction $transactionModel,
    int $userId,
    string $type,
    string $path
  ): ?array {
    $editingIdRaw = (string) (\query('edit', '') ?? '');
    if ($editingIdRaw === '') {
      return null;
    }

    $editingId = $this->normalizePositiveIntId($editingIdRaw);
    if ($editingId === null) {
      \setFlash('transactions_error', 'Некорректный идентификатор транзакции для редактирования.');
      \redirect($path);
    }

    $editingTransaction = $transactionModel->findByIdForUser($pdo, $editingId, $userId);
    if ($editingTransaction === null) {
      \setFlash('transactions_error', 'Транзакция для редактирования недоступна.');
      \redirect($path);
    }

    $editingType = isset($editingTransaction['category_type']) ? (string) $editingTransaction['category_type'] : '';
    if ($editingType !== $type) {
      \setFlash('transactions_error', 'Транзакция для редактирования относится к другому разделу.');
      \redirect($this->pathByType($editingType));
    }

    return $editingTransaction;
  }

  private function normalizePositiveIntId(string $rawValue): ?int
  {
    if (!ctype_digit($rawValue)) {
      return null;
    }

    $value = (int) $rawValue;

    return $value > 0 ? $value : null;
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

  private function validateInput(string $amountRaw, string $categoryIdRaw, string $date, string $comment): ?string
  {
    if ($amountRaw === '' || $categoryIdRaw === '' || $date === '') {
      return 'Заполните обязательные поля: сумма, категория и дата.';
    }

    if (!is_numeric($amountRaw)) {
      return 'Сумма должна быть числом.';
    }

    $amount = (float) $amountRaw;
    if ($amount <= 0) {
      return 'Сумма должна быть больше нуля.';
    }

    if (!ctype_digit($categoryIdRaw) || (int) $categoryIdRaw <= 0) {
      return 'Выберите корректную категорию.';
    }

    $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
      return 'Укажите корректную дату.';
    }

    $today = new \DateTimeImmutable();
    $todayString = $today->format('Y-m-d');
    if ($date > $todayString) {
      return 'Дата транзакции не может быть в будущем.';
    }

    if (mb_strlen($comment) > self::MAX_COMMENT_LENGTH) {
      return 'Комментарий не должен превышать 255 символов.';
    }

    return null;
  }

  private function resolveReturnPath(string $rawPath): string
  {
    return match ($rawPath) {
      '/income' => '/income',
      '/expenses' => '/expenses',
      default => self::DEFAULT_RETURN_PATH,
    };
  }

  private function resolveTypeFromInputPath(string $path): string
  {
    return $path === '/expenses' ? self::EXPENSE_TYPE : self::INCOME_TYPE;
  }

  private function pathByType(string $type): string
  {
    return $type === self::EXPENSE_TYPE ? '/expenses' : '/income';
  }
}
