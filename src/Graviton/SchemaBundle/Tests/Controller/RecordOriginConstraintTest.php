<?php
/**
 * test for RecordOriginConstraint
 */

namespace Graviton\SchemaBundle\Tests\Controller;

use Graviton\SchemaBundle\Constraint\RecordOriginConstraint;
use Graviton\TestBundle\Test\RestTestCase;
use Symfony\Component\HttpFoundation\Response;
use JsonSchema\Rfc3339;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class RecordOriginConstraintTest extends RestTestCase
{

    /**
     * load fixtures
     *
     * @return void
     */
    public function setUp() : void
    {
        $this->loadFixturesLocal(
            array(
                'GravitonDyn\CustomerBundle\DataFixtures\MongoDB\LoadCustomerData',
            )
        );
    }

    /**
     * test the validation of the RecordOriginConstraint
     *
     * @dataProvider createDataProvider
     *
     * @param object  $entity           The object to create
     * @param integer $expectedStatus   Header status code
     * @param string  $expectedResponse Post data result of post
     *
     * @return void
     */
    public function testRecordOriginHandlingOnCreate($entity, $expectedStatus, $expectedResponse)
    {
        $client = static::createRestClient();
        $client->post('/person/customer/', $entity);

        $response = $client->getResponse();
        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $client->getResults());
    }

    /**
     * tests for the case if user doesn't provide an id in payload.. constraint
     * must take the id from the request in that case.
     *
     * @return void
     */
    public function testRecordOriginHandlingWithNoIdInPayload()
    {
        $record = (object) [
            //'id' => '' - no, no id.. that's the point ;-)
            'customerNumber' => 555,
            'name' => 'Muster Hans'
        ];

        $client = static::createRestClient();
        $client->put('/person/customer/100', $record);

        $fields = 'customerNumber, name, recordOrigin, groups, someObject.oneField, createDate, id';

        $this->assertEquals(
            $this->getExpectedErrorMessage($fields)[0],
            $client->getResults()[0]
        );

        $this->assertEquals(1, count($client->getResults()));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
    }

    /**
     * Test the validation of the RecordOriginConstraint
     *
     * @param array   $fieldsToSet      Fields to be modified
     * @param integer $expectedStatus   Header status code
     * @param string  $expectedResponse Result to be returned
     * @param boolean $checkSavedEntry  To check db for correct result
     *
     * @dataProvider updateDataProvider
     *
     * @return void
     */
    public function testRecordOriginHandlingOnUpdate(
        $fieldsToSet,
        $expectedStatus,
        $expectedResponse,
        $checkSavedEntry = true
    ) {
        $client = static::createRestClient();
        $client->request('GET', '/person/customer/100');
        $result = $client->getResults();

        // apply changes
        foreach ($fieldsToSet as $key => $val) {
            $result->{$key} = $val;
        }

        $expectedObject = $result;

        $client = static::createRestClient();
        $client->put('/person/customer/100', $result);

        $response = $client->getResponse();
        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $client->getResults());

        if ($checkSavedEntry) {
            // fetch it again and compare
            $client = static::createRestClient();
            $client->request('GET', '/person/customer/100');
            $this->assertEquals($expectedObject, $client->getResults());
        }
    }

    /**
     * Test the validation of the RecordOriginConstraint
     *
     * @param array   $ops              PATCH operations
     * @param integer $expectedStatus   Header status code
     * @param string  $expectedResponse Result to be returned
     *
     * @dataProvider patchDataProvider
     *
     * @return void
     */
    public function testRecordOriginHandlingOnPatch(
        $ops,
        $expectedStatus,
        $expectedResponse
    ) {
        $original = ini_get('date.timezone');
        ini_set('date.timezone', 'Asia/Kuala_Lumpur');

        $client = static::createRestClient();

        $client->request('PATCH', '/person/customer/100', [], [], [], json_encode($ops));

        $response = $client->getResponse();
        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $client->getResults());

        ini_set('date.timezone', $original);
    }

    /**
     * test to see if DELETE on a recordorigin: core is denied
     *
     * @return void
     */
    public function testDeleteHandling()
    {
        $client = static::createRestClient();
        $client->request('DELETE', '/person/customer/100');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString(
            "'recordOrigin' must not be one of the following keywords",
            $client->getResults()->message
        );
    }

    /**
     * Data provider for POST related stuff
     *
     * @return array
     */
    public function createDataProvider()
    {
        $baseObj = [
            'customerNumber' => 888,
            'name' => 'Muster Hans'
        ];

        return [

            /*** STUFF THAT SHOULD BE ALLOWED ***/

            'create-allowed-object' => [
                'entity' => (object) array_merge(
                    $baseObj,
                    [
                        'recordOrigin' => 'hans'
                    ]
                ),
                'httpStatusExpected' => Response::HTTP_CREATED,
                'expectedResponse' => null
            ],

            /*** STUFF THAT SHOULD BE DENIED ***/

            'create-recordorigin-core' => [
                'entity' => (object) array_merge(
                    $baseObj,
                    [
                        'recordOrigin' => 'core'
                    ]
                ),
                'httpStatusExpected' => Response::HTTP_BAD_REQUEST,
                'expectedResponse' => [
                    (object) [
                        'propertyPath' => 'recordOrigin',
                        'message' => 'Creating documents with the recordOrigin field having a '.
                            'value of core is not permitted.'
                    ]
                ]
            ]
        ];
    }

    /**
     * Data provider for PUT related stuff
     *
     * @return array
     */
    public function updateDataProvider()
    {
        return [

            /*** STUFF THAT SHOULD BE ALLOWED ***/
            'create-allowed-object' => [
                'fieldsToSet' => [
                    'addedField' => (object) [
                        'some' => 'property',
                        'another' => 'one'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_NO_CONTENT,
                'expectedResponse' => null
            ],
            'subproperty-modification' => [
                'fieldsToSet' => [
                    'someObject' => (object) [
                        'oneField' => 'value',
                        'twoField' => 'twofield'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_NO_CONTENT,
                'expectedResponse' => null
            ],

            /*** STUFF THAT NEEDS TO BE DENIED ***/
            'denied-subproperty-modification' => [
                'fieldsToSet' => [
                    'someObject' => (object) [
                        'oneField' => 'changed-value'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_BAD_REQUEST,
                'expectedResponse' => $this->getExpectedErrorMessage('someObject.oneField'),
                'checkSavedEntry' => false
            ],
            'denied-try-change-recordorigin' => [
                'fieldsToSet' => [
                    'recordOrigin' => 'hans'
                ],
                'httpStatusExpected' => Response::HTTP_BAD_REQUEST,
                'expectedResponse' => $this->getExpectedErrorMessage('recordOrigin'),
                'checkSavedEntry' => false
            ],
        ];
    }


    /**
     * providing the conditional errorMessage Object
     *
     * @param string $changedFields the Field the user wants to change
     *
     * @return array
     */
    private function getExpectedErrorMessage($changedFields)
    {
        $expectedErrorOutput = [
            (object) [
                'propertyPath' => 'recordOrigin',
                'message' => 'Prohibited modification attempt on record with recordOrigin of core.'
                    .' You tried to change ('.$changedFields.'), but you can only change'
                    .' (addedField, someObject.twoField) by recordOriginException.'
            ]
        ];
        return $expectedErrorOutput;
    }

    /**
     * Data provider for PATCH related stuff
     *
     * @return array
     */
    public function patchDataProvider()
    {
        return [

            /*** STUFF THAT SHOULD BE ALLOWED ***/

            'patch-allowed-attribute' => [
                'ops' => [
                    [
                        'op' => 'add',
                        'path' => '/someObject/twoField',
                        'value' => 'myValue'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_OK,
                'expectedResponse' => null
            ],
            'patch-add-object-data' => [
                'ops' => [
                    [
                        'op' => 'add',
                        'path' => '/addedField',
                        'value' => [
                            'someProperty' => 'someValue',
                            'anotherOne' => 'oneMore'
                        ]
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_OK,
                'expectedResponse' => null
            ],

            /*** STUFF THAT NEEDS TO BE DENIED ***/
            'patch-denied-subproperty' => [
                'ops' => [
                    [
                        'op' => 'add',
                        'path' => '/someObject/oneField',
                        'value' => 'myValue'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_BAD_REQUEST,
                'expectedResponse' => $this->getExpectedErrorMessage('someObject.oneField')
            ],
            'patch-denied-recordorigin-change' => [
                'ops' => [
                    [
                        'op' => 'replace',
                        'path' => '/recordOrigin',
                        'value' => 'hans'
                    ]
                ],
                'httpStatusExpected' => Response::HTTP_BAD_REQUEST,
                'expectedResponse' => $this->getExpectedErrorMessage('recordOrigin')
            ],

        ];
    }

    /**
     * test the validation of the RecordOriginConstraint
     *
     * @return void
     */
    public function testRecordOriginUTCDateHandling()
    {
        $client = static::createRestClient();
        $client->request('get', '/person/customer/100');
        $customer = $client->getResults();

        $this->assertObjectHasAttribute('createDate', $customer, json_encode($customer));

        // Check date and convert it, make a UTC+1 change
        $createDate = Rfc3339::createFromString($customer->createDate);
        $zone = new \DateTimeZone('America/Los_Angeles');
        $createDate->setTimezone($zone);
        $customer->createDate = $createDate->format(\DateTime::ATOM);

        $client = static::createRestClient();
        $client->put('/person/customer/100', $customer);
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * test the validation of the convertDatetimeToUTC
     *
     * @return void
     */
    public function testPrivateConvertUTC()
    {
        $data = [
            "fielddata" => "2016-05-09T22:00:00+0100",
            "fieldobj"  => [
                    "subfield" => "2016-10-09T10:00:00+0100"
                ]
            ];
        $schema = [
            "properties" => [
                "fielddata" => ["format" => "date-time"],
                "fieldobj"  => [
                    "properties" => [
                        "subfield" => [
                            "format" => "date-time"
                            ]
                        ]
                    ]
                ]
            ];
        $schema = json_decode(json_encode($schema));
        $data = json_decode(json_encode($data));

        $zone = new \DateTimeZone('UTC');

        /** @var RecordOriginConstraint $class */
        $class = $this->getContainer()->get('graviton.schema.constraint.recordorigin');
        $method = $this->getPrivateClassMethod(get_class($class), 'convertDatetimeToUTC');

        $result = $method->invokeArgs($class, [$data, $schema, $zone]);

        $this->assertEquals('2016-05-09T21:00:00+0000', $result->fielddata);
        $this->assertEquals('2016-10-09T09:00:00+0000', $result->fieldobj->subfield);
    }
}
