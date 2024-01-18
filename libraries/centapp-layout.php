<?php
function centAppLayout()
{
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : centAppController::TAB_DASHBOARD;
    ?>

    <div class="centapp">
        <div class="wrap">
            <div class="wp-heading-inline">
                <h1><?=__( 'Cent.app Payments', 'centapp'); ?></h1>
            </div>

            <hr class="wp-header-end">

            <ul class="subsubsub">
                <li class="tab">
                    <a
                        href="<?=esc_url_raw(add_query_arg(array('tab' => centAppController::TAB_DASHBOARD), $_SERVER['REQUEST_URI'])); ?>"
                        class="<?=$active_tab == centAppController::TAB_DASHBOARD ? 'current' : ''; ?>"
                        aria-current="page"
                    >
                        <?=__( 'Dashboard', 'centapp'); ?>
                    </a> |</li>
                <li class="tab">
                    <a
                        href="<?=esc_url_raw(add_query_arg(array('tab' => centAppController::TAB_PAYMENTS), $_SERVER['REQUEST_URI'])); ?>"
                        class="<?=$active_tab == centAppController::TAB_PAYMENTS ? 'current' : ''; ?>"
                        aria-current="page"
                    >
                        <?=__( 'Payments', 'centapp'); ?>
                    </a> |
                </li>
                <li class="tab">
                    <a
                        href="<?=esc_url_raw(add_query_arg(array('tab' => centAppController::TAB_SETTINGS), $_SERVER['REQUEST_URI'])); ?>"
                        class="<?=$active_tab == centAppController::TAB_SETTINGS ? 'current' : ''; ?>"
                        aria-current="page"
                    >
                        <?=__( 'Credentials', 'centapp'); ?>
                    </a>
                </li>
            </ul>

            <div class="clear"></div>
            <div style="display: block; position:relative; width: calc(100% - 30px); background-color:#fff; padding: 15px;">
                <?php switch ($active_tab){
                    case centAppController::TAB_DASHBOARD:
                        centAppDashboard();
                        break;
                    case centAppController::TAB_PAYMENTS:
                        centAppPayments();
                        break;
                    case centAppController::TAB_SETTINGS:
                        centAppSettings();
                        break;
                } ?>
            </div>
        </div>
    </div>
<?php
}

function centAppDashboard()
{
    global $wpdb;
    $table_name = $wpdb->prefix . centAppController::CENTAPP_TABLE;
    $all = $wpdb->get_row( "SELECT count(id) as total, sum(amount) as profit FROM $table_name" );
    $paid = $wpdb->get_row( "SELECT count(id) as total, sum(amount) as profit FROM $table_name WHERE status = 1" );
    $notpaid = $wpdb->get_row( "SELECT count(id) as total, sum(amount) as profit FROM $table_name WHERE status != 1" );
    ?>
        <h3><?=__('Dashboard', 'centapp'); ?></h3>

        <div style="display: flex; flex-direction: row; justify-content: space-between; gap: 30px;">
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; background-color:#aaccff; width: calc((100% - 60px) / 3);">
                <h4 style="margin: 0 0 5px;">Created</h4>
                <div style="font-size: 32px; display: flex; font-weight: bold; flex-direction: column; justify-content: center; align-items: center; color:  #000; line-height: 110%;">
                    <?=$all->total; ?>
                    <div style="font-size: 14px; font-weight: bold; display: block; margin-left: 10px; font-size: 12px; color: #0783be; ">[ <?=$all->profit; ?> RUB ]</div>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; background-color:#aaffbc; width: calc((100% - 60px) / 3);">
                <h4 style="margin: 0 0 5px;">Paid</h4>
                <div style="font-size: 32px; display: flex; font-weight: bold; flex-direction: column; justify-content: center; align-items: center; color:  #000; line-height: 110%;">
                    <?=$paid->total; ?>
                    <div style="font-size: 14px; font-weight: bold; display: block; margin-left: 10px; font-size: 12px; color: #0783be; ">[ <?=$paid->profit; ?> RUB ]</div>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; background-color:#ffc9aa; width: calc((100% - 60px) / 3);">
                <h4 style="margin: 0 0 5px;">Not paid</h4>
                <div style="font-size: 32px; display: flex; font-weight: bold; flex-direction: column; justify-content: center; align-items: center; color:  #000; line-height: 110%;">
                    <?=$notpaid->total; ?>
                    <div style="font-size: 14px; font-weight: bold; display: block; margin-left: 10px; font-size: 12px; color: #0783be; ">[ <?=$notpaid->profit; ?> RUB ]</div>
                </div>
            </div>
        </div>

        <br>

        <h3><?=__('New Payment Request', 'centapp'); ?></h3>

        <form id="centapp-<?=centAppController::TAB_DASHBOARD; ?>" method="post" action="" style="padding: 15px; background: aliceblue;">

            <input type="hidden" name="centapp-action" value="<?=centAppController::TAB_DASHBOARD; ?>" />
            <?php wp_nonce_field('centapp_nonce', 'centapp_nonce') ?>

            <table class="form-table" role="presentation">
                <tbody>

                <tr>
                    <th scope="row">
                        <label for="amount"><?=__('Amount', 'centapp'); ?></label>
                    </th>
                    <td>
                        <input
                                name="amount"
                                type="text"
                                id="amount"
                                placeholder="500"
                                class="regular-text"
                        /> RUB
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="notes"><?=__('Subject', 'centapp'); ?></label>
                    </th>
                    <td>
                        <input
                                name="subject"
                                type="text"
                                id="subject"
                                placeholder="Donate"
                                class="regular-text"
                                style="width: 100%;"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="notes"><?=__('Notes', 'centapp'); ?></label>
                    </th>
                    <td>
                        <textarea
                                name="notes"
                                id="notes"
                                placeholder="Payer, notes, etc."
                                class="regular-text"
                                style="width: 100%;"
                        ></textarea>
                    </td>
                </tr>

                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?=__('Create Payment Request', 'centapp'); ?>">
            </p>
        </form>
    <?php
}

