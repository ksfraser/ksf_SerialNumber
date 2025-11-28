<?php
/**
 * Assets Integration Interface
 *
 * Defines the contract for future Assets module integration with SerialNumber tracking.
 * This interface establishes the methods that the Assets module should implement
 * to enable employee loan/issue functionality and asset lifecycle management.
 *
 * @package Modules\SerialNumber
 * @author FrontAccounting Refactoring Team
 * @license GPL-3.0
 */

namespace Modules\SerialNumber;

/**
 * Assets Integration Interface
 *
 * This interface defines the methods that should be implemented by the future
 * Assets module to integrate with serial number tracking for:
 * - Employee loan/issue management
 * - Asset maintenance tracking
 * - Asset disposal processing
 * - Asset lifecycle management
 */
interface AssetsIntegrationInterface
{
    /**
     * Loan/issue an asset to an employee
     *
     * @param array $loanData Loan information
     * @return bool Success status
     */
    public function loanAssetToEmployee(array $loanData): bool;

    /**
     * Process return of loaned asset from employee
     *
     * @param array $returnData Return information
     * @return bool Success status
     */
    public function returnAssetFromEmployee(array $returnData): bool;

    /**
     * Record asset maintenance activity
     *
     * @param array $maintenanceData Maintenance information
     * @return bool Success status
     */
    public function recordAssetMaintenance(array $maintenanceData): bool;

    /**
     * Process asset disposal
     *
     * @param array $disposalData Disposal information
     * @return bool Success status
     */
    public function disposeAsset(array $disposalData): bool;

    /**
     * Get assets currently loaned to employees
     *
     * @param string|null $employeeId Optional employee filter
     * @return array List of loaned assets
     */
    public function getLoanedAssets(?string $employeeId = null): array;

    /**
     * Get overdue asset returns
     *
     * @return array List of overdue returns
     */
    public function getOverdueReturns(): array;

    /**
     * Get assets due for maintenance
     *
     * @return array List of assets due for maintenance
     */
    public function getAssetsDueForMaintenance(): array;

    /**
     * Get asset maintenance history
     *
     * @param string $serialNo Serial number
     * @return array Maintenance history
     */
    public function getAssetMaintenanceHistory(string $serialNo): array;

    /**
     * Get asset lifecycle information
     *
     * @param string $serialNo Serial number
     * @return array Lifecycle information
     */
    public function getAssetLifecycle(string $serialNo): array;
}