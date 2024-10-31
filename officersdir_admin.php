<?php
/**
 * Contains functions handing the administration of the Officers Directory
 * in the WordPress administration
 */

/* Copyright 2010 Douglas Bell (email: douglas@douglasbell.us)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Displays the full table of officers for the WordPress admin with the opportunity to manage them
 */
function officersDirList($confirmMessage = '')
{
	// First question -- is this thing on? This function checks for us
	officersDirInstall();

	// We should already have this, but just in case we don't...
	include_once(dirname(__FILE__) . '/class.officersdir.php');
	
	$officersDir = new OfficersDir();
	$officersDir->selectOfficers(); // All of them, this time!
	$officersTable = $officersDir->displayOfficersAdmin();
	
	// As long as the admin is here, they should know if we're expecting reCAPTCHA API Keys out of them
	$recaptchaPublicKey		= get_option('officersdir_recaptchapublic');
	$recaptchaPrivateKey	= get_option('officersdir_recaptchaprivate');
	
	$recaptchaWarning		= ((!defined('OFFICERSDIR_RECAPTCHAPUBLIC') || !defined('OFFICERSDIR_RECAPTCHAPRIVATE')) && (empty($recaptchaPublicKey) || empty($recaptchaPrivateKey))) ? '<div class="error"><p>You have not specified your <a href="./../wp-admin/options-general.php?page=officers-directory/officers_directory.php">reCAPTCHA API keys</a> yet. The contact form feature will not work until you have done this.</p></div>' : '';
	
	// Do we have a confirmation message to display?
	$displayMessage = (!empty($confirmMessage)) ? "<div id=\"message\" class=\"updated fade\"><p><strong>$confirmMessage</strong></p></div>" : '';
	
	$officersDirListPage = <<<EOT
{$recaptchaWarning}
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Manage Directory of Officers</h2>
{$displayMessage}
<form method="post" action="tools.php?page=officers-directory/officers_directory.php">
<p><input style="float:right" type="submit" class="button" name="officerAction" value="Re-Order" />
With selected: <input type="submit" class="button" name="officerAction" value="Edit" />&nbsp;<input type="submit" class="button" name="officerAction" value="Delete" /> &nbsp;&nbsp;&nbsp; Add&nbsp;<input type="text" name="newOfficerCount" size="2" maxlength="2" value="1" />&nbsp;New&nbsp;Officer(s)&nbsp;<input type="submit" class="button" name="officerAction" value="Add" /> &nbsp;&nbsp;&nbsp; <input type="text" name="newPositionType" size="30" maxlength="255" value="" />&nbsp;<input type="submit" class="button" name="officerAction" value="Add Position Type" /></p>
{$officersTable}
<p><input style="float:right" type="submit" class="button" name="officerAction" value="Re-Order" />
With selected: <input type="submit" class="button" name="officerAction" value="Edit" />&nbsp;<input type="submit" class="button" name="officerAction" value="Delete" /></p>
</form>
<br />
</div>
EOT;
	echo $officersDirListPage;
}

/**
 * We're adding new officers, so display the requested number of forms for adding new officer data
 */
