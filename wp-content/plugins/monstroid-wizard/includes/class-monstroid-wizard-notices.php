<?php
/**
 * Add admin notices, warnings and checks
 *
 * @package   monstroid_wizard
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !class_exists( 'monstroid_wizard_notices' ) ) {

	/**
	 * Add admin notice
	 *
	 * @since 1.0.0
	 */
	class monstroid_wizard_notices {

		/**
		 * Cherry wizard dismissed notice meta fiels name
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $dismissed_notice = 'monstroid_wizard_dismissed_notice';

		function __construct() {

			add_action( 'admin_head', array( $this, 'dismiss' ) );
			add_action( 'admin_notices', array( $this, 'show_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
			add_action( 'switch_theme', array( $this, 'update_dismiss' ) );
			add_action( 'monstroid_wizard_install_notices', array( $this, 'show_install_notice' ) );

			add_action( 'init', array( $this, 'check_permissions' ) );
			add_action( 'init', array( $this, 'check_server_config' ), 15 );
		}

		/**
		 * Check if current user alredy dissmissed current notice
		 *
		 * @since 1.0.0
		 */
		public function notice_dismissed() {
			return get_user_meta( get_current_user_id(), $this->dismissed_notice, true );
		}

		/**
		 * Enqueues thickbox scripts/styles for plugin info.
		 *
		 * Thickbox is not automatically included on all admin pages, so we must
		 * manually enqueue it for those pages.
		 *
		 * Thickbox is only loaded if the user has not dismissed the admin
		 * notice
		 *
		 * @since 1.0.0
		 */
		public function thickbox() {

			if ( $this->notice_dismissed() ) {
				return;
			}
			add_thickbox();
		}

		/**
		 * dismiss installation notice
		 *
		 * @since 1.0.0
		 */
		public function dismiss() {
			if ( isset( $_GET[sanitize_key( 'monstroid_wizard_dismiss' )] ) ) {
				update_user_meta( get_current_user_id(), $this->dismissed_notice, 1 );
			}
		}

		/**
		 * delete dismiss user meta on theme switch
		 *
		 * @since 1.0.0
		 */
		public function update_dismiss() {

			delete_user_meta( get_current_user_id(), $this->dismissed_notice );

		}

		/**
		 * Show installation start notice
		 *
		 * @since 1.0.0
		 */
		public function show_notice() {

			// check if installation needed before do anything
			$need_install = get_option( 'monstroid_wizard_need_install' );

			if ( ! $need_install ) {
				return;
			}

			global $current_screen, $monstroid_wizard;

			// break function if already dismissed
			if ( $this->notice_dismissed() ) {
				return;
			}

			// show nothing on wizard page
			if ( $monstroid_wizard->is_wizard_page() ) {
				return;
			}
			?>
			<div class="<?php echo $monstroid_wizard->ui_wrapper_class( array( 'updated' ) ); ?>">
				<div class="wizard-admin-notice_">
					<div class="wizard-admin-notice-content_">
						<strong>
							<?php _e( 'Wizard will help you to install your Monstroid theme.', $monstroid_wizard->slug ); ?>
						</strong>
						<div class="wizar-notice-actions_">
							<a class="button-primary_" href="<?php echo menu_page_url( $monstroid_wizard->slug, false ); ?>">
								<span class="dashicons dashicons-download"></span>
								<?php _e( "Install theme", $monstroid_wizard->slug ); ?>
							</a>
							<a class="dismiss-notice_" href="<?php echo add_query_arg( 'monstroid_wizard_dismiss', 'dismiss_admin_notices' ); ?>" target="_parent">
								<span class="dashicons dashicons-dismiss"></span>
								<?php _e( "Dismiss", $monstroid_wizard->slug ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php

		}

		/**
		 * check require directory permissions
		 *
		 * @since 1.0.0
		 */
		public function check_permissions() {

			global $monstroid_wizard;

			// check settings only on wizard related pages (no need to do this at other admin pages)
			if ( ! $monstroid_wizard->is_wizard_page() ) {
				return;
			}

			$plugins_dir = WP_PLUGIN_DIR;
			$themes_dir  = get_theme_root();
			$uploads_dir = wp_upload_dir();
			$uploads_dir = $uploads_dir['basedir'];

			$check_perms = 'ok';
			$message = array();

			if ( !is_writable($plugins_dir) ) {
				$check_perms = 'error';
				$message['plugins_dir'] = array(
					'type' => 'error',
					'text' => __( 'Plugins directory not writable', $monstroid_wizard->slug )
				);
			}

			if ( !is_writable($themes_dir) ) {
				$check_perms = 'error';
				$message['themes_dir'] = array(
					'type' => 'error',
					'text' => __( 'Themes directory not writable', $monstroid_wizard->slug )
				);
			}

			if ( !is_writable($uploads_dir) ) {
				$check_perms = 'error';
				$message['uploads_dir'] = array(
					'type' => 'error',
					'text' => __( 'Uploads directory not writable', $monstroid_wizard->slug )
				);
			}

			$_SESSION['monstroid_wizard_messages'] = $message;

			$monstroid_wizard->dir_permissions = $check_perms;

		}

		/**
		 * check require server configuration
		 *
		 * @since 1.0.0
		 */
		public function check_server_config() {

			global $monstroid_wizard;

			// check settings only on wizard related pages (no need to do this at other admin pages)
			if ( !$monstroid_wizard->is_wizard_page() ) {
				return;
			}

			$server_settings = 'ok';

			$messages = isset( $_SESSION['monstroid_wizard_messages'] ) ? $_SESSION['monstroid_wizard_messages'] : array();

			if ( !$messages ) {
				$messages = array();
			}

			$must_settings = array(
				'safe_mode'           => 'off',
				'file_uploads'        => 'on',
				'memory_limit'        => 128,
				'post_max_size'       => 8,
				'upload_max_filesize' => 8,
				'max_input_time'      => 45,
				'max_execution_time'  => 30
			);

			$units = array(
				'safe_mode'           => '',
				'file_uploads'        => '',
				'memory_limit'        => 'Mb',
				'post_max_size'       => 'Mb',
				'upload_max_filesize' => 'Mb',
				'max_input_time'      => 's',
				'max_execution_time'  => 's'
			);

			// curret server settings
			$current_settings = array();

			//result array
			$result = array();

			$current_settings['safe_mode'] = 'off';
			if ( ini_get('safe_mode') ) {
				$current_settings['safe_mode'] = 'on';
			}

			$current_settings['file_uploads'] = 'off';
			if ( ini_get('file_uploads') ) {
				$current_settings['file_uploads'] = 'on';
			}

			$current_settings['memory_limit']        = (int)ini_get('memory_limit');
			$current_settings['post_max_size']       = (int)ini_get('post_max_size');
			$current_settings['upload_max_filesize'] = (int)ini_get('upload_max_filesize');
			$current_settings['max_input_time']      = (int)ini_get('max_input_time');
			$current_settings['max_execution_time']  = (int)ini_get('max_execution_time');

			$diff = array_diff_assoc($must_settings, $current_settings);

			if ( strcmp($must_settings['safe_mode'], $current_settings['safe_mode']) ) {
				$result['safe_mode'] = $must_settings['safe_mode'];
				$messages['safe_mode'] = array(
					'type' => 'warning',
					'text' => 'Safe mode - ' . $result['safe_mode'] . '. Current - ' . $current_settings['safe_mode']
				);
				$server_settings = 'warning';
			}

			if ( strcmp($must_settings['file_uploads'], $current_settings['file_uploads']) ) {
				$result['file_uploads'] = $must_settings['file_uploads'];
				$messages['file_uploads'] = array(
					'type' => 'error',
					'text' => 'File uploads - ' . $result['file_uploads'] . 'Current - ' . $current_settings['file_uploads']
				);
				$server_settings = 'error';
			}

			foreach ( $diff as $key => $value ) {
				if ( $current_settings[$key] < $value ) {
					$result[$key] = $value;
					$messages[$key] = array(
						'type' => 'warning',
						'text' => $key . ' - ' . $value . $units[$key] . '. Current - ' . $current_settings[$key]. $units[$key]
					);
					$server_settings = 'error' != $server_settings ? 'warning' : 'error';
				}
			}

			$_SESSION['monstroid_wizard_messages'] = $messages;

			$monstroid_wizard->server_settings = $server_settings;

		}

		/**
		 * Show installation notices
		 *
		 * @since 1.0.0
		 */
		public function show_install_notice() {

			global $monstroid_wizard;

			if ( 'ok' == $monstroid_wizard->dir_permissions && 'ok' == $monstroid_wizard->server_settings ) {
				return;
			}

			$messages = isset( $_SESSION['monstroid_wizard_messages'] ) ? $_SESSION['monstroid_wizard_messages'] : array();

			if ( empty($messages) ) {
				return;
			}

			echo '<div class="wizard-install-notices">';

			echo '<p>';
			echo __( 'Your hosting server configuration doesn\'t meet Cherry Framework requirements.', $monstroid_wizard->slug );
			echo sprintf( ' <a href="http://www.cherryframework.com/documentation/cf4/index.php?lang=en&section=introduction" class="details-link">%s</a>', __( 'More Details', $monstroid_wizard->slug ) );
			echo '</p>';

			foreach ( $messages as $message ) {
				echo '<div class="wizard-notice-item ' . $message['type'] . '">' . $message['text'] . '</div>';
			}

			if ( 'warning' == $monstroid_wizard->server_settings && 'error' != $monstroid_wizard->dir_permissions ) {
				echo '<div class="submit-wrap_"><a href="#" class="wizard-run-install button-primary_">' . __( 'Continue anyway', $monstroid_wizard->slug ) . '</a></div>';
			}

			echo '</div>';

			if ( 'error' == $monstroid_wizard->dir_permissions || 'error' == $monstroid_wizard->server_settings ) {
				echo '<div class="wizard-install-todo">';
					echo '<p>' . __( 'Change required settings and try again', $monstroid_wizard->slug ) . '</p>';
					echo '<a href="' .  add_query_arg( array( 'step' => 1 ), menu_page_url( $monstroid_wizard->slug, false ) ) . '">' . __( 'Try again', $monstroid_wizard->slug ) . '</a>';
				echo '</div>';
				return;
			}

		}

	}

	new monstroid_wizard_notices();

}