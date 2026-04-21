<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

/**
 * Отправка писем через SMTP (Yandex и аналоги).
 * Настройки только из .env: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 * MAIL_FROM, MAIL_FROM_NAME, MAIL_ENCRYPTION (ssl|tls).
 */
final class MailService
{
  private const SMTP_TIMEOUT_SECONDS = 30;

  /** @var resource|null */
  private $socket = null;

  public function send(string $to, string $subject, string $bodyText): bool
  {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
      \log_error('MailService: некорректный адрес получателя');
      return false;
    }

    $host = \env('MAIL_HOST');
    $port = (int) \env('MAIL_PORT');
    $user = \env('MAIL_USERNAME');
    $password = \env('MAIL_PASSWORD');
    $from = \env('MAIL_FROM');
    $fromName = \env('MAIL_FROM_NAME', 'FinanceTracker');
    $encryption = strtolower(\env('MAIL_ENCRYPTION', 'ssl'));

    if ($port <= 0 || $port > 65535) {
      \log_error('MailService: некорректный MAIL_PORT');
      return false;
    }

    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
      \log_error('MailService: некорректный MAIL_FROM');
      return false;
    }

    if ($encryption !== 'ssl' && $encryption !== 'tls') {
      \log_error('MailService: MAIL_ENCRYPTION должен быть ssl или tls');
      return false;
    }

    try {
      if ($encryption === 'ssl') {
        $this->connectSsl($host, $port);
      } else {
        $this->connectStartTls($host, $port);
      }

      $this->expectCode([220]);
      $this->ehlo(self::smtpHostname());

      if ($encryption === 'tls') {
        $this->cmd('STARTTLS', [220]);
        $cryptoOk = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoOk !== true) {
          throw new \RuntimeException('Не удалось включить TLS');
        }
        $this->ehlo(self::smtpHostname());
      }

      $this->cmd('AUTH LOGIN', [334]);
      $this->cmd(base64_encode($user), [334]);
      $this->cmd(base64_encode($password), [235]);

      $this->cmd('MAIL FROM:<' . $from . '>', [250]);
      $this->cmd('RCPT TO:<' . $to . '>', [250, 251]);

      $this->cmd('DATA', [354]);
      $payload = $this->buildMessage($from, $fromName, $to, $subject, $bodyText);
      $this->writeDataBlock($payload);
      $this->expectCode([250]);

      $this->cmd('QUIT', [221]);
    } catch (Throwable $e) {
      \log_exception($e, ['context' => 'MailService::send']);
      $this->closeSocket();
      return false;
    }

    $this->closeSocket();
    return true;
  }

  private function connectSsl(string $host, int $port): void
  {
    $context = stream_context_create([
      'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
      ],
    ]);

    $remote = 'ssl://' . $host . ':' . $port;
    $socket = @stream_socket_client(
      $remote,
      $errno,
      $errstr,
      self::SMTP_TIMEOUT_SECONDS,
      STREAM_CLIENT_CONNECT,
      $context
    );

    if (!is_resource($socket)) {
      throw new \RuntimeException("SMTP SSL: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, self::SMTP_TIMEOUT_SECONDS);
    $this->socket = $socket;
  }

  private function connectStartTls(string $host, int $port): void
  {
    $remote = 'tcp://' . $host . ':' . $port;
    $socket = @stream_socket_client(
      $remote,
      $errno,
      $errstr,
      self::SMTP_TIMEOUT_SECONDS,
      STREAM_CLIENT_CONNECT
    );

    if (!is_resource($socket)) {
      throw new \RuntimeException("SMTP TCP: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, self::SMTP_TIMEOUT_SECONDS);
    $this->socket = $socket;
  }

  private function ehlo(string $hostname): void
  {
    $this->cmd('EHLO ' . $hostname, [250]);
  }

  /**
   * @param list<int> $expectedCodes
   */
  private function cmd(string $line, array $expectedCodes): void
  {
    $this->sendLine($line);
    $this->expectCode($expectedCodes);
  }

  /**
   * @param list<int> $expectedCodes
   */
  private function expectCode(array $expectedCodes): void
  {
    $response = $this->readResponse();
    $code = $this->parseResponseCode($response);
    if (!in_array($code, $expectedCodes, true)) {
      throw new \RuntimeException('SMTP неожиданный ответ: ' . trim(str_replace(["\r", "\n"], ' ', $response)));
    }
  }

  private function readResponse(): string
  {
    if (!is_resource($this->socket)) {
      throw new \RuntimeException('SMTP: сокет не открыт');
    }

    $buffer = '';
    while (true) {
      $line = fgets($this->socket, 8192);
      if ($line === false) {
        break;
      }
      $buffer .= $line;
      if (strlen($line) >= 4 && $line[3] === ' ') {
        break;
      }
    }

    if ($buffer === '') {
      throw new \RuntimeException('SMTP: пустой ответ сервера');
    }

    return $buffer;
  }

  private function parseResponseCode(string $response): int
  {
    if (strlen($response) < 3 || !ctype_digit(substr($response, 0, 3))) {
      return 0;
    }

    return (int) substr($response, 0, 3);
  }

  private function sendLine(string $line): void
  {
    if (!is_resource($this->socket)) {
      throw new \RuntimeException('SMTP: сокет не открыт');
    }

    $written = fwrite($this->socket, $line . "\r\n");
    if ($written === false) {
      throw new \RuntimeException('SMTP: ошибка записи');
    }
  }

  private function writeDataBlock(string $payload): void
  {
    if (!is_resource($this->socket)) {
      throw new \RuntimeException('SMTP: сокет не открыт');
    }

    $written = fwrite($this->socket, $payload . "\r\n.\r\n");
    if ($written === false) {
      throw new \RuntimeException('SMTP: ошибка записи DATA');
    }
  }

  private function buildMessage(string $from, string $fromName, string $to, string $subject, string $bodyText): string
  {
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $body = $this->dotStuff($bodyText);

    $headers = [
      'From: ' . $encodedFromName . ' <' . $from . '>',
      'To: <' . $to . '>',
      'Subject: ' . $encodedSubject,
      'MIME-Version: 1.0',
      'Content-Type: text/plain; charset=UTF-8',
      'Content-Transfer-Encoding: 8bit',
    ];

    return implode("\r\n", $headers) . "\r\n\r\n" . $body;
  }

  private function dotStuff(string $body): string
  {
    $normalized = str_replace(["\r\n", "\r"], "\n", $body);
    $lines = explode("\n", $normalized);
    $out = [];
    foreach ($lines as $line) {
      if ($line !== '' && $line[0] === '.') {
        $line = '.' . $line;
      }
      $out[] = $line;
    }

    return implode("\r\n", $out);
  }

  private function closeSocket(): void
  {
    if (is_resource($this->socket)) {
      fclose($this->socket);
    }
    $this->socket = null;
  }

  private static function smtpHostname(): string
  {
    if (!\defined('APP_URL')) {
      return 'localhost';
    }

    $host = parse_url((string) \APP_URL, PHP_URL_HOST);
    return is_string($host) && $host !== '' ? $host : 'localhost';
  }
}