function officersDirAdd($newOfficersCount = 1)
{
	$i = 1;

	// Instantiate our OfficerTypes class
	include_once(dirname(__FILE__) . '/class.officerstypes.php');
	$officersTypes = new OfficersTypes();
	$officersTypes->selectPositionTypes();
	
	// Initialize our page
	$outputAddForm = <<<EOT
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Add New Officers to Directory</h2>
<form method="post" action="tools.php?page=officers-directory/officers_directory.php">
EOT;
	
	// Keep looping out opportunities to submit officers until we reach our count
	// We're using a do-while loop because we definitely want at least one new officer
	do
	{
		// Get our drop-down of position types
		$positionTypesMenu = $officersTypes->displayDropdown("newOfficers[{$i}][positionType]");

		$outputAddForm .= <<<EOT
<h3>New Position #{$i}</h3>
<table class="form-table">
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionName]">Position Name</label></th>
		<td><input name="newOfficers[{$i}][positionName]" type="text" id="newOfficers[{$i}][positionName]" value="" class="regular-text" />
			<span class="description">The title of this officer, i.e. "President".</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionShortname]">Position Shortname</label></th>
		<td><input name="newOfficers[{$i}][positionShortname]" type="text" id="newOfficers[{$i}][positionShortname]" maxlength="100" value="" class="regular-text" />
			<span class="description">Used to identify this position internally. All lowercase, no spaces.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionOfficer]">Position Officer</label></th>
		<td><input name="newOfficers[{$i}][positionOfficer]" type="text" id="newOfficers[{$i}][positionOfficer]" value="" class="regular-text" />
			<span class="description">The name of the person filling this position. Leave blank if vacant.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionCoOfficer]">Position Co-Officer</label></th>
		<td><input name="newOfficers[{$i}][positionCoOfficer]" type="text" id="newOfficers[{$i}][positionCoOfficer]" value="" class="regular-text" />
			<span class="description">Only enter a second name here if there are two co-officers.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionEmail]">Contact E-mail</label></th>
		<td><input name="newOfficers[{$i}][positionEmail]" type="text" id="newOfficers[{$i}][positionEmail]" value="" class="regular-text" />
			<span class="description">This will never be displayed publicly. Only required if you're using the contact form.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionType]">Position Type</label></th>
		<td>{$positionTypesMenu}
			<span class="description">Officers with the same position type will be grouped together.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionOrder]">Position Order</label></th>
		<td><input name="newOfficers[{$i}][positionOrder]" type="text" id="newOfficers[{$i}][positionOrder]" value="" size="3" maxlength="3" />
			<span class="description">Optional. Enter a number identifying the order this officer will appear in the directory among officers in its Position Type.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="newOfficers[{$i}][positionDescription]">Position Description</label></th>
		<td><textarea name="newOfficers[{$i}][positionDescription]" rows="3" cols="50" id="newOfficers[{$i}][positionDescription]" class="large-text"></textarea>
			<span class="description">Optional. Use this space to briefly describe what this officer does if you would like to make this information publicly viewable.</span>
		</td>
	</tr>
</table>
EOT;
		$i++; // Up our counter
	} while ($i <= $newOfficersCount);
	
	$addButton = ($newOfficersCount <= 1) ? 'Add Officer' : 'Add Officers';
	
	// Close it out and echo it
	$outputAddForm .= <<<EOT
<p class="submit">
<input type="submit" class="button" name="viewForm" value="Cancel" />&nbsp;&nbsp;<input type="submit" class="button-primary" name="officerAdd_submit" value="{$addButton}" />
</p>
</form>
<br />
</div>
EOT;
	echo $outputAddForm;
}

/**
 * The Add form was submitted, so we need to insert some new officers
 */
function officersDirInsert()
{
	global $wpdb;
	$i = 0; // Basic counter for grammar
	
	// Loop through each submission and submit it to the database
	foreach ($_POST['newOfficers'] as $officer)
	{
		// Are we missing anything?
		if (empty($officer['positionShortname']) && empty($officer['positionShortname']) && empty($officer['positionOfficer']))
		{
			continue;
		}
	
		$wpdb->query($wpdb->prepare(
		"INSERT INTO {$wpdb->prefix}officers (positionShortname, positionName, positionOfficer, positionCoOfficer, positionEmail, positionDescription, positionType, positionOrder)
			VALUES (%s, %s, %s, %s, %s, %s, %d, %d)",
			$officer['positionShortname'], $officer['positionName'], $officer['positionOfficer'], $officer['positionCoOfficer'], $officer['positionEmail'], $officer['positionDescription'], $officer['positionType'], $officer['positionOrder']));
		
		$i++;
	}
	
	$officerPlural = ($i == 1) ? 'officer was' : 'officers were';
	
	// All done, let's output our main list with a success message
	officersDirList("The $officerPlural successfully added to the directory.");
}

/**
 * Display our page for editing the officers, with fields for all of the officers shown in $officersSelectArray
 */
function officersDirEdit($officersSelectArray)
{
	global $wpdb;
	// Should have already done this, but just in case we didn't...
	include_once(dirname(__FILE__) . '/class.officersdir.php');
	
	// We need an array of officers, or else we'll just go back to the list
	if (!is_array($officersSelectArray) || empty($officersSelectArray))
	{
		officersDirList();
		return;
	}
	
	// Just in case, since it is user data, let's escape the shortnames provided
	array_walk($officersSelectArray, array($wpdb, 'escape'));
	
	// Get into our OfficersDir class
	$officersDir = new OfficersDir();
	$officersDir->selectOfficers(array(), $officersSelectArray); // Selecting only the officers we asked for
	$editForm = $officersDir->displayOfficersEdit();
	
	// Now output the entire page + form
	$outputEditForm = <<<EOT
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Edit Selected Officers</h2>
<form method="post" action="tools.php?page=officers-directory/officers_directory.php">
{$editForm}
<p class="submit">
<input type="submit" name="viewForm" class="button" value="Cancel" />&nbsp;&nbsp;<input type="submit" name="officerEdit_submit" class="button-primary" value="Save Changes" />
</p>
</form>
<br />
</div>
EOT;
	echo $outputEditForm;
}

