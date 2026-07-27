<?php

declare(strict_types=1);

namespace Tests\Classes;

use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\ApiResponse::class)) {
            require_once __DIR__ . '/../../classes/ApiResponse.php';
        }
    }

    public function testSuccessReturnsCorrectJson(): void
    {
        ob_start();
        \ApiResponse::success(['id' => 1], 'Created', 201);
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Created', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals(201, http_response_code());
    }

    public function testSuccessWithNullDataOmitsDataKey(): void
    {
        ob_start();
        \ApiResponse::success(null, 'OK', 200);
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertTrue($data['success']);
        $this->assertArrayNotHasKey('data', $data);
    }

    public function testErrorReturnsCorrectJson(): void
    {
        ob_start();
        \ApiResponse::error('Not found', 404);
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Not found', $data['error']);
        $this->assertEquals(404, http_response_code());
    }

    public function testPaginatedReturnsCorrectStructure(): void
    {
        $rows = [['id' => 1], ['id' => 2]];
        ob_start();
        \ApiResponse::paginated($rows, 50, 2, 25, 2);
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertTrue($data['success']);
        $this->assertEquals($rows, $data['data']);
        $this->assertEquals(50, $data['total']);
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(25, $data['perPage']);
        $this->assertEquals(2, $data['pages']);
    }

    public function testCreatedCallsSuccessWith201(): void
    {
        ob_start();
        \ApiResponse::created(['new_id' => 5]);
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Registro creado exitosamente', $data['message']);
        $this->assertEquals(201, http_response_code());
    }

    public function testNotFoundReturns404(): void
    {
        ob_start();
        \ApiResponse::notFound('Elemento no existe');
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Elemento no existe', $data['error']);
        $this->assertEquals(404, http_response_code());
    }

    public function testBadRequestReturns400(): void
    {
        ob_start();
        \ApiResponse::badRequest('Bad data');
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Bad data', $data['error']);
        $this->assertEquals(400, http_response_code());
    }

    public function testServerErrorReturns500(): void
    {
        ob_start();
        \ApiResponse::serverError('DB down');
        $output = ob_get_clean();
        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('DB down', $data['error']);
        $this->assertEquals(500, http_response_code());
    }

    public function testValidateRequiredWithAllFieldsPresentReturnsTrue(): void
    {
        ob_start();
        $result = \ApiResponse::validateRequired(
            ['name', 'email'],
            ['name' => 'John', 'email' => 'john@example.com']
        );
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testValidateRequiredWithMissingFieldsReturnsFalse(): void
    {
        ob_start();
        $result = \ApiResponse::validateRequired(
            ['name', 'email', 'phone'],
            ['name' => 'John']
        );
        ob_end_clean();

        $this->assertFalse($result);
    }

    public function testValidateRequiredWithEmptyStringFieldReturnsFalse(): void
    {
        ob_start();
        $result = \ApiResponse::validateRequired(
            ['name'],
            ['name' => '']
        );
        ob_end_clean();

        $this->assertFalse($result);
    }

    public function testValidateRequiredWithNullFieldReturnsFalse(): void
    {
        ob_start();
        $result = \ApiResponse::validateRequired(
            ['name'],
            ['name' => null]
        );
        ob_end_clean();

        $this->assertFalse($result);
    }

    public function testNoContentReturns204(): void
    {
        ob_start();
        \ApiResponse::noContent();
        $output = ob_get_clean();

        $this->assertEquals(204, http_response_code());
        $this->assertEmpty($output);
    }
}
