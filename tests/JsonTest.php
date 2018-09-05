<?php
declare(strict_types=1);
namespace Narrowspark\SecurityAdvisories\Test;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class JsonTest extends TestCase
{
    public function testJson(): void
    {
        static::assertJson(\file_get_contents(\dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'security-advisories.json'));
    }
}