/**
 * Our edit form was submitted, so we need to submit some updates
 *
 * Since the user got to pick which officers to edit, let's just presume that all of them should be updated
 */
function officersDirUpdate()
{
	global $wpdb;
	$updateShortnames = array();
	
	// Let's iterate through our array of submitted shortnames and submit the update to the database
	foreach ($_POST['updateOfficers'] as $officer)
	{
		$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}officers SET positionShortname = %s, positionName = %s, positionOfficer = %s, positionCoOfficer = %s, positionEmail = %s, positionDescription = %s, positionType = %d WHERE positionShortname = %s",
			$officer['positionShortname'], $officer['positionName'], $officer['positionOfficer'], $officer['positionCoOfficer'], $officer['positionEmail'], $officer['positionDescription'], $officer['positionType'], $officer['oldShortname']));
	}
	
	// All done, let's output our main list with a success message
	officersDirList('The selected officers were updated successfully.');
}

/**
 * This means that we've been asked to delete the officers, but we want to confirm it with them first
 */
function officersDirDeleteAsk($officersSelectArray)
{
	// We need an array of officers, or else we'll just go back to the list
	if (!is_array($officersSelectArray) || empty($officersSelectArray))
	{
		officersDirList();
		return;
	}

	global $wpdb;
	$selectItems	= '';
	$hiddenItems	= '';
	$positionsList	= '<ul>';
	
	// Go through each of the indicated shortnames and prep them for the database
	// We're also doing cleanup on the shortnames since we're actually going straight from user-submitted to database this time
	foreach ($_POST['officersEdit'] as $shortname)
	{
		$escShortname = $wpdb->escape($shortname);

		$selectItems .= (!empty($selectItems)) ? ' OR ' : ''; // Add an OR clause if needed
		$selectItems .= "positionShortname = '$escShortname'";
		
		$hiddenItems .= '<input type="hidden" name="officersDelete[]" value="' . $shortname . '" />';
	}
	
	// Not going to use our class for this, just select them quickly from the database
	$positions = $wpdb->get_col("SELECT positionName FROM {$wpdb->prefix}officers WHERE $selectItems ORDER BY positionType, positionOrder");
	
	// If we couldn't select any positions, we can't delete any, so let's just pretend this moment never happened
	if (empty($positions))
	{
		officersDirList();
		return;
	}
	
	// Iterate through each position to create an unordered list to (hopefully) shame the end user
	foreach ($positions as $position)
	{
		$positionsList .= "<li>$position</li>";
	}
	$positionsList .= '</ul>';
	
	// Finally let's output the page
	$officersDeletePage = <<<EOT
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Delete Officers from Directory</h2>
<p>You have asked to permanently delete the following officers from the directory:</p>
{$positionsList}
<p>Are you sure you want to do this? This action cannot be undone.</p>
<form method="post" action="tools.php?page=officers-directory/officers_directory.php">
{$hiddenItems}
<input type="submit" class="button" name="officerDeleteConfirm" value="Yes, Delete These Officers" />&nbsp;&nbsp;<input type="submit" class="button" name="officerForm" value="Cancel" />
</form>
<br /><br />
</div>
EOT;
	echo $officersDeletePage;
}

/**
 * We have confirmation from mission control to delete these officers
 */
function officersDirDeleteConfirmed()
{
	global $wpdb;
	$deleteItems = '';

	// We may have escaped the SQL SELECT query on the confirm page, but we passed along the original value
	// Therefore, let's give this one more try
	foreach ($_POST['officersDelete'] as $shortname)
	{
		$escShortname = $wpdb->escape($shortname);
		
		$deleteItems .= (!empty($deleteItems)) ? ' OR ' : ''; // Add a comma if needed
		$deleteItems .= "positionShortname = '$escShortname'";
	}
	
	// Run the query
	$wpdb->query("DELETE FROM {$wpdb->prefix}officers WHERE $deleteItems");
	
	// And go back to the main list with a confirm message
	officersDirList('The selected officers have been deleted successfully.');
}

/**
 * Re-Order the officers in our database
 */
