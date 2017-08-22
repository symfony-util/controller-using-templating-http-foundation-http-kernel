<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

// use Symfony\Component\DependencyInjection\Reference;
// Bundles
// use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
// use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;

// use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

class FrameworkKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            // new WebServerBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/', SymfonyUtil\Controller\EngineAsArgumentController::class, 'index');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        // $loader->import($this->getProjectDir() . '/config/parameters.yml'); // PHPUnit
        // $loader->load($this->getProjectDir().'/config/config_'.$this->getEnvironment().'.yml');

        //Controllers
        $c->autowire(SymfonyUtil\Controller\EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);

        // Extensions
        $c->loadFromExtension('framework', [
            'secret' => 'NotSecret', // What about use $ uuid -v4  or $ uuidgen
            'test' => in_array($this->getEnvironment(), ['test'], true), // test.client service for eg. PHPUnit
            'profiler' => ['enabled' => in_array($this->getEnvironment(), ['dev', 'test'], true)],
            'templating' => ['engines' => 'twig'],
        ]);
        $c->loadFromExtension('twig', [
            'debug' => true,
            'paths' => ['%kernel.project_dir%/tests/templates'],
        ]); // Sets the template directories...
    }
}
