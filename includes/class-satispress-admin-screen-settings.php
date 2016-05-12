<?php
/**
 * Settings screen.
 *
 * @package SatisPress
 * @author Brady Vercher <brady@blazersix.com>
 * @since 0.2.0
 */
class SatisPress_Admin_Screen_Settings {
	/**
	 * Base patch for packages.
	 *
	 * @since 0.2.0
	 * @type string
	 */
	public $base_path = '';

	/**
	 * Load the screen.
	 *
	 * @since 0.2.0
	 */
	public function load() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'add_sections' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_notices', array( $this, 'htaccess_notice' ) );
	}

	/**
	 * Set the base path for packages.
	 *
	 * @since 0.2.0
	 *
	 * @param string $path Base path for packages.
	 */
	public function set_base_path( $path ) {
		$this->base_path = $path;
	}

	/**
	 * Add the settings menu item.
	 *
	 * @since 0.2.0
	 */
	public function add_menu_item() {
		$screen_hook = add_options_page(
			__( 'SatisPress', 'satispress' ),
			__( 'SatisPress', 'satispress' ),
			'manage_options',
			'satispress',
			array( $this, 'render_screen' )
		);

		add_action( 'load-' . $screen_hook, array( $this, 'setup_screen' ) );
	}

	/**
	 * Set up the screen.
	 *
	 * @since 0.2.0
	 * @todo Add help tabs.
	 */
	public function setup_screen() {
		$screen = get_current_screen();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 0.2.0
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'satispress-admin' );
		wp_enqueue_style( 'satispress-admin' );
	}

	/**
	 * Register settings.
	 *
	 * @since 0.2.0
	 */
	public function register_settings() {
		register_setting( 'satispress', 'satispress', array( $this, 'sanitize_settings' ) );
		register_setting( 'satispress', 'satispress_themes', array( $this, 'sanitize_theme_settings' ) );
	}

	/**
	 * Add settings sections.
	 *
	 * @since 0.2.0
	 */
	public function add_sections() {
		add_settings_section(
			'default',
			__( 'General', 'satispress' ),
			'__return_null',
			'satispress'
		);

		add_settings_section(
			'security',
			__( 'Security', 'satispress' ),
			array( $this, 'render_section_security_description' ),
			'satispress'
		);

		add_settings_section(
			'themes',
			__( 'Themes', 'satispress' ),
			array( $this, 'render_section_themes_description' ),
			'satispress'
		);
	}

	/**
	 * Register individual settings.
	 *
	 * @since 0.2.0
	 */
	public function add_settings() {
		add_settings_field(
			'vendor',
			__( 'Vendor', 'satispress' ),
			array( $this, 'render_field_vendor' ),
			'satispress',
			'default'
		);

		add_settings_field(
			'enable_basic_authentication',
			__( 'Authentication', 'satispress' ),
			array( $this, 'render_field_basic_authentication' ),
			'satispress',
			'security'
		);

		add_settings_field(
			'themes',
			__( 'Themes', 'satispress' ),
			array( $this, 'render_field_themes' ),
			'satispress',
			'themes'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 0.2.0
	 */
	public function sanitize_settings( $value ) {
		if ( ! empty( $value['vendor'] ) ) {
			$value['vendor'] = sanitize_text_field( $value['vendor'] );
		}

		if ( ! isset( $value['enable_basic_authentication' ] ) ) {
			$value['enable_basic_authentication'] = 'no';
		} else {
			$value['enable_basic_authentication'] = 'yes';
		}

		return $value;
	}

	/**
	 * Sanitize list of themes.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $value Setting value.
	 * @return array
	 */
	public function sanitize_theme_settings( $value ) {
		return array_filter( array_unique( (array) $value ) );
	}

	/**
	 * Display the screen.
	 *
	 * @since 0.2.0
	 */
	public function render_screen() {
		$permalink = satispress_get_packages_permalink();
		$packages = SatisPress::instance()->get_packages();
		include( SATISPRESS_DIR . 'views/screen-settings.php' );
	}

	/**
	 * Display the security section description.
	 *
	 * @since 0.2.0
	 */
	public function render_section_security_description() {
		_e( 'Your packages are public by default. At a minimum, you can secure them using HTTP Basic Authentication.', 'satispress' );
	}

	/**
	 * Display the themes section description.
	 *
	 * @since 0.2.0
	 */
	public function render_section_themes_description() {
		_e( 'Choose themes to make available in your SatisPress repository.', 'satispress' );
	}

	/**
	 * Display a field for defining the vendor.
	 *
	 * @since 0.2.0
	 */
	public function render_field_vendor() {
		$value = $this->get_setting( 'vendor', '' );
		?>
		<p>
			<input type="text" name="satispress[vendor]" id="satispress-vendor" value="<?php echo esc_attr( $value ); ?>"><br>
			<span class="description">Default is <code>satispress</code></span>
		</p>
		<?php
	}

	/**
	 * Display the basic authentication settings field.
	 *
	 * @since 0.2.0
	 */
	public function render_field_basic_authentication() {
		$value = $this->get_setting( 'enable_basic_authentication', 'no' );
		?>
		<p class="satispress-togglable-field">
			<label>
				<input type="checkbox" name="satispress[enable_basic_authentication]" id="satispress-enable-basic-authentication" value="yes" <?php checked( $value, 'yes' ); ?>>
				<?php _e( 'Enable HTTP Basic Authentication?', 'satispress' ); ?>
			</label>
		</p>
		<?php
		$htaccess = new SatisPress_Htaccess( $this->base_path );
		if ( ! $htaccess->is_writable() ) {
			printf( '<p class="satispress-field-error">%s</p>', __( ".htaccess file isn't writable." ) );
		}
	}

	/**
	 * Display the themes list field.
	 *
	 * @since 0.2.0
	 */
	public function render_field_themes() {
		$value = get_option( 'satispress_themes', array() );

		$themes = wp_get_themes();
		foreach ( $themes as $slug => $theme ) {
			printf( '<label><input type="checkbox" name="satispress_themes[]" value="%1$s" %2$s> %3$s</label><br>',
				esc_attr( $slug ),
				checked( in_array( $slug, $value ), true, false ),
				$theme->get( 'Name' )
			);
		}
	}

	/**
	 * Display a notice if Basic Authentication is enabled and .htaccess doesn't exist.
	 *
	 * @since 0.2.0
	 */
	public function htaccess_notice() {
		$value = $this->get_setting( 'enable_basic_authentication', 'no' );
		$htaccess_file = $this->base_path . '.htaccess';

		if ( 'yes' === $value && ! file_exists( $htaccess_file ) ) {
			?>
			<div class="error">
				<p>
					<?php _e( "Warning: .htaccess doesn't exist. Your SatisPress packages are public.", 'satispress' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Retrieve a setting.
	 *
	 * @since 0.2.0
	 *
	 * @param string $key Setting name.
	 * @param mixed $default Optional. Default setting value.
	 * @return mixed
	 */
	protected function get_setting( $key, $default = false ) {
		$option = get_option( 'satispress' );
		return isset( $option[ $key ] ) ? $option[ $key ] : false;
	}

	/**
	 * Retrieve the contents of a view.
	 *
	 * @since 0.2.0
	 *
	 * @param string $file View filename.
	 * @return string
	 */
	protected function get_view( $file ) {
		ob_start();
		include( SATISPRESS_DIR . 'views/' . $file );
		return ob_get_clean();
	}
}
