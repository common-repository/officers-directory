<?php
/*
Plugin Name: Officers Directory
Plugin URI: http://www.douglasbell.us/plugins/
Description: Allows for the creation and management of a directory of officers for your organization, including separate listings of multiple types of officers. Includes an integrated contact form with reCAPTCHA support.
Version: 1.2.0
Author: Douglas Bell
Author URI: http://www.douglasbell.us
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

/*--- PLUGIN INSTALLATION ---*/

// This will be run upon hitting the Officers Directory page in the admin

$officersDirVersion = '1.2.0';

function officersDirInstall()
{
	global $wpdb, $officersDirVersion;
	$tableName = $wpdb->prefix . 'officers';
	
	// Only worth doing this if we haven't been set up yet
	if (get_option('officersdir_version') == $officersDirVersion)
	{
		return;
	}
	
	// We don't already have our stuff installed, so let's do it now
	if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName)
	{
		// Add the wp_officers table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}officers (
  positionShortname varchar(255) NOT NULL DEFAULT '',
  positionName varchar(255) NOT NULL DEFAULT '',
  positionOfficer varchar(255) NOT NULL DEFAULT '',
  positionCoOfficer varchar(255) NOT NULL,
  positionEmail varchar(255) NOT NULL,
  positionDescription text,
  positionType int(11) NOT NULL,
  positionOrder tinyint(3) NOT NULL,
  PRIMARY KEY (positionShortname)
)";
		$wpdb->query($sql);
	}
	
	$tableName = $wpdb->prefix . 'officers_types';

	if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName)
	{
		// Add the wp_officers_types table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}officers_types (
	positionTypeID int(11) NOT NULL AUTO_INCREMENT,
	positionTypeName varchar(255) NOT NULL,
	positionTypeOrder tinyint(2) NOT NULL,
	PRIMARY KEY (positionTypeID)
)";
		$wpdb->query($sql);
		
		// Give us a default position type
		$wpdb->query("INSERT INTO {$wpdb->prefix}officers_types (positionTypeName, positionTypeOrder)
			VALUES ('Default', 1)");
	}
	
	// Check to see if we have to update anything from a previous version
	$currentVersion = get_option('officersdir_version', '0');
	if (version_compare($currentVersion, $officersDirVersion, '<'))
	{
		// Update to version 1.1.0 or later
		if (version_compare($currentVersion, '1.1.0', '<'))
		{			
			// See if we have a reCAPTCHA API key still available; the update process might have killed it
			// If not, we'll be able to add the option later
			@include_once(dirname(__FILE__) . '/apikeys_config.php');
			
			if ($officersRecaptchaPublicKey != 'paste here')
			{
				update_option('officersdir_recaptchapublic', $officersRecaptchaPublicKey);
			}
			if ($officersRecaptchaPrivateKey != 'paste here')
			{
				update_option('officersdir_recaptchaprivate', $officersRecaptchaPrivateKey);
			}
		} // End 1.1.0
		
		// Add code for future updates here as needed
	}

	update_option('officersdir_version', $officersDirVersion); // No camelcase here for historical reasons
}


/*--- PUBLIC-FACING SHORTCODES AND FORMS ---*/

// Include our friendly neighborhood files
include_once(dirname(__FILE__) . '/class.officerscontact.php');
include_once(dirname(__FILE__) . '/class.officersdir.php');
include_once(dirname(__FILE__) . '/class.officerstypes.php');

// Add shortcodes to WordPress for the display of the officers table & contact form
add_shortcode('officers-table', 'officersTableShortcode');
add_shortcode('officers-contact', 'contactFormShortcode');

/**
 * Displays the officers table using WordPress' Shortcode API
 */
