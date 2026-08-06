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
testCase('Login', '/', 'ajax_empresas2=true&ajax_username=22600781');
testCase('Login (no data)', '/', '');
testCase('Login (partial)', '/', 'ajax_empresas2=true');

// 2. Router check
echo "\n--- 2. Server ---\n";
testCase('Home GET', '/', null, 200, false);

// Now the API
echo "\n--- 3. API Auth ---\n";
testCase('API auth/login', '/api/v1/auth/auth.php/login', json_encode([
    'usu_username' => '22600781',
    'usu_password' => 'test123*'
]), 200, true);

echo "\n--- 4. Admin ---\n";
testCase('Admin check session', '/administrador/FRONT/adm_con_control_1.2.php', 'ajax_check=true');

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