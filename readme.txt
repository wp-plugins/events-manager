=== Events Manager ===  
Contributors: nutsmuggler, netweblogic    
Donate link: http://davidebenini.it  
Tags: events, manager, calendar, gigs, concert, maps, geotagging, rsvp  
Requires at least: 2.9   
Tested up to: 3.0.1   
Stable tag: 3.0.5

Manage events and display them in your blog. Includes recurring events, location management, calendar, Google map integration, RSVP. 
             
== Description ==

Events Manager 3.0 is a full-featured event management solution for Wordpress. Events Manager supports recurring events, venues data, RSVP and maps. With Events Manager you can plan and publish your tour, or let people reserve spaces for your weekly meetings. You can then add events list, calendars and description to your blog using a sidebar widget or shortcodes; if you’re web designer you can simply employ the template tags provided by Events Manager. 

Events Manager integrates with Google Maps; thanks the geocoding, Events Manager can find the location of your events, and accordingly display a map. Now there's no need for Google Maps API keys, as we are now using their new v3 API.

Events Manager provides also a RSS feed, to keep your subscribers updated about the events you're organising.

Events manager is fully customisable; you can customise the amount of data displayed and their format in events lists, pages and in the RSS feed. You can choose to show or hide the events page, and change its title.   

Events Manager is fully localisable and already localised in Italian, Spanish, German and Swedish.

For more information visit the [Documentation Page](http://davidebenini.it/wordpress-plugins/events-manager/) and [Support Forum](http://davidebenini.it/events-manager-forum/).

== Installation ==

= Installing or Upgrading from 2.x =

1. Back up your database if upgrading is recommended, as with any plugin or wordpress upgrade.
2. Upload the `events-manager` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add events list or calendars following the instructions in the Usage section.

= Upgrading from 1.* only =

Events Manager 1.* adopters should:

1. backup the Wordpress database. 
2. deactivate Events Manager 1.*
3. delete Events Managers 1.* and upload Events Manager 2.* to the server
4. activate Events Manager 2.* 
5. Download and activate Events Manager 3.*
    
== Usage == 

After the installation, Events Manager add a top level "Events" menu to your Wordpress Administration.

*  The *Events* page lets you edit or delete the events. The *Add new* page lets you insert a new event.  
	In the event edit page you can specify the number of spaces available for your event. Yuo just need to turn on RSVP for the event and specify the spaces available in the right sidebar box.  
	When a visitor responds to your events, the box sill show you his reservation. You can remoe reservation by clicking on the *x* button or view the respondents data in a printable page.
*  The *Locations* page lets you add, delete and edit locations directly. Locations are automatically added with events if not present, but this interface lets you customise your locations data and add a picture. 
*  The *People* page serves as a gathering point for the information about the people who reserved a space in your events.
*  The *Settings* page allows a fine-grained control over the plugin. Here you can set the [format](#formatting-events) of events in the Events page.
* The *Help* page will provide you with information on troubleshooting and where to ask for support.

Events list and calendars can be added to your blogs through widgets, shortcodes and template tags. See the full documentation at the [Events Manager Support Page](http://davidebenini.it/wordpress-plugins/events-manager/).
 
== Frequently Asked Questions ==

= I enabled the Google Maps integration, but instead of the map there is a green/gray background. What should I do? =

If your event pages aren't loading maps properly, it may be that your theme is not set up correctly. Open the `header.php` page of your theme; if your theme hasn't any `header.php` page, just open the `index.php page` and/or any page containing the `<head>` section of the html code. Make sure that the page contains a line like this:              

    <?php wp_head(); ?>              

If your page(s) doesn't contain such line, add it just before the line containing `</head>`. Now everything should work allright.    
For curiosity's sake, `<?php wp_head(); ?>` is an action hook, that is a function call allowing plugins to insert their stuff in Wordpress pages; if you're a theme maker, you should make sure to include `<?php wp_head(); ?> ` and all the necessary hooks in your theme.

= How do I resize the map? = 

Insert some code similar to this in your css:

    #event-map {
	    width: 300px !important;
	    height: 200px !important;
    }

Do not leave out the `!important` directive; it is, needless to say, important.

= Can I customise the event page? =

Sure, you can do that by editing the page and changing its [template](http://codex.wordpress.org/Pages#Page_Templates). For heavy customisation, you can use the some of the plugin's own conditional tags, described in the *Template Tags* section.

= Can I customise the event lists, etc? = 

Yes, you can use css to match the id and classes of the events markup.

= How does Events Manager work? =   

When installed, events Manager creates a special "Events" page. This page is used for the dynamic content of the events. All the events link actually link to this page, which gets rendered differently for each event.

= Are events posts? =

Events aren't posts. They are stored in a different table and have no relationship whatsoever with posts.

= Why aren't events posts? =

I decided to treat events as a separate class because my priority was the usability of the user interface in the administration; I wanted my users to have a simple, straightforward way of inserting the events, without confusing them with posts. I wanted to make my own simple event form.  
If you need to treat events like posts, you should use one of the other excellent events plugin.

= Is Events Manager available in my language? = 

At this stage, Events Manager is only available in English and Italian. Yet, the plugin is fully localisable; I will welcome any translator willing to add to this package a translation of Events Manager into his mother tongue.

== Screenshots ==

1. A default event page with a map automatically pulled from Google Maps through the #_MAP placeholder.
2. The events management page.
3. The Events Manager Options page.

== Change Log ==
= 3.0.6 =
* Added revised German translation, thanks to 

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
* added spanish translation
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
* Refactored all the underlying achitecture, to make it object oriented. Now classes and templates are separate.    
* Merged the events and recurrences tables                                                   
* Tables migration from dbem to em (to provide a fallback in case the previous merge goes wrong)
* Bugfix: 127 limit increased (got rid of tinyint types)
* Bugfix: fixed all major php bugs preventing the use with Wordpress 3.0
* Bugfix: fixed all major js bugs preventing the use with Wordpress 3.0
* Restyling of the Settings page    
* Added a setting to revert to 2.2
* optimizing EM_Locations and removing redundant code across objects

For changelog of 2.x and lower, see the readme.txt file of version 2.2.2