<?php
/**
 * test a JsonExceptionLister
 */

namespace Graviton\CoreBundle\Tests\Services;

use Graviton\CoreBundle\Listener\JsonExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Functional test
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class JsonExceptionListenerTest extends TestCase
{

    /**
     * test normal handling
     *
     * @return void
     */
    public function testStatus500()
    {
        $sut = new JsonExceptionListener();

        $kernelMock = $this->getMockForAbstractClass(HttpKernelInterface::class);
        $req = new Request();

        $exception = new \Exception('This is the exception message', 501);
        $exceptionEvent = new ExceptionEvent(
            $kernelMock,
            $req,
            0,
            $exception
        );

        $sut->onKernelException($exceptionEvent);

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertEquals(
            '{"code":501,"exceptionClass":"Exception","message":"This is the exception message"}',
            $response->getContent()
        );
    }
}
