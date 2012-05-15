<?php

namespace Eotvos\EjtvBundle\Tests\Controller;

use Cancellar\CommonBundle\Test\ModelWebTestCase;

/**
 * Simple tests for the postalcode controller
 * 
 * @uses ModelWebTestCase
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class PostalcodeControllerTest extends ModelWebTestCase
{
    /**
     * Database setup
     * 
     * @return void
     */
    public function setUp()
    {
        parent::setUp(__DIR__ ."/../../TestFixtures");
    }

    /**
     * Exactly one result
     * 
     * @return void
     */
    public function testConcrete()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/postalcode/8900');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], '8900');
        $this->assertEquals(count($response['results']), 1);
        $this->assertEquals($response['results'][0]['code'], '8900A');
        $this->assertEquals($response['results'][0]['name'], 'Zalaegerszeg');
    }

    /**
     * Multiple results.
     * 
     * @return void
     *
     * @todo check for prefix condition
     */
    public function testMultiple()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/postalcode/8901');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], '8901');
        $this->assertEquals(count($response['results']), 2);
        $this->assertNotEquals($response['results'][0]['name'], $response['results'][1]['name']);
    }

    /**
     * More results
     * 
     * @return void
     */
    public function testList()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/postalcode/890');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], '890');
        $this->assertEquals(count($response['results']), 3);
    }

    /**
     * No results
     * 
     * @return void
     */
    public function testNone()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/postalcode/777');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(count($response['results']), 0);
    }

    /**
     * No results
     * 
     * @return void
     */
    public function testLonger()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/postalcode/8900AA');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(count($response['results']), 0);
    }

}
