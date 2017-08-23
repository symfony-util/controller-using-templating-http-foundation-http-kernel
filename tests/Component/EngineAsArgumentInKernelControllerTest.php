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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollectionBuilder;
use SymfonyUtil\Controller\EngineAsArgumentController;
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

    public function testFrameworkReturnsResponse()
    {
        $this->assertInstanceOf(
            // Response::class, // 5.4 < php
            'Symfony\Component\HttpFoundation\Response',
            (new AppKernel('dev', true))->handle(Request::create('/', 'GET'))
        );
    }

    public function testComponentReturnsResponse()
    {
        $requestStack = new RequestStack();
        $routes = new RouteCollectionBuilder(); // Because I know how to use it.
        $routes->add('/', EngineAsArgumentController::class, 'index'); // returns Symfony/Component/Routing/Route
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(
            new UrlMatcher(
                $routes->build(), 
                new RequestContext()
            ),
            $requestStack
        ));
        $dispatcher->addSubscriber(new ResponseListener('UTF-8'));
        $this->assertInstanceOf(
            // Response::class, // 5.4 < php
            'Symfony\Component\HttpFoundation\Response',
            (new HttpKernel(
                $dispatcher,
                new ControllerResolver(),
                $requestStack,
                new ArgumentResolver()
            ))->handle(Request::create('/', 'GET'))
        );
    }
}

// http://api.symfony.com/3.3/Symfony/Bridge/Twig/TwigEngine.html
// http://api.symfony.com/3.3/Symfony/Bundle/TwigBundle/TwigEngine.html
