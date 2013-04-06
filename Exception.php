<?php

/*
 * (c) Markus Lanthaler <mail@markus-lanthaler.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ML\GitSynchronizer;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * A GitSynchronizer exception
 *
 * @author Markus Lanthaler <mail@markus-lanthaler.com>
 */
class Exception extends \RuntimeException implements HttpExceptionInterface
{
    private $statusCode;
    private $publicMessage;

    /**
     * Constructor
     *
     * @param integer $statusCode The HTTP status code
     * @param string  $message    The exception message
     * @param string  $repository The currently being processed repository
     * @param integer $code       The internal exception code
     */
    public function __construct($statusCode, $message = null, $repository = null, $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->publicMessage = $message;

        parent::__construct($message . ($repository ? ' (' . $repository . ')' : ''), $code);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return array();
    }

    public function getPublicMessage()
    {
        return $this->publicMessage;

    }
}
