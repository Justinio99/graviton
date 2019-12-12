<?php
/**
 * RqlOperatorNotAllowedListener class file
 */

namespace Graviton\ExceptionBundle\Listener;

use Graviton\ExceptionBundle\Exception\RqlOperatorNotAllowedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class RqlOperatorNotAllowedListener extends RestExceptionListener
{
    /**
     * Handle the exception and send the right response
     *
     * @param ExceptionEvent $event Event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof RqlOperatorNotAllowedException) {
            $response = $exception->getResponse() ?: new Response();
            $response = $response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setContent($this->getSerializedContent(['message' => $exception->getMessage()]));

            $event->setResponse($response);
        }
    }
}
