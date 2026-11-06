<?php

test('terms of use page can be rendered', function () {
    $response = $this->get('/termos');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Legal/Terms')
        );
});

test('privacy policy page can be rendered', function () {
    $response = $this->get('/privacidade');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Legal/Privacy')
        );
});
