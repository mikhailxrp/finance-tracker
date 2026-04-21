<?php

declare(strict_types=1);

$transactionId = isset($transactionId) ? (int) $transactionId : 0;
$deleteModalId = isset($deleteModalId) && is_string($deleteModalId) ? $deleteModalId : '';
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/income';

if ($transactionId <= 0 || $deleteModalId === '') {
  return;
}
?>
<div
  class="modal fade"
  id="<?= htmlspecialchars($deleteModalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
  tabindex="-1"
  aria-labelledby="<?= htmlspecialchars($deleteModalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>Label"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title fs-5" id="<?= htmlspecialchars($deleteModalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>Label">
          Подтверждение удаления
        </h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        Вы уверены, что хотите удалить эту транзакцию? Действие нельзя отменить.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <form method="POST" action="/transaction/<?= $transactionId ?>/delete">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <input type="hidden" name="return_to" value="<?= htmlspecialchars($currentPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <button type="submit" class="btn btn-danger">Удалить</button>
        </form>
      </div>
    </div>
  </div>
</div>
