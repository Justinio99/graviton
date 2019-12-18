<?php
/**
 * FilterResponseListener for setting up CORS headers.
 */

namespace Graviton\RestBundle\Listener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * FilterResponseListener for setting up CORS headers.
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class CorsResponseListener
{
    /**
     * @var string[]
     */
    private $headers = [];

    /**
     * @var string[]
     */
    private $allowHeaders = [];

    /**
     * @var array
     */
    private $staticHeaders = [];

    /**
     * add an allowed header
     *
     * @param string $header header to allow
     *
     * @return void
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * add an allowed header
     *
     * @param string $header header to expose
     *
     * @return void
     */
    public function addAllowHeader($header)
    {
        $this->allowHeaders[] = $header;
    }

    /**
     * add a static header
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @return void
     */
    public function addStaticHeader($name, $value)
    {
        $this->staticHeaders[$name] = $value;
    }

    /**
     * add a rel=self Link header to the response
     *
     * @param ResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Cors session do not allow in some browsers the *.
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin', '*'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        $corsMethods = $request->attributes->get('corsMethods', '');
        if (!empty($corsMethods)) {
            $response->headers->set('Access-Control-Allow-Methods', $corsMethods);
        }
        $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->headers));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowHeaders));

        foreach ($this->staticHeaders as $headerName => $headerValue) {
            $response->headers->set($headerName, $headerValue);
        }
    }
}
