=== Formidable Forms ===
Requires at least: 3.8
Requires at least Captcha: 4.0.5
Tested up to: 4.7.3
Stable tag: 1.15

== Changelog ==
= 1.15 =
* Fix: Make sure auto updating works properly
* Fix: Get field title from new BWS Captcha setting

= 1.14 =
* Make sure Formidable options are saved on BWS Captcha page
* Allow captcha to be hidden for logged-in users

= 1.13 =
* Make sure captcha works with BWS 4.2.3
* Fix captcha with alternate submits

= 1.12 =
* Make sure captcha shows up on forms with BWS 4.1.6

= 1.11 =
* Get updates from FormidablePros.com
* Move code into class
* Codestyling cleanup
* Add a nonce field if the captcha is not included on a form so that can be checked instead
* Change text domain to one that is more unique
* Check the captcha settings after the form is loaded instead of before
* Fixes for paged forms
* Requires at least v4.0.5 of the Captcha plugin

= 1.10 =
* Use the new Captcha hooks for adding the checkbox in the settings
* Prevent Formidable no-conflict styling from forcing a long field
* Added the error class for the label and input box so they will have the same error styling as other fields in the form
* Show required indicator with the label
* Added frm_cpt_field_classes hook for adding field container classes
* Added Catalan translation

= 1.09.03 =
* Update for Captcha Pro compatibility

= 1.09.02 =
* Update for Captcha v3.9.8 compatibility

= 1.09.01 =
* Fixed php notices after submit

= 1.09 =
* Added checkbox on form settings page to not load math captcha on that form
* Update for Formidable v1.07.02 compatibility

= 1.08 =
* Save checkbox correctly on captcha settings page in v3.8.8

= 1.07 =
* Update for Captcha v3.8.8 compatibility
* Dropped get_option fallback for get_site_option
* Update for preview page compatibility in Formidable v1.07.02

= 1.06 =
* Update for Captcha v3.8.2 compatibility
* Fixed auto updating when used with Formidable 1.07+

= 1.05 =
* Added a PO file for translations
* Updated for compatibility with Captcha v3.7.3 and future versions

= 1.04 =
* Updated for Captcha v3.4 compatibility
* Added plugin auto update

= 1.03 =
* Updated for Captcha v2.4.x compatibility

= 1.02 =
* Updated validation messages to accept the answer "0"
* Updated to only show the captcha on the last page of a multi-paged form instead of all pages
