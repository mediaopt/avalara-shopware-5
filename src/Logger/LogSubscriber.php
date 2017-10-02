<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Logger;

use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Subscriber\Log\SimpleLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Copy of GuzzleHttp\Subscriber\Log\LogSubscriber with slight changes (thx private properties...)
 *
 * Plugin class that will add request and response logging to an HTTP request.
 *
 * The log plugin uses a message formatter that allows custom messages via
 * template variable substitution.
 *
 * @see MessageLogger for a list of available template variable substitutions
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Logger
 */

class LogSubscriber implements SubscriberInterface
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var Formatter Formatter used to format log messages */
    protected $formatter;
    
    /**
     *
     * @var int
     */
    protected $timestampBefore;
    
    /**
     *
     * @var \GuzzleHttp\Message\Response
     */
    protected $lastResponseWithError;

    /**
     * @param LoggerInterface|callable|resource|null $logger Logger used to log
     *     messages. Pass a LoggerInterface to use a PSR-3 logger. Pass a
     *     callable to log messages to a function that accepts a string of
     *     data. Pass a resource returned from ``fopen()`` to log to an open
     *     resource. Pass null or leave empty to write log messages using
     *     ``echo()``.
     * @param string|Formatter $formatter Formatter used to format log
     *     messages or a string representing a message formatter template.
     */
    public function __construct($logger = null, $formatter = null)
    {
        $this->logger = $logger instanceof LoggerInterface ? $logger : new SimpleLogger($logger);

        $this->formatter = $formatter instanceof Formatter ? $formatter : new Formatter($formatter);
    }

    /**
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            'before' => ['onBefore', RequestEvents::EARLY],
            'complete' => ['onComplete', RequestEvents::VERIFY_RESPONSE - 10],
            'error' => ['onError', RequestEvents::EARLY],
        ];
    }

    /**
     *
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        $this->logger->log(
            $this->getLogLevel($event),
            $this->formatter->format(
                $event->getRequest(),
                $event->getResponse(),
                null,
                [
                            'processingTime' => $this->getProcessingTime(),
                        ]
            ),
            [
            'request' => $event->getRequest(),
            'response' => $event->getResponse()
                ]
        );
    }

    /**
     *
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        $this->formatter->setTemplate(\GuzzleHttp\Subscriber\Log\Formatter::DEBUG);
        $ex = $event->getException();
        $this->logger->log(
            LogLevel::CRITICAL,
            $this->formatter->format(
                $event->getRequest(),
                $event->getResponse(),
                $ex,
                [
                    'processingTime' => $this->getProcessingTime(),
                ]
            ),
            [
                'request' => $event->getRequest(),
                'response' => $event->getResponse(),
                'exception' => $ex
            ]
        );
        
        $this->lastResponseWithError = $event->getResponse();
        $this->formatter->resetTemplate();
    }
    
    /**
     *
     * @return \GuzzleHttp\Message\Response
     */
    public function getLastResponseWithError()
    {
        return $this->lastResponseWithError;
    }
    
    /**
     * Will set last timestamp
     * @param BeforeEvent $event
     */
    public function onBefore(BeforeEvent $event)
    {
        $this->timestampBefore = microtime(true);
    }
    
    /**
     *
     * @return int
     */
    protected function getProcessingTime()
    {
        return number_format(microtime(true) - $this->timestampBefore, 2);
    }

    /**
     * @param CompleteEvent $event
     * @return string
     */
    private function getLogLevel(CompleteEvent $event)
    {
        return 0 === strpos($event->getResponse()->getStatusCode(), '2')
            ? LogLevel::INFO
            : LogLevel::WARNING
        ;
    }
}
