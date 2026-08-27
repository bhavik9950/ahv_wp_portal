<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Support;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * SSRF-hardened HTTP GET for fetching user/Meta-supplied media URLs.
 *
 *  - https only
 *  - host must resolve exclusively to public unicast IPs
 *  - every redirect hop is re-validated (max 2 hops)
 *  - connect/read timeouts and a hard download size cap
 *
 * Never use plain Http::get() for a URL whose host the user can influence.
 */
final class SafeHttpClient
{
    /** @var list<array{0:string,1:int}> CIDR blocks to reject (IPv4). */
    private const BLOCKED_V4 = [
        ['0.0.0.0', 8],
        ['10.0.0.0', 8],
        ['100.64.0.0', 10],
        ['127.0.0.0', 8],
        ['169.254.0.0', 16],   // link-local incl. 169.254.169.254 metadata
        ['172.16.0.0', 12],
        ['192.0.0.0', 24],
        ['192.168.0.0', 16],
        ['198.18.0.0', 15],
        ['224.0.0.0', 4],
        ['240.0.0.0', 4],
    ];

    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @param  list<string>  $allowedMimePrefixes  e.g. ['image/', 'video/', 'application/pdf']
     */
    public function download(string $url, array $allowedMimePrefixes, ?int $maxBytes = null): DownloadedFile
    {
        $maxBytes ??= (int) config('services.whatsapp.http.max_download_bytes');
        $connectTimeout = (int) config('services.whatsapp.http.connect_timeout', 10);
        $timeout = (int) config('services.whatsapp.http.timeout', 30);
        $maxHops = (int) config('services.whatsapp.http.max_redirects', 2);

        $current = $url;

        for ($hop = 0; $hop <= $maxHops; $hop++) {
            $this->assertUrlIsSafe($current);

            $response = $this->http
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withoutRedirecting()
                ->withOptions(['stream' => false])
                ->get($current);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if ($location === '') {
                    throw new RuntimeException('Redirect without Location header.');
                }
                $current = $this->resolveLocation($current, $location);

                continue;
            }

            return $this->finish($response, $allowedMimePrefixes, $maxBytes);
        }

        throw new RuntimeException('Too many redirects.');
    }

    private function finish(Response $response, array $allowedMimePrefixes, int $maxBytes): DownloadedFile
    {
        if (! $response->successful()) {
            throw new RuntimeException('Media fetch failed with HTTP '.$response->status());
        }

        $body = $response->body();

        if (strlen($body) > $maxBytes) {
            throw new RuntimeException('Media exceeds maximum allowed size.');
        }

        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $ok = false;
        foreach ($allowedMimePrefixes as $prefix) {
            if (str_starts_with($mime, strtolower($prefix))) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            throw new RuntimeException("Disallowed media content type: {$mime}");
        }

        return new DownloadedFile($body, $mime, strlen($body));
    }

    private function assertUrlIsSafe(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? null) !== 'https') {
            throw new RuntimeException('Only https URLs are allowed.');
        }

        $host = $parts['host'] ?? '';
        if ($host === '') {
            throw new RuntimeException('URL has no host.');
        }

        $ips = $this->resolve($host);
        if ($ips === []) {
            throw new RuntimeException("Host does not resolve: {$host}");
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new RuntimeException("Host resolves to a non-public address ({$ip}).");
            }
        }
    }

    /** @return list<string> */
    private function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $v4 = gethostbynamel($host) ?: [];
        $v6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return array_values(array_unique([...$v4, ...$v6]));
    }

    private function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Reject loopback / link-local / ULA; allow only global unicast.
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $long = ip2long($ip);
        foreach (self::BLOCKED_V4 as [$subnet, $bits]) {
            $mask = -1 << (32 - $bits);
            if ((ip2long($subnet) & $mask) === ($long & $mask)) {
                return false;
            }
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function resolveLocation(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $b = parse_url($base);
        $scheme = $b['scheme'] ?? 'https';
        $host = $b['host'] ?? '';
        $port = isset($b['port']) ? ':'.$b['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = isset($b['path']) ? rtrim(dirname($b['path']), '/') : '';

        return "{$scheme}://{$host}{$port}{$path}/{$location}";
    }
}
