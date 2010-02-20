=== Events Manager ===  
Contributors: nutsmuggler, netweblogic    
Donate link: http://davidebenini.it  
Tags: events, manager, calendar, gigs, concert, maps, geotagging  
Requires at least: 2.7   
Tested up to: 2.8.4   
Stable tag: 2.0rc2   

Manage events and display them in your blog. Includes recurring events, location management, calendar, Google map integration, RSVP. 
             
== Description ==

Events Manager 2.0 is a full-featured event management solution for Wordpress. Events Manager supports recurring events, venues data, RSVP and maps. With Events Manager you can plan and publish your tour, or let people reserve spaces for your weekly meetings. You can then add events list, calendars and description to your blog using a sidebar widget or shortcodes; if you’re web designer you can simply employ the template tags provided by Events Manager. 

Events Manager integrates with Google Maps; thanks the geocoding, Events Manager can find the location of your events, and accordingly display a map. To enable Google Maps integration, you need a Google maps API key, which you can obtain freely at the [Google Maps API Signup Page](http://code.google.com/apis/maps/signup.html).

Events Manager provides also a RSS feed, to keep your subscribers updated about the events you're organising.

Events manager is fully customisable; you can customise the amount of data displayed and their format in events lists, pages and in the RSS feed. You can choose to show or hide the events page, and change its title.   

Events Manager is fully localisable and already localised in Italian, Spanish, German and Swedish.

For more information visit the [Documentation Page](http://davidebenini.it/wordpress-plugins/events-manager/) and [Support Forum](http://davidebenini.it/events-manager-forum/).

== Installation ==

1. Upload the `events-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add events list or calendars following the instructions in the Usage section.

Events Manager 1.* adopters should:

1. backup the Wordpress database. 
2. deactivate Events Manager 1.*
3. delete Events Managers 1.* and upload Events Manager 2.* to the server
4. activate Events Manager 2.*

Events Manager should take care of your events database migration automatically, but since this is a beta release it's much better to perform a database backup, as previously suggested.

== Usage == 

After the installation, Events Manager add a top level "Events" menu to your Wordpress Administration.

*  The *Events* page lets you edit or delete the events. The *Add new* page lets you insert a new event.  
	In the event edit page you can specify the number of spaces available for your event. Yuo just need to turn on RSVP for the event and specify the spaces available in the right sidebar box.  
	When a visitor responds to your events, the box sill show you his reservation. You can remoe reservation by clicking on the *x* button or view the respondents data in a printable page.
*  The *Locations* page lets you add, delete and edit locations directly. Locations are automatically added with events if not present, but this interface lets you customise your locations data and add a picture. 
*  The *People* page serves as a gathering point for the information about the people who reserved a space in your events.
*  The *Settings* page allows a fine-grained control over the plugin. Here you can set the [format](#formatting-events) of events in the Events page.

Events list and calendars can be added to your blogs through widgets, shortcodes and template tags. See the full documentation at the [Events Manager Support Page](http://davidebenini.it/wordpress-plugins/events-manager/).
 
== Frequently Asked Questions ==

= I enabled the Google Maps integration, but instead of the map there is a green background. What should I do? =

I call that "the green screen of death", but it's quite easy to fix your issue. If you see that green background, your theme has a little problem that should be fixed. Open the `header.php` page of your theme; if your theme hasn't any `header.php` page, just open the `index.php page` and/or any page containing the `<head>` section of the html code. Make sure that the page contains a line like this:              

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

1.0b1   
Fixed a small bug which prevented the loading of default options in the plugin.

1.0b2
Added a `#_URL` placeholder. 

1.0b3
Fixed a small ampersand bug which prevented validation.

1.0b4  
Permalinks now properly working.  
Text now uses wordpress filters.  
Map #_NOTES bug fixed; maps better centred.
           
1.0b5  
Fixed a bug that caused trouble in the new post page javascript

1.0  
No changes, only made this plugin officially out of beta after weeks without any bug popping out.
      
1.0.1  
Added the `dbem_is_events_page`  `dbem_is_single_event_page`, `dbem_is_multiple_events_page()`, `dbem_are_events_available` conditional template tags.      
Added a "no events message option".    
Added two important FAQ items, to document how to prevent the "green screen on death" and how to resize the map.  
Fixed a bug that filtered `the_content` even in unrelated lists.    
Fixed CSS bug: enclosed list in Events page in "ul" elements, as it should be.   
Fixed a bug loaded the Google Maps Api when deleting events.      
Fixed a bug that prevented validation in the default widget list item format.     

1.1b
Added a javascript datepicker   

2.0b1 
Added locations support.
Added RSVP and people management.
Added repeated events.
Added multiple map.  
Fixed a bug in calendars which displayed only the first events when more are present.       

2.0b2
Fixed some bugs

2.0b3 
Fixed some bugs affecting EM 1.0 users
Added 2 settings: EM page as calendar and change EM page
Added Swedish and German localisations

2.0b4
Fixed a bug in the RSS generator
Added alternate start and end time selector for those installs not supporting the default system
Removed "Mappa totale" from the gloabl map code  
Fixed a problem in the back button in the events table
Removed some debug "echo" from the RSVP form
Hopefully fixed a database scheme bug that some users signalled  

2.0rc1
Added JS validation and fallback server-side validation  
Added a dbem\_is\_rsvpable() conditional template tag 
Fixed a css bug with some themes, preventing the correct visualisation of the map.
Fixed MySql bugs in the main view and in the activation page
Added the proper expanded PHP tags
Fixed links in the RSS feed       

2.0rc2
Marcus Skies jumps in as a contributor
Made the edit page WP 2.8 compatible (CSS tags)
Added a "Duplicate Event", since your reoccurring event doesn't give perfect date flexibility
Added a Category option, so you can categorize each event
Manage categories with own subpanel
DDM available in event page (like with people)
Added shortcode option in event_list, so category=ID is an option now
Added #_CATEGORY as a placeholder
Added the TinyMCE of wordpress to the description of the event. That solves the problem of adding pictures!
Added an end date option always on for multi-day events.
Added a new placeholder format to deal with the end date. You can now wrap dates in #_{} or #@_{} . The values inside will have a format of date(). For example #_{Y-m-d} #@_{ \u\n\t\i\l Y-m-d} will show as "2009-03-23 until 2009-03-28" (only for end dates with no recurrence) or just "2009-03-23" for normal events.
  
2.1     
Properly added Marcus Sykes as a contributor  
Added a full calendar  
Added an #_EDITEVENT placeholder  
Added Brazialian Portuguese localization and some translatable strings
Categories are now displayed in the events table
Now weeks starts according to WP settings       
Moved the hide page option up for better access  
Attributes column was not created when the plugin was upgraded, fixed
Added comment field to the RSVP form and #_COMMENT placeholder in RSVP email templates 
Added customizable title to small calendar      
Removed php short tags
Bugfix:there was a time bug
Bugfix: event_time not taken into consideration in ordering events, fixed
Bugfix: on calendar for days after 28  on the event calendar view
Bugfix: for events in days with single digit
Bugfix: events link in the calendar now work with permalink
Bugfix: today in next mont was not matched in the calendar 
Bugfix: _RESPPHONE was not matched in emails
Bugfix: fixed security vulnerability, which could lead to sql inject attacks
Bugfix: bloginfo('wpurl') instead of bloginfo('url')