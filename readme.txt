=== Single Sign On For TNG ===
Contributors: @britcoder
Tags: TNG, Genealogy, Single Sign On, The Next Generation, Family Tree
Requires at least: 6.6.1
Tested up to: 6.6.1
Stable tag: 1.1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Single Sign On  For TNG automates the login to the genealogy program TNG by Darrin Lithgoe.

== Description ==

Single Sign On For TNG improves the user experience when WordPress and the TNG Genealogy Software are on the same server.
User accounts for both systems are managed through the WordPress user registration system.  This includes account creation, deletion and password change.
Logging in and out of the users WordPress account automatically logs in and out of their account in TNG.
This plugin makes no attempt to visually incorporate TNG into the WordPress theme.  But TNG's template feature allows the developer to design a visual for TNG which is consistent with that of the WordPress Site.

== Frequently Asked Questions ==

= Is this plug-in easy to set up? =

Yes.  See the screenshots.  The full path and url to your TNG site is required plus a one-time key generation.

= How does this plugin process WordPress registrations? =

When a person applies for an account (ie: registers) at the WordPress site an entry for the person is added in both WordPress and TNG, awaiting separate approval. Approving the WordPress registration does NOT approve the TNG one. That must be done separately in TNG's administrator panel, so that you can decide whether or not to give them TNG access, and if so, at what level, branch, etc. You may want to use one of the many plugins that allow additional questions in the WordPress registration form so that you can decide how to proceed with registration in WordPress and TNG based on the answers.

= How does this plugin deal with existing WordPress users that are not in TNG? =

An optional setting allows for the automatic addition of a WordPress user to the TNG account system, based on their role. This occurs transparently when they log in to the WordPress site.  Multiple matching roles can be defined.  The user's TNG account is automatically given active Guest privileges.  The Admin is sent an email alerting that this has happened so that the TNG privilege level can be adjusted if needed.

= I have users in TNG whose account in WordPress has a different password.   How should I proceed? =

No action is required.  The plugin will synchronize the password in TNG with that in WordPress automatically when the user logs in.  This assumes that the user name in WordPress and TNG are the same.  If not another user will be added to TNG with the right user name.  It is up to the administrator to deal with any residual user accounts in TNG that are now obsolete in this eventuality.

= Does this plug-in visually integrate with my WordPress site? =

No, that is not its intent.  It provides functionality to automatically log in to and out of the TNG Family Tree site when the user logs in to or out of the WordPress site. The template functionality within TNG allows you to give it an equivalent appearance.

= Does the automatic signon to TNG create a password security risk? =

No. The password is stored in a browser cookie in encrypted form using OpenSSL.  The key used for encryption/decryption is unique to each installation and not publicly accessible.

== Screenshots ==

1. This is the setup screen prior to entering data.
2. The setup screen after completion and with the key generated.

== Changelog ==

= 1.0.0 =
* Initial Release.

= 1.0.1 =
* Added tags and fixed a typo in readme.txt.

= 1.1.0 =
  Added an optional feature to add existing WordPress user accounts to TNG on log in, based on a role match.

== Upgrade Notice ==

= 1.0.0 =
This is the initial version

== Installation ==

Install and activate the plug-in in the normal manner.  Then go to Settings, Single Sign On For TNG and configure the settings.  See the HELP section on that page for additional information.

