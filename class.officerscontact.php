<?php
/**
 * Officers Contact Class
 *
 * Generates the e-mail contact form (integrated with Officers Directory class) & processes submitted e-mails
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

class OfficersContact
{
	// Our form variables
	public $messageTo;
	public $messageName;
	public $messageFrom;
	public $messageSubject;
	public $messageBody;
	public $messageTime;
	public $userIP;
	public $sendToSelf;
	
	// Two controllers to see if the form was submitted and if the e-mail was sent
	public $submitted = false;
	public $emailed = false;
	
	// Form error handling
	public $error = false;
	public $errorMsg = array();
	
	// Our reCAPTCHA keys, which we will load in upon constructing the class
	public $recaptchaPublicKey;
	public $recaptchaPrivateKey;
	
	// Get our class started by initalizing our reCAPTCHA keys
	public function __construct()
	{
		$this->recaptchaPublicKey	= (defined('OFFICERSDIR_RECAPTCHAPUBLIC')) ? OFFICERSDIR_RECAPTCHAPUBLIC : get_option('officersdir_recaptchapublic');
		$this->recaptchaPrivateKey	= (defined('OFFICERSDIR_RECAPTCHAPRIVATE')) ? OFFICERSDIR_RECAPTCHAPRIVATE : get_option('officersdir_recaptchaprivate');
	}
	
	/**
	 * Processes the data submitted from the contact form in preparation for e-mail
	 * Checking to see if the form is submitted should happen outside of this class
	 * Expected input is $_POST['emailForm']
	 */
	public function processFormData($formData)
	{
		$this->messageTo		= ($formData['messageTo'] == 'refresh') ? '' : htmlspecialchars($formData['messageTo']);
		$this->messageName		= htmlspecialchars($formData['messageName']);
		$this->messageFrom		= htmlspecialchars($formData['messageFrom']);
		$this->messageSubject	= str_replace("\'", "'", htmlspecialchars($formData['messageSubject']));
		$this->messageBody		= strip_tags($formData['messageBody']); // No HTML permitted for the message body
		$this->messageTime		= time(); // Not sure what we're using this for, but why not
		$this->userIP			= $formData['ipAddress'];
		$this->sendToSelf		= $formData['sendToSelf'];
		$this->submitted		= true;
	}
	
	/**
	 * Checks for errors with the submitted form and sets our error handling properties with the proper status
	 * Should be run after processFormData
	 */
	public function checkFormErrors()
	{
		// Three excuses to not send this e-mail, here they are
		if (empty($this->messageTo))
		{
			$this->error		= true;
			$this->errorMsg[]	= 'You did not select a recipient for this e-mail.';
		}
		if (empty($this->messageFrom))
		{
			$this->error		= true;
			$this->errorMsg[]	= 'You must specify a return address for this e-mail.';
		}
		// This is a PHP 5.2.0+ function, so if the function doesn't exist, we'll go on blind faith
		else if (function_exists('filter_var'))
		{
			$emailFrom = filter_var($this->messageFrom, FILTER_VALIDATE_EMAIL);
			if (!$emailFrom)
			{
				$this->error		= true;
				$this->errorMsg[]	= 'The return address you specified is not recognized as a valid e-mail address.';
			}
			else
			{
				$this->messageFrom = $emailFrom; // In case our filter yielded a more precise version
			}
		}
		if (empty($this->messageBody))
		{
			$this->error		= true;
			$this->errorMsg[]	= 'There is no text in this e-mail message. You cannot send a blank e-mail.';
		}
	}
	
	/**
	 * Checks the reCAPTCHA form submission to ensure the right CAPTCHA was submitted
	 * Requires the reCAPTCHA library plugin to be installed, if it can't be found this throws an error at the unsuspecting user
	 *
	 * Expected vars are $_POST['recaptcha_challenge_field'] and $_POST['recaptcha_response_field']
	 */
	public function checkReCAPTCHAErrors($recaptcha_challenge_field, $recaptcha_response_field)
	{
		// Make sure we have the function
		include_once(dirname(__FILE__) . '/recaptchalib.php');
		if (!function_exists('recaptcha_check_answer'))
		{
			$this->error		= true;
			$this->errorMsg[]	= 'The reCAPTCHA functions were not recognized, and so this form is not operational at this time. Please try again later.';
			return;
		}
		
		$recaptchaResp = recaptcha_check_answer($this->recaptchaPrivateKey, $_SERVER["REMOTE_ADDR"], $recaptcha_challenge_field, $recaptcha_response_field);
		if (!$recaptchaResp->is_valid)
		{
			$this->error		= true;
			$this->errorMsg[]	= 'You did not enter the anti-spam verification words correctly. Please try again.';
		}
	}
	
	/**
	 * At long last we can send the e-mail! (Checking on $this->error should happen outside of this class
	 */
	public function sendEmail()
	{
		global $wpdb;
	
		$displayName	= (empty($this->messageName)) ? 'Someone' : $this->messageName;
		$siteName		= get_bloginfo('name');
		$adminEmail		= get_bloginfo('admin_email');

		$emailMessage = <<<EOT
Hello,

{$displayName} has sent you an e-mail message from the {$siteName} website, which is displayed below.
E-mail address: {$this->messageFrom}
IP Address: {$this->userIP}

Message sent to you follows:
~~~~~~~~~~~~~~~~~~~~~~~~~~~

{$this->messageBody}
EOT;
		$emailMessage	= str_replace("\'", "'", wordwrap($emailMessage, 70));
		$emailHeaders	= "From: {$this->messageName} <{$this->messageFrom}>\r\n" .
			"Reply-To: {$this->messageName} <{$this->messageFrom}>\r\n" .
			'X-Mailer: PHP/' . phpversion();
		
		// Before we send this off, let's select some info about this officer from the database
		// To get the right database table we'll initialize the OfficersDir class
		// We are presuming that if we're sending the e-mail at this point (we are) we won't need this class again
		$officersDir	= new OfficersDir();
		$recipientInfo	= $wpdb->get_row("SELECT positionOfficer, positionCoOfficer, positionEmail FROM $officersDir->officersTable WHERE positionShortname = '$this->messageTo'");
		$emailTo		= $recipientInfo->positionEmail;
		
		// Send the e-mail!
		mail($emailTo, $this->messageSubject, $emailMessage, $emailHeaders);
		
		// Did the user want us to send them a copy? Let's do so now
		if ($this->sendToSelf)
		{
			$sendTo = (empty($recipientInfo->positionCoOfficer)) ? $recipientInfo->positionOfficer : $recipientInfo->positionOfficer . ' and ' . $recipientInfo->positionCoOfficer; // Checking on if there is a co-officer or not
			$sendSelfMessage = <<<EOT
Hello,

You sent the following e-mail to {$sendTo} via the Contact form on the {$siteName} website, and have requested that you be sent a copy.

Message sent follows:
~~~~~~~~~~~~~~~~~~~~~~~~~~

{$this->messageBody}
EOT;
			$sendSelfMessage	= str_replace("\'", "'", wordwrap($sendSelfMessage, 70));
			$sendSelfHeaders	= "From: {$siteName} <{$adminEmail}>\r\n" .
				"Reply-To: {$this->messageName} <{$this->messageFrom}>\r\n" .
				'X-Mailer: PHP/' . phpversion();
			mail($this->messageFrom, 'Message Sent: ' . $this->messageSubject, $sendSelfMessage, $sendSelfHeaders);
		}
		$this->emailed = true;
	}
	
	/**
	 * A private function that formats all of our accumulated errors for us, nice and tidy
	 */
	private function genFormErrors()
	{
		if ($this->error)
		{
			// Set up an area where we will list all the errors that we've received
			$formErrors = '<p style="color:red"><strong>The following errors were encountered from the e-mail you submitted. Please fix them and try again.</strong>';
			// This foreach loop goes through each error message and adds it to $formErrors
			foreach ($this->errorMsg as $value)
			{
				$formErrors .= '<br />' . $value;
			}
			$formErrors .= '</p>';
			return $formErrors;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Shows the Contact form to the user
	 * If form has already been submitted, this will show any applicable errors and pre-populate the fields with submitted data
	 *
	 * @param string $action The action="" attribute for <form>, indicating where to send the form for processing
	 * @param string $hiddenFields Any hidden fields to include with the form, defaults to ''
	 * @param array $types Lists the types of officers to select for the dropdown, passes on to $officersdir->selectOfficers
	 * @param array $shortnames Lists the specific officers to select for the dropdown, passes on to $officersdir->selectOfficers
	 */
	public function showContactForm($action, $hiddenFields = '', $types = array(), $shortnames = array())
	{
		// Check this box by default, but if the form was submitted, then let's see what the user set
		$sendSelfChecked = ($this->submitted) ? (($this->sendToSelf) ? ' checked="checked"' : '') : ' checked="checked"';
		
		// Vars that could have been set by $_GET, which would override anything already in our object from $_POST
		if (isset($_GET['subject']))
		{
			$this->messageSubject = htmlspecialchars($_GET['subject']);
		}
		if (isset($_GET['officer']))
		{
			$this->messageTo = htmlspecialchars($_GET['officer']);
		}
		
		// Get our drop-down of officers out of the OfficersDir class
		$officersDir = new OfficersDir();
		$officersDir->selectOfficers($types, $shortnames);
		$officersDropdown = $officersDir->displayOfficersDropdown('emailForm[messageTo]', $this->messageTo);
		
		// Get our reCAPTCHA form, if supported; if not, we'll spit an error
		include_once(dirname(__FILE__) . '/recaptchalib.php');
		if (function_exists('recaptcha_get_html') && !empty($this->recaptchaPublicKey) && !empty($this->recaptchaPrivateKey))
		{
			$recaptchaForm = recaptcha_get_html($this->recaptchaPublicKey);
		}
		else
		{
			$recaptchaForm = '<div style="color:red">Unable to display the reCAPTCHA anti-spam confirmation form. Submitting this form will not work at the present time. Please try again later.</div>';
		}
		
		// Grab our errors
		$formErrors = ($this->error) ? $this->genFormErrors() : '';
		
		// Now for our form to display
		$contactForm = <<<EOT
<form method="post" action="{$action}">
{$formErrors}
<style type="text/css">
table.nostyle td,table.nostyle th,table.nostyle tr.even td,table.nostyle tr:hover td {
	border: 0 !important;
	margin: 0 0 0 0 !important;
}
</style>
<table class="nostyle" id="officersdircontact">
	<tr>
		<td><strong>Send mail to:</strong></td>
		<td>{$officersDropdown}</td>
	</tr>
	<tr>
		<td>Your Name (optional):</td>
		<td><input type="text" name="emailForm[messageName]" value="{$this->messageName}" size="40" maxlength="100" /></td>
	</tr>
	<tr>
		<td><strong>Your e-mail address:</strong></td>
		<td><input type="text" name="emailForm[messageFrom]" value="{$this->messageFrom}" size="40" maxlength="100" /></td>
	</tr>
	<tr>
		<td>Subject:</td>
		<td><input type="text" name="emailForm[messageSubject]" value="{$this->messageSubject}" size="40" maxlength="255" /></td>
	</tr>
	<tr>
		<td>Message Body:</td>
		<td><textarea name="emailForm[messageBody]" cols="40" rows="8">{$this->messageBody}</textarea></td>
	</tr>
	<tr>
		<td colspan="2"><input type="checkbox" name="emailForm[sendToSelf]" {$sendSelfChecked}/>&nbsp;&nbsp;Send Me a Copy of This E-mail</td>
	</tr>
	<tr>
		<td>Anti-Spam Verification:</td>
		<td>{$recaptchaForm}</td>
	</tr>
	<tr>
		<td colspan="2">{$hiddenFields}<input type="hidden" name="emailForm[ipAddress]" value="{$_SERVER['REMOTE_ADDR']}" /><input type="submit" name="officerContactForm_submit" value="Send E-mail" /></td>
	</tr>
</table>
</form>
EOT;
		return $contactForm;
	}
}
