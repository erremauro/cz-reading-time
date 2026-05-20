<?php
/**
 * Plugin Name: CZ Reading Time
 * Description: Calcola e visualizza automaticamente il tempo di lettura totale e per sezione nei singoli articoli.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: CZ
 */

if (!defined('ABSPATH')) {
    exit;
}

function czrt_get_option_key_words_per_minute() {
    return 'czrt_words_per_minute';
}

function czrt_get_default_words_per_minute() {
    return 250;
}

function czrt_normalize_words_per_minute($value) {
    $value = (int) $value;

    if ($value < 1) {
        return czrt_get_default_words_per_minute();
    }

    return $value;
}

function czrt_get_words_per_minute() {
    return czrt_normalize_words_per_minute(get_option(czrt_get_option_key_words_per_minute(), czrt_get_default_words_per_minute()));
}

function czrt_activate() {
    add_option(czrt_get_option_key_words_per_minute(), czrt_get_default_words_per_minute());
}
register_activation_hook(__FILE__, 'czrt_activate');

function czrt_register_settings() {
    register_setting(
        'czrt_settings',
        czrt_get_option_key_words_per_minute(),
        array(
            'type' => 'integer',
            'sanitize_callback' => 'czrt_normalize_words_per_minute',
            'default' => czrt_get_default_words_per_minute(),
        )
    );
}
add_action('admin_init', 'czrt_register_settings');

function czrt_add_settings_page() {
    add_options_page(
        __('CZ Reading Time', 'cz-reading-time'),
        __('CZ Reading Time', 'cz-reading-time'),
        'manage_options',
        'cz-reading-time',
        'czrt_render_settings_page'
    );
}
add_action('admin_menu', 'czrt_add_settings_page');

function czrt_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('CZ Reading Time', 'cz-reading-time'); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields('czrt_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr(czrt_get_option_key_words_per_minute()); ?>">
                            <?php esc_html_e('Parole al minuto', 'cz-reading-time'); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            id="<?php echo esc_attr(czrt_get_option_key_words_per_minute()); ?>"
                            type="number"
                            min="1"
                            step="1"
                            class="small-text"
                            name="<?php echo esc_attr(czrt_get_option_key_words_per_minute()); ?>"
                            value="<?php echo esc_attr((string) czrt_get_words_per_minute()); ?>">
                        <p class="description">
                            <?php esc_html_e('Valore usato per calcolare il tempo di lettura: numero di parole / parole al minuto.', 'cz-reading-time'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Salva impostazioni', 'cz-reading-time')); ?>
        </form>
    </div>
    <?php
}

function czrt_add_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=cz-reading-time')),
        esc_html__('Impostazioni', 'cz-reading-time')
    );

    array_unshift($links, $settings_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'czrt_add_plugin_action_links');

function czrt_get_post_from_context() {
    $post = get_post();

    if ($post instanceof WP_Post) {
        return $post;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return null;
    }

    $post = get_post($post_id);

    return $post instanceof WP_Post ? $post : null;
}

function czrt_should_show_for_current_user() {
    if (!is_user_logged_in()) {
        return false;
    }

    $meta_key = function_exists('czup_get_meta_key_show_readingtime')
        ? czup_get_meta_key_show_readingtime()
        : 'czup_show_readingtime';

    return get_user_meta(get_current_user_id(), $meta_key, true) === '1';
}

function czrt_is_filter_suppressed($set = null) {
    static $suppressed = false;

    if ($set !== null) {
        $suppressed = (bool) $set;
    }

    return $suppressed;
}

function czrt_render_content_without_injection($content) {
    $previous_state = czrt_is_filter_suppressed();
    czrt_is_filter_suppressed(true);
    $rendered = apply_filters('the_content', $content);
    czrt_is_filter_suppressed($previous_state);

    return is_string($rendered) ? $rendered : '';
}

