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

namespace Narrowspark\SecurityAdvisories\Test;

use PHPUnit\Framework\TestCase;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function file_get_contents;

/**
 * @internal
 *
 * @small
 */
final class JsonTest extends TestCase
{
    public function testJson(): void
    {
        self::assertJson(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'security-advisories.json'));
    }
}
