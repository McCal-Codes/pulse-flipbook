<?php
/**
 * Plugin Name: Pulse Flipbook
 * Description: Opinionated issue flipbook viewer for Pulse Magazine.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Pulse Magazine
 * Text Domain: pulse-flipbook
 * GitHub Plugin URI: https://github.com/McCal-Codes/pulse-flipbook
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PULSE_FLIPBOOK_PATH', plugin_dir_path(__FILE__));
define('PULSE_FLIPBOOK_URL', plugin_dir_url(__FILE__));

function pulse_flipbook_defaults(): array
{
    return [
        'auto_render_single_issue' => 1,
        'viewer_height' => 760,
        'show_download_button' => 1,
    ];
}

function pulse_flipbook_get_settings(): array
{
    $saved = get_option('pulse_flipbook_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    return wp_parse_args($saved, pulse_flipbook_defaults());
}

function pulse_flipbook_setting(string $key, $default = null)
{
    $settings = pulse_flipbook_get_settings();
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function pulse_flipbook_sanitize_settings(array $input): array
{
    $defaults = pulse_flipbook_defaults();
    $height = absint((int)($input['viewer_height'] ?? $defaults['viewer_height']));
    if ($height < 480) {
        $height = 480;
    }
    if ($height > 1200) {
        $height = 1200;
    }

    return [
        'auto_render_single_issue' => empty($input['auto_render_single_issue']) ? 0 : 1,
        'viewer_height' => $height,
        'show_download_button' => empty($input['show_download_button']) ? 0 : 1,
    ];
}

function pulse_flipbook_register_settings(): void
{
    register_setting('pulse_flipbook_group', 'pulse_flipbook_settings', [
        'type' => 'array',
        'sanitize_callback' => 'pulse_flipbook_sanitize_settings',
        'default' => pulse_flipbook_defaults(),
    ]);

    add_settings_section(
        'pulse_flipbook_main',
        __('Pulse Flipbook Settings', 'pulse-flipbook'),
        '__return_false',
        'pulse-flipbook'
    );

    add_settings_field(
        'auto_render_single_issue',
        __('Auto-render on single issue pages', 'pulse-flipbook'),
        'pulse_flipbook_render_checkbox_field',
        'pulse-flipbook',
        'pulse_flipbook_main',
        ['key' => 'auto_render_single_issue']
    );

    add_settings_field(
        'viewer_height',
        __('Viewer height (px)', 'pulse-flipbook'),
        'pulse_flipbook_render_number_field',
        'pulse-flipbook',
        'pulse_flipbook_main',
        ['key' => 'viewer_height']
    );

    add_settings_field(
        'show_download_button',
        __('Show PDF download button', 'pulse-flipbook'),
        'pulse_flipbook_render_checkbox_field',
        'pulse-flipbook',
        'pulse_flipbook_main',
        ['key' => 'show_download_button']
    );
}
add_action('admin_init', 'pulse_flipbook_register_settings');

function pulse_flipbook_register_admin_page(): void
{
    add_options_page(
        __('Pulse Flipbook', 'pulse-flipbook'),
        __('Pulse Flipbook', 'pulse-flipbook'),
        'manage_options',
        'pulse-flipbook',
        'pulse_flipbook_render_admin_page'
    );
}
add_action('admin_menu', 'pulse_flipbook_register_admin_page');

function pulse_flipbook_render_checkbox_field(array $args): void
{
    $key = (string)($args['key'] ?? '');
    $checked = (int)pulse_flipbook_setting($key, 0) === 1 ? 'checked' : '';
    printf(
        '<label><input type="checkbox" name="pulse_flipbook_settings[%1$s]" value="1" %2$s /> %3$s</label>',
        esc_attr($key),
        $checked,
        esc_html__('Enabled', 'pulse-flipbook')
    );
}

function pulse_flipbook_render_number_field(array $args): void
{
    $key = (string)($args['key'] ?? '');
    $value = (int)pulse_flipbook_setting($key, 760);
    printf(
        '<input type="number" min="480" max="1200" step="10" name="pulse_flipbook_settings[%1$s]" value="%2$d" />',
        esc_attr($key),
        $value
    );
}

function pulse_flipbook_render_admin_page(): void
{
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Pulse Flipbook Settings', 'pulse-flipbook'); ?></h1>
        <p><?php esc_html_e('Opinionated issue flipbook for PDF-based issue reading. On each Issue edit screen, use “Issue PDF (Flipbook)” to upload or select a PDF from the Media Library, or set the PDF URL in Pulse Mag issue fields. If both are set, the URL is used first. Use [pulse_flipbook] shortcode when needed.', 'pulse-flipbook'); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('pulse_flipbook_group');
            do_settings_sections('pulse-flipbook');
            submit_button(__('Save Settings', 'pulse-flipbook'));
            ?>
        </form>
    </div>
    <?php
}

function pulse_flipbook_is_valid_pdf_attachment(int $attachment_id): bool
{
    if ($attachment_id <= 0) {
        return false;
    }
    $post = get_post($attachment_id);
    if (!$post || $post->post_type !== 'attachment') {
        return false;
    }
    $mime = (string)get_post_mime_type($attachment_id);
    return $mime === 'application/pdf';
}

/**
 * Resolve PDF URL for flipbook: explicit URL first, then Media Library PDF attachment.
 */
