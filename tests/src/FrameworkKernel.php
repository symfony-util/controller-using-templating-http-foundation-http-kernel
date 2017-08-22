<?php

use DoctrineDbalUtil\Connection\ConnectionTrait;
use DoctrineDbalUtil\Connection\QueryTrait;
use DoctrineDbalUtil\Connection\Pagerfanta\PagedQueryTrait;
use DoctrineDbalUtil\Connection\Ramsey\Uuid;
use DoctrineDbalUtil\DbalContrib\Event\Listeners\SqliteSessionInit;
use DoctrineDbalUtil\UrlMultiTaxonomy\Schema\SchemaBuilder as  UrlSchemaBuilder;
use FosUserUtil\Doctrine\DBAL\SchemaBuilder as  UserSchemaBuilder;
use PhpTaxonomy\MultiTaxonomy\Doctrine\DBAL\SchemaBuilder as MultiTaxonomySchemaBuilder;
use SensioLabs\Security\Command\SecurityCheckerCommand;
use SensioLabs\Security\SecurityChecker;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

// Bundles
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\UserBundle\FOSUserBundle;
use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

class RaphiaDBAL {

    use ConnectionTrait, QueryTrait, Uuid\QueryTrait, PagedQueryTrait {
    }
}


class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function getCommands() {
        return [
        new SecurityCheckerCommand(new SecurityChecker()), // $ bin/console security:check ../composer.lock // because path is in app
    ];
    }

    public function getDbalSchema() {
        $schema = new \Doctrine\DBAL\Schema\Schema();
        
        $UserTableName = 'http_user';
        // TODO UserSchemaBuilder !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $UserTable = (new UserSchemaBuilder($schema))->userTable();

        $U = new UrlSchemaBuilder($schema);
        $UrlTable = $U->UrlTable();
        $OwnedUrlTable = $U->OwnedUrlTable($UrlTable);
        $LinkUrlUser = $U->LinkUrlUser($OwnedUrlTable, $UserTableName);
        
        $T = new MultiTaxonomySchemaBuilder($schema);
        $TaxoTable = $T->TaxoTable();
        $LinkTaxoTaxo = $T->LinkTaxoTaxo($TaxoTable);
        $LinkTaxonomyUser = $T->LinkTaxonomyUser($LinkTaxoTaxo, $UserTableName);
        $LinkUrlTaxo = $T->LinkUrlTaxo($OwnedUrlTable, $TaxoTable);

        return $schema;
    }

    public function getProjectDir() {
        return getcwd();
    }
    // https://symfony.com/blog/new-in-symfony-3-3-a-simpler-way-to-get-the-project-root-directory

    public function getRootDir() {
        return $this->getProjectDir() . '/app';
    }

    public function getCacheDir() {
        return sys_get_temp_dir() . '/' . get_current_user() . parent::getCacheDir();
    }

    public function getLogDir() {
        return sys_get_temp_dir() . '/' . get_current_user() . parent::getLogDir();
    }

    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new FOSUserBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new SensioFrameworkExtraBundle(),
            new DoctrineBundle(),
            new \WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new DoctrineDbalUtil\CliBundle\DbalUtilCliBundle(),
            new FosUserUtil\DoctrineDbalUtilCrudBundle\FosUserUtilDbalUtilCrudBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)):
            $bundles[] = new SensioGeneratorBundle();
            if (class_exists(WebProfilerBundle::class)):
                $bundles[] = new WebProfilerBundle();
                //^ depends on TwigBundle, appears on html pages with regular HTML structure
            endif;
            $bundles[] = new \Symfony\Bundle\WebServerBundle\WebServerBundle();
        endif;

        return $bundles;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        if (isset($this->bundles['WebProfilerBundle'])) {
            $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml', '/_wdt');
            $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml', '/_profiler');
        }

        $routes->add('/', SymfonyUtil\Controller\EngineAsArgumentController::class, 'index');
        $routes->import('@FOSUserBundle/Resources/config/routing/all.xml');
        $routes->import('@FosUserUtilDbalUtilCrudBundle/Controller/', '/', 'annotation');
        $routes->import($this->getProjectDir().'/vendor/php-taxonomy/multitaxonomy-doctrine-dbal-util-pagerfanta-twig-controller/default.yml', '/taxonomy', 'yaml');
        $routes->import($this->getProjectDir().'/vendor/doctrine-dbal-util/url-multitaxonomy-pager-controller/Controller/UrlController.php', '', 'annotation');
        // https://symfony.com/doc/current/routing.html#controller-string-syntax
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $invoker = new Invoker\Invoker;
        $loader->import($this->getProjectDir() . '/config/parameters.yml'); // PHPUnit
        // $loader->load($this->getProjectDir().'/config/config_'.$this->getEnvironment().'.yml');

        //Controllers
        $c->autowire(\PhpTaxonomy\MultiTaxonomy\DoctrineDbalUtil\Pagerfanta\Twig\Controller\MultiTaxonomyController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);
        $c->autowire(\DoctrineDbalUtil\UrlMultiTaxonomy\PagerController\Controller\UrlController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);
        $c->autowire(SymfonyUtil\Controller\EngineAsArgumentController::class)
            ->setAutoconfigured(true)
            ->addTag('controller.service_arguments')
            ->setPublic(false);

        // SECURITY URL Voter Appears not to be needed...
        // $c->register('app.url_voter', UrlVoter::class)
            // ->setPublic(false) // small performance boost
        //     ->addTag('security.voter')
        // ;
        $c->register('sqlite.listener', SqliteSessionInit::class)
            // ->setPublic(false) // small performance boost
            ->addTag('doctrine.event_listener', ['event' => 'postConnect'])
        ;
        $c->register('raphia_model', RaphiaDBAL::class) // TODO: bad name, change for something more general! (service and class) (query object?)
        // $c->register(RaphiaDBAL::class)
            ->addArgument(new Reference('doctrine.dbal.default_connection'))
        ;

        // Extensions
        $c->loadFromExtension('framework', [
            'secret' => '%secret%', // What about use $ uuid -v4
            'test' => $invoker->call('in_array', [
                'needle' => $this->getEnvironment(),
                'haystack' => ['test'],
                'strict' => true,
            ]), // test.client service for eg. PHPUnit
            'profiler' => [
                // 'enabled' => '%profiler_enabled%', // Do not enable in prod!
                'enabled' => in_array($this->getEnvironment(), ['dev', 'test'], true), // Do not enable in prod! or skip 'profiler'
            ],
            'form' => null, // Security login.
            'session' => null, // Security login.
            'csrf_protection' => true, // Security login.
            'translator' => null,
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
        $c->loadFromExtension('security', [
            'encoders' => [UserInterface::class => 'bcrypt'], // FOS\UserBundle\Model\UserInterface: bcrypt, sha512
            // 'acl' => ['connection' => 'default'],
            'role_hierarchy' => [
                'ROLE_ADMIN' => ['ROLE_USER'],
                'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
            ],
            'providers' => [
                'fos_userbundle' => ['id' => 'fos_user.user_provider.username'],
                // 'in_memory' => ['memory' => []],
            ],
            'firewalls' => [
                // 'secured_area' => ['anonymous'  => []],

                // Disabling the security for the web debug toolbar, the profiler and Assetic.
                'dev' => [
                    'pattern'          => '^/(_(profiler|wdt)|css|images|js)/',
                    'security'         => false,
                ],

                'main' => [
                    'pattern'                  => '^/', // .*
                    'form_login' => [
                        'provider'             => 'fos_userbundle',
                        'csrf_token_generator' => 'security.csrf.token_manager', // needs csrf_protection in the framework
                        # if you are using Symfony < 2.8, use the following config instead:
                        # csrf_provider: form.csrf_provider
                    ],
                    'logout'           => true,
                    'anonymous'        => true,
                ],
            ],
            'access_control' => [
                // URL of FOSUserBundle which need to be available to anonymous users
                ['path' => '^/login$', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                // ['path' => '^/register', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/resetting', 'role'=> 'IS_AUTHENTICATED_ANONYMOUSLY'],
        
                # Secured part of the site
                # This config requires being logged for the whole site and having the admin role for the admin part.
                # Change these rules to adapt them to your needs
                ['path' => '^/admin/', 'role' => ['ROLE_ADMIN']], // used?
                ['path' => '^/user/', 'role' => ['ROLE_SUPER_ADMIN']],
                ['path' => '^/register', 'role' => ['ROLE_SUPER_ADMIN']],
                ['path' => '^/$', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/.*', 'role' => ['ROLE_ACCEPTED_USER']],
            ],
        ]);
        $c->loadFromExtension('doctrine', [
            'dbal' => [
                // 'url' => 'sqlite:///:memory:', // Needs a way to write schema.
                // 'url' => 'sqlite:///../data/' . basename(__FILE__, ".php") . '.sqlite3',
                // 'url' => 'pgsql:host=/var/run/postgresql;user=', // Works with DBAL alone, but not inside Symfony.
                // 'driver' => 'pdo_pgsql', 'host' => '/var/run/postgresql', 'user' => null,
                // 'url' => 'pgsql:host=/var/run/postgresql;dbname=tmp_' . basename(__FILE__,".php"),
                // 'driver' => 'pdo_pgsql', 'host' => '/var/run/postgresql', 'dbname' => 'tmp_' . basename(__FILE__,".php"), 'user' => null,
                'driver' => '%database_driver%', 'host' => '%database_host%', 'dbname' => '%database_name%', 'user' => '%database_user%',
                'charset' => 'UTF8',
                'types' => [
                    'uuid'        => 'Ramsey\Uuid\Doctrine\UuidType',
                    // 'uuid_binary' => 'Ramsey\Uuid\Doctrine\UuidBinaryType',
                    // 'json' => 'Sonata\Doctrine\Types\JsonType',
                ],
                // 'mapping_types' => [
                    // 'uuid_binary' => 'binary',
                // ],
            ],
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                        // 'mappings' => [
                        //     'AppBundle' => [],
                            // 'FOSUserBundle' => [], # If SonataUserBundle extends it
                        // ],
                    ],
                ],
            ],
        ]);

        // FOS User Bundle
        //v Needed only by 'db_driver' => 'custom' and not by 'db_driver' => 'orm'
        //v Maybe not all of them are required even for custom
        $c->register('custom.util.password_updater', FOS\UserBundle\Util\PasswordUpdater::class)
            ->setPublic(false) // small performance boost
            ->addArgument(new Reference('security.encoder_factory'))
        ;

        // $c->register('custom.util.canonicalizer.default', FOS\UserBundle\Util\Canonicalizer::class)
        $c->register(FOS\UserBundle\Util\Canonicalizer::class)
            ->setPublic(false) // small performance boost
        ;
        $c->setAlias('custom.util.username_canonicalizer', FOS\UserBundle\Util\Canonicalizer::class);
        $c->setAlias('custom.util.email_canonicalizer', FOS\UserBundle\Util\Canonicalizer::class);
        $c->register('custom.util.canonical_fields_updater', FOS\UserBundle\Util\CanonicalFieldsUpdater::class)
            ->setPublic(false) // small performance boost
            ->addArgument(new Reference('custom.util.username_canonicalizer'))
            ->addArgument(new Reference('custom.util.email_canonicalizer'))
        ;

        /*
        // $c->setParameter('custom.model_manager_name', 'fos_user.backend_type_orm');
        $c->setParameter('custom.model_manager_name', null);
        $c->register('custom.object_manager', Doctrine\Common\Persistence\ObjectManager::class)
            // ->setPublic(false) // small performance boost
            ->addArgument('%custom.model_manager_name%')
            // ->setFactory([new Reference('fos_user.doctrine_registry'), 'getManager']); // alias to doctrine
            ->setFactory([new Reference('doctrine'), 'getManager']);
        ;

        // $c->setParameter('custom.model.user.class', \AppBundle\Entity\User::class);
        $c->register('custom.user_manager', FOS\UserBundle\Doctrine\UserManager::class) // Does not work
            // ->setPublic(false) // small performance boost
            ->addArgument(new Reference('custom.util.password_updater'))
            ->addArgument(new Reference('custom.util.canonical_fields_updater'))
            ->addArgument(new Reference('custom.object_manager'))
            // ->addArgument('%custom.model.user.class%')
            ->addArgument('%fos_user.model.user.class%')
        ;
        */
        $c->register('custom.user_manager', FosUserUtil\Model\UserManager::class) // Does not work, not sure, looks like it works fine
            // ->setPublic(false) // small performance boost
            ->addArgument(new Reference('custom.util.password_updater'))
            ->addArgument(new Reference('custom.util.canonical_fields_updater'))
            // ->addArgument(new Reference('custom.object_manager'))
            ->addArgument(new Reference('raphia_model'))
            // ->addArgument('%custom.model.user.class%')
            ->addArgument('%fos_user.model.user.class%')
        ;
        //^ Needed only by 'db_driver' => 'custom' and not by 'db_driver' => 'orm'
        $c->loadFromExtension('fos_user', [
            // 'db_driver' => 'orm', // Works very good, trying to replace by custom + service.user_manager
            'db_driver' => 'custom',
            'firewall_name' => 'main',
            'user_class' => FosUserUtil\Model\DefaultUser::class,
            // 'group' => [
            //     'group_class' => BaseGroup::class,
            //     'group_manager' => 'sonata.user.orm.group_manager',
            // ],
            'service' => [
                'user_manager' => 'custom.user_manager', // Needed only by 'db_driver' => 'custom' and not by 'db_driver' => 'orm'
                'mailer'       => 'fos_user.mailer.noop',
            ],
            'from_email' => [
                'address' => '%mailer_user%',
                'sender_name' => '%mailer_user%',
            ],
        ]);
        // configure WebProfilerBundle only if the bundle is enabled
        if (isset($this->bundles['WebProfilerBundle'])):
            $c->loadFromExtension('web_profiler', [
                'toolbar' => '%debug_toolbar%',
                'intercept_redirects' => '%debug_redirects%',
            ]);
        endif;
    }
}
