# Better CSS Globals for Elementor

This plugin changes the class names of the custom global colors and custom global typographies to a more readable form that is more reusable in CSS.

## Description

This plugin changes the variables names of the custom global colors and custom global typographies to a more readable form that is more reusable in CSS.

For example, if you have a custom global color called "Testimonial Container" it changes the variable name from, for example, '--e-global-color-ab1337' to '--e-global-color-testimonial_container'.

The same applies to global custom typographies. For example, '--e-global-typography-df1337-font-size' becomes '--e-global-typography-eye_catcher-font-size' if the typography is named 'Eye Catcher'.'--e-global-color-ad9560e' in '--e-global-color-testimonial-container'.

## Installation

1. Download the [latest release](https://github.com/pand0r/better-global-classnames/releases)
2. Upload into your WordPress
3. Activate the plugin through the 'Plugins' screen in WordPress

## Changelog

### 1.2.0
- Added a plugin settings page under `Settings -> Better CSS Globals for Elementor`.
- Added separate toggles for container background class marker and data attribute marker.
- Added plugin textdomain loading and language files (`.pot`, `de_DE.po`, `de_DE.mo`).
- Improved performance for Elementor post updates by processing in paged batches and reducing replacement passes.
- Improved map merge robustness (avoids `array_merge_recursive` nesting issues in stored ID maps).
- Hardened data handling for invalid/empty kit meta structures.
- Raised minimum PHP version to 8.1 and modernized internal type declarations for clearer contracts.

### 1.1.0
- Added an `ABSPATH` guard to prevent direct access.
- Declared Elementor as a required plugin.
- Introduced an activation hook that renames global IDs and updates the active kit.
- Added a deactivation hook restoring the original IDs and posts.
- Preserved global ID mappings across activations.
- Updated the license text and "tested up to" information.
- General code cleanup.

### 1.0.2
- Renamed Plugin

### 1.0.1
- Bugfix, added missing str_replace

### 1.0.0
- Initial release

## License

This plugin is licensed under the GPLv3 or Later License. For more information, please visit: https://www.gnu.org/licenses/gpl-3.0.html
