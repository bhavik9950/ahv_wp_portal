<?php

declare(strict_types=1);

it('redirects the root path to the dashboard', function () {
    $this->get('/')->assertRedirect(route('dashboard', absolute: false));
});
