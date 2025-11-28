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
$page_security = 'SA_SERIALITEMS';
$path_to_root = "../../..";

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);

page(_($help_context = "Serial Number Inquiry"), false, false, "", $js);

//--------------------------------------------------------------------------------------------

function display_serial_items()
{
	global $db_connections;
	$prefix = $db_connections[user_company()]['tbpref'];

	$stock_id = get_post('stock_id', '');
	$serial_no = get_post('serial_no', '');
	$status = get_post('status', '');
	$location = get_post('location', '');

	$sql = "SELECT si.*, si.created_at as entry_date
			FROM {$prefix}serial_items si
			WHERE 1=1";

	if (!empty($stock_id)) {
		$sql .= " AND si.stock_id LIKE " . db_escape('%' . $stock_id . '%');
	}

	if (!empty($serial_no)) {
		$sql .= " AND si.serial_no LIKE " . db_escape('%' . $serial_no . '%');
	}

	if (!empty($status)) {
		$sql .= " AND si.status = " . db_escape($status);
	}

	if (!empty($location)) {
		$sql .= " AND si.location = " . db_escape($location);
	}

	$sql .= " ORDER BY si.stock_id, si.serial_no";

	$result = db_query($sql, "Cannot get serial items");

	start_table(TABLESTYLE);
	$th = array(_("Item Code"), _("Serial Number"), _("Status"), _("Location"), _("Entry Date"));
	table_header($th);

	$k = 0;
	while ($row = db_fetch($result)) {
		alt_table_row_color($k);

		label_cell($row['stock_id']);
		label_cell($row['serial_no']);
		label_cell($row['status']);
		label_cell($row['location']);
		label_cell(sql2date($row['entry_date']));

		end_row();
	}

	end_table(1);
}

//--------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE2);

text_row(_("Item Code:"), 'stock_id', null, 20, 20);
text_row(_("Serial Number:"), 'serial_no', null, 20, 50);

array_selector_row(_("Status:"), 'status', null,
	array('' => _("All"), 'active' => _("Active"), 'sold' => _("Sold"),
		  'returned' => _("Returned"), 'scrapped' => _("Scrapped")));

locations_list_row(_("Location:"), 'location', null, false, _("All"));

end_table(1);

submit_center('Search', _("Search"), true, '', 'default');

end_form();

//--------------------------------------------------------------------------------------------

if (isset($_POST['Search']) || isset($_GET['stock_id'])) {
	display_serial_items();
}

end_page();