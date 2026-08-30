<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class OpenApiSpecTest extends TestCase
{
    private function loadSpec(): array
    {
        $path = __DIR__ . '/../../api/openapi.json';
        $this->assertFileExists($path, 'El archivo api/openapi.json no existe');

        $raw = file_get_contents($path);
        $this->assertNotFalse($raw);

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded, 'api/openapi.json no es JSON válido: ' . json_last_error_msg());

        return $decoded;
    }

    public function testOpenApiIsValidJson(): void
    {
        $this->loadSpec();
        $this->addToAssertionCount(1);
    }

    public function testOpenApiVersionAndStructure(): void
    {
        $spec = $this->loadSpec();

        $this->assertEquals('3.0.3', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertNotEmpty($spec['info']['title']);
        $this->assertNotEmpty($spec['info']['version']);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertNotEmpty($spec['paths']);
        $this->assertArrayHasKey('BearerAuth', $spec['components']['securitySchemes'] ?? []);
    }

    public function testInfoDescriptionHasNoMojibakeCharacters(): void
    {
        $spec = $this->loadSpec();
        $description = $spec['info']['description'] ?? '';

        $this->assertNotEmpty($description);

        // El texto debe estar en UTF-8 válido y no contener secuencias mojibake
        // ni caracteres de reemplazo U+FFFD.
        $this->assertStringNotContainsString(
            "\u{FFFD}",
            $description,
            'La descripción contiene el carácter de reemplazo U+FFFD'
        );

        $mojibake = ['Ã', 'Â', 'Å', 'Ã', 'ã'];
        foreach ($mojibake as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $description,
                "La descripción contiene mojibake detectado: '$pattern'"
            );
        }

        // Verificar que los acentos esperados están presentes y bien codificados
        $this->assertStringContainsString('Autenticación', $description);
        $this->assertStringContainsString('facturación', $description);
        $this->assertStringContainsString('Códigos', $description);
    }

    public function testEveryPathHasAtLeastOneOperationWithSummary(): void
    {
        $spec = $this->loadSpec();

        foreach ($spec['paths'] as $path => $item) {
            $operations = array_filter(
                array_keys($item),
                static fn (string $k): bool => in_array($k, ['get', 'post', 'put', 'delete', 'patch'], true)
            );
            $this->assertNotEmpty(
                $operations,
                "El path '$path' no define ninguna operación HTTP"
            );

            foreach ($operations as $op) {
                if ($op === 'get') {
                    continue; // los GET de docs no requieren summary
                }
                $this->assertArrayHasKey(
                    'summary',
                    $item[$op],
                    "La operación '$op' del path '$path' no tiene summary"
                );
            }
        }
    }

    public function testSecuritySchemeIsApiKeyInHeader(): void
    {
        $spec = $this->loadSpec();
        $scheme = $spec['components']['securitySchemes']['BearerAuth'];

        $this->assertEquals('apiKey', $scheme['type']);
        $this->assertEquals('header', $scheme['in']);
        $this->assertEquals('Authorization', $scheme['name']);
    }
}
