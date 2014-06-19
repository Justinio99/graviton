<?php

namespace Graviton\I18nBundle\Tests\Controller;

use Graviton\TestBundle\Test\RestTestCase;

/**
 * Basic functional test for /i18n/language.
 *
 * @category I18nBundle
 * @package  Graviton
 * @author   Lucas Bickel <lucas.bickel@swisscom.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.com
 */
class LanguageControllerTest extends RestTestCase
{
    /**
     * @const complete content type string expected on a resouce
     */
    const CONTENT_TYPE = 'application/json; charset=UTF-8; profile=http://localhost/schema/i18n/language/';

    /**
     * check if a list of all languages can be optained
     *
     * @return void
     */
    public function testFindAll()
    {
        $client = static::createRestClient();
        $client->request('GET', '/i18n/language');

        $response = $client->getResponse();
        $results = $client->getResults();

        $this->assertResponseContentType(self::CONTENT_TYPE.'collection', $response);

        // we assume that initially all systems will only know of the english lang
        $this->assertcount(1, $results);

        $this->assertEquals('en', $results[0]->id);

        $this->assertEquals('en', $response->headers->get('Content-Language'));

        $this->assertEquals('English', $results[0]->name->en);
    }

    /**
     * test add language and request both languages
     *
     * @return void
     */
    public function testAddAndUseNewLanguage()
    {
        $newLang = new \stdClass;
        $newLang->id = 'de';

        $client = static::createRestClient();
        $client->post('/i18n/language', $newLang);

        $response = $client->getResponse();
        $results = $client->getResults();

        $this->assertResponseContentType(self::CONTENT_TYPE.'item', $response);

        $this->assertEquals('de', $results->id);

        $this->assertEquals('en', $response->headers->get('Content-Language'));

        $client->request('GET', '/i18n/language', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'en,de'));

        $response = $client->getResponse();
        $results = $client->getResults();

        $this->assertEquals('en, de', $response->headers->get('Content-Language'));

        $client->request('GET', '/i18n/language/en', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'en,de'));

        $results = $client->getResults();

        $this->assertEquals('English', $results->name->en);
        $this->assertEquals('Englisch', $results->name->de);
    }

    /**
     * check that we do not return unknown languages
     *
     * @return void
     */
    public function testDontReturnUnknownLanguage()
    {
        $client = static::createRestClient();

        $client->request('GET', '/i18n/language', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'en,de'));

        $response = $client->getResponse();

        $this->assertEquals('en', $response->headers->get('Content-Language'));

        $this->markTestIncomplete();
    }
}
