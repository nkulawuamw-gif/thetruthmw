<?php
/**
 * Plugin Name: TheTruth Site Settings
 * Description: Custom site settings for managing website name, logo, and theme colors.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: TheTruth MW
 * Text Domain: thetruth-settings
 */

defined('ABSPATH') || exit;

define('TTS_VERSION', '1.1.1');
define('TTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TTS_PLUGIN_URL', plugin_dir_url(__FILE__));

class TheTruthSettings
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        @ini_set('upload_max_filesize', '256M');
        @ini_set('post_max_size', '256M');
        @ini_set('max_execution_time', '300');
        @ini_set('max_input_time', '300');
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_head', [$this, 'output_custom_styles'], 100);
        add_action('wp_head', [$this, 'output_ad_script'], 1);
        add_action('wp_head', [$this, 'output_news_schema'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('after_setup_theme', [$this, 'set_custom_logo'], 5);
        add_filter('wp_get_attachment_image_attributes', [$this, 'filter_logo_attributes'], 10, 3);
        add_filter('option_blogname', [$this, 'filter_site_name'], 10, 2);
        add_filter('option_blogdescription', [$this, 'filter_tagline'], 10, 2);
        add_filter('pre_option_blogname', [$this, 'filter_site_name_pre'], 10, 2);
        add_filter('pre_option_blogdescription', [$this, 'filter_tagline_pre'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_breaking_news_meta_box']);
        add_action('save_post', [$this, 'save_breaking_news_meta']);
        add_action('add_meta_boxes', [$this, 'add_audio_meta_box']);
        add_action('save_post', [$this, 'save_audio_meta']);
        add_action('edit_form_after_editor', [$this, 'render_audio_below_editor']);
        add_action('admin_footer', [$this, 'expand_meta_boxes']);
        add_shortcode('tts_logo', [$this, 'logo_shortcode']);
        add_shortcode('tts_breaking_news', [$this, 'breaking_news_shortcode']);
        add_shortcode('tts_category_filter', [$this, 'category_filter_shortcode']);
        add_shortcode('tts_trending_posts', [$this, 'trending_posts_shortcode']);
        add_shortcode('tts_audio_section', [$this, 'audio_section_shortcode']);
        add_action('add_meta_boxes', [$this, 'add_video_meta_box']);
        add_action('save_post', [$this, 'save_video_meta']);
        add_action('edit_form_after_editor', [$this, 'render_video_below_editor']);
        add_shortcode('tts_video_section', [$this, 'video_section_shortcode']);
        add_shortcode('tts_media_section', [$this, 'media_section_shortcode']);
        add_shortcode('tts_header_ads', [$this, 'header_ads_shortcode']);
        add_filter('the_content', [$this, 'append_video_audio_to_content']);
        add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_upload_filename']);
        add_filter('wp_insert_attachment_data', [$this, 'sanitize_attachment_data'], 10, 2);
        add_filter('upload_size_limit', [$this, 'increase_upload_limit']);
    }

    public function sanitize_upload_filename($file)
    {
        $name = pathinfo($file['name'], PATHINFO_FILENAME);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = sanitize_file_name($name);
        if (strlen($name) > 80) {
            $name = substr($name, 0, 80);
        }
        $name = rtrim($name, '.-_');
        $file['name'] = $name . '.' . $ext;
        return $file;
    }

    public function sanitize_attachment_data($data, $postarr)
    {
        if (isset($data['post_title']) && strlen($data['post_title']) > 100) {
            $data['post_title'] = substr($data['post_title'], 0, 100);
        }
        if (isset($data['post_name']) && strlen($data['post_name']) > 180) {
            $data['post_name'] = substr($data['post_name'], 0, 180);
        }
        return $data;
    }

    public function increase_upload_limit($size)
    {
        return 256 * MB_IN_BYTES;
    }

    public function add_admin_menu()
    {
        add_options_page(
            __('Insight Settings', 'thetruth-settings'),
            __('Insight Settings', 'thetruth-settings'),
            'manage_options',
            'thetruth-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        $settings = [
            'site_branding' => [
                'tts_site_name' => ['type' => 'string', 'default' => ''],
                'tts_site_tagline' => ['type' => 'string', 'default' => ''],
                'tts_site_logo' => ['type' => 'integer', 'default' => 0],
                'tts_header_ads' => ['type' => 'string', 'default' => '[]', 'sanitize' => [$this, 'sanitize_header_ads'], 'show_in_rest' => false],
            ],
            'news_settings' => [
                'tts_posts_per_category' => ['type' => 'integer', 'default' => 5],
                'tts_breaking_count' => ['type' => 'integer', 'default' => 5],
                'tts_trending_days' => ['type' => 'integer', 'default' => 7],
            ],
            'theme_colors' => [
                // General
                'tts_color_primary' => ['type' => 'string', 'default' => '#111111'],
                'tts_color_secondary' => ['type' => 'string', 'default' => '#686868'],
                'tts_color_accent' => ['type' => 'string', 'default' => '#FFEE58'],
                'tts_color_background' => ['type' => 'string', 'default' => '#FFFFFF'],
                'tts_color_text' => ['type' => 'string', 'default' => '#111111'],
                'tts_color_link' => ['type' => 'string', 'default' => '#503AA8'],
                // Header
                'tts_header_bg' => ['type' => 'string', 'default' => '#1565c0'],
                'tts_header_text' => ['type' => 'string', 'default' => '#ffffff'],
                'tts_header_search_bg' => ['type' => 'string', 'default' => '#ffffff'],
                'tts_header_ad_border' => ['type' => 'string', 'default' => 'rgba(255,255,255,0.15)'],
                // Breaking News
                'tts_breaking_bg' => ['type' => 'string', 'default' => '#1b5e20'],
                'tts_breaking_text' => ['type' => 'string', 'default' => '#ffffff'],
                'tts_breaking_label_bg' => ['type' => 'string', 'default' => '#FFEE58'],
                'tts_breaking_label_text' => ['type' => 'string', 'default' => '#111111'],
                'tts_breaking_arrow' => ['type' => 'string', 'default' => '#FFEE58'],
                // Hero
                'tts_hero_bg' => ['type' => 'string', 'default' => '#1b5e20'],
                'tts_hero_overlay' => ['type' => 'string', 'default' => 'rgba(0,0,0,0.85)'],
                'tts_hero_text' => ['type' => 'string', 'default' => '#ffffff'],
                // News Cards
                'tts_card_bg' => ['type' => 'string', 'default' => '#ffffff'],
                'tts_card_border' => ['type' => 'string', 'default' => '#e0e0e0'],
                'tts_card_title' => ['type' => 'string', 'default' => '#1a1a1a'],
                'tts_card_excerpt' => ['type' => 'string', 'default' => '#555555'],
                'tts_card_author' => ['type' => 'string', 'default' => '#888888'],
                'tts_card_author_name' => ['type' => 'string', 'default' => '#444444'],
                'tts_card_date' => ['type' => 'string', 'default' => '#999999'],
                'tts_card_separator' => ['type' => 'string', 'default' => '#f0f0f0'],
                // Buttons
                'tts_button_bg' => ['type' => 'string', 'default' => '#1565c0'],
                'tts_button_text' => ['type' => 'string', 'default' => '#ffffff'],
                'tts_button_hover' => ['type' => 'string', 'default' => '#0d47a1'],
                // Sections
                'tts_section_border' => ['type' => 'string', 'default' => '#e53935'],
                'tts_section_title' => ['type' => 'string', 'default' => '#1a1a1a'],
                // Pagination
                'tts_pagination_bg' => ['type' => 'string', 'default' => '#1565c0'],
                'tts_pagination_text' => ['type' => 'string', 'default' => '#333333'],
                'tts_pagination_border' => ['type' => 'string', 'default' => '#dddddd'],
                // Category Filter
                'tts_cat_filter_bg' => ['type' => 'string', 'default' => '#f0f0f0'],
                'tts_cat_filter_text' => ['type' => 'string', 'default' => '#333333'],
                'tts_cat_filter_active_bg' => ['type' => 'string', 'default' => '#1b5e20'],
                'tts_cat_filter_active_text' => ['type' => 'string', 'default' => '#ffffff'],
            ],
        ];

        foreach ($settings as $section => $fields) {
            foreach ($fields as $option_name => $args) {
                $sanitize = isset($args['sanitize']) ? $args['sanitize'] : ('integer' === $args['type'] ? 'absint' : 'sanitize_text_field');
                register_setting('tts_settings_group', $option_name, [
                    'type' => $args['type'],
                    'sanitize_callback' => $sanitize,
                    'default' => $args['default'],
                    'show_in_rest' => false,
                ]);
            }
        }
    }

    public function enqueue_admin_assets($hook)
    {
        if ('settings_page_thetruth-settings' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_script(
            'tts-admin',
            TTS_PLUGIN_URL . 'assets/admin.js',
            ['jquery', 'wp-color-picker'],
            TTS_VERSION,
            true
        );

        wp_enqueue_style(
            'tts-admin',
            TTS_PLUGIN_URL . 'assets/admin.css',
            [],
            TTS_VERSION
        );

        wp_localize_script('tts-admin', 'ttsAdmin', [
            'title' => __('Select Logo', 'thetruth-settings'),
            'button' => __('Use as Logo', 'thetruth-settings'),
        ]);


    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'site_branding';
        ?>
        <div class="wrap tts-wrap">
            <h1><?php echo esc_html__('Insight Settings', 'thetruth-settings'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=thetruth-settings&tab=site_branding"
                   class="nav-tab <?php echo 'site_branding' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Site Identity', 'thetruth-settings'); ?>
                </a>
                <a href="?page=thetruth-settings&tab=theme_colors"
                   class="nav-tab <?php echo 'theme_colors' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Theme Colors', 'thetruth-settings'); ?>
                </a>
                <a href="?page=thetruth-settings&tab=news_settings"
                   class="nav-tab <?php echo 'news_settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php _e('News Settings', 'thetruth-settings'); ?>
                </a>
            </h2>

            <form method="post" action="options.php" class="tts-form">
                <?php
                settings_fields('tts_settings_group');

                if ('site_branding' === $active_tab) {
                    $this->render_branding_tab();
                } elseif ('theme_colors' === $active_tab) {
                    $this->render_colors_tab();
                } elseif ('news_settings' === $active_tab) {
                    $this->render_news_settings_tab();
                }

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function render_branding_tab()
    {
        $site_name = get_option('tts_site_name', '');
        $site_tagline = get_option('tts_site_tagline', '');
        $logo_id = (int) get_option('tts_site_logo', 0);
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
        ?>
        <div class="tts-tab-content">
            <div class="tts-card">
                <h2><?php _e('Website Identity', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Customize how your site appears to visitors. These settings override the default WordPress site title and tagline.', 'thetruth-settings'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tts_site_name"><?php _e('Website Name', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="tts_site_name"
                                   name="tts_site_name"
                                   value="<?php echo esc_attr($site_name); ?>"
                                   class="regular-text"
                                   placeholder="<?php _e('Enter your website name', 'thetruth-settings'); ?>" />
                            <p class="description"><?php _e('The name of your website displayed to visitors.', 'thetruth-settings'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="tts_site_tagline"><?php _e('Tagline', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="tts_site_tagline"
                                   name="tts_site_tagline"
                                   value="<?php echo esc_attr($site_tagline); ?>"
                                   class="regular-text"
                                   placeholder="<?php _e('Enter your site tagline', 'thetruth-settings'); ?>" />
                            <p class="description"><?php _e('A short description or tagline for your website.', 'thetruth-settings'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Site Logo', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <div class="tts-logo-upload">
                                <div class="tts-logo-preview" id="tts-logo-preview" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                                    <?php if ($logo_url) : ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Site Logo', 'thetruth-settings'); ?>" style="max-width:200px;height:auto;" />
                                    <?php endif; ?>
                                </div>
                                <div class="tts-logo-actions">
                                    <button type="button" class="button button-primary" id="tts-upload-logo">
                                        <?php _e('Upload / Choose Logo', 'thetruth-settings'); ?>
                                    </button>
                                    <button type="button" class="button" id="tts-remove-logo" style="<?php echo $logo_id ? '' : 'display:none;'; ?>">
                                        <?php _e('Remove Logo', 'thetruth-settings'); ?>
                                    </button>
                                    <input type="hidden" name="tts_site_logo" id="tts_site_logo" value="<?php echo esc_attr($logo_id); ?>" />
                                </div>
                                <p class="description"><?php _e('Upload or select a logo image from the media library.', 'thetruth-settings'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Header Ads', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <div id="tts-ads-container">
                                <?php
                                $ads = json_decode(get_option('tts_header_ads', '[]'), true);
                                if (!is_array($ads) || empty($ads)) {
                                    $ads = [''];
                                }
                                foreach ($ads as $i => $ad) :
                                ?>
                                <div class="tts-ad-row">
                                    <textarea rows="3"
                                              class="large-text code tts-ad-textarea"
                                              placeholder="<?php _e('Paste ad HTML or code here...', 'thetruth-settings'); ?>"><?php echo esc_textarea($ad); ?></textarea>
                                    <button type="button" class="button tts-remove-ad" <?php echo $i === 0 ? 'style="display:none"' : ''; ?>><?php _e('Remove', 'thetruth-settings'); ?></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="tts_header_ads" id="tts_header_ads" value="<?php echo esc_attr(get_option('tts_header_ads', '[]')); ?>" />
                            <button type="button" class="button" id="tts-add-ad"><?php _e('+ Add Another Ad', 'thetruth-settings'); ?></button>
                            <p class="description"><?php _e('One ad is shown per day, rotating through your list. Accepts HTML.', 'thetruth-settings'); ?></p>
                            <script>
                            jQuery(function($) {
                                var container = $('#tts-ads-container');
                                var hidden = $('#tts_header_ads');
                                function sync() {
                                    var ads = [];
                                    container.find('.tts-ad-textarea').each(function() {
                                        var val = $(this).val().trim();
                                        if (val) ads.push(val);
                                    });
                                    hidden.val(ads.length ? JSON.stringify(ads) : '[]');
                                }
                                container.on('input', '.tts-ad-textarea', sync);
                                $('#tts-add-ad').on('click', function() {
                                    var row = $('<div class="tts-ad-row"><textarea rows="3" class="large-text code tts-ad-textarea" placeholder="<?php _e('Paste ad HTML or code here...', 'thetruth-settings'); ?>"></textarea><button type="button" class="button tts-remove-ad"><?php _e('Remove', 'thetruth-settings'); ?></button></div>');
                                    row.find('.tts-remove-ad').on('click', function() { $(this).closest('.tts-ad-row').remove(); sync(); });
                                    container.append(row);
                                    sync();
                                });
                                container.on('click', '.tts-remove-ad', function() {
                                    $(this).closest('.tts-ad-row').remove();
                                    sync();
                                });
                            });
                            </script>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="tts-card">
                <h2><?php _e('Site Preview', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Preview how your site identity will appear.', 'thetruth-settings'); ?></p>
                <div class="tts-preview">
                    <div class="tts-preview-header">
                        <?php if ($logo_url) : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Logo', 'thetruth-settings'); ?>" class="tts-preview-logo" />
                        <?php endif; ?>
                        <div class="tts-preview-text">
                            <strong class="tts-preview-name"><?php echo esc_html($site_name ?: __('Your Website Name', 'thetruth-settings')); ?></strong>
                            <span class="tts-preview-tagline"><?php echo esc_html($site_tagline ?: __('Your Tagline Here', 'thetruth-settings')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_colors_tab()
    {
        $groups = [
            'general' => [
                'title' => __('General Colors', 'thetruth-settings'),
                'desc' => __('Base colors for the overall site appearance.', 'thetruth-settings'),
                'fields' => [
                    'tts_color_primary' => ['label' => __('Primary', 'thetruth-settings'), 'desc' => __('Main brand color for headings and key elements.', 'thetruth-settings'), 'default' => '#111111'],
                    'tts_color_secondary' => ['label' => __('Secondary', 'thetruth-settings'), 'desc' => __('Secondary color for less prominent elements.', 'thetruth-settings'), 'default' => '#686868'],
                    'tts_color_accent' => ['label' => __('Accent', 'thetruth-settings'), 'desc' => __('Accent color for highlights and calls to action.', 'thetruth-settings'), 'default' => '#FFEE58'],
                    'tts_color_background' => ['label' => __('Page Background', 'thetruth-settings'), 'desc' => __('Site background color.', 'thetruth-settings'), 'default' => '#FFFFFF'],
                    'tts_color_text' => ['label' => __('Body Text', 'thetruth-settings'), 'desc' => __('Default text color.', 'thetruth-settings'), 'default' => '#111111'],
                    'tts_color_link' => ['label' => __('Link Color', 'thetruth-settings'), 'desc' => __('Color for hyperlinks.', 'thetruth-settings'), 'default' => '#503AA8'],
                ],
            ],
            'header' => [
                'title' => __('Header & Top Bar', 'thetruth-settings'),
                'desc' => __('Colors for the top navigation bar.', 'thetruth-settings'),
                'fields' => [
                    'tts_header_bg' => ['label' => __('Header Background', 'thetruth-settings'), 'desc' => __('Top bar background color.', 'thetruth-settings'), 'default' => '#1565c0'],
                    'tts_header_text' => ['label' => __('Header Text', 'thetruth-settings'), 'desc' => __('Site title, nav links, and header text color.', 'thetruth-settings'), 'default' => '#ffffff'],
                    'tts_header_search_bg' => ['label' => __('Search Background', 'thetruth-settings'), 'desc' => __('Search input background color.', 'thetruth-settings'), 'default' => '#ffffff'],
                    'tts_header_ad_border' => ['label' => __('Ad Section Border', 'thetruth-settings'), 'desc' => __('Border color above the ad section.', 'thetruth-settings'), 'default' => 'rgba(255,255,255,0.15)'],
                ],
            ],
            'breaking' => [
                'title' => __('Breaking News Bar', 'thetruth-settings'),
                'desc' => __('Colors for the breaking news ticker.', 'thetruth-settings'),
                'fields' => [
                    'tts_breaking_bg' => ['label' => __('Bar Background', 'thetruth-settings'), 'desc' => __('Breaking news bar background.', 'thetruth-settings'), 'default' => '#1b5e20'],
                    'tts_breaking_text' => ['label' => __('Bar Text', 'thetruth-settings'), 'desc' => __('Breaking news headline color.', 'thetruth-settings'), 'default' => '#ffffff'],
                    'tts_breaking_label_bg' => ['label' => __('Label Background', 'thetruth-settings'), 'desc' => __('"BREAKING" label background.', 'thetruth-settings'), 'default' => '#FFEE58'],
                    'tts_breaking_label_text' => ['label' => __('Label Text', 'thetruth-settings'), 'desc' => __('"BREAKING" label text color.', 'thetruth-settings'), 'default' => '#111111'],
                    'tts_breaking_arrow' => ['label' => __('Arrow Color', 'thetruth-settings'), 'desc' => __('Color of the arrow icons (→) next to each headline.', 'thetruth-settings'), 'default' => '#FFEE58'],
                ],
            ],
            'hero' => [
                'title' => __('Hero / Featured Story', 'thetruth-settings'),
                'desc' => __('Colors for the featured story section at the top of the home page.', 'thetruth-settings'),
                'fields' => [
                    'tts_hero_bg' => ['label' => __('Hero Background', 'thetruth-settings'), 'desc' => __('Hero section fallback background.', 'thetruth-settings'), 'default' => '#1b5e20'],
                    'tts_hero_overlay' => ['label' => __('Overlay Color', 'thetruth-settings'), 'desc' => __('Gradient overlay at the bottom of the hero image. Use rgba or gradient.', 'thetruth-settings'), 'default' => 'rgba(0,0,0,0.85)'],
                    'tts_hero_text' => ['label' => __('Hero Text', 'thetruth-settings'), 'desc' => __('Title and excerpt text color on the hero.', 'thetruth-settings'), 'default' => '#ffffff'],
                ],
            ],
            'cards' => [
                'title' => __('News Cards / Boxes', 'thetruth-settings'),
                'desc' => __('Colors for the story boxes in the Latest News section.', 'thetruth-settings'),
                'fields' => [
                    'tts_card_bg' => ['label' => __('Card Background', 'thetruth-settings'), 'desc' => __('Story box background.', 'thetruth-settings'), 'default' => '#ffffff'],
                    'tts_card_border' => ['label' => __('Card Border', 'thetruth-settings'), 'desc' => __('Story box border color.', 'thetruth-settings'), 'default' => '#e0e0e0'],
                    'tts_card_title' => ['label' => __('Title Color', 'thetruth-settings'), 'desc' => __('Story title text color.', 'thetruth-settings'), 'default' => '#1a1a1a'],
                    'tts_card_excerpt' => ['label' => __('Excerpt Color', 'thetruth-settings'), 'desc' => __('Story excerpt text color.', 'thetruth-settings'), 'default' => '#555555'],
                    'tts_card_author' => ['label' => __('Author Label', 'thetruth-settings'), 'desc' => __('"Written by:" label color.', 'thetruth-settings'), 'default' => '#888888'],
                    'tts_card_author_name' => ['label' => __('Author Name', 'thetruth-settings'), 'desc' => __('Author name text color.', 'thetruth-settings'), 'default' => '#444444'],
                    'tts_card_date' => ['label' => __('Date Color', 'thetruth-settings'), 'desc' => __('Post date text color.', 'thetruth-settings'), 'default' => '#999999'],
                    'tts_card_separator' => ['label' => __('Separator Lines', 'thetruth-settings'), 'desc' => __('Lines between sections inside a card.', 'thetruth-settings'), 'default' => '#f0f0f0'],
                ],
            ],
            'buttons' => [
                'title' => __('Buttons', 'thetruth-settings'),
                'desc' => __('Colors for the Read More buttons on story cards.', 'thetruth-settings'),
                'fields' => [
                    'tts_button_bg' => ['label' => __('Button Background', 'thetruth-settings'), 'desc' => __('Read More button background.', 'thetruth-settings'), 'default' => '#1565c0'],
                    'tts_button_text' => ['label' => __('Button Text', 'thetruth-settings'), 'desc' => __('Read More button text color.', 'thetruth-settings'), 'default' => '#ffffff'],
                    'tts_button_hover' => ['label' => __('Button Hover', 'thetruth-settings'), 'desc' => __('Button background on hover.', 'thetruth-settings'), 'default' => '#0d47a1'],
                ],
            ],
            'sections' => [
                'title' => __('Section Headings', 'thetruth-settings'),
                'desc' => __('Colors for section titles like "Latest News".', 'thetruth-settings'),
                'fields' => [
                    'tts_section_border' => ['label' => __('Left Border', 'thetruth-settings'), 'desc' => __('Left accent border color on section headings.', 'thetruth-settings'), 'default' => '#e53935'],
                    'tts_section_title' => ['label' => __('Title Text', 'thetruth-settings'), 'desc' => __('Section heading text color.', 'thetruth-settings'), 'default' => '#1a1a1a'],
                ],
            ],
            'pagination' => [
                'title' => __('Pagination', 'thetruth-settings'),
                'desc' => __('Colors for page navigation at the bottom of the news list.', 'thetruth-settings'),
                'fields' => [
                    'tts_pagination_bg' => ['label' => __('Active Background', 'thetruth-settings'), 'desc' => __('Active/hover pagination button background.', 'thetruth-settings'), 'default' => '#1565c0'],
                    'tts_pagination_text' => ['label' => __('Text Color', 'thetruth-settings'), 'desc' => __('Default pagination button text.', 'thetruth-settings'), 'default' => '#333333'],
                    'tts_pagination_border' => ['label' => __('Border Color', 'thetruth-settings'), 'desc' => __('Pagination button border.', 'thetruth-settings'), 'default' => '#dddddd'],
                ],
            ],
            'filter' => [
                'title' => __('Category Filter', 'thetruth-settings'),
                'desc' => __('Colors for the category filter buttons.', 'thetruth-settings'),
                'fields' => [
                    'tts_cat_filter_bg' => ['label' => __('Inactive Background', 'thetruth-settings'), 'desc' => __('Default category button background.', 'thetruth-settings'), 'default' => '#f0f0f0'],
                    'tts_cat_filter_text' => ['label' => __('Inactive Text', 'thetruth-settings'), 'desc' => __('Default category button text.', 'thetruth-settings'), 'default' => '#333333'],
                    'tts_cat_filter_active_bg' => ['label' => __('Active Background', 'thetruth-settings'), 'desc' => __('Active/hover category button background.', 'thetruth-settings'), 'default' => '#1b5e20'],
                    'tts_cat_filter_active_text' => ['label' => __('Active Text', 'thetruth-settings'), 'desc' => __('Active/hover category button text.', 'thetruth-settings'), 'default' => '#ffffff'],
                ],
            ],
        ];
        ?>
        <div class="tts-tab-content">
            <?php foreach ($groups as $key => $group) : ?>
            <div class="tts-card">
                <h2><?php echo esc_html($group['title']); ?></h2>
                <p class="description"><?php echo esc_html($group['desc']); ?></p>
                <div class="tts-color-grid">
                    <?php foreach ($group['fields'] as $field_key => $field) :
                        $value = get_option($field_key, $field['default']);
                    ?>
                        <div class="tts-color-item">
                            <div class="tts-color-header">
                                <label for="<?php echo esc_attr($field_key); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                </label>
                                <span class="tts-color-hex"><?php echo esc_html($value); ?></span>
                            </div>
                            <div class="tts-color-input-wrap">
                                <input type="text"
                                       id="<?php echo esc_attr($field_key); ?>"
                                       name="<?php echo esc_attr($field_key); ?>"
                                       value="<?php echo esc_attr($value); ?>"
                                       class="tts-color-picker"
                                       data-default-color="<?php echo esc_attr($field['default']); ?>" />
                            </div>
                            <p class="description"><?php echo esc_html($field['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="tts-card">
                <h2><?php _e('Color Preview', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Live preview of your selected color scheme.', 'thetruth-settings'); ?></p>
                <div class="tts-color-preview">
                    <div class="tts-preview-card" style="background-color:<?php echo esc_attr(get_option('tts_color_background', '#FFFFFF')); ?>;color:<?php echo esc_attr(get_option('tts_color_text', '#111111')); ?>;border:1px solid <?php echo esc_attr(get_option('tts_color_secondary', '#686868')); ?>;">
                        <h3 style="color:<?php echo esc_attr(get_option('tts_color_primary', '#111111')); ?>;"><?php _e('Sample Heading', 'thetruth-settings'); ?></h3>
                        <p><?php _e('This is sample body text to demonstrate how your colors will look together.', 'thetruth-settings'); ?></p>
                        <a href="#" style="color:<?php echo esc_attr(get_option('tts_color_link', '#503AA8')); ?>;"><?php _e('Sample Link', 'thetruth-settings'); ?></a>
                        <div style="margin-top:10px;">
                            <span style="display:inline-block;padding:8px 16px;background-color:<?php echo esc_attr(get_option('tts_button_bg', '#1565c0')); ?>;color:<?php echo esc_attr(get_option('tts_button_text', '#ffffff')); ?>;border-radius:4px;">
                                <?php _e('Sample Button', 'thetruth-settings'); ?>
                            </span>
                            <span style="display:inline-block;padding:8px 16px;background-color:<?php echo esc_attr(get_option('tts_color_accent', '#FFEE58')); ?>;color:<?php echo esc_attr(get_option('tts_color_primary', '#111111')); ?>;border-radius:4px;margin-left:8px;">
                                <?php _e('Accent Badge', 'thetruth-settings'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tts-card">
                <h2><?php _e('Reset Colors', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Restore all colors to their default values.', 'thetruth-settings'); ?></p>
                <button type="button" class="button" id="tts-reset-colors">
                    <?php _e('Reset to Defaults', 'thetruth-settings'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    public function output_custom_styles()
    {
        $colors = [
            '--tts-color-primary' => ['key' => 'tts_color_primary', 'default' => '#111111'],
            '--tts-color-secondary' => ['key' => 'tts_color_secondary', 'default' => '#686868'],
            '--tts-color-accent' => ['key' => 'tts_color_accent', 'default' => '#FFEE58'],
            '--tts-color-background' => ['key' => 'tts_color_background', 'default' => '#FFFFFF'],
            '--tts-color-text' => ['key' => 'tts_color_text', 'default' => '#111111'],
            '--tts-color-link' => ['key' => 'tts_color_link', 'default' => '#503AA8'],
            '--tts-header-bg' => ['key' => 'tts_header_bg', 'default' => '#1565c0'],
            '--tts-header-text' => ['key' => 'tts_header_text', 'default' => '#ffffff'],
            '--tts-header-search-bg' => ['key' => 'tts_header_search_bg', 'default' => '#ffffff'],
            '--tts-header-ad-border' => ['key' => 'tts_header_ad_border', 'default' => 'rgba(255,255,255,0.15)'],
            '--tts-breaking-bg' => ['key' => 'tts_breaking_bg', 'default' => '#1b5e20'],
            '--tts-breaking-text' => ['key' => 'tts_breaking_text', 'default' => '#ffffff'],
            '--tts-breaking-label-bg' => ['key' => 'tts_breaking_label_bg', 'default' => '#FFEE58'],
            '--tts-breaking-label-text' => ['key' => 'tts_breaking_label_text', 'default' => '#111111'],
            '--tts-breaking-arrow' => ['key' => 'tts_breaking_arrow', 'default' => '#FFEE58'],
            '--tts-hero-bg' => ['key' => 'tts_hero_bg', 'default' => '#1b5e20'],
            '--tts-hero-overlay' => ['key' => 'tts_hero_overlay', 'default' => 'rgba(0,0,0,0.85)'],
            '--tts-hero-text' => ['key' => 'tts_hero_text', 'default' => '#ffffff'],
            '--tts-card-bg' => ['key' => 'tts_card_bg', 'default' => '#ffffff'],
            '--tts-card-border' => ['key' => 'tts_card_border', 'default' => '#e0e0e0'],
            '--tts-card-title' => ['key' => 'tts_card_title', 'default' => '#1a1a1a'],
            '--tts-card-excerpt' => ['key' => 'tts_card_excerpt', 'default' => '#555555'],
            '--tts-card-author' => ['key' => 'tts_card_author', 'default' => '#888888'],
            '--tts-card-author-name' => ['key' => 'tts_card_author_name', 'default' => '#444444'],
            '--tts-card-date' => ['key' => 'tts_card_date', 'default' => '#999999'],
            '--tts-card-separator' => ['key' => 'tts_card_separator', 'default' => '#f0f0f0'],
            '--tts-button-bg' => ['key' => 'tts_button_bg', 'default' => '#1565c0'],
            '--tts-button-text' => ['key' => 'tts_button_text', 'default' => '#ffffff'],
            '--tts-button-hover' => ['key' => 'tts_button_hover', 'default' => '#0d47a1'],
            '--tts-section-border' => ['key' => 'tts_section_border', 'default' => '#e53935'],
            '--tts-section-title' => ['key' => 'tts_section_title', 'default' => '#1a1a1a'],
            '--tts-pagination-bg' => ['key' => 'tts_pagination_bg', 'default' => '#1565c0'],
            '--tts-pagination-text' => ['key' => 'tts_pagination_text', 'default' => '#333333'],
            '--tts-pagination-border' => ['key' => 'tts_pagination_border', 'default' => '#dddddd'],
            '--tts-cat-filter-bg' => ['key' => 'tts_cat_filter_bg', 'default' => '#f0f0f0'],
            '--tts-cat-filter-text' => ['key' => 'tts_cat_filter_text', 'default' => '#333333'],
            '--tts-cat-filter-active-bg' => ['key' => 'tts_cat_filter_active_bg', 'default' => '#1b5e20'],
            '--tts-cat-filter-active-text' => ['key' => 'tts_cat_filter_active_text', 'default' => '#ffffff'],
        ];

        $css = ':root {' . "\n";
        foreach ($colors as $property => $cfg) {
            $value = get_option($cfg['key'], $cfg['default']);
            if (empty($value)) {
                $value = $cfg['default'];
            }
            $css .= '    ' . $property . ': ' . esc_attr($value) . ';' . "\n";
        }
        $css .= '}' . "\n";

        $css .= '
.tts-news-wrap .wp-block-post-template {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 24px !important;
    grid-template-columns: none !important;
    list-style: none !important;
    padding: 0 !important;
}
.tts-news-wrap .wp-block-post-template > * {
    flex: 1 !important;
    min-width: 280px !important;
    max-width: 100% !important;
    display: block !important;
}
';

        echo '<style id="tts-custom-styles">' . "\n" . $css . '</style>' . "\n";
    }

    public function output_news_schema()
    {
        if (!is_single()) {
            return;
        }
        $post = get_queried_object();
        if (!$post || 'post' !== $post->post_type) {
            return;
        }

        $categories = get_the_category($post->ID);
        $cat_names = [];
        foreach ($categories as $cat) {
            $cat_names[] = $cat->name;
        }

        $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
        ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "<?php echo esc_js(get_the_title($post->ID)); ?>",
            "url": "<?php echo esc_js(get_permalink($post->ID)); ?>",
            "datePublished": "<?php echo esc_js(get_the_date('c', $post->ID)); ?>",
            "dateModified": "<?php echo esc_js(get_the_modified_date('c', $post->ID)); ?>",
            "description": "<?php echo esc_js(wp_trim_words(get_the_excerpt($post->ID) ?: get_the_title($post->ID), 30)); ?>",
            "articleSection": "<?php echo esc_js(implode(', ', $cat_names)); ?>",
            "author": {
                "@type": "Person",
                "name": "<?php echo esc_js(get_the_author_meta('display_name', $post->post_author)); ?>"
            },
            "publisher": {
                "@type": "Organization",
                "name": "<?php echo esc_js(get_option('tts_site_name', get_bloginfo('name'))); ?>"
            }<?php if ($image) : ?>,
            "image": {
                "@type": "ImageObject",
                "url": "<?php echo esc_js($image[0]); ?>",
                "width": <?php echo (int) $image[1]; ?>,
                "height": <?php echo (int) $image[2]; ?>
            }<?php endif; ?>
        }
        </script>
        <?php
    }

    public function set_custom_logo()
    {
        $logo_id = (int) get_option('tts_site_logo', 0);
        if ($logo_id) {
            add_theme_support('custom-logo', [
                'height' => 200,
                'width' => 200,
                'flex-height' => true,
                'flex-width' => true,
            ]);
            set_theme_mod('custom_logo', $logo_id);
        }
    }

    public function filter_logo_attributes($attr, $attachment, $size)
    {
        $logo_id = (int) get_option('tts_site_logo', 0);
        if ($logo_id && isset($attachment->ID) && (int) $attachment->ID === $logo_id) {
            $attr['class'] = isset($attr['class']) ? $attr['class'] . ' tts-custom-logo' : 'tts-custom-logo';
        }
        return $attr;
    }

    public function filter_site_name($value, $option)
    {
        $custom_name = get_option('tts_site_name', '');
        return $custom_name ?: $value;
    }

    public function filter_tagline($value, $option)
    {
        $custom_tagline = get_option('tts_site_tagline', '');
        return $custom_tagline ?: $value;
    }

    public function filter_site_name_pre($value, $option)
    {
        $custom_name = get_option('tts_site_name', '');
        return $custom_name ?: false;
    }

    public function filter_tagline_pre($value, $option)
    {
        $custom_tagline = get_option('tts_site_tagline', '');
        return $custom_tagline ?: false;
    }

    public function sanitize_header_ads($value)
    {
        if (is_array($value)) {
            $ads = $value;
        } else {
            $ads = json_decode($value, true);
            if (!is_array($ads)) {
                $ads = [];
            }
        }
        $sanitized = [];
        foreach ($ads as $ad) {
            $ad = wp_kses_post(trim($ad));
            if ($ad) {
                $sanitized[] = $ad;
            }
        }
        return wp_json_encode($sanitized);
    }

    public function get_header_ad_for_today()
    {
        $ads = json_decode(get_option('tts_header_ads', '[]'), true);
        if (!is_array($ads) || empty($ads)) {
            return '';
        }
        $day = (int) date('z');
        return $ads[$day % count($ads)];
    }

    public function get_header_ads()
    {
        $ads = json_decode(get_option('tts_header_ads', '[]'), true);
        return is_array($ads) ? $ads : [];
    }

    private function format_ad($raw)
    {
        $trimmed = trim($raw);
        if (strpos($trimmed, '<') !== false) {
            return wp_kses_post($trimmed);
        }
        if (preg_match('#^https?://\S+\.(jpe?g|png|gif|webp)(\?.*)?$#i', $trimmed)) {
            return '<img src="' . esc_url($trimmed) . '" alt="ad" />';
        }
        if (preg_match('#^https?://\S+\.(mp4|webm|ogg|mov)(\?.*)?$#i', $trimmed)) {
            return '<video src="' . esc_url($trimmed) . '" autoplay muted loop playsinline></video>';
        }
        return esc_url($trimmed);
    }

    public function output_ad_script()
    {
        ?>
        <script>
        function ttsAdClick(e) {
            var ad = e.currentTarget;
            e.preventDefault();
            var link = ad.querySelector("a");
            if (link && link.href) {
                window.location.href = link.href;
                return;
            }
            var img = ad.querySelector("img");
            if (img && img.src) {
                window.open(img.src, "_blank");
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var ads = document.querySelectorAll('.tts-header-ad');
            ads.forEach(function(container) {
                var slides = container.querySelectorAll('.tts-header-ad-slide');
                if (slides.length === 0) return;
                slides[0].classList.add('active');
                var v = slides[0].querySelector('video');
                if (v) { v.currentTime = 0; v.play(); }
                if (slides.length < 2) return;
                var idx = 0;
                setInterval(function() {
                    var oldV = slides[idx].querySelector('video');
                    if (oldV) { oldV.pause(); oldV.currentTime = 0; }
                    slides[idx].classList.remove('active');
                    idx = (idx + 1) % slides.length;
                    slides[idx].classList.add('active');
                    var newV = slides[idx].querySelector('video');
                    if (newV) { newV.currentTime = 0; newV.play(); }
                }, 6000);
            });
        });
        (function() {
            var input = document.querySelector('.wp-block-search input[type="search"]');
            if (!input) return;
            var wrap = input.closest('.wp-block-search') || input.parentElement;
            var box = document.createElement('div');
            box.className = 'tts-search-results';
            box.style.cssText = 'display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ddd;max-height:360px;overflow-y:auto;z-index:99999;';
            wrap.style.position = 'relative';
            wrap.appendChild(box);
            var timer;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                var q = input.value.trim();
                if (q.length < 2) { box.style.display = 'none'; return; }
                timer = setTimeout(function() {
                    fetch('/wp-json/wp/v2/posts?search=' + encodeURIComponent(q) + '&per_page=8&_fields=title,id')
                        .then(function(r) { return r.json(); })
                        .then(function(posts) {
                            if (!posts.length || !Array.isArray(posts)) { box.style.display = 'none'; return; }
                            var origin = window.location.origin;
                            box.innerHTML = '';
                            posts.forEach(function(p) {
                                var id = p.id;
                                if (!id) return;
                                var d = document.createElement('div');
                                var title = p.title && p.title.rendered ? p.title.rendered : '(no title)';
                                d.textContent = title;
                                d.style.cssText = 'display:block;padding:10px 14px;color:#333;font-size:14px;border-bottom:1px solid #eee;cursor:pointer;';
                                d.addEventListener('mouseenter', function(){this.style.background='#f5f5f5';});
                                d.addEventListener('mouseleave', function(){this.style.background='';});
                                (function(postId) {
                                    d.addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        window.location.href = origin + '/?p=' + postId;
                                    });
                                })(id);
                                box.appendChild(d);
                            });
                            box.style.display = 'block';
                        })
                        .catch(function() { box.style.display = 'none'; });
                }, 300);
            });
            document.addEventListener('click', function(e) {
                if (!wrap.contains(e.target)) box.style.display = 'none';
            });
        })();
        </script>
        <?php
    }

    public function header_ads_shortcode()
    {
        $ads = $this->get_header_ads();
        if (empty($ads)) {
            return '';
        }
        $output = '<div class="tts-header-ad" onclick="ttsAdClick(event)">';
        $output .= '<div class="tts-header-ad-track">';
        foreach ($ads as $ad) {
            $output .= '<div class="tts-header-ad-slide">' . $this->format_ad($ad) . '</div>';
        }
        $output .= '</div></div>';
        return $output;
    }

    public function logo_shortcode($atts)
    {
        $logo_id = (int) get_option('tts_site_logo', 0);
        if (!$logo_id) {
            return '';
        }

        $atts = shortcode_atts([
            'size' => 'full',
            'class' => '',
            'alt' => get_option('tts_site_name', get_bloginfo('name')),
        ], $atts);

        $img = wp_get_attachment_image($logo_id, $atts['size'], false, [
            'class' => 'tts-logo ' . esc_attr($atts['class']),
            'alt' => esc_attr($atts['alt']),
        ]);

        return $img;
    }

    public function enqueue_frontend_assets()
    {
        wp_enqueue_style(
            'tts-frontend',
            TTS_PLUGIN_URL . 'assets/frontend.css',
            [],
            TTS_VERSION
        );

        wp_enqueue_style(
            'tts-search-suggest',
            TTS_PLUGIN_URL . 'assets/search-suggest.css',
            [],
            TTS_VERSION
        );

        wp_enqueue_script(
            'tts-search-suggest',
            TTS_PLUGIN_URL . 'assets/search-suggest.js',
            ['jquery'],
            TTS_VERSION,
            true
        );

        wp_localize_script('tts-search-suggest', 'wpRestController', [
            'url' => rest_url(),
        ]);

        wp_enqueue_script(
            'tts-news-box',
            TTS_PLUGIN_URL . 'assets/news-box.js',
            ['jquery'],
            TTS_VERSION,
            true
        );

        $audio_posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => 50,
            'meta_key' => '_tts_has_audio',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);

        wp_localize_script('tts-news-box', 'ttsAudioData', [
            'postIds' => $audio_posts,
            'label' => __('Listen to Audio', 'thetruth-settings'),
        ]);
    }

    public function add_breaking_news_meta_box()
    {
        add_meta_box(
            'tts_breaking_news',
            __('Breaking News', 'thetruth-settings'),
            [$this, 'render_breaking_news_meta_box'],
            'post',
            'side',
            'high'
        );
    }

    public function render_breaking_news_meta_box($post)
    {
        wp_nonce_field('tts_breaking_news_nonce', 'tts_breaking_news_nonce');
        $is_breaking = (int) get_post_meta($post->ID, '_tts_breaking_news', true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="tts_breaking_news" value="1" <?php checked($is_breaking, 1); ?> />
                <?php _e('Mark as Breaking News', 'thetruth-settings'); ?>
            </label>
        </p>
        <p class="description"><?php _e('Breaking news posts appear in the ticker at the top of the site.', 'thetruth-settings'); ?></p>
        <?php
    }

    public function save_breaking_news_meta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['tts_breaking_news_nonce']) || !wp_verify_nonce($_POST['tts_breaking_news_nonce'], 'tts_breaking_news_nonce')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_breaking = isset($_POST['tts_breaking_news']) ? 1 : 0;
        update_post_meta($post_id, '_tts_breaking_news', $is_breaking);
    }

    // ─── Audio Meta Box ────────────────────────────────────────────

    public function add_audio_meta_box()
    {
        add_meta_box(
            'tts_audio',
            __('Upload Audio Here', 'thetruth-settings'),
            [$this, 'render_audio_meta_box'],
            'post',
            'normal',
            'default'
        );
    }

    public function render_audio_meta_box($post)
    {
        wp_nonce_field('tts_audio_nonce', 'tts_audio_nonce');
        $audio_id = (int) get_post_meta($post->ID, '_tts_audio_id', true);
        $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
        ?>
        <style>
        #tts_audio .inside { padding: 16px; }
        #tts_audio .tts-audio-checkbox { margin-bottom: 12px; }
        #tts_audio .tts-audio-checkbox label { font-weight: 600; font-size: 14px; }
        #tts_audio .tts-audio-checkbox input[type="checkbox"] { width: 18px; height: 18px; margin-right: 6px; }
        #tts_audio .tts-audio-preview audio { width: 100%; height: 50px; margin-bottom: 8px; }
        #tts_audio .button { padding: 8px 20px; font-size: 13px; height: auto; line-height: 1.4; }
        #tts_audio .description { font-size: 12px; margin-top: 10px; }
        </style>
        <div class="tts-audio-checkbox">
            <label>
                <input type="checkbox" name="tts_has_audio" value="1" <?php checked(get_post_meta($post->ID, '_tts_has_audio', true), 1); ?> />
                <?php _e('This post has audio', 'thetruth-settings'); ?>
            </label>
        </div>
        <div class="tts-audio-upload">
            <div class="tts-audio-preview" id="tts-audio-preview" style="<?php echo $audio_url ? '' : 'display:none;'; ?>">
                <audio controls style="width:100%;height:50px;">
                    <source src="<?php echo esc_url($audio_url); ?>" />
                </audio>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="button button-primary" id="tts-upload-audio"><?php _e('Upload / Choose Audio File', 'thetruth-settings'); ?></button>
                <button type="button" class="button" id="tts-remove-audio" style="<?php echo $audio_id ? '' : 'display:none;'; ?>"><?php _e('Remove Audio', 'thetruth-settings'); ?></button>
            </div>
            <input type="hidden" name="tts_audio_id" id="tts_audio_id" value="<?php echo esc_attr($audio_id); ?>" />
        </div>
        <p class="description"><?php _e('Upload an audio file (MP3, OGG, WAV, M4A). The player will appear in the Audio Interviews section on the front page.', 'thetruth-settings'); ?></p>
        <script>
        jQuery(function($) {
            var audioFrame;
            $('#tts-upload-audio').on('click', function(e) {
                e.preventDefault();
                if (audioFrame) { audioFrame.open(); return; }
                audioFrame = wp.media({
                    title: '<?php _e('Select Audio', 'thetruth-settings'); ?>',
                    button: { text: '<?php _e('Use as Audio', 'thetruth-settings'); ?>' },
                    multiple: false,
                    library: { type: 'audio' },
                });
                audioFrame.on('select', function() {
                    var attachment = audioFrame.state().get('selection').first().toJSON();
                    $('#tts_audio_id').val(attachment.id);
                    var $preview = $('#tts-audio-preview');
                    $preview.html('<audio controls style="width:100%;height:50px;"><source src="' + attachment.url + '" /></audio>').show();
                    $('#tts-remove-audio').show();
                });
                audioFrame.open();
            });
            $('#tts-remove-audio').on('click', function(e) {
                e.preventDefault();
                $('#tts_audio_id').val('');
                $('#tts-audio-preview').hide().empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function save_audio_meta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['tts_audio_nonce']) || !wp_verify_nonce($_POST['tts_audio_nonce'], 'tts_audio_nonce')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $audio_id = isset($_POST['tts_audio_id']) ? (int) $_POST['tts_audio_id'] : 0;
        update_post_meta($post_id, '_tts_audio_id', $audio_id);

        $has_audio = isset($_POST['tts_has_audio']) ? 1 : ($audio_id > 0 ? 1 : 0);
        update_post_meta($post_id, '_tts_has_audio', $has_audio);
    }

    public function render_audio_below_editor($post)
    {
        if ('post' !== $post->post_type) {
            return;
        }
        $audio_id = (int) get_post_meta($post->ID, '_tts_audio_id', true);
        $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
        wp_nonce_field('tts_audio_nonce', 'tts_audio_nonce');
        ?>
        <div id="tts-audio-editor-section" style="margin:20px 0;padding:16px;background:#f0f0f1;border:1px solid #c3c4c7;border-radius:4px;">
            <h2 style="margin-top:0;font-size:16px;font-weight:600;"><?php _e('Upload Audio Here', 'thetruth-settings'); ?></h2>
            <p style="margin-bottom:12px;">
                <label>
                    <input type="checkbox" name="tts_has_audio" value="1" <?php checked(get_post_meta($post->ID, '_tts_has_audio', true), 1); ?> />
                    <?php _e('This post has audio', 'thetruth-settings'); ?>
                </label>
            </p>
            <div class="tts-audio-preview" id="tts-audio-preview" style="<?php echo $audio_url ? '' : 'display:none;'; ?>margin-bottom:8px;">
                <audio controls style="width:100%;height:50px;">
                    <source src="<?php echo esc_url($audio_url); ?>" />
                </audio>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" class="button button-primary" id="tts-upload-audio"><?php _e('Upload / Choose Audio File', 'thetruth-settings'); ?></button>
                <button type="button" class="button" id="tts-remove-audio" style="<?php echo $audio_id ? '' : 'display:none;'; ?>"><?php _e('Remove Audio', 'thetruth-settings'); ?></button>
                <input type="hidden" name="tts_audio_id" id="tts_audio_id" value="<?php echo esc_attr($audio_id); ?>" />
                <span style="margin-left:8px;color:#888;font-size:12px;"><?php _e('MP3, OGG, WAV, M4A', 'thetruth-settings'); ?></span>
            </div>
        </div>
        <script>
        jQuery(function($) {
            var audioFrame;
            $('#tts-upload-audio').on('click', function(e) {
                e.preventDefault();
                if (audioFrame) { audioFrame.open(); return; }
                audioFrame = wp.media({
                    title: '<?php _e('Select Audio', 'thetruth-settings'); ?>',
                    button: { text: '<?php _e('Use as Audio', 'thetruth-settings'); ?>' },
                    multiple: false,
                    library: { type: 'audio' },
                });
                audioFrame.on('select', function() {
                    var attachment = audioFrame.state().get('selection').first().toJSON();
                    $('#tts_audio_id').val(attachment.id);
                    var $preview = $('#tts-audio-preview');
                    $preview.html('<audio controls style="width:100%;height:50px;"><source src="' + attachment.url + '" /></audio>').show();
                    $('#tts-remove-audio').show();
                });
                audioFrame.open();
            });
            $('#tts-remove-audio').on('click', function(e) {
                e.preventDefault();
                $('#tts_audio_id').val('');
                $('#tts-audio-preview').hide().empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function expand_meta_boxes()
    {
        $screen = get_current_screen();
        if (!$screen || 'post' !== $screen->base) {
            return;
        }
        ?>
        <script>
        jQuery(function($) {
            var expand = function() {
                var btn = $('.edit-post-meta-boxes-area .components-panel__body-toggle');
                if (btn.length) {
                    btn.attr('aria-expanded', 'true');
                    $('.edit-post-meta-boxes-area .components-panel__body').addClass('is-opened');
                }
            };
            expand();
            setTimeout(expand, 1000);
            setTimeout(expand, 2000);
        });
        </script>
        <?php
    }

    public function audio_section_shortcode($atts)
    {
        $atts = shortcode_atts([
            'count' => 5,
            'title' => __('Audio Interviews', 'thetruth-settings'),
        ], $atts);

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_tts_has_audio', 'value' => '1'],
                ['key' => '_tts_audio_id', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($posts)) {
            return '';
        }

        $output = '<div class="tts-audio-section">';
        $output .= '<h2 class="tts-audio-title">' . esc_html($atts['title']) . '</h2>';
        $output .= '<div class="tts-audio-list">';

        foreach ($posts as $post) {
            $audio_id = (int) get_post_meta($post->ID, '_tts_audio_id', true);
            $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
            $cats = get_the_category($post->ID);
            $cat_html = '';
            if (!empty($cats)) {
                $cat_html = '<span class="tts-audio-cat">' . esc_html($cats[0]->name) . '</span>';
            }

            $output .= '<div class="tts-audio-item">';
            $output .= '<div class="tts-audio-info">';
            $output .= '<h3 class="tts-audio-headline"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html(get_the_title($post->ID)) . '</a></h3>';
            $output .= '<div class="tts-audio-meta">' . $cat_html . ' <span class="tts-audio-date">' . get_the_date(get_option('date_format'), $post->ID) . '</span></div>';
            $output .= '</div>';
            if ($audio_url) {
                $output .= '<div class="tts-audio-player">' . wp_audio_shortcode(['src' => $audio_url]) . '</div>';
            }
            $output .= '</div>';
        }

        $output .= '</div></div>';
        return $output;
    }

    // ─── Video Meta Box ────────────────────────────────────────────

    public function add_video_meta_box()
    {
        add_meta_box(
            'tts_video',
            __('Upload Video Here', 'thetruth-settings'),
            [$this, 'render_video_meta_box'],
            'post',
            'normal',
            'default'
        );
    }

    public function render_video_meta_box($post)
    {
        wp_nonce_field('tts_video_nonce', 'tts_video_nonce');
        $video_id = (int) get_post_meta($post->ID, '_tts_video_id', true);
        $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
        ?>
        <style>
        #tts_video .inside { padding: 16px; }
        #tts_video .tts-video-checkbox { margin-bottom: 12px; }
        #tts_video .tts-video-checkbox label { font-weight: 600; font-size: 14px; }
        #tts_video .tts-video-checkbox input[type="checkbox"] { width: 18px; height: 18px; margin-right: 6px; }
        #tts_video .tts-video-preview video { width: 100%; max-height: 200px; margin-bottom: 8px; background: #000; }
        #tts_video .button { padding: 8px 20px; font-size: 13px; height: auto; line-height: 1.4; }
        #tts_video .description { font-size: 12px; margin-top: 10px; }
        </style>
        <div class="tts-video-checkbox">
            <label>
                <input type="checkbox" name="tts_has_video" value="1" <?php checked(get_post_meta($post->ID, '_tts_has_video', true), 1); ?> />
                <?php _e('This post has video', 'thetruth-settings'); ?>
            </label>
        </div>
        <div class="tts-video-upload">
            <div class="tts-video-preview" id="tts-video-preview" style="<?php echo $video_url ? '' : 'display:none;'; ?>">
                <video controls style="width:100%;max-height:200px;background:#000;">
                    <source src="<?php echo esc_url($video_url); ?>" />
                </video>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="button button-primary" id="tts-upload-video"><?php _e('Upload / Choose Video File', 'thetruth-settings'); ?></button>
                <button type="button" class="button" id="tts-remove-video" style="<?php echo $video_id ? '' : 'display:none;'; ?>"><?php _e('Remove Video', 'thetruth-settings'); ?></button>
            </div>
            <input type="hidden" name="tts_video_id" id="tts_video_id" value="<?php echo esc_attr($video_id); ?>" />
        </div>
        <p class="description"><?php _e('Upload a video file (MP4, WEBM, OGV). The player will appear in the Video Clips section on the front page.', 'thetruth-settings'); ?></p>
        <script>
        jQuery(function($) {
            var videoFrame;
            $('#tts-upload-video').on('click', function(e) {
                e.preventDefault();
                if (videoFrame) { videoFrame.open(); return; }
                videoFrame = wp.media({
                    title: '<?php _e('Select Video', 'thetruth-settings'); ?>',
                    button: { text: '<?php _e('Use as Video', 'thetruth-settings'); ?>' },
                    multiple: false,
                    library: { type: 'video' },
                });
                videoFrame.on('select', function() {
                    var attachment = videoFrame.state().get('selection').first().toJSON();
                    $('#tts_video_id').val(attachment.id);
                    var $preview = $('#tts-video-preview');
                    $preview.html('<video controls style="width:100%;max-height:200px;background:#000;"><source src="' + attachment.url + '" /></video>').show();
                    $('#tts-remove-video').show();
                });
                videoFrame.open();
            });
            $('#tts-remove-video').on('click', function(e) {
                e.preventDefault();
                $('#tts_video_id').val('');
                $('#tts-video-preview').hide().empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function save_video_meta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['tts_video_nonce']) || !wp_verify_nonce($_POST['tts_video_nonce'], 'tts_video_nonce')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $video_id = isset($_POST['tts_video_id']) ? (int) $_POST['tts_video_id'] : 0;
        update_post_meta($post_id, '_tts_video_id', $video_id);

        $has_video = isset($_POST['tts_has_video']) ? 1 : ($video_id > 0 ? 1 : 0);
        update_post_meta($post_id, '_tts_has_video', $has_video);
    }

    public function render_video_below_editor($post)
    {
        if ('post' !== $post->post_type) {
            return;
        }
        $video_id = (int) get_post_meta($post->ID, '_tts_video_id', true);
        $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
        wp_nonce_field('tts_video_nonce', 'tts_video_nonce');
        ?>
        <div id="tts-video-editor-section" style="margin:20px 0;padding:16px;background:#f0f0f1;border:1px solid #c3c4c7;border-radius:4px;">
            <h2 style="margin-top:0;font-size:16px;font-weight:600;"><?php _e('Upload Video Here', 'thetruth-settings'); ?></h2>
            <p style="margin-bottom:12px;">
                <label>
                    <input type="checkbox" name="tts_has_video" value="1" <?php checked(get_post_meta($post->ID, '_tts_has_video', true), 1); ?> />
                    <?php _e('This post has video', 'thetruth-settings'); ?>
                </label>
            </p>
            <div class="tts-video-preview" id="tts-video-preview" style="<?php echo $video_url ? '' : 'display:none;'; ?>margin-bottom:8px;">
                <video controls style="width:100%;max-height:200px;background:#000;">
                    <source src="<?php echo esc_url($video_url); ?>" />
                </video>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" class="button button-primary" id="tts-upload-video"><?php _e('Upload / Choose Video File', 'thetruth-settings'); ?></button>
                <button type="button" class="button" id="tts-remove-video" style="<?php echo $video_id ? '' : 'display:none;'; ?>"><?php _e('Remove Video', 'thetruth-settings'); ?></button>
                <input type="hidden" name="tts_video_id" id="tts_video_id" value="<?php echo esc_attr($video_id); ?>" />
                <span style="margin-left:8px;color:#888;font-size:12px;"><?php _e('MP4, WEBM, OGV', 'thetruth-settings'); ?></span>
            </div>
        </div>
        <script>
        jQuery(function($) {
            var videoFrame;
            $('#tts-upload-video').on('click', function(e) {
                e.preventDefault();
                if (videoFrame) { videoFrame.open(); return; }
                videoFrame = wp.media({
                    title: '<?php _e('Select Video', 'thetruth-settings'); ?>',
                    button: { text: '<?php _e('Use as Video', 'thetruth-settings'); ?>' },
                    multiple: false,
                    library: { type: 'video' },
                });
                videoFrame.on('select', function() {
                    var attachment = videoFrame.state().get('selection').first().toJSON();
                    $('#tts_video_id').val(attachment.id);
                    var $preview = $('#tts-video-preview');
                    $preview.html('<video controls style="width:100%;max-height:200px;background:#000;"><source src="' + attachment.url + '" /></video>').show();
                    $('#tts-remove-video').show();
                });
                videoFrame.open();
            });
            $('#tts-remove-video').on('click', function(e) {
                e.preventDefault();
                $('#tts_video_id').val('');
                $('#tts-video-preview').hide().empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function video_section_shortcode($atts)
    {
        $atts = shortcode_atts([
            'count' => 5,
            'title' => __('Video Clips', 'thetruth-settings'),
        ], $atts);

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_tts_has_video', 'value' => '1'],
                ['key' => '_tts_video_id', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($posts)) {
            return '';
        }

        $output = '<div class="tts-video-section">';
        $output .= '<h2 class="tts-video-title">' . esc_html($atts['title']) . '</h2>';
        $output .= '<div class="tts-video-list">';

        foreach ($posts as $post) {
            $video_id = (int) get_post_meta($post->ID, '_tts_video_id', true);
            $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
            $cats = get_the_category($post->ID);
            $cat_html = '';
            if (!empty($cats)) {
                $cat_html = '<span class="tts-video-cat">' . esc_html($cats[0]->name) . '</span>';
            }

            $output .= '<div class="tts-video-item">';
            $output .= '<div class="tts-video-info">';
            $output .= '<h3 class="tts-video-headline"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html(get_the_title($post->ID)) . '</a></h3>';
            $output .= '<div class="tts-video-meta">' . $cat_html . ' <span class="tts-video-date">' . get_the_date(get_option('date_format'), $post->ID) . '</span></div>';
            $output .= '</div>';
            if ($video_url) {
                $output .= '<div class="tts-video-player">' . wp_video_shortcode(['src' => $video_url]) . '</div>';
            }
            $output .= '</div>';
        }

        $output .= '</div></div>';
        return $output;
    }

    public function media_section_shortcode($atts)
    {
        $atts = shortcode_atts([
            'count' => 20,
            'title' => __('Video and Audio Section', 'thetruth-settings'),
        ], $atts);

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_tts_has_video', 'value' => '1'],
                ['key' => '_tts_video_id', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
                ['key' => '_tts_has_audio', 'value' => '1'],
                ['key' => '_tts_audio_id', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($posts)) {
            return '';
        }

        $output = '<div class="tts-media-section">';
        $output .= '<h2 class="tts-media-title">' . esc_html($atts['title']) . '</h2>';
        $output .= '<div class="tts-media-list">';

        foreach ($posts as $post) {
            $video_id = (int) get_post_meta($post->ID, '_tts_video_id', true);
            $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
            $audio_id = (int) get_post_meta($post->ID, '_tts_audio_id', true);
            $audio_url = $audio_id ? wp_get_attachment_url($audio_id) : '';
            $cats = get_the_category($post->ID);
            $cat_html = '';
            if (!empty($cats)) {
                $cat_html = '<span class="tts-media-cat">' . esc_html($cats[0]->name) . '</span>';
            }

            $output .= '<div class="tts-media-item">';
            $output .= '<div class="tts-media-info">';
            $output .= '<h3 class="tts-media-headline"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html(get_the_title($post->ID)) . '</a></h3>';
            $output .= '<div class="tts-media-meta">' . $cat_html . ' <span class="tts-media-date">' . get_the_date(get_option('date_format'), $post->ID) . '</span></div>';
            $output .= '</div>';
            if ($video_url) {
                $output .= '<div class="tts-media-player">' . wp_video_shortcode(['src' => $video_url]) . '</div>';
            } elseif ($audio_url) {
                $output .= '<div class="tts-media-player">' . wp_audio_shortcode(['src' => $audio_url]) . '</div>';
            }
            $output .= '</div>';
        }

        $output .= '</div></div>';
        return $output;
    }

    public function append_video_audio_to_content($content)
    {
        if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        $extra = '';

        $video_id = (int) get_post_meta($post_id, '_tts_video_id', true);
        if ($video_id) {
            $video_url = wp_get_attachment_url($video_id);
            if ($video_url) {
                $extra .= '<div class="tts-single-video" style="margin:24px 0;">' . wp_video_shortcode(['src' => $video_url]) . '</div>';
            }
        }

        $audio_id = (int) get_post_meta($post_id, '_tts_audio_id', true);
        if ($audio_id) {
            $audio_url = wp_get_attachment_url($audio_id);
            if ($audio_url) {
                $extra .= '<div class="tts-single-audio" style="margin:24px 0;">' . wp_audio_shortcode(['src' => $audio_url]) . '</div>';
            }
        }

        if ($extra) {
            $content .= $extra;
        }

        return $content;
    }

    private function render_news_settings_tab()
    {
        ?>
        <div class="tts-tab-content">
            <div class="tts-card">
                <h2><?php _e('News Layout Settings', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Configure how news content is displayed on your site.', 'thetruth-settings'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tts_posts_per_category"><?php _e('Posts Per Category Section', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="tts_posts_per_category"
                                   name="tts_posts_per_category"
                                   value="<?php echo esc_attr(get_option('tts_posts_per_category', 5)); ?>"
                                   class="small-text"
                                   min="1" max="20" />
                            <p class="description"><?php _e('Number of posts to show in each category section.', 'thetruth-settings'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="tts_breaking_count"><?php _e('Breaking News Count', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="tts_breaking_count"
                                   name="tts_breaking_count"
                                   value="<?php echo esc_attr(get_option('tts_breaking_count', 5)); ?>"
                                   class="small-text"
                                   min="1" max="20" />
                            <p class="description"><?php _e('Number of breaking news items to show in the ticker.', 'thetruth-settings'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="tts_trending_days"><?php _e('Trending Posts Window (Days)', 'thetruth-settings'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="tts_trending_days"
                                   name="tts_trending_days"
                                   value="<?php echo esc_attr(get_option('tts_trending_days', 7)); ?>"
                                   class="small-text"
                                   min="1" max="90" />
                            <p class="description"><?php _e('Show trending posts from the last N days.', 'thetruth-settings'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="tts-card">
                <h2><?php _e('Available Shortcodes', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('Use these shortcodes in your content to add news-related features.', 'thetruth-settings'); ?></p>
                <table class="widefat" style="margin-top:12px;">
                    <thead>
                        <tr>
                            <th><?php _e('Shortcode', 'thetruth-settings'); ?></th>
                            <th><?php _e('Description', 'thetruth-settings'); ?></th>
                            <th><?php _e('Example', 'thetruth-settings'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[tts_breaking_news]</code></td>
                            <td><?php _e('Displays a scrolling breaking news ticker with the latest marked breaking news posts.', 'thetruth-settings'); ?></td>
                            <td><code>[tts_breaking_news count="5"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[tts_category_filter]</code></td>
                            <td><?php _e('Shows a filterable list of category links for news browsing.', 'thetruth-settings'); ?></td>
                            <td><code>[tts_category_filter show_count="1"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[tts_trending_posts]</code></td>
                            <td><?php _e('Displays a numbered list of trending/recent posts.', 'thetruth-settings'); ?></td>
                            <td><code>[tts_trending_posts count="5"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[tts_audio_section]</code></td>
                            <td><?php _e('Displays a list of posts with audio players.', 'thetruth-settings'); ?></td>
                            <td><code>[tts_audio_section count="5" title="Audio"]</code></td>
                        </tr>
                        <tr>
                            <td><code>[tts_video_section]</code></td>
                            <td><?php _e('Displays a list of posts with video players.', 'thetruth-settings'); ?></td>
                            <td><code>[tts_video_section count="5" title="Videos"]</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="tts-card">
                <h2><?php _e('How to Use', 'thetruth-settings'); ?></h2>
                <p class="description"><?php _e('To add news features to your site:', 'thetruth-settings'); ?></p>
                <ol style="margin-top:12px;line-height:1.8;">
                    <li><?php _e('Go to Posts and create/edit a post. Check "Mark as Breaking News" in the sidebar to feature it in the breaking news ticker.', 'thetruth-settings'); ?></li>
                    <li><?php _e('The search bar in the header allows visitors to search your news content.', 'thetruth-settings'); ?></li>
                    <li><?php _e('The main navigation includes news category links for quick filtering.', 'thetruth-settings'); ?></li>
                    <li><?php _e('Use the shortcodes above in any page or post content to display news widgets.', 'thetruth-settings'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }

    public function breaking_news_shortcode($atts)
    {
        $atts = shortcode_atts([
            'count' => (int) get_option('tts_breaking_count', 5),
        ], $atts);

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'meta_key' => '_tts_breaking_news',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($posts)) {
            $posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => $atts['count'],
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
        }

        if (empty($posts)) {
            return '';
        }

        $output = '<div class="tts-breaking-news" style="display:flex;align-items:center;background:#111;color:#fff;overflow:hidden;padding:0;">';
        $output .= '<span class="tts-breaking-label">&#9888; Breaking</span>';
        $output .= '<div style="overflow:hidden;flex:1;padding:8px 0;">';
        $output .= '<div class="tts-breaking-items">';

        foreach ($posts as $post) {
            $output .= '<a href="' . esc_url(get_permalink($post->ID)) . '" style="color:#fff;text-decoration:none;font-size:0.85rem;white-space:nowrap;">' . esc_html(get_the_title($post->ID)) . '</a>';
        }

        $output .= '</div></div></div>';

        return $output;
    }

    public function category_filter_shortcode($atts)
    {
        $atts = shortcode_atts([
            'show_count' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ], $atts);

        $categories = get_categories([
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'hide_empty' => true,
        ]);

        if (empty($categories)) {
            return '';
        }

        $current_cat = get_query_var('cat');
        $output = '<div class="tts-cat-filter">';
        $output .= '<a href="' . esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')) . '" class="' . ($current_cat ? '' : 'tts-cat-active') . '">' . __('All', 'thetruth-settings') . '</a>';

        foreach ($categories as $cat) {
            $class = $current_cat === $cat->term_id ? 'tts-cat-active' : '';
            $label = $atts['show_count'] ? $cat->name . ' (' . $cat->count . ')' : $cat->name;
            $output .= '<a href="' . esc_url(get_category_link($cat->term_id)) . '" class="' . $class . '">' . esc_html($label) . '</a>';
        }

        $output .= '</div>';

        return $output;
    }

    public function trending_posts_shortcode($atts)
    {
        $atts = shortcode_atts([
            'count' => 5,
            'days' => (int) get_option('tts_trending_days', 7),
        ], $atts);

        $date_query = null;
        if ($atts['days'] > 0) {
            $date_query = [
                [
                    'after' => '-' . $atts['days'] . ' days',
                    'column' => 'post_date',
                ],
            ];
        }

        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => $atts['count'],
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'date_query' => $date_query,
        ]);

        if (empty($posts)) {
            $posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => $atts['count'],
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
        }

        if (empty($posts)) {
            return '';
        }

        $output = '<div class="tts-trending-grid">';
        $i = 1;
        foreach ($posts as $post) {
            $output .= '<div class="tts-trending-item">';
            $output .= '<span class="tts-trending-number">' . $i . '</span>';
            $output .= '<div class="tts-trending-content">';
            $output .= '<h4 class="tts-trending-title"><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html(get_the_title($post->ID)) . '</a></h4>';
            $output .= '<span class="tts-trending-meta">' . get_the_date(get_option('date_format'), $post->ID) . '</span>';
            $output .= '</div></div>';
            $i++;
        }

        $output .= '</div>';

        return $output;
    }
}

TheTruthSettings::instance();
