<?php

/**
 * Represents the view for the administration dashboard.
 *
 * @package    PT
 * @subpackage Views
 * @author     David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'admin-helper-functions.php' );

$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'keys';
$active_subtab = isset( $_GET['subtab'] ) ? $_GET['subtab'] : '';
?>

<div class="wrap">
	<?php settings_errors(); ?>
	<div id="pt-settings">
		<div id="pt-settings-content">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php

				$pt_tabs = pt_get_admin_tabs();
                $pt_marketing_subtabs = pt_get_marketing_subtabs();

				foreach ( $pt_tabs as $key => $value ) {
                    if ($key == 'marketing') {
                        if (isset($pt_marketing_subtabs['activecampaign'])) {
                            $start_tab = 'activecampaign';
                        }
                        elseif (isset($pt_marketing_subtabs['mailchimp'])) {
                            $start_tab = 'mailchimp';
                        }
                        else {
                            $start_tab = 'mailpoet';
                        } ?>

                        <a href="<?php echo esc_url( add_query_arg( array('tab'=> $key, 'subtab' => $start_tab), remove_query_arg( 'settings-updated' ) ) ); ?>"
                           class="nav-tab marketing-tab
							<?php echo $active_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $value; ?></a>
                        <?php
                    }
                    else { ?>
                        <a href="<?php echo esc_url( add_query_arg( 'tab', $key, remove_query_arg( array('settings-updated', 'subtab') ) ) ); ?>"
                           class="nav-tab
							<?php echo $active_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $value; ?></a>
                        <?php
                    }
				}
				?>
			</nav>

			<div id="tab_container">
				<form method="post" action="options.php">
					<?php
					$pt_tabs = pt_get_admin_tabs();
                    $pt_marketing_subtabs = pt_get_marketing_subtabs();

					foreach ( $pt_tabs as $key => $value ) {
						if ( $active_tab == $key ) {

                            if ($key == 'marketing') {

                                echo '<div class="marketing-options">';
                                $i = 1;
                                foreach ($pt_marketing_subtabs as $mt_key => $mt_value) {
                                    ?>
                                    <a href="<?php echo esc_url(add_query_arg('subtab', $mt_key, remove_query_arg( 'settings-updated' ))); ?>"
                                       class="<?php echo $active_subtab == $mt_key ? 'marketing-opt-active' : ''; ?>"><?php echo $mt_value; ?></a>
                                    <?php

                                    if ($i < count($pt_marketing_subtabs)) echo  ' | ';
                                    $i++;
                                }
                                echo '</div>';
                                foreach ($pt_marketing_subtabs as $mt_key => $mt_value) {

                                    if ($active_subtab == $mt_key) {
                                        settings_fields( 'pt_settings_' . $mt_key );
                                        do_settings_sections( 'pt_settings_' . $mt_key );

                                        do_action( 'pt_settings_' . $mt_key );
                                        submit_button();
                                    }

                                }
                            }
                            else {
                                settings_fields( 'pt_settings_' . $key );
                                do_settings_sections( 'pt_settings_' . $key );

                                do_action( 'pt_settings_' . $key );
                                submit_button();
                            }
						}
					}
					?>
				</form>
			</div>
			<!-- #tab_container-->
		</div>
		<!-- #pt-settings-content -->

		<div id="pt-settings-sidebar">
			<?php include( 'admin-sidebar.php' ); ?>
		</div>

	</div>
</div><!-- .wrap -->
