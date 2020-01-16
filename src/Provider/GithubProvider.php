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

use InvalidArgumentException;
use Narrowspark\SecurityAdvisories\Contract\Provider as ProviderContract;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Safe\DateTimeImmutable;
use Viserio\Component\Http\Request;
use Viserio\Component\Parser\Dumper\JsonDumper;
use Viserio\Component\Parser\Parser\JsonParser;
use function array_key_exists;
use function array_merge;
use function count;
use function end;
use function explode;
use function implode;
use function strpos;

class GithubProvider implements ProviderContract
{
    /** @var string */
    private const GRAPHQL_QUERY = 'query {
        securityVulnerabilities(ecosystem: COMPOSER, first: 100 %s) {
            edges {
                cursor
                node {
                    vulnerableVersionRange
                    package {
                      name
                    }
                    advisory {
                        description
                        references {
                            url
                        }
                        publishedAt
                    }
                    severity
                }
            }
            pageInfo {
                hasNextPage
            }
        }
    }';

    /** @var \Psr\Http\Client\ClientInterface */
    private $client;

    /** @var string */
    private $token;

    /** @var \Viserio\Component\Parser\Parser\JsonParser */
    private $jsonParser;

    /** @var \Viserio\Component\Parser\Dumper\JsonDumper */
    private $jsonDumper;

    /**
     * Create a new GithubProvider instance.
     *
     * @param \Psr\Http\Client\ClientInterface $client
     * @param string                           $token
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ClientInterface $client, string $token)
    {
        if ($token === '') {
            throw new InvalidArgumentException('Token for the github client cant be empty.');
        }

        $this->client = $client;
        $this->token = $token;
        $this->jsonParser = new JsonParser();
        $this->jsonDumper = new JsonDumper();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Viserio\Contract\Parser\Exception\DumpException
     * @throws \Safe\Exceptions\StringsException
     */
    public function fetch(): array
    {
        $advisories = [];
        $cursor = '';

        do {
            $response = $this->client->sendRequest($this->createRequest($cursor));

            $data = $this->jsonParser->parse((string) $response->getBody());

            $vulnerabilities = $data['data']['securityVulnerabilities'];
            $advisories = array_merge($advisories, $vulnerabilities['edges']);

            if (! $hasNextPage = $vulnerabilities['pageInfo']['hasNextPage']) {
                continue;
            }

            $cursor = end($advisories)['cursor'];
        } while ($hasNextPage);

        $preparedAdvisories = [];
        $count = 0;

        foreach ($advisories as $advisory) {
            $node = $advisory['node'];

            $packageName = $node['package']['name'];

            if (array_key_exists($packageName, $preparedAdvisories)) {
                $packages = $preparedAdvisories[$packageName];
            } else {
                $packages = [];
            }

            $references = $node['advisory']['references'];

            /** @var null|string $cve */
            $cve = null;
            /** @var null|string $link */
            $link = null;

            foreach ($references as $reference) {
                $link = $reference['url'];

                if (($pos = strpos($link, 'CVE-')) !== false) {
                    $cve = \Safe\substr($link, $pos);
                }
            }

            $dateTime = new DateTimeImmutable($node['advisory']['publishedAt']);

            $name = $cve ?? $dateTime->format('Y-m-d');
            $package = [
                'title' => $node['advisory']['description'],
                'link' => $link,
                'cve' => $cve,
                'branches' => [],
            ];

            if (array_key_exists($name, $packages)) {
                $count++;
                $name .= '-' . $count;
            }

            $versions = explode(', ', $node['vulnerableVersionRange']);

            // Github is not providing branch version, we will use the last version as a branch version 4.12.4 => 4.12.x or 4.0 => 4.x
            \Safe\preg_match_all('/\d+/', (string) end($versions), $matches);

            $matches = $matches[0];

            unset($matches[count($matches) - 1]);

            $package['branches'][implode('.', $matches) . '.x'] = [
                'time' => $dateTime->getTimestamp(),
                'versions' => $versions,
            ];

            $package['reference'] = \Safe\sprintf('composer://%s', $packageName);

            $packages[$name] = $package;
            $preparedAdvisories[$packageName] = $packages;
        }

        return $preparedAdvisories;
    }

    /**
     * Generates a github request object.
     *
     * @param string $cursor
     *
     * @throws \Viserio\Contract\Parser\Exception\DumpException
     * @throws \Safe\Exceptions\StringsException
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    private function createRequest(string $cursor): RequestInterface
    {
        $after = $cursor === '' ? '' : \Safe\sprintf(', after: "%s"', $cursor);

        return new Request(
            'https://api.github.com/graphql',
            'POST',
            [
                'Authorization' => \Safe\sprintf('bearer %s', $this->token),
                'Content-Type' => 'application/json',
                'User-Agent' => 'Curl',
            ],
            $this->jsonDumper->dump(['query' => \Safe\sprintf(self::GRAPHQL_QUERY, $after)])
        );
    }
}
