<?php

namespace Drupal\sp_create;

use Psr\Log\LoggerInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Class CreatePlanLogger.
 */
class DebugLogger {
  use LoggerTrait;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Whether to log debug messages.
   *
   * @var string
   */
  protected $debugInfo;

  /**
   * Drupal style instance.
   *
   * @var \Drupal\Console\Core\Style\DrupalStyle
   */
  protected $io;

  /**
   * DebugLogger constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger object.
   * @param bool $debugInfo
   *   If true, debug messages will be logged.
   * @param \Drupal\Console\Core\Style\DrupalStyle|null $io
   *   Optional object to allow for console output.
   */
  public function __construct(LoggerInterface $logger, $debugInfo = FALSE, DrupalStyle $io = NULL) {
    $this->logger = $logger;
    $this->debugInfo = $debugInfo;
    $this->io = $io;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   *   See LoggerTrait for possible values.
   * @param string $message
   *   A translatable string with replacement tokens.
   * @param array $context
   *   Replacement token values.
   */
  public function log($level, $message, array $context = []) {
    if (NULL !== $this->io) {
      // @codingStandardsIgnoreStart
      $this->io->info(t($message, $context));
      // @codingStandardsIgnoreEnd
    }
    // Only log messages if debugInfo is true.
    if (TRUE === $this->debugInfo || $level !== LogLevel::DEBUG) {
      $this->logger->log($level, $message, $context);
    }
  }

}
