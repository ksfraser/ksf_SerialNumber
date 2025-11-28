# SerialNumber Module

## Overview

The SerialNumber module provides comprehensive serial number tracking functionality for FrontAccounting. It enables organizations to track individual items through their lifecycle from receipt to sale, with full integration to both the legacy FA 2.4 framework and the modern event/plugin system.

## Features

### Core Functionality
- **Serial Number Assignment**: Assign unique serial numbers to inventory items
- **Movement Tracking**: Track serial numbers across all transactions (sales, purchases, adjustments)
- **Status Management**: Monitor item status (active, sold, returned, scrapped, loaned, disposed)
- **Location Tracking**: Track items across different warehouse locations
- **Custom Attributes**: Add custom attributes to serial numbers (warranty info, specifications, etc.)

### Assets Integration (Future Enhancement)
- **Employee Loan/Issue Management**: Track assets loaned to employees with approval workflows
- **Asset Maintenance Tracking**: Schedule and record maintenance activities per serial number
- **Asset Disposal Processing**: Track asset retirement with disposal methods and values
- **Lifecycle Management**: Complete asset lifecycle from acquisition to disposal

### Integration Features
- **Legacy FA Compatibility**: Works with existing FA 2.4 transaction processing
- **Modern Event System**: Integrates with the modern event system for extensible architecture
- **Plugin System**: Supports plugins for custom serial number logic
- **Database Abstraction**: Uses Doctrine DBAL for cross-version compatibility

### Reporting & Inquiry
- **Serial Number Inquiry**: Search and view serial numbers by various criteria
- **Movement History**: Track complete movement history for each serial number
- **Status Reports**: Generate reports on serial number status and location
- **Statistics**: View statistics on serial number usage and status distribution

## Installation

### Automatic Installation
1. Place the module files in `modules/SerialNumber/`
2. Go to Setup → Install/Activate Extensions
3. Find "SerialNumber" in the list and click "Install"
4. The module will create necessary database tables automatically

### Manual Installation
If automatic installation fails, run the SQL scripts in `install/serial_tables.sql` manually.

## Configuration

### System Preferences
Enable serial tracking in System Preferences:
- Go to Setup → System Preferences
- Check "Enable Serial Number Tracking"

### Item-Level Configuration
Configure serial tracking for individual items:
- Go to Inventory → Items → Modify
- Check "Serial Tracking" for items that require serial numbers

## Usage

### Adding Serial Numbers
1. Go to Serial Number → Serial Number Entry
2. Select the stock item
3. Enter the serial number
4. Select the location
5. Click "Add Serial Number"

### Viewing Serial Numbers
1. Go to Serial Number → Serial Number Inquiry
2. Use filters to search for specific serial numbers
3. View details including status, location, and entry date

### Transaction Processing
Serial numbers are automatically validated during:
- Sales invoice creation
- Customer delivery notes
- Inventory adjustments
- Goods receipts
- Supplier invoices

## API Integration

### Service Layer
```php
use Services\SerialNumberService;

$service = new SerialNumberService($repository);

// Validate serial numbers for transaction
$service->validateTransactionSerials($cart, $transType);

// Record movements
$service->recordTransactionMovements($cart, $transType);
```

### Repository Layer
```php
use Database\SerialNumberRepository;

$repository = new SerialNumberRepository();

// Get serial item
$item = $repository->getSerialItem('STOCK001', 'SN12345');

// Create movement
$movementId = $repository->createMovement($movementData);
```

### Event Integration
The module integrates with the event system:

```php
// Listen for transaction events
$eventManager->addListener('transaction.postwrite', function($event) {
    // Handle serial number processing
});
```

## Database Schema

### serial_items
- `id`: Primary key
- `stock_id`: Item code
- `serial_no`: Serial number (unique per item)
- `status`: Current status (active, sold, returned, scrapped)
- `location`: Current location code
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### serial_movements
- `id`: Primary key
- `serial_item_id`: Reference to serial_items
- `trans_type`: Transaction type
- `trans_no`: Transaction number
- `stock_id`: Item code
- `serial_no`: Serial number
- `location_from`: Source location
- `location_to`: Destination location
- `qty`: Quantity moved
- `reference`: Transaction reference
- `created_at`: Movement timestamp

### serial_attributes
- `id`: Primary key
- `serial_item_id`: Reference to serial_items
- `attribute_name`: Attribute name
- `attribute_value`: Attribute value
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## Security

The module defines the following security areas:
- `SA_SERIALNUMBER`: Serial number management (entry, transfer, adjustment)
- `SA_SERIALITEMS`: Serial number inquiry access
- `SA_SERIALMOVEMENTS`: Movement history access
- `SA_SERIALREPORTS`: Report access

## Testing

Run the test suite:
```bash
phpunit tests/SerialNumberModuleTest.php
```

## Troubleshooting

### Common Issues

1. **Tables not created**: Check database permissions and run manual SQL installation
2. **Serial validation fails**: Ensure items are configured for serial tracking
3. **Events not firing**: Verify event system is properly initialized

### Debug Mode
Enable debug logging in `config.php`:
```php
define('FA_SERIAL_DEBUG', true);
```

## Future Enhancements

- **Barcode Integration**: Generate and scan barcodes for serial numbers
- **Bulk Operations**: Import/export serial numbers in bulk
- **Advanced Reporting**: Integration with WebERP-style advanced reporting
- **Mobile App**: Mobile interface for warehouse operations
- **IoT Integration**: Connect with IoT devices for automated tracking

## Assets Integration

The SerialNumber module includes forward-compatible interfaces and event handlers for future Assets module integration:

### Employee Loan/Issue Management
```php
// Loan asset to employee
$eventData = [
    'serial_no' => 'SN123456',
    'employee_id' => 'EMP001',
    'loan_date' => '2025-11-28',
    'expected_return' => '2025-12-15',
    'notes' => 'Temporary assignment for project work'
];

$eventManager->dispatch('assets.employee.loan', $eventData);
```

### Asset Maintenance Tracking
```php
// Record maintenance activity
$maintenanceData = [
    'serial_no' => 'SN123456',
    'maintenance_type' => 'Preventive Maintenance',
    'maintenance_date' => '2025-11-28',
    'next_due' => '2026-05-28',
    'cost' => 150.00,
    'notes' => 'Replaced filters and checked calibration'
];

$eventManager->dispatch('assets.maintenance', $maintenanceData);
```

### Asset Disposal Processing
```php
// Process asset disposal
$disposalData = [
    'serial_no' => 'SN123456',
    'disposal_date' => '2025-11-28',
    'disposal_method' => 'sold',
    'disposal_value' => 500.00,
    'notes' => 'Sold to external vendor'
];

$eventManager->dispatch('assets.disposal', $disposalData);
```

### Supported Asset Statuses
- **active**: Available for use
- **loaned**: Currently loaned to an employee
- **sold**: Sold to customer
- **returned**: Returned from loan/customer
- **scrapped**: Scrapped/damaged
- **disposed**: Permanently disposed

### Assets Integration Interface
The module provides `AssetsIntegrationInterface.php` that defines the contract for future Assets module implementation, including methods for loan management, maintenance tracking, and lifecycle reporting.

## Support

For support and contributions:
- Report issues on the project repository
- Check the documentation for API references
- Join the community forums for discussions

## License

This module is released under the GPL-3.0 license, matching FrontAccounting's license.