<?php

namespace Rodrigodiez\Component\RichConsole;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class Application
 *
 * @package Rodrigodiez\Component\RichConsole
 * @author Rodrigo Díez Villamuera <rodrigo.diez@netropy.es>
 */
class Application extends ConsoleApplication
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $container;

    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array|mixed
     */
    protected $appInfo = array();


    /**
     * @param null $configPath
     * @param array $configFilenames
     */
    function __construct($configPath = null, $configFilenames = array())
    {
        $configPath = $configPath == null? __DIR__ . '/../../../../../../app/config': $configPath;
        $this->container = new ContainerBuilder();

        // Load app parameters and config files into container
        $loader = new YamlFileLoader($this->container, new FileLocator($configPath));
        $loader->load('parameters.yml');

        foreach ($configFilenames as $filename) {
            $loader->load($filename);
        }

        $this->appInfo = $this->container->getParameter('application.info');
        parent::__construct($this->appInfo['name'], $this->appInfo['version']);

        // Set dispatcher definition, register listeners and subscribers
        $dispatcherDef = $this->container->register('event_dispatcher', 'Symfony\\Component\\EventDispatcher\\ContainerAwareEventDispatcher');
        $dispatcherDef->addArgument($this->container);
        $this->registerEventListeners();

        $this->container->compile();

        // Once container is compiled we can get the event_dispatcher from dic
        $this->dispatcher = $this->container->get('event_dispatcher');

        // Add console commands (services console.command tagged)
        foreach ($this->getTaggedCommands() as $id) {
            $command = $this->container->get($id);
            $this->add($command);
        }
    }

    /**
     * Find definitions tagged as console.command and add them
     * to our application
     *
     * @returns array
     * @throws \InvalidArgumentException
     */
    protected function getTaggedCommands()
    {
        $taggedIds = $this->container->findTaggedServiceIds('console.command');
        $validIds = array();

        foreach($taggedIds as $id => $tagAttributes) {
            $definition = $this->container->getDefinition($id);
            $refl = new \ReflectionClass($definition->getClass());

            if (!$refl->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')) {
                throw new \InvalidArgumentException(sprintf('The service "%s" tagged "console.command" must be must be a subclass of "Symfony\\Component\\Console\\Command\\Command".', $id));
            }

            if (!$definition->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" tagged "console.command" must be public.', $id));
            }

            if ($refl->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" tagged "console.command" must not be abstract.', $id));
            }

            if ($refl->implementsInterface('Symfony\\Component\\DependencyInjection\\ContainerAwareInterface')) {
                $definition->addMethodCall('setContainer', array($this->container));
            }

            $validIds []= $id;
        }

        return $validIds;
    }

    /**
     * Search definitions tagged as kernel.event_listener or
     * kernel_event_subscriber and add them to the event_dispatcher
     *
     * @see https://github.com/symfony/HttpKernel/blob/master/DependencyInjection/RegisterListenersPass.php
     * @throws \InvalidArgumentException
     */
    protected function registerEventListeners()
    {
        $definition = $this->container->findDefinition('event_dispatcher');

        foreach ($this->container->findTaggedServiceIds('kernel.event_listener') as $id => $events) {
            $def = $this->container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
            }

            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "kernel.event_listener" tags.', $id));
                }

                if (!isset($event['method'])) {
                    $event['method'] = 'on'.preg_replace_callback(array(
                                '/(?<=\b)[a-z]/i',
                                '/[^a-z0-9]/i',
                            ), function ($matches) { return strtoupper($matches[0]); }, $event['event']);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }

                $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
            }
        }

        foreach ($this->container->findTaggedServiceIds('kernel.event_subscriber') as $id => $attributes) {
            $def = $this->container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $def->getClass();

            $refClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('addSubscriberService', array($id, $class));
        }
    }
}
 