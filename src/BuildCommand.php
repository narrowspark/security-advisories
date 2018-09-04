<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Console\Command\AbstractCommand;
use Viserio\Component\Contract\Parser\Exception\ParseException;
use Viserio\Component\Parser\Dumper\JsonDumper;
use Viserio\Component\Parser\Parser\YamlParser;

class BuildCommand extends AbstractCommand
{
    /**
     * @var string
     */
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Create a new Builder Instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->filesystem   = new Filesystem();
        $this->yamlParser   = new YamlParser();
        $this->jsonDumper   = new JsonDumper();
        $this->jsonDumper->setOptions(JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $mainDir                = \dirname(__DIR__);
        $securityAdvisoriesSha  = $mainDir . \DIRECTORY_SEPARATOR . 'security-advisories-sha';
        $gitDir                 = $mainDir . \DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'git';

        $this->info('Cloning FriendsOfPHP/security-advisories.');
        $this->getOutput()->writeln('');

        $gitCloneProcess = new Process('git clone git@github.com:FriendsOfPHP/security-advisories.git ' . $gitDir);
        $gitCloneProcess->run();

        if (! $gitCloneProcess->isSuccessful()) {
            $this->error((new ProcessFailedException($gitCloneProcess))->getMessage());
        }

        $gitSha1Process = new Process('cd ' . $gitDir . ' && git rev-parse --verify HEAD');
        $gitSha1Process->run();

        if (! $gitSha1Process->isSuccessful()) {
            $commitSha1 = null;
            $this->error((new ProcessFailedException($gitSha1Process))->getMessage());
        } else {
            $commitSha1 = $gitSha1Process->getOutput();
        }

        $update = true;

        if ($commitSha1 !== null && \file_exists($securityAdvisoriesSha)) {
            $update = $commitSha1 !== \file_get_contents($securityAdvisoriesSha);
        }

        if ($update === false) {
            $this->info('security-advisories.json is up to date.');
            $this->filesystem->remove($gitDir);

            return 0;
        }

        $securityAdvisoriesDir = $mainDir . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'sensiolabs' . \DIRECTORY_SEPARATOR . 'security-advisories';
        $dir                   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($securityAdvisoriesDir));

        $this->info('Start collection security advisories.');

        $progress = new ProgressBar($this->getOutput(), \count(\iterator_to_array($dir)));
        $progress->start();

        $data     = [];
        $messages = [];

        foreach ($dir as $file) {
            if (! $file->isFile()) {
                $progress->advance();

                continue;
            }

            $path = \str_replace($mainDir, '', (string) \realpath($file->getPathname()));

            if ($file->getExtension() !== 'yaml') {
                $messages[$path][] = 'The file extension should be ".yaml".';

                continue;
            }

            try {
                $packageName = \str_replace($securityAdvisoriesDir . \DIRECTORY_SEPARATOR, '', (string) $file->getPath());
                $fileName    = \str_replace($file->getExtension(), '', (string) $file->getFilename());

                $data[$packageName][$fileName] = $this->yamlParser->parse((string) \file_get_contents($file->__toString()));
            } catch (ParseException $exception) {
                $messages[$path][] = $exception->getMessage();
            }

            $progress->advance();
        }

        $progress->finish();

        if (\count(\array_filter($messages)) !==0) {
            $this->getOutput()->writeln('');
            $this->getOutput()->writeln('');

            foreach ($messages as $path => $message) {
                foreach ($message as $m) {
                    $this->warn($path . ': ' . $m);
                }
            }
        }

        $this->getOutput()->writeln('');
        $this->getOutput()->writeln('');
        $this->info('Start writing security-advisories.json.');

        $this->filesystem->dumpFile($mainDir . \DIRECTORY_SEPARATOR . 'security-advisories.json', $this->jsonDumper->dump($data));
        $this->filesystem->dumpFile($securityAdvisoriesSha, $commitSha1);
        $this->filesystem->remove($gitDir);

        return 0;
    }
}
