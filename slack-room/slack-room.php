<?php
/**
 * Plugin Name: Slack Room
 * Plugin URI: 
 * Description: Show Slack Room Content inside wp page.
 * Version: 1.0
 * Author: Osvaldo Galvez
 */

/**
 * 
 * Require our custom widget file
 * 
 */

require "widget/SlackRoom.php";

/**
 * This function is to init our plugin options settings
*/
function slackr_settings_init() {
   // Register a new setting for "slackr" page.
	register_setting( 'slackr', 'slackr_options' );

   // Register a new section in the "slackr" page.
	add_settings_section(
		'slackr_section',
		__( 'Settings', 'slackr' ), 'slackr_section_callback',
		'slackr'
	);

   // Register the timezone field in the "slackr_section" section, inside the "slackr" page.
	add_settings_field(
	'slackr_field_timezone', 
		__( 'Timezone', 'slackr' ),
		'slackr_fields_cb',
		'slackr',
		'slackr_section',
		array(
			'label_for'         => 'slackr_field_timezone',
			'id' 				=> 'slackr_field_timezone', 
			'class'             => 'slackr_row',
			'slackr_custom_data' => 'custom',
		)
	);

   // Register the slackApiToken field in the "slackr_section" section, inside the "slackr" page.
    add_settings_field(
		'slackr_field_slack_api_token',
			__( 'Slack API Token', 'slackr' ),
		'slackr_fields_cb',
		'slackr',
		'slackr_section',
		array(
			'label_for'         => 'slackr_field_slack_api_token',
			'id' 				=> 'slackr_field_slack_api_token',
			'class'             => 'slackr_row',
			'slackr_custom_data' => 'custom',
		)
	);


}

/**
* Register our slackr_settings_init to the admin_init action hook.
*/
add_action( 'admin_init', 'slackr_settings_init' );


/**
* Section callback function.
*
* @param array $args  The settings array, defining title, id, callback.
*
*/
function slackr_section_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please add your Slack API Token and the Timezone', 'slackr' ); ?></p>
<?php
}

/**
* 
* @param array $args
*/
function slackr_fields_cb( $args ) {
   // Get the value of the setting we've registered with register_setting()
	$options = get_option( 'slackr_options' );
	?>
	<input onkeypress="javascript:getChannels();" type="text" id="<?php echo esc_attr( $args['id'] ); ?>" name="slackr_options[<?php echo esc_attr( $args['id'] ); ?>]" value= "<?php echo isset( $options[ $args['id'] ]) ? $options[ $args['id'] ] : "" ?>"/>
	<?php
}

/**
* Add the top level menu page.
*/
function slackr_options_page() {
	add_menu_page(
		'Slack Room',
		'Slack Room Settings',
		'manage_options',
		'slackr',
		'slackr_options_page_html'
	);
}


/**
* Register our slackr_options_page to the admin_menu action hook.
*/
add_action( 'admin_menu', 'slackr_options_page' );


/**
* Menu callback function
*/
function slackr_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'slackr_messages', 'slackr_message', __( 'Settings Saved', 'slackr' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'slackr_messages' );
	?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		<?php
		// output security fields for the registered setting "slackr"
		settings_fields( 'slackr' );
		// output setting sections and their fields
		// (sections are registered for "slackr", each field is registered to a specific section)
		do_settings_sections( 'slackr' );
		// output save settings button
		submit_button( 'Save Settings' );
		?>
	</form>
	</div>
	<?php
}

/**
 * Function to register our custom slack romm widget
 */
function slack_room_register_widget() {
	register_widget( 'slack_room_widget' );
}
add_action( 'widgets_init', 'slack_room_register_widget' );
