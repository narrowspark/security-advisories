<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Console\Command\AbstractCommand;

class CommitCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'commit';

    /**
     * {@inheritdoc}
     */
    protected $signature = 'commit';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Commit changes to narrowspark/security-advisories';

    /**
     * Path to dir.
     *
     * @var string
     */
    protected $mainDir;

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $filePath = __DIR__ . \DIRECTORY_SEPARATOR . 'update';

        if (! \file_exists($filePath)) {
            $this->info('Nothing to update.');

            return 0;
        }

        \unlink($filePath);

        $this->info('Making a commit to narrowspark/security-advisories.');

        $gitCommitProcess = new Process('git commit -a -m "Automatically updated on ' . (new \DateTimeImmutable('now'))->format(\DateTimeImmutable::RFC7231) . '"');
        $gitCommitProcess->run();

        if (! $gitCommitProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCommitProcess))->getMessage());

            return 1;
        }

        $this->info($gitCommitProcess->getOutput());

        $gitCommitProcess = new Process('git push');
        $gitCommitProcess->run();

        if (! $gitCommitProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCommitProcess))->getMessage());

            return 1;
        }

        $this->info($gitCommitProcess->getOutput());

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->mainDir = \dirname(__DIR__);
    }
}
