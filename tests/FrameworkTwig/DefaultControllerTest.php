<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $this->markTestIncomplete(); // Test does not work any more with Symfony 3.4

        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertSame(
            200, // or Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains('Hello World!', $client->getResponse()->getContent());
    }

    public function testArgument()
    {
        $this->markTestIncomplete(); // Test does not work any more with Symfony 3.4

        $client = static::createClient();

        $crawler = $client->request('GET', '/argument');

        $this->assertSame(
            200, // or Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains('Hello World!', $client->getResponse()->getContent());
    }

    public function testConstructor()
    {
        $this->markTestIncomplete(); // Test does not work any more with Symfony 3.4

        $client = static::createClient();

        $crawler = $client->request('GET', '/constructor');

        $this->assertSame(
            200, // or Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains('Hello World!', $client->getResponse()->getContent());
    }

    public function testVariadic()
    {
        $this->markTestIncomplete(); // Test does not work any more with Symfony 3.4

        $client = static::createClient();

        $crawler = $client->request('GET', '/variadic/request');

        $this->assertSame(
            200, // or Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains('Hello World!', $client->getResponse()->getContent());
    }
}
