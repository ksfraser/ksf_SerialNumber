# Changelog

All notable changes to the SerialNumber module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-28

### Added
- Initial release of comprehensive SerialNumber module for FrontAccounting
- Complete serial number lifecycle tracking (creation, movement, status updates)
- Integration with FrontAccounting 2.4+ transaction processing
- Event-driven architecture with PSR-14 compliance
- Doctrine DBAL integration for database abstraction
- Comprehensive PHPUnit test suite (12 test methods)
- Assets integration interfaces for future employee loan/issue management
- Business requirements documentation
- API documentation and usage examples
- Security role definitions for module access control
- Database schema with serial_items, serial_movements, and serial_attributes tables
- Legacy FA compatibility layer
- Modern plugin system integration
- Custom fields support for serial number attributes
- Reporting and inquiry interfaces
- Automatic table creation during module installation

### Features
- **Serial Number Entry**: Web interface for adding serial numbers to inventory items
- **Serial Number Inquiry**: Search and filter serial numbers with detailed information
- **Movement Tracking**: Complete audit trail of serial number movements across transactions
- **Status Management**: Track serial number status (active, sold, returned, scrapped, loaned, disposed)
- **Location Tracking**: Monitor serial numbers across warehouse locations
- **Transaction Validation**: Automatic validation during sales, purchases, and adjustments
- **Event Integration**: Extensible event system for custom workflows
- **Assets Ready**: Forward-compatible interfaces for future Assets module integration

### Technical Implementation
- **SOLID Principles**: Clean architecture following SRP, OCP, LSP, ISP, DIP
- **Dependency Injection**: Service locator pattern for loose coupling
- **Repository Pattern**: Data access abstraction with Doctrine DBAL
- **Service Layer**: Business logic encapsulation
- **Event Dispatching**: PSR-14 compliant event system
- **Testing**: Comprehensive unit tests with PHPUnit
- **Documentation**: Complete API documentation and usage guides

### Database Schema
- `serial_items`: Core serial number data with status and location tracking
- `serial_movements`: Complete movement history with transaction references
- `serial_attributes`: Custom attributes support for extended functionality

### Security
- Role-based access control with specific security areas
- Input validation and sanitization
- SQL injection prevention through prepared statements
- XSS protection in web interfaces

### Compatibility
- FrontAccounting 2.4+
- PHP 8.0+
- MySQL/MariaDB databases
- PSR-14 Event Dispatcher
- Doctrine DBAL 2.x

### Future Integration Points
- Assets module employee loan/issue management
- Maintenance scheduling and tracking
- Depreciation calculations
- Barcode generation and scanning
- Mobile application support
- IoT device integration

### Files Included
- Core module files (SerialNumber.php, hooks.php, app.php)
- Service and repository classes
- Test suite (SerialNumberModuleTest.php)
- Documentation (README.md, BusinessRequirements.md)
- Database installation scripts
- Assets integration interfaces and examples
- License and changelog files

### Installation
- Automatic module installation with database table creation
- Manual SQL installation option
- Configuration through System Preferences
- Item-level serial tracking configuration

### Testing
- 12 comprehensive test methods covering all major functionality
- Unit tests for service layer, repository layer, and business logic
- Integration tests for event system and database operations
- Test coverage for error conditions and edge cases

### Documentation
- Complete README with installation and usage instructions
- API documentation with code examples
- Business requirements specification
- Architecture and design documentation
- Troubleshooting guide

---

## Types of changes
- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes