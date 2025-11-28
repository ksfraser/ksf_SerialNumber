<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
class hooks_SerialNumber extends hooks {
	var $module_name = 'SerialNumber';

	/*
		Install additional tabs provided by extension
	*/
	function install_tabs($app) {
		$app->add_application(new SerialNumber_app);
	}

	/*
		Install additonal menu options provided by extension
	*/
	function install_options($app) {
	}

	function install_access() {
		$security_sections[SS_SERIALNUMBER] = _("Serial Number Management");

		$security_areas['SA_SERIALNUMBER'] = array(SS_SERIALNUMBER|1, _("Serial Number Management"));
		$security_areas['SA_SERIALITEMS'] = array(SS_SERIALNUMBER|2, _("Serial Number Items"));
		$security_areas['SA_SERIALMOVEMENTS'] = array(SS_SERIALNUMBER|3, _("Serial Number Movements"));
		$security_areas['SA_SERIALREPORTS'] = array(SS_SERIALNUMBER|4, _("Serial Number Reports"));

		return array($security_areas, $security_sections);
	}

	/*
		Database transaction hooks
	*/
	function db_prewrite(&$cart, $trans_type) {
		// Handle serial number validation before transaction write
		if (in_array($trans_type, array(ST_SALESINVOICE, ST_CUSTDELIVERY, ST_INVADJUST))) {
			return $this->validate_serial_numbers($cart, $trans_type);
		}
		return true;
	}

	function db_postwrite(&$cart, $trans_type) {
		// Update serial number movements after transaction write
		if (in_array($trans_type, array(ST_SALESINVOICE, ST_CUSTDELIVERY, ST_INVADJUST))) {
			return $this->update_serial_movements($cart, $trans_type);
		}
		return true;
	}

	function db_prevoid($trans_type, $trans_no) {
		// Handle serial number reversal before transaction void
		if (in_array($trans_type, array(ST_SALESINVOICE, ST_CUSTDELIVERY, ST_INVADJUST))) {
			return $this->reverse_serial_movements($trans_type, $trans_no);
		}
		return true;
	}

	/*
		Serial number validation
	*/
	private function validate_serial_numbers(&$cart, $trans_type) {
		global $SysPrefs;

		if (!$SysPrefs->serial_tracking_enabled()) {
			return true; // Skip if serial tracking not enabled
		}

		// Validate serial numbers for each line item
		foreach ($cart->line_items as $line_no => $line_item) {
			if ($this->requires_serial_number($line_item)) {
				$validation_result = $this->validate_line_serials($line_item, $trans_type);
				if (!$validation_result['valid']) {
					return $validation_result['message'];
				}
			}
		}

		return true;
	}

	/*
		Update serial number movements
	*/
	private function update_serial_movements(&$cart, $trans_type) {
		global $SysPrefs;

		if (!$SysPrefs->serial_tracking_enabled()) {
			return true;
		}

		foreach ($cart->line_items as $line_item) {
			if ($this->requires_serial_number($line_item)) {
				$this->record_serial_movements($line_item, $cart, $trans_type);
			}
		}

		return true;
	}

	/*
		Reverse serial number movements
	*/
	private function reverse_serial_movements($trans_type, $trans_no) {
		global $SysPrefs;

		if (!$SysPrefs->serial_tracking_enabled()) {
			return true;
		}

		// Reverse serial movements for the voided transaction
		return $this->reverse_transaction_serials($trans_type, $trans_no);
	}

	/*
		Check if item requires serial number
	*/
	private function requires_serial_number($line_item) {
		// Check item category or item-specific settings
		return get_item_pref('serial_tracking', $line_item->stock_id);
	}

	/*
		Validate serial numbers for a line item
	*/
	private function validate_line_serials($line_item, $trans_type) {
		// Implementation for serial number validation
		return array('valid' => true, 'message' => '');
	}

	/*
		Record serial number movements
	*/
	private function record_serial_movements($line_item, $cart, $trans_type) {
		// Implementation for recording serial movements
	}

	/*
		Reverse transaction serials
	*/
	private function reverse_transaction_serials($trans_type, $trans_no) {
		// Implementation for reversing serial movements
	}

	/*
		Module installation
	*/
	function install_extension($check_only=false) {
		// Create serial number tables
		$sqls = array(
			"CREATE TABLE IF NOT EXISTS " . $this->get_table_prefix() . "serial_items (
				id int(11) NOT NULL AUTO_INCREMENT,
				stock_id varchar(20) NOT NULL,
				serial_no varchar(50) NOT NULL,
				status enum('active','sold','returned','scrapped') NOT NULL DEFAULT 'active',
				location varchar(5) NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY unique_serial (stock_id, serial_no),
				KEY idx_stock_id (stock_id),
				KEY idx_status (status),
				KEY idx_location (location)
			) ENGINE=InnoDB",

			"CREATE TABLE IF NOT EXISTS " . $this->get_table_prefix() . "serial_movements (
				id int(11) NOT NULL AUTO_INCREMENT,
				serial_item_id int(11) NOT NULL,
				trans_type int(11) NOT NULL,
				trans_no int(11) NOT NULL,
				stock_id varchar(20) NOT NULL,
				serial_no varchar(50) NOT NULL,
				location_from varchar(5) DEFAULT NULL,
				location_to varchar(5) DEFAULT NULL,
				qty decimal(10,4) NOT NULL DEFAULT 1,
				reference varchar(100) DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY idx_serial_item (serial_item_id),
				KEY idx_trans (trans_type, trans_no),
				KEY idx_stock_serial (stock_id, serial_no),
				CONSTRAINT fk_serial_movement_item FOREIGN KEY (serial_item_id)
					REFERENCES " . $this->get_table_prefix() . "serial_items (id) ON DELETE CASCADE
			) ENGINE=InnoDB",

			"CREATE TABLE IF NOT EXISTS " . $this->get_table_prefix() . "serial_attributes (
				id int(11) NOT NULL AUTO_INCREMENT,
				serial_item_id int(11) NOT NULL,
				attribute_name varchar(50) NOT NULL,
				attribute_value text,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY idx_serial_item (serial_item_id),
				KEY idx_attribute (attribute_name),
				CONSTRAINT fk_serial_attribute_item FOREIGN KEY (serial_item_id)
					REFERENCES " . $this->get_table_prefix() . "serial_items (id) ON DELETE CASCADE
			) ENGINE=InnoDB"
		);

		if ($check_only) {
			return true; // Just check if tables can be created
		}

		global $db;
		foreach ($sqls as $sql) {
			if (!db_query($sql, "Cannot create serial number tables")) {
				return false;
			}
		}

		return true;
	}

	/*
		Module uninstallation
	*/
	function uninstall_extension($check_only=false) {
		if ($check_only) {
			return true; // Allow uninstall check
		}

		// Drop serial number tables
		$tables = array(
			$this->get_table_prefix() . "serial_attributes",
			$this->get_table_prefix() . "serial_movements",
			$this->get_table_prefix() . "serial_items"
		);

		foreach ($tables as $table) {
			db_query("DROP TABLE IF EXISTS $table", "Cannot drop serial number table");
		}

		return true;
	}

	/*
		Get table prefix for this module
	*/
	private function get_table_prefix() {
		global $db_connections;
		return $db_connections[user_company()]['tbpref'];
	}
}