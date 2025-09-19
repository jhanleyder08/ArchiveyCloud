<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    // La ruta raíz redirige a login, así que esperamos un 302
    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});
