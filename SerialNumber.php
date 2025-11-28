<?php
/**
 * SerialNumber Module
 *
 * Implements serial number tracking for inventory items with full integration
 * to both legacy FA 2.4 framework and modern event/plugin system.
 *
 * @package Modules\SerialNumber
 * @author FrontAccounting Refactoring Team
 * @license GPL-3.0
 */

namespace Modules\SerialNumber;

use Modules\ModuleInterface;
use Events\EventManager;
use Plugins\PluginManager;
use Services\SerialNumberService;
use Database\SerialNumberRepository;

/**
 * SerialNumber Module Class
 *
 * Provides comprehensive serial number tracking functionality including:
 * - Serial number assignment and management
 * - Movement tracking across transactions
 * - Integration with inventory, sales, and purchasing
 * - Custom attributes support
 * - Reporting and inquiry capabilities
 */
class SerialNumber implements ModuleInterface
{
    /**
     * Module name
     */
    const MODULE_NAME = 'SerialNumber';

    /**
     * Module version
     */
    const VERSION = '1.0.0';

    /**
     * Serial number service instance
     */
    private SerialNumberService $service;

    /**
     * Serial number repository instance
     */
    private SerialNumberRepository $repository;

    /**
     * Event manager instance
     */
    private EventManager $eventManager;

    /**
     * Plugin manager instance
     */
    private PluginManager $pluginManager;

    /**
     * Module configuration
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Module configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enable_tracking' => true,
            'auto_generate' => false,
            'require_unique' => true,
            'track_movements' => true,
            'enable_attributes' => true,
        ], $config);

        $this->initializeDependencies();
    }

    /**
     * Initialize module dependencies
     */
    private function initializeDependencies(): void
    {
        $this->repository = new SerialNumberRepository();
        $this->service = new SerialNumberService($this->repository);
        $this->eventManager = EventManager::getInstance();
        $this->pluginManager = PluginManager::getInstance();
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get module description
     */
    public function getDescription(): string
    {
        return 'Advanced serial number tracking module for inventory management';
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return [
            'Events',
            'Plugins',
            'Database',
            'Services'
        ];
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enable_tracking'] ?? true;
    }

    /**
     * Initialize the module
     */
    public function initialize(): bool
    {
        try {
            // Register event listeners
            $this->registerEventListeners();

            // Register plugin hooks
            $this->registerPluginHooks();

            // Initialize database tables
            $this->initializeDatabase();

            return true;
        } catch (\Exception $e) {
            error_log("SerialNumber module initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register event listeners
     */
    private function registerEventListeners(): void
    {
        // Transaction events
        $this->eventManager->addListener('transaction.prewrite', [$this, 'onTransactionPreWrite']);
        $this->eventManager->addListener('transaction.postwrite', [$this, 'onTransactionPostWrite']);
        $this->eventManager->addListener('transaction.prevoid', [$this, 'onTransactionPreVoid']);

        // Inventory events
        $this->eventManager->addListener('inventory.adjustment', [$this, 'onInventoryAdjustment']);
        $this->eventManager->addListener('inventory.transfer', [$this, 'onInventoryTransfer']);

        // Sales events
        $this->eventManager->addListener('sales.delivery', [$this, 'onSalesDelivery']);
        $this->eventManager->addListener('sales.invoice', [$this, 'onSalesInvoice']);

        // Purchasing events
        $this->eventManager->addListener('purchasing.receipt', [$this, 'onPurchasingReceipt']);
        $this->eventManager->addListener('purchasing.invoice', [$this, 'onPurchasingInvoice']);

        // Future Assets integration events
        $this->eventManager->addListener('assets.employee.loan', [$this, 'onEmployeeLoan']);
        $this->eventManager->addListener('assets.employee.return', [$this, 'onEmployeeReturn']);
        $this->eventManager->addListener('assets.maintenance', [$this, 'onAssetMaintenance']);
        $this->eventManager->addListener('assets.disposal', [$this, 'onAssetDisposal']);
    }

    /**
     * Register plugin hooks
     */
    private function registerPluginHooks(): void
    {
        $this->pluginManager->registerHook('serial.validate', [$this->service, 'validateSerialNumbers']);
        $this->pluginManager->registerHook('serial.generate', [$this->service, 'generateSerialNumber']);
        $this->pluginManager->registerHook('serial.track', [$this->service, 'trackMovement']);
    }

    /**
     * Initialize database tables
     */
    private function initializeDatabase(): void
    {
        $this->repository->createTables();
    }

    /**
     * Handle transaction pre-write event
     */
    public function onTransactionPreWrite(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $cart = $eventData['cart'] ?? null;
        $transType = $eventData['trans_type'] ?? null;

        if ($cart && $transType) {
            $this->service->validateTransactionSerials($cart, $transType);
        }
    }

    /**
     * Handle transaction post-write event
     */
    public function onTransactionPostWrite(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $cart = $eventData['cart'] ?? null;
        $transType = $eventData['trans_type'] ?? null;

        if ($cart && $transType) {
            $this->service->recordTransactionMovements($cart, $transType);
        }
    }

    /**
     * Handle transaction pre-void event
     */
    public function onTransactionPreVoid(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $transType = $eventData['trans_type'] ?? null;
        $transNo = $eventData['trans_no'] ?? null;

        if ($transType && $transNo) {
            $this->service->reverseTransactionMovements($transType, $transNo);
        }
    }

    /**
     * Handle inventory adjustment event
     */
    public function onInventoryAdjustment(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleInventoryAdjustment($eventData);
    }

    /**
     * Handle inventory transfer event
     */
    public function onInventoryTransfer(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleInventoryTransfer($eventData);
    }

    /**
     * Handle sales delivery event
     */
    public function onSalesDelivery(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleSalesDelivery($eventData);
    }

    /**
     * Handle sales invoice event
     */
    public function onSalesInvoice(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleSalesInvoice($eventData);
    }

    /**
     * Handle purchasing receipt event
     */
    public function onPurchasingReceipt(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handlePurchasingReceipt($eventData);
    }

    /**
     * Handle employee loan/issue event
     */
    public function onEmployeeLoan(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleEmployeeLoan($eventData);
    }

    /**
     * Handle employee return event
     */
    public function onEmployeeReturn(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleEmployeeReturn($eventData);
    }

    /**
     * Handle asset maintenance event
     */
    public function onAssetMaintenance(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleAssetMaintenance($eventData);
    }

    /**
     * Handle asset disposal event
     */
    public function onAssetDisposal(array $eventData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->service->handleAssetDisposal($eventData);
    }

    /**
     * Get serial number service
     */
    public function getService(): SerialNumberService
    {
        return $this->service;
    }

    /**
     * Get serial number repository
     */
    public function getRepository(): SerialNumberRepository
    {
        return $this->repository;
    }

    /**
     * Get module configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update module configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Shutdown the module
     */
    public function shutdown(): void
    {
        // Cleanup resources if needed
    }
}