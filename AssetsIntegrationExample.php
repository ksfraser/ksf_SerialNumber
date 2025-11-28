<?php
/**
 * Assets Module Integration Example
 *
 * This file demonstrates how the future Assets module should integrate
 * with the SerialNumber module for employee loan/issue management.
 *
 * This is a conceptual example showing the integration patterns and
 * event handling that should be implemented in the Assets module.
 *
 * @package Modules\Assets
 * @author FrontAccounting Refactoring Team
 * @license GPL-3.0
 */

namespace Modules\Assets;

use Modules\SerialNumber\AssetsIntegrationInterface;
use Events\EventManager;
use Services\SerialNumberService;

/**
 * Assets Module Integration Class
 *
 * Example implementation showing how the Assets module should integrate
 * with the SerialNumber module for comprehensive asset lifecycle management.
 *
 * This class demonstrates:
 * - Employee loan/issue processing
 * - Asset return handling
 * - Maintenance scheduling and tracking
 * - Asset disposal processing
 * - Integration with serial number tracking
 */
class AssetsModule implements AssetsIntegrationInterface
{
    /**
     * Serial number service instance
     */
    private SerialNumberService $serialService;

    /**
     * Event manager instance
     */
    private EventManager $eventManager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->serialService = new SerialNumberService();
        $this->eventManager = EventManager::getInstance();

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for Assets integration
     */
    private function registerEventListeners(): void
    {
        // Listen for serial number events that affect assets
        $this->eventManager->addListener('serial.employee.loan', [$this, 'onSerialLoan']);
        $this->eventManager->addListener('serial.employee.return', [$this, 'onSerialReturn']);
        $this->eventManager->addListener('serial.maintenance', [$this, 'onSerialMaintenance']);
        $this->eventManager->addListener('serial.disposal', [$this, 'onSerialDisposal']);
    }

