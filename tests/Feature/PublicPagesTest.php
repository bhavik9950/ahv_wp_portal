<?php

declare(strict_types=1);

it('renders home page successfully', function () {
    $this->get('/')->assertOk()->assertSee('AHV WhatsApp');
});

it('renders services page successfully', function () {
    $this->get('/services')->assertOk()->assertSee('Enterprise Communication Services');
});

it('renders pricing page successfully', function () {
    $this->get('/pricing')->assertOk()->assertSee('649');
});

it('renders about page successfully', function () {
    $this->get('/about')->assertOk()->assertSee('About AHV Software');
});

it('renders contact page successfully', function () {
    $this->get('/contact')->assertOk()->assertSee('Contact Us');
});

it('renders faq page successfully', function () {
    $this->get('/faq')->assertOk()->assertSee('Frequently Asked Questions');
});
