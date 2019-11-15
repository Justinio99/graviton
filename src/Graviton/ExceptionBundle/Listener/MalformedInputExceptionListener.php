<?php
/**
 * Listener for no input exceptions
 */

namespace Graviton\ExceptionBundle\Listener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Graviton\ExceptionBundle\Exception\MalformedInputException;

/**
 * Listener for no input exceptions
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class MalformedInputExceptionListener extends RestExceptionListener
{
    /**
     * Handle the exception and send the right response
     *
     * @param ExceptionEvent $event Event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event)
    {
        if (($exception = $event->getException()) instanceof MalformedInputException) {
            $msg = array("message" => "Bad Request - " . $exception->getMessage());

            $response = $exception->getResponse()
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setContent($this->getSerializedContent($msg));

            $event->setResponse($response);
        }
    }
}
