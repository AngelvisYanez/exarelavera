<?php

require_once __DIR__ . '/vendor/autoload.php';

$results = [];

// 1. PHP DebugBar
try {
    if (class_exists('\\DebugBar\\StandardDebugBar')) {
        $debugbar = new \DebugBar\StandardDebugBar();
        $results['php-debugbar'] = 'OK';
    } else {
        $results['php-debugbar'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['php-debugbar'] = 'Error: ' . $e->getMessage();
}

// 2. Monolog
try {
    if (class_exists('\\Monolog\\Logger')) {
        $log = new \Monolog\Logger('test');
        $results['monolog'] = 'OK';
    } else {
        $results['monolog'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['monolog'] = 'Error: ' . $e->getMessage();
}

// 3. Whoops
try {
    if (class_exists('\\Whoops\\Run')) {
        $whoops = new \Whoops\Run();
        $results['whoops'] = 'OK';
    } else {
        $results['whoops'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['whoops'] = 'Error: ' . $e->getMessage();
}

// 4. SpreadsheetReader
try {
    // Checking if class exists (instantiation requires a valid file)
    if (class_exists('SpreadsheetReader')) {
        $results['spreadsheet-reader'] = 'OK';
    } else {
        $results['spreadsheet-reader'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['spreadsheet-reader'] = 'Error: ' . $e->getMessage();
}

// 5. PHPMailer
try {
    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $results['phpmailer'] = 'OK';
    } else {
        $results['phpmailer'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['phpmailer'] = 'Error: ' . $e->getMessage();
}

// 6. NuSOAP
try {
    if (class_exists('nusoap_client')) {
        $results['nusoap'] = 'OK';
    } else {
        $results['nusoap'] = 'Error: Class nusoap_client not found';
    }
} catch (\Throwable $e) {
    $results['nusoap'] = 'Error: ' . $e->getMessage();
}

// 7. TCPDF
try {
    if (class_exists('TCPDF')) {
        $tcpdf = new \TCPDF();
        $results['tcpdf'] = 'OK';
    } else {
        $results['tcpdf'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['tcpdf'] = 'Error: ' . $e->getMessage();
}

// 8. mPDF
try {
    if (class_exists('\\Mpdf\\Mpdf')) {
        $mpdf = new \Mpdf\Mpdf();
        $results['mpdf'] = 'OK';
    } else {
        $results['mpdf'] = 'Error: Class not found';
    }
} catch (\Throwable $e) {
    $results['mpdf'] = 'Error: ' . $e->getMessage();
}

// Output results
echo "Dependency Test Results:\n";
echo "========================\n";
foreach ($results as $package => $status) {
    echo str_pad($package, 25) . ": " . $status . "\n";
}

$hasErrors = in_array(false, array_map(function($v) { return $v === 'OK'; }, $results));

if ($hasErrors) {
    echo "\nSome dependencies failed to load.\n";
    exit(1);
}

echo "\nAll dependencies loaded successfully!\n";
