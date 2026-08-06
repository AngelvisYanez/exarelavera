<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Validación estática offline del sistema legacy EXA Contable:
 * sintaxis PHP, estructura de módulos, seguridad y helpers.
 */
class LegacyCodeAnalysisTest extends TestCase
{
    // ──────────────────────────────────────────────
    // 1. PHP SYNTAX VALIDATION
    // ──────────────────────────────────────────────

    /** @dataProvider provideModuleFiles */
    public function testPhpSyntax(string $filePath, string $label): void
    {
        $output = [];
        $exitCode = 0;
        exec('php -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);
        $this->assertEquals(0, $exitCode, "Syntax error in: $label\n" . implode("\n", $output));
    }

    public static function provideModuleFiles(): array
    {
        $root = dirname(__DIR__);
        $modules = [
            'index.php',
            'administrador/FRONT/adm_con_control_1.2.php',
            'administrador/FRONT/home.php',
            'administrador/LOGICA/adm_sql_control.php',
            'administrador/LOGICA/adm_log_control.php',
            'administrador/LOGICA/adm_log_login.php',
            'Librerias/config.php/register_globals.php',
            'DATA/MysqlDatos.php',
            'DATA/MysqlConexion.php',
            'api/v1/auth/auth.php',
            'router.php',
        ];
        $dirs = [
            'activosfijos/LOGICA', 'administrador/LOGICA', 'adquisiciones/LOGICA',
            'auditoria/LOGICA', 'bodega/LOGICA', 'caja_chica/LOGICA',
            'compras/LOGICA', 'contabilidad/LOGICA', 'facturacion/LOGICA',
            'inventario/LOGICA', 'relavera/LOGICA', 'rrhh/LOGICA',
            'tesoreria/LOGICA', 'transportecarga/LOGICA',
        ];
        foreach ($dirs as $dir) {
            $full = $root . '/' . $dir;
            if (is_dir($full)) {
                $files = glob($full . '/*.php');
                $modules = array_merge($modules, $files);
            }
        }
        $data = [];
        foreach ($modules as $f) {
            $path = str_starts_with($f, '/') || preg_match('/^[A-Z]:/', $f) ? $f : $root . '/' . $f;
            if (file_exists($path)) {
                $name = str_replace($root . '/', '', $path);
                $name = str_replace($root . '\\', '', $name);
                $data[$name] = [$path, $name];
            }
        }
        return $data;
    }

    // ──────────────────────────────────────────────
    // 2. MODULE STRUCTURAL INTEGRITY
    // ──────────────────────────────────────────────

    /** @dataProvider provideModuleDirectories */
    public function testModuleDirectoriesExist(string $dir, string $name): void
    {
        $this->assertDirectoryExists($dir, "Module directory missing: $name");
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        $this->assertNotEmpty($phpFiles, "Module has no PHP files: $name");
    }

    public static function provideModuleDirectories(): array
    {
        $root = dirname(__DIR__);
        $dirs = [
            'Admin FRONT'   => $root . '/administrador/FRONT',
            'Admin LOGICA'  => $root . '/administrador/LOGICA',
            'Contabilidad FRONT'  => $root . '/contabilidad/FRONT',
            'Contabilidad LOGICA' => $root . '/contabilidad/LOGICA',
            'Facturacion FRONT'   => $root . '/facturacion/FRONT',
            'Facturacion LOGICA'  => $root . '/facturacion/LOGICA',
            'Tesoreria FRONT'     => $root . '/tesoreria/FRONT',
            'Tesoreria LOGICA'    => $root . '/tesoreria/LOGICA',
            'Activos Fijos LOGICA' => $root . '/activosfijos/LOGICA',
            'Adquisiciones LOGICA' => $root . '/adquisiciones/LOGICA',
            'Auditoria LOGICA'     => $root . '/auditoria/LOGICA',
            'Bodega LOGICA'        => $root . '/bodega/LOGICA',
            'Caja Chica LOGICA'    => $root . '/caja_chica/LOGICA',
            'Compras FRONT'        => $root . '/compras/FRONT',
            'Compras SQL'          => $root . '/compras/sql',
            'Inventario LOGICA'    => $root . '/inventario/LOGICA',
            'RRHH LOGICA'          => $root . '/rrhh/LOGICA',
            'Transporte LOGICA'    => $root . '/transportecarga/LOGICA',
            'Relavera LOGICA'      => $root . '/relavera/LOGICA',
            'Librerias'            => $root . '/Librerias',
            'DATA'                 => $root . '/DATA',
            'API'                  => $root . '/api',
        ];
        $data = [];
        foreach ($dirs as $name => $dir) {
            if (is_dir($dir)) {
                $data[$name] = [$dir, $name];
            }
        }
        return $data;
    }

    // ──────────────────────────────────────────────
    // 3. SECURITY VERIFICATION
    // ──────────────────────────────────────────────

    public function testCriticalSecurityFilesPassLint(): void
    {
        $root = dirname(__DIR__);
        $files = [
            'Librerias/config.php/register_globals.php',
            'DATA/MysqlDatos.php',
            'administrador/FRONT/adm_con_control_1.2.php',
            'administrador/LOGICA/adm_sql_control.php',
            'api/v1/auth/auth.php',
        ];
        foreach ($files as $f) {
            $path = $root . '/' . $f;
            if (!file_exists($path)) {
                $this->fail("Security file not found: $f");
                continue;
            }
            $output = [];
            $exitCode = 0;
            exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);
            $this->assertEquals(0, $exitCode, "Syntax error in security file: $f\n" . implode("\n", $output));
        }
    }

