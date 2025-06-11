<?php
defined('ABSPATH') || exit;

/**
 * Plugin Name: Better Elementor Globals
 * Requires Plugins: elementor
 * Description: Changes the variables names of custom global colors and custom global typographies to a more readable and reusable form.
 * Author: Patrick Heina
 * Version: 1.1.0
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
 * Update all Elementor posts that contain old global IDs.
 *
 * @param array $map Array with 'colors' and 'typography' mappings old => new.
 */
function beg_update_elementor_posts(array $map) {
    $posts = get_posts([
        'post_type'      => 'any',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_elementor_edit_mode',
                'compare' => 'EXISTS',
            ],
        ],
        'fields'         => 'ids',
    ]);

    foreach ($posts as $post_id) {
        $data = get_post_meta($post_id, '_elementor_data', true);
        if (empty($data)) {
            continue;
        }

        $new_data = $data;
        foreach ($map['colors'] ?? [] as $old => $new) {
            $new_data = str_replace($old, $new, $new_data);
        }
        foreach ($map['typography'] ?? [] as $old => $new) {
            $new_data = str_replace($old, $new, $new_data);
        }

        if ($new_data !== $data) {
            update_post_meta($post_id, '_elementor_data', $new_data);
        }
    }
}

/**
 * Sanitize global IDs of a kit and return an array of changed IDs.
 *
 * @param array $meta Kit meta.
 * @param array $map  Mapping array passed by reference.
 * @return array      Sanitized meta.
 */
function beg_sanitize_kit_meta(array $meta, array &$map) {
    if (!empty($meta['custom_colors'])) {
        foreach ($meta['custom_colors'] as $index => $custom_color) {
            $new_id = str_replace('-', '_', sanitize_title($custom_color['title']));
            if ($custom_color['_id'] !== $new_id) {
                $map['colors'][$custom_color['_id']] = $new_id;
                $meta['custom_colors'][$index]['_id'] = $new_id;
            }
        }
    }

    if (!empty($meta['custom_typography'])) {
        foreach ($meta['custom_typography'] as $index => $custom_typography) {
            $new_id = str_replace('-', '_', sanitize_title($custom_typography['title']));
            if ($custom_typography['_id'] !== $new_id) {
                $map['typography'][$custom_typography['_id']] = $new_id;
                $meta['custom_typography'][$index]['_id'] = $new_id;
            }
        }
    }

    return $meta;
}

add_action('elementor/document/after_save', function ($document) {
    if ('Elementor\Core\Kits\Documents\Kit' === get_class($document)) {
        $meta = get_post_meta($document->get_main_id(), '_elementor_page_settings', true);
        $map  = [];
        $meta = beg_sanitize_kit_meta($meta, $map);

        if (!empty($map)) {
            update_post_meta($document->get_main_id(), '_elementor_page_settings', $meta);
            $stored_map = get_option('beg_id_map', []);
            $merged_map = array_merge_recursive($stored_map, $map);
            update_option('beg_id_map', $merged_map);
            beg_update_elementor_posts($map);
        }
    }

    return $document;
}, 20);

function beg_activate() {
    $kit_id = get_option('elementor_active_kit');
    if (empty($kit_id)) {
        return;
    }

    $meta = get_post_meta($kit_id, '_elementor_page_settings', true);
    $map  = [];
    $meta = beg_sanitize_kit_meta($meta, $map);

    if (!empty($map)) {
        update_post_meta($kit_id, '_elementor_page_settings', $meta);
        update_option('beg_id_map', $map);
        beg_update_elementor_posts($map);
        wp_update_post(['ID' => $kit_id]);
    }
}

function beg_deactivate() {
    $kit_id = get_option('elementor_active_kit');
    if (empty($kit_id)) {
        return;
    }

    $map = get_option('beg_id_map', []);
    if (empty($map)) {
        return;
    }

    $meta = get_post_meta($kit_id, '_elementor_page_settings', true);

    if (!empty($meta['custom_colors'])) {
        foreach ($meta['custom_colors'] as $index => $custom_color) {
            foreach ($map['colors'] as $old => $new) {
                if ($custom_color['_id'] === $new) {
                    $meta['custom_colors'][$index]['_id'] = $old;
                }
            }
        }
    }

    if (!empty($meta['custom_typography'])) {
        foreach ($meta['custom_typography'] as $index => $custom_typography) {
            foreach ($map['typography'] as $old => $new) {
                if ($custom_typography['_id'] === $new) {
                    $meta['custom_typography'][$index]['_id'] = $old;
                }
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
