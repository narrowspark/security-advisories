<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisoriesBuilder;

use Symfony\Component\Console\Helper\ProgressBar;
use Viserio\Component\Console\Command\AbstractCommand;
use Viserio\Component\Contract\Parser\Exception\ParseException;
use Viserio\Component\Parser\Dumper\JsonDumper;
use Viserio\Component\Parser\Parser\YamlParser;

class BuildCommand extends AbstractCommand
{
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
     * Create a new Builder Instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->yamlParser = new YamlParser();
        $this->jsonDumper = new JsonDumper();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $dir = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__ . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'sensiolabs' . \DIRECTORY_SEPARATOR . 'security-advisories'
            )
        );

        $progress = new ProgressBar($this->getOutput(), \count(\iterator_to_array($dir)));
        $progress->start();

        $data = [];

        /** @var $dir \SplFileInfo[] */
        foreach ($dir as $file) {
            if (! $file->isFile()) {
                $progress->advance();

                continue;
            }

            $path = \str_replace(__DIR__ . \DIRECTORY_SEPARATOR, '', $file->getPathname());

            if ($file->getExtension() !== 'yaml') {
                $messages[$path][] = 'The file extension should be ".yaml".';

                continue;
            }

            try {
                $data[] = $this->yamlParser->parse(\file_get_contents($file));
            } catch (ParseException $exception) {
                $messages[$path][] = $exception->getMessage();
            }

            $progress->advance();
        }

        $progress->finish();
    }
}
