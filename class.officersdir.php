<?php
/**
 * Officers Directory Class
 *
 * This class accepts data for officers and processes it, either by updating it or displaying it in a certain fashion
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
 
class OfficersDir
{
	public $officersTable; // Contains the database table name for this particular WP site
	
	// If we're selecting officer data, it will wind up in here
	public $officersArray = array();
	
	static public $jsOutputted = false; // Controller to make sure the collapsing JS isn't outputted twice on one page
	
	// Get the class ready to go, mainly by figuring out where our database connection is
	// A custom blog ID can be provided, otherwise we assume the current one
	public function __construct($blogID = null)
	{
		global $wpdb;
		$this->officersTable = $wpdb->get_blog_prefix($blogID) . 'officers';
	}
	
	/**
	 * Use this function to select officers from $this->officersTable
	 * Selects all officers and information in database by default
	 *
	 * In the instance that multiple different groupings need to be displayed on the same page, this function can be used
	 * to override the previous selection, but this class should not be called multiple times on one page
	 *
	 * @param array|string $types The NAMES of position types of officers to select; an empty array selects all types
	 * @param array|string $shortnames If specifed, will only select the specific shortnames listed (overrides $type)
	 */
	public function selectOfficers($types = array(), $shortnames = array())
	{
		global $wpdb;
		$whereClause = '';
		
		// If we have an array of shortnames, get them down into a WHERE clause
		if (!empty($shortnames))
		{
			$whereCounter = false;
			foreach ($shortnames as $shortname)
			{
				$whereWord = (!$whereCounter) ? 'WHERE ' : 'OR '; // Use WHERE for our first one, and add others with OR
				$whereClause .= "$whereWord positionShortname = '$shortname'";
				
				$whereCounter = true; // Up the counter if not done already
			}
		}
		// If we instead have an array of types, same deal
		else if (!empty($types))
		{
			// Instantiate our OfficersTypes class so we can convert these type names to IDs
			include_once(dirname(__FILE__) . '/class.officerstypes.php');
			$officersTypes = new OfficersTypes();
			$officersTypes->selectPositionTypes();
			
			$whereCounter = false;
			foreach ($types as $type)
			{
				$typeID = $officersTypes->typeID($type);
				// If the type name wasn't recognized as valid, we'll skip this one
				if (empty($typeID))
				{
					continue;
				}
				
				$whereWord = (!$whereCounter) ? 'WHERE ' : ' OR '; // Use WHERE for our first one, and add others with OR
				$whereClause .= "$whereWord positionType = $typeID";
				
				$whereCounter = true; // Up the counter if not done already
			}
		}
		// Select the officers from the database
		$officersSelection = $wpdb->get_results("SELECT * FROM $this->officersTable $whereClause ORDER BY positionType, positionOrder ASC");
		
		// For each different position type, I want this to go into a branch of $this->officersArray array to separate the types
		// This means the syntax will be $this->officersArray[positionType][int]->columnName isn't that fun?
		foreach ($officersSelection as &$officer)
		{
			// Please stripslashes on a couple of things
			$officer->positionName			= stripslashes($officer->positionName);
			$officer->positionOfficer		= stripslashes($officer->positionOfficer);
			$officer->positionCoOfficer		= stripslashes($officer->positionCoOfficer);
			$officer->positionEmail			= stripslashes($officer->positionEmail);
			$officer->positionDescription	= stripslashes($officer->positionDescription);
			
			// Now we can deposit it in the class array
			$this->officersArray[$officer->positionType][] = $officer;
		}
	}
	
	/**
	 * Displays the officers table for public view
	 *
	 * If there is multiple position types selected, this will merge them into a single table with dividing headers;
	 * those wishing separate tables should instead run $this->selectOfficers a second time
	 *
	 * @param string $showEmailLinks If specified, contains a link to the contact form -- E-mail links won't display if null
	 * @param bool $showDescriptions If true, will show collapsible position descriptions
	 * @param string $positionTitle Will display in the table as header for the "Position" column
	 * @param string $officerTitle Will display in the table as header for the "Officer" column
	 */
	public function displayOfficersTable($showEmailLinks = '', $showDescriptions = false, $positionTitle = 'Position', $officerTitle = 'Officer')
	{
		// Were we asked to show descriptions? Then let's make sure we have our JS out if not already
		if ($showDescriptions && !self::$jsOutputted)
		{
			$displayTable = <<<EOT
<script language="javascript" type="text/javascript">
<!--

function handleClick(id) {
	var obj = "";	

		// Check browser compatibility
		if(document.getElementById)
			obj = document.getElementById(id);
		else if(document.all)
			obj = document.all[id];
		else if(document.layers)
			obj = document.layers[id];
		else
			return 1;

		if (!obj) {
			return 1;
		}
		else if (obj.style) 
		{			
			obj.style.display = ( obj.style.display != "none" ) ? "none" : "";
		}
		else 
		{ 
			obj.visibility = "show"; 
		}
}
//-->
</script>
EOT;
		}
		else
		{
			$displayTable = ''; // Ensuring this gets initialized
		}

		// If we have no e-mail form specified, hide the e-mail links
		$email_th = (!empty($showEmailLinks)) ? '<th style="font-size: 16px; width: 80px">E-mail:</th>' : '';

		// Initialize our table header
		$displayTable .= <<<EOT
<table width="100%" id="officersdirtable">
	<tr>
		<th style="font-size: 16px">{$positionTitle}:</th>
		<th style="font-size: 16px">{$officerTitle}:</th>
		{$email_th}
	</tr>
EOT;

		// Instantiate our OfficersTypes class for later use
		include_once(dirname(__FILE__) . '/class.officerstypes.php');
		$officersTypes = new OfficersTypes();
		$officersTypes->selectPositionTypes();

		// Loop through each type of position that we have to show
		$positionTypes		= array_keys($this->officersArray);
		$positionTypesSize	= sizeof($positionTypes);
		foreach ($positionTypes as $positionType)
		{
			// We have an ID number, we need a name
			$typeName = $officersTypes->typeName($positionType);
			
			// Output a row indicating the posiiton type, UNLESS there's only one type here
			if ($positionTypesSize > 1)
			{
				$displayTable .= <<<EOT
	<tr>
		<td colspan="3" style="font-size: 16px"><strong>{$typeName}:</strong></td>
	</tr>
EOT;
			}
			
			// Now we can loop through the officers recognized under this position type
			foreach ($this->officersArray[$positionType] as $officer)
			{
				// If the position type for this officer is different 
				// If we were asked to show descriptions, let's prepare the HTML for that
				$descLink	= ($showDescriptions && !empty($officer->positionDescription)) ? '&nbsp;<a href="javascript:handleClick(\'description_' . $officer->positionShortname . '\');"><img src="' . get_bloginfo('url') . '/wp-content/plugins/officers-directory/images/down_triangle.gif" style="border:0" /></a>' : '';
				$descText	= ($showDescriptions && !empty($officer->positionDescription)) ? '	<tr id="description_' . $officer->positionShortname . '" style="display: none">
		<td style="font-size: 12px" colspan="3">' . $officer->positionDescription . '</td>
	</tr>' : '';

				// If an officer is unnamed, the position is vacant
				if (empty($officer->positionOfficer))
				{
					// If no e-mail links, there is no need for a colspan
					$email_colspan = (!empty($showEmailLinks)) ? ' colspan="2"' : '';
					$displayTable .= <<<EOT
	<tr>
		<td style="font-size: 16px;">{$officer->positionName}{$descLink}</td>
		<td{$email_colspan} style="font-size: 16px">VACANT</td>
	</tr>
	{$descText}
EOT;
				}
				else
				{
					// Is there a co-officer?
					$coOfficer = (!empty($officer->positionCoOfficer)) ? '<br />' . $officer->positionCoOfficer : '';
					
					// If no e-mail links, hide the td with the link
					if (!empty($showEmailLinks))
					{
						// To ?officer= or &officer= that is the question
						$officer_GetVar	= (strpos($showEmailLinks, '?') === false) ? '?officer=' : '&officer=';
						$email_td		= '<td style="font-size: 16px"><a href="' . $showEmailLinks . $officer_GetVar . $officer->positionShortname . '">E-mail</a></td>';
					}
					else
					{
						$email_td = '';
					}
					
					$displayTable .= <<<EOT
	<tr>
		<td style="font-size: 16px;">{$officer->positionName}{$descLink}</td>
		<td style="font-size: 16px">{$officer->positionOfficer}{$coOfficer}</td>
		{$email_td}
	</tr>
	{$descText}
EOT;
				}
			}
		}
		$displayTable .= '</table><br />';
		
		return $displayTable;
	}
	
	/**
	 * Prepares the officers to be displayed in a drop-down
	 *
	 * @param string $selectName The name of the <select> element used in the drop-down
	 * @param string $defaultPos The shortname of the position to be pre-selected, if applicable
	 */
	public function displayOfficersDropdown($selectName, $defaultPos = '')
	{
		// Initialize our dropdown and include our default option
		$dropdown = <<<EOT
<select name="{$selectName}">
	<option value="refresh">Select a recipient:</option>
EOT;

		// Instantiate our OfficersTypes class for later use
		include_once(dirname(__FILE__) . '/class.officerstypes.php');
		$officersTypes = new OfficersTypes();
		$officersTypes->selectPositionTypes();

		// Loop through each type of position that we have to show
		$positionTypes = array_keys($this->officersArray);
		$positionTypesSize = sizeof($positionTypes);
		foreach ($positionTypes as $positionType)
		{
			// We have an ID number, we need a name
			$typeName = $officersTypes->typeName($positionType);
			
			// Output a disabled option as a header for this position type, unless there's only one
			if ($positionTypesSize > 1)
			{
				$dropdown .= <<<EOT
	<option disabled=\"disabled\">--- {$typeName} ---</option>"
EOT;
			}
			
			// Now we can loop through the officers recognized under this position type
			foreach ($this->officersArray[$positionType] as $officer)
			{
				$positionList = (empty($officer->positionOfficer)) ? $officer->positionName : $officer->positionName . ' - ' . $officer->positionOfficer; // If there's no specified officer, the position is vacant
				$positionList .= (!empty($officer->positionCoOfficer)) ? ' and ' . $officer->positionCoOfficer : ''; // Add the co-officer if there is one
				
				// Should this one be selected by default
				$selected = ($defaultPos == $officer->positionShortname) ? ' selected="selected"' : '';
				
				// Now add this position to the drop-down
				$dropdown .= <<<EOT
	<option value="{$officer->positionShortname}"{$selected}>{$positionList}</option>
EOT;
			}
		}
		$dropdown .= '</select>';
		
		return $dropdown;
	}
	
	/**
	 * Prepares the officers to be displayed in the WordPress admin panel
	 *
	 * The output for this function should be encapsulated inside <form></form>
	 */
	public function displayOfficersAdmin()
	{
		// Our columns for the table, which appear twice
		$tableColumnsList = <<<EOT
			<th scope="col" class="manage-column check-column"><input type="checkbox" /></th>
			<th scope="col" class="manage-column">Position Name</th>
			<th scope="col" class="manage-column">Officer</th>
			<th scope="col" class="manage-column">E-mail</th>
			<th scope="col" class="manage-column">Order</th>
EOT;

		// Our handleClick JavaScript for the admin
		$handleClickJS = <<<EOT
<script language="javascript" type="text/javascript">
<!--

function handleClick(id) {
	var obj = "";	

		// Check browser compatibility
		if(document.getElementById)
			obj = document.getElementById(id);
		else if(document.all)
			obj = document.all[id];
		else if(document.layers)
			obj = document.layers[id];
		else
			return 1;

		if (!obj) {
			return 1;
		}
		else if (obj.style) 
		{			
			obj.style.display = ( obj.style.display != "none" ) ? "none" : "";
		}
		else 
		{ 
			obj.visibility = "show"; 
		}
}
//-->
</script>
EOT;

		// Now let's initialize our table
		$officersListPage = <<<EOT
{$handleClickJS}
<table class="widefat">
	<thead>
		<tr>
{$tableColumnsList}
		</tr>
	</thead>
	<tfoot>
		<tr>
{$tableColumnsList}
		</tr>
	</tfoot>
	<tbody id="the-list">
EOT;
		
		$i = 1; // This counter allows us to use the alternating tr classes requested by the admin
		$p = 1; // Counter for the number of position types we're working with
		
		// Instantiate our OfficersTypes class for later use
		include_once(dirname(__FILE__) . '/class.officerstypes.php');
		$officersTypes = new OfficersTypes();
		$officersTypes->selectPositionTypes();
		
		// Loop through each type of position that we have to show
		// Unlike other places, here we want to show all position types
		$positionTypes = array_keys($officersTypes->typesArray);
		$positionTypesSize = sizeof($positionTypes);
		foreach ($positionTypes as $positionType)
		{			
			// Let's start by showing a table row for this position type
			$bgClass = ($i % 2) ? ' class="alternate"' : ''; // If this is an odd row, use class="alternate" for a darker bg
			
			// We have an ID number, we need a name
			$typeName = $officersTypes->typeName($positionType);
			
			// I Can Has Editing Links?
			$moveUpLink		= '<a href="tools.php?page=officers-directory/officers_directory.php&type=' . $positionType . '&move=up"><img src="' . get_bloginfo('url') . '/wp-content/plugins/officers-directory/images/arrow-up.png" style="border:0" /></a>';
			$moveDownLink	= '<a href="tools.php?page=officers-directory/officers_directory.php&type=' . $positionType . '&move=down"><img src="' . get_bloginfo('url') . '/wp-content/plugins/officers-directory/images/arrow-down.png" style="border:0" /></a>';
			$editLink		= '<a href="javascript:handleClick(\'edittype_' . $positionType . '\');"><img src="' . get_bloginfo('url') . '/wp-content/plugins/officers-directory/images/icon-edit.png" style="border:0" /></a>';
			$deleteLink		= '<a href="tools.php?page=officers-directory/officers_directory.php&type=' . $positionType . '&delete=true"><img src="' . get_bloginfo('url') . '/wp-content/plugins/officers-directory/images/icon-del.png" style="border:0" /></a>';
			
			// If we have only one position type, the Edit link is all we need
			if ($positionTypesSize == 1)
			{
				$moveUpDown = $editLink;
			}
			// If this is the first position type, we can only Move Down; if this is the last one, we can only Move Up
			else if ($p == 1)
			{
				$moveUpDown = $moveDownLink . '&nbsp;' . $editLink . '&nbsp;' . $deleteLink;
			}
			else if ($p == $positionTypesSize)
			{
				$moveUpDown = $moveUpLink . '&nbsp;' . $editLink . '&nbsp;' . $deleteLink;
			}
			else
			{
				$moveUpDown = $moveUpLink . '&nbsp;' . $moveDownLink . '&nbsp;' . $editLink . '&nbsp;' . $deleteLink;
			}
			
			// Now we can spit out a header-ish row with this type
			$officersListPage .= <<<EOT
	<tr{$bgClass}>
		<td colspan="2" style="font-size: 16px"><strong>{$typeName}</strong></td>
		<td colspan="2" style="font-size: 16px"><div style="display:none" id="edittype_{$positionType}"><input name="updateType[{$positionType}]" type="text" size="30" maxlength="255" id="updateType[{$positionType}]" value="{$typeName}" />&nbsp;<input type="submit" class="button" name="officerAction" value="Re-Name" /></div></td>
		<td>{$moveUpDown}</td>
	</tr>
EOT;
			$i++; // Up our counter

			// If we don't have any officers under this position type
			if (empty($this->officersArray[$positionType]))
			{
				$bgClass = ($i % 2) ? ' class="alternate"' : ''; // If this is an odd row, use class="alternate" for a darker bg
				$officersListPage .= "<tr{$bgClass}><td colspan=\"6\">There are no officers listed under this position type.</td></tr>";
			}
			// Otherwise, let's loop through each officer under this position type
			else
			{
				foreach ($this->officersArray[$positionType] as $officer)
				{
					$bgClass 	= ($i % 2) ? ' class="alternate"' : ''; // If this is an odd row, use class="alternate" for darker bg
					$coOfficer	= (!empty($officer->positionCoOfficer)) ? ' and ' . $officer->positionCoOfficer : '';
				
					// Now we'll go ahead and output this officer for the list
					$officersListPage .= <<<EOT
		<tr{$bgClass}>
			<th class="check-column"><input type="checkbox" name="officersEdit[]" value="{$officer->positionShortname}" /></th>
			<td><a class="row-title" href="tools.php?page=officers-directory/officers_directory.php&officer={$officer->positionShortname}" title="Edit info for {$officer->positionName}">{$officer->positionName}</a><br /><div style="font-size:10px">({$officer->positionShortname})</div></td>
			<td>{$officer->positionOfficer}{$coOfficer}</td>
			<td>{$officer->positionEmail}</td>
			<td><input type="text" size="2" maxlength="2" name="officerReorder[{$officer->positionShortname}]" value="{$officer->positionOrder}" /></td>
		</tr>
EOT;
					$i++; // Up our counter
				}
			}
			$p++; // Up our other counter
		}
		
		$officersListPage .= <<<EOT
	</tbody>
</table>
EOT;
		return $officersListPage;
	}
	
	/**
	 * Prepares the officers to be displayed for the Edit pane of the WordPress admin panel
	 *
	 * The output for this function should be encapsulated in <form></form>
	 */
	public function displayOfficersEdit()
	{
		$officersEditForm = ''; // Just initializing, no starter content here
		
		// Instantiate our OfficerTypes class
		include_once(dirname(__FILE__) . '/class.officerstypes.php');
		$officersTypes = new OfficersTypes();
		$officersTypes->selectPositionTypes();
		
		// Loop through each type of position that we have to show
		$positionTypes = array_keys($this->officersArray);
		foreach ($positionTypes as $positionType)
		{
			// Loop through each officer under this position type
			foreach ($this->officersArray[$positionType] as $officer)
			{
				// Get our drop-down of position types
				$positionTypesMenu = $officersTypes->displayDropdown("updateOfficers[{$officer->positionShortname}][positionType]", $officer->positionType);
				
				// And go ahead and output a form table for this officer
				$officersEditForm .= <<<EOT
<h3>Edit Position - {$officer->positionName}</h3>
<input type="hidden" name="updateOfficers[{$officer->positionShortname}][oldShortname]" value="{$officer->positionShortname}" />
<table class="form-table">
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionName]">Position Name</label></th>
		<td><input name="updateOfficers[{$officer->positionShortname}][positionName]" type="text" id="updateOfficers[{$officer->positionShortname}][positionName]" value="{$officer->positionName}" class="regular-text" />
			<span class="description">The title of this officer, i.e. "President".</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionShortname]">Position Shortname</label></th>
		<td><input name="updateOfficers[{$officer->positionShortname}][positionShortname]" type="text" id="updateOfficers[{$officer->positionShortname}][positionShortname]" maxlength="100" value="{$officer->positionShortname}" class="regular-text" />
			<span class="description">Used to identify this position internally, and cannot duplicate another shortname. All lowercase, no spaces.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionOfficer]">Position Officer</label></th>
		<td><input name="updateOfficers[{$officer->positionShortname}][positionOfficer]" type="text" id="updateOfficers[{$officer->positionShortname}][positionOfficer]" value="{$officer->positionOfficer}" class="regular-text" />
			<span class="description">The name of the person filling this position. Leave blank if vacant.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionCoOfficer]">Position Co-Officer</label></th>
		<td><input name="updateOfficers[{$officer->positionShortname}][positionCoOfficer]" type="text" id="updateOfficers[{$officer->positionShortname}][positionCoOfficer]" value="{$officer->positionCoOfficer}" class="regular-text" />
			<span class="description">Only enter a second name here if there are two co-officers.</span>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionEmail]">Contact E-mail</label></th>
		<td><input name="updateOfficers[{$officer->positionShortname}][positionEmail]" type="text" id="updateOfficers[{$officer->positionShortname}][positionEmail]" value="{$officer->positionEmail}" class="regular-text" /></td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionType]">Position Type</label></th>
		<td>{$positionTypesMenu}</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label for="updateOfficers[{$officer->positionShortname}][positionDescription]">Position Description</label></th>
		<td><textarea name="updateOfficers[{$officer->positionShortname}][positionDescription]" rows="3" cols="50" id="updateOfficers[{$officer->positionShortname}][positionDescription]" class="large-text">{$officer->positionDescription}</textarea>
			<span class="description">Optional. Use this space to briefly describe what this officer does if you would like to make this information publicly viewable in your public-facing directory.</span>
		</td>
	</tr>
</table>
EOT;
			}
		}
		return $officersEditForm;
	}
}