function pulse_flipbook_pdf_url_for_issue(int $issue_id): string
{
    $url = (string)get_post_meta($issue_id, '_pulse_issue_pdf_url', true);
    $url = esc_url_raw($url);
    if ($url !== '') {
        return $url;
    }

    $attachment_id = absint((int)get_post_meta($issue_id, '_pulse_issue_pdf_attachment_id', true));
    if (!pulse_flipbook_is_valid_pdf_attachment($attachment_id)) {
        return '';
    }

    $file_url = wp_get_attachment_url($attachment_id);
    return is_string($file_url) ? esc_url_raw($file_url) : '';
}

function pulse_flipbook_add_issue_pdf_meta_box(): void
{
    add_meta_box(
        'pulse_flipbook_issue_pdf',
        __('Issue PDF (Flipbook)', 'pulse-flipbook'),
        'pulse_flipbook_render_issue_pdf_meta_box',
        'issue',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'pulse_flipbook_add_issue_pdf_meta_box');

function pulse_flipbook_render_issue_pdf_meta_box(\WP_Post $post): void
{
    wp_nonce_field('pulse_flipbook_issue_pdf_save', 'pulse_issue_pdf_attachment_nonce');

    $attachment_id = absint((int)get_post_meta($post->ID, '_pulse_issue_pdf_attachment_id', true));
    $label = __('No PDF selected.', 'pulse-flipbook');
    if (pulse_flipbook_is_valid_pdf_attachment($attachment_id)) {
        $file = get_attached_file($attachment_id);
        if (is_string($file) && $file !== '') {
            $label = basename($file);
        } else {
            $title = get_the_title($attachment_id);
            $label = $title !== '' ? $title : __('PDF attachment', 'pulse-flipbook');
        }
    } elseif ($attachment_id > 0) {
        $label = __('Invalid attachment — select a PDF file.', 'pulse-flipbook');
    }

    ?>
    <p><?php esc_html_e('Upload a PDF from the Media Library, or set “PDF URL” in Pulse Mag issue fields. If both are set, the URL is used first.', 'pulse-flipbook'); ?></p>
    <p>
        <input type="hidden" id="pulse_issue_pdf_attachment_id" name="pulse_issue_pdf_attachment_id" value="<?php echo esc_attr((string)$attachment_id); ?>" />
        <strong id="pulse-issue-pdf-filename"><?php echo esc_html($label); ?></strong>
    </p>
    <p>
        <button type="button" class="button button-secondary" id="pulse-issue-pdf-select"><?php esc_html_e('Select or upload PDF', 'pulse-flipbook'); ?></button>
        <button type="button" class="button-link" id="pulse-issue-pdf-clear"><?php esc_html_e('Remove PDF', 'pulse-flipbook'); ?></button>
    </p>
    <?php
}

function pulse_flipbook_save_issue_pdf_meta(int $post_id): void
{
    if (!isset($_POST['pulse_issue_pdf_attachment_nonce']) || !wp_verify_nonce((string)$_POST['pulse_issue_pdf_attachment_nonce'], 'pulse_flipbook_issue_pdf_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'issue') {
        return;
    }

    $raw = isset($_POST['pulse_issue_pdf_attachment_id']) ? (int)$_POST['pulse_issue_pdf_attachment_id'] : 0;
    $attachment_id = absint($raw);

    if ($attachment_id <= 0) {
        delete_post_meta($post_id, '_pulse_issue_pdf_attachment_id');
        return;
    }

    if (!pulse_flipbook_is_valid_pdf_attachment($attachment_id)) {
        delete_post_meta($post_id, '_pulse_issue_pdf_attachment_id');
        return;
    }

    update_post_meta($post_id, '_pulse_issue_pdf_attachment_id', $attachment_id);
}
add_action('save_post_issue', 'pulse_flipbook_save_issue_pdf_meta');

function pulse_flipbook_admin_enqueue_scripts(string $hook): void
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'issue') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'pulse-flipbook-admin',
        PULSE_FLIPBOOK_URL . 'assets/pulse-flipbook-admin.js',
        ['jquery'],
        '0.1.1',
        true
    );
    wp_localize_script('pulse-flipbook-admin', 'pulseFlipbookAdmin', [
        'i18nTitle' => __('Select Issue PDF', 'pulse-flipbook'),
        'i18nButton' => __('Use this PDF', 'pulse-flipbook'),
        'i18nNotPdf' => __('Please choose a PDF file.', 'pulse-flipbook'),
        'i18nNone' => __('No PDF selected.', 'pulse-flipbook'),
    ]);
}
add_action('admin_enqueue_scripts', 'pulse_flipbook_admin_enqueue_scripts');

