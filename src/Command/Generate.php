<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuiteCli\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sweetchuck\ComposerSuiteHandler\SuiteHandler;
use Sweetchuck\ComposerSuiteHandler\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Generate extends Command implements ContainerAwareInterface, LoggerAwareInterface
{

    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'generate';

    protected Filesystem $fs;

    protected SuiteHandler $suiteHandler;

    protected string $workingDirectory = '.';

    protected string $composerJsonFile = 'composer.json';

    /**
     * @var array<int|string, mixed>
     */
    protected array $composerJsonData = [];

    /**
     * @var array<string, suite-definition>
     */
    protected array $suiteDefinitions = [];

    /**
     * @var array{
     *     exitCode: int,
     * }
     */
    protected array $result = [
        'exitCode' => 0,
    ];

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Generates alternative composer.*.json files.')
            ->addArgument(
                'working-directory',
                InputArgument::OPTIONAL,
                'Path to the project root',
                '.',
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fs = $this->container->get('filesystem');
        $this->suiteHandler = $this->container->get('composer_suite_handler');

        try {
            $this
                ->validate($input)
                ->doIt();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return max($e->getCode(), 1);
        }

        return $this->result['exitCode'];
    }

    /**
     * @return $this
     */
    protected function validate(InputInterface $input)
    {
        $this->workingDirectory = $input->getArgument('working-directory');
        if ($this->workingDirectory === '') {
            $this->workingDirectory = '.';
        }

        $this->composerJsonFile = Path::join(
            $this->workingDirectory,
            'composer.json',
        );

        if (!$this->fs->exists($this->composerJsonFile)) {
            throw new \RuntimeException(
                sprintf(
                    "File %s does not exists",
                    $this->composerJsonFile,
                ),
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];
        $this->composerJsonData = Utils::decode(file_get_contents($this->composerJsonFile) ?: '{}');
        $this->suiteDefinitions = $this
            ->suiteHandler
            ->collectSuiteDefinitions(
                $this->composerJsonFile,
                $this->composerJsonData['extra'] ?? [],
            );

        if (!$this->suiteDefinitions) {
            $this->logger->warning(sprintf(
                'There are no suite definitions in project root: %s',
                Path::getDirectory($this->composerJsonFile),
            ));

            return $this;
        }

        $this->dumpSuites();

        return $this;
    }

    /**
     * @return $this
     */
    protected function dumpSuites()
    {
        foreach ($this->suiteDefinitions as $suiteDefinition) {
            $this->dumpSuite($suiteDefinition);
        }

        return $this;
    }

    /**
     * @param suite-definition $suiteDefinition
     *
     * @return $this
     */
    protected function dumpSuite(array $suiteDefinition)
    {
        $actions = $suiteDefinition['actions'] ?? [];
        $name = $suiteDefinition['name'] ?? 'unknown';
        if (!$actions) {
            $this->logger->warning(sprintf(
                'There are no action steps in the "%s" suite',
                $name,
            ));

            return $this;
        }

        $suiteFileName = $this->suiteHandler->suiteFileName(
            $this->composerJsonFile,
            $name,
        );

        $suiteData = $this->suiteHandler->generate($this->composerJsonData, $actions);
        $task = $this->suiteHandler->whatToDo($suiteFileName, $suiteData);
        $this->doItMessage($task, $suiteFileName);
        if (in_array($task, ['create', 'update'])) {
            $this->fs->dumpFile(
                $suiteFileName,
                Utils::encode($suiteData) . "\n",
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function doItMessage(string $task, string $fileName)
    {
        switch ($task) {
            case 'skip':
                $this->logger->info("no need to update <info>$fileName</info>");
                break;

            case 'create':
                $this->logger->info("create <info>$fileName</info>");
                break;

            case 'update':
                $this->logger->info("update <info>$fileName</info>");
                break;
        }

        return $this;
    }
}
