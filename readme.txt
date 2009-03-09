=== Events Manager ===  
Contributors: nutsmuggler  
Tags: events, manager, calendar, gigs, concert, maps, geotagging  
Requires at least: 2.5.1   
Tested up to: 2.6   
Stable tag: 1.0.1   
Donate link: http://davidebenini.it/wordpress-plugins/

Manage events and display them in your blog. Includes recurring events, location management, calendar, Google map integration, RSVP. Works with widgets, template tags and shortcodes.
             
== Description ==

Events Manager is a plugin to manage events such as music gigs, art expositions, or even job meetings. Events Manager inserts an *Events* page in the *Manage* menu of Wordpress Administration, to let you insert, modify and delete events. You can describe events specifying their date and location, and also add a few notes. You can then add events list, calendars and description to your blog using a sidebar widget; if you're web designer you can simply employ the template tags provided by Events Manager. 

Events Manager integrates with Google Maps; thanks the geocoding, Events Manager can find the location of your events, and accordingly display a map. To enable Google Maps integration, you need a Google maps API key, which you can obtain freely at the [Google Maps API Signup Page](http://code.google.com/apis/maps/signup.html).

Events Manager provides also a RSS feed, to keep your subscribers updated about the events you're organising.

Events manager is fully customisable; you can customise the amount of data displayed and their format in events lists, pages and in the RSS feed. You can choose to show or hide the events page, and change its title.   

Events Manager is fully localisable. I have added an Italian localisation, and I'd welcome any translator willing to localise this plugin into his mother tongue.

== Installation ==


1. Upload the `events-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add events list or calendars following the instructions in the Usage section.

== Usage == 

After the installation, Events Manager adds two pages to your Wordpress Administration.

* Manage/Events is the page where you add, delete or modify events. You will see three pre-installed events in this page. Delete them and add your events own events. Choose between the visualisation of present/past/all events.
* Settings/Events Manager is where you set the Events Manager options. The page fields contain a description of their use; for more information, see *Formatting the events*.

Events list and calendars can be added to your blogs through widgets or template tags.

= Widgets =

Add the Events List or Events Calendar widgets to any of your sidebar. To do so, your theme must be [widget-ready](http://automattic.com/code/widgets/). You can change the title of both widgets. Moreover, you can adjust the formats of the events of Events List according to your need; see *Formatting Events*.
                           

= Template tags =  

If you're into theming, you should probably use the template tags provided by Events Manager. Here's a comprehensive list.

`<?php dbem_get_events_list(limit, scope, order, format,display); ?>`  

Prints or returns a list of the events. Accepts up to five optional parameters:      

* `limit` indicates the maximum number of events to display. Default is 3.  
* `scope` indicates lets you choose whether to show `future`, `past` or `all` events. Default is `future`.
* `order` indicates indicates the order of the events. Choose between ASC (ascendant, default) and DESC (descendant).
* `format`: the format of each item. If not specified, Events Manager will use the format specified in the *Default event list format* setting of the setting page.
* `display` indicates whether the list should be printed (`true`) or just returned (`false`). This option should be ignored, unless you know what you are doing.

Example: `dbem_get_events_list(5, "all", "DESC")` will print a list of the latest 5 events, including past ones, in a descendant order.

 
`<?php dbem_get_calendar(); ?>`

Prints the current month calendar, highlighting any event and linking to it. Accepts no parameters.

`<?php dbem_get_events_page(justurl) ?>`

Prints a link to the events page. If you set the optional `justurl` property to `true`, the function only prints the URL of the events page. 

`<?php dbem_rss_link(justurl) ?>`
Prints a the link to the events RSS. If you set the optional `justurl` property to `true`, the function only prints the RSS URL. 

= Conditional template tags =

These tags return true or false, and are useful to structure your themes.  

`<?php dbem_are_events_available(scope) ?>` 
Returns true if events are available in `scope`. The default value of `scope` is future.

`<?php dbem_is_events_page() ?>`
Returns true if the page loaded corresponds to the events page.

`<?php dbem_is_single_event_page() ?>`
Returns true if the page loaded corresponds to a single event page. 

`<?php dbem_is_multiple_events_page() ?>`
Returns true if the page loaded corresponds the multiple events page.   

== Formatting the events ==

Events Manager lets you choose the format of the events displayed in your list and pages. Navigate to Settings/Events Manager and set the format of events in list; the format of the list widget is set directly in the widget settings.   

The syntax of events format is quite simple. Basically, just write your html code in the usual way. Then you can add a number of placeholders corresponding to the data of the event. They are:

* `#_NAME` displays the name of the event
* `#_LOCATION` displays the location (theatre, pub, etc)
* `#_ADDRESS` displays the address
* `#_TOWN` displays the town 
* `#_LINKEDNAME` displays the event name with a link to the event page
* `#_URL` simply prints the events URL. You can use this placeholder to build your own customised links


To add temporal information about the events, use [PHP syntax format characters](http://www.php.net/manual/en/function.date.php) with a # before them. For example:

* `#d` displays a Day of the month, with 2 digits with leading zeros
* `#m` displas short textual representation of a month, three letters (*jan* through *dec*)
* etc              

If you have enabled the Google Map integration, you can use #_MAP to display a map; this placeholder, of course, shouldn't generally be used for list items.
                                
== Google Maps Integration == 

To use Google Maps with Events Manager, you need a Goggle Map API key. Don't worry, it's free, you can get one [here](http://code.google.com/apis/maps/signup.html).

Once you have got you API key, go to *Settings/Events Manager*, insert you key in the *Google Maps API Key* field and set *Enable Google Maps integration?* to *Yes*.

Now you just need to put a the #_MAP placeholder in the *Default single event format*, and your map will show in the page dedicated to the event.   

To resize the map, simply tweak the `#event-map` in your css.

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

== Future features ==    

This is  a short list of the features that I intend to implement:

* Javascript datepicker
* End dates; events will have a beginning and (optionally) an ending

I have other ideas in the pipeline, but I'll stick to this ones and implement them first. 

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