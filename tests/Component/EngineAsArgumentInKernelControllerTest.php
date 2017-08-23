<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Component\AppKernel;

/**
 * @covers \SymfonyUtil\Controller\EngineAsArgumentController
 */
final class EngineAsArgumentInKernelControllerTest extends TestCase
{
    public function testCanBeCreated()
    {
        $this->assertInstanceOf(
            // ...::class, // 5.4 < php
            'Symfony\Component\HttpKernel\Kernel',
            new AppKernel('dev', true)
        );
    }

    public function testKernelInterface()
    {
        $this->assertInstanceOf(
            // ...::class, // 5.4 < php
            'Symfony\Component\HttpKernel\KernelInterface',
            new AppKernel('dev', true)
        );
    }

    public function testReturnsResponse()
    {
        $this->assertInstanceOf(
            // Response::class, // 5.4 < php
            'Symfony\Component\HttpFoundation\Response',
            (new AppKernel('dev', true))->handle(Request::create('/', 'GET'))
        );
    }
}

// http://api.symfony.com/3.3/Symfony/Bridge/Twig/TwigEngine.html
// http://api.symfony.com/3.3/Symfony/Bundle/TwigBundle/TwigEngine.html
