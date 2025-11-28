<?php
/**
 * SerialNumber Module Validation Script
 *
 * Validates that the SerialNumber module can be loaded and initialized
 * without syntax errors or missing dependencies.
 */

// Include necessary files
require_once __DIR__ . '/../includes/session.inc';

// Check if required classes exist
$requiredClasses = [
    'Modules\\ModuleInterface',
    'Events\\EventManager',
    'Plugins\\PluginManager',
    'Services\\SerialNumberService',
    'Database\\SerialNumberRepository'
];

echo "Checking required classes...\n";
foreach ($requiredClasses as $class) {
    if (class_exists($class)) {
        echo "✓ $class found\n";
    } else {
        echo "✗ $class not found\n";
    }
}

// Check if SerialNumber module class exists
echo "\nChecking SerialNumber module...\n";
if (class_exists('Modules\\SerialNumber\\SerialNumber')) {
    echo "✓ SerialNumber class found\n";

    try {
        $module = new Modules\SerialNumber\SerialNumber();
        echo "✓ SerialNumber module instantiated successfully\n";

        echo "Module Name: " . $module->getName() . "\n";
        echo "Module Version: " . $module->getVersion() . "\n";
        echo "Module Description: " . $module->getDescription() . "\n";
        echo "Module Enabled: " . ($module->isEnabled() ? 'Yes' : 'No') . "\n";

        $dependencies = $module->getDependencies();
        echo "Dependencies: " . implode(', ', $dependencies) . "\n";

    } catch (Exception $e) {
        echo "✗ Error instantiating SerialNumber module: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ SerialNumber class not found\n";
}

// Check service and repository
echo "\nChecking service and repository...\n";
if (class_exists('Services\\SerialNumberService')) {
    echo "✓ SerialNumberService found\n";
} else {
    echo "✗ SerialNumberService not found\n";
}

if (class_exists('Database\\SerialNumberRepository')) {
    echo "✓ SerialNumberRepository found\n";
} else {
    echo "✗ SerialNumberRepository not found\n";
}

echo "\nValidation complete.\n";