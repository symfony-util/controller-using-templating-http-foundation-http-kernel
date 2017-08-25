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
use Symfony\Bridge\Twig\TwigEngine; // From Bridge or Bundle.
// use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
//^ Do not know how to configure this.
// * In a constructor of a kernel derivative
// * Using the DI container and looking example config
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher; // != Symfony\Bundle\FrameworkBundle\Routing\Router
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParser; // != Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser
use Symfony\Component\Templating\TemplateNameParserInterface;
use SymfonyUtil\Controller\EngineInConstructorController;
use Tests\Component\AppKernel;

final class EngineInConstructorInKernelControllerTest extends TestCase
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
            (new AppKernel('dev', true))->handle(Request::create('/constructor', 'GET'))
        );
    }

    public function testControllerResponse()
    { // From: https://symfony.com/doc/current/create_framework/unit_testing.html
        // TODO: Try with a real matcher see next test...
        // TODO: Use real controller to be tested! OK
        $matcher = $this->createMock(UrlMatcherInterface::class); // What about another test with RequestMatcherInterface?
        // use getMock() on PHPUnit 5.3 or below
        // $matcher = $this->getMock(UrlMatcherInterface::class);

        $matcher
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue([
                '_route' => 'foo',
                'name' => 'Fabien',
                '_controller' => EngineInConstructorController::class,
            ]))
        ;
        $matcher
            ->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($this->createMock(RequestContext::class)))
        ;

        $c = $this->container();
        $c->compile();
        $requestStack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener($matcher, $requestStack)); // Returns nothing.
        $dispatcher->addSubscriber(new ResponseListener('UTF-8'));
        $response = (new HttpKernel(
            $dispatcher,
            new ContainerControllerResolver($c),
            $requestStack,
            new ArgumentResolver()
        ))->handle(new Request()); // Mock will inject the controller.

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Hello Component!', $response->getContent());
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

    public function testComponentReturnsResponse()
    {
        $c = $this->container();
        $c->compile();
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
        $routes->add('/', EngineInConstructorController::class, 'index');
    }

    private function loadRoutes(LoaderInterface $loader = null)
    { // from Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait
        $routes = new RouteCollectionBuilder($loader);
        $this->configureRoutes($routes);

        return $routes->build();
    }

    private function configureJustHelloRoutes(RouteCollectionBuilder $routes)
    { // from Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait
        $routes->add(
            '/',
            function () {
                return new Response('Hello');
            },
            'index'
        );
        //^ Returns Symfony/Component/Routing/Route .
    }

    private function loadJustHelloRoutes(LoaderInterface $loader = null)
    { // from Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait
        $routes = new RouteCollectionBuilder($loader);
        $this->configureJustHelloRoutes($routes);

        return $routes->build();
    }

    private function container()
    {
        $c = new ContainerBuilder();

        $c->autowire(TwigEngine::class) // From Bridge or Bundle
            ->setArgument('$environment', new Twig_Environment(new Twig_Loader_Array(['index.html.twig' => 'Hello Component!'])))
            ->setArgument('$parser', new TemplateNameParser()) // From Templating or Framework
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(EngineInterface::class, TwigEngine::class);

        // Unit Testing
        // $c->autowire('test.client', Client::class)
        //     ->setPublic(true); // Public needed!

        //Controllers
        $c->autowire(EngineInConstructorController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(true);

        return $c;
    }
}

// http://api.symfony.com/3.3/Symfony/Bridge/Twig/TwigEngine.html
// http://api.symfony.com/3.3/Symfony/Bundle/TwigBundle/TwigEngine.html
