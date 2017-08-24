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
// use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver; // Do not know how to configure this.
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateNameParserInterface;
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

    public function testControllerResponse()
    {
        $matcher = $this->createMock(UrlMatcherInterface::class);
        // use getMock() on PHPUnit 5.3 or below
        // $matcher = $this->getMock(UrlMatcherInterface::class);

        $matcher
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue([
                '_route' => 'foo',
                'name' => 'Fabien',
                '_controller' => function ($name) {
                    return new Response('Hello '.$name);
                },
            ]))
        ;
        $matcher
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($this->createMock(RequestContext::class)))
        ;

        $requestStack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(
            $matcher,
            $requestStack
        ));
        $dispatcher->addSubscriber(new ResponseListener('UTF-8'));
        $response = (new HttpKernel(
            $dispatcher,
            // new ContainerControllerResolver($c),
            new ControllerResolver(),
            $requestStack,
            new ArgumentResolver() // OK
        // ))->handle(Request::create('/', 'GET'));
        ))->handle(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Hello Fabien', $response->getContent());
    }

    public function testContainerCanBeCreated()
    {
        $this->assertInstanceOf(
            // ...::class, // 5.4 < php
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            $this->container()
        );
    }

    public function testContainerInterface()
    {
        $this->assertInstanceOf(
            // ...::class, // 5.4 < php
            'psr\Container\ContainerInterface',
            $this->container()
        );
    }

    public function ComponentReturnsResponse() // Not yet a test!
    {
        $requestStack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(
            new UrlMatcher(
                $this->loadRoutes(),
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
                new ContainerControllerResolver($c),
                $requestStack,
                new ArgumentResolver()
            ))->handle(Request::create('/', 'GET'))
        );
    }

    private function configureRoutes(RouteCollectionBuilder $routes)
    { // from Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait
        $routes->add('/', EngineAsArgumentController::class, 'index'); // .'::__invoke'
        //^ It should be tested if the actually used controller resolver can resolve this!
        //^ Returns Symfony/Component/Routing/Route .
    }

    private function loadRoutes(LoaderInterface $loader = null)
    { // from Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait
        $routes = new RouteCollectionBuilder($loader);
        $this->configureRoutes($routes);

        return $routes->build();
    }

    private function container()
    {
        $c = new ContainerBuilder();
        // https://symfony.com/doc/current/service_container.html

        $c->autowire(TemplateNameParser::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(TemplateNameParserInterface::class, TemplateNameParser::class);

        $c->autowire(Twig_Loader_Array::class, Twig_Loader_Array::class)
            ->setArgument('$templates', ['index.html.twig' => 'Hello Component!'])
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(Twig_LoaderInterface::class, Twig_Loader_Array::class);

        $c->autowire(Twig_Environment::class, Twig_Environment::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(Twig\Environment::class, Twig_Environment::class);

        $c->autowire(TwigEngine::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(EngineInterface::class, TwigEngine::class);

        // Unit Testing
        // $c->autowire('test.client', Client::class)
        //     ->setPublic(true); // Public needed!

        //Controllers
        $c->autowire(EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);

        return $c;
    }
}

// http://api.symfony.com/3.3/Symfony/Bridge/Twig/TwigEngine.html
// http://api.symfony.com/3.3/Symfony/Bundle/TwigBundle/TwigEngine.html
