<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

// Bundles
// use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
// use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle

// use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

class FrameworkKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return = [
            new FrameworkBundle(),
            new TwigBundle(),
            new WebServerBundle()
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
            // 'secret' => 'NotSecret', // What about use $ uuid -v4  or $ uuidgen
            'test' => in_array($this->getEnvironment(), ['test'], true), // test.client service for eg. PHPUnit
            'profiler' => ['enabled' => in_array($this->getEnvironment(), ['dev', 'test'], true)],
            'templating' => [
                'engines' => 'twig',
            ],
        ]);
        $c->loadFromExtension('twig', [
            'debug' => true,
            'paths' => [
                '%kernel.project_dir%/templates',
                '%kernel.project_dir%/vendor/php-taxonomy/multitaxonomy-doctrine-dbal-util-pagerfanta-twig-templates' => 'MultiTaxonomyDbalUtilBundle',
                '%kernel.project_dir%/vendor/doctrine-dbal-util/url-multitaxonomy-pagerfanta-twig' => 'UrlMultiTaxonomyPagerfanta',
            ],
        ]); // Sets the template directories...
    }
}
