<?php

namespace Eotvos\EjtvBundle\Tests\Controller;

use Cancellar\CommonBundle\Test\ModelWebTestCase;

/**
 * Simple tests for the school controller
 * 
 * @uses ModelWebTestCase
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolControllerTest extends ModelWebTestCase
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
     * Too sort query
     * 
     * @return void
     */
    public function testShort()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/school/Z');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], 'Z');
        $this->assertEquals(count($response['results']), 0);
    }

    /**
     * Exactly one result
     * 
     * @return void
     */
    public function testConcrete()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/school/Zrinyi+M');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], 'Zrinyi M');
        $this->assertEquals(count($response['results']), 1);
        $this->assertEquals($response['results'][0]['omid'], 'OM1111');
        $this->assertEquals($response['results'][0]['name'], 'Zrinyi Miklos');
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

        $crawler = $client->request('GET', '/ajax/school/Zrinyi');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], 'Zrinyi');
        $this->assertEquals(count($response['results']), 2);
        $this->assertNotEquals($response['results'][0]['name'], $response['results'][1]['name']);
    }

    /**
     * No more results
     * 
     * @return void
     */
    public function testList()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/school/Zr');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($response['prefix'], 'Zr');
        $this->assertEquals(count($response['results']), 2);
    }

    /**
     * No results
     * 
     * @return void
     */
    public function testNone()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/ajax/school/Asdf');
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

        $crawler = $client->request('GET', '/ajax/school/Zrinyi+Miklos+Gimi');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(count($response['results']), 0);
    }

}

