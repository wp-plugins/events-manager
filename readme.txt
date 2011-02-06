=== Events Manager ===  
Contributors: nutsmuggler, netweblogic    
Donate link: http://wp-events-plugin.com
Tags: events, event, bookings, events manager, calendar, gigs, concert, venue, location, maps, geotagging, rsvp  
Requires at least: 2.9   
Tested up to: 3.0.4
Stable tag: 3.0.94

Fully featured events management including recurring events, location management, calendar, Google map integration, booking management and more.
             
== Description ==

Events Manager 3.0 is a full-featured event management solution for Wordpress based on the principles of flexibility, reliability and powerful features!

Main Features

* Easy event creation (single day with start/end times)
* Recurring and long (multi-day) events
* Assign event locations and view events by location
* Event categories
* Easily create custom event attributes (e.g. dress code)
* Bookings Management (including approval/rejections, export CVS, and more!)
* Google Map integration straight out the box!
* Advanced permissions - restrict user management of events.
* Sidebars to display your events and locations
* Calendaring widgets show your events in an AJAX calendar format
* Fine grained control of how every aspect of your events are shown on your site, easily modify templates from the settings page
* RSS Feeds
* Plenty of template tags and shortcodes for use in your posts and pages
* Actively maintained and supported
* Fully localizable, translations already available in German, Spanish, Czech, Italian, Dutch, Portuguese and Swedish
* And much more!

Events Manager 3.0 was written from the ground up with flexibility in mind. Through use of object oriented programming and exposing hooks and filters throughout the site, you can modify Events Manager just like you would Wordpress!

Events Manager is fully localisable and already localised in Italian, Spanish, German and Swedish.

