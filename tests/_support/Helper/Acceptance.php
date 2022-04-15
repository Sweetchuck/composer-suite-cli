<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuiteCli\Tests\Helper;

use Codeception\Module;

class Acceptance extends Module
{

    /**
     * @var array<string>
     */
    protected $requiredFields = [];

    /**
     * @var array{
     *     pharPath: string,
     * }
     */
    protected $config = [
        'pharPath' => './artifacts/composer-suite.phar',
    ];

    public function grabPharPath(): string
    {
        return $this->config['pharPath'];
    }
}
