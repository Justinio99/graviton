<?php
/**
 * RqlQueryRequestListener class file
 */

namespace Graviton\RestBundle\Listener;

use Graviton\RqlParserBundle\Listener\RequestListenerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * RQL query listener
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class RqlQueryRequestListener implements RequestListenerInterface
{
    /**
     * @var array Allowed route IDs
     */
    private $allowedRoutes = [];
    /**
     * @var RequestListenerInterface
     */
    private $requestListener;

    /**
     * Constructor
     *
     * @param RequestListenerInterface $requestListener Original RQL listener
     * @param array                    $allowedRoutes   Allowed route IDs
     */
    public function __construct(RequestListenerInterface $requestListener, array $allowedRoutes)
    {
        $this->requestListener = $requestListener;
        $this->allowedRoutes = $allowedRoutes;
    }

    /**
     * Process RQL query if it is allowed for current route
     *
     * @param ResponseEvent $event Event
     * @return void
     */
    public function onKernelRequest(ResponseEvent $event)
    {
        if (!in_array($event->getRequest()->attributes->get('_route'), $this->allowedRoutes, true)) {
            return;
        }

        $this->requestListener->onKernelRequest($event);
    }
}