For more information and support please visit the [Documentation Page](http://wp-events-plugin.com/documentation/) and [Support Forum](http://wp-events-plugin.com/forums/).

== Installation ==

Events Manager works like any standard Wordpress plugin, and requires little configuration to start managing events. If you get stuck, visit the [Documentation Page](http://wp-events-plugin.com/documentation/) and [Support Forum](http://wp-events-plugin.com/forums/).

Whenever installing or upgrading any plugin, or even Wordpress itself, it is always recommended you back up your database first!

= Installing =
 
1. If installing, go to Plugins > Add New in the admin area, and search for events manager.
2. Click install, once installed, activate and you're done!

Once installed, you can start adding events straight away, although you may want to visit the [Documentation Pages](http://wp-events-plugin.com/documentation/) and unleash the full power of Events Manager.

= Upgrading =

1. When upgrading, visit the plugins page in your admin area, scroll down to events manager and click upgrade.
2. Wordpress will help you upgrade automatically.

See Wordpress docs for more info on [managing plugins](http://codex.wordpress.org/Managing_Plugins) in general.

= Upgrading from 2.x to 3.x =

Version 3.x uses different tables than 2.x. Events should be migrated automatically without any action needed from you. However, in the event something does go wrong (very rare, we've done it many times), you can downgrade immediately without losing any settings, or you can click on the help page and try re-importing your events. If you run into any issues, let us know in the forums and we'll be happy to help.
 
== Frequently Asked Questions ==

See our [FAQ](http://wp-events-plugin.com/documentation/faq/) page, which is updated regularly.

You may also be interested in our [troubleshooting](http://wp-events-plugin.com/documentation/troubleshooting/) and other [documentation](http://wp-events-plugin.com/documentation/)

== Screenshots ==

1. A default event page with a map automatically pulled from Google Maps through the #_MAP placeholder.
2. The events management page.
3. The Events Manager Options page.

== Change Log ==

= 3.0.94 =
* Fixed missing events, locations etc. due to permissions
* Fixed location widget bug
* fixed broken global map js

= 3.0.93 =
* Fixed bug with ownership and widgets
* Resolved 2.9 incompatibility
* Fixed rss ownership bug
* Fixed calendar bug where pre/post dates don't show events
* Fixed calendar, now showing today correctly
* Categories blank page fix
* fixed page nav conflicts with role scoper
* added shortcut to manage bookings on event list


= 3.0.92 =
* Fixed permission issue
* Fixed category not saving
* Fixed location saving issue


= 3.0.91 =
* Documentation finally up to date now!
* widget bug fixed
* added event permissions, so users can manage their own events/locations/categories
* improved event booking UI and management tools
* export CSV of bookings
* booking approvals added
* bookings can have individual notes
* calendar widget shows selected month if clicked on 
* custom attributes field, for atts that don't need to be in a template (e.g. pdf file url)
* time limit for main events list and events widget (e.g. show events that occur within x months)
* default location
* default category
* added extra validation so event start date/times can't be after end date/time
* calendar navigation will pass on all arguments for following month (e.g. category, etc)
* small map balloon fix for some rare js conflicts
* fixed location gui editor

= 3.0.9 =
* Fixed small calendar discrepancies
* added event and location single shortcodes
* shortcodes now accept html within format attribute or within the shortcode tags [like]<p>this</p>[/like]
* fixed pagination functionality (or lack thereof) in shortcodes
* improved user experience when navigating/editing events in admin area
* added #_CONTACTAVATAR placeholder - avatar for contact person
* ajax loading spinner graphic added to calendars
* internal wp_mail support added
* added "all events" link to events widget
* fixed date translations
* cleaned up the settings page documentation and added placeholder docs on help page.
* fixed "enable notification emails" option in settings
* added admin email option that would be send every event booking to admin 

= 3.0.81 =
* Fixed pagination bugs
* Global locations map won't show locations with 0-0 coords
* Fixed bug in recurrence description
* Removed most (if not all) php warnings
* Fixed booked seats calculation errors
* Removed dependence on php calendar

= 3.0.8 =
* Event lists now have pagination links for both admin and public areas!
* Fixed time zone issue with calendars, now taking time from WP settings, not server
* Added option to show long events if showing a calendar of events page.
* Multiple maps on one page will now show up.
* Modified styling of map balloons to not use #content (if you modded your theme, look at the CSS to override).
* Media uploads in GUI now working as expected
* Orderby ordering in events widget

= 3.0.7 =
* Renaming a few functions/shortcodes for consistency
* Fixing #_LOCATIONPAGEURL issue
* Fixed ordering issue again
* New template tags
* First filter

= 3.0.6 =
* Added revised German translation
* Fixed ordering issue
* Fixed old template tag attributes not being read
* Changed map balloon wrapper id to class

= 3.0.5 =
* Fixed 12pm bug
* Re-added #_LOCATIONPAGEURL (although officially it's depreciated)
* Added default order by settings in options page
* Added default event list limits in options page
* Added orderby attribute for shortcode
* scope attribute now also allows searching between dates, e.g. "2010-01-01,2010-01-31"
* Fixed booking email reporting bug

= 3.0.4 =
* Title rewriting workaround for themes where main menus are broken on events pages
* Added option to show lists on calendar days regardless of whether there is only one event on that day.
* added Spanish translation
* fixed rsvp deletion issue
* fixed potential phpmailer conflicts
* CSS issue with maps fixed
* optimized placeholders, adding new standard placeholders

= 3.0.3 =
* RSS Showing up again
* Fixed some reported fatal errors
* Added locations widget
* Adding location widget
* optimizing EM_Locations and removing redundant code across objects
* fixed locations_map shortcode attributes
* harmonized search attributes for locations and events
* rewrote recurrence code from scratch
* got rid of most php notices

= 3.0.2 =
* Recruccence bugfix

= 3.0.1 =
* Fixed spelling typos
* Fixed warnings for bad location image uploads (e.g. too big etc.)
* Fixed error for #_EXCERPT not showing

= 3.0 =
* Refactored all the underlying architecture, to make it object oriented. Now classes and templates are separate.    
* Merged the events and recurrences tables                                                   
* Tables migration from dbem to em (to provide a fallback in case the previous merge goes wrong)
* Bugfix: 127 limit increased (got rid of tinyint types)
* Bugfix: fixed all major php bugs preventing the use with Wordpress 3.0
* Bugfix: fixed all major js bugs preventing the use with Wordpress 3.0
* Restyling of the Settings page    
* Added a setting to revert to 2.2
* optimizing EM_Locations and removing redundant code across objects

For changelog of 2.x and lower, see the readme.txt file of version 2.2.2
