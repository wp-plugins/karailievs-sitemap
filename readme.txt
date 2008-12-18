=== Karailiev's sitemap ===
Contributors: Valentin Karailiev
Tags: seo, sitemap, google, yahoo, msn, xml sitemap, xml
Requires at least: 2.5
Tested up to: 2.7
Stable tag: trunk
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=valentin%40karailiev%2enet&item_name=karailievs%2dsitemap&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8


This plugin adds a XML sitemap to your blog.
It's used to show all your pages and posts to the search engines like Google, Yahoo and MSN.


== Description ==
This plugin adds a XML sitemap to your blog.
It's used to show all your pages and posts to the search engines like Google, Yahoo and MSN.


= Changes in version 0.7.1: =
* Date bug fix


= Changes in version 0.7: =
* Google news sitemap added. It shows the posts from the last 3 days. Writable file named `sitemap-news.xml` has to be created
* Database query optimization
* Time format change


= Changes in version 0.6: =
* Users can determine the priority of posts, pages, categories and tags
* Users can determine the change frequency of posts, pages, categories and tags


= Changes in version 0.5.1: =
* Fixes some compability problems


= Changes in version 0.5: =
* The plugin pings Google on rebuild (no more than once per hour (recommended by Google))
* Users can change the sitemap's location


= Changes in version 0.4: =
* Restore lost MySQL server connection (reported by [Matteo](http://www.italiasw.com/))
* Categories added to the sitemap
* Tags added to the sitemap


= Changes in version 0.3: =
* There is an option to turn on or off sitemap rebuilding when comments are changed (submit/edit/delete)
* There is an option to turn on or off sitemap rebuilding when attachments are changed (upload/edit/delete)


= Changes in version 0.2: =
* Sitemap file has the usual name `sitemap.xml`
* Plugin checks if the file exists and if it's writable
* Sitemap is generated only when content changes (new/edit/delete post/page/comment/attachment)
* Sitemap generates URLs according the permalinks settings

== Installation ==
1. Upload `karailievs-sitemap` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a file named sitemap.xml in your blog folder. The file must be writable for the web server. See more instructions on [plugin's homepage](http://www.karailiev.net/karailievs-sitemap/)
1. Turn the sitemap on via the Settings -> Sitemap screen
1. Open your sitemap to test it (e.g. http://www.karailiev.net/sitemap.xml)


== Frequently Asked Questions ==
= Does this plugin ping Google on change? =
Yes, it does.

= Can I mess everything up? =
I've tried to make the plugin easy to use and configure.
Until you do not change the advanced settings it will work (good or bad).
Changing the advanced settings may have unexpected (and unwanted) consequences.