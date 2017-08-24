<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Component;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddCacheClearerPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddCacheWarmerPass;
// use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddConsoleCommandPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\ControllerArgumentValueResolverPass;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\RoutingResolverPass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Kernel; // Manages an environment made of bundles. HttpKernel is needed in addition!
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateNameParserInterface;
use SymfonyUtil\Controller\EngineAsArgumentController;

// use Twig_Environment;
// use Twig_Loader_Array;
// use Twig_LoaderInterface;

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
        // HttpKernel has to be added!

        $c->register('event_dispatcher',                                ContainerAwareEventDispatcher::class) // services.xml TODO: Obsolete use EventDispatcher instead, but may cause other problems
            ->addArgument(new Reference('service_container'))
        ;

        $c->register('http_kernel',                                HttpKernel::class) // services.xml
            ->addArgument(new Reference('event_dispatcher'))
            ->addArgument(new Reference('controller_resolver'))
            ->addArgument(new Reference('request_stack'))
            ->addArgument(new Reference('argument_resolver'))
        ;

        $c->register('request_stack',                                RequestStack::class); // services.xml

/*
        $c->register('service_container')
            ->setSynthetic(true)
            ->setAutowiringTypes([ContainerInterface::class])
            ->setAutowiringTypes([Container::class])
        ;
        $c->set('service_container', $c);
        // S3.3 Deprecation note: http://symfony.com/blog/new-in-symfony-3-3-deprecated-the-autowiring-types
*/

        $c->register('kernel')
            ->setSynthetic(true)
        ;
        $c->set('kernel', $this);

        $c->register('uri_signer',                                UriSigner::class) // services.xml
            ->addArgument('12345') // app config
        ;

        $c->register('config_cache_factory',                                ResourceCheckerConfigCacheFactory::class) // services.xml
            ->addArgument([])
        ;
        $c->register('controller_name_converter',                                ControllerNameParser::class) // web.xml
            ->setPublic(false)
            ->addTag('monolog.logger', ['channel' => 'request'])
            ->addArgument(new Reference('kernel'))
        ;

        $c->register('controller_resolver',                                ControllerResolver::class) // web.xml
