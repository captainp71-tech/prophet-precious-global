<?php
/**
 * Plugin Name: Prophet Precious Global App Bridge
 * Description: App homepage, Intervention story feed, and Android push notification controls for the Prophet Precious Global Android app.
 * Version: 1.0.0
 * Author: Prophet Precious Global
 */

if (!defined('ABSPATH')) exit;

class PPG_App_Bridge {
    const OPTION = 'ppg_app_bridge_settings';
    const SLUG = 'ppg-app-bridge';

    public function __construct() {
        add_shortcode('ppg_app_home', [$this, 'render_home']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_footer', [$this, 'identity_bridge'], 99);
        add_filter('the_content', [$this, 'app_download_notice'], 20);
    }

    public function settings() {
        return wp_parse_args(get_option(self::OPTION, []), [
            'onesignal_app_id' => '',
            'onesignal_rest_api_key' => '',
            'app_download_url' => '',
            'featured_page_url' => '',
        ]);
    }

    public function admin_menu() {
        add_menu_page('PPG Android App', 'PPG Android App', 'manage_options', self::SLUG, [$this, 'admin_page'], 'dashicons-smartphone', 58);
        add_submenu_page(self::SLUG, 'Push Notifications', 'Push Notifications', 'manage_options', self::SLUG . '-push', [$this, 'push_page']);
    }

    public function register_settings() {
        register_setting('ppg_app_bridge_group', self::OPTION, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input) {
        $old = $this->settings();
        return [
            'onesignal_app_id' => sanitize_text_field($input['onesignal_app_id'] ?? ''),
            'onesignal_rest_api_key' => sanitize_text_field($input['onesignal_rest_api_key'] ?? ''),
            'app_download_url' => esc_url_raw($input['app_download_url'] ?? ''),
            'featured_page_url' => esc_url_raw($input['featured_page_url'] ?? ''),
        ];
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        $s = $this->settings();
        ?>
        <div class="wrap">
          <h1>Prophet Precious Global Android App</h1>
          <p>Configure the OneSignal Android app connection. Keep the REST API key private. Never place it in the Android app or public page source.</p>
          <form method="post" action="options.php">
            <?php settings_fields('ppg_app_bridge_group'); ?>
            <table class="form-table">
              <tr><th><label for="ppg_app_id">OneSignal App ID</label></th><td><input class="regular-text" id="ppg_app_id" name="<?php echo esc_attr(self::OPTION); ?>[onesignal_app_id]" value="<?php echo esc_attr($s['onesignal_app_id']); ?>"></td></tr>
              <tr><th><label for="ppg_rest_key">OneSignal REST API Key</label></th><td><input type="password" autocomplete="new-password" class="regular-text" id="ppg_rest_key" name="<?php echo esc_attr(self::OPTION); ?>[onesignal_rest_api_key]" value="<?php echo esc_attr($s['onesignal_rest_api_key']); ?>"></td></tr>
              <tr><th><label for="ppg_download">APK download URL</label></th><td><input type="url" class="regular-text" id="ppg_download" name="<?php echo esc_attr(self::OPTION); ?>[app_download_url]" value="<?php echo esc_attr($s['app_download_url']); ?>"></td></tr>
              <tr><th><label for="ppg_featured">Featured page URL (optional)</label></th><td><input type="url" class="regular-text" id="ppg_featured" name="<?php echo esc_attr(self::OPTION); ?>[featured_page_url]" value="<?php echo esc_attr($s['featured_page_url']); ?>"></td></tr>
            </table>
            <?php submit_button(); ?>
          </form>
          <hr>
          <h2>Create the app homepage</h2>
          <p>Create a WordPress page with the slug <code>app-home</code> and place this shortcode in its content:</p>
          <code>[ppg_app_home]</code>
          <p>Set that URL as the app's starting page in the Android project configuration.</p>
        </div>
        <?php
    }

    public function push_page() {
        if (!current_user_can('manage_options')) return;
        $notice = '';
        if (isset($_POST['ppg_send_push']) && check_admin_referer('ppg_send_push_action', 'ppg_push_nonce')) {
            $title = sanitize_text_field(wp_unslash($_POST['push_title'] ?? ''));
            $message = sanitize_textarea_field(wp_unslash($_POST['push_message'] ?? ''));
            $url = esc_url_raw(wp_unslash($_POST['push_url'] ?? ''));
            $target = sanitize_text_field(wp_unslash($_POST['push_target'] ?? 'all'));
            $user_ids = sanitize_text_field(wp_unslash($_POST['push_user_ids'] ?? ''));
            $result = $this->send_push($title, $message, $url, $target, $user_ids);
            $notice = is_wp_error($result) ? $result->get_error_message() : 'The notification request was accepted by OneSignal. Check OneSignal delivery statistics for final delivery results.';
        }
        ?>
        <div class="wrap">
          <h1>Android Push Notifications</h1>
          <?php if ($notice) echo '<div class="notice notice-info"><p>' . esc_html($notice) . '</p></div>'; ?>
          <p>Send to Android app subscribers only. To target individual members, enter their WordPress user IDs separated by commas. Those users must have opened the Android app and allowed notifications.</p>
          <form method="post">
            <?php wp_nonce_field('ppg_send_push_action', 'ppg_push_nonce'); ?>
            <table class="form-table">
              <tr><th><label for="push_title">Notification title</label></th><td><input required maxlength="80" class="regular-text" id="push_title" name="push_title"></td></tr>
              <tr><th><label for="push_message">Message</label></th><td><textarea required maxlength="240" rows="4" class="large-text" id="push_message" name="push_message"></textarea></td></tr>
              <tr><th><label for="push_url">Open this page when tapped (optional)</label></th><td><input type="url" class="regular-text" id="push_url" name="push_url" placeholder="https://prophetprecious.com/..."></td></tr>
              <tr><th>Audience</th><td><label><input type="radio" name="push_target" value="all" checked> All Android app subscribers</label><br><label><input type="radio" name="push_target" value="selected"> Selected WordPress user IDs</label></td></tr>
              <tr><th><label for="push_user_ids">WordPress user IDs</label></th><td><input class="regular-text" id="push_user_ids" name="push_user_ids" placeholder="12, 18, 29"></td></tr>
            </table>
            <?php submit_button('Send Android Notification', 'primary', 'ppg_send_push'); ?>
          </form>
        </div>
        <?php
    }

    private function send_push($title, $message, $url, $target, $user_ids_csv) {
        $s = $this->settings();
        if (!$s['onesignal_app_id'] || !$s['onesignal_rest_api_key']) {
            return new WP_Error('ppg_missing_config', 'Add your OneSignal App ID and REST API Key in PPG Android App settings first.');
        }
        if (!$title || !$message) return new WP_Error('ppg_missing_text', 'A title and message are required.');

        $payload = [
            'app_id' => $s['onesignal_app_id'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'channel_for_external_user_ids' => 'push',
            'target_channel' => 'push',
            'data' => ['source' => 'wordpress_admin'],
        ];
        if ($url) $payload['url'] = $url;

        if ($target === 'selected') {
            $ids = array_filter(array_map('absint', explode(',', $user_ids_csv)));
            if (!$ids) return new WP_Error('ppg_no_users', 'Enter at least one valid WordPress user ID.');
            $external_ids = [];
            foreach ($ids as $id) {
                if (get_user_by('id', $id)) $external_ids[] = 'wp_' . $id;
            }
            if (!$external_ids) return new WP_Error('ppg_no_valid_users', 'None of those WordPress user IDs were found.');
            $payload['include_aliases'] = ['external_id' => array_values($external_ids)];
        } else {
            // The app sets this tag after native Android initialization.
            $payload['included_segments'] = ['Subscribed Users'];
            $payload['filters'] = [
                ['field' => 'tag', 'key' => 'platform', 'relation' => '=', 'value' => 'android']
            ];
        }

        $response = wp_remote_post('https://api.onesignal.com/notifications', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Key ' . $s['onesignal_rest_api_key'],
            ],
            'body' => wp_json_encode($payload),
        ]);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            $detail = $body['errors'][0] ?? ('OneSignal returned HTTP ' . $code);
            return new WP_Error('ppg_onesignal_error', is_string($detail) ? $detail : wp_json_encode($detail));
        }
        return $body;
    }

