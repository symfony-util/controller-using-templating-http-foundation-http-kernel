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
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateNameParserInterface;
use SymfonyUtil\Controller\EngineAsArgumentController;

// use Twig_Environment;
// use Twig_Loader_Array;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/', EngineAsArgumentController::class, 'index');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
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
            // ->setShared(true) // not needed: default
            ->setPublic(false);
        $c->setAlias(EngineInterface::class, TwigEngine::class);

        if (in_array($this->getEnvironment(), ['test'], true)) {
            $c->autowire('test.client', Client::class)
                // ->setAutoconfigured(false)
                // ->setShared(false)
                ->setPublic(true); // sure? -> better
        }

        //Controllers
        $c->autowire(EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);

        // Extensions
        $c->loadFromExtension('framework', [
            'secret' => 'NotSecret', // What about use $ uuid -v4  or $ uuidgen
        ]);
    }
}

// Information for Service "test.client"
// =====================================

//  ---------------- ---------------------------------------
//   Option           Value
//  ---------------- ---------------------------------------
//   Service ID       test.client
//   Class            Symfony\Bundle\FrameworkBundle\Client
//   Tags             -
//   Public           yes
//   Synthetic        no
//   Lazy             no
//   Shared           no
//   Abstract         no
//   Autowired        no
//   Autoconfigured   no
//  ---------------- ---------------------------------------
