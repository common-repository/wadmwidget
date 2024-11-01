wadmwidget
Contributors: Jondor
Tags: WerkAanDeMuur, OhMyPrints, Phototools
Requires at least: 3.0.1
Tested up to: 5.2
Stable tag: 1.4
Requires PHP: 5.6
Donate link: https://gerhardhoogterp.nl/plugins/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A widget to connect photos to the Werk aan de Muur / Oh My Prints sales page. Part of the phototools plugins

== Description ==

A widget to connect photos to the Werk aan de Muur / Oh My Prints sales page. 

* Checks the userid/password for validity
* Checks if a workcode is yours
* Filter on the postlist
* Workcode field in the quickedit and on the editscreen
* Widget to place on the photo page
* select the sites language (Dutch, German or French)

Result: https://gerhardhoogterp.nl/2017/12/28/old-farm-in-the-woods/

New: shortcode [wadm]. Use as:

none:				[wadm]					Same as [wadm data=formatted_link]
id:				[wadm data=id]
ownerid:			[wadm data=ownerid]
file:				[wadm data=file]
link:				[wadm data=link]
image:				[wadm data=image]
imagehttps:			[wadm data=imagehttps]
price:				[wadm data=price]
size:				[wadm data=size]
size-width:			[wadm data=size-width]			Only the width in pixels
size-height:		        [wadm data=size-height]			Only the height in pixels
aspect:				[wadm data=aspect]
title:				[wadm data=title]
formatted:			[wadm data=formatted]			Formated string (title, size and price)
formatted_link:			[wadm data=formatted_link]		Formated with link, target=_blank"

Languages supported for the are only dutch and german, Dutch if you redirect to WerkAanDeMuuer, German when you redirect to OhMyPrints. 
Regretfully the usefullness of this is rather limited due to the limited amount of data. 

The link used by the shortcode has an extra wadm_link class for all your specialized formatting needs. 

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. go to the media list, filter on "unattached" and check out the menu and the bulk pulldown. 

== Frequently Asked Questions ==

== Screenshots ==

1. Settings screen
2. Filter and indicatorcolumn
3. Field in quickedit window
4. Workcode widget in post edit screen with "correct" indication
5. The result as shown to the user. 
6. Shortcodes editscreen
7. Shortcodes translated in the userview
8. Alternative widget style


== Changelog ==

= 1.0 =
First release

= 1.1 =
* Some link related fixes
* added a shortcode for the imagedata
* added a second widget style: The photo thumbnail with the WadM text over it. Select in the widget form.

= 1.2 =
Fixed some minor compatabilitie issues.

= 1.4 =
Fixed minor typo which disabled the WadM marker in the postlist

== Upgrade Notice ==

Nothing yet.

== to do ==

