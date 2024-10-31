=== Plugin Name ===
Contributors: webmacster87
Tags: officers, positions, directory, contact form, e-mail
Requires at least: 3.0.0
Tested up to: 3.0.1
Stable tag: 1.2.0

Allows for the creation and management of a directory of officers for your organization. Includes an integrated contact form with reCAPTCHA support.

== Description ==

This plugin is designed for organizations of any size that want to provide their visitors with an organized list of their officers and/or a way for visitors to contact those officers. Using this plugin you can create a directory of your officers, which can be display publicly in a table view. You can also embed a reCAPTCHA-powered contact form on your site that integrates with the officers directory in order to let your visitors select the recipient of their e-mail from among your officers.

Features include:

*	Can identify each officer by their position name/title & the name of the officer holding that position
*	Support for one officer, two co-officers, or identifying a position as vacant
*	E-mail addresses of officers are kept private from the public-facing areas of your site
*	Optionally provide a description of what each officer's duties are that can be displayed publicly
*	Group related officer positions together under custom position types
*	Complete control of the display order of position types and officers within those types

This plugin is compatible with the multisite-enabled installations of WordPress, so that each site in your network can maintain a different directory of officers. This plugin also integrates with WordPress' contextual help system, so you can use the sliding Help tabs in the admin panel for detailed information about how to manage the directory.

IMPORTANT! This plugin requires PHP 5.0.0 or later to be running under your WordPress installation.

== Installation ==

