<?php
/**
 * SerialNumber Module Tests
 *
 * Unit tests for the SerialNumber module functionality including
 * service layer, repository layer, and integration tests.
 *
 * @package Tests
 * @author FrontAccounting Refactoring Team
 * @license GPL-3.0
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Modules\SerialNumber\SerialNumber;
use Services\SerialNumberService;
use Database\SerialNumberRepository;

/**
 * SerialNumber Module Test Class
 *
 * Tests the SerialNumber module components and integration.
 */
class SerialNumberModuleTest extends TestCase
{
    /**
     * Serial number module instance
     */
    private SerialNumber $module;

    /**
     * Serial number service instance
     */
    private SerialNumberService $service;

    /**
     * Serial number repository instance
     */
    private SerialNumberRepository $repository;

    /**
     * Test data
     */
    private array $testData;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        // Initialize test data
        $this->testData = [
            'stock_id' => 'TEST001',
            'serial_no' => 'SN123456789',
            'location' => 'DEF',
            'status' => 'active'
        ];

        // Create module instance
        $this->module = new SerialNumber();

        // Get service and repository from module
        $this->service = $this->module->getService();
        $this->repository = $this->module->getRepository();
    }

    /**
     * Test module initialization
     */
    public function testModuleInitialization(): void
    {
        $this->assertInstanceOf(SerialNumber::class, $this->module);
        $this->assertEquals('SerialNumber', $this->module->getName());
        $this->assertEquals('1.0.0', $this->module->getVersion());
        $this->assertTrue($this->module->isEnabled());
    }

    /**
     * Test module dependencies
     */
    public function testModuleDependencies(): void
    {
        $dependencies = $this->module->getDependencies();

        $this->assertIsArray($dependencies);
        $this->assertContains('Events', $dependencies);
        $this->assertContains('Plugins', $dependencies);
        $this->assertContains('Database', $dependencies);
        $this->assertContains('Services', $dependencies);
    }

    /**
     * Test service instance
     */
    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(SerialNumberService::class, $this->service);
    }

    /**
     * Test repository instance
     */
    public function testRepositoryInstance(): void
    {
        $this->assertInstanceOf(SerialNumberRepository::class, $this->repository);
    }

    /**
     * Test serial number generation
     */
    public function testSerialNumberGeneration(): void
    {
        $generated = $this->service->generateSerialNumber($this->testData['stock_id']);

        $this->assertIsString($generated);
        $this->assertNotEmpty($generated);
        $this->assertStringStartsWith('TES', $generated); // Should start with first 3 chars of stock_id
    }

    /**
     * Test serial item creation
     */
    public function testSerialItemCreation(): void
    {
        $itemId = $this->repository->createSerialItem($this->testData);

        $this->assertIsInt($itemId);
        $this->assertGreaterThan(0, $itemId);

        // Verify item was created
        $item = $this->repository->getSerialItemById($itemId);
        $this->assertIsArray($item);
        $this->assertEquals($this->testData['stock_id'], $item['stock_id']);
        $this->assertEquals($this->testData['serial_no'], $item['serial_no']);
        $this->assertEquals($this->testData['status'], $item['status']);
        $this->assertEquals($this->testData['location'], $item['location']);
    }

    /**
     * Test serial item retrieval
     */
    public function testSerialItemRetrieval(): void
    {
        // Create test item
        $itemId = $this->repository->createSerialItem($this->testData);

        // Test retrieval by stock_id and serial_no
        $item = $this->repository->getSerialItem(
            $this->testData['stock_id'],
            $this->testData['serial_no']
        );

        $this->assertIsArray($item);
        $this->assertEquals($itemId, $item['id']);
        $this->assertEquals($this->testData['stock_id'], $item['stock_id']);
        $this->assertEquals($this->testData['serial_no'], $item['serial_no']);
    }

    /**
     * Test serial item update
     */
    public function testSerialItemUpdate(): void
    {
        // Create test item
        $itemId = $this->repository->createSerialItem($this->testData);

        // Update status
        $result = $this->repository->updateSerialStatus($itemId, 'sold');

        $this->assertTrue($result);

        // Verify update
        $item = $this->repository->getSerialItemById($itemId);
        $this->assertEquals('sold', $item['status']);
    }

    /**
     * Test serial movement creation
     */
    public function testSerialMovementCreation(): void
    {
        // Create test item first
        $itemId = $this->repository->createSerialItem($this->testData);

        // Create movement
        $movementData = [
            'serial_item_id' => $itemId,
            'trans_type' => 10, // Sales invoice
            'trans_no' => 123,
            'stock_id' => $this->testData['stock_id'],
            'serial_no' => $this->testData['serial_no'],
            'location_from' => $this->testData['location'],
            'location_to' => 'SOLD',
            'qty' => 1,
            'reference' => 'Test sale'
        ];

        $movementId = $this->repository->createMovement($movementData);

        $this->assertIsInt($movementId);
        $this->assertGreaterThan(0, $movementId);
    }

    /**
     * Test serial attributes
     */
    public function testSerialAttributes(): void
    {
        // Create test item first
        $itemId = $this->repository->createSerialItem($this->testData);

        // Create attribute
        $attributeData = [
            'serial_item_id' => $itemId,
            'attribute_name' => 'warranty_expiry',
            'attribute_value' => '2025-12-31'
        ];

        $attributeId = $this->repository->createAttribute($attributeData);

        $this->assertIsInt($attributeId);
        $this->assertGreaterThan(0, $attributeId);

        // Get attributes
        $attributes = $this->repository->getAttributesBySerial($itemId);

        $this->assertIsArray($attributes);
        $this->assertCount(1, $attributes);
        $this->assertEquals('warranty_expiry', $attributes[0]['attribute_name']);
        $this->assertEquals('2025-12-31', $attributes[0]['attribute_value']);
    }

    /**
     * Test serial items by stock
     */
    public function testSerialItemsByStock(): void
    {
        // Create multiple test items
        $this->repository->createSerialItem($this->testData);

        $anotherItem = $this->testData;
        $anotherItem['serial_no'] = 'SN987654321';
        $this->repository->createSerialItem($anotherItem);

        // Get items by stock
        $items = $this->repository->getSerialItemsByStock($this->testData['stock_id']);

        $this->assertIsArray($items);
        $this->assertCount(2, $items);

        foreach ($items as $item) {
            $this->assertEquals($this->testData['stock_id'], $item['stock_id']);
        }
    }

    /**
     * Test statistics
     */
    public function testStatistics(): void
    {
        // Create test item
        $this->repository->createSerialItem($this->testData);

        // Get statistics
        $stats = $this->repository->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['active']);
    }

    /**
     * Test search functionality
     */
    public function testSearchFunctionality(): void
    {
        // Create test item
        $this->repository->createSerialItem($this->testData);

        // Search by stock_id
        $results = $this->repository->searchSerialItems([
            'stock_id' => $this->testData['stock_id']
        ]);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals($this->testData['stock_id'], $results[0]['stock_id']);

        // Search by serial_no
        $results = $this->repository->searchSerialItems([
            'serial_no' => $this->testData['serial_no']
        ]);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals($this->testData['serial_no'], $results[0]['serial_no']);
    }

    /**
     * Clean up test data
     */
    protected function tearDown(): void
    {
        // Clean up test data if needed
        // Note: In a real implementation, you might want to use transactions
        // and roll back changes, or clean up specific test data
    }
}