=== Better CSS Globals for Elementor ===
Contributors: pand0r
Tags: elementor, elementor addons, elementor global settings, reusability, css variables, css classes, development
Requires at least: 5.9
Tested up to: 6.9.1
Stable tag: 1.2.0
Requires PHP: 8.1
Requires Plugins: elementor
License: GPLv3 or Later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Changes the variables names of custom global colors and custom global typographies to a more readable and reusable form.

== Description ==

This plugin changes the variables names of the custom global colors and custom global typographies to a more readable form that is more reusable in CSS.

For example, if you have a custom global color called "Testimonial Container" it changes the variable name from, for example, '--e-global-color-ab1337' to '--e-global-color-testimonial_container'.

The same applies to global custom typographies. For example, '--e-global-typography-df1337-font-size' becomes '--e-global-typography-eye_catcher-font-size' if the typography is named 'Eye Catcher'.

The plugin also supports optional frontend markers for Elementor containers with global background colors:
- CSS class marker (`bg-global-*`)
- data attribute marker (`data-bg-global`)

Both marker types can be configured independently on the plugin settings page.

== Changelog ==
= 1.2.0 =
* Added a plugin settings page under `Settings -> Better CSS Globals for Elementor`.
* Added separate toggles for container background class marker and data attribute marker.
* Added plugin textdomain loading and language files (`.pot`, `de_DE.po`, `de_DE.mo`).
* Improved performance for Elementor post updates by processing in paged batches and reducing replacement passes.
* Improved map merge robustness (avoids `array_merge_recursive` nesting issues in stored ID maps).
* Hardened data handling for invalid/empty kit meta structures.
* Raised minimum PHP version to 8.1 and modernized internal type declarations for clearer contracts.

= 1.1.0 =
* Added an ABSPATH guard to prevent direct access.
* Declared Elementor as a required plugin.
* Introduced an activation hook renaming global IDs and updating the active kit.
* Added a deactivation hook restoring original IDs and posts.
* Preserved global ID mappings across activations.
* Updated the license text and "tested up to" information.
* General code cleanup.

= 1.0.2 =
* Renamed Plugins

= 1.0.1 =
* Bugfix: added missing str_replace

= 1.0.0 =
* initial release
