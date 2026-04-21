<?php

declare(strict_types=1);

/**
 * Инициализация приложения: один раз загружаем `.env` и объявляем константы.
 * Подключать первым во входной точке (например `public/index.php`, CLI-скрипты).
 */

require_once dirname(__DIR__) . '/src/Core/functions.php';

loadEnv(dirname(__DIR__) . '/.env');

define('ROOT_PATH', dirname(__DIR__));
define('APP_ENV', env('APP_ENV'));
define('APP_URL', env('APP_URL'));

/** Каталог логов (по умолчанию `storage/logs` от корня проекта). */
define('LOG_DIR', env('LOG_DIR', ROOT_PATH . '/storage/logs'));
/** Имя файла лога внутри `LOG_DIR`. */
define('LOG_FILE', env('LOG_FILE', 'app.log'));
/**
 * Минимальный уровень записи: debug | info | warning | error.
 * Сообщения ниже порога отбрасываются.
 */
define('APP_LOG_LEVEL', strtolower(env('APP_LOG_LEVEL', 'error')));

require_once dirname(__DIR__) . '/src/Core/Logger.php';
