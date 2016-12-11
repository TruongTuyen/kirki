<?php
/**
 * Customizer Control: code.
 *
 * Creates a new custom control.
 * Custom controls accept raw HTML/JS.
 *
 * @package     Kirki
 * @subpackage  Controls
 * @copyright   Copyright (c) 2016, Aristeides Stathopoulos
 * @license     http://opensource.org/licenses/https://opensource.org/licenses/MIT
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a "code" control, using CodeMirror.
 */
class Kirki_Control_Code extends WP_Customize_Control {

	/**
	 * The control type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = 'kirki-code';

	/**
	 * Used to automatically generate all CSS output.
	 *
	 * @access public
	 * @var array
	 */
	public $output = array();

	/**
	 * Data type
	 *
	 * @access public
	 * @var string
	 */
	public $option_type = 'theme_mod';

	/**
	 * The kirki_config we're using for this control
	 *
	 * @access public
	 * @var string
	 */
	public $kirki_config = 'global';

	/**
	 * The translation strings.
	 *
	 * @access protected
	 * @since 2.3.5
	 * @var array
	 */
	protected $l10n = array();

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @access public
	 */
	public function enqueue() {

		// Register codemirror.
		wp_register_script( 'codemirror', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/lib/codemirror.js', array( 'jquery' ) );

		// If we're using html mode, we'll also need to include the multiplex addon
		// as well as dependencies for XML, JS, CSS languages.
		if ( in_array( $this->choices['language'], array( 'html', 'htmlmixed' ), true ) ) {
			wp_enqueue_script( 'codemirror-multiplex', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/addon/mode/multiplex.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-xml', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/xml/xml.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-javascript', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/javascript/javascript.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-css', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/css/css.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-htmlmixed', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/htmlmixed/htmlmixed.js', array( 'jquery', 'codemirror', 'codemirror-multiplex', 'codemirror-language-xml', 'codemirror-language-javascript', 'codemirror-language-css' ) );
		} elseif ( 'php' === $this->choices['language'] ) {
			wp_enqueue_script( 'codemirror-language-xml', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/xml/xml.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-clike', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/clike/clike.js', array( 'jquery', 'codemirror' ) );
			wp_enqueue_script( 'codemirror-language-php', trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/php/php.js', array( 'jquery', 'codemirror', 'codemirror-language-xml', 'codemirror-language-clike' ) );
		} else {
			// Add language script.
			wp_enqueue_script( 'codemirror-language-' . $this->choices['language'], trailingslashit( Kirki::$url ) . 'controls/code/codemirror/mode/' . $this->choices['language'] . '/' . $this->choices['language'] . '.js', array( 'jquery', 'codemirror' ) );
		}

		// Add theme styles.
		wp_enqueue_style( 'codemirror-theme-' . $this->choices['theme'], trailingslashit( Kirki::$url ) . 'controls/code/codemirror/theme/' . $this->choices['theme'] . '.css' );

		wp_enqueue_script( 'kirki-code', trailingslashit( Kirki::$url ) . 'controls/code/code.js', array( 'jquery', 'customize-base', 'codemirror' ), false, true );
		wp_enqueue_style( 'kirki-code-css', trailingslashit( Kirki::$url ) . 'controls/code/code.css', null );

	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @see WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$this->json['default'] = $this->setting->default;
		if ( isset( $this->default ) ) {
			$this->json['default'] = $this->default;
		}
		$this->json['output']      = $this->output;
		$this->json['value']       = $this->value();
		$this->json['choices']     = $this->choices;
		$this->json['link']        = $this->get_link();
		$this->json['id']          = $this->id;
		$this->json['l10n']        = $this->l10n();
		$this->json['kirkiConfig'] = $this->kirki_config;

		if ( 'user_meta' === $this->option_type ) {
			$this->json['value'] = get_user_meta( get_current_user_id(), $this->id, true );
		}

		$this->json['inputAttrs'] = '';
		foreach ( $this->input_attrs as $attr => $value ) {
			$this->json['inputAttrs'] .= $attr . '="' . esc_attr( $value ) . '" ';
		}

	}

	/**
	 * An Underscore (JS) template for this control's content (but not its container).
	 *
	 * Class variables for this control class are available in the `data` JS object;
	 * export custom variables by overriding {@see WP_Customize_Control::to_json()}.
	 *
	 * @see WP_Customize_Control::print_template()
	 *
	 * @access protected
	 */
	protected function content_template() {
		?>
		<label>
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{{ data.label }}}</span>
			<# } #>
			<# if ( data.description ) { #>
				<span class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
			<a href="#" class="button edit button-primary">{{ data.choices.label }}</a>
			<textarea {{{ data.inputAttrs }}} class="kirki-codemirror-editor collapsed">{{{ data.value }}}</textarea>
			<a href="#" class="close">
				<span class="dashicons dashicons-no"></span>
				<span class="screen-reader-text">{{ data.l10n['close-editor'] }}</span>
			</a>
		</label>
		<?php
	}

	/**
	 * Returns an array of translation strings.
	 *
	 * @access protected
	 * @since 2.4.0
	 * @param string|false $id The string-ID.
	 * @return string
	 */
	protected function l10n( $id = false ) {
		$translation_strings = array(
			'close-editor' => esc_attr__( 'Close Editor', 'kirki' ),
		);
		return apply_filters( 'kirki/' . $this->kirki_config . '/l10n', $translation_strings );
	}
}