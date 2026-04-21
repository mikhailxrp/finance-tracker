<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Transaction;
use Throwable;

/**
 * Дашборд с агрегатами пользователя за всё время.
 */
final class DashboardController
{
  private const DEFAULT_PERIOD = 'month';

  public function index(): void
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

    $transactionModel = new Transaction();
    $period = $transactionModel->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $totals = [
      'total_income' => '0.00',
      'total_expense' => '0.00',
      'balance' => '0.00',
    ];
    $topExpenseCategory = null;
    $recentTransactions = [];
    $chartData = [
      'expense_categories' => [],
      'daily_dynamics' => [],
    ];
    $dashboardError = null;

    try {
      $pdo = \getPdo();
      $totals = $transactionModel->getDashboardTotalsForUser($pdo, $userId, $period);
      $topExpenseCategory = $transactionModel->getTopExpenseCategoryForUser($pdo, $userId, $period);
      $recentTransactions = $transactionModel->getRecentForUser($pdo, $userId, 5, $period);
      $expenseCategories = \method_exists($transactionModel, 'getExpensesByCategoryForPeriod')
        ? $transactionModel->{'getExpensesByCategoryForPeriod'}($pdo, $userId, 5, $period)
        : [];
      $dailyDynamics = \method_exists($transactionModel, 'getDailyDynamicsForPeriod')
        ? $transactionModel->{'getDailyDynamicsForPeriod'}($pdo, $userId, $period)
        : [];
      $chartData = [
        'expense_categories' => is_array($expenseCategories) ? $expenseCategories : [],
        'daily_dynamics' => is_array($dailyDynamics) ? $dailyDynamics : [],
      ];
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'dashboard_index', 'user_id' => $userId]);
      $dashboardError = 'Не удалось загрузить данные дашборда. Попробуйте позже.';
    }

    header('Content-Type: text/html; charset=utf-8');
    \render('dashboard', [
      'userName' => $profile['name'],
      'userEmail' => $profile['email'],
      'totals' => $totals,
      'topExpenseCategory' => $topExpenseCategory,
      'recentTransactions' => $recentTransactions,
      'chartData' => $chartData,
      'dashboardError' => $dashboardError,
      'selectedPeriod' => $period,
      'currentPath' => '/dashboard',
    ]);
  }
};
