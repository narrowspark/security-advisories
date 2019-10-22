<?php

declare(strict_types=1);

/**
 * This file is part of Narrowspark Framework.
 *
 * (c) Daniel Bannert <d.bannert@anolilab.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Narrowspark\SecurityAdvisories\Test;

use Narrowspark\SecurityAdvisories\BuildCommand;
use Symfony\Component\Filesystem\Filesystem;
use Viserio\Component\Console\Tester\CommandTestCase;

/**
 * @internal
 *
 * @small
 */
final class IntegrationTest extends CommandTestCase
{
    /** @var \Narrowspark\SecurityAdvisories\BuildCommand */
    private $buildCommand;

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        (new Filesystem())->remove([
            __DIR__ . \DIRECTORY_SEPARATOR . 'build',
            __DIR__ . \DIRECTORY_SEPARATOR . 'security-advisories-sha',
            __DIR__ . \DIRECTORY_SEPARATOR . 'security-advisories.json',
            \dirname(__DIR__, 1) . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'update',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildCommand = new class() extends BuildCommand {
            /**
             * {@inheritdoc}
             */
            protected function configure(): void
            {
                $this->mainDir = __DIR__;
            }
        };
    }

    public function testBuild(): void
    {
        $tester = $this->executeCommand($this->buildCommand);

        $output = $tester->getDisplay(true);

        self::assertStringContainsString('Cloning FriendsOfPHP/security-advisories.', $output);
        self::assertStringContainsString('Start collection security advisories.', $output);
        self::assertStringContainsString('Start writing security-advisories.json.', $output);
    }
}
