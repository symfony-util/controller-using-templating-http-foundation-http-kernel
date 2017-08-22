<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParser;

class ComponentKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            // new TwigBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/', SymfonyUtil\Controller\EngineAsArgumentController::class, 'index');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        // https://symfony.com/doc/current/service_container.html
        $c->autowire(TemplateNameParser::class)
            ->setAutoconfigured(true)
            ->setPublic(false);

        $c->autowire(Twig_Loader_Array::class, Twig_Loader_Array::class)
            ->setArgument('$templates', ['index.html.twig' => 'Hello World!'])
            ->setAutoconfigured(true)
            ->setPublic(false);

        $c->autowire(Twig_Environment::class, Twig_Environment::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(Twig\Environment::class, Twig_Environment::class);

        // $c->autowire(TwigEngine::class, TwigEngine::class)
        $c->autowire(TwigEngine::class)
            ->setAutoconfigured(true)
            ->setShared(true) // not needed: default
            ->setPublic(false);
        $c->setAlias(EngineInterface::class, TwigEngine::class);

        //Controllers
        $c->autowire(SymfonyUtil\Controller\EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);

        // Extensions
        $c->loadFromExtension('framework', [
            'secret' => 'NotSecret', // What about use $ uuid -v4  or $ uuidgen
            'test' => in_array($this->getEnvironment(), ['test'], true), // test.client service for eg. PHPUnit
            // 'templating' => ['engines' => 'twig'],
        ]);
        // $c->loadFromExtension('twig', [
        //     'debug' => true,
        //     'paths' => ['%kernel.project_dir%/tests/templates'],
        // ]);
    }
}
