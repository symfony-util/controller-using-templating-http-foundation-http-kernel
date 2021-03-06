<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// namespace Tests\FrameworkTwig;

use Knp\Rad\ResourceResolver\Bundle\ResourceResolverBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollectionBuilder;
use SymfonyUtil\Controller\EngineAsArgumentController;
use SymfonyUtil\Controller\TemplatingController;
use SymfonyUtil\Controller\VariadicController;
use Tests\FrameworkTwig\Identity;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new ResourceResolverBundle(),
            new TwigBundle(),
            new WebServerBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/', EngineAsArgumentController::class, 'index');
        $routes->add('/argument', EngineAsArgumentController::class, 'argument');
        $routes->add('/constructor', TemplatingController::class, 'constructor');
        $routes->addRoute(new Route('/variadic/{id}', [
                '_controller' => VariadicController::class,
                '_resources' => [
                    'arguments' => [Identity::class, [['eight' => '$id', '2' => '$request', '3' => '@request', '5' => 'request']]],
                ],
            ]),
            'variadic_request'
        );
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        //Controllers
        $c->autowire(EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(true);

        $c->autowire(TemplatingController::class)
            ->setAutoconfigured(true)
            // ->addTag('controller.service_arguments')
            ->setPublic(true);

        $c->autowire(VariadicController::class)
            ->setAutoconfigured(true)
            ->setPublic(true);

        $c->autowire(Identity::class)
            ->setAutoconfigured(true)
            ->setPublic(true);

        // Extensions
        $c->loadFromExtension('framework', [
            'secret' => 'NotSecret', // What about use $ uuid -v4  or $ uuidgen
            'test' => in_array($this->getEnvironment(), ['test'], true), // test.client service for eg. PHPUnit
            'templating' => ['engines' => 'twig'],
        ]);
        $c->loadFromExtension('twig', [
            'debug' => true,
            'paths' => ['%kernel.project_dir%/tests/templates'],
        ]);
    }

    public function getRootDir() // Not sure if any use!
    {
        return getcwd();
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/'.get_current_user().parent::getCacheDir();
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/'.get_current_user().parent::getLogDir();
    }
}
