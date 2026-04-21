<?php

declare(strict_types=1);

/**
 * Маршруты: метод → путь → [ИмяКонтроллера без namespace, метод] или callable.
 */

return [
  'GET' => [
    '/' => ['DashboardController', 'index'],
    '/dashboard' => ['DashboardController', 'index'],
    '/transactions' => ['TransactionController', 'index'],
    '/income' => ['TransactionController', 'income'],
    '/expenses' => ['TransactionController', 'expenses'],
    '/savings' => ['GoalController', 'index'],
    '/credits' => ['CreditController', 'index'],
    '/login' => ['AuthController', 'showLogin'],
    '/register' => ['AuthController', 'showRegister'],
    '/forgot-password' => ['AuthController', 'showForgotPassword'],
    '/reset-password/{token}' => ['AuthController', 'showResetPassword'],
    '/verify-email' => ['AuthController', 'showVerifyEmail'],
    '/verify-email/{token}' => ['AuthController', 'verifyEmail'],
    '/transaction/{id}' => ['TransactionController', 'show'],
    '/transaction/{id}/edit' => ['TransactionController', 'edit'],
    '/category/{id}/delete' => ['CategoryController', 'delete'],
  ],
  'POST' => [
    '/transactions' => ['TransactionController', 'store'],
    '/income' => ['TransactionController', 'storeIncome'],
    '/expenses' => ['TransactionController', 'storeExpense'],
    '/savings/create' => ['GoalController', 'create'],
    '/savings/update' => ['GoalController', 'update'],
    '/savings/delete' => ['GoalController', 'delete'],
    '/savings/contribute' => ['GoalController', 'contribute'],
    '/savings/status' => ['GoalController', 'setStatus'],
    '/credits/create' => ['CreditController', 'create'],
    '/credits/update' => ['CreditController', 'update'],
    '/credits/delete' => ['CreditController', 'delete'],
    '/credits/close' => ['CreditController', 'close'],
    '/purchase-plans/create' => ['PurchasePlanController', 'create'],
    '/purchase-plans/delete' => ['PurchasePlanController', 'delete'],
    '/purchase-plans/convert' => ['PurchasePlanController', 'convert'],
    '/transaction/{id}/update' => ['TransactionController', 'update'],
    '/transaction/{id}/delete' => ['TransactionController', 'delete'],
    '/login' => ['AuthController', 'login'],
    '/register' => ['AuthController', 'register'],
    '/logout' => ['AuthController', 'logout'],
    '/forgot-password' => ['AuthController', 'sendResetLink'],
    '/reset-password' => ['AuthController', 'resetPassword'],
    '/verify-email/resend' => ['AuthController', 'resendVerification'],
  ],
];
