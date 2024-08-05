<?php

declare(strict_types=1);

namespace Drupal\psul_splunk_logger\Logger;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Output errors to stdout so splunk can consume them.
 */
final class SplunkLogger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * Constructs a SplunkLogger object.
   */
  public function __construct(
    private readonly LogMessageParserInterface $parser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    global $base_url;

    $output = fopen('php://stdout', 'w');
    $severity = RfcLogLevel::getLevels()[$level];

    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    // Handle log messages that are not string.
    if ($message instanceof TranslatableMarkup) {
      $message = $message->__toString();
    }
    elseif ($message instanceof FormattableMarkup) {
      $message = $message->__toString();
    }
    else {
      // This is a string so replace placeholders.
      $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
    }

    $link = $context['link'];

    if ($link instanceof GeneratedLink) {
      $link = $link->__toString();
    }

    // JSON formatted needed for splunk.
    $format = '{"app": "drupal", "severity": "!severity", "type": "!type", "date": "!date", "message": "!message",  "uid": "!uid",  "request-uri": "!request_uri", "refer": "!referer", "ip":  "!ip",  "link": "!link"}';

    $entry = strtr($format, [
      '!base_url'    => $base_url,
      '!timestamp'   => $context['timestamp'],
      '!severity'    => $severity,
      '!type'        => $context['channel'],
      '!message'     => strip_tags($message),
      '!uid'         => $context['uid'],
      '!request_uri' => $context['request_uri'],
      '!referer'     => $context['referer'],
      '!ip'          => $context['ip'],
      '!link'        => strip_tags($link),
      '!date'        => date('Y-m-d\TH:i:s', $context['timestamp']),
    ]);

    fwrite($output, $entry . "\r\n");
    fclose($output);
  }

}
