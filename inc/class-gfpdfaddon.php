<?php

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
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 1, 2 );
		add_action( 'gform_post_payment_action', array( $this, 'after_payment' ), 1, 2 );
		add_filter( 'gform_pre_send_email', array( $this, 'notification_merge_tags' ), 10, 4 );
		add_filter( 'gform_confirmation', array( $this, 'confirmation_merge_tags' ), 10, 4 );
		
		//Tinymce Table Plugin
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 999 );
		add_filter( 'mce_buttons', array( $this, 'mce_buttons' ), 999 );
		
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
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'wp-color-picker',
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'gravityforms-to-pdf'
					)
				)
			),
			array(
				'handle'  => 'gf_pdf_styles_css',
				'src'     => GF_PDF_ADDON_URL . '/css/styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'gravityforms-to-pdf'
					)
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}


	/**
	 * Filter for the tinymce external plugins
	 * 
	 * @param array of plugins for the tinymce
	 * 
	 * @return array modified array for the plugins
	 */
	public function mce_external_plugins($plugins) {
		$plugins['table'] = GF_PDF_ADDON_URL . 'js/tinymce-table/plugin.min.js';
		return $plugins;
	}

	/**
	 * Buttons for External Plugins 
	 * 
	 * @param array of buttons
	 * 
	 * @param array modified array of buttons
	 */
	public function mce_buttons($buttons) {
		array_push($buttons, 'separator', 'table');
		return $buttons;
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
								'name' => 'pdf_enabled'
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
								'name'  => 'page_number_enabled'
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
		echo '<span class="mt-'. '_gaddon_setting_' . $field['name'].'"></span>';
		wp_editor( $this->get_setting( $field['name'] ), '_gaddon_setting_' . $field['name'], array( 'autop' => false, 'editor_class' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right' ) );
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
				'value' => 'opensans',
			),
			array(
				'label' => esc_html__( 'PT Serif', 'gf_pdf_addon' ),
				'value' => 'ptserif',
			)
		);
	}

	/**
	 * Evaluate the conditional logic.
	 *
	 * @param array $form The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 *
	 * @return bool
	 */
	public function is_pdf_enabled( $form ) {
		$settings = $this->get_form_settings( $form );

		$name       = 'pdf_enabled';
		$is_enabled = rgar( $settings, $name );

		if ( $is_enabled ) {
			return true;
		}
	}

	public function is_feed_entry(  $entry ) {
		if( empty($entry['transaction_type']) && empty($entry['payment_status']) )
			return false;

		return true;
	}

	/**
	 * Generating PDF on form submission without payments.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_submission( $entry, $form ) {

		// Evaluate the rules configured for the custom_logic setting.
		$result = $this->is_pdf_enabled( $form );

		if ( $result && !$this->is_feed_entry( $entry ) ) {
			$this->generate_pdf( $form, $entry );
            $this->generate_word( $form, $entry );
		}
	}

	/**
	 * Generating PDF on form submission after payment is completed.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_payment( $entry, $action ) {

        $form = GFAPI::get_form( $entry['form_id'] );
		// Evaluate the rules configured for the custom_logic setting.
		$result = $this->is_pdf_enabled( $form );
		if (  $result && in_array( $action['type'], array( 'add_subscription_payment', 'complete_payment') ) ) {
			$this->generate_pdf( $form, $entry );
		}
	}

	/**
	 * Genreating PDF for the Form Entry
	 * 
	 * @return string PDF filepath for the form entry
	 */
	public function generate_pdf( $form, $entry ) {
		
		$filepath = $this->get_pdf_filepath( $entry );
		$pdf = new GFPDFGenerator( $form, $entry, $filepath);
		$pdf->save();

		gform_update_meta( $entry['id'], 'pdf_addon_file', $this->get_pdf_url( $entry ) );
		return $filepath;
	}

    /**
     * Genreating Word for the Form Entry
     *
     * @return string PDF filepath for the form entry
     */
    public function generate_word( $form, $entry ) {

        $filepath = $this->get_word_filepath( $entry );
        $word = new GFWordGenerator( $form, $entry, $filepath);
        $word->save();

        gform_update_meta( $entry['id'], 'pdf_addon_word_file', $this->get_word_url( $entry ) );
        return $filepath;
    }

	/**
	 * Get form upload directory
	 * 
	 * @return string Form directory path
	 */
	public function get_pdf_form_dir( $form_id ) {
		$upload_dir   = wp_upload_dir();
		$form_dir = $upload_dir['basedir'].'/gfpdf/'. $form_id;

		if ( ! file_exists( $form_dir ) ) {
			wp_mkdir_p( $form_dir );
		}

		return trailingslashit( $form_dir );
	}

	/**
	 * Get form upload directory url
	 * 
	 * @return string Form directory url
	 */
	public function get_pdf_form_dir_url( $form_id ) {
		$upload_dir = wp_upload_dir();
		$form_dir = $upload_dir['baseurl'] .'/gfpdf/'. $form_id;
		return trailingslashit( $form_dir );
	}

	/**
	 * Get pdf file url 
	 * 
	 * @return string pdf file url for the entry
	 */
	public function get_pdf_url( $entry ) {
		$form_dir =  $this->get_pdf_form_dir_url( $entry['form_id'] );
		return $form_dir . $entry['id'] . '.pdf';
	}


    /**
     * Get word file url
     *
     * @return string pdf file url for the entry
     */
    public function get_word_url( $entry ) {
        $form_dir =  $this->get_pdf_form_dir_url( $entry['form_id'] );
        return $form_dir . $entry['id'] . '.docx';
    }

	/**
	 * Get PDF file path
	 * 
	 * @return string PDF filepath for the entry
	 */
	public function get_pdf_filepath( $entry ) {
		$form_dir = $this->get_pdf_form_dir( $entry['form_id'] );

		return $form_dir . $entry['id'] . '.pdf';
	}


    /**
     * Get Word file path
     *
     * @return string PDF filepath for the entry
     */
    public function get_word_filepath( $entry ) {
        $form_dir = $this->get_pdf_form_dir( $entry['form_id'] );

        return $form_dir . $entry['id'] . '.docx';
    }

	/**
	 * PDF column for entries page 
	 * 
	 * @return array entry_meta array
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {
		$entry_meta['pdf_addon_file']   = array(
			'label' => 'PDF',
			'is_numeric' => false,
			'is_default_column' => true,
			'update_entry_meta_callback' => array( $this, 'update_entry_meta' ),
			'filter' => array( 'operators' => array( 'is' ))
			);
        $entry_meta['pdf_addon_word_file']   = array(
            'label' => 'Word',
            'is_numeric' => false,
            'is_default_column' => true,
            'update_entry_meta_callback' => array( $this, 'update_entry_meta' ),
            'filter' => array( 'operators' => array( 'is' ))
        );
		return $entry_meta;
	}

	/**
	 * Callback for the entry meta update
	 * 
	 * @return string
	 */
	public function update_entry_meta( $key, $lead, $form ) {
		return ''; 
	}

	/**
	 * filter hook for gform_pre_send_email
	 * Merge Tags for the notifications send to the users
	 * 
	 * @return array email attributes
	 */
	public function notification_merge_tags(  $email, $message_format, $notification , $entry ) {

		$pdf_url = gform_get_meta( rgar( $entry, 'id' ), 'pdf_addon_file');
        $word_url = gform_get_meta( rgar( $entry, 'id' ), 'pdf_addon_word_file');

        $form = GFAPI::get_form( $entry['form_id'] );

		if ( empty($pdf_url) && $this->is_pdf_enabled( $form ) && !$this->is_feed_entry( $entry )) {
			$pdf_url = $this->get_pdf_url( $entry );
		}

        if ( empty($word_url) && $this->is_pdf_enabled( $form ) && !$this->is_feed_entry( $entry )) {
            $word_url = $this->get_word_url( $entry );
        }

		$customTags = [ 'pdf_url' => $pdf_url, 'word_url' => $word_url ];
		
		$email['message'] = $this->merge_tags( $email['message'], $customTags);

		return $email;
	}

	/**
	 * Filter hook for the confirmation messages 
	 * For Merge tags replacement 
	 * 
	 * @return mixed confirmation array or message depends on settings
	 */
	public function confirmation_merge_tags( $confirmation, $form, $entry, $ajax ) {
		$pdf_url = gform_get_meta( rgar( $entry, 'id' ), 'pdf_addon_file');
        $word_url = gform_get_meta( rgar( $entry, 'id' ), 'pdf_addon_word_file');

		if ( empty($pdf_url) && $this->is_pdf_enabled( $form ) && !$this->is_feed_entry( $entry )) {
			$pdf_url = $this->get_pdf_url( $entry );
		}

        if ( empty($word_url) && $this->is_pdf_enabled( $form ) && !$this->is_feed_entry( $entry )) {
            $word_url = $this->get_word_url( $entry );
        }

        $customTags = [ 'pdf_url' => $pdf_url, 'word_url' => $word_url ];
		
		$updatedText = is_array($confirmation) ? $confirmation['redirect'] :  $confirmation;
		$updatedText = $this->merge_tags( $updatedText, $customTags);

		if(is_array($confirmation))
			$confirmation['redirect'] = $updatedText;
		else
			$confirmation = $updatedText;

		return $confirmation;
	}

	/**
	 * Merge Tags replacement for the PDF Addon
	 * @param string original message with merge tags
	 * @param array tags array with values to be replaced
	 * 
	 * @return string updated message with values
	 */
	private function merge_tags( $body, $tags) {
		preg_match_all('/{(\w+)}/', $body, $matches);
		$newBody = $body;

		foreach ( $matches[0] as $index => $tag ) {
			if (isset( $tags[$matches[1][$index]] )) {
				$newBody = str_replace( $tag, $tags[$matches[1][$index]] , $newBody );
			}
		}
		
		return $newBody; 
	}

	/**
	 * Custom debug function for testing
	 */
	public function debug() {
		$args = func_get_args();
		echo '<pre>';
		foreach ($args as $index => $arg) {
			print_r($arg);
		}
		echo '</pre>';
		die('== GF PDF Debug ==');
	}

}