function officersDirReorder()
{
	global $wpdb;
	$reorderArray = array();
	
	// First let's select what the current position orders are from the database
	$officers		= $wpdb->get_results("SELECT positionShortname, positionOrder FROM {$wpdb->prefix}officers");
	$officersRev	= array_reverse($officers); // debug
	
	$reorderArray	= array_map('intval', (array) $_POST['officerReorder']);
	
	// Now loop through each selected officer
	foreach ($officers as $officer)
	{
		// If the submitted order number differs from the database, submit an update query
		if ($officer->positionOrder != $reorderArray[$officer->positionShortname])
		{
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}officers SET positionOrder = %d WHERE positionShortname = %s", $reorderArray[$officer->positionShortname], $officer->positionShortname));
		}
	}
	
	// Now that that's done, go back to the main list with a confirm message
	officersDirList('The display order of officers has been updated successfully.');
}

/**
 * Adding a brand new position type to our collection
 *
 * @param string $newTypeName The name of the new Position Type to be inserted to the database
 */
function officersDirAddType($newTypeName)
{
	global $wpdb;
	
	// First we desire to know what the order number of our last position type is, so we can one-up it
	$highTypeOrder	= $wpdb->get_var("SELECT positionTypeOrder FROM {$wpdb->prefix}officers_types ORDER BY positionTypeOrder DESC LIMIT 1");
	$newTypeOrder	= (!$highTypeOrder) ? 1 : $highTypeOrder + 1;
	
	// Now we can insert our new type
	$wpdb->query($wpdb->prepare(
	"INSERT INTO {$wpdb->prefix}officers_types (positionTypeName, positionTypeOrder)
		VALUES (%s, %d)",
		$newTypeName, $newTypeOrder));
	
	// All done, let's output our main list with a success message
	officersDirList('The new position type was successfully added to the directory.');
}

/**
 * Updating the name(s) of one or more of our position types
 *
 * @param array $newTypesArray Array of new position type names with $id => $name
 */
function officersDirUpdateType($newTypesArray)
{
	global $wpdb;
	
	// Please sir, let it be an array, otherwise I'll go hungry
	if (!is_array($newTypesArray) || empty($newTypesArray))
	{
		officersDirList();
		return;
	}
	
	// Iterate through our array to identify positions to update
	foreach ($newTypesArray as $id => $type)
	{
		$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}officers_types SET positionTypeName = %s WHERE positionTypeID = %d",
			$type, $id));
	}
	
	// All done, let's output our main list with a success message
	officersDirList('The position types have been updated successfully.');
}

/**
 * We're going to be re-ordering our position types here
 * Essentially a wrapper for $officertypes->reorderTypes()
 *
 * @param int $id The database's ID number for the position type to be changed
 * @param string $move Can be either 'up or 'down' -- 'up' reduces the type's order number; 'down' increases it
 */
function officersDirReorderTypes($id, $move)
{
	// Instantiate our OfficersTypes class and pass the responsibilities over to it
	include_once(dirname(__FILE__) . '/class.officerstypes.php');
	$officersTypes = new OfficersTypes();
	$officersTypes->selectPositionTypes();
	$officersTypes->reorderTypes($id, $move);
	
	// Finally, go back to our main list; no success message for something this trivial (so to speak)
	officersDirList();
}

/**
 * Ask if we're going to delete a position type, and find out what we'll do with the officers
 *
 * @param int $id The database's ID number for the position type to be deleted
 * @param string $errMessage Specify an error message for display (optional)
 */
