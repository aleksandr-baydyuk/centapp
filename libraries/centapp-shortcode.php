<?php
function centAppShortcode()
{
    global $wpdb;
    $table_name = $wpdb->prefix . centAppController::CENTAPP_TABLE;

    $out_sum = $_POST('OutSum');
    $commission = $_POST('Commission');
    $orderId = $_POST("InvId");
    $crc = $_POST("SignatureValue");
    $status = $_POST("Status");

    $crc = strtoupper($crc);
    $my_crc = strtoupper(md5($out_sum . ":" . $orderId . ":" . get_option('centapp_token')));

    if ($my_crc == $crc) {

        if ($status == 'SUCCESS' || $status == 'OVERPAID'){
            $wpdb->update(
                $table_name,
                array(
                    'outsum' => $out_sum,
                    'commission' => $commission,
                    'status' => 1,
                    'callbackData' => json_encode($_POST)
                ),
                array( 'centappId' => $orderId )
            );

            return __('Success payment');
        }

        if ($status == 'FAIL' || $status == 'UNDERPAID') {
            $wpdb->update(
                $table_name,
                array(
                    'outsum' => $out_sum,
                    'commission' => $commission,
                    'status' => 2,
                    'callbackData' => json_encode($_POST)
                ),
                array( 'centappId' => $orderId )
            );

            return __('Failed payment');
        }

    }

    return __('Failed payment');
}

add_shortcode('centapp', 'centAppShortcode');
