<?php
/*
Plugin Name: DTS Dealer Locator
Description: This plugin allows users to find licensed FFL dealers based on ZIP code and radius selection.
Version: 0.0.2
Author: DareToSurpass
*/

if (!defined('ABSPATH')) {
    exit;
}

function ffl_dealer_finder_shortcode() {
    ob_start();
    ?>
    <button type="button" id="find-a-dealer-btn">Find A Dealer</button>
    <div id="ffl-dealer-finder-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Find A Dealer</h3>
            <div>
                <label for="ffl-zip-code">Enter Zip Code:</label>
                <input type="text" id="ffl-zip-code" />
            </div>
            <div>
                <label for="ffl-radius">Choose Radius in Miles:</label>
                <input type="range" id="ffl-radius" min="0" max="100" value="15" />
                <span id="radius-value">15</span> miles
            </div>
            <button id="ffl-find-dealer-btn">Submit</button>
            <div id="ffl-dealer-results"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ffl_dealer_finder', 'ffl_dealer_finder_shortcode');

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'enqueue_ffl_dealer_finder_scripts');
function enqueue_ffl_dealer_finder_scripts() {
    wp_enqueue_script('ffl-dealer-finder', plugin_dir_url(__FILE__) . 'assets/ffl-dealer-finder.js', array('jquery'), '1.0', true);
    wp_localize_script('ffl-dealer-finder', 'fflAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ffl_dealer_nonce'),
    ));
    wp_enqueue_style('ffl-dealer-finder-styles', plugin_dir_url(__FILE__) . 'assets/ffl-dealer-finder.css');
}

// Add modal directly to the WooCommerce checkout page
add_action('woocommerce_after_checkout_billing_form', 'add_ffl_dealer_finder_to_checkout');
function add_ffl_dealer_finder_to_checkout() {
    echo do_shortcode('[ffl_dealer_finder]');
}

// Handle the dealer search
add_action('wp_ajax_nopriv_search_ffl_dealers', 'search_ffl_dealers');
add_action('wp_ajax_search_ffl_dealers', 'search_ffl_dealers');

function search_ffl_dealers() {
    check_ajax_referer('ffl_dealer_nonce', 'security');

    $zip_code = sanitize_text_field($_POST['zip_code']);
    $radius = intval($_POST['radius']);

    $results = [];

    if (($handle = fopen(plugin_dir_path(__FILE__) . '0524-ffl-list-complete(Sheet1).csv', 'r')) !== FALSE) {
        $header = fgetcsv($handle, 1000, ',');
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $dealer = array_combine($header, $data);
            $dealer_data = [
                'license_name'   => $dealer['LICENSE_NAME'],
                'business_name'  => $dealer['BUSINESS_NAME'],
                'address'        => $dealer['PREMISE_STREET'],
                'city'           => $dealer['PREMISE_CITY'],
                'state'          => $dealer['PREMISE_STATE'],
                'zip_code'       => $dealer['PREMISE_ZIP_CODE'],
            ];

            // Calculate distance (dummy function, replace with actual distance calculation)
            $distance = calculate_distance($zip_code, $dealer_data['zip_code']);

            if ($distance <= $radius) {
                $dealer_data['distance'] = $distance;
                $results[] = $dealer_data;
            }
        }
        fclose($handle);
    }

    // Sort results by distance
    usort($results, function($a, $b) {
        return $a['distance'] - $b['distance'];
    });

    // Limit results to 10
    $results = array_slice($results, 0, 10);

    wp_send_json_success($results);
}

// Dummy distance calculation function (replace with actual implementation)
function calculate_distance($zip1, $zip2) {
    // Implement actual distance calculation logic here
    return rand(1, 100); // Placeholder
}

// Add dealer to shipping information
add_action('wp_ajax_add_ffl_dealer_to_shipping', 'add_ffl_dealer_to_shipping');

function add_ffl_dealer_to_shipping() {
    check_ajax_referer('ffl_dealer_nonce', 'security');

    $dealer_info = $_POST['dealer_info'];

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'shipping_first_name', sanitize_text_field($dealer_info['business_name']));
        update_user_meta($user_id, 'shipping_address_1', sanitize_text_field($dealer_info['address']));
        update_user_meta($user_id, 'shipping_city', sanitize_text_field($dealer_info['city']));
        update_user_meta($user_id, 'shipping_state', sanitize_text_field($dealer_info['state']));
        update_user_meta($user_id, 'shipping_postcode', sanitize_text_field($dealer_info['zip_code']));
        wp_send_json_success();
    } else {
        wp_send_json_error('User not logged in');
    }
}
?>