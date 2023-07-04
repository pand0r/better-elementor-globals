<?php

/**
 * Plugin Name: Better Global Classnames for Elementor
 * Description: Changes the class names of custom global colors and custom global typographies to a more readable and reusable form.
 * Plugin URI: https://wordpress.org/plugins/better-global-classnames-for-elementor/
 * Author: Patrick Heina
 * Version: 1.0.1
 * Author URI: https://der-panda.de
 *
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * This plugin is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or any later version.
 *
 * Elementor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */


add_action('elementor/document/after_save', function ($document) {
    if ('Elementor\Core\Kits\Documents\Kit' === get_class($document)) {

        $meta = get_post_meta($document->get_main_id(), '_elementor_page_settings', true);

        if (true === isset($meta['custom_colors']) && false === empty($meta['custom_colors'])) {
            foreach ($meta['custom_colors'] as $index => $custom_color) {
                $meta['custom_colors'][$index]['_id'] = str_replace('-', '_', sanitize_title($custom_color['title']));
            }
        }

        if (true === isset($meta['custom_typography']) && false === empty($meta['custom_typography'])) {
            foreach ($meta['custom_typography'] as $index => $custom_typography) {
                $meta['custom_typography'][$index]['_id'] = str_replace('-', '_', sanitize_title($custom_typography['title']));
            }
        }

        update_post_meta($document->get_main_id(), '_elementor_page_settings', $meta);
    }
    return $document;
}, 20);