function czrt_strip_footnotes_from_html($html) {
    if ($html === '') {
        return '';
    }

    if (!class_exists('DOMDocument')) {
        $html = preg_replace('/<div\b[^>]*class=(["\'])footnotes\1[^>]*>.*?<\/div>/is', '', $html);

        return wp_strip_all_tags((string) $html, true);
    }

    $internal_errors = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $html_with_wrapper = '<?xml encoding="utf-8" ?><div id="czrt-root">' . $html . '</div>';
    $document->loadHTML($html_with_wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($document);
    $nodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " footnotes ")]');

    if ($nodes instanceof DOMNodeList) {
        foreach ($nodes as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    $text = '';
    $root = $document->getElementById('czrt-root');
    if ($root) {
        $text = $root->textContent;
    }

    libxml_clear_errors();
    libxml_use_internal_errors($internal_errors);

    return is_string($text) ? $text : '';
}

function czrt_count_words_in_text($text) {
    if (!is_string($text) || trim($text) === '') {
        return 0;
    }

    if (!preg_match_all('/[\p{L}\p{N}]+(?:[\'’\-][\p{L}\p{N}]+)*/u', $text, $matches)) {
        return 0;
    }

    return count($matches[0]);
}

function czrt_count_words_in_html($html) {
    return czrt_count_words_in_text(czrt_strip_footnotes_from_html($html));
}

function czrt_get_minutes_from_word_count($word_count) {
    $word_count = max(0, (int) $word_count);

    if ($word_count === 0) {
        return 0;
    }

    return (int) ceil($word_count / czrt_get_words_per_minute());
}

function czrt_format_reading_time($minutes) {
    $minutes = max(0, (int) $minutes);

    if ($minutes < 60) {
        return sprintf(
            _n('%d minuto', '%d minuti', $minutes, 'cz-reading-time'),
            $minutes
        );
    }

    $hours = (int) floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    $hours_label = sprintf(
        _n('%d ora', '%d ore', $hours, 'cz-reading-time'),
        $hours
    );

    if ($remaining_minutes === 0) {
        return $hours_label;
    }

    $minutes_label = sprintf(
        _n('%d minuto', '%d minuti', $remaining_minutes, 'cz-reading-time'),
        $remaining_minutes
    );

    return $hours_label . ' e ' . $minutes_label;
}

function czrt_get_post_pages($post) {
    if (!$post instanceof WP_Post) {
        return array();
    }

    return preg_split('/<!--nextpage-->/i', (string) $post->post_content) ?: array();
}

function czrt_get_total_reading_time_minutes($post = null) {
    if (!$post) {
        $post = czrt_get_post_from_context();
    }

    if (!$post instanceof WP_Post) {
        return 0;
    }

    $pages = czrt_get_post_pages($post);
    $word_count = 0;

    foreach ($pages as $page_content) {
        $rendered_page = czrt_render_content_without_injection((string) $page_content);
        $word_count += czrt_count_words_in_html($rendered_page);
    }

    return czrt_get_minutes_from_word_count($word_count);
}

function czrt_get_total_reading_time_html($post = null) {
    if (!czrt_should_show_for_current_user()) {
        return '';
    }

    if (!$post) {
        $post = czrt_get_post_from_context();
    }

    if (!$post instanceof WP_Post) {
        return '';
    }

    return sprintf(
        '<p class="reading-time reading-time-total"><em><span class="reading-time-label">%s</span> <span class="reading-time-value">%s</span></em></p>',
        esc_html__('Tempo Totale di Lettura: circa', 'cz-reading-time'),
        esc_html(czrt_format_reading_time(czrt_get_total_reading_time_minutes($post)))
    );
}

function czrt_build_section_reading_time_html($minutes) {
    return sprintf(
        '<p class="reading-time reading-time-section"><em><span class="reading-time-label">%s</span> <span class="reading-time-value">%s</span></em></p>',
        esc_html__('Tempo di Lettura: circa', 'cz-reading-time'),
        esc_html(czrt_format_reading_time($minutes))
    );
}

function czrt_is_excluded_heading_html($heading_html, $context_html = '') {
    if (preg_match('/<h2\b[^>]*class=(["\'])[^"\']*\bcz-outline-heading\b[^"\']*\1/is', $heading_html) === 1) {
        return true;
    }

    if ($context_html === '' || !class_exists('DOMDocument')) {
        return false;
    }

    $internal_errors = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="czrt-context-root">' . $context_html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $xpath = new DOMXPath($document);
    $headings = $xpath->query('//h2');

    if (!$headings instanceof DOMNodeList || $headings->length === 0) {
        libxml_clear_errors();
        libxml_use_internal_errors($internal_errors);

        return false;
    }

    $heading = $headings->item($headings->length - 1);
    $is_in_footnotes = false;

    while ($heading instanceof DOMNode && $heading->parentNode) {
        $heading = $heading->parentNode;

        if (
            $heading instanceof DOMElement &&
            strtolower($heading->tagName) === 'div' &&
            preg_match('/(^|\s)footnotes(\s|$)/', $heading->getAttribute('class')) === 1
        ) {
            $is_in_footnotes = true;
            break;
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($internal_errors);

    return $is_in_footnotes;
}

function czrt_inject_section_reading_time($content) {
    if (czrt_is_filter_suppressed()) {
        return $content;
    }

    if (is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query() || !czrt_should_show_for_current_user()) {
        return $content;
    }

    if (!preg_match('/<h2\b[^>]*>.*?<\/h2>/is', $content)) {
        return $content;
    }

    $parts = preg_split('/(<h2\b[^>]*>.*?<\/h2>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts) || count($parts) < 3) {
        return $content;
    }

    $rebuilt = array_shift($parts);

    while (count($parts) >= 2) {
        $heading_html = array_shift($parts);
        $section_html = array_shift($parts);
        $minutes = czrt_get_minutes_from_word_count(czrt_count_words_in_html($section_html));

        $rebuilt .= $heading_html;

        $is_collapsable_toggle = (bool) preg_match('/<h2\b[^>]*\bcollapsable-toggle\b/is', $heading_html);

        if ($is_collapsable_toggle) {
            $reading_time_html = czrt_build_section_reading_time_html($minutes);
            $section_html = preg_replace_callback(
                '/(<div\b[^>]*\bcollapsable-content\b[^>]*>)/is',
                function ($m) use ($reading_time_html) {
                    return $m[1] . $reading_time_html;
                },
                $section_html,
                1
            );
        } elseif (!czrt_is_excluded_heading_html($heading_html, $rebuilt . $heading_html)) {
            $rebuilt .= czrt_build_section_reading_time_html($minutes);
        }

        $rebuilt .= $section_html;
    }

    if (!empty($parts)) {
        $rebuilt .= implode('', $parts);
    }

    return $rebuilt;
}
add_filter('the_content', 'czrt_inject_section_reading_time', 20);
