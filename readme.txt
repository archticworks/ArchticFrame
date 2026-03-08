=== ArchticFrame ===
Contributors: archtic
Tags: archive, custom post types, gutenberg, acf, archive builder, archive page
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ArchticFrame adds a dedicated archive page to any custom post type, allowing archive pages to be built with Gutenberg blocks or ACF fields.

== Description ==

ArchticFrame provides a structured way to build editable archive pages for custom post types.

Normally, archive pages are controlled entirely by theme templates. This makes it difficult for content editors to manage archive layouts or add custom content without modifying theme files.

ArchticFrame solves this by introducing a **managed archive page** for each custom post type.

This archive page behaves like a normal post in the editor and can contain:

• Gutenberg blocks  
• Advanced Custom Fields (ACF) fields  
• custom metadata  
• reusable blocks  

The content of this archive page can be rendered inside archive templates using simple helper functions.

The plugin automatically manages the lifecycle of these archive pages, ensuring they:

• never appear in archive loops  
• cannot be accessed directly  
• stay synchronised with plugin settings  
• are restored or removed when archive management changes  

This allows archive pages to function like **content-managed landing pages** while still displaying the standard archive page listings.

== Features ==

• Dedicated archive page for each enabled custom post type
• Gutenberg block support for archive layouts
• Compatible with Advanced Custom Fields
• Automatic archive page creation and management
• Archive page excluded from archive loops
• Automatic redirect if archive page is accessed directly
• Theme template override support
• Simple helper functions for theme developers

== How It Works ==

1. Activate the plugin
2. Navigate to **Settings → ArchticFrame**
3. Enable archive management for a custom post type
4. The plugin creates a managed archive page
5. Edit this post using Gutenberg or ACF
6. Render the archive content inside your archive template

The archive page content can be placed above the archive loop or anywhere within your archive layout.

== Template Loading ==

When archive management is enabled for a custom post type, ArchticFrame looks for templates in the following order:

1. `archtic-{post_type}.php` in the active theme  
2. `archtic.php` in the active theme  
3. the plugin fallback template  

Example:

`archtic-services.php`
`archtic-projects.php`
`archtic.php`

This allows theme developers to fully customise archive layouts while maintaining a safe fallback.

== Helper Functions ==

ArchticFrame provides helper functions for theme developers.

Output archive content:

`<?php archtic_content(); ?>`

Get the archive page ID:

`$post_id = archtic_id();`

Get the archive page title:

`echo archtic_title();`

Retrieve an ACF field from the archive page:

`echo archtic_field('subtitle');`

These helpers allow themes to integrate archive content without directly querying the archive page.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Navigate to **Settings → ArchticFrame**.
4. Enable archive management for the desired custom post types.
5. Edit the generated archive page and add archive content.

== Frequently Asked Questions ==

= Does this replace archive templates? =

No. Themes still control archive layout.  
ArchticFrame simply provides editable content for archive pages.

= Can I use Advanced Custom Fields? =

Yes. ACF fields can be added to the archive page and retrieved using the helper functions.

= Will the archive page appear in archive loops? =

No. ArchticFrame automatically excludes the archive page from archive queries.

= Can users access the archive page directly? =

No. Direct access is automatically redirected to the archive URL.

= What happens if archive management is disabled? =

The archive page will be moved to the trash automatically.

== Changelog ==

= 1.0.0 =
• Initial release
• Managed archive pages
• Gutenberg archive content support
• ACF helper integration
• Archive loop exclusion
• Archive redirect protection
• Template override support

== License ==

This plugin is licensed under the GPLv2 or later.

See: https://www.gnu.org/licenses/gpl-2.0.html