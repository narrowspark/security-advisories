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
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $gitDiffProcess = new Process('git diff --exit-code');
        $gitDiffProcess->run();

        if ($gitDiffProcess->isSuccessful()) {
            $this->info('Nothing to update.');

            return 0;
        }

        $this->info('Making a commit to narrowspark/security-advisories.');

        $gitCommitProcess = new Process('git commit -a -m "Automatically updated on '.(new \DateTimeImmutable('now'))->format(\DateTimeImmutable::RFC7231).'"');
        $gitCommitProcess->run();

        if (! $gitCommitProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCommitProcess))->getMessage());

            return 1;
        }

        return 0;
    }
}
