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

namespace Narrowspark\SecurityAdvisories\Test\Provider\Traits;

use DateTime;
use PHPUnit\Framework\Assert;
use function array_key_exists;
use function file_put_contents;
use function is_int;
use function is_string;
use function strpos;
use function var_export;

trait AssertAdvisoryTrait
{
    public static function assertAdvisory(array $advisories): void
    {
        file_put_contents('t', var_export($advisories, true));

        foreach ($advisories as $packageName => $data) {
            if (! is_string($packageName)) {
                Assert::fail('Key needs to be a string');
            }

            foreach ($data as $key => $value) {
                // assert if key is a cve string or date
                if (! is_string($key)) {
                    Assert::fail('Key is not a CVE- string or a datetime in format [\d+-\d+-\d+]');
                }

                if (! array_key_exists('title', $value)) {
                    Assert::fail('title key needs to provided');
                }

                if (array_key_exists('cve', $value) && $value['cve'] === null && is_string($value['cve']) && strpos($value['cve'], 'CVE-') === false) {
                    Assert::fail('The cve key can only have a CVE value string');
                }

                if (! array_key_exists('branches', $value)) {
                    Assert::fail('branches key needs to provided');
                }

                foreach ($value['branches'] as $version => $branch) {
                    if (! is_string($version) && ! is_int($version)) {
                        Assert::fail('key inside branches needs to be a branch name or version');
                    }

                    if (is_int($branch['time']) && $branch['time'] === null && is_string($branch['time']) && DateTime::createFromFormat(DateTime::ATOM, $branch['time']) === false) {
                        Assert::fail('time needs to be a datetime string or a timestamp');
                    }

                    if (! array_key_exists('versions', $branch)) {
                        Assert::fail('versions key needs to be provided');
                    }

                    foreach ($branch['versions'] as $version) {
                        if (! is_string($version)) {
                            Assert::fail('value of versions needs to be a string');
                        }
                    }
                }

                if (! array_key_exists('reference', $value)) {
                    Assert::fail('reference key needs to provided');
                }

                if (! is_string($value['reference'])) {
                    Assert::fail('reference needs to be a string');
                }
            }
        }
    }
}
