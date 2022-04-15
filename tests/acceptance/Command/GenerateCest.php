<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuiteCli\Tests\Acceptance\Command;

use Sweetchuck\ComposerSuiteCli\Tests\AcceptanceTester;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCest
{

    public function _before(): void
    {
        $this->clearComposerSuites();
    }

    public function _after(): void
    {
        $this->clearComposerSuites();
    }

    /**
     * @return $this
     */
    protected function clearComposerSuites()
    {
        $fs = new Filesystem();
        $fixturesDir = codecept_data_dir('fixtures');
        /** @var \Iterator<\SplFileInfo> $files */
        $files = new \GlobIterator("$fixturesDir/*/composer.*.json");
        while ($files->valid()) {
            $current = $files->current();
            $fs->remove($current->getPathname());
            $files->next();
        }

        return $this;
    }

    public function generateSuccess(AcceptanceTester $I): void
    {
        $projectRoot = codecept_data_dir('fixtures/project_01');
        $pharPath = $I->grabPharPath();
        $I->assertNotEmpty($pharPath);

        $I->assertFileNotExists("$projectRoot/composer.symfony4.json");
        $I->assertFilenotExists("$projectRoot/composer.symfony5.json");

        $I->runShellCommand(
            sprintf(
                '%s generate %s',
                escapeshellcmd($pharPath),
                escapeshellarg($projectRoot),
            ),
        );

        $I->assertFileExists("$projectRoot/composer.symfony4.json");
        $I->assertJsonFileEqualsJsonFile(
            "$projectRoot/composer.symfony4.json",
            "$projectRoot/expected.symfony4.json",
        );

        $I->assertFileExists("$projectRoot/composer.symfony5.json");
        $I->assertJsonFileEqualsJsonFile(
            "$projectRoot/composer.symfony5.json",
            "$projectRoot/expected.symfony5.json",
        );
    }
}