    /**
     * Loan/issue an asset to an employee
     *
     * @param array $loanData Loan information
     * @return bool Success status
     */
    public function loanAssetToEmployee(array $loanData): bool
    {
        try {
            // Validate loan data
            $this->validateLoanData($loanData);

            // Check if asset is available
            if (!$this->isAssetAvailable($loanData['serial_no'])) {
                throw new \Exception("Asset {$loanData['serial_no']} is not available for loan");
            }

            // Create loan record in assets system
            $loanId = $this->createLoanRecord($loanData);

            // Dispatch serial number loan event
            $eventData = array_merge($loanData, ['loan_id' => $loanId]);
            $this->eventManager->dispatch('assets.employee.loan', $eventData);

            return true;
        } catch (\Exception $e) {
            error_log("Asset loan failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process return of loaned asset from employee
     *
     * @param array $returnData Return information
     * @return bool Success status
     */
    public function returnAssetFromEmployee(array $returnData): bool
    {
        try {
            // Validate return data
            $this->validateReturnData($returnData);

            // Update loan record with return information
            $this->updateLoanRecord($returnData);

            // Dispatch serial number return event
            $this->eventManager->dispatch('assets.employee.return', $returnData);

            return true;
        } catch (\Exception $e) {
            error_log("Asset return failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record asset maintenance activity
     *
     * @param array $maintenanceData Maintenance information
     * @return bool Success status
     */
    public function recordAssetMaintenance(array $maintenanceData): bool
    {
        try {
            // Validate maintenance data
            $this->validateMaintenanceData($maintenanceData);

            // Create maintenance record
            $maintenanceId = $this->createMaintenanceRecord($maintenanceData);

            // Schedule next maintenance if applicable
            if (!empty($maintenanceData['next_due'])) {
                $this->scheduleNextMaintenance($maintenanceData['serial_no'], $maintenanceData['next_due']);
            }

            // Dispatch serial number maintenance event
            $eventData = array_merge($maintenanceData, ['maintenance_id' => $maintenanceId]);
            $this->eventManager->dispatch('assets.maintenance', $eventData);

            return true;
        } catch (\Exception $e) {
            error_log("Maintenance recording failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process asset disposal
     *
     * @param array $disposalData Disposal information
     * @return bool Success status
     */
    public function disposeAsset(array $disposalData): bool
    {
        try {
            // Validate disposal data
            $this->validateDisposalData($disposalData);

            // Create disposal record
            $disposalId = $this->createDisposalRecord($disposalData);

            // Update asset status to disposed
            $this->updateAssetStatus($disposalData['serial_no'], 'disposed');

            // Dispatch serial number disposal event
            $eventData = array_merge($disposalData, ['disposal_id' => $disposalId]);
            $this->eventManager->dispatch('assets.disposal', $eventData);

            return true;
        } catch (\Exception $e) {
            error_log("Asset disposal failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get assets currently loaned to employees
     *
     * @param string|null $employeeId Optional employee filter
     * @return array List of loaned assets
     */
    public function getLoanedAssets(?string $employeeId = null): array
    {
        // Query assets with loaned status
        $query = "SELECT * FROM assets_loans WHERE status = 'active'";
        if ($employeeId) {
            $query .= " AND employee_id = " . db_escape($employeeId);
        }

        $result = db_query($query);
        return db_fetch_all($result);
    }

    /**
     * Get overdue asset returns
     *
     * @return array List of overdue returns
     */
    public function getOverdueReturns(): array
    {
        $query = "SELECT * FROM assets_loans
                 WHERE status = 'active'
                 AND expected_return < CURDATE()";

        $result = db_query($query);
        return db_fetch_all($result);
    }

    /**
     * Get assets due for maintenance
     *
     * @return array List of assets due for maintenance
     */
    public function getAssetsDueForMaintenance(): array
    {
        $query = "SELECT * FROM assets_maintenance_schedule
                 WHERE next_due <= CURDATE()
                 AND status = 'scheduled'";

        $result = db_query($query);
        return db_fetch_all($result);
    }

    /**
     * Get asset maintenance history
     *
     * @param string $serialNo Serial number
     * @return array Maintenance history
     */
    public function getAssetMaintenanceHistory(string $serialNo): array
    {
        $query = "SELECT * FROM assets_maintenance
                 WHERE serial_no = " . db_escape($serialNo) . "
                 ORDER BY maintenance_date DESC";

        $result = db_query($query);
        return db_fetch_all($result);
    }

    /**
     * Get asset lifecycle information
     *
     * @param string $serialNo Serial number
     * @return array Lifecycle information
     */
    public function getAssetLifecycle(string $serialNo): array
    {
        return [
            'acquisition' => $this->getAcquisitionInfo($serialNo),
            'loans' => $this->getLoanHistory($serialNo),
            'maintenance' => $this->getAssetMaintenanceHistory($serialNo),
            'movements' => $this->getMovementHistory($serialNo),
            'disposal' => $this->getDisposalInfo($serialNo)
        ];
    }

    // Event handlers for serial number integration

    /**
     * Handle serial loan event
     */
    public function onSerialLoan(array $eventData): void
    {
        // Update local asset records when serial number is loaned
        $this->updateAssetLocation($eventData['serial_no'], 'loaned');
    }

    /**
     * Handle serial return event
     */
    public function onSerialReturn(array $eventData): void
    {
        // Update local asset records when serial number is returned
        $this->updateAssetLocation($eventData['serial_no'], 'returned');
    }

    /**
     * Handle serial maintenance event
     */
    public function onSerialMaintenance(array $eventData): void
    {
        // Update maintenance schedule based on completed work
        if (!empty($eventData['next_due'])) {
            $this->updateMaintenanceSchedule($eventData['serial_no'], $eventData['next_due']);
        }
    }

    /**
     * Handle serial disposal event
     */
    public function onSerialDisposal(array $eventData): void
    {
        // Mark asset as disposed in local records
        $this->markAssetDisposed($eventData['serial_no'], $eventData);
    }

    // Private helper methods (implementation details)

    private function validateLoanData(array $data): void
    {
        // Validation logic
    }

    private function validateReturnData(array $data): void
    {
        // Validation logic
    }

    private function validateMaintenanceData(array $data): void
    {
        // Validation logic
    }

    private function validateDisposalData(array $data): void
    {
        // Validation logic
    }

    private function isAssetAvailable(string $serialNo): bool
    {
        // Check availability logic
        return true;
    }

    private function createLoanRecord(array $data): int
    {
        // Create loan record logic
        return 1;
    }

    private function updateLoanRecord(array $data): void
    {
        // Update loan record logic
    }

    private function createMaintenanceRecord(array $data): int
    {
        // Create maintenance record logic
        return 1;
    }

    private function createDisposalRecord(array $data): int
    {
        // Create disposal record logic
        return 1;
    }

    private function updateAssetStatus(string $serialNo, string $status): void
    {
        // Update asset status logic
    }

    private function scheduleNextMaintenance(string $serialNo, string $nextDue): void
    {
        // Schedule maintenance logic
    }

    private function updateAssetLocation(string $serialNo, string $location): void
    {
        // Update location logic
    }

    private function updateMaintenanceSchedule(string $serialNo, string $nextDue): void
    {
        // Update schedule logic
    }

    private function markAssetDisposed(string $serialNo, array $data): void
    {
        // Mark disposed logic
    }

    private function getAcquisitionInfo(string $serialNo): array
    {
        // Get acquisition info logic
        return [];
    }

    private function getLoanHistory(string $serialNo): array
    {
        // Get loan history logic
        return [];
    }

    private function getMovementHistory(string $serialNo): array
    {
        // Get movement history logic
        return [];
    }

    private function getDisposalInfo(string $serialNo): array
    {
        // Get disposal info logic
        return [];
    }
}