function officersDirDeleteTypeAsk($id, $errMessage = '')
{
	// Instantiate our OfficersTypes class
	include_once(dirname(__FILE__) . '/class.officerstypes.php');
	$officersTypes = new OfficersTypes();
	$officersTypes->selectPositionTypes();
	
	// We are required to have at least one default position type; if this is the only one, bail
	if (sizeof($officersTypes->typesArray) == 1)
	{
		officersDirList('You are required to have at least one defined position type, so you cannot delete this one. Try re-naming it or add a new one.');
		return;
	}
	
	// Find out what our requested position type is
	$delType		= $officersTypes->typeName($id);
	
	// Get a drop-down menu of all types except for this one
	$delTypeMenu	= $officersTypes->displayDropdown('delTypeTransfer', 0, $id);
	
	// Pass along the ID that we're deleting via a hidden field
	$hiddenItems	= '<input type="hidden" name="delTypeID" value="' . $id . '" />';
	
	// Do we have any officers in this position type? Instantiate our officers class to find out
	include_once(dirname(__FILE__) . '/class.officersdir.php');
	$officers = new OfficersDir();
	$officers->selectOfficers(array($id));
	
	// If we have no officers in this position type, then we don't need to ask what to do with them
	if (empty($officers->officersArray))
	{
		$hiddenItems .= '<input type="hidden" name="delAction" value="deleteall" />';
		$delAskForm = '';
	}
	
	// We do have officers in this position type, so we need to know whether to delete them or transfer them
	else
	{
		$officersArraySize	= sizeof($officers->officersArray);
		$officersAskLang	= ($officersArraySize == 1) ? 'There is currently one officer listed under this position type. Would you like to delete it or transfer it to another type?' : "There are $officersArraySize officers listed under this position type. Would you like to delete them or transfer them to another type?";
		$delAskForm			= <<<EOT
<p>{$officersAskLang}</p>
<p><input type="radio" name="delAction" value="deleteall" />&nbsp;Delete all officers with this position type.<br />
<input type="radio" name="delAction" value="transfer" />&nbsp;Transfer officers to this position type: {$delTypeMenu}</p>
EOT;
	}
	
	// Do we have an error message to display?
	$displayMessage = (!empty($errMessage)) ? "<div id=\"message\" class=\"updated fade\"><p><strong>$errMessage</strong></p></div>" : '';
	
	// Finally let's output the page
	$delTypePage = <<<EOT
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Delete Position Type from Directory</h2>
{$displayMessage}
<p>You have asked to permanently delete the <strong>{$delType}</strong> position type from the directory. Are you sure you want to do this? This action cannot be undone.</p>
<form method="post" action="tools.php?page=officers-directory/officers_directory.php">
{$delAskForm}
{$hiddenItems}
<input type="submit" class="button" name="delTypeConfirm" value="Yes, Delete This Position Type" />&nbsp;&nbsp;<input type="submit" class="button" name="officerForm" value="Cancel" />
</form>
<br /><br />
</div>
EOT;
	echo $delTypePage;
}

/**
 * We have confirmed that we want to delete a position type, so let's delete it
 *
 * @param int $id The database's ID number for the position type to be deleted
 * @param int $transferID The ID number for the position type to transfer officers to; if blank, officers will be deleted
 */
function officersDirDeleteTypeConfirmed($id, $transferID = 0)
{
	global $wpdb;
	
	// For starters, delete this position type from the wp_officers_types table
	$wpdb->query("DELETE FROM {$wpdb->prefix}officers_types WHERE positionTypeID = $id");
	
	// If a transfer ID is specified, let's change all the selected officers to that new ID
	if (!empty($transferID))
	{
		$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}officers SET positionType = %d WHERE positionType = %d",
			$transferID, $id));
	}
	// No transfer ID specified, so delete all of the officers with this ID
	else
	{
		$wpdb->query("DELETE FROM {$wpdb->prefix}officers WHERE positionType = $id");
	}
	
	// Now that we have deleted a type, our orders are going to get all messed up
	// Let's instantiate our OfficersTypes class so we can get a head-count of what's left
	include_once(dirname(__FILE__) . '/class.officerstypes.php');
	$officersTypes = new OfficersTypes();
	$officersTypes->selectPositionTypes();
	
	// Now we're going to loop through each of our selected types and give them a new order number
	$i = 1;
	foreach ($officersTypes->typesArray as $typeID => $typeName)
	{
		$wpdb->query($wpdb->prepare(
			"UPDATE {$wpdb->prefix}officers_types SET positionTypeOrder = %d WHERE positionTypeID = %d",
			$i, $typeID));
		
		$i++;
	}
	
	// All done, let's output our main list with a success message
	officersDirList('The selected position type was deleted successfully.');
}

/**
 * Just display our info about the shortcodes
 */
function officersDirShortcodes()
{
	include_once(dirname(__FILE__) . '/officersdir_help.php');
	$shortcodesHelp = officersDirHelp_Shortcodes();

	$shortcodesHelpPage = <<<EOT
<div class="wrap">
<div id="icon-tools" class="icon32"><br /></div>
<h2>Using the Officers Directory Shortcodes</h2>
<p><a href="./../wp-admin/tools.php?page=officers-directory/officers_directory.php">&laquo; Back to the Officers Directory</a></p>
{$shortcodesHelp}
<br />
</div>
EOT;
	echo $shortcodesHelpPage;
}
