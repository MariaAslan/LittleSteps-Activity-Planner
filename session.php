<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('LITTLESTEPS_SESSION');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/LittleSteps/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}