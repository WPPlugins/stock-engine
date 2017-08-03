=== Stock Engine ===

Contributors: Relevad
Tags: custom stock table, stock engine, stock table, stocks, quotes, stock market, stock price, share prices, market changes, stock widget
Requires at least: 3.8.0
Tested up to: 4.5.2
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Stock Engine plugin allows you to place stock tables onto your site using shortcodes.


== Description ==

The Stock Engine allows your site admin to create an unlimited number of commercial quality stock ticker symbol tables with custom column naming, variable stock lists, color schemes and an automatic color update based on price change. 
Stock Engines can be placed anywhere on your site using shortcodes.

Features:

 * Choice of stocks
 * Pre-built skins/themes
 * Four different layouts
 * Appearance customizations: width, height, text & background color, number of stocks displayed at one time, dynamic coloring based on price
 * Display features: cell borders, row borders, preset column sorting, dynamic sorting, hover highlight of rows
 * CSS input for entire table (allows for alignment, borders, margins, padding, etc.)
 * Preview of entire table after saving on settings page

Requirements:

 * PHP version >= 5.3.0 (Dependent on 5.3 functionality. Plugin will not work without 5.3 or higher)
 * Jquery version 1.7 or higher (wordpress 4.1 ships with 1.11.1)
 * Ability to execute wordpress shortcodes in the location(s) you want to place stocks. (see installation)

This plugin was developed by Relevad Corporation. Authors: Artem Skorokhodov, Matthew Hively, Jeffrey Hively and Boris Kletser.

== Installation ==

1. Upload the 'stock-engine' folder to the '/wp-content/plugins/' directory

1. Activate the Stock Engine plugin through the Plugins menu in WordPress

1. Configure appearance and stock symbols in "Relevad Plugins" -> StockEngine

1. Place Shortcodes
 * Pages / Posts: 
  Add the shortcode `[stock-engine]` to where you want the table shown on your post/page.
  
 * Widgets: 
  Add `[stock-engine]` inside a Shortcode Widget or add `<?php echo do_shortcode('[stock-engine]'); ?>` inside a PHP Code Widget
  
 * Themes: 
  Add the PHP code `<?php echo do_shortcode('[stock-engine]'); ?>` where you want the table to show up.
  
 
  There are many plugins that enable shortcode or PHP in widgets. 
  Here are two great ones: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/) and [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)


== Frequently Asked Questions ==

= Can I get data for any company? =

The current version of the plugin supports almost any stock on NASDAQ or NYSE.

= How do I add stocks to the stock table? =

All stocks can be added in the Stock Engine settings page (Relevad Plugins -> StockEngine).

Choose or create a stock table from the list, and click the edit button to reach the configuration screen.

Type in your stock list separated by new lines in the Stocks input box.

= How do I place the shortcode into a widget? =

You need a plugin that enables shortcode or PHP widgets.

There are plenty of such plugins on the WordPress.org. 
These worked well for us: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/), [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)

Install and activate your desired shortcode or PHP widget plugin and add it to the desired sidebar/section (Appearance->Widgets)

If you added a shortcode widget, type in `[stock-engine]` inside it.

If you added a PHP widget, type in `<?php echo do_shortcode('[stock-engine]'); ?>` inside it.

That will display the table in the appropriate space.

= Can I place two tables with different formatting on one page? =

Yes, simply create a new shortcode from the shortcodes list table page (click add new), then place it's shortcode onto the page whereever you want. Each table can be formatted completely independently and even have their own individual stock lists.

= The table is too small / too big! Can I adjust the size? =

Yes. There are several ways to adjust the size of the table. You can change the layout, change the height (in px), or reduce the number of stocks displayed. Any of these options can be changed from the settings page (Relevad Plugins -> StockEngine -> Edit).


= Something's not working or I found a bug. What do I do? =

First, please make sure that your Stock Engine plugin is updated to the latest version, as well as any other Relevad plugins you may have installed.
If updating does not resolve your issue please contact plugins AT relevad DOT com
or
find this plugin on wordpress.org and contact us through the support tab.


== Screenshots ==

1. Example table

2. A portion of the settings screen

3. The settings screen has a preview of the current table

4. You can create as many different tables as you want

5. A few different styles of tables


== Changelog ==

= 1.0 =
Plugin released.

== Upgrade Notice ==

