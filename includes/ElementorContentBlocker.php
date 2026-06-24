<?php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $lx_elementor_widget_ids_to_block;
$lx_elementor_widget_ids_to_block = [];

function lx_wpconsent_elementor_widget_filter(Widget_Base $widget): void
{
    global $lx_elementor_widget_ids_to_block;
    static $widgets = [];
    if(empty($widgets)) {
        $widgets_to_block = apply_filters("lx/elementor/widgets_to_block", ["marketing" => ["youtube" => ["video-playlist", "video", "e-youtube", "media-carousel"]]]);
        foreach($widgets_to_block as $c => $category) {
            foreach($category as $s => $service) {
                foreach($service as $w) {
                    $widgets[$w] = array("widget" => $w, "service" => $s, "category" => $c);
                }
            }
        }
    }

    if(!apply_filters("lx/elementor/should_block_widget", array_key_exists($widget->get_name(), $widgets), $widget)) {
        return;
    }
    else {
        $w = $widgets[$widget->get_name()];
        $content_placeholder = new WPConsent_Content_Placeholder();
        $io = $widget->get_settings_for_display("image_overlay");
        if(is_array($io)) $io = $io["url"];
        $placeholder_html = $content_placeholder->get_placeholder_html(
            '',
            apply_filters("lx/elementor/blocked_widget_service", $w["service"], $widget),
            apply_filters("lx/elementor/blocked_widget_category", $w["category"], $widget),
            match($widget->get_name()) {
                "video" => $widget->get_settings_for_display("youtube_url") ?? $widget->get_settings_for_display("vimeo_url") ?? $widget->get_settings_for_display("dailymotion_url"),
                default => ""
            }
        );
        if($io) $placeholder_html = preg_replace("/<img [^>]*>/", '<img decoding=\"async\" src="' . $io . '" />', $placeholder_html);
        $atts = apply_filters("lx/elementor/widget_placeholder_attributes", ["class" => "lx-wpconsent-blocked-widget elementor-element lx-wpconsent-blocked-widget-" . $widget->get_name(), "style" => ""], $widget);
        $atts = array_map(fn($key) => esc_attr($key) . '="' . esc_attr($atts[$key]) . '"', array_keys($atts));
        $atts = implode(" ", $atts);
        echo "<div " . $atts . ">" . $placeholder_html . "<!--";
        $lx_elementor_widget_ids_to_block[] = $widget->get_id();
    }
}
if(!is_admin()) add_filter("elementor/frontend/widget/before_render", "lx_wpconsent_elementor_widget_filter", 10, 1);

function lx_wpconsent_elementor_after_render(Widget_Base $widget): void {
    global $lx_elementor_widget_ids_to_block;
    if(in_array($widget->get_id(), $lx_elementor_widget_ids_to_block)) {
        echo "--></div>";
    }
}
if(!is_admin()) add_filter("elementor/frontend/widget/after_render", "lx_wpconsent_elementor_after_render", 10, 1);

function lx_wpconsent_elementor_widget_special_cases(bool $should_block, Widget_Base $widget): bool
{
    return $should_block && match ($widget->get_name()) {
        "video" => $widget->get_settings_for_display("video_type") != "hosted",
        "video-playlist" => array_any($widget->get_settings_for_display("tabs"), fn($tab) => $tab["type"] != "hosted" ),
        default => true,
    };
}
add_filter("lx/elementor/should_block_widget", "lx_wpconsent_elementor_widget_special_cases", 20, 2);

function lx_wpconsent_elementor_placeholder_attributes(array $attributes, Widget_Base $widget): array
{
    switch($widget->get_name()) {
        case "video":
            $ar = $widget->get_settings_for_display("aspect_ratio") ?? "169";
            $ratio = $widget->get_controls("aspect_ratio")["selectors_dictionary"][$ar];
            $attributes["style"] .= "--aspect-ratio: $ratio;";
            $attributes["class"] .= " lx-wpconsent-ratio-placeholder";
            break;
    }
    return $attributes;
}
add_filter("lx/elementor/widget_placeholder_attributes", "lx_wpconsent_elementor_placeholder_attributes", 20, 2);

function lx_wpcontent_elementor_widget_service(string $service, Widget_Base $widget): string
{
    return match($widget->get_name()) {
        "video" => $widget->get_settings_for_display("video_type"),
        "video-playlist" => count(array_unique($type = array_column($widget->get_settings_for_display("tabs"), "type"))) == 1 ? $type[0] : "",
        "media-carousel" => "",
        default => $service
    };
}
add_filter("lx/elementor/blocked_widget_service", "lx_wpcontent_elementor_widget_service", 20, 2);
