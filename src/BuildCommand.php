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

use Http\Client\Curl\Client;
use Narrowspark\SecurityAdvisories\Provider\FriendsOfPhpProvider;
use Narrowspark\SecurityAdvisories\Provider\GithubProvider;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Console\Command\AbstractCommand;
use Viserio\Component\Filesystem\Filesystem;
use Viserio\Component\Parser\Dumper\JsonDumper;
use Viserio\Contract\Console\Exception\InvalidArgumentException;
use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use function array_merge;
use function count;
use function dirname;
use function getenv;
use function is_dir;
use function is_string;
use function ksort;

class BuildCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'build';

    /**
     * {@inheritdoc}
     */
    protected $signature = 'build
        [--token= : Github token]
    ';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Builds the security-advisories.json';

    /**
     * Path to dir.
     *
     * @var string
     */
    protected $rootDir;

    /** @var string */
    protected $securityAdvisoriesSha;

    /** @var string */
    protected $downloadDir;

    /**
     * A JsonDumper instance.
     *
     * @var \Viserio\Component\Parser\Dumper\JsonDumper
     */
    private $jsonDumper;

    /**
     * A Filesystem instance.
     *
     * @var \Viserio\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * Create a new Builder Command Instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
        $this->jsonDumper = new JsonDumper();
        $this->jsonDumper->setOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        if (is_dir($this->downloadDir)) {
            $this->filesystem->deleteDirectory($this->downloadDir);
        }

        $this->info('Fetching FriendsOfPHP/security-advisories.');

        $friendsOfPHPSecurityAdvisories = (new FriendsOfPhpProvider($this->downloadDir))->fetch();

        $this->info('Fetching Github security-advisories.');

        $token = $this->option('token') ?? getenv('GITHUB_TOKEN');

        if (! is_string($token) || $token === '') {
            throw new InvalidArgumentException('Please provide a github token.');
        }

        $githubSecurityAdvisories = (new GithubProvider(new Client(), $token))->fetch();

        $gitShaProcess = Process::fromShellCommandline('cd ' . $this->downloadDir . ' && git rev-parse --verify HEAD');
        $gitShaProcess->run();

        if (! $gitShaProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitShaProcess))->getMessage());

            return 1;
        }

        $commitSha1 = count($githubSecurityAdvisories) . $gitShaProcess->getOutput();
        $update = true;

        if ($this->filesystem->has($this->securityAdvisoriesSha)) {
            $update = $commitSha1 !== \Safe\file_get_contents($this->securityAdvisoriesSha);
        }

        if ($update === false) {
            $this->info('security-advisories.json is up to date.');

            return 0;
        }

        $data = array_merge($githubSecurityAdvisories, $friendsOfPHPSecurityAdvisories);

        ksort($data);

        $this->info('Start writing security-advisories.json.');

        $jsonPath = $this->rootDir . DIRECTORY_SEPARATOR . 'security-advisories.json';

        $this->filesystem->delete($jsonPath);
        $this->filesystem->delete($this->securityAdvisoriesSha);

        $this->filesystem->write($jsonPath, $this->jsonDumper->dump($data));
        $this->filesystem->write($this->securityAdvisoriesSha, $commitSha1);

        $this->filesystem->deleteDirectory($this->downloadDir);
        $this->filesystem->write(__DIR__ . DIRECTORY_SEPARATOR . 'update', '');

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->securityAdvisoriesSha = $this->rootDir . DIRECTORY_SEPARATOR . 'security-advisories-sha';
        $this->downloadDir = $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'security-advisories';
    }
}
