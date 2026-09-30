<?php

test('the application returns a successful response from health endpoint', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
