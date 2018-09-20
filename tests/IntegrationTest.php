<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories\Test;

use Narrowspark\SecurityAdvisories\BuildCommand;
use Narrowspark\SecurityAdvisories\CommitCommand;
use Symfony\Component\Filesystem\Filesystem;
use Viserio\Component\Console\Tester\CommandTestCase;

/**
 * @internal
 */
final class IntegrationTest extends CommandTestCase
{
    /**
     * @var \Narrowspark\SecurityAdvisories\BuildCommand
     */
    private $buildCommand;

    /**
     * @var \Narrowspark\SecurityAdvisories\CommitCommand
     */
    private $commitCommand;

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

        $this->commitCommand = new class() extends CommitCommand {
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

        static::assertContains('Cloning FriendsOfPHP/security-advisories.', $output);
        static::assertContains('Start collection security advisories.', $output);
        static::assertContains('Start writing security-advisories.json.', $output);
    }

    /**
     * @depends testBuild
     */
    public function testCommit(): void
    {
        $tester = $this->executeCommand($this->commitCommand);

        $output = $tester->getDisplay(true);

        static::assertContains('Nothing to update.', $output);
    }
}
