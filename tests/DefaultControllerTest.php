<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Controller;

class DefaultControllerTest extends \Framework\WebTestCase
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
}
