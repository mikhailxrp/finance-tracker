<?php

declare(strict_types=1);

/**
 * Выполняет POST JSON-запрос через cURL.
 *
 * @return array{ok: bool, status: int, body: string, error: ?string}
 */
function postJson(string $url, array $payload, int $timeout = 360, ?string $bearerToken = null): array
{
  if ($url === '') {
    return [
      'ok' => false,
      'status' => 0,
      'body' => '',
      'error' => 'Webhook URL is empty.',
    ];
  }

  $ch = curl_init($url);
  if ($ch === false) {
    return [
      'ok' => false,
      'status' => 0,
      'body' => '',
      'error' => 'Failed to initialize cURL.',
    ];
  }

  $headers = [
    'Content-Type: application/json',
    'Accept: text/plain, application/json;q=0.9, */*;q=0.8',
  ];

  if ($bearerToken !== null && $bearerToken !== '') {
    $headers[] = 'Authorization: Bearer ' . $bearerToken;
  }

  $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($encodedPayload === false) {
    curl_close($ch);
    return [
      'ok' => false,
      'status' => 0,
      'body' => '',
      'error' => 'Failed to encode JSON payload.',
    ];
  }

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $encodedPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => min(30, max(1, $timeout)),
    CURLOPT_TIMEOUT => max(1, $timeout),
  ]);

  $responseBody = curl_exec($ch);
  $curlError = curl_error($ch);
  $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  if (!is_string($responseBody)) {
    return [
      'ok' => false,
      'status' => $statusCode,
      'body' => '',
      'error' => $curlError !== '' ? $curlError : 'Unknown cURL error.',
    ];
  }

  return [
    'ok' => $curlError === '' && $statusCode >= 200 && $statusCode < 300,
    'status' => $statusCode,
    'body' => trim($responseBody),
    'error' => $curlError !== '' ? $curlError : null,
  ];
}
