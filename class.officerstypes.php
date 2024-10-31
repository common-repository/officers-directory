<?php
/**
 * Officers Directory Position Types Class
 *
 * This class provides methods for managing the position types used by the Officers Directory
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
 
class OfficersTypes
{
	public $table; // Contains the database table name for this particular WP site
	
	// If we're selecting position types, they will wind up in here
	public $typesArray	= array();
	public $ordersArray	= array();
	
	// Get the function ready to go, mainly by figuring out where our database connection is
	// A custom blog ID can be provided, otherwise we assume the current one
	public function __construct($blogID = null)
	{
		global $wpdb;
		$this->table = $wpdb->get_blog_prefix($blogID) . 'officers_types';
	}
	
	/**
	 * Use this function to select officers from $this->table
	 * Selects all officers and information in database by default
	 *
	 * In the instance that multiple different groupings need to be displayed on the same page, this function can be used
	 * to override the previous selection, but this class should not be called multiple times on one page
	 *
	 * @param array $types The IDs position types of to select; an empty array selects all types
	 */
	public function selectPositionTypes($types = array())
	{
		global $wpdb;
		$whereClause = '';

		// If we have an array of types, get them into a WHERE clause
		if (!empty($types))
		{
			$whereCounter = false;
			foreach ($types as $type)
			{
				$whereWord = (!$whereCounter) ? 'WHERE ' : 'OR '; // Use WHERE for our first one, and add others with OR
				$whereClause .= "$whereWord positionTypeID = $type";
				
				$whereCounter = true; // Up the counter if not done already
			}
		}
		// None of the WHERE conditions, so nothing to see here
		else
		{
			$whereClause = '';
		}
		
		// Select the officers from the database
		$typesSelection = $wpdb->get_results("SELECT positionTypeID, positionTypeName, positionTypeOrder FROM $this->table $whereClause ORDER BY positionTypeOrder ASC");
		
		// Add each of our types into $this->types_array
		foreach ($typesSelection as &$type)
		{
			$this->typesArray[$type->positionTypeID]	= stripslashes($type->positionTypeName);
			$this->ordersArray[$type->positionTypeID]	= $type->positionTypeOrder;
		}
	}
	
	/**
	 * Returns the name of a position type when provided with the ID
	 * This method is preferred over directly accessing the $this->types_array array,
	 * in case the format of the arrays gets changed in future versions
	 *
	 * @param int $id The position type ID, as assigned by the database
	 */
	public function typeName($id)
	{
		return $this->typesArray[$id];
	}
	
	/**
	 * Returns the ID of a position type when provided with the name
	 *
	 * @param string $name The position type name
	 */
	public function typeID($name)
	{
		$flippedArray = array_flip($this->typesArray);
		
		// Return false if the requested name doesn't exist
		if (isset($flippedArray[$name]))
		{
			return $flippedArray[$name];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Prepares the officers to be displayed in a drop-down
	 *
	 * @param string $selectName The name of the <select> element used in the drop-down
	 * @param int $defaultPos The ID of the position type to be pre-selected, if applicable
	 * @param int $skip If specified, skips this position type from the list
	 */
	public function displayDropdown($selectName, $defaultPos = 0, $skip = 0)
	{
		// Initialize our dropdown and include our default option
		$dropdown = <<<EOT
<select name="{$selectName}">
EOT;

		// Loop through each type of position that we have to show
		foreach ($this->typesArray as $id => $type)
		{
			// Skip this one if we're asked to
			if ($skip == $id)
			{
				continue;
			}
			
			// Should this one be selected by default?
			$selected = ($defaultPos == $id) ? ' selected="selected"' : '';
			
			// Now add this position to the drop-down
			$dropdown .= <<<EOT
	<option value="{$id}"{$selected}>{$type}</option>
EOT;
		}
		$dropdown .= '</select>';
		
		return $dropdown;
	}
	
	/**
	 * This is the way we re-order our types so early in the morning
	 *
	 * @param int $id The database's ID number for the position type to be changed
	 * @param string $move Can be either 'up or 'down' -- 'up' reduces the type's order number; 'down' increases it
	 */
	public function reorderTypes($id, $move)
	{
		global $wpdb;
		
		// Identify the type we want to change by name & current order
		$changeTypeName		= $this->typesArray[$id];
		$changeTypeOrder	= $this->ordersArray[$id];
		
		// Depending on our $move we will determine what the new order for this type should be
		switch ($move)
		{
			case 'up':
				// First off, we can't do this if we're editing the first item
				if ($changeTypeOrder == 1)
				{
					return;
				}
				$alterTypeOrder = $changeTypeOrder - 1;
			break;
			
			case 'down':
				// On this one, we can't do this if we're editing the last item
				if ($changeTypeOrder == sizeof($this->ordersArray))
				{
					return;
				}
				$alterTypeOrder = $changeTypeOrder + 1;
			break;
			
			// We don't know what's going on here, so just bail
			default:
			return;
		}

		// Next, we're going to affect another position type if we do this, and we don't know which it is
		// Therefore we'll blindly do that one first
		$wpdb->query("UPDATE {$this->table} SET positionTypeOrder = $changeTypeOrder WHERE positionTypeOrder = $alterTypeOrder");

		// That done, we can now change the order of our requested type
		$wpdb->query("UPDATE {$this->table} SET positionTypeOrder = $alterTypeOrder WHERE positionTypeName = '$changeTypeName'");
	}
}
