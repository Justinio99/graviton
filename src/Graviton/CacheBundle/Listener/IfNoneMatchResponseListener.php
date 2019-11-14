<?php
/**
 * FilterResponseListener for adding a IfNoneMatch header.
 */

namespace Graviton\CacheBundle\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * FilterResponseListener for adding a IfNoneMatch header.
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class IfNoneMatchResponseListener
{
    /**
     * add a IfNoneMatch header to the response
     *
     * @param ResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $ifNoneMatch = $request->headers->get('if-none-match');
        $etag = $response->headers->get('ETag', 'empty');

        if ($ifNoneMatch === $etag) {
            $response->setStatusCode(304);
            $response->setContent(null);
        }
    }
}
