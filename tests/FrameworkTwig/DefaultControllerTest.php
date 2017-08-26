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
        $client = static::createClient();

        $crawler = $client->request('GET', '/variadic/request');

        $this->assertSame(
            200, // or Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );

        $this->assertContains('Hello World!', $client->getResponse()->getContent());
    }
}
