<?php

GFForms::include_addon_framework();

class GFPDFAddOn extends GFAddOn {

	protected $_version = GF_PDF_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gravityforms-to-pdf';
	protected $_path = 'gravityforms-to-pdf/gravityforms_to_pdf.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms to PDF Add-On';
	protected $_short_title = 'PDF';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFPDFAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFPDFAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handling hooks and loading of language files.
	 */
	public function init() {
		parent::init();
	}


	/**
	 * Enqueuing the scripts for PDF Settings.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'gf_pdf_script',
				'src'     => GF_PDF_ADDON_URL . 'js/scripts.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'wp-color-picker' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'gravityforms-to-pdf'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}


	/**
	 * Settings Options for the Form for PDF.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'PDF Settings', 'gf_pdf_addon' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enable PDF', 'gf_pdf_addon' ),
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'Enable PDF for this form', 'gf_pdf_addon' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'gf_pdf_addon' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'		=> esc_html__( 'Background Color', 'gf_pdf_addon' ),
						'type'		=> 'text',
						'name'		=> 'bg_color',
						'tooltip'	=> esc_html__( 'Background Color of PDF Pages', 'gf_pdf_addon' ),
						'class'		=> 'medium gf_pdf_colorpicker'
					),
					array(
						'label'   => esc_html__( 'Body Font Family', 'gf_pdf_addon' ),
						'type'    => 'select',
						'name'    => 'body_font',
						'tooltip' => esc_html__( 'Body text font family', 'gf_pdf_addon' ),
						'choices' => $this->font_choices(),
					),
					array(
						'label'		=> esc_html__( 'Body Font Color', 'gf_pdf_addon' ),
						'type'		=> 'text',
						'name'		=> 'body_color',
						'tooltip'	=> esc_html__( 'Body font color', 'gf_pdf_addon' ),
						'class'		=> 'medium gf_pdf_colorpicker'
					),
					array(
						'label'   => esc_html__( 'Heading Font Family', 'gf_pdf_addon' ),
						'type'    => 'select',
						'name'    => 'heading_font',
						'tooltip' => esc_html__( 'Headings (H1-H6) text font family', 'gf_pdf_addon' ),
						'choices' => $this->font_choices(),
					),
					array(
						'label'		=> esc_html__( 'Heading Font Color', 'gf_pdf_addon' ),
						'type'		=> 'text',
						'name'		=> 'heading_color',
						'tooltip'	=> esc_html__( 'Heading font color', 'gf_pdf_addon' ),
						'class'		=> 'medium gf_pdf_colorpicker'
					),	
					array(
						'label' => 'Header',
						'type'  => 'rich_text_field',
						'name'  => 'header_text',
						'tooltip'	=> esc_html__( 'To add an optional short text/image displayed in all page headers', 'gf_pdf_addon' ),
					),
					array(
						'label' => 'Footer',
						'type'  => 'rich_text_field',
						'name'  => 'footer_text',
						'tooltip'	=> esc_html__( 'To add an optional short text/image displayed in all page footers', 'gf_pdf_addon' ),
					),
					array(
						'label'   => esc_html__( 'Page number', 'gf_pdf_addon' ),
						'type'    => 'checkbox',
						'name'    => 'page_number',
						'tooltip' => esc_html__( 'If checked it will display a "Page [X] of [Y]" at the bottom of the page, below the footer text', 'gf_pdf_addon' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'gf_pdf_addon' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'   => esc_html__( 'Page Number Alignment', 'gf_pdf_addon' ),
						'type'    => 'select',
						'name'    => 'page_number_alignment',
						'tooltip' => esc_html__( 'Headings (H1-H6) text font family', 'gf_pdf_addon' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Left', 'gf_pdf_addon' ),
								'value' => 'left',
							),
							array(
								'label' => esc_html__( 'Right', 'gf_pdf_addon' ),
								'value' => 'right',
							),
							array(
								'label' => esc_html__( 'Center', 'gf_pdf_addon' ),
								'value' => 'center',
							)
						)
					),
					array(
						'label' => 'Content',
						'type'  => 'rich_text_field',
						'name'  => 'pdf_content',
						'tooltip'	=> esc_html__( 'PDF Content', 'gf_pdf_addon' ),
					),
				),
			),
		);
	}

	/**
	 * Custom Field: rich_text_field 
	 * Custom Rich Text Field with media option for the Settings Page
	 * Used for Header, Footer and Content of PDF 
	 */
	public function settings_rich_text_field( $field, $echo = true){
		wp_editor( $this->get_setting( $field['name'] ), '_gaddon_setting_' . $field['name'], array( 'autop' => false, 'editor_class' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-left' ) );
	}
	
	/**
	 * Font List for the PDF
	 * 
	 * @return array
	 */
	public function font_choices() {
		return array(
			array(
				'label' => esc_html__( 'Open Sans', 'gf_pdf_addon' ),
				'value' => 'open-sans',
			),
			array(
				'label' => esc_html__( 'PT Serif', 'gf_pdf_addon' ),
				'value' => 'pt-serif',
			)
		);
	}

}
