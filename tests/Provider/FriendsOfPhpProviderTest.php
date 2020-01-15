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

namespace Narrowspark\SecurityAdvisories\Test\Provider;

use Narrowspark\SecurityAdvisories\Provider\FriendsOfPhpProvider;
use Narrowspark\SecurityAdvisories\Test\Provider\Traits\AssertAdvisoryTrait;
use PHPUnit\Framework\TestCase;
use Viserio\Component\Filesystem\Filesystem;
use const DIRECTORY_SEPARATOR;
use function dirname;

/**
 * @internal
 *
 * @small
 */
final class FriendsOfPhpProviderTest extends TestCase
{
    use AssertAdvisoryTrait;

    /** @var string */
    private $gitDownloadDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->gitDownloadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixture' . DIRECTORY_SEPARATOR . 'security-advisories';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->deleteDirectory($this->gitDownloadDir);
    }

    public function testFetch(): void
    {
        $provider = new FriendsOfPhpProvider($this->gitDownloadDir);

        self::assertAdvisory($provider->fetch());

        $this->expectNotToPerformAssertions();
    }
}