//            ->setPublic(false)
            ->addTag('monolog.logger', ['channel' => 'request'])
            ->addArgument(new Reference('service_container'))
            ->addArgument(new Reference('controller_name_converter'))
            ->addArgument(new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
        ;

        $c->register('argument_metadata_factory',                                ArgumentMetadataFactory::class) // web.xml
//            ->setPublic(false)
        ;

        $c->register('argument_resolver',                                ArgumentResolver::class) // web.xml
//            ->setPublic(false)
            ->addArgument(new Reference('argument_metadata_factory'))
            ->addArgument([])
        ;

        $c->setParameter('router.resource',                       'kernel:loadRoutes'); // resource_type: service
//         $c->setParameter('router.resource',                       new Expression('service("kernel").loadRoutes'));
//        $c->setParameter('router.resource',                       MicroKernel::class . ':loadRoutes');
//         $c->setParameter('router.resource',                       new Reference('kernel') . ':loadRoutes');
//        $c->setParameter('router.resource',                       MicroKernel::loadRoutes);
//        $c->setParameter('router.resource',                       self::loadRoutes);
//        $c->setParameter('router.resource',                       parent::loadRoutes);
//        $c->setParameter('router.resource',                       [new Reference('kernel'), 'loadRoutes']);
//        $c->setParameter('router.resource',                       ['kernel', 'loadRoutes']);
//        $c->setParameter('router.resource',                       [MicroKernel::class, 'loadRoutes']);

        $c->setParameter('router.cache_class_prefix',             $c->getParameter('kernel.container_class'));
        // symfony/framework-bundle/DependencyInjection/FrameworkExtension.php
        $c->setParameter('router.options.generator_class',        UrlGenerator::class); // routing.xml
        $c->setParameter('router.options.generator_base_class',   UrlGenerator::class); // routing.xml
        $c->setParameter('router.options.generator_dumper_class', PhpGeneratorDumper::class); // routing.xml
        $c->setParameter('router.options.generator.cache_class',  '%router.cache_class_prefix%UrlGenerator'); // routing.xml
        $c->setParameter('router.options.matcher_class',          RedirectableUrlMatcher::class); // routing.xml
        $c->setParameter('router.options.matcher_base_class',     RedirectableUrlMatcher::class); // routing.xml
        $c->setParameter('router.options.matcher_dumper_class',   PhpMatcherDumper::class); // routing.xml
        $c->setParameter('router.options.matcher.cache_class',    '%router.cache_class_prefix%UrlMatcher'); // routing.xml

        $c->setParameter('router.request_context.host', 'localhost'); // routing.xml
        $c->setParameter('router.request_context.scheme', 'http'); // routing.xml
        $c->setParameter('router.request_context.base_url', ''); // routing.xml

        $c->setParameter('request_listener.http_port', 80);
        $c->setParameter('request_listener.https_port', 443);
        // symfony/framework-bundle/DependencyInjection/FrameworkExtension.php

        $c->register('routing.resolver', LoaderResolver::class) // routing.xml
            ->setPublic(false)
        ;

        $c->register('routing.loader.service',                            ServiceRouterLoader::class) // routing.xml
//            ->setPublic(false)
            ->addTag('routing.loader')
            ->addArgument(new Reference('service_container'))
        ;
        $c->register('routing.loader',                            DelegatingLoader::class) // routing.xml
            ->addArgument(new Reference('controller_name_converter'))
            ->addArgument(new Reference('routing.resolver'))
        ;
//...
        $c->register('router.default',                            Router::class) // routing.xml
//            ->setPublic(false)
            ->addTag('monolog.logger', ['channel' => 'router'])
            ->addArgument(new Reference('service_container'))
            ->addArgument('%router.resource%')
            ->addArgument([
                'cache_dir'              => '%kernel.cache_dir%',
                'debug'                  => '%kernel.debug%',
                'generator_class'        => '%router.options.generator_class%',
                'generator_base_class'   => '%router.options.generator_base_class%',
                'generator_dumper_class' => '%router.options.generator_dumper_class%',
                'generator_cache_class'  => '%router.options.generator.cache_class%',
                'matcher_class'          => '%router.options.matcher_class%',
                'matcher_base_class'     => '%router.options.matcher_base_class%',
                'matcher_dumper_class'   => '%router.options.matcher_dumper_class%',
                'matcher_cache_class'    => '%router.options.matcher.cache_class%',
                'resource_type'          => 'service', ////////////////////!
            ])
            ->addArgument(new Reference('router.request_context', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
            ->addArgument(new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
            ->addMethodCall('setConfigCacheFactory', [new Reference('config_cache_factory')])
        ;
        $c->setAlias('router', 'router.default');

        $c->register('router_listener',                            RouterListener::class) // routing.xml
            ->addTag('kernel.event_subscriber')
            ->addTag('monolog.logger', ['channel' => 'request'])
            ->addArgument(new Reference('router'))
            ->addArgument(new Reference('request_stack'))
            ->addArgument(new Reference('router.request_context', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
            ->addArgument(new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE))
        ;

        // $c->setParameter('', );
        $c->addCompilerPass(new RoutingResolverPass());
        $c->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
        // $c->addCompilerPass(new AddConsoleCommandPass()); // Depnedency need!
        $c->addCompilerPass(new AddCacheWarmerPass());
        $c->addCompilerPass(new AddCacheClearerPass());
        $c->addCompilerPass(new ControllerArgumentValueResolverPass());

        $c->autowire(TemplateNameParser::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(TemplateNameParserInterface::class, TemplateNameParser::class);

        $c->autowire(\Twig_Loader_Array::class, \Twig_Loader_Array::class)
            ->setArgument('$templates', ['index.html.twig' => 'Hello Component!'])
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(\Twig_LoaderInterface::class, \Twig_Loader_Array::class);

        $c->autowire(\Twig_Environment::class, \Twig_Environment::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(\Twig\Environment::class, \Twig_Environment::class);

        $c->autowire(TwigEngine::class)
            ->setAutoconfigured(true)
            ->setPublic(false);
        $c->setAlias(EngineInterface::class, TwigEngine::class);

        if (in_array($this->getEnvironment(), ['test'], true)) {
            $c->autowire('test.client', Client::class)
                ->setPublic(true); // Public needed!
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
