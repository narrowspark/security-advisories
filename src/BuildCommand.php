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

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Console\Command\AbstractCommand;
use Viserio\Component\Parser\Dumper\JsonDumper;
use Viserio\Component\Parser\Parser\YamlParser;
use Viserio\Contract\Parser\Exception\ParseException;

class BuildCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'build';

    /**
     * {@inheritdoc}
     */
    protected $signature = 'build';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Builds the security-advisories.json';

    /**
     * Path to dir.
     *
     * @var string
     */
    protected $mainDir;

    /**
     * A YamlParser instance.
     *
     * @var \Viserio\Component\Parser\Parser\YamlParser
     */
    private $yamlParser;

    /**
     * A JsonDumper instance.
     *
     * @var \Viserio\Component\Parser\Dumper\JsonDumper
     */
    private $jsonDumper;

    /**
     * A Filesystem instance.
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * Create a new Builder Command Instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
        $this->yamlParser = new YamlParser();
        $this->jsonDumper = new JsonDumper();
        $this->jsonDumper->setOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $securityAdvisoriesSha = $this->mainDir . \DIRECTORY_SEPARATOR . 'security-advisories-sha';
        $gitDir = $this->mainDir . \DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'git';

        if (\is_dir($gitDir)) {
            $this->filesystem->remove($gitDir);
        }

        $this->info('Cloning FriendsOfPHP/security-advisories.');
        $this->getOutput()->writeln('');

        $gitCloneProcess = Process::fromShellCommandline('git clone git@github.com:FriendsOfPHP/security-advisories.git ' . $gitDir);
        $gitCloneProcess->run();

        if (! $gitCloneProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCloneProcess))->getMessage());

            return 1;
        }

        $gitShaProcess = Process::fromShellCommandline('cd ' . $gitDir . ' && git rev-parse --verify HEAD');
        $gitShaProcess->run();

        if (! $gitShaProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitShaProcess))->getMessage());

            return 1;
        }

        $commitSha1 = $gitShaProcess->getOutput();
        $update = true;

        if ($this->filesystem->exists($securityAdvisoriesSha)) {
            $update = $commitSha1 !== \file_get_contents($securityAdvisoriesSha);
        }

        if ($update === false) {
            $this->info('security-advisories.json is up to date.');

            return 0;
        }

        $finder = Finder::create()->files()->name('/(\.yaml|.\yml)$/')->in($gitDir)->sortByName()->ignoreDotFiles(true);

        $this->info('Start collection security advisories.');

        $progress = new ProgressBar($this->getOutput(), $finder->count());
        $progress->start();

        $data = [];
        $messages = [];

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $path = \str_replace($this->mainDir, '', (string) \realpath($file->getPathname()));

            try {
                $packageName = \str_replace($gitDir . \DIRECTORY_SEPARATOR, '', $file->getPath());
                $fileName = \str_replace('.' . $file->getExtension(), '', $file->getFilename());

                $data[$packageName][$fileName] = $this->yamlParser->parse((string) \file_get_contents($file->__toString()));
            } catch (ParseException $exception) {
                $messages[$path][] = $exception->getMessage();
            }

            $progress->advance();
        }

        $progress->finish();

        if (\count(\array_filter($messages)) !== 0) {
            $this->getOutput()->writeln('');
            $this->getOutput()->writeln('');

            foreach ($messages as $path => $message) {
                foreach ($message as $m) {
                    $this->warn($path . ': ' . $m);
                }
            }
        }

        $this->getOutput()->writeln('');
        $this->info('Start writing security-advisories.json.');

        $this->filesystem->dumpFile($this->mainDir . \DIRECTORY_SEPARATOR . 'security-advisories.json', $this->jsonDumper->dump($data));
        $this->filesystem->dumpFile($securityAdvisoriesSha, $commitSha1);

        $this->filesystem->remove($gitDir);

        $this->filesystem->dumpFile(__DIR__ . \DIRECTORY_SEPARATOR . 'update', '');

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
