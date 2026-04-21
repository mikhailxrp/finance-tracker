<?php

declare(strict_types=1);

require_once __DIR__ . '/Request.php';

/**
 * Загружает маршруты из файла config/routes.php.
 *
 * Ожидаемый формат:
 * [
 *   'GET' => [
 *     '/' => ['DashboardController', 'index'],
 *   ],
 *   'POST' => [
 *     '/login' => ['AuthController', 'login'],
 *   ],
 * ]
 */
function loadRoutes(string $routesFile): array
{
  if (!is_readable($routesFile)) {
    throw new RuntimeException("Файл маршрутов недоступен: {$routesFile}");
  }

  $routes = require $routesFile;
  if (!is_array($routes)) {
    throw new RuntimeException('config/routes.php должен возвращать массив маршрутов.');
  }

  return $routes;
}

/**
 * Ищет обработчик маршрута по текущим метод/пути.
 */
function matchRoute(array $routes, string $method, string $path): ?array
{
  $methodRoutes = $routes[$method] ?? null;
  if (!is_array($methodRoutes)) {
    return null;
  }

  foreach ($methodRoutes as $pattern => $handler) {
    if (!is_string($pattern) || (!is_callable($handler) && !is_array($handler))) {
      continue;
    }

    $params = [];
    if (matchPattern($pattern, $path, $params)) {
      return [
        'handler' => $handler,
        'params' => $params,
      ];
    }
  }

  return null;
}

/**
 * Проверяет путь по шаблону маршрута и извлекает именованные параметры.
 */
function matchPattern(string $pattern, string $path, array &$params): bool
{
  $regex = compileRoutePattern($pattern);
  if (preg_match($regex, $path, $matches) !== 1) {
    return false;
  }

  $params = [];
  foreach ($matches as $key => $value) {
    if (!is_int($key) && is_string($value)) {
      $params[$key] = $value;
    }
  }

  return true;
}

/**
 * Преобразует /resource/{id} в регулярное выражение с именованными группами.
 */
function compileRoutePattern(string $pattern): string
{
  $escaped = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
  $escapedPattern = is_string($escaped) ? $escaped : $pattern;
  return '#^' . $escapedPattern . '$#';
}

/**
 * Проверяет, существует ли путь в других HTTP-методах.
 */
function pathExistsInOtherMethod(array $routes, string $method, string $path): bool
{
  foreach ($routes as $routeMethod => $methodRoutes) {
    if (!is_string($routeMethod) || strtoupper($routeMethod) === $method || !is_array($methodRoutes)) {
      continue;
    }

    foreach ($methodRoutes as $pattern => $handler) {
      if (!is_string($pattern) || (!is_callable($handler) && !is_array($handler))) {
        continue;
      }

      $params = [];
      if (matchPattern($pattern, $path, $params)) {
        return true;
      }
    }
  }

  return false;
}

/**
 * Вызывает callable с параметрами маршрута по именам аргументов.
 */
function invokeCallable(callable $handler, array $params): mixed
{
  $closure = Closure::fromCallable($handler);
  $reflection = new ReflectionFunction($closure);
  $args = buildArguments($reflection->getParameters(), $params);

  return $closure(...$args);
}

/**
 * Вызывает метод контроллера с параметрами маршрута по именам аргументов.
 */
function invokeControllerAction(object $controller, string $action, array $params): mixed
{
  $reflection = new ReflectionMethod($controller, $action);
  $args = buildArguments($reflection->getParameters(), $params);

  return $reflection->invokeArgs($controller, $args);
}

/**
 * Строит аргументы на основе сигнатуры функции/метода.
 */
function buildArguments(array $parameters, array $params): array
{
  $args = [];
  foreach ($parameters as $parameter) {
    $name = $parameter->getName();
    if (array_key_exists($name, $params)) {
      $args[] = $params[$name];
      continue;
    }

    if ($parameter->isDefaultValueAvailable()) {
      $args[] = $parameter->getDefaultValue();
      continue;
    }

    throw new RuntimeException("Отсутствует обязательный параметр маршрута: {$name}");
  }

  return $args;
}

/**
 * Выполняет найденный обработчик или отдаёт 404/500.
 */
function dispatch(array $routes): void
{
  $method = requestMethod();
  $path = requestPath();

  $match = matchRoute($routes, $method, $path);
  if ($match === null) {
    $statusCode = pathExistsInOtherMethod($routes, $method, $path) ? 405 : 404;
    http_response_code($statusCode);
    echo $statusCode === 405 ? '405 Method Not Allowed' : '404 Not Found';
    return;
  }

  $handler = $match['handler'];
  $params = $match['params'];

  if (is_callable($handler)) {
    invokeCallable($handler, is_array($params) ? $params : []);
    return;
  }

  // Формат ['ControllerName', 'method'].
  [$controllerName, $action] = $handler;
  if (!is_string($controllerName) || !is_string($action)) {
    throw new RuntimeException('Неверный формат обработчика маршрута.');
  }

  $controllerClass = "App\\Controllers\\{$controllerName}";
  if (!class_exists($controllerClass)) {
    throw new RuntimeException("Контроллер не найден: {$controllerClass}");
  }

  $controller = new $controllerClass();
  if (!method_exists($controller, $action)) {
    throw new RuntimeException("Метод {$action} не найден в {$controllerClass}");
  }

  invokeControllerAction($controller, $action, is_array($params) ? $params : []);
}