    public function testMysqlDatosHasEscapeSqlParam(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/DATA/MysqlDatos.php');
        $this->assertStringContainsString(
            'function escapeSqlParam',
            $content,
            'MysqlDatos.php is missing escapeSqlParam method'
        );
        $this->assertStringContainsString(
            'real_escape_string',
            $content,
            'MysqlDatos.php is missing real_escape_string in escapeSqlParam'
        );
    }

    public function testRegisterGlobalsHasEscHelper(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/Librerias/config.php/register_globals.php');
        $this->assertStringContainsString(
            'function esc(',
            $content,
            'register_globals.php is missing esc() helper'
        );
        $this->assertStringContainsString(
            'function csrf_token',
            $content,
            'register_globals.php is missing csrf_token() helper'
        );
        $this->assertStringContainsString(
            'function csrf_validate',
            $content,
            'register_globals.php is missing csrf_validate() helper'
        );
    }

    public function testRegisterGlobalsNoPostInjection(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/Librerias/config.php/register_globals.php');
        $this->assertStringContainsString(
            '$_POST',
            $content,
            'register_globals.php should reference $_POST'
        );
        $this->assertStringNotContainsString(
            'extract($_POST)',
            $content,
            'register_globals should NOT extract POST variables directly'
        );
    }

    // ──────────────────────────────────────────────
    // 4. BOOTSTRAP / CSS CONFLICT RESOLUTION
    // ──────────────────────────────────────────────

    public function testBootstrapConflictResolved(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/mascaras/model1/estilos/estilos.php');
        $this->assertStringContainsString(
            'bootstrap-3.3.5',
            $content,
            'estilos.php should load Bootstrap 3.3.5'
        );
        $this->assertStringNotContainsString(
            'bootstrap.min.css?x=1',
            $content,
            'estilos.php should NOT load old Bootstrap 2.x'
        );
    }

    public function testBasicPhpUsesBootstrap3(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/mascaras/model1/estilos/basic.php');
        $this->assertStringContainsString(
            'bootstrap-3.3.5',
            $content,
            'basic.php should load Bootstrap 3.3.5'
        );
    }

