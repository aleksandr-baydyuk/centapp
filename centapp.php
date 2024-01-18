<?php
/**
 * Cent.app Payments
 *
 * @package       centapp
 * @author        Baidiuk A.
 *
 * @wordpress-plugin
 * Plugin Name: Cent.app Payments
 * Plugin URI: https://github.com/aleksandr-baydyuk/centapp
 * Description: WordPress plugin for creating and accounting payments Cent.app.
 * Version: 1.0.0
 * Author: Baidiuk A.
 * Author URI: https://baidiuk.com/
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 **/

require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-content/plugins/centapp/libraries/centapp-layout.php');
require_once(ABSPATH . 'wp-content/plugins/centapp/libraries/centapp-shortcode.php');

class centAppController
{
    const TAB_DASHBOARD = 'dashboard';
    const TAB_PAYMENTS = 'payments';
    const TAB_SETTINGS = 'settings';
    const CENTAPP_TABLE = 'centapp';

    protected static $instance = NULL;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_uninstall_hook( __FILE__, 'uninstall');

        add_action('admin_menu', array($this, 'addPluginToMenu'));
        add_action('init', array($this, 'updateConfig'));
    }

    function activate()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . self::CENTAPP_TABLE;

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            centappId varchar(32) NOT NULL,
            currency varchar(3) NOT NULL,
            amount float(12,2) NOT NULL DEFAULT 0,
            commission float(12,2) NOT NULL DEFAULT 0,
            outsum float(12,2) NOT NULL DEFAULT 0,
            link varchar(255) NOT NULL,
            status int(11) NOT NULL DEFAULT 0,
            createdAt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updatedAt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            notes text,
            callbackData text,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( $sql );
    }

    static function uninstall() {
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            die;
        }

        /* SETTINGS */
        delete_option( 'centapp_base_url' );
        delete_site_option( 'centapp_shop_id' );
        delete_site_option( 'centapp_token' );

    }

    function addPluginToMenu()
    {
        add_menu_page(__( 'Cent.app Payments', 'centapp'), __( 'Cent.app', 'centapp'), 'manage_options', 'centapp', 'centAppLayout', 'dashicons-carrot');
    }

    function updateConfig()
    {
        if (isset($_POST['centapp-action']) && $_POST['centapp-action'] == self::TAB_SETTINGS ) {
            if (
                isset($_POST['centapp_nonce']) &&
                !empty($_POST['centapp_nonce']) &&
                wp_verify_nonce(sanitize_key($_POST['centapp_nonce']), 'centapp_nonce')
            ) {
                update_option('centapp_base_url', isset($_POST['base_url']) ? sanitize_text_field($_POST['base_url']) : '');
                update_option('centapp_shop_id', isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '');
                update_option('centapp_token', isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '');

                add_action('admin_notices', array($this, 'updateConfigSuccessNotice'));
            }
        }

        if (isset($_POST['centapp-action']) && $_POST['centapp-action'] == self::TAB_DASHBOARD ) {
            if (
                isset($_POST['centapp_nonce']) &&
                !empty($_POST['centapp_nonce']) &&
                wp_verify_nonce(sanitize_key($_POST['centapp_nonce']), 'centapp_nonce')
            ) {
                $amount =  isset($_POST['amount']) ? sanitize_text_field($_POST['amount']) : 0;
                $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
                $notes = isset($_POST['notes']) ? sanitize_text_field($_POST['notes']) : '';

                if ($amount != '' && $subject != '' && $notes != ''){
                    global $wpdb;
                    $table_name = $wpdb->prefix . centAppController::CENTAPP_TABLE;

                    $currentDate = new DateTimeImmutable();
                    $orderDate = $currentDate->format('Y-m-d H:i:s');

                    $checkoutData  = 'amount='.$amount;
                    $checkoutData .= '&order_id='.urlencode(md5($orderDate));
                    $checkoutData .= '&type=normal';
                    $checkoutData .= '&shop_id='.get_option('centapp_shop_id');
                    $checkoutData .= '&currency_in=RUB';
                    $checkoutData .= '&payer_pays_commission=1';
                    $checkoutData .= '&name='.urlencode($subject);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => get_option('centapp_base_url').'bill/create',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>  $checkoutData,
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Bearer '.get_option('centapp_token'),
                            'Content-Type: application/x-www-form-urlencoded'
                        ),
                    ));

                    $response = curl_exec($curl);
                    curl_close($curl);

                    $linkInfo = json_decode($response);

                    if (!in_array('bill_id', $linkInfo)){
                        add_action('admin_notices', array($this, 'createRequestErrorNotice'));
                    } else {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'centappId' => $linkInfo['bill_id'],
                                'currency' => 'RUB',
                                'amount' => $amount,
                                'commission' => 0,
                                'outsum' => 0,
                                'link' => $linkInfo['link_page_url'],
                                'status' => 0,
                                'createdAt' => $orderDate,
                                'updatedAt' => $orderDate,
                                'notes' => $notes,
                                'callbackData' => '{}',
                            )
                        );
                        add_action('admin_notices', array($this, 'createRequestSuccessNotice'));
                    }

                } else {

                    add_action('admin_notices', array($this, 'createRequestErrorNotice'));
                }
            }
        }
    }

    function updateConfigSuccessNotice()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?=__('Settings saved.', 'centapp'); ?></p>
            <button type="button" class="notice-dismiss"></button>
        </div>
        <?php
    }

    function createRequestSuccessNotice()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?=__('Payment Request created.', 'centapp'); ?></p>
            <button type="button" class="notice-dismiss"></button>
        </div>
        <?php
    }

    function createRequestErrorNotice()
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?=__('Error creating Payment Request. Try again.', 'centapp'); ?></p>
            <button type="button" class="notice-dismiss"></button>
        </div>
        <?php
    }
}




$centappClient = centAppController::getInstance();