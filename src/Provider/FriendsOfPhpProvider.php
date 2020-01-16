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

namespace Narrowspark\SecurityAdvisories\Provider;

use Narrowspark\SecurityAdvisories\Contract\Provider as ProviderContract;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Viserio\Component\Finder\Finder;
use Viserio\Component\Parser\Parser\YamlParser;

class FriendsOfPhpProvider implements ProviderContract
{
    private const URL = 'git@github.com:FriendsOfPHP/security-advisories.git';

    /**
     * Path to download friends of php security yaml advisories.
     *
     * @var string
     */
    private $downloadDir;

    /**
     * A YamlParser instance.
     *
     * @var \Viserio\Component\Parser\Parser\YamlParser
     */
    private $yamlParser;

    /**
     * Create a new FriendsOfPhpProvider instance.
     *
     * @param string $downloadDir
     */
    public function __construct(string $downloadDir)
    {
        $this->downloadDir = $downloadDir;
        $this->yamlParser = new YamlParser();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): array
    {
        $gitCloneProcess = Process::fromShellCommandline(\Safe\sprintf('git clone %s %s', self::URL, $this->downloadDir));
        $gitCloneProcess->run();

        if (! $gitCloneProcess->isSuccessful()) {
            throw new ProcessFailedException($gitCloneProcess);
        }

        $finder = Finder::create()
            ->name('/(\.yaml|.\yml)$/')
            ->files()
            ->in($this->downloadDir)
            ->sortByName()
            ->ignoreDotFiles(true);

        $data = [];

        /** @var \Viserio\Contract\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $data[$file->getSubPath()][$file->getFilenameWithoutExtension()] = $this->yamlParser->parse($file->getContents());
        }

        return $data;
    }
}