    public function frontend_assets() {
        if (!is_page('app-home')) return;
        wp_register_style('ppg-app-home', false);
        wp_enqueue_style('ppg-app-home');
        wp_add_inline_style('ppg-app-home', $this->css());
    }

    private function css() {
        return '
        .ppg-app{max-width:1120px;margin:0 auto;padding:22px 16px;color:#201b2d;font-family:inherit}
        .ppg-hero{background:#f4efff;border-radius:22px;padding:34px 25px;margin-bottom:26px}
        .ppg-kicker{color:#6b35bd;font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:12px}
        .ppg-hero h1{font-size:clamp(30px,5vw,48px);line-height:1.08;margin:10px 0;color:#271447}
        .ppg-tagline{font-size:16px;color:#51465f;margin:0 0 20px}
        .ppg-btn{display:inline-block;background:#6032a8;color:#fff!important;padding:12px 18px;border-radius:9px;text-decoration:none!important;font-weight:700;margin:4px 7px 4px 0}
        .ppg-btn-red{background:#b52335}
        .ppg-section{margin:30px 0}
        .ppg-section h2{font-size:23px;margin:0 0 15px;color:#29164a}
        .ppg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}
        .ppg-story{border:1px solid #eee8f6;border-radius:15px;overflow:hidden;background:#fff}
        .ppg-story img{width:100%;height:170px;object-fit:cover;display:block}
        .ppg-story-body{padding:16px}
        .ppg-story h3{font-size:18px;line-height:1.3;margin:0 0 9px}
        .ppg-story h3 a{color:#29164a;text-decoration:none}
        .ppg-story p{font-size:14px;color:#625b6b;margin:0}
        .ppg-links{display:flex;flex-wrap:wrap;gap:10px}
        .ppg-panel{background:#faf8fd;border:1px solid #eee8f6;border-radius:15px;padding:20px}
        @media(max-width:600px){.ppg-hero{padding:26px 19px}.ppg-story img{height:150px}}
        ';
    }

    public function render_home() {
        $posts = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'category_name' => 'intervention',
            'ignore_sticky_posts' => true,
        ]);
        ob_start(); ?>
        <main class="ppg-app">
          <section class="ppg-hero">
            <div class="ppg-kicker">Prophet Precious Global</div>
            <h1>Understanding the Hidden. Changing the Outcome.</h1>
            <p class="ppg-tagline">Prophetic insight, prayer, and direction for the issues that matter in your life.</p>
            <a class="ppg-btn" href="<?php echo esc_url(home_url('/general-diagnosis')); ?>">Request Spiritual Diagnosis</a>
            <a class="ppg-btn ppg-btn-red" href="<?php echo esc_url(home_url('/register-to-the-prayer-altar/')); ?>">Join the Prayer Altar</a>
          </section>

          <section class="ppg-section">
            <h2>Latest Stories and Prophetic Updates</h2>
            <div class="ppg-grid">
              <?php if ($posts->have_posts()): while ($posts->have_posts()): $posts->the_post(); ?>
                <article class="ppg-story">
                  <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) the_post_thumbnail('medium_large'); ?>
                  </a>
                  <div class="ppg-story-body">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>
                  </div>
                </article>
              <?php endwhile; wp_reset_postdata(); else: ?>
                <div class="ppg-panel">New stories and updates will appear here when they are published in the Intervention category.</div>
              <?php endif; ?>
            </div>
          </section>

          <section class="ppg-section">
            <h2>Connect with the Ministry</h2>
            <div class="ppg-grid">
              <div class="ppg-panel"><h3>Prayer Altar</h3><p>Join the Financial Breakthrough Prayer Altar and access prayer support through your member dashboard.</p><a class="ppg-btn" href="<?php echo esc_url(home_url('/register-to-the-prayer-altar/')); ?>">Register</a></div>
              <div class="ppg-panel"><h3>Programmes and Meetings</h3><p>Find current ministry programmes, announcements, and meeting information.</p><a class="ppg-btn" href="<?php echo esc_url(home_url('/')); ?>">Explore the Website</a></div>
              <div class="ppg-panel"><h3>Member Account</h3><p>Sign in to access your account, registrations, and available member resources.</p><a class="ppg-btn" href="<?php echo esc_url(wp_login_url(home_url('/partner-dashboard/'))); ?>">Sign In</a></div>
            </div>
          </section>
          <?php if (!is_user_logged_in()): ?>
            <section class="ppg-section ppg-panel"><strong>Already registered?</strong> <a href="<?php echo esc_url(wp_login_url(home_url('/partner-dashboard/'))); ?>">Sign in to your account</a>.</section>
          <?php else: ?>
            <section class="ppg-section ppg-panel"><strong>You are signed in.</strong> <a href="<?php echo esc_url(home_url('/partner-dashboard/')); ?>">Open your dashboard</a>.</section>
          <?php endif; ?>
        </main>
        <?php
        return ob_get_clean();
    }

    public function identity_bridge() {
        if (!is_user_logged_in()) return;
        $user_id = get_current_user_id();
        ?>
        <script>
        (function(){
          function setIdentity(){
            try {
              if (window.PPGNative && typeof window.PPGNative.login === 'function') {
                window.PPGNative.login(<?php echo (int)$user_id; ?>);
              }
            } catch(e) {}
          }
          if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setIdentity);
          else setIdentity();
        })();
        </script>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('ppg-app/v1', '/feed', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_feed'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_feed() {
        $q = new WP_Query([
            'post_type' => 'post', 'post_status' => 'publish',
            'posts_per_page' => 10, 'category_name' => 'intervention',
        ]);
        $items = [];
        foreach ($q->posts as $post) {
            $items[] = [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'excerpt' => wp_strip_all_tags(get_the_excerpt($post)),
                'date' => get_the_date('c', $post),
                'image' => get_the_post_thumbnail_url($post, 'large') ?: '',
            ];
        }
        return rest_ensure_response($items);
    }

    public function app_download_notice($content) {
        return $content;
    }
}
new PPG_App_Bridge();
