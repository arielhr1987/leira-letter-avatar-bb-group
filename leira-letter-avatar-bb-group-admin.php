<?php
/**
 * Add admin Social Groups settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Leira_Letter_Avatar_BB_Group_Admin
 */
class Leira_Letter_Avatar_BB_Group_Admin{

	/**
	 * Singleton instance
	 *
	 * @since    1.0.0
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * The Singleton method
	 *
	 * @return self
	 * @since  1.0.0
	 * @access public
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Leira_Letter_Avatar_BB_Group_Admin constructor.
	 */
	private function __construct() {
		add_action( 'bp_admin_setting_groups_register_fields', array( $this, 'register_settings' ) );
	}

	/**
	 * Get sanitizer class
	 */
	protected function sanitizer() {
		return leira_letter_avatar()->sanitizer;
	}

	/**
	 * Register settings
	 *
	 * @param BP_Admin_Setting_Groups $settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings( $settings ) {
		if ( current_user_can( 'administrator' ) ) {
			$settings->add_section( 'group-letter-avatar', __( 'Group Letter Avatar', 'leira-letter-avatar-bb-group' ) );

			/**
			 * Format Settings
			 */
			$settings->add_select_field(
				'leira_letter_avatar_bb_group_format',
				__( 'Format', 'leira-letter-avatar-bb-group' ),
				array(
					'input_options'     => array(
						'svg' => '.svg',
						'png' => '.png',
						'jpg' => '.jpg',
					),
					'input_description' => __( 'The format to generate the image', 'leira-letter-avatar-bb-group' ),

					'input_run_js'  => false,
					'input_default' => 'svg'
				),
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this->sanitizer(), 'format' ),
					'default'           => 'svg'
				) );

			/**
			 * Rounded Settings
			 */
			$settings->add_select_field(
				'leira_letter_avatar_bb_group_rounded',
				__( 'Shape', 'leira-letter-avatar-bb-group' ),
				array(
					'input_options' => array(
						0 => 'Square',
						1 => 'Circle',
					),
					'input_run_js'  => false,
					'input_default' => 1
				),
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this->sanitizer(), 'boolean' ),
					'default'           => 1
				) );

			/**
			 * Letter Settings
			 */
			$settings->add_select_field(
				'leira_letter_avatar_bb_group_letters',
				__( 'Letters', 'leira-letter-avatar-bb-group' ),
				array(
					'input_options'     => array(
						1 => '1 Letter',
						2 => '2 Letters',
					),
					'input_run_js'      => false,
					'input_description' => __( 'The number of letters to use to fill the image', 'leira-letter-avatar-bb-group' ),
					'input_default'     => 2,
					//'class' => 'child-no-padding-first'
				),
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this->sanitizer(), 'letters' ),
					'default'           => 2,
				) );

			$settings->add_checkbox_field(
				'leira_letter_avatar_bb_group_uppercase',
				'',
				array(
					'input_text'    => __( 'Make letters uppercase', 'leira-letter-avatar-bb-group' ),
					'input_default' => 1,
					//'class' => 'child-no-padding'
				),
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this->sanitizer(), 'boolean' ),
					'default'           => 1
				) );

			/**
			 * Color Settings
			 */
			$name          = 'leira_letter_avatar_bb_group_color_method';
			$callback      = array( $this, 'render_radio_field_html' );
			$callback_args = $this->generate_radio_input_args( $name, array(
				'input_option'  => 'auto',
				'input_default' => 'auto',
				'input_text'    => __( 'Automatically determine letters color based on background color', 'leira-letter-avatar-bb-group' ),
				//'class'      => 'child-no-padding'
			) );
			$field_args    = array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this->sanitizer(), 'color_method' ),
				'default'           => 'auto'
			);

			$settings->add_field(
				$name,
				__( 'Color', 'leira-letter-avatar-bb-group' ),
				$callback,
				$field_args,
				$callback_args );


			$callback_args = $this->generate_radio_input_args( $name, array(
				'input_option'  => 'fixed',
				'input_default' => 'auto',
				'input_text'    => __( 'Use this color for the letters', 'leira-letter-avatar-bb-group' ),
				//'class'      => 'child-no-padding-first'
			) );
			$settings->add_field(
				$name . 1,
				'',
				$callback,
				$field_args,
				$callback_args );

			/**
			 * Background Settings
			 */
			$name          = 'leira_letter_avatar_bb_group_method';
			$callback      = array( $this, 'render_radio_field_html' );
			$callback_args = $this->generate_radio_input_args( $name, array(
				'input_option'  => 'fixed',
				'input_default' => 'fixed',
				'input_text'    => __( 'Use this background color for all users', 'leira-letter-avatar-bb-group' ),
				'class'         => 'child-no-padding-first'
			) );
			$field_args    = array(
				'type'              => 'string',
				'sanitize_callback' => array( $this->sanitizer(), 'method' ),
				'default'           => 'fixed',
			);

			$settings->add_field(
				$name,
				__( 'Background', 'leira-letter-avatar-bb-group' ),
				$callback,
				$field_args,
				$callback_args );

			$args = array(
				'class' => 'child-no-padding'
			);
			$settings->add_input_field(
				'leira_letter_avatar_bb_group_bg',
				'',
				array(
					'class'       => 'child-no-padding',
					'input_value' => bp_get_option( 'leira_letter_avatar_bb_group_bg', 'fc91ad' )
				),
				array(
					'type'              => 'string',
					'description'       => '',
					'sanitize_callback' => array( $this->sanitizer(), 'background' ),
					'default'           => 'fc91ad'
				) );

			$callback_args = $this->generate_radio_input_args( $name, array(
				'input_option'  => 'random',
				'input_default' => 'fixed',
				'input_text'    => __( 'Use a random background color from the list below:', 'leira-letter-avatar-bb-group' ),
				'class'         => 'child-no-padding-first'
			) );
			$settings->add_field(
				$name . 1,
				'',
				$callback,
				$field_args,
				$callback_args );

			$settings->add_field(
				'leira_letter_avatar_bb_group_bgs',
				'',
				array( $this, 'render_backgrounds_settings' ),
				array(
					'type'              => 'string',
					'description'       => '',
					'sanitize_callback' => array( $this->sanitizer(), 'backgrounds' ),
					'default'           => ''
				),
				$args );
		}
	}

	/**
	 * Render background settings page
	 */
	public function render_background_settings() {
		$bg = get_option( 'leira_letter_avatar_bb_group_bg', 'fc91ad' );
		$bg = $this->sanitizer()->background( $bg );
		?>
        <input type="text"
               name="leira_letter_avatar_bb_group_bg"
               data-picker_default="#fc91ad"
               data-picker_palettes="#fc91ad,#37c5ab,#fd9a00,#794fcf,#19C976"
               value="<?php echo esc_attr( $bg ); ?>"
               class="leira-letter-avatar-color-field">
		<?php
	}

	/**
	 * Backgrounds settings
	 */
	public function render_backgrounds_settings() {
		$bgs = get_option( 'leira_letter_avatar_bb_group_bgs', '' );
		$bgs = $this->sanitizer()->backgrounds( $bgs );
		?>
        <textarea name="leira_letter_avatar_bb_group_bgs" rows="3" cols="50" id=""
                  class="large-text code"><?php echo esc_textarea( $bgs ) ?></textarea>

        <p class="description">
			<?php _e( 'Use comma to separate each color. Colors should be in hex format (i.e. fc91ad).', 'leira-letter-avatar-bb-group' ) ?>
        </p>
		<?php
	}

	/**
	 * Output the radio field html based on the arguments
	 *
	 * @since 1.0.0
	 */
	public function render_radio_field_html( $args ) {
		$input_value = is_null( $args['input_value'] ) ? $args['input_default'] : $args['input_value'];

		printf(
			'
				<input id="%1$s" name="%2$s" type="radio" value="%3$s" %4$s autocomplete="off"/>
				<label for="%1$s">%5$s</label>
				%6$s
			',
			$args['input_id'],
			$args['input_name'],
			$args['input_option'],
			checked( $input_value, $args['input_option'], false ),
			$args['input_text'],
			$args['input_description'] ? "<p class=\"description\">{$args['input_description']}</p>" : ''
		);
	}

	/**
	 * Generate radio input args
	 *
	 * @param $name
	 * @param $callback_args
	 *
	 * @return array|object
	 */
	public function generate_radio_input_args( $name, $callback_args ) {
		$callback_args = wp_parse_args(
			$callback_args,
			array(
				'input_name'        => $name,
				'input_id'          => sanitize_text_field( $name ) . '_' . $callback_args['input_option'],
				'input_text'        => '',
				'input_description' => '',
				'input_value'       => bp_get_option( $name, null ),
				'input_option'      => '',
				'input_default'     => 0,
				'input_run_js'      => false,
			)
		);

		return $callback_args;
	}
}