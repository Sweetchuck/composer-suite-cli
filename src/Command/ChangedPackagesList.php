<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuiteCli\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sweetchuck\ComposerSuiteHandler\RequireDiffer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ChangedPackagesList extends Command implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'changed-packages:list';

    protected Filesystem $fs;

    protected RequireDiffer $requireDiffer;

    protected string $workingDirectory = '.';

    protected string $composerJsonFile = 'composer.json';

    /**
     * @var array{
     *     exitCode: int,
     *     diff: array<mixed>,
     * }
     */
    protected array $result = [
        'exitCode' => 0,
        'diff' => [],
    ];

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Lists the changed packages between composer.json and the composer.ACTUAL.json')
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
        $this->requireDiffer = $this->container->get('composer_require_differ');

        try {
            $this
                ->validate($input)
                ->executeCalculateDiff();
            foreach ($this->result['diff'] as $name => $diff) {
                $output->writeln($name);
            }
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
        $this->fs = $this->container->get('filesystem');
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
    protected function executeCalculateDiff()
    {
        $actualComposerJson = Path::join(
            $this->workingDirectory,
            getenv('COMPOSER') ?: 'composer.json',
        );

        $base = json_decode(
            file_get_contents($this->composerJsonFile) ?: '{}',
            true,
        );
        $extended = json_decode(
            file_get_contents($actualComposerJson) ?: '{}',
            true,
        );

        $this->result['diff'] = $this->requireDiffer->diff($base, $extended);

        return $this;
    }
}
