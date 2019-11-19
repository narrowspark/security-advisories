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

namespace Narrowspark\SecurityAdvisories;

use DateTimeImmutable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Console\Command\AbstractCommand;
use const DIRECTORY_SEPARATOR;
use function dirname;

class CommitCommand extends AbstractCommand
{
    /** @var string */
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
     * A Filesystem instance.
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * Create a new Commit Command Instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'update';

        if (! $this->filesystem->exists($filePath)) {
            $this->info('Nothing to update.');

            return 0;
        }

        $this->filesystem->remove($filePath);

        $this->info('Making a commit to narrowspark/security-advisories.');

        $rootPath = dirname(__DIR__, 1);
        $filesToCommit = ' -o ' . $rootPath . DIRECTORY_SEPARATOR . 'security-advisories.json  -o ' . $rootPath . DIRECTORY_SEPARATOR . 'security-advisories-sha';

        $gitCommitProcess = Process::fromShellCommandline(
            'git commit -m "Automatically updated on ' . (new DateTimeImmutable('now'))->format(DateTimeImmutable::RFC7231) . '"' . $filesToCommit
        );
        $gitCommitProcess->run();

        if (! $gitCommitProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCommitProcess))->getMessage());

            return 1;
        }

        $this->info($gitCommitProcess->getOutput());

        $gitCommitProcess = Process::fromShellCommandline('git push origin HEAD:master --quiet > /dev/null 2>&1');
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
        $this->mainDir = dirname(__DIR__);
    }
}
