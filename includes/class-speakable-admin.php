<?php
/**
 * Admin settings page for Speakable.
 *
 * @package Speakable
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPEAKABLE_Admin
 *
 * Handles the plugin settings page under Settings > Speakable.
 *
 * @since 1.0.0
 */
class SPEAKABLE_Admin {

	/**
	 * Stores the actual hook suffixes returned by add_menu_page / add_submenu_page
	 * so enqueue_admin_assets can compare against the real values WordPress generates.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $page_hooks = array();

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hooks['settings'] = add_menu_page(
			__( 'Speakable', 'speakable' ),
			__( 'Speakable', 'speakable' ),
			'manage_options',
			'speakable',
			array( $this, 'render_settings_page' ),
			'dashicons-controls-volumeon',
			80
		);

		// First submenu mirrors the parent slug — hook is the same as the top-level page.
		add_submenu_page(
			'speakable',
			__( 'Settings', 'speakable' ),
			__( 'Settings', 'speakable' ),
			'manage_options',
			'speakable',
			array( $this, 'render_settings_page' )
		);

		$this->page_hooks['analytics'] = add_submenu_page(
			'speakable',
			__( 'Analytics', 'speakable' ),
			__( 'Analytics', 'speakable' ),
			'manage_options',
			'speakable-analytics',
			array( $this, 'render_analytics_page' )
		);

		$this->page_hooks['help'] = add_submenu_page(
			'speakable',
			__( 'Help', 'speakable' ),
			__( 'Help', 'speakable' ),
			'manage_options',
			'speakable-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Register settings for sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'speakable_settings_group',
			SPEAKABLE_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize all settings before saving.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_key', $input['enabled_post_types'] );
		}

		$sanitized['voice_name'] = isset( $input['voice_name'] ) ? sanitize_text_field( $input['voice_name'] ) : '';

		$sanitized['speech_rate'] = isset( $input['speech_rate'] ) ? (float) $input['speech_rate'] : 1.0;
		$sanitized['speech_rate'] = max( 0.5, min( 2.0, $sanitized['speech_rate'] ) );

		$sanitized['pitch'] = isset( $input['pitch'] ) ? (float) $input['pitch'] : 1.0;
		$sanitized['pitch'] = max( 0.0, min( 2.0, $sanitized['pitch'] ) );

		$sanitized['volume'] = isset( $input['volume'] ) ? (float) $input['volume'] : 1.0;
		$sanitized['volume'] = max( 0.0, min( 1.0, $sanitized['volume'] ) );

		$color = isset( $input['button_color'] ) ? sanitize_hex_color( $input['button_color'] ) : '';
		$sanitized['button_color'] = $color ? $color : '#d60017';

		$valid_positions              = array( 'before', 'after' );
		$position                     = isset( $input['button_position'] ) ? $input['button_position'] : 'before';
		$sanitized['button_position'] = in_array( $position, $valid_positions, true ) ? $position : 'before';

		$sanitized['show_progress_bar']  = ! empty( $input['show_progress_bar'] );
		$sanitized['show_speed_control'] = ! empty( $input['show_speed_control'] );
		$sanitized['sticky_player']      = ! empty( $input['sticky_player'] );

		return $sanitized;
	}

	/**
	 * Enqueue admin CSS and JS on the plugin settings page only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'speakable-admin',
			SPEAKABLE_PLUGIN_URL . 'assets/css/speakable-admin.css',
			array(),
			SPEAKABLE_VERSION
		);

		// Settings page needs color picker and admin JS.
		if ( isset( $this->page_hooks['settings'] ) && $this->page_hooks['settings'] === $hook ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'speakable-admin',
				SPEAKABLE_PLUGIN_URL . 'assets/js/speakable-admin.js',
				array( 'jquery', 'wp-color-picker' ),
				SPEAKABLE_VERSION,
				true
			);
			wp_localize_script( 'speakable-admin', 'speakableAdmin', array(
				'i18n' => array(
					'stop'        => __( 'Stop', 'speakable' ),
					'playPreview' => __( 'Play Preview', 'speakable' ),
					'playing'     => __( 'Playing...', 'speakable' ),
				),
			) );
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options       = get_option( SPEAKABLE_OPTION_KEY, array() );
		$voice         = isset( $options['voice_name'] ) ? $options['voice_name'] : '';
		$rate          = isset( $options['speech_rate'] ) ? (float) $options['speech_rate'] : 1.0;
		$pitch         = isset( $options['pitch'] ) ? (float) $options['pitch'] : 1.0;
		$volume        = isset( $options['volume'] ) ? (float) $options['volume'] : 1.0;
		$color         = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';
		$position      = isset( $options['button_position'] ) ? $options['button_position'] : 'before';
		$progress_bar  = ! empty( $options['show_progress_bar'] );
		$speed_control = ! empty( $options['show_speed_control'] );
		$sticky_player  = ! empty( $options['sticky_player'] );
		$enabled_types  = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$post_types    = get_post_types( array( 'public' => true ), 'objects' );
		$opt_key       = SPEAKABLE_OPTION_KEY;
		?>
		<div class="speakable-admin-wrap">

			<!-- Header -->
			<div class="speakable-header">
				<div class="speakable-header-inner">
					<div class="speakable-header-left">
						<div class="speakable-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
						</div>
						<div>
							<h1 class="speakable-header-title"><?php esc_html_e( 'Speakable', 'speakable' ); ?></h1>
							<p class="speakable-header-version"><?php echo esc_html( 'v' . SPEAKABLE_VERSION ); ?></p>
						</div>
					</div>
					<div class="speakable-header-right">
						<span class="speakable-status-badge speakable-status-active">
							<span class="speakable-status-dot"></span>
							<?php esc_html_e( 'Active', 'speakable' ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="speakable-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'speakable' ); ?>">
				<button type="button" class="speakable-tab speakable-tab-active" data-tab="voice" role="tab" aria-selected="true" aria-controls="speakable-panel-voice" id="speakable-tab-voice">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
					<?php esc_html_e( 'Voice', 'speakable' ); ?>
				</button>
				<button type="button" class="speakable-tab" data-tab="display" role="tab" aria-selected="false" aria-controls="speakable-panel-display" id="speakable-tab-display" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
					<?php esc_html_e( 'Display', 'speakable' ); ?>
				</button>
				<button type="button" class="speakable-tab" data-tab="preview" role="tab" aria-selected="false" aria-controls="speakable-panel-preview" id="speakable-tab-preview" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Preview', 'speakable' ); ?>
				</button>
			</div>

			<!-- Form wraps all tab panels -->
			<form action="options.php" method="post" class="speakable-form">
				<?php settings_fields( 'speakable_settings_group' ); ?>

				<!-- Voice Tab -->
				<div class="speakable-tab-panel speakable-tab-panel-active" data-panel="voice" role="tabpanel" id="speakable-panel-voice" aria-labelledby="speakable-tab-voice">
					<div class="speakable-card">
						<div class="speakable-card-header">
							<h2 class="speakable-card-title"><?php esc_html_e( 'Voice Configuration', 'speakable' ); ?></h2>
							<p class="speakable-card-desc"><?php esc_html_e( 'Select a voice and adjust playback parameters. Voices come from your browser/OS.', 'speakable' ); ?></p>
						</div>
						<div class="speakable-card-body">

							<!-- Voice Select -->
							<div class="speakable-field">
								<label class="speakable-field-label" for="speakable-voice-name"><?php esc_html_e( 'Voice', 'speakable' ); ?></label>
								<div class="speakable-field-control">
									<select id="speakable-voice-name" name="<?php echo esc_attr( $opt_key ); ?>[voice_name]" class="speakable-select">
										<option value=""><?php esc_html_e( 'Browser Default', 'speakable' ); ?></option>
									</select>
									<input type="hidden" id="speakable-voice-saved" value="<?php echo esc_attr( $voice ); ?>" />
								</div>
								<p class="speakable-field-hint"><?php esc_html_e( 'Available voices vary by browser and OS. Visitors hear their own device voices.', 'speakable' ); ?></p>
							</div>

							<!-- Speed Slider -->
							<div class="speakable-field">
								<label class="speakable-field-label" for="speakable-speech-rate"><?php esc_html_e( 'Speed', 'speakable' ); ?></label>
								<div class="speakable-slider-row">
									<span class="speakable-slider-min">0.5x</span>
									<input type="range" id="speakable-speech-rate" name="<?php echo esc_attr( $opt_key ); ?>[speech_rate]"
										class="speakable-range" min="0.5" max="2" step="0.1" value="<?php echo esc_attr( $rate ); ?>" />
									<span class="speakable-slider-max">2x</span>
									<span class="speakable-slider-value" id="speakable-rate-val"><?php echo esc_html( $rate ); ?>x</span>
								</div>
							</div>

							<!-- Pitch Slider -->
							<div class="speakable-field">
								<label class="speakable-field-label" for="speakable-pitch"><?php esc_html_e( 'Pitch', 'speakable' ); ?></label>
								<div class="speakable-slider-row">
									<span class="speakable-slider-min">0</span>
									<input type="range" id="speakable-pitch" name="<?php echo esc_attr( $opt_key ); ?>[pitch]"
										class="speakable-range" min="0" max="2" step="0.1" value="<?php echo esc_attr( $pitch ); ?>" />
									<span class="speakable-slider-max">2</span>
									<span class="speakable-slider-value" id="speakable-pitch-val"><?php echo esc_html( $pitch ); ?></span>
								</div>
							</div>

							<!-- Volume Slider -->
							<div class="speakable-field">
								<label class="speakable-field-label" for="speakable-volume"><?php esc_html_e( 'Volume', 'speakable' ); ?></label>
								<div class="speakable-slider-row">
									<span class="speakable-slider-min">0</span>
									<input type="range" id="speakable-volume" name="<?php echo esc_attr( $opt_key ); ?>[volume]"
										class="speakable-range" min="0" max="1" step="0.1" value="<?php echo esc_attr( $volume ); ?>" />
									<span class="speakable-slider-max">1</span>
									<span class="speakable-slider-value" id="speakable-volume-val"><?php echo esc_html( $volume ); ?></span>
								</div>
							</div>

						</div>
					</div>

					<div class="speakable-save-row">
						<?php submit_button( __( 'Save Settings', 'speakable' ), 'primary speakable-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Display Tab -->
				<div class="speakable-tab-panel" data-panel="display" role="tabpanel" id="speakable-panel-display" aria-labelledby="speakable-tab-display">
					<div class="speakable-card">
						<div class="speakable-card-header">
							<h2 class="speakable-card-title"><?php esc_html_e( 'Player Appearance', 'speakable' ); ?></h2>
							<p class="speakable-card-desc"><?php esc_html_e( 'Control where and how the player appears on your site.', 'speakable' ); ?></p>
						</div>
						<div class="speakable-card-body">

							<!-- Post Types -->
							<div class="speakable-field">
								<label class="speakable-field-label"><?php esc_html_e( 'Enable on Post Types', 'speakable' ); ?></label>
								<div class="speakable-post-types-grid">
									<?php foreach ( $post_types as $pt ) : ?>
										<?php if ( 'attachment' === $pt->name ) continue; ?>
										<label class="speakable-chip">
											<input type="checkbox" name="<?php echo esc_attr( $opt_key ); ?>[enabled_post_types][]"
												value="<?php echo esc_attr( $pt->name ); ?>"
												<?php checked( in_array( $pt->name, $enabled_types, true ) ); ?> />
											<span class="speakable-chip-inner">
												<svg class="speakable-chip-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
												<span class="speakable-chip-label"><?php echo esc_html( $pt->labels->singular_name ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Button Color -->
							<div class="speakable-field">
								<label class="speakable-field-label" for="speakable-button-color"><?php esc_html_e( 'Button Color', 'speakable' ); ?></label>
								<div class="speakable-field-control">
									<input type="text" id="speakable-button-color" name="<?php echo esc_attr( $opt_key ); ?>[button_color]"
										value="<?php echo esc_attr( $color ); ?>" class="speakable-color-field" data-default-color="#d60017" />
								</div>
							</div>

							<!-- Position -->
							<div class="speakable-field">
								<label class="speakable-field-label"><?php esc_html_e( 'Button Position', 'speakable' ); ?></label>
								<div class="speakable-position-cards">
									<label class="speakable-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="before" <?php checked( $position, 'before' ); ?> />
										<span class="speakable-position-card-inner">
											<span class="speakable-position-preview">
												<span class="speakable-position-player-bar"></span>
												<span class="speakable-position-line speakable-position-line-full"></span>
												<span class="speakable-position-line speakable-position-line-full"></span>
												<span class="speakable-position-line speakable-position-line-short"></span>
											</span>
											<span class="speakable-position-label"><?php esc_html_e( 'Before Content', 'speakable' ); ?></span>
										</span>
									</label>
									<label class="speakable-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="after" <?php checked( $position, 'after' ); ?> />
										<span class="speakable-position-card-inner">
											<span class="speakable-position-preview">
												<span class="speakable-position-line speakable-position-line-full"></span>
												<span class="speakable-position-line speakable-position-line-full"></span>
												<span class="speakable-position-line speakable-position-line-short"></span>
												<span class="speakable-position-player-bar"></span>
											</span>
											<span class="speakable-position-label"><?php esc_html_e( 'After Content', 'speakable' ); ?></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Toggles -->
							<div class="speakable-field">
								<h2><?php esc_html_e( 'Player Features', 'speakable' ); ?></h2>
								<div class="speakable-toggles-list">
									<label class="speakable-toggle-row">
										<span class="speakable-toggle-info">
											<span class="speakable-toggle-name"><?php esc_html_e( 'Progress Bar', 'speakable' ); ?></span>
											<span class="speakable-toggle-desc"><?php esc_html_e( 'Visual indicator showing reading progress below the controls', 'speakable' ); ?></span>
										</span>
										<span class="speakable-toggle-switch-wrap">
											<input type="checkbox" id="speakable-progress" name="<?php echo esc_attr( $opt_key ); ?>[show_progress_bar]" value="1" <?php checked( $progress_bar ); ?> />
											<span class="speakable-toggle-switch"><span class="speakable-toggle-knob"></span></span>
										</span>
									</label>
									<label class="speakable-toggle-row">
										<span class="speakable-toggle-info">
											<span class="speakable-toggle-name"><?php esc_html_e( 'Speed Control', 'speakable' ); ?></span>
											<span class="speakable-toggle-desc"><?php esc_html_e( 'Dropdown for visitors to change playback speed (0.75x - 2x)', 'speakable' ); ?></span>
										</span>
										<span class="speakable-toggle-switch-wrap">
											<input type="checkbox" id="speakable-speed" name="<?php echo esc_attr( $opt_key ); ?>[show_speed_control]" value="1" <?php checked( $speed_control ); ?> />
											<span class="speakable-toggle-switch"><span class="speakable-toggle-knob"></span></span>
										</span>
									</label>
									<label class="speakable-toggle-row">
										<span class="speakable-toggle-info">
											<span class="speakable-toggle-name"><?php esc_html_e( 'Sticky Player', 'speakable' ); ?></span>
											<span class="speakable-toggle-desc"><?php esc_html_e( 'Show a mini player at the bottom of the screen while scrolling during playback', 'speakable' ); ?></span>
										</span>
										<span class="speakable-toggle-switch-wrap">
											<input type="checkbox" id="speakable-sticky" name="<?php echo esc_attr( $opt_key ); ?>[sticky_player]" value="1" <?php checked( $sticky_player ); ?> />
											<span class="speakable-toggle-switch"><span class="speakable-toggle-knob"></span></span>
										</span>
									</label>
								</div>
							</div>

						</div>
					</div>

					<div class="speakable-save-row">
						<?php submit_button( __( 'Save Settings', 'speakable' ), 'primary speakable-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Preview Tab -->
				<div class="speakable-tab-panel" data-panel="preview" role="tabpanel" id="speakable-panel-preview" aria-labelledby="speakable-tab-preview">
					<div class="speakable-card">
						<div class="speakable-card-header">
							<h2 class="speakable-card-title"><?php esc_html_e( 'Live Preview', 'speakable' ); ?></h2>
							<p class="speakable-card-desc"><?php esc_html_e( 'Test the current voice settings before saving. Type or paste any text below.', 'speakable' ); ?></p>
						</div>
						<div class="speakable-card-body">
							<div class="speakable-preview-area">
								<textarea id="speakable-preview-text" rows="4" class="speakable-textarea"
									placeholder="<?php esc_attr_e( 'Type something to hear it spoken aloud...', 'speakable' ); ?>"
								><?php esc_html_e( 'Welcome to Speakable. This plugin lets your visitors listen to articles with a single click. Try adjusting the speed, pitch, and volume in the Voice tab to find the perfect settings for your audience.', 'speakable' ); ?></textarea>

								<div class="speakable-preview-controls">
									<button type="button" id="speakable-preview-btn" class="speakable-btn-preview">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
										<span id="speakable-preview-label"><?php esc_html_e( 'Play Preview', 'speakable' ); ?></span>
									</button>
									<button type="button" id="speakable-stop-preview-btn" class="speakable-btn-stop" style="display:none;">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										<?php esc_html_e( 'Stop', 'speakable' ); ?>
									</button>
									<div class="speakable-preview-wave" id="speakable-wave" style="display:none;">
										<span></span><span></span><span></span><span></span><span></span>
									</div>
								</div>
							</div>

							<!-- Player Mockup -->
							<div class="speakable-mockup">
								<p class="speakable-mockup-label"><?php esc_html_e( 'This is how the player looks on your site:', 'speakable' ); ?></p>
								<div class="speakable-player-mockup" style="--speakable-color: <?php echo esc_attr( $color ); ?>;">
									<div class="speakable-mockup-controls">
										<span class="speakable-mockup-play">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
											<?php esc_html_e( 'Listen to this article', 'speakable' ); ?>
										</span>
										<span class="speakable-mockup-stop">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										</span>
										<?php if ( $speed_control ) : ?>
											<span class="speakable-mockup-speed">1x</span>
										<?php endif; ?>
										<span class="speakable-mockup-counter">0 / 12</span>
									</div>
									<?php if ( $progress_bar ) : ?>
										<div class="speakable-mockup-progress">
											<div class="speakable-mockup-progress-fill"></div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

			</form>

		</div>
		<?php
	}

	/**
	 * Render the Analytics page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options        = get_option( SPEAKABLE_OPTION_KEY, array() );
		$enabled_types  = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$speech_rate    = isset( $options['speech_rate'] ) ? (float) $options['speech_rate'] : 1.0;
		$progress_bar   = ! empty( $options['show_progress_bar'] );
		$speed_control  = ! empty( $options['show_speed_control'] );
		$sticky_player  = ! empty( $options['sticky_player'] );
		$button_color   = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';
		$position       = isset( $options['button_position'] ) ? $options['button_position'] : 'before';

		// Gather basic stats.
		$total_posts = 0;
		foreach ( $enabled_types as $pt ) {
			$counts = wp_count_posts( $pt );
			if ( $counts ) {
				$total_posts += (int) $counts->publish;
			}
		}
		?>
		<div class="speakable-admin-wrap">

			<!-- Header -->
			<div class="speakable-header">
				<div class="speakable-header-inner">
					<div class="speakable-header-left">
						<div class="speakable-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
						</div>
						<div>
							<h1 class="speakable-header-title"><?php esc_html_e( 'Analytics', 'speakable' ); ?></h1>
							<p class="speakable-header-version"><?php esc_html_e( 'Usage overview for your TTS player', 'speakable' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Overview Stats -->
			<div class="speakable-card" style="border-radius: 0 0 12px 12px;">
				<div class="speakable-card-header">
					<h2 class="speakable-card-title"><?php esc_html_e( 'Overview', 'speakable' ); ?></h2>
				</div>
				<div class="speakable-card-body">
					<div class="speakable-stats-grid">
						<div class="speakable-stat-card">
							<div class="speakable-stat-icon" style="background: #eff6ff; color: #3b82f6;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							</div>
							<div class="speakable-stat-info">
								<span class="speakable-stat-value"><?php echo esc_html( $total_posts ); ?></span>
								<span class="speakable-stat-label"><?php esc_html_e( 'TTS-Enabled Posts', 'speakable' ); ?></span>
							</div>
						</div>

						<div class="speakable-stat-card">
							<div class="speakable-stat-icon" style="background: #f0fdf4; color: #22c55e;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
							</div>
							<div class="speakable-stat-info">
								<span class="speakable-stat-value"><?php echo esc_html( count( $enabled_types ) ); ?></span>
								<span class="speakable-stat-label"><?php esc_html_e( 'Enabled Post Types', 'speakable' ); ?></span>
							</div>
						</div>

						<div class="speakable-stat-card">
							<div class="speakable-stat-icon" style="background: #fef3c7; color: #f59e0b;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							</div>
							<div class="speakable-stat-info">
								<span class="speakable-stat-value"><?php echo esc_html( number_format( $speech_rate, 1 ) ); ?>x</span>
								<span class="speakable-stat-label"><?php esc_html_e( 'Default Speed', 'speakable' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Feature Status -->
			<div class="speakable-card" style="margin-top: 20px;">
				<div class="speakable-card-header">
					<h2 class="speakable-card-title"><?php esc_html_e( 'Feature Status', 'speakable' ); ?></h2>
					<p class="speakable-card-desc"><?php esc_html_e( 'Current configuration of all player features.', 'speakable' ); ?></p>
				</div>
				<div class="speakable-card-body">
					<div class="speakable-feature-list">
						<?php
						$features = array(
							array(
								'name'    => __( 'Progress Bar', 'speakable' ),
								'enabled' => $progress_bar,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
							),
							array(
								'name'    => __( 'Speed Control', 'speakable' ),
								'enabled' => $speed_control,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
							),
							array(
								'name'    => __( 'Sticky Player', 'speakable' ),
								'enabled' => $sticky_player,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
							),
						);
						foreach ( $features as $feature ) :
						?>
						<div class="speakable-feature-row">
							<div class="speakable-feature-left">
								<span class="speakable-feature-icon"><?php echo wp_kses_post( $feature['icon'] ); ?></span>
								<span class="speakable-feature-name"><?php echo esc_html( $feature['name'] ); ?></span>
							</div>
							<?php if ( $feature['enabled'] ) : ?>
								<span class="speakable-feature-badge speakable-feature-on"><?php esc_html_e( 'ON', 'speakable' ); ?></span>
							<?php else : ?>
								<span class="speakable-feature-badge speakable-feature-off"><?php esc_html_e( 'OFF', 'speakable' ); ?></span>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>

					<!-- Configuration Summary -->
					<div class="speakable-config-summary">
						<div class="speakable-config-item">
							<span class="speakable-config-label"><?php esc_html_e( 'Player Position', 'speakable' ); ?></span>
							<span class="speakable-config-value"><?php echo 'before' === $position ? esc_html__( 'Before Content', 'speakable' ) : esc_html__( 'After Content', 'speakable' ); ?></span>
						</div>
						<div class="speakable-config-item">
							<span class="speakable-config-label"><?php esc_html_e( 'Button Color', 'speakable' ); ?></span>
							<span class="speakable-config-value">
								<span class="speakable-config-color" style="background: <?php echo esc_attr( $button_color ); ?>;"></span>
								<?php echo esc_html( $button_color ); ?>
							</span>
						</div>
						<div class="speakable-config-item">
							<span class="speakable-config-label"><?php esc_html_e( 'Post Types', 'speakable' ); ?></span>
							<span class="speakable-config-value"><?php echo esc_html( implode( ', ', array_map( 'ucfirst', $enabled_types ) ) ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Coming Soon -->
			<div class="speakable-card" style="margin-top: 20px;">
				<div class="speakable-card-body">
					<div class="speakable-info-note">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
						<p><?php esc_html_e( 'Detailed playback analytics (total plays, average listen duration, most-listened posts) are coming in a future update. Stay tuned!', 'speakable' ); ?></p>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the Help page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="speakable-admin-wrap">

			<!-- Header -->
			<div class="speakable-header">
				<div class="speakable-header-inner">
					<div class="speakable-header-left">
						<div class="speakable-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						</div>
						<div>
							<h1 class="speakable-header-title"><?php esc_html_e( 'Help & Support', 'speakable' ); ?></h1>
							<p class="speakable-header-version"><?php esc_html_e( 'Everything you need to get started', 'speakable' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- FAQ -->
			<div class="speakable-card" style="border-radius: 0 0 12px 12px;">
				<div class="speakable-card-header">
					<h2 class="speakable-card-title"><?php esc_html_e( 'Frequently Asked Questions', 'speakable' ); ?></h2>
				</div>
				<div class="speakable-card-body">
					<div class="speakable-help-list">

						<div class="speakable-help-item">
							<h3 class="speakable-help-q"><?php esc_html_e( 'How does this plugin work?', 'speakable' ); ?></h3>
							<p class="speakable-help-a"><?php esc_html_e( 'Speakable uses the Web Speech API built into modern browsers. It reads the article text aloud using system voices on the visitor\'s device. No external API or server required - it\'s completely free.', 'speakable' ); ?></p>
						</div>

						<div class="speakable-help-item">
							<h3 class="speakable-help-q"><?php esc_html_e( 'Which browsers are supported?', 'speakable' ); ?></h3>
							<p class="speakable-help-a"><?php esc_html_e( 'Chrome, Edge, Safari, Firefox, and Opera all support the Web Speech API. If a visitor\'s browser doesn\'t support it, a friendly fallback message is shown instead of the player.', 'speakable' ); ?></p>
						</div>

						<div class="speakable-help-item">
							<h3 class="speakable-help-q"><?php esc_html_e( 'Why do voices sound different on different devices?', 'speakable' ); ?></h3>
							<p class="speakable-help-a"><?php esc_html_e( 'The Web Speech API uses voices installed on the visitor\'s operating system. Windows, macOS, Android, and iOS each have different built-in voices. The voice you select in settings is a preference - if it\'s not available on a visitor\'s device, their browser default is used.', 'speakable' ); ?></p>
						</div>

						<div class="speakable-help-item">
							<h3 class="speakable-help-q"><?php esc_html_e( 'Can I use this with custom post types?', 'speakable' ); ?></h3>
							<p class="speakable-help-a"><?php esc_html_e( 'Yes! Go to Speakable > Settings > Display tab and enable any public post type. The player will appear on singular views for all enabled types.', 'speakable' ); ?></p>
						</div>

						<div class="speakable-help-item">
							<h3 class="speakable-help-q"><?php esc_html_e( 'Does this plugin slow down my site?', 'speakable' ); ?></h3>
							<p class="speakable-help-a"><?php esc_html_e( 'No. The plugin loads a single lightweight CSS and JS file only on pages where the player is displayed. There are no external API calls, no server-side processing, and no impact on page load speed.', 'speakable' ); ?></p>
						</div>

					</div>
				</div>
			</div>

			<!-- Quick Links -->
			<div class="speakable-card" style="margin-top: 20px;">
				<div class="speakable-card-header">
					<h2 class="speakable-card-title"><?php esc_html_e( 'Quick Links', 'speakable' ); ?></h2>
				</div>
				<div class="speakable-card-body">
					<div class="speakable-help-links">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=speakable' ) ); ?>" class="speakable-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
							<span><?php esc_html_e( 'Plugin Settings', 'speakable' ); ?></span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=speakable-analytics' ) ); ?>" class="speakable-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
							<span><?php esc_html_e( 'View Analytics', 'speakable' ); ?></span>
						</a>
						<a href="https://devshagor.com/" target="_blank" rel="noopener noreferrer" class="speakable-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
							<span><?php esc_html_e( 'Devshagor Website', 'speakable' ); ?></span>
						</a>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}