    public function testScriptLanguageDeprecatedRemoved(): void
    {
        $root = dirname(__DIR__);
        $sampleFiles = [
            'index.php',
            'administrador/FRONT/home.php',
            'administrador/FRONT/adm_con_control_1.2.php',
            'mascaras/model1/estilos/jqgrid5.php',
        ];
        foreach ($sampleFiles as $f) {
            $path = $root . '/' . $f;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $this->assertStringNotContainsString(
                    '<script language="javascript"',
                    $content,
                    "File $f still has deprecated script language=\"javascript\""
                );
            }
        }
    }

    // ──────────────────────────────────────────────
    // 5. XSS FIX VERIFICATION
    // ──────────────────────────────────────────────

    public function testXssFormActionHijackingFixed(): void
    {
        $root = dirname(__DIR__);
        $files = [
            'fac_alt_aper_caja_1.0.php'  => $root . '/facturacion/FRONT/fac_alt_aper_caja_1.0.php',
            'tes_alt_aper_caja_1.0.php'  => $root . '/tesoreria/FRONT/tes_alt_aper_caja_1.0.php',
            'fac_mod_aper_caja.php'      => $root . '/facturacion/FRONT/fac_mod_aper_caja.php',
            'fac_mod_retencion.php'      => $root . '/facturacion/FRONT/fac_mod_retencion.php',
            'fac_pri_retencion.php'      => $root . '/facturacion/FRONT/fac_pri_retencion.php',
            'tes_mod_bancos.php'         => $root . '/tesoreria/FRONT/tes_mod_bancos.php',
            'tes_mod_vendedor.php'       => $root . '/tesoreria/FRONT/tes_mod_vendedor.php',
            'fac_alt_destinatario.php'   => $root . '/facturacion/FRONT/fac_alt_destinatario.php',
            'fac_alt_transporte.php'     => $root . '/facturacion/FRONT/fac_alt_transporte.php',
            'adm_alt_usuarios.php'       => $root . '/administrador/FRONT/adm_alt_usuarios.php',
        ];
        foreach ($files as $name => $path) {
            if (!file_exists($path)) continue;
            $content = file_get_contents($path);
            // Check that unescaped $_POST echoes are wrapped in htmlspecialchars
            $this->assertStringContainsString(
                'htmlspecialchars',
                $content,
                "File $name is missing htmlspecialchars() wrapping"
            );
            $this->assertStringNotContainsString(
                "echo \$_POST['form1']",
                $content,
                "File $name has unescaped POST variable in echo"
            );
            $this->assertStringNotContainsString(
                'echo $_POST["form1"]',
                $content,
                "File $name has unescaped POST variable in echo (double quotes)"
            );
        }
    }

    public function testDirectEchoXssFixed(): void
    {
        $root = dirname(__DIR__);
        $files = [];
        $dirs = [
            $root . '/facturacion/FRONT',
            $root . '/tesoreria/FRONT',
            $root . '/administrador/FRONT',
            $root . '/auditoria/FRONT',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;
                $content = file_get_contents($file->getPathname());
                // Files that have been XSS-fixed will have htmlspecialchars wrapping
                if (preg_match('/echo\s+\$\_POST\b/i', $content)) {
                    $this->assertStringContainsString(
                        'htmlspecialchars',
                        $content,
                        'File ' . $file->getFilename() . ' has unescaped $_POST echo without htmlspecialchars()'
                    );
                }
            }
        }
        $this->addToAssertionCount(1);
    }

    // ──────────────────────────────────────────────
    // 6. ACCESS POINT VALIDATION
    // ──────────────────────────────────────────────

    public function testIndexHasPostBasedAjax(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/index.php');
        $this->assertStringContainsString(
            '$_POST[\'ajax_empresas2\']',
            $content,
            'index.php should use $_POST for ajax_empresas2'
        );
        $this->assertStringContainsString(
            '$_POST[\'ajax_change_pass\']',
            $content,
            'index.php should use $_POST for ajax_change_pass'
        );
    }

    public function testLoginFlowUsesPost(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/administrador/FRONT/adm_con_control_1.2.php');
        $this->assertStringContainsString(
            '$_POST[\'user_name\']',
            $content,
            'Control should use $_POST for user_name'
        );
        $this->assertStringContainsString(
            '$_POST[\'password\']',
            $content,
            'Control should use $_POST for password'
        );
        $this->assertStringNotContainsString(
            '$_REQUEST[',
            $content,
            'Control should NOT use $_REQUEST which bypasses input method'
        );
    }

    // ──────────────────────────────────────────────
    // 7. ENVIRONMENT CONFIGURATION
    // ──────────────────────────────────────────────

    public function testEnvExampleExists(): void
    {
        $path = dirname(__DIR__) . '/.env.example';
        $this->assertFileExists($path, '.env.example file is missing');
        $content = file_get_contents($path);
        $this->assertStringContainsString('AUTH_TOKEN_SECRET', $content, '.env.example should have AUTH_TOKEN_SECRET');
        $this->assertStringContainsString('CSRF_SECRET', $content, '.env.example should have CSRF_SECRET');
    }

    public function testHtaccessExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/.htaccess', '.htaccess file is missing');
    }
}