function centAppPayments()
{
    global $wpdb;
    $table_name = $wpdb->prefix . centAppController::CENTAPP_TABLE;
    $results = $wpdb->get_results( "SELECT * FROM $table_name" );

    ?>
        <table class="wp-list-table widefat fixed striped table-view-list payments">
            <thead>
                <tr>
                    <th scope="col">CentappId</th>
                    <th scope="col">Payer</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Currency</th>
                    <th scope="col">Link</th>
                    <th scope="col">Commission</th>
                    <th scope="col">OutSum</th>
                    <th scope="col">Status</th>
                    <th scope="col">createdAt</th>
                    <th scope="col">updatedAt</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php foreach ( $results as $result ) : ?>
                    <tr id="payment-<?=$result->id; ?>">
                        <td><?=$result->centappId; ?></td>
                        <td><?=$result->notes; ?></td>
                        <td><?=$result->amount; ?></td>
                        <td><?=$result->currency; ?></td>
                        <td><?=$result->link; ?></td>
                        <td><?=$result->commission; ?></td>
                        <td><?=$result->outsum; ?></td>
                        <td><?=$result->status; ?></td>
                        <td class='date column-date' data-colname="Date"><?=$result->createdAt; ?></td>
                        <td class='date column-date' data-colname="Date"><?=$result->updatedAt; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($results) == 0) : ?>
                    <tr>
                        <td colspan="10"><?php echo __('No data..'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php
}

function centAppSettings()
{
    ?>
        <form id="centapp-<?=centAppController::TAB_SETTINGS; ?>" method="post" action="">

         <input type="hidden" name="centapp-action" value="<?=centAppController::TAB_SETTINGS; ?>" />
         <?php wp_nonce_field('centapp_nonce', 'centapp_nonce') ?>

         <table class="form-table" role="presentation">
             <tbody>

                 <tr>
                     <th scope="row">
                         <label for="base_url"><?=__('Base URL', 'centapp'); ?></label>
                     </th>
                     <td>
                         <input
                             name="base_url"
                             type="text"
                             id="base_url"
                             value="<?=get_option('centapp_base_url') ? esc_attr(get_option('centapp_base_url')) : ''; ?>"
                             class="regular-text"
                             style="width: 100%;"
                         />
                     </td>
                 </tr>

                 <tr>
                     <th scope="row">
                         <label for="shop_id"><?=__('Shop ID', 'centapp'); ?></label>
                     </th>
                     <td>
                         <input
                             name="shop_id"
                             type="text"
                             id="shop_id"
                             value="<?=get_option('centapp_shop_id') ? esc_attr(get_option('centapp_shop_id')) : ''; ?>"
                             class="regular-text"
                             style="width: 100%;"
                         />
                     </td>
                 </tr>

                 <tr>
                     <th scope="row">
                         <label for="token"><?=__('API Token', 'centapp'); ?></label>
                     </th>
                     <td>
                         <input
                             name="token"
                             type="text"
                             id="token"
                             value="<?=get_option('centapp_token') ? esc_attr(get_option('centapp_token')) : ''; ?>"
                             class="regular-text"
                             style="width: 100%;"
                         />
                     </td>
                 </tr>

             </tbody>
         </table>

         <p class="submit">
             <input type="submit" name="submit" id="submit" class="button button-primary" value="<?=__('Save Changes', 'centapp'); ?>">
         </p>
        </form>
    <?php
}
?>
