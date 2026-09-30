<?php
/*
 * Plugin Name: WPConsent Elementor
 * Description: Elementor compatibility fixes for WPConsent
 * Author: Sigrid Rittby
 * Author URI: https://lumence.dev/plugin/wpconsent-elementor
 * License: GPLv2
 * Version: 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/includes/ElementorContentBlocker.php';

function lx_wpconsent_blocked_scripts(array $categorized_scripts): array
{
    $categorized_scripts["marketing"]["google-maps"]["iframes"][] = "maps.google.com/maps";
    $categorized_scripts["marketing"]["youtube"]["scripts"][] = ELEMENTOR_ASSETS_URL . "js/youtube-handler";

    return $categorized_scripts;
}
add_filter( 'wpconsent_blocked_scripts', "lx_wpconsent_blocked_scripts", 10, 2 );

function lx_wpconsent_force_service_detection(array $data): void
{
    $categories = apply_filters("lx_wpconsent_forced_services", ["marketing" => ["youtube","google-maps"]]);
    $all = array_unique(array_merge($data["services_needed"], ...array_values($categories)));

    wpconsent()->file_cache->delete("services");
    $services_needed = (new WPConsent_Services())->get_services($all);

    foreach($categories as $category => $services) {
        $services_diff = array_diff($services, array_column($data["scripts"][$category] ?? [], "name"));
        foreach($services_diff as $service) {
            $data["scripts"][$category][] = [
                "name"        => $service,
                "service"     => $services_needed[$service]["label"],
                "logo"        => $services_needed[$service]["logo"],
                "cookies"     => $services_needed[$service]["cookies"],
                "description" => $services_needed[$service]["description"],
                "url"         => $services_needed[$service]["service_url"]
            ];
        }
    }

    $data["services_needed"] = $all;

    $scanner_data = array(
        'date' => current_time( 'mysql' ),
        'data' => $data,
    );
    update_option("wpconsent_scanner_data", $scanner_data);
}
add_action( 'wpconsent_scan_data_saved', "lx_wpconsent_force_service_detection", 99, 1 );

function lx_wpconsent_admin_notices(): void
{
    ?>
    <?php if(get_option("elementor_local_google_fonts", 0) != 1): ?>
    <div class="notice notice-warning">
        Your site is loading remote Google Fonts and is not GDPR-compliant. <a href="<?php echo esc_url(get_admin_url(null, "/admin.php?page=elementor-settings#tab-performance")); ?>">Click here</a> to fix.
    </div>
    <?php endif; ?>
    <?php
}
add_action("admin_notices", "lx_wpconsent_admin_notices");

function lx_wpconsent_scripts(): void
{
    wp_enqueue_style("lx-wpconsent-compat-css", plugin_dir_url(__FILE__) . 'assets/css/wpconsent-elementor-compat.css');
    wp_enqueue_script("lx-wpconsent-compat-js", plugin_dir_url(__FILE__) . 'assets/js/wpconsent-elementor-compat.js', [], filemtime(plugin_dir_path(__FILE__) . 'assets/js/wpconsent-elementor-compat.js'), true);
}
add_action("wp_enqueue_scripts", "lx_wpconsent_scripts");

// Disable auto-updates as this plugin is not distributed via wordpress.org
add_filter("site_transient_update_plugins", function($value) {
    $plugin = plugin_basename(__FILE__);
    if(isset($value) && is_object($value)) {
        if(isset($value->response[$plugin])) {
            unset($value->response[$plugin]);
        }
    }
    return $value;
});
