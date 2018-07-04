<?php
/**
 * Handling authentication for clients in the same network.
 */

namespace Graviton\SecurityBundle\Authentication\Strategies;

use Graviton\TestBundle\Test\RestTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Class SameSubnetStrategyTest
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class SameSubnetStrategyTest extends RestTestCase
{
    protected $strategy;
    /** @var Client */
    protected $client;
    protected $propertyKey;

    /**
     * UnitTest Starts this on reach test
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->propertyKey = $this->client->getKernel()
            ->getContainer()
            ->getParameter('graviton.security.authentication.strategy.subnet.key');
        $this->strategy = new SameSubnetStrategy(
            $this->propertyKey
        );
    }

    /**
     * x header shall be sent in order to gain the roles for the subnet user.
     *
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\SameSubnetStrategy::apply
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::extractFieldInfo
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::validateField
     *
     * @return void
     */
    public function testApply()
    {
        $this->client->request(
            'GET', //method
            '/', //uri
            [], //parameters
            [], //files
            [] //server
        );

        $this->expectException('\InvalidArgumentException');
        $this->strategy->apply($this->client->getRequest());
    }

    /**
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\SameSubnetStrategy::apply
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::extractFieldInfo
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::validateField
     *
     * @return void
     */
    public function testApplyHeader()
    {
        $client = static::createClient([], ['HTTP_X-GRAVITON-AUTHENTICATION' => 'test-user-name']);
        $this->strategy->setSubnetIp('127.0.0.0/7');
        $client->request('GET', '/');

        $this->assertSame('test-user-name', $this->strategy->apply($client->getRequest()));
    }

    /**
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\SameSubnetStrategy::apply
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::extractFieldInfo
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::validateField
     *
     * @return void
     */
    public function testApplyExpectingInvalidArgumentException()
    {
        $this->client->request(
            'GET', //method
            '/', //uri
            [], //parameters
            [], //files
            [] //server
        );

        $strategy = new SameSubnetStrategy('10.2.0.2');

        $this->expectException('\InvalidArgumentException');
        $strategy->apply($this->client->getRequest());
    }

    /**
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\SameSubnetStrategy::apply
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::extractFieldInfo
     * @covers \Graviton\SecurityBundle\Authentication\Strategies\AbstractHttpStrategy::validateField
     *
     * @return void
     */
    public function testApplyHeaderReturnEmpty()
    {
        $options = ['HTTP_X-GRAVITON-AUTHENTICATION' => 'test-user-name', 'REMOTE_ADDR' => '126.0.0.1'];
        $client = static::createClient([], $options);
        $client->request('GET', '/');

        $this->assertSame('', $this->strategy->apply($client->getRequest()));
    }
}
