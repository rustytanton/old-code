WP Mothballer
=============

Converts a dynamic WordPress site to static HTML for archival purposes.

Prerequisites
-------------

1. A working [WordPress](http://wordpress.org) installation

2. [Apache Ant](http://ant.apache.org/) installed on your server

Preparing the site
------------------

1. Install the [Google Sitemap Generator plugin](http://wordpress.org/extend/plugins/google-sitemap-generator/) or an equivalent plugin to generate a [sitemap](http://www.sitemaps.org/protocol.html) for the WordPress site.

2. The sitemap.xml file will contain a full list of URLs for the site except for paginated date/category/tag archive pages. The Wordpress theme will probably need to be modified to list all links to posts in a date/category/tag archive on one page (an example child theme I used on my wife's site is in the [doc](doc) folder).

3. Disable comments on all posts and pages with this MySQL query so the comment form won't show: `UPDATE wp_posts SET comment_status="closed";`

4. Disable any plugins which require server-side calls from the front end (ex: AJAX polls) or which depend on external URLs (ex: Google Analytics).

Usage
-----

1. Clone this repo into the WordPress root directory (in the same directory as the sitemap.xml file).

2. Change to the wp-mothballer directory and run the `ant` command.

3. Back up the WordPress installation, then replace all the files in your WordPress directory with the contents of the dist directory. 

Everything in the WordPress folder will be included in the cached archive except for files with a .php extension. Rules in the .htaccess file will allow for keeping pretty permalinks with static HTML files.