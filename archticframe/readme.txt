=== ArchticFrame ===
Contributors: archtic
Tags: archive, custom post types, gutenberg, acf, archive page
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create editable archive pages for custom post types using Gutenberg blocks or ACF.

== Description ==

ArchticFrame provides a structured way to build content-managed archive pages for custom post types.

Normally, WordPress archive pages are controlled entirely by theme templates.
This makes it difficult for editors to manage archive content or layouts without modifying theme files.

ArchticFrame solves this by introducing Archive Pages.

An Archive Page is an internal content object that represents the archive content for a custom post type.
Editors can build archive layouts using:

- Gutenberg blocks
- Advanced Custom Fields (ACF)
- custom metadata
- reusable blocks

These Archive Pages are automatically linked to their corresponding custom post type archives.

The archive content can then be rendered within archive templates using simple helper functions.

Archive Pages behave like editable landing page content while still allowing the archive to display its normal post listings.

== Key Concepts ==

ArchticFrame introduces a dedicated internal post type:

Archive Pages

Each Archive Page represents the content for a specific custom post type archive.

Examples:

- Projects → Archive Page
- Services → Archive Page
- Events → Archive Page

Archive Pages are automatically managed by the plugin and are intended to act as content sources for archive layouts.

== Features ==

- Dedicated Archive Page for each enabled custom post type
- Gutenberg block support for archive layouts
- Compatible with Advanced Custom Fields (ACF)
- Automatic archive page creation and management
- Archive Pages stored in a dedicated admin section
- Automatic linking between Archive Pages and post type archives
- Template override support for themes
- Simple helper functions for theme developers

== How It Works ==

1. Activate the plugin
2. Navigate to Archive Pages → Settings
3. Enable archive management for a custom post type
4. ArchticFrame automatically creates an Archive Page
5. Edit the Archive Page using Gutenberg or ACF
6. Render the archive content inside your archive template

The archive content can be placed above the post listing or anywhere within your archive layout.

== Archive Pages Admin ==

ArchticFrame adds a new admin section:

Archive Pages

This section lists all Archive Pages managed by the plugin.

Each Archive Page corresponds to a specific custom post type archive.

Archive Pages include a View Archive link which opens the actual archive URL.

Editors only manage the content of these pages — the archive relationships are handled automatically.

== Template Loading ==

When archive management is enabled for a custom post type, ArchticFrame attempts to load templates in the following order:

1. archtic-{post_type}.php in the active theme
2. archtic.php in the active theme
3. the plugin fallback template

Examples:

archtic-projects.php
archtic-services.php
archtic.php

This allows theme developers to fully customise archive layouts while maintaining a safe fallback.

== Helper Functions ==

ArchticFrame provides helper functions for theme developers.

Output archive content:

<?php archtic_content(); ?>

Get the archive object ID:

$post_id = archtic_id();

Get the archive title:

echo archtic_title();

Retrieve an ACF field from the archive:

echo archtic_field('subtitle');

These helpers allow themes to integrate archive content without directly querying the archive object.

== Archive Listings Shortcode ==

ArchticFrame includes a shortcode that allows archive listings to be displayed directly inside Archive Pages.

This allows editors to build custom archive layouts using Gutenberg blocks while still displaying the posts from the archive.

The shortcode automatically detects the current archive post type when used inside an ArchticFrame Archive Page.

= Basic Usage =

[archtic_listing]

This will output the archive posts for the current custom post type.

= Shortcode Attributes =

posts_per_page

Controls how many posts are displayed.

Default: 12

Example:

[archtic_listing posts_per_page="6"]


show

Controls which elements appear for each listing item.

Available options:

- image
- title
- excerpt
- content
- button

Default:

image,title,excerpt,button

Example:

[archtic_listing show="image,title,button"]


button_text

Changes the label used for the button when the button option is enabled.

Default:

Read more

Example:

[archtic_listing button_text="View project"]


link

Controls how items link to their individual posts.

Available values:

- button – only the button links to the post
- card – the entire item card is clickable
- both – card and button both link to the post
- none – no links are generated

Default:

button

Example:

[archtic_listing link="card"]


= HTML Structure =

The shortcode outputs the following structure to allow flexible styling:

<div class="archtic-listings">
  <div class="archtic-wrap">
    <div class="archtic-grid">
      <div class="archtic-col">
        <article class="archtic-item">
        </article>
      </div>
    </div>
  </div>
</div>

Themes can style the archive listings using the following classes:

- .archtic-listings
- .archtic-wrap
- .archtic-grid
- .archtic-col
- .archtic-item
- .archtic-item__image
- .archtic-item__title
- .archtic-item__excerpt
- .archtic-item__content
- .archtic-item__actions
- .archtic-item__button

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin through the Plugins screen.
3. Navigate to Archive Pages → Settings.
4. Enable archive management for the desired custom post types.
5. Edit the generated Archive Page and add archive content.

== Frequently Asked Questions ==

= Does this replace archive templates? =

No.
Themes still control archive layout.
ArchticFrame simply provides editable archive content.

= Can I use Advanced Custom Fields? =

Yes.
ACF fields can be added to Archive Pages and retrieved using helper functions.

= Can I customise archive templates? =

Yes.
Themes can provide custom templates such as archtic-{post_type}.php or archtic.php.

= What happens if archive management is disabled? =

The corresponding Archive Page will automatically be moved to the trash.

== Changelog ==

= 1.0.0 =
- Initial release
- Archive Pages system
- Gutenberg archive content support
- ACF helper integration
- Template override support
- Helper functions for archive content

== License ==

This plugin is licensed under the GPLv2 or later.

See: https://www.gnu.org/licenses/gpl-2.0.html