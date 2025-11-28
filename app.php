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
class SerialNumber_app extends application {
	var $name = 'SerialNumber';
	var $help_context = '&Serial Number Management';

	function SerialNumber_app() {
		parent::__construct();
	}

	function build_menu() {
		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("Serial Number Entry"),
			"modules/SerialNumber/serial_entry.php", SA_SERIALNUMBER);
		$this->add_lapp_function(0, _("Serial Number Transfer"),
			"modules/SerialNumber/serial_transfer.php", SA_SERIALNUMBER);
		$this->add_lapp_function(0, _("Serial Number Adjustment"),
			"modules/SerialNumber/serial_adjustment.php", SA_SERIALNUMBER);

		$this->add_module(_("Inquiries"));
		$this->add_lapp_function(1, _("Serial Number Inquiry"),
			"modules/SerialNumber/inquiry/serial_inquiry.php", SA_SERIALITEMS);
		$this->add_lapp_function(1, _("Serial Movement History"),
			"modules/SerialNumber/inquiry/serial_movements.php", SA_SERIALMOVEMENTS);

		$this->add_module(_("Reports"));
		$this->add_lapp_function(2, _("Serial Number Report"),
			"modules/SerialNumber/reports/serial_report.php", SA_SERIALREPORTS);
		$this->add_lapp_function(2, _("Serial Movement Report"),
			"modules/SerialNumber/reports/movement_report.php", SA_SERIALREPORTS);
		$this->add_lapp_function(2, _("Serial Status Report"),
			"modules/SerialNumber/reports/status_report.php", SA_SERIALREPORTS);
	}

	function user_menu() {
		// Add user-specific menu items if needed
	}
}