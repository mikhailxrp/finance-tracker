<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/functions.php';

final class CategoryController
{
  public function delete(string $id): void
  {
    \requireAuth();

    header('Content-Type: text/plain; charset=utf-8');
    echo "Category delete: {$id}";
  }
}
