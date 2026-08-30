<?php
/**
 * AJAX Integration Test Suite
 * Tests that AJAX endpoints return valid JSON responses without errors
 * 
 * Usage: php tests\test_ajax_integration.php [--base-url=http://127.0.0.1:8000]
 */

$baseUrl = 'http://127.0.0.1:8000';
foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = substr($arg, strlen('--base-url='));
    }
}

$passed = 0;
$failed = 0;
$results = [];

function testCase($name, $url, $postData = null, $expectedHttp = 200, $expectJson = true) {
    global $baseUrl, $passed, $failed, $results;
    
    $fullUrl = $baseUrl . $url;
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $error = curl_error($ch);
    curl_close($ch);
    
    $status = 'PASS';
    $issues = [];
    
    if ($httpCode !== $expectedHttp) {
        $status = 'FAIL';
        $issues[] = "HTTP $httpCode (expected $expectedHttp)";
    }
    
    $hasContentType = preg_match('/Content-Type:\s*(.+)/i', $headers, $m);
    if ($expectJson && !$hasContentType) {
        $issues[] = 'Missing Content-Type header';
    }
    
    $json = null;
    if ($expectJson) {
        $json = json_decode($body, true);
        if ($json === null && strlen(trim($body)) > 0) {
            $status = 'FAIL';
            $issues[] = 'Invalid JSON: ' . substr($body, 0, 200);
        }
    }
    
    if ($hasContentType) {
        $ct = trim($m[1]);
        if ($expectJson && strpos($ct, 'application/json') === false) {
            $status = 'WARN';
            $issues[] = "Content-Type: $ct (not application/json)";
        }
    }
    
    if ($error) {
        $status = 'FAIL';
        $issues[] = "cURL error: $error";
    }
    
    if ($status === 'PASS') $passed++;
    else $failed++;
    
    $results[] = [
        'name' => $name,
        'status' => $status,
        'http' => $httpCode,
        'issues' => $issues,
        'body_len' => strlen($body),
    ];
    
    return $json;
}

echo "=== AJAX Integration Test Suite ===\n";
echo "Base URL: $baseUrl\n\n";

// 1. Login
echo "--- 1. Login ---\n";
testCase('Login (con usuario)', '/', 'ajax_empresas2=true&ajax_username=22600781');
testCase('Login (sin AJAX = pagina HTML)', '/', '', 200, false);
testCase('Login (AJAX parcial)', '/', 'ajax_empresas2=true');

// 2. Router check
echo "\n--- 2. Server ---\n";
testCase('Home GET', '/', null, 200, false);

// 3. API Auth (endpoints reales de api/v1/auth/auth.php)
echo "\n--- 3. API Auth ---\n";
$empresas = testCase('API auth/empresas', '/api/v1/auth/empresas', json_encode([
    'username' => '22600781',
]), 200, true);
$empresaName = '';
if (is_array($empresas) && !empty($empresas['empresas']) && isset($empresas['success']) && $empresas['success']) {
    $empresaName = $empresas['empresas'][0]['Emp_Nom'] ?? '';
}
if ($empresaName !== '') {
    testCase('API auth/login (credenciales de prueba)', '/api/v1/auth/login', json_encode([
        'username' => '22600781',
        'password' => 'test123*',
        'empresa' => $empresaName,
    ]), 200, true);
} else {
    testCase('API auth/login (parametros incompletos)', '/api/v1/auth/login', json_encode([
        'username' => '22600781',
    ]), 400, true);
}

echo "\n--- 4. Admin ---\n";
testCase('Admin check session (sin sesion redirige a login)', '/administrador/FRONT/adm_con_control_1.2.php', 'ajax_check=true', 302, false);

echo "\n\n=== Results ===\n";
echo "Passed: $passed\nFailed: $failed\n\n";

foreach ($results as $r) {
    $icon = $r['status'] === 'PASS' ? 'OK' : ($r['status'] === 'WARN' ? '~~' : 'FAIL');
    echo "[$icon] {$r['name']} (HTTP {$r['http']})";
    if (!empty($r['issues'])) {
        echo ' - ' . implode('; ', $r['issues']);
    }
    echo "\n";
}

exit($failed > 0 ? 1 : 0);