function officersTableShortcode($atts, $content = null)
{
	// Get our shortcode attributes
	extract(shortcode_atts(array(
		'types' => '',
		'shortnames' => '',
		'descriptions' => 'false',
		'contactform' => '',
		'positiontitle' => 'Position',
		'officertitle' => 'Officer',
	), $atts));
	
	// If we have types or shortnames specified, explode them into an array
	$typesArray			= (!empty($types)) ? explode('|', $types) : array();
	$shortnamesArray	= (!empty($shortnames)) ? explode('|', $shortnames) : array();
	
	// If descriptions is the default, we'll assume it wasn't mentioned; if it was mentioned, assume they want them
	$showDescriptions	= ($descriptions == 'false') ? false : true;
	
	// Now initialize the class and get our table to display
	$officersDir = new OfficersDir();
	$officersDir->selectOfficers($typesArray, $shortnamesArray);
	return $officersDir->displayOfficersTable($contactform, $showDescriptions, $positiontitle, $officertitle);
}

/**
 * Displays the contact form using WordPress' Shortcode API
 * Processing the form is handled separately, but if the form is recognized as processed successfully,
 * display a success message in place of the contact form
 */
function contactFormShortcode($atts, $content = null)
{
	// Get our shortcode attributes
	extract(shortcode_atts(array(
		'types' => '',
		'shortnames' => ''
	), $atts));
	
	// If we have types or shortnames specified, explode them into an array
	$typesArray			= (!empty($types)) ? explode('|', $types) : array();
	$shortnamesArray	= (!empty($shortnames)) ? explode('|', $shortnames) : array();
	
	// We don't think there's any hidden fields
	$hiddenFields		= '';
	
	// Globalize our Contact form class in case it already exists, but we'll also check to see that it does exist
	global $officersContact;
	if ($officersContact instanceof OfficersContact)
	{
		// Was the e-mail sent? If so, let's replace the form with a success message
		if ($officersContact->emailed)
		{
			return '<p style="text-align: center"><strong>Your e-mail was sent successfully. <a href="' . get_permalink() . '">Send another e-mail</a> or <a href="' . get_bloginfo('url') . '">return to the ' . get_bloginfo('name') . ' home page</a>.</strong></p>';
		}
	}
	// We haven't already processed the form, so let's instantiate the class
	else
	{
		$officersContact = new OfficersContact();
	}
	
	// Send our form to this same URL
	$action = get_permalink();
	
	// Regardless of submitted or not, we'll just do this to output the form
	return $officersContact->showContactForm($action, $hiddenFields, $typesArray, $shortnamesArray);
}

// If we find that the contact form was submitted, we want to process the form immediately
if (isset($_POST['officerContactForm_submit']))
{
	add_action('init', 'processContactForm');
}

/**
 * Processes the contact form by sending it along to our class to do all of the work
 */
