<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/v1/auth/empresas';
$input = json_encode(['username' => 'admin']);
file_put_contents('php://memory', $input); // Mocking input is tricky in CLI for Slim.