1. Upload the `officers-directory` folder and its entire contents to the `/wp-content/plugins` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. If you are planning to use the contact form, register for [reCAPTCHA API keys](https://www.google.com/recaptcha/admin/create) for your site, then submit them in the 'Officers Directory' panel under the 'Settings' menu in WordPress.
4. Configure your directory and add officers using the 'Officers Directory' panel under the 'Tools' menu in WordPress.
5. Add the `[officers-table]` and/or `[officers-contact]` shortcodes to your pages. Instructions for these are provided on the plugin's settings pane, and in the 'Other Notes' section of this readme.

NOTE: This plugin creates two new tables in your database, however they are not created until the Officers Directory admin panel is loaded for the first time. On multi-site installations, these two tables are created separately for each site on your network once the Officers Directory admin panel is loaded for the first time on that site.

== Frequently Asked Questions ==

= Do I have to use the "position types" feature? =

The directory is designed such that each officer must belong to a position type. However, if there is only one position type defined, its name will never be displayed publicly. Therefore you can continue using the default one if you do not plan to take advantage of this feature.

= How does the ordering system work? =

Officers are always grouped together within their specified position type. You can use the up/down arrows listed next to each position type in the admin panel to re-order the appearance of those groups.
Within each position type, you can change the Order numbers assigned to each officer to change the display order. Officers will be listed sequentially according to these numbers. (This works similar to how you can order pages in WordPress, but the interface is a bit easier to navigate.)

= What are "shortnames" and why should I care? =

A shortname is a short version of the position name that is all-lowercase (no spaces). It is a unique identifier of each position in the database which is used internally to refer to the position. As an example, if you have a "President" officer, you could make `president` be the shortname.
You can also use the shortname in a number of places on your site. For example, if the page with your contact form is located at `http://www.example.com/wordpress/contact/` you can make a link to `http://www.example.com/wordpress/contact/?officer=president` which will pre-fill the contact form with the President as the recipient.
You have the flexibility to determine the shortname any way you want, however it is required. The shortname is listed in the Officers Directory admin panel in parentheses.

= I want to change the CSS of the officers table and contact form. =

The officers table has `<table id="officersdirtable">` and the contact form has `<table id="officersdircontact">` as their encapsulating HTML elements. Thus you should be able to use those IDs to apply custom CSS to them.
It is recommended that the contact form have no CSS whatsoever applied to it. The officers table, on the other hand, may benefit from some additional CSS beyond a bare table.

== Screenshots ==

1. The interface for adding new officers & editing them. Note the ability to add & edit multiple officers at once.
2. The main table, showing officers organized by position type. Controls for managing all aspects of the directory are easily available.
3. Contextual help is provided throughout the admin interface to explain how to manage the directory.
4. The officers table, displayed in the Twenty Ten theme. The e-mail links connect to the Contact form and pre-fill it.
5. The integrated contact form, with a drop-down menu to select the e-mail recipient.

== Changelog ==

= 1.2.0 =
* Added a feature for multi-site installations; specifying the reCAPTCHA API Keys in `wp-config.php` will automatically apply them across the network and hide the Officers Directory settings panel.
* If in a multi-site install, the Officers Directory settings panel will provide the code for `wp-config.php` to users with Super Admin capabilities.
* Added a separate admin screen for the shortcodes help, which is no longer shown on the Officers Directory settings panel.
* When adding new officers in bulk, the form now ignores any new officers that were left blank.

= 1.1.0 =
* Increased the width of the E-mail column for better style compatibility.
* Fixed an issue with incorrect single-edit links in the officers admin.
* Fixed an issue where contact form e-mails were not being sent.
* Closed a possible security issue where hidden fields could be passed to the officers contact form via the `[officers-contact]` shortcode.
* Now setting reCAPTCHA API Keys in the WordPress admin, mainly because WordPress evilly replaces files during the update process.
* New Officers Directory settings pane for setting the API Keys, and also provides the how-to on using the shortcodes. More settings will be added here in the future.

= 1.0.5 =
* Fixed an issue with the `positiontitle` and `officertitle` attributes in the `[officers-table]` shortcode not working.

= 1.0.4 =
* Did you know that the WordPress Directory forces a dash (-) in the plugin folder, even when you programmed with an underscore (_) in mind? Lovely. All related bugs have been fixed in this one.

= 1.0.3 =
* Fixed an issue where the wp_officers_types table might not be created due to a duplicate PRIMARY KEY declaration, which could cause a SQL error.
* Fixed an issue where the plugin version number was not being added to the database.
* officersDirInstall() function now only runs when the plugin version number listed in the database does not match the one in the plugin file.

= 1.0.2 =
* One more try to get the plugin metadata correct, including getting the readme.txt and officers_directory.php version numbers consistent.

= 1.0.1 =
* Fixing some issues with my readme.txt file. (I'm new at this.)

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

= 1.1.0 =
* Please note that after upgrading to this version, you may have to re-set your reCAPTCHA API keys.

== Officers Directory Shortcodes ==

To display a directory of your officers, use the `[officers-table]` shortcode in your post. You can also specify a number of attributes (by including them within the brackets) to customize the display of this form:

*	`contactform="http://example.com/contact/"` Insert the link to the page or post containing the contact form shortcode (see below) and the directory will display e-mail links for each officer that will pre-fill the contact form's "Send mail to" menu.
*	`types="Type 1|Type 2|etc."` If you want to restrict the directory to only show certain types of officers, specify the name of each type here, separated by a pipe symbol (|).
*	`shortnames="officer1|officer2|etc."` If you want to specify exactly which officers appear in the directory, specify the *shortname* of each officer here, separated by a pipe symbol (|). Shortnames are shown inside parentheses in the list below.
*	`descriptions="true"` If you want to display descriptions for each position that specifies them in your directory, include this attribute in your shortcode. Descriptions use a collapsible effect to display to the end-user.
*	`positiontitle="Position"` Change this if you want the header of the "Position" column to have a different title.
*	`officertitle="Officer"` Change this if you want the header of the "Officer" column to have a different title.

To display a contact form for your officers, use the `[officers-contact]` shortcode in your post. You can also specify a number of attributes (by including them within the brackets) to customize which officers are available to this form:

*	`types="Type 1|Type 2|etc."` If you want to restrict the contact form to only list certain types of officers, specify the name of each type here, separated by a pipe symbol (|).
*	`shortnames="officer1|officer2|etc."` If you want to specify exacty which officers can be contacted, specify the *shortname* of each officer here, separated by a pipe symbol (|). Shortnames are shown inside parentheses in the list below.

IMPORTANT! For this contact form to work, you <em>must</em> specify API Keys for the [reCAPTCHA](http://www.google.com/recaptcha/) service. [Get your API keys](https://www.google.com/recaptcha/admin/create) and then add them to the `apikeys_config.php` file in the `/wp_content/plugins/officers-directory/` folder.</p>

== Credits ==

This plugin includes the PHP reCAPTCHA library by Mike Crawford and Ben Maurer; Copyright (c) 2007 reCAPTCHA; used under the terms of the GPLv2.
Thanks to Lorelle VanFossen for convincing me to release this plugin publicly, and thanks to the guys in the #phpBB-Coding channel on irc.freenode.net for helping me out when the debugging got tough.