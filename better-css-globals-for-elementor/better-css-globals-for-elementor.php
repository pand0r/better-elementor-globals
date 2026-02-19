<?php
defined('ABSPATH') || exit;

/**
 * Plugin Name: Better CSS Globals for Elementor
 * Requires Plugins: elementor
 * Description: Changes the variables names of custom global colors and custom global typographies to a more readable and reusable form.
 * Text Domain: better-elementor-globals
 * Domain Path: /languages
 * Author: Patrick Heina
 * Version: 1.2.0
 * Requires PHP: 8.1
 * Author URI: https://der-panda.de
 *
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * This plugin is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or any later version.
 */

/**
 * Load plugin text domain.
 */
function beg_load_textdomain(): void {
    load_plugin_textdomain('better-elementor-globals', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('plugins_loaded', 'beg_load_textdomain');


/**
 * Update all Elementor posts that contain old global IDs.
 *
 * @param array $map Array with 'colors' and 'typography' mappings old => new.
 */
function beg_update_elementor_posts(array $map): void {
    $normalized_map = beg_normalize_id_map($map);
    $replacements   = [];

    foreach (['colors', 'typography'] as $type) {
        foreach ($normalized_map[$type] as $old => $new) {
            if ($old !== $new) {
                $replacements[$old] = $new;
            }
        }
    }

    if (empty($replacements)) {
        return;
    }

    $paged          = 1;
    $posts_per_page = 200;

    do {
        $posts = get_posts([
            'post_type'              => 'any',
            'post_status'            => 'any',
            'posts_per_page'         => $posts_per_page,
            'paged'                  => $paged,
            'fields'                 => 'ids',
            'meta_query'             => [
                [
                    'key'     => '_elementor_data',
                    'compare' => 'EXISTS',
                ],
            ],
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
        ]);

        foreach ($posts as $post_id) {
            $data = get_post_meta($post_id, '_elementor_data', true);
            if (!is_string($data) || '' === $data) {
                continue;
            }

            $new_data = strtr($data, $replacements);

            if ($new_data !== $data) {
                update_post_meta($post_id, '_elementor_data', $new_data);
            }
        }

        $paged++;
    } while (!empty($posts));
}

/**
 * Sanitize global IDs of a kit and return an array of changed IDs.
 *
 * @param mixed $meta Kit meta.
 * @param array $map  Mapping array passed by reference.
 * @return array      Sanitized meta.
 */
function beg_sanitize_kit_meta(array|string $meta, array &$map): array {
    if (!is_array($meta)) {
        return [];
    }

    if (!empty($meta['custom_colors']) && is_array($meta['custom_colors'])) {
        foreach ($meta['custom_colors'] as $index => $custom_color) {
            if (!is_array($custom_color) || empty($custom_color['_id'])) {
                continue;
            }

            $new_id = str_replace('-', '_', sanitize_title($custom_color['title'] ?? ''));
            if ('' !== $new_id && $custom_color['_id'] !== $new_id) {
                $map['colors'][$custom_color['_id']] = $new_id;
                $meta['custom_colors'][$index]['_id'] = $new_id;
            }
        }
    }

    if (!empty($meta['custom_typography']) && is_array($meta['custom_typography'])) {
        foreach ($meta['custom_typography'] as $index => $custom_typography) {
            if (!is_array($custom_typography) || empty($custom_typography['_id'])) {
                continue;
            }

            $new_id = str_replace('-', '_', sanitize_title($custom_typography['title'] ?? ''));
            if ('' !== $new_id && $custom_typography['_id'] !== $new_id) {
                $map['typography'][$custom_typography['_id']] = $new_id;
                $meta['custom_typography'][$index]['_id'] = $new_id;
            }
        }
    }

    return $meta;
}

/**
 * Normalize a global color ID for HTML attribute/class usage.
 *
 * @param mixed $id Raw global color ID.
 * @return string|null
 */
function beg_normalize_global_color_id(mixed $id): ?string {
    if (!is_string($id) && !is_numeric($id)) {
        return null;
    }

    $normalized = sanitize_key((string) $id);
    $normalized = str_replace('-', '_', $normalized);

    return '' === $normalized ? null : $normalized;
}

/**
 * Extract global color ID from Elementor global reference.
 * Example: globals/colors?id=blue
 *
 * @param mixed $reference Global reference string.
 * @return string|null
 */
function beg_extract_global_color_id_from_reference(mixed $reference): ?string {
    if (!is_string($reference) || '' === trim($reference)) {
        return null;
    }

    $query = parse_url($reference, PHP_URL_QUERY);
    if (is_string($query)) {
        parse_str($query, $query_args);
        if (!empty($query_args['id'])) {
            return beg_normalize_global_color_id($query_args['id']);
        }
    }

    if (preg_match('/(?:^|\/)globals\/colors\?id=([a-zA-Z0-9_-]+)/', $reference, $matches)) {
        return beg_normalize_global_color_id($matches[1]);
    }

    return null;
}

/**
 * Extract global color ID from CSS var() value.
 * Example: var(--e-global-color-blue)
 *
 * @param mixed $value CSS value.
 * @return string|null
 */
function beg_extract_global_color_id_from_css_value(mixed $value): ?string {
    if (!is_string($value) || '' === trim($value)) {
        return null;
    }

    if (preg_match('/var\(\s*--e-global-color-([a-zA-Z0-9_-]+)/', $value, $matches)) {
        return beg_normalize_global_color_id($matches[1]);
    }

    return null;
}

/**
 * Sanitize checkbox option values.
 *
 * @param mixed $value Raw option value.
 * @return string
 */
function beg_sanitize_checkbox_option(mixed $value): string {
    return ('1' === (string) $value || true === $value || 'true' === (string) $value) ? '1' : '0';
}

/**
 * Normalize map value to scalar string.
 *
 * @param mixed $value Raw map value.
 * @return string|null
 */
function beg_normalize_map_value(mixed $value): ?string {
    while (is_array($value)) {
        $value = end($value);
    }

    if (!is_string($value) && !is_numeric($value)) {
        return null;
    }

    $value = trim((string) $value);
    return '' === $value ? null : $value;
}

/**
 * Normalize ID map structure.
 *
 * @param mixed $map Raw map value.
 * @return array
 */
function beg_normalize_id_map(mixed $map): array {
    $normalized = ['colors' => [], 'typography' => []];
    if (!is_array($map)) {
        return $normalized;
    }

    foreach (array_keys($normalized) as $type) {
        if (empty($map[$type]) || !is_array($map[$type])) {
            continue;
        }

        foreach ($map[$type] as $old => $new) {
            $old_value = beg_normalize_map_value($old);
            $new_value = beg_normalize_map_value($new);

            if (null === $old_value || null === $new_value) {
                continue;
            }

            $normalized[$type][$old_value] = $new_value;
        }
    }

    return $normalized;
}

/**
 * Merge two ID maps.
 *
 * @param mixed $base_map Base map.
 * @param mixed $new_map  New map.
 * @return array
 */
function beg_merge_id_maps(mixed $base_map, mixed $new_map): array {
    $base = beg_normalize_id_map($base_map);
    $new  = beg_normalize_id_map($new_map);

    foreach (['colors', 'typography'] as $type) {
        foreach ($new[$type] as $old => $replacement) {
            $base[$type][$old] = $replacement;
        }
    }

    return $base;
}

/**
 * Check if container background class marker is enabled.
 *
 * @return bool
 */
function beg_is_container_background_marker_class_enabled(): bool {
    return '1' === get_option('beg_enable_container_bg_marker_class', '1');
}

/**
 * Check if container background data attribute marker is enabled.
 *
 * @return bool
 */
function beg_is_container_background_marker_data_enabled(): bool {
    return '1' === get_option('beg_enable_container_bg_marker_data', '1');
}

/**
 * Migrate legacy marker option to split class/data options.
 */
function beg_maybe_migrate_marker_options(): void {
    $legacy_option = get_option('beg_enable_container_bg_marker', null);
    $legacy_value  = beg_sanitize_checkbox_option(null === $legacy_option ? '1' : $legacy_option);

    if (false === get_option('beg_enable_container_bg_marker_class', false)) {
        add_option('beg_enable_container_bg_marker_class', $legacy_value);
    }

    if (false === get_option('beg_enable_container_bg_marker_data', false)) {
        add_option('beg_enable_container_bg_marker_data', $legacy_value);
    }
}

/**
 * Register Better Elementor Globals settings.
 */
function beg_register_settings(): void {
    beg_maybe_migrate_marker_options();

    register_setting(
        'beg_settings',
        'beg_enable_container_bg_marker_class',
        [
            'type'              => 'string',
            'sanitize_callback' => 'beg_sanitize_checkbox_option',
            'default'           => '1',
        ]
    );

    register_setting(
        'beg_settings',
        'beg_enable_container_bg_marker_data',
        [
            'type'              => 'string',
            'sanitize_callback' => 'beg_sanitize_checkbox_option',
            'default'           => '1',
        ]
    );
}

/**
 * Add plugin options page.
 */
function beg_add_options_page(): void {
    add_options_page(
        __('Better CSS Globals for Elementor', 'better-elementor-globals'),
        __('Better CSS Globals for Elementor', 'better-elementor-globals'),
        'manage_options',
        'better-elementor-globals',
        'beg_render_options_page'
    );
}

/**
 * Render plugin options page.
 */
function beg_render_options_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $marker_class_enabled = get_option('beg_enable_container_bg_marker_class', '1');
    $marker_data_enabled  = get_option('beg_enable_container_bg_marker_data', '1');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Better CSS Globals for Elementor', 'better-elementor-globals'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('beg_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php echo esc_html__('Container Background Marker', 'better-elementor-globals'); ?>
                        </th>
                        <td>
                            <label for="beg_enable_container_bg_marker_class">
                                <input type="hidden" name="beg_enable_container_bg_marker_class" value="0" />
                                <input
                                    type="checkbox"
                                    id="beg_enable_container_bg_marker_class"
                                    name="beg_enable_container_bg_marker_class"
                                    value="1"
                                    <?php checked('1', $marker_class_enabled); ?>
                                />
                                <?php echo esc_html__('Adds a CSS class with the global background color ID (e.g. bg-global-blue). Only added when the container background uses a global color.', 'better-elementor-globals'); ?>
                            </label>
                            <br />
                            <label for="beg_enable_container_bg_marker_data">
                                <input type="hidden" name="beg_enable_container_bg_marker_data" value="0" />
                                <input
                                    type="checkbox"
                                    id="beg_enable_container_bg_marker_data"
                                    name="beg_enable_container_bg_marker_data"
                                    value="1"
                                    <?php checked('1', $marker_data_enabled); ?>
                                />
                                <?php echo esc_html__('Adds a data attribute with the global background color ID (e.g. data-bg-global="blue"). Only added when the container background uses a global color.', 'better-elementor-globals'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Add container background global marker to wrapper attributes.
 *
 * @param mixed $element Elementor element instance.
 */
function beg_add_container_background_marker(mixed $element): void {
    $add_class_marker = beg_is_container_background_marker_class_enabled();
    $add_data_marker  = beg_is_container_background_marker_data_enabled();

    if (!$add_class_marker && !$add_data_marker) {
        return;
    }

    if (
        !is_object($element) ||
        !method_exists($element, 'get_settings_for_display') ||
        !method_exists($element, 'add_render_attribute')
    ) {
        return;
    }

    $settings = $element->get_settings_for_display();
    if (!is_array($settings) || empty($settings)) {
        return;
    }

    $global_color_id = null;

    if (!empty($settings['__globals__']) && is_array($settings['__globals__'])) {
        $global_reference = $settings['__globals__']['background_color'] ?? null;
        $global_color_id = beg_extract_global_color_id_from_reference($global_reference);
    }

    if (!$global_color_id) {
        $global_color_id = beg_extract_global_color_id_from_css_value($settings['background_color'] ?? null);
    }

    if (!$global_color_id) {
        return;
    }

    if ($add_data_marker) {
        $element->add_render_attribute('_wrapper', 'data-bg-global', $global_color_id);
    }

    if ($add_class_marker) {
        $element->add_render_attribute('_wrapper', 'class', 'bg-global-' . $global_color_id);
    }
}

add_action('init', 'beg_maybe_migrate_marker_options');
add_action('admin_init', 'beg_register_settings');
add_action('admin_menu', 'beg_add_options_page');
add_action('elementor/frontend/container/before_render', 'beg_add_container_background_marker', 20);

add_action('elementor/document/after_save', function ($document) {
    if ($document instanceof \Elementor\Core\Kits\Documents\Kit) {
        $meta = get_post_meta($document->get_main_id(), '_elementor_page_settings', true);
        $map  = [];
        $meta = beg_sanitize_kit_meta($meta, $map);

        if (!empty($map)) {
            update_post_meta($document->get_main_id(), '_elementor_page_settings', $meta);
            $stored_map = beg_normalize_id_map(get_option('beg_id_map', []));
            $merged_map = beg_merge_id_maps($stored_map, $map);
            update_option('beg_id_map', $merged_map);
            beg_update_elementor_posts($map);
        }
    }

}, 20);

function beg_activate(): void {
    beg_maybe_migrate_marker_options();

    $kit_id = get_option('elementor_active_kit');
    if (empty($kit_id)) {
        return;
    }

    $meta = get_post_meta($kit_id, '_elementor_page_settings', true);
    $map  = [];
    $meta = beg_sanitize_kit_meta($meta, $map);

    if (!empty($map)) {
        update_post_meta($kit_id, '_elementor_page_settings', $meta);
        $normalized_map = beg_normalize_id_map($map);
        update_option('beg_id_map', $normalized_map);
        beg_update_elementor_posts($normalized_map);
        wp_update_post(['ID' => $kit_id]);
    }
}

function beg_deactivate(): void {
    $kit_id = get_option('elementor_active_kit');
    if (empty($kit_id)) {
        return;
    }

    $map = beg_normalize_id_map(get_option('beg_id_map', []));
    if (empty($map['colors']) && empty($map['typography'])) {
        return;
    }

    $meta = get_post_meta($kit_id, '_elementor_page_settings', true);
    if (!is_array($meta)) {
        return;
    }

    if (!empty($meta['custom_colors']) && is_array($meta['custom_colors'])) {
        $color_reverse_map = array_flip($map['colors']);

        foreach ($meta['custom_colors'] as $index => $custom_color) {
            if (!is_array($custom_color) || empty($custom_color['_id'])) {
                continue;
            }

            if (isset($color_reverse_map[$custom_color['_id']])) {
                $meta['custom_colors'][$index]['_id'] = $color_reverse_map[$custom_color['_id']];
            }
        }
    }

    if (!empty($meta['custom_typography']) && is_array($meta['custom_typography'])) {
        $typography_reverse_map = array_flip($map['typography']);

        foreach ($meta['custom_typography'] as $index => $custom_typography) {
            if (!is_array($custom_typography) || empty($custom_typography['_id'])) {
                continue;
            }

            if (isset($typography_reverse_map[$custom_typography['_id']])) {
                $meta['custom_typography'][$index]['_id'] = $typography_reverse_map[$custom_typography['_id']];
            }
        }
    }

    update_post_meta($kit_id, '_elementor_page_settings', $meta);
    wp_update_post(['ID' => $kit_id]);

    $reverse = ['colors' => [], 'typography' => []];
    foreach ($map as $type => $pairs) {
        foreach ($pairs as $old => $new) {
            $reverse[$type][$new] = $old;
        }
    }

    beg_update_elementor_posts($reverse);

    delete_option('beg_id_map');
}

register_activation_hook(__FILE__, 'beg_activate');
register_deactivation_hook(__FILE__, 'beg_deactivate');
