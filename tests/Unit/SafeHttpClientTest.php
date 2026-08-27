<?php

declare(strict_types=1);

use App\Services\WhatsApp\Support\SafeHttpClient;
use Illuminate\Http\Client\Factory as HttpFactory;

beforeEach(fn () => $this->client = new SafeHttpClient(new HttpFactory));

it('rejects non-https schemes', function () {
    $this->client->download('http://example.com/x.jpg', ['image/']);
})->throws(RuntimeException::class, 'Only https URLs are allowed.');

it('rejects loopback hosts', function () {
    $this->client->download('https://127.0.0.1/x.jpg', ['image/']);
})->throws(RuntimeException::class, 'non-public');

it('rejects the cloud metadata address', function () {
    $this->client->download('https://169.254.169.254/latest/meta-data/', ['application/']);
})->throws(RuntimeException::class, 'non-public');

it('rejects private RFC1918 ranges', function () {
    $this->client->download('https://10.1.2.3/x.jpg', ['image/']);
})->throws(RuntimeException::class, 'non-public');