function processContactForm()
{
	global $officersContact;
	
	// It probably doesn't already exist, but just in case...
	if (!($officersContact instanceof OfficersContact))
	{
		$officersContact = new OfficersContact();
	}
	
	$officersContact->processFormData($_POST['emailForm']);
	$officersContact->checkFormErrors();
	$officersContact->checkReCAPTCHAErrors($_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
	if (!$officersContact->error)
	{
		$officersContact->sendEmail();
	}
	// If there was an error, our class and shortcode function will take care of it later on in the script
	// After all we're currently in init, it's too early to yell at the user at this point!
}


/*--- OFFICERS DIRECTORY ADMIN ---*/

include_once(dirname(__FILE__) . '/officersdir_admin.php');

// Register our function to add our admin page
add_action('admin_menu', 'addOfficersDirPage');

// Add Officers Directory to the Toole menu in WP Admin
function addOfficersDirPage()
{
	global $officersDirPageHook; // Globalized so we can get to our contextual help
	$officersDirPageHook = add_management_page('Manage Directory of Officers', 'Officers Directory', 'edit_users', __FILE__, 'officersDirAdminPage');
}

// Depending on our needs, determine what function to use to display our admin page
function officersDirAdminPage()
{
	// Our URL has specifically asked us to look up a single officer
	if (isset($_GET['officer']))
	{
		officersDirEdit(array($_GET['officer']));
	}
	// We've gotten confirmation to delete certain officers
	else if (isset($_POST['officerDeleteConfirm']))
	{
		officersDirDeleteConfirmed();
	}
	// We need to update our officers, as an edit form was submitted
	else if (isset($_POST['officerEdit_submit']))
	{
		officersDirUpdate();
	}
	// We need to add some new officers thanks to the submitted form
	else if (isset($_POST['officerAdd_submit']))
	{
		officersDirInsert();
	}
	// We've gotten some kind of form submitted, but we need to determine what we were asked to do
	else if (isset($_POST['officerAction']))
	{
		switch ($_POST['officerAction'])
		{
			case 'Add':
				officersDirAdd($_POST['newOfficerCount']);
			break;
			
			case 'Edit':
				officersDirEdit($_POST['officersEdit']);
			break;
			
			case 'Delete':
				officersDirDeleteAsk($_POST['officersEdit']);
			break;
			
			case 'Re-Order':
				officersDirReorder();
			break;
			
			case 'Add Position Type':
				officersDirAddType($_POST['newPositionType']);
			break;
			
			case 'Re-Name':
				officersDirUpdateType($_POST['updateType']);
			break;
			
			// Anything else? We don't know what's going on, so let's just go to the list
			default:
				officersDirList();
			break;
		}
	}
	// We have been asked to move some position types
	else if (isset($_GET['type']) && isset($_GET['move']))
	{
		officersDirReorderTypes($_GET['type'], $_GET['move']);
	}
	// We have been asked to delete a position type, but not confirmed yet
	else if (isset($_GET['type']) && isset($_GET['delete']))
	{
		officersDirDeleteTypeAsk($_GET['type']);
	}
	// We have confirmed that we want to delete a position type
	else if (isset($_POST['delTypeConfirm']))
	{
		// Find out if we want to do with the officers in this type
		switch ($_POST['delAction'])
		{
			case 'deleteall':
				officersDirDeleteTypeConfirmed($_POST['delTypeID']);
			break;
			
			case 'transfer':
				officersDirDeleteTypeConfirmed($_POST['delTypeID'], $_POST['delTypeTransfer']);
			break;
			
			// No radio button was selected, so go back
			default:
				officersDirDeleteTypeAsk($_POST['delTypeID'], 'Please confirm whether the officers listed under this position type should be deleted or transferred to another type.');
			break;
		}
	}
	// The user just wants some help on the shortcodes
	else if (isset($_GET['help']) && ($_GET['help'] == 'shortcode'))
	{
		officersDirShortcodes();
	}
	// Nothing requested for, so let's just go to the list
	else
	{
		officersDirList();
	}
}

/*--- OFFICERS DIRECTORY SETTINGS ---*/

// Start by adding the settings page, unless we're specifying API keys as a constant
if (!defined('OFFICERSDIR_RECAPTCHAPUBLIC') || !defined('OFFICERSDIR_RECAPTCHAPRIVATE'))
{
	add_action('admin_menu', 'addOfficersDirSettings');
}

function addOfficersDirSettings()
{
	add_options_page('Manage Officers Directory Settings', 'Officers Directory', 'edit_users', __FILE__, 'officersDirAdminSettings');
	add_action('admin_init', 'officersDirRegisterPanel');
}

// Register our settings with the Settings API
function officersDirRegisterPanel()
{
	register_setting('officersDirOptions', 'officersdir_recaptchapublic');
	register_setting('officersDirOptions', 'officersdir_recaptchaprivate');
}

// Actually show off our form
function officersDirAdminSettings()
{
	// Gonna include our help section on this page (for now)
	include_once(dirname(__FILE__) . '/officersdir_help.php');
	
	// If we're in a multi-site install, we'd like to offer an olive branch
	if (is_multisite())
	{
		// Are we talking to the Super Admin?
		if (is_super_admin())
		{
			$multisiteNote = <<<EOT
<h3>Information for WordPress Multi-Site Super Admins:</h3>
<p>While not required, it is recommended that you pre-set the reCAPTCHA API Keys for all sites on this network. Doing so will hide this settings screen from all sites automatically. Simply open your <code>wp-config.php</code> file and add the following lines of code <strong>above</strong> the line reading <code>/* That&#8217;s all, stop editing! Happy blogging. */</code>, substituting your API Keys inside the single-quotes where indicated:</p>
<textarea class="code" readonly="readonly" cols="100" rows="3">
/* reCAPTCHA API Keys for the Officers Directory Plugin */
define ( 'OFFICERSDIR_RECAPTCHAPUBLIC', 'enter your PUBLIC key here' );
define ( 'OFFICERSDIR_RECAPTCHAPRIVATE' , 'enter your PRIVATE key here' );</textarea>
EOT;
		}
		else
		{
			$multisiteNote = <<<EOT
<p>NOTE: You may need to contact the Super Admin of this network to retrieve the API Keys used on this website domain. They should be able to register one set of reCAPTCHA API Keys that can be used on all sites on this network.</p>
EOT;
		}
	}
	else
	{
		$multisiteNote = '';
	}

	?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Manage Officers Directory Settings</h2>
<p>On this screen you can enter the <a href="https://www.google.com/recaptcha/admin/create">reCAPTCHA API Keys</a> required for the <a href="./../wp-admin/tools.php?page=officers-directory/officers_directory.php">Officers Directory</a> contact form to work on your site.</p>
<form method="post" action="options.php">
<table class="form-table">
	<tr valign="top">
		<th scope="row">reCAPTCHA Public Key:</th>
		<td><input type="text" name="officersdir_recaptchapublic" size="50" value="<?php echo get_option('officersdir_recaptchapublic'); ?>" /></td>
	</tr>
	<tr valign="top">
		<th scope="row">reCAPTCHA Private Key:</th>
		<td><input type="text" name="officersdir_recaptchaprivate" size="50" value="<?php echo get_option('officersdir_recaptchaprivate'); ?>" /></td>
	</tr>
</table>
<?php settings_fields('officersDirOptions'); ?>
<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
</form>
<?php echo $multisiteNote; ?>
<br />
</div>
	<?php
}

/*--- CONTEXTUAL HELP FOR THE OFFICERS ADMINISTRATION ---*/

function officersDirAdminHelp($contextual_help, $screen_id, $screen)
{
	global $officersDirPageHook;
	if ($screen_id == $officersDirPageHook)
	{
		include_once(dirname(__FILE__) . '/officersdir_help.php');
		
		// Our contextual help is going to depend on what sort of page we're loading
		// So we'll use the same if/else and switch statements from officersDirAdminPage
		// Unless, of course, that screen was going to take us to process a form
		if (isset($_GET['officer']))
		{
			$contextual_help = officersDirHelp_Edit();
		}
		else if (isset($_POST['officerAction']))
		{
			switch ($_POST['officerAction'])
			{
				case 'Add':
					$contextual_help = officersDirHelp_Add();
				break;
				
				case 'Edit':
					$contextual_help = officersDirHelp_Edit();
				break;
				
				case 'Delete':
					$contextual_help = officersDirHelp_Delete();
				break;
				
				// Anything else? We don't know what's going on, so let's just go to the list
				default:
					$contextual_help = officersDirHelp_List();
				break;
			}
		}
		else if (isset($_GET['type']) && isset($_GET['delete']))
		{
			$contextual_help = officersDirHelp_DeleteType();
		}
		else if (isset($_GET['help']) && ($_GET['help'] == 'shortcode'))
		{
			// They're already getting help on the page, nothing needed here
		}
		// Nothing requested for, so assume we're on the main list
		else
		{
			$contextual_help = officersDirHelp_List();
		}
		
		return $contextual_help;
	}
	return $contextual_help;
}

add_action('contextual_help', 'officersDirAdminHelp', 10, 3);
