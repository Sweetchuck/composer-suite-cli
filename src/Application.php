<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuiteCli;

use Sweetchuck\ComposerSuiteCli\Command\Generate;
use Sweetchuck\ComposerSuiteHandler\SuiteHandler;
use Symfony\Component\Console\Application as ApplicationBase;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition as ServiceDefinition;
use Symfony\Component\DependencyInjection\Reference as ServiceReference;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
class Application extends ApplicationBase implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return $this
     */
    public function initialize()
    {
        $this
            ->initializeContainer()
            ->initializeCommands();

        return $this;
    }

    /**
     * @return $this
     */
    protected function initializeContainer()
    {
        if ($this->container === null) {
            $this->container = new ContainerBuilder();
        }

        if (!$this->container->has('output')) {
            $service = new ServiceDefinition(ConsoleOutput::class);
            $this->container->setDefinition('output', $service);
        }

        if (!$this->container->has('logger')) {
            $service = new ServiceDefinition(ConsoleLogger::class);
            $service->addArgument(new ServiceReference('output'));
            $this->container->setDefinition('logger', $service);
        }

        if (!$this->container->has('filesystem')) {
            $this->container->register('filesystem', Filesystem::class);
        }

        if (!$this->container->has('composer_suite_handler')) {
            $this->container->register('composer_suite_handler', SuiteHandler::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initializeCommands()
    {
        $cmdGenerate = new Generate();
        $cmdGenerate->setContainer($this->container);
        $cmdGenerate->setLogger($this->container->get('logger'));
        $this->add($cmdGenerate);

        return $this;
    }
}
