# WenPai Comments
Contributors: wenpai
Tags: comments
Requires at least: 5.9
Tested up to: 7.0
Stable tag: 2.0.0
License: GPLv2 or later
Requires PHP: 7.4

Modular comment controls and moderation tools for WordPress.

## Description

WPComments can either disable the WordPress comment system or selectively add comment-management features. All optional enhancements are off until an administrator enables them.

**Key Features:**

* Complete comment disable mode, including REST API and XML-RPC comment entry points
* Private and sticky comments
* Comment validation and character limits
* Comment reports with hashed client identifiers, throttling, and retention cleanup
* Reply subscription and administrator notification email
* Frequently used moderation replies and edit history
* Comment-author role badges and optional website-field removal
* Multisite network controls with per-site storage

**Disable mode:**

* Stop comments and pingbacks on all existing and future content.
* Remove "Discussion" from Settings in the admin menu.
* Remove "Comments" from the admin menu.
* Remove the comments bubble from the admin bar.
* Remove "Recent Comments" from the site activity dashboard widget.
* Remove the comments feed link.
* Unregister all core comment blocks in the editor.
* Remove "Manage Comments" from My Sites on multisite. (If network activated.)

Use Settings > WPComments to enable only the features required for the site. On multisite, network administrators can control or enforce selected settings.

If you find something we missed, [please let us know](https://github.com/WenPai-org/wpcomments)!

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wpcomments` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Open Settings > WPComments and enable the required features. To remove comments entirely, enable Disable Comments.

For multisite installations, network administrators can control comment settings at the site level through Network Admin > Settings > General.

## Frequently Asked Questions

### Does this plugin require any configuration?

Optional features are disabled by default. Enable only the features needed by the site. Multisite network administrators can control selected settings across sites.

### Will this affect my existing comments?

No, existing comments remain in your database. The plugin simply prevents new comments and hides comment-related interface elements.

### Is this compatible with multisite?

Yes! Network administrators can control comment settings for individual sites through the network admin interface.

## Changelog

### Unreleased

* Align feature loading with saved site and network settings.
* Repair the report table schema, frontend report action, throttling, and retention cleanup.
* Repair character limiter assets and settings, role badges, and administrator email notifications.
* Add storage initialization for new multisite sites and clear scheduled cleanup on deactivation.
* Preserve plugin data on uninstall unless deletion is explicitly enabled.
* Harden request validation, output escaping, and large subscription-list queries.

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
