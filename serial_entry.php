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
$page_security = 'SA_SERIALNUMBER';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Serial Number Entry"), false, false, "", $js);

//--------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['stock_id']) == 0) {
		display_error(_("The stock item must be selected."));
		set_focus('stock_id');
		return false;
	}

	if (strlen($_POST['serial_no']) == 0) {
		display_error(_("The serial number must be entered."));
		set_focus('serial_no');
		return false;
	}

	if (strlen($_POST['location']) == 0) {
		display_error(_("The location must be selected."));
		set_focus('location');
		return false;
	}

	// Check if serial number already exists for this item
	global $db_connections;
	$prefix = $db_connections[user_company()]['tbpref'];

	$sql = "SELECT COUNT(*) FROM {$prefix}serial_items
			WHERE stock_id = ".db_escape($_POST['stock_id'])."
			AND serial_no = ".db_escape($_POST['serial_no']);
	$result = db_query($sql, "Cannot check serial number existence");
	$count = db_fetch_row($result);

	if ($count[0] > 0) {
		display_error(_("This serial number already exists for the selected item."));
		set_focus('serial_no');
		return false;
	}

	return true;
}

function handle_submit()
{
	global $db_connections;
	$prefix = $db_connections[user_company()]['tbpref'];

	if (!can_process())
		return;

	$sql = "INSERT INTO {$prefix}serial_items (
				stock_id, serial_no, status, location, created_at, updated_at
			) VALUES (
				".db_escape($_POST['stock_id']).",
				".db_escape($_POST['serial_no']).",
				'active',
				".db_escape($_POST['location']).",
				NOW(), NOW()
			)";

	db_query($sql, "Cannot add serial number");

	display_notification(_("Serial number has been added."));

	// Clear form for next entry
	$_POST['serial_no'] = '';
	set_focus('serial_no');
}

//--------------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM'])) {
	handle_submit();
}

//--------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE2);

stock_items_list_row(_("Item:"), 'stock_id', null, false, true);

text_row(_("Serial Number:"), 'serial_no', null, 20, 50);

locations_list_row(_("Location:"), 'location', null);

end_table(1);

submit_center('ADD_ITEM', _("Add Serial Number"), true, '', 'default');

end_form();

end_page();