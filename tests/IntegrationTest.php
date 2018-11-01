<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories\Test;

use Narrowspark\SecurityAdvisories\BuildCommand;
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

        $this->assertContains('Cloning FriendsOfPHP/security-advisories.', $output);
        $this->assertContains('Start collection security advisories.', $output);
        $this->assertContains('Start writing security-advisories.json.', $output);
    }
}