function pulse_flipbook_register_assets(): void
{
    wp_register_style(
        'pulse-flipbook-style',
        PULSE_FLIPBOOK_URL . 'assets/pulse-flipbook.css',
        [],
        '0.1.3'
    );

    wp_register_script(
        'pulse-flipbook-script',
        PULSE_FLIPBOOK_URL . 'assets/pulse-flipbook.js',
        [],
        '0.1.3',
        true
    );
}
add_action('wp_enqueue_scripts', 'pulse_flipbook_register_assets');

function pulse_flipbook_render_markup(int $issue_id, bool $enqueue = true): string
{
    $pdf_url = pulse_flipbook_pdf_url_for_issue($issue_id);
    if ($pdf_url === '') {
        return '';
    }

    if ($enqueue) {
        wp_enqueue_style('pulse-flipbook-style');
        wp_enqueue_script('pulse-flipbook-script');
    }

    $height = (int)pulse_flipbook_setting('viewer_height', 760);
    $show_download = (int)pulse_flipbook_setting('show_download_button', 1) === 1 ? '1' : '0';
    $title = get_the_title($issue_id);

    ob_start();
    ?>
    <section class="pulse-flipbook" data-pdf-url="<?php echo esc_url($pdf_url); ?>" data-viewer-height="<?php echo esc_attr((string)$height); ?>" data-show-download="<?php echo esc_attr($show_download); ?>">
        <header class="pulse-flipbook__header">
            <div>
                <p class="pulse-flipbook__kicker"><?php esc_html_e('Issue Reader', 'pulse-flipbook'); ?></p>
                <h2 class="pulse-flipbook__title"><?php echo esc_html($title); ?></h2>
            </div>
            <div class="pulse-flipbook__controls">
                <button type="button" class="pulse-flipbook__btn" data-action="prev" aria-label="<?php esc_attr_e('Previous page', 'pulse-flipbook'); ?>">&larr;</button>
                <span class="pulse-flipbook__status" data-role="status">1 / 1</span>
                <button type="button" class="pulse-flipbook__btn" data-action="next" aria-label="<?php esc_attr_e('Next page', 'pulse-flipbook'); ?>">&rarr;</button>
                <button type="button" class="pulse-flipbook__btn" data-action="zoom-out" aria-label="<?php esc_attr_e('Zoom out', 'pulse-flipbook'); ?>">-</button>
                <button type="button" class="pulse-flipbook__btn" data-action="zoom-in" aria-label="<?php esc_attr_e('Zoom in', 'pulse-flipbook'); ?>">+</button>
                <button type="button" class="pulse-flipbook__btn" data-action="fullscreen" aria-label="<?php esc_attr_e('Fullscreen', 'pulse-flipbook'); ?>">&#9974;</button>
                <?php if ((int)$show_download === 1) : ?>
                    <a class="pulse-flipbook__btn pulse-flipbook__btn--link" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Download PDF', 'pulse-flipbook'); ?></a>
                <?php endif; ?>
            </div>
        </header>
        <div class="pulse-flipbook__viewport" style="height: <?php echo esc_attr((string)$height); ?>px;">
            <canvas class="pulse-flipbook__canvas" data-role="canvas"></canvas>
            <div class="pulse-flipbook__loading" data-role="loading"><?php esc_html_e('Loading issue...', 'pulse-flipbook'); ?></div>
            <div class="pulse-flipbook__error" data-role="error" hidden>
                <p><?php esc_html_e('The flipbook could not load. Use the PDF download button instead.', 'pulse-flipbook'); ?></p>
            </div>
        </div>
    </section>
    <?php

    return (string)ob_get_clean();
}

function pulse_flipbook_shortcode(array $atts): string
{
    $atts = shortcode_atts([
        'issue' => 0,
    ], $atts, 'pulse_flipbook');

    $issue_id = absint((int)$atts['issue']);
    if ($issue_id <= 0 && is_singular('issue')) {
        $issue_id = get_the_ID();
    }
    if ($issue_id <= 0 || get_post_type($issue_id) !== 'issue') {
        return '';
    }

    return pulse_flipbook_render_markup($issue_id, true);
}
add_shortcode('pulse_flipbook', 'pulse_flipbook_shortcode');

function pulse_flipbook_inject_single_issue(string $content): string
{
    if (!is_singular('issue') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    if ((int)pulse_flipbook_setting('auto_render_single_issue', 1) !== 1) {
        return $content;
    }

    $issue_id = get_the_ID();
    if (!$issue_id || get_post_type($issue_id) !== 'issue') {
        return $content;
    }

    $flipbook = pulse_flipbook_render_markup($issue_id, true);
    if ($flipbook === '') {
        return $content;
    }

    return $content . $flipbook;
}
add_filter('the_content', 'pulse_flipbook_inject_single_issue', 20);
