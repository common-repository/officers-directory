<?php
/**
 * Contains the contents of the contextual help displayed by the sliding panel in the WordPress admin
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

// Show this info on the main officers list
function officersDirHelp_List()
{
	$contextual_help = <<<EOT
<p>From this screen you can perform a number of actions to manage your officers:
<ul><li><strong>Add new officers to this list:</strong> Enter the number of new officers that you would like to add at once in the textbox above the table (maximum 99 at a time) and click Add.</li>
<li><strong>Edit the data for existing officers:</strong> You can edit an individual officer's data by simply clicking on the officer's Position Name. To edit multiple officers at once, check the boxes for each officer to edit, then click the Edit button.</li>
<li><strong>Permanently delete existing officers:</strong> Check the boxes for each officer to delete, then click the Delete button. You will asked to confirm that you want to do this.</li>
<li><strong>Re-Order the display of officers:</strong> Officers are grouped together by their Type. Within each group, the display of the officers is shown sequentially based on the order number. To re-order the officers, simply change the order numbers, and click the Re-Order Officers button. The table will reload in the new order so you can make further changes, if necessary.</li></ul></p>

<p>Position Types allow you to group related officer positions together. You must have at least one position type, but if you only have one type specified, its name will not be displayed publicly. The following options are available to manage your position types:
<ul><li><strong>Add a New Type:</strong> Enter the name for your new position type in the textfield above the table, and click "Add Position Type." The new type will be added at the bottom of the list.</li>
<li><strong>Re-Order the Types:</strong> Click on the up/down arrow icons to re-order the groups of officers associated with a position type.</li>
<li><strong>Re-Name a Type:</strong> Click on the E icon, which will display a textfield that you can use to re-name the position type.</li>
<li><strong>Delete a Type:</strong> Click on the X icon to delete the position type. You will be asked if you want to delete all of the officers belonging to that type or transfer them to a different position type.</li></ul></p>

<p>You can publicly display your officers directory in a table or in a contact form using WordPress shortcodes. <a href="./../wp-admin/tools.php?page=officers-directory/officers_directory.php&help=shortcode">More Information &raquo;</a></p>
EOT;
	return $contextual_help;
}

// Info on how to publicly display the officers using shortcodes
function officersDirHelp_Shortcodes()
{
	$contextual_help = <<<EOT
<p>To display a directory of your officers, use the <strong>[officers-table]</strong> shortcode in your post. You can also specify a number of attributes (by including them within the brackets) to customize the display of this form:</p>
<ul><li><strong>contactform="http://example.com/contact/"</strong> Insert the link to the page or post containing the contact form shortcode (see below) and the directory will display e-mail links for each officer that will pre-fill the contact form's "Send mail to" menu.</li>
<li><strong>descriptions="true"</strong> If you want to display descriptions for each position that specifies them in your directory, include this attribute in your shortcode. Descriptions use a collapsible effect to display to the end-user.</li>
<li><strong>types="Type 1|Type 2|etc."</strong> If you want to restrict the directory to only show certain types of officers, specify the name of each type here, separated by a pipe symbol (|).</li>
<li><strong>shortnames="officer1|officer2|etc."</strong> If you want to specify exactly which officers appear in the directory, specify the <em>shortname</em> of each officer here, separated by a pipe symbol (|). Shortnames are shown inside parentheses in the list below.</li>
<li><strong>positiontitle="Position"</strong> Change this if you want the header of the "Position" column to have a different title.</li>
<li><strong>officertitle="Officer"</strong> Change this if you want the header of the "Officer" column to have a different title.</li></ul>

<p>To display a contact form for your officers, use the <strong>[officers-contact]</strong> shortcode in your post. You can also specify a number of attributes (by including them within the brackets) to customize which officers are available to this form:</p>
<ul><li><strong>types="Type 1|Type 2|etc."</strong> If you want to restrict the contact form to only list certain types of officers, specify the name of each type here, separated by a pipe symbol (|).</li>
<li><strong>shortnames="officer1|officer2|etc."</strong> If you want to specify exacty which officers can be contacted, specify the <em>shortname</em> of each officer here, separated by a pipe symbol (|). Shortnames are shown inside parentheses in the list below.</li>
EOT;
	return $contextual_help;
}

// Show this info on the screen for adding new officers
function officersDirHelp_Add()
{
	$contextual_help = <<<EOT
<p>From this screen you can add new officers to your directory. The following options are requested for each officer:</p>
<ul><li><strong>Position Name:</strong> Enter the name or title of this officer, i.e. "President" or "General Manager."</li>
<li><strong>Position Shortname:</strong> Enter a "reduced" version of the position name, preferably using all lowercase and no spaces or non-alphanumeric characters. This is used to refer to this officer internally and is not displayed publicly.</li>
<li><strong>Position Officer:</strong> This is the name of the person holding this officer position. If this field is left blank, the position will be identified as "Vacant."</li>
<li><strong>Position Co-Officer:</strong> Use this field if two people share this position. This name will not be shown publicly if the first name is left blank.</li>
<li><strong>Contact E-mail:</strong> E-mails sent to this officer through the contact form will be sent to this e-mail address. This address will never be displayed publicly.</li>
<li><strong>Position Type:</strong> Enter the name of a position type to group related officers together. Yes, this is a little rudimentary now and will be improved soon.</li>
<li><strong>Position Order:</strong> Optional. You may enter a number to determine where in the list this officer will appear. If left blank, the officer will be listed to the end of their group, but you can easily change the order later.</li>
<li><strong>Position Description:</strong> Optional. If you want, you can show a description of what this officer does to your visitors.</li></ul>
EOT;
	return $contextual_help;
}

// Show this info on the screen for editing officer info
function officersDirHelp_Edit()
{
	$contextual_help = <<<EOT
<p>From this screen you can edit the options for officers to your directory. The following options are available for each officer:</p>
<ul><li><strong>Position Name:</strong> Enter the name or title of this officer, i.e. "President" or "General Manager."</li>
<li><strong>Position Shortname:</strong> Enter a "reduced" version of the position name, preferably using all lowercase and no spaces or non-alphanumeric characters. This is used to refer to this officer internally and is not displayed publicly.</li>
<li><strong>Position Officer:</strong> This is the name of the person holding this officer position. If this field is left blank, the position will be identified as "Vacant."</li>
<li><strong>Position Co-Officer:</strong> Use this field if two people share this position. This name will not be shown publicly if the first name is left blank.</li>
<li><strong>Contact E-mail:</strong> E-mails sent to this officer through the contact form will be sent to this e-mail address. This address will never be displayed publicly.</li>
<li><strong>Position Type:</strong> Enter the name of a position type to group related officers together. Yes, this is a little rudimentary now and will be improved soon.</li>
<li><strong>Position Description:</strong> Optional. If you want, you can show a description of what this officer does to your visitors.</li></ul>
EOT;
	return $contextual_help;
}

// Show this info on the screen for deleting officers
function officersDirHelp_Delete()
{
	$contextual_help = <<<EOT
<p>Please confirm whether or not you want to permanently delete the officers listed below from your directory. Remember that you are also able to edit the options for these officers, or re-order where they appear in the directory, if you choose not to delete them.</p>
EOT;
	return $contextual_help;
}

// Show this info on the screen for deleting a position type
function officersDirHelp_DeleteType()
{
	$contextual_help = <<<EOT
<p>Please confirm whether or not you want to permanently delete this position type from your directory. Remember that you are able to re-name the position type, or re-order where it appears in the directory, if you choose not to delete it.</p>
<p>If you currently have officers in the directory that belong to this position type, you will be asked whether you want to also permanently delete these officers, or if you want to transfer them to another position type.</p>
<p>Officers are required to belong to a position type, therefore you must always have at least one specified in your officers directory. (If there is only one type defined, its name will not be displayed publicly.)</p>
EOT;
	return $contextual_help;
}
