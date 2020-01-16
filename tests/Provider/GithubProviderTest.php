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

use Http\Client\Curl\Client;
use Narrowspark\SecurityAdvisories\Provider\GithubProvider;
use Narrowspark\SecurityAdvisories\Test\Provider\Traits\AssertAdvisoryTrait;
use PHPUnit\Framework\TestCase;
use Viserio\Component\Http\Response;

/**
 * @internal
 *
 * @small
 */
final class GithubProviderTest extends TestCase
{
    use AssertAdvisoryTrait;

    /**
     * @dataProvider provideFetchCases
     *
     * @param mixed $apiResponses
     */
    public function testFetch($apiResponses): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(...$apiResponses);

        $provider = new GithubProvider($client, 'some_token');

        self::assertAdvisory($provider->fetch());
    }

    /**
     * There is an "discussion" about Stream body pointer placement.
     *
     * @see https://groups.google.com/forum/#!topic/php-fig/S5YIw-Pu1yM
     *
     * @return array
     */
    public static function provideFetchCases(): iterable
    {
        $bodies = [
            <<<'F'
                {
                    "data": {
                        "securityVulnerabilities": {
                            "edges": [
                                {
                                    "cursor": "Y3Vyc29yOnYyOpK5MjAyMC0wMS0wOFQxODoxNTowNiswMTowMM0LdA==",
                                    "node": {
                                        "vulnerableVersionRange": "< 0.12.0",
                                        "package": {
                                          "name": "enshrined/svg-sanitize"
                                        },
                                        "advisory": {
                                            "description": "enshrined/svg-sanitize before 0.12.0 mishandles script and data values in attributes, as demonstrated by unexpected whitespace such as in the javascript&#9;:alert substring.",
                                            "references": [
                                                {
                                                  "url": "https://nvd.nist.gov/vuln/detail/CVE-2019-18857"
                                                }
                                            ],
                                            "publishedAt": "2020-01-08T17:15:37Z"
                                        },
                                        "severity": "MODERATE"
                                    }
                                }
                            ],
                            "pageInfo": {
                                "hasNextPage": true
                            }
                        }
                    }
                }
                F,
            <<<'R'
                {
                    "data": {
                        "securityVulnerabilities": {
                            "edges": [
                                {
                                    "cursor": "Y3Vyc29yOnYyOpK5MjAxOS0xMi0xN1QyMDo0MjozMiswMTowMM0LXQ==",
                                    "node": {
                                        "vulnerableVersionRange": ">= 4.5.0, < 4.8.6",
                                        "package": {
                                            "name": "contao/core-bundle"
                                        },
                                        "advisory": {
                                            "description": "### Impact\n\nA back end user with access to the form generator can upload arbitrary files and execute them on the server.\n\n### Patches\n\nUpdate to Contao 4.4.46 or 4.8.6.\n\n### Workarounds\n\nConfigure your web server so it does not execute PHP files and other scripts in the Contao file upload directory.\n\n### References\n\nhttps://contao.org/en/security-advisories/unrestricted-file-uploads.html\n\n### For more information\n\nIf you have any questions or comments about this advisory, open an issue in [contao/contao](https://github.com/contao/contao/issues/new/choose).",
                                            "references": [
                                                {
                                                    "url": "https://github.com/contao/contao/security/advisories/GHSA-wjx8-cgrm-hh8p"
                                                },
                                                {
                                                    "url": "https://nvd.nist.gov/vuln/detail/CVE-2019-19745"
                                                }
                                            ],
                                            "publishedAt": "2019-12-17T22:53:10Z"
                                        },
                                        "severity": "HIGH"
                                    }
                                }
                            ],
                            "pageInfo": {
                                "hasNextPage": true
                            }
                        }
                    }
                }
                R,
            <<<'S'
                {
                    "data": {
                        "securityVulnerabilities": {
                            "edges": [],
                            "pageInfo": {
                                "hasNextPage": false,
                                "endCursor": null
                            }
                        }
                    }
                }
                S,
        ];

        $data = [0 => [0 => []]];

        foreach ($bodies as $body) {
            $response = new Response(200, [], $body);
            $response->getBody()->rewind();

            $data[0][0][] = $response;
        }

        return $data;
    }
}
