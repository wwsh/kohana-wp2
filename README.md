# Kohana-WP2

WordPress 4+ plugin to execute a Kohana 3.x MVC framework route and inject
the output result into a WordPress page, post or widget.

Originally licensed under [GPL2](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html) by the author to meet the
requirement for a WordPress plugin.
Reworked version keeps same license.

## Description

Kohana-WP2 allows to embed Kohana applications, using the final Kohana (3.3.x) to WordPress pages. We even supplied an AJAX bridge, which makes it possible to route AJAX calls from WordPress into Kohana and respond to it.

## Installation

You can follow the original installation instructions, found [here](https://github.com/mgutz/kohana-wp/blob/master/README.md).

Please place your entire application code inside the folder:
> /wp-content/kohana/site/

## Directory structure

Directory structure for applications follows the convention of Kohana MVC applications. Note, that this section is modified and differs from the original Kohana-WP plugin.

    WORDPRESS_SITE/
        wp-content/
            kohana/
                site/                      #=> non-member end-user tier (premium/ is another internal tier at my startup)
                    application/
                        classes/            #=> controllers, models
                        ...
                    modules/                #=> app modules (all are loaded, suffix with .off to disable)
                        auth.off/
                        mustache.off/
                        db/
                    public/                 #=> static assets
                    system/                 #=> Kohana MVC framework (optional but recommended)
                    views/                  #=> templates and code-behind classes
                ...
            plugins/
                kohana-wp2/                 #=> the plugin
                    application/            #=> classes to integrate with WordPress
                    modules/                #=> custom controller, views and helpers to faciliate creating applications
                    system/                 #=> default Kohana MVC framework

Do not forget to include all specific modules into the plugins/kohana-wp2/modules/ folder, which your Kohana app will use.

## Constants

    WORDPRESS_SITE/                         #=> ABSPATH
        wp-content/                         #=> WP_CONTENT_DIR
            kohana/                         #=> KOHANA_APPS_ROOT
                site
                    application/            #=> APPPATH
                        classes/
                    modules/                #=> MODPATH
                    public/
                    system/                 #=> SYSPATH
            plugins/
                kohana-wp/                  #=> KWP_DOCROOT
                    application/            #=> KWP_APPPATH
                        classes/
                    modules/                #=> KWP_MODPATH
                    public/
                    system/                 #=> KWP_SYSPATH
                
## Shortcode
Usage:
> [kohana_exec radiotoolkit/widgets_sandbox/show?_id=chat&channel=PUBLICCHAT&name=PUBLIC]

Explanation:
* First part is the original Kohana route (radiotoolkit app, widgets/sandbox controller, show method)
* ? part are the parameters to be passed statically as GET parameters into the code. So, the app will receive:

        $_GET['_id'] = 'chat';
        $_GET['channel'] = 'PUBLICCHAT';
        $_GET['name'] = 'PUBLIC';

## AJAX

An explicit "turbo loading" admin-ajax.php has been written to be used by your Kohana code. It has to tolerate a specific URL format, though. See KOHANA_AJAX_URL_TPL in the code for an URL template example and feel free to customize to fit your needs.
Link all AJAX calls into the admin-ajax.php and keep WP Ajax standards.

Detailed documentation may follow soon.
## Notice

This plugin code has not been following any coding standards and was modified for some specific demand with the intention never to be published. We decided otherwise, though, due to lacking support of the dying out Kohana 3.x and Kohana integration misery in the WP world. Hence this public upload.

## TODO
* Refactoring
* Documentation
* Really nice examples, especially on AJAX