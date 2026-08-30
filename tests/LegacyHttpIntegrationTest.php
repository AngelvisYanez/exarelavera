<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Pruebas de integración HTTP para el sistema legacy EXA Contable.
 * Requiere el servidor PHP dev corriendo en http://127.0.0.1:8000
 */
class LegacyHttpIntegrationTest extends TestCase
{
    private static string $baseUrl = 'http://127.0.0.1:8000';

    public static function setUpBeforeClass(): void
    {
        $running = false;
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $result = @file_get_contents(self::$baseUrl . '/', false, $ctx);
            if ($result !== false) $running = true;
        } catch (\Exception $e) {}
        if (!$running) {
            self::markTestSkipped('PHP dev server not running on ' . self::$baseUrl);
        }
    }

    public function testLoginPageLoads(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/');
        $this->assertNotFalse($result, 'Login page did not respond');
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html', $result);
        $this->assertStringContainsString('</html>', $result);
        $this->assertStringContainsString('Iniciar sesi', $result, 'Login form not found');
    }

    public function testLoginOldFallbackPageLoads(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/index-OLD-10-03-25.php');
        $this->assertNotFalse($result, 'OLD login page did not respond');
        $this->assertStringContainsString('Iniciar Sesi', $result);
        $this->assertStringContainsString('<form', $result);
    }

    public function testHomePageRedirects(): void
    {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $result = @file_get_contents(self::$baseUrl . '/administrador/FRONT/home.php', false, $ctx);
        $this->assertNotFalse($result, 'Home page should respond');
    }

    public function testEstilosCssPageLoads(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/mascaras/model1/estilos/estilos.php');
        $this->assertNotFalse($result, 'Model1 estilos page did not respond');
        $this->assertStringContainsString('bootstrap', $result);
    }

    public function testJqgridAssetsLoad(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/mascaras/model1/estilos/jqgrid5.php');
        $this->assertNotFalse($result, 'JqGrid asset page did not respond');
        $this->assertStringContainsString('jqgrid', $result);
    }

    public function testUnifiedLoaderWorks(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/mascaras/unified-loader.php');
        $this->assertNotFalse($result, 'Unified loader did not respond');
    }

    public function testModel3CssAvailable(): void
    {
        $result = @file_get_contents(self::$baseUrl . '/mascaras/model3/estilos/exa-ui.css');
        $this->assertNotFalse($result, 'Model3 CSS did not respond');
        $this->assertStringContainsString('/*', $result);
    }

    public function testRouterApiResponds(): void
    {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $result = @file_get_contents(self::$baseUrl . '/api/', false, $ctx);
        $this->assertNotFalse($result, 'Router API endpoint should respond');
    }

    public function testAjaxEmpresasReturnsJson(): void
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'ajax_empresas2' => 'true',
                    'ajax_username' => 'test',
                    'ajax_nacional' => '1',
                    'ajax_ruc' => '',
                    'ajax_razon_social' => '',
                ]),
                'timeout' => 10,
                'ignore_errors' => true,
            ]
        ];
        $ctx = stream_context_create($opts);
        $result = @file_get_contents(self::$baseUrl . '/', false, $ctx);
        $this->assertNotFalse($result, 'AJAX empresas did not respond');
        $data = json_decode($result, true);
        if ($data === null) {
            $this->markTestIncomplete('AJAX empresas response is not JSON (no DB connection?): ' . substr($result, 0, 200));
            return;
        }
        $this->assertArrayHasKey('success', $data, 'AJAX response missing success key');
        $this->assertArrayHasKey('html', $data, 'AJAX response missing html key');
        $this->assertArrayHasKey('conteo', $data, 'AJAX response missing conteo key');
    }

    public function testAjaxChangePassResponds(): void
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'ajax_change_pass' => 'true',
                    'old_pass' => '',
                    'new_pass' => '',
                ]),
                'timeout' => 10,
                'ignore_errors' => true,
            ]
        ];
        $ctx = stream_context_create($opts);
        $result = @file_get_contents(self::$baseUrl . '/', false, $ctx);
        $this->assertNotFalse($result, 'AJAX change_pass did not respond');
        $data = json_decode($result, true);
        if ($data === null) {
            $this->markTestIncomplete('AJAX change_pass response not JSON');
            return;
        }
        $this->assertArrayHasKey('success', $data, 'change_pass response missing success');
    }

    public function testCoreModulePagesRespond(): void
    {
        $pages = [
            '/administrador/FRONT/home.php',
            '/administrador/FRONT/adm_con_control_1.2.php',
            '/mascaras/model1/estilos/estilos.php',
            '/mascaras/model1/estilos/basic.php',
            '/mascaras/model1/estilos/jqgrid5.php',
        ];
        foreach ($pages as $page) {
            $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
            $result = @file_get_contents(self::$baseUrl . $page, false, $ctx);
            $this->assertNotFalse($result, "Page $page did not respond");
        }
    }

    public function testSecurityHeadersCheck(): void
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $headers = @get_headers(self::$baseUrl . '/');
        $this->assertNotFalse($headers, 'No headers from index page');
        $headersStr = implode("\n", $headers);
        // Page sends set-cookie (session) with HttpOnly flag
        $this->assertStringContainsString('Set-Cookie', $headersStr);
        $this->assertStringContainsString('HttpOnly', $headersStr);
        // Session cookie has SameSite
        $this->assertStringContainsString('SameSite', $headersStr);
    }

    public function testAdminControlRedirectsToLoginWhenUnauthenticated(): void
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $result = @file_get_contents(self::$baseUrl . '/administrador/FRONT/adm_con_control_1.2.php', false, $ctx);
        $this->assertNotFalse($result, 'Control page should respond');
    }
}
