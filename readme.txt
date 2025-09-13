# WPComments
Contributors: wenpai
Tags: comments
Requires at least: 5.9
Tested up to: 6.7
Stable tag: 2.0.0
License: GPLv2 or later
Requires PHP: 7.4

WPComments everywhere in WordPress.

## Description

WPComments is a comprehensive WordPress plugin that completely disables comments functionality across your entire WordPress site. It provides a clean and efficient way to remove all comment-related features without affecting your site's performance.

**Key Features:**

* Complete comment system disable
* REST API comment endpoint protection
* XML-RPC comment method blocking
* Dashboard comment widget removal
* Block editor comment blocks removal
* Multisite network support with site-level control
* Internationalization ready
* No configuration required - works out of the box

**What it does:**

* Stop comments and pingbacks on all existing and future content.
* Remove "Discussion" from Settings in the admin menu.
* Remove "Comments" from the admin menu.
* Remove the comments bubble from the admin bar.
* Remove "Recent Comments" from the site activity dashboard widget.
* Remove the comments feed link.
* Unregister all core comment blocks in the editor.
* Remove "Manage Comments" from My Sites on multisite. (If network activated.)

No configuration necessary (or available).

If you find something we missed, [please let us know](https://github.com/WenPai-org/wpcomments)!

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wpcomments` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. The plugin works automatically - no configuration needed!

For multisite installations, network administrators can control comment settings at the site level through Network Admin > Settings > General.

## Frequently Asked Questions

### Does this plugin require any configuration?

No! The plugin works automatically once activated. For multisite installations, network administrators have additional control options.

### Will this affect my existing comments?

No, existing comments remain in your database. The plugin simply prevents new comments and hides comment-related interface elements.

### Is this compatible with multisite?

Yes! Network administrators can control comment settings for individual sites through the network admin interface.

## Changelog

### 2.0.0

* Fix a fatal PHP error when a comment query requests a count.
* Note: If you had manually removed our `comments_pre_query` filter for any reason,
  (you likely weren't), you'll need to adjust your code to account for the new
  namespaced function.
* Update development dependencies.

### 1.3.2

* No functional changes.
* Update development dependencies.
* Confirm WordPress 6.6 support.

### 1.3.1

* No functional changes.
* Replace `@wordpress/scripts` dependency with leaner configuration.
* Confirm WordPress 6.5 support.

### 1.3.0

* Remove "Manage Comments" from site menus under My Sites on multisite.
* Confirm WordPress 6.4 support.
* Update `wordpress/scripts` dependency to 26.15.0.
* Improve linting configuration.

### 1.2.1

* No functional changes, only custodial.
* Confirm WordPress 6.2 support.
* Update `wordpress/scripts` dependency to 26.1.0.
* Improve linting configuration.

### 1.2.0

* Update the list of blocks unregistered to include latest from Gutenberg and WordPress.
* Update `wordpress/scripts` to 24.6.0.
* Refactor how the list of blocks is managed to make future maintenance easier. Props [@wenpai](https://profiles.wordpress.org/wenpai/).
* Only unregister blocks that are registered. Props [@wenpai](https://profiles.wordpress.org/wenpai/).

### 1.1.1

* Unregister core comment blocks in the editor.

### 1.0.2

* Add automated GitHub deployments to wp.org.

### 1.0.1

* Add PHP namespace.

### 1.0.0

* Initial release.
