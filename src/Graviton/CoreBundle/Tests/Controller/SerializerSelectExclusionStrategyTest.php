<?php
/**
 * SerializerSelectExclusionStrategyTest class file
 */

namespace Graviton\CoreBundle\Tests\Controller;

use Graviton\TestBundle\Test\RestTestCase;
use Symfony\Component\HttpFoundation\Response;
use GravitonDyn\TestCasePrimitiveArrayBundle\DataFixtures\MongoDB\LoadTestCasePrimitiveArrayData;
use GravitonDyn\TestCaseNullExtrefBundle\DataFixtures\MongoDB\LoadTestCaseNullExtrefData;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class SerializerSelectExclusionStrategyTest extends RestTestCase
{

    /**
     * load fixtures (in this case we can reuse fixtures from other tests)
     *
     * @return void
     */
    public function setUp()
    {
        if (!class_exists(LoadTestCasePrimitiveArrayData::class)) {
            $this->markTestSkipped('TestCasePrimitiveArray definition is not loaded');
        }
        if (!class_exists(LoadTestCaseNullExtrefData::class)) {
            $this->markTestSkipped('TestCaseNullExtref definition is not loaded');
        }

        $this->loadFixtures(
            [LoadTestCasePrimitiveArrayData::class, LoadTestCaseNullExtrefData::class],
            null,
            'doctrine_mongodb'
        );
    }

    /**
     * Test testRqlSelectionOnArrays testing the correct serialization of nested arrays
     *
     * @return void
     */
    public function testRqlSelectionOnArrays()
    {
        $expectedResult = json_decode(
            file_get_contents(dirname(__FILE__).'/../resources/serializer-exclusion-array.json'),
            false
        );

        $client = static::createRestClient();
        $client->request(
            'GET',
            '/testcase/primitivearray/testdata?select(hash.strarray,arrayhash.intarray,arrayhash.hasharray)'
        );
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResult, $client->getResults());
    }

    /**
     * Test testRqlSelectionOnNested testing the correct serialization of deeply nested values
     *
     * @return void
     */
    public function testRqlSelectionOnNested()
    {
        $expectedResult = json_decode(
            file_get_contents(dirname(__FILE__).'/../resources/serializer-exclusion-nested.json'),
            false
        );

        $client = static::createRestClient();
        $client->request(
            'GET',
            '/testcase/nullextref/testdata?select(requiredExtref,requiredExtrefDeep.deep.deep,optionalExtrefDeep)'
        );
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResult, $client->getResults());
    }
}