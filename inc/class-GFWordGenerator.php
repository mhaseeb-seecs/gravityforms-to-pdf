<?php

class GFWordGenerator extends GFPDFGenerator
{


    public function __construct($form, $entry, $path)
    {
        $this->form = $form;
        $this->entry = $entry;
        $this->filepath = $path;
    }


    /**
     *  Generating Word
     */
    public function save()
    {
        require_once(GF_PDF_ADDON_PATH . 'inc/html2docx/phpword/PHPWord.php');
        require_once(GF_PDF_ADDON_PATH . 'inc/html2docx/simplehtmldom/simple_html_dom.php');
        require_once(GF_PDF_ADDON_PATH . 'inc/html2docx/htmltodocx_converter/h2d_htmlconverter.php');
        require_once(GF_PDF_ADDON_PATH . 'inc/html2docx/support_functions.php');

        //Initializing PHPWord
        $phpword_object = new PHPWord();
        $this->setFont($phpword_object);

        //Create Main Section
        $section = $phpword_object->createSection();
        //Create Header
        $headerSection = $section->createHeader();
        //Create Footer
        $footerSection = $section->createFooter();

        //add footer numbering


        //Initialize State for the document
        $initial_state = $this->initialState($phpword_object);

        //Generate Header Section
        htmltodocx_insert_html($headerSection, $this->headerNodes(), $initial_state);
        //Generate Footer Section
        htmltodocx_insert_html($footerSection, $this->footerNodes(), $initial_state);
        $this->maybeAddPageNumber($footerSection);
        
        //Generate Content Section
        htmltodocx_insert_html($section, $this->contentNodes(), $initial_state);


        //Saving file to respective folder
        $h2d_file_uri = $this->filepath;
        $objWriter = PHPWord_IOFactory::createWriter($phpword_object, 'Word2007');
        $objWriter->save($h2d_file_uri);

    }

    /**
     * Generate Content HTML Nodes for the conversion
     *
     * @return array of nodes
     */
    protected function contentNodes()
    {
        $html = $this->maybeFormat($this->getSetting('pdf_content'));
        $html = apply_filters($this->filter_tag('content_html'), $html);

        $html_dom = new simple_html_dom();
        $html_dom->load('<html><body>' . $html . '</body></html>');
        $html_dom_array = $html_dom->find('html', 0)->children();

        return $html_dom_array[0]->nodes;
    }

    /**
     * Generate Header HTML Nodes for the conversion
     *
     * @return array of nodes
     */
    protected function headerNodes()
    {
        $html = $this->maybeFormat($this->getSetting('header_text'));
        $html = apply_filters($this->filter_tag('header_html'), $html);

        $headerDOM = new simple_html_dom();
        $headerDOM->load('<html><body>' . $html . '</body></html>');
        $headerDOMArray = $headerDOM->find('html', 0)->children();

        return $headerDOMArray[0]->nodes;
    }

    /**
     * Generate Footer HTML Nodes for the conversion
     *
     * @return array of nodes
     */
    protected function footerNodes()
    {

        $html = $this->maybeFormat($this->getSetting('footer_text'));
        $html = apply_filters($this->filter_tag('footer_html'), $html);

        $footerDOM = new simple_html_dom();
        $footerDOM->load('<html><body>' . $html . '</body></html>');
        $footerDOMArray = $footerDOM->find('html', 0)->children();

        return $footerDOMArray[0]->nodes;
    }

    protected function maybeAddPageNumber(&$footer) {
        //Add Page Number in case page number is enabled.
        $page_number = $this->getSetting('page_number_enabled');

        if($page_number) {
            $footer->addPreserveText('Page {PAGE} of {NUMPAGES}.', null, array('align' => $this->getSetting('page_number_alignment', 'center') ));
        }


    }

    /**
     * Get fonts list or particular font name
     *
     * @param mixed setting key of font (optional)
     *
     * @return string of font name
     */
    protected function getFonts($font = false)
    {
        $fonts = [
            'opensans' => 'Open Sans',
            'ptserif' => 'PT Serif'
        ];

        if ($font !== false) {
            if (isset($fonts[$font])) {
                return $fonts[$font];
            } else {
                return $fonts['opensans'];
            }
        }

        return $fonts;

    }

    /**
     * Setting font for the word document
     *
     * @param object of phpword
     *
     */
    protected function setFont(&$phpWord)
    {
        $fontSettings = $this->getSetting('body_font', 'opensans');
        $phpWord->setDefaultFontName($this->getFonts($fontSettings));

    }

    /**
     * Get Heading font for the word document
     *
     * @return string of fontname
     */
    protected function getHeadingFont()
    {
        $fontSettings = $this->getSetting('heading_font','opensans');
        return $this->getFonts($fontSettings);
    }

    /**
     * Get Heading font for the word document
     *
     * @param object of phpword
     *
     * @return array of initialize state
     */
    protected function initialState(&$phpword_object)
    {
        $paths = htmltodocx_paths();
        return array(
            // Required parameters:
            'phpword_object' => &$phpword_object,
            // Must be passed by reference.
            // 'base_root' => 'http://test.local', // Required for link elements - change it to your domain.
            // 'base_path' => '/htmltodocx/documentation/', // Path from base_root to whatever url your links are relative to.
            'base_root' => $paths['base_root'],
            'base_path' => $paths['base_path'],
            // Optional parameters - showing the defaults if you don't set anything:
            'current_style' => array('size' => '11'),
            // The PHPWord style on the top element - may be inherited by descendent elements.
            'parents' => array(0 => 'body'),
            // Our parent is body.
            'list_depth' => 0,
            // This is the current depth of any current list.
            'context' => 'section',
            // Possible values - section, footer or header.
            'pseudo_list' => true,
            // NOTE: Word lists not yet supported (TRUE is the only option at present).
            'pseudo_list_indicator_font_name' => 'Wingdings',
            // Bullet indicator font.
            'pseudo_list_indicator_font_size' => '7',
            // Bullet indicator size.
            'pseudo_list_indicator_character' => 'l ',
            // Gives a circle bullet point with wingdings.
            'table_allowed' => true,
            // Note, if you are adding this html into a PHPWord table you should set this to FALSE: tables cannot be nested in PHPWord.
            'treat_div_as_paragraph' => true,
            // If set to TRUE, each new div will trigger a new line in the Word document.

            'style_sheet' => $this->styles(),

        );
    }

    protected function styles()
    {
        $headingFont = $this->getHeadingFont();

        // Set of default styles -
        // to set initially whatever the element is:
        // NB - any defaults not set here will be provided by PHPWord.
        $styles['default'] =
            array(
                'size' => 11,
                'color' => str_replace('#', '', $this->getSetting('body_color')),
            );

        // Element styles:
        // The keys of the elements array are valid HTML tags;
        // The arrays associated with each of these tags is a set
        // of PHPWord style definitions.
        $styles['elements'] =
            array(
                'h1' => array(
                    'bold' => true,
                    'size' => 20,
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont
                ),
                'h2' => array(
                    'bold' => true,
                    'size' => 15,
                    'spaceAfter' => 150,
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont

                ),
                'h3' => array(
                    'size' => 12,
                    'bold' => true,
                    'spaceAfter' => 100,
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont
                ),
                'h4' => array(
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont
                ),
                'h5' => array(
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont
                ),
                'h6' => array(
                    'color' => str_replace('#', '', $this->getSetting('heading_color')),
                    'name' => $headingFont
                ),
                'li' => array(),
                'ol' => array(
                    'spaceBefore' => 200,
                ),
                'ul' => array(
                    'spaceAfter' => 150,
                ),
                'b' => array(
                    'bold' => true,
                ),
                'em' => array(
                    'italic' => true,
                ),
                'i' => array(
                    'italic' => true,
                ),
                'strong' => array(
                    'bold' => true,
                ),
                'sup' => array(
                    'superScript' => true,
                    'size' => 6,
                ), // Superscript not working in PHPWord
                'u' => array(
                    'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE,
                ),
                'a' => array(
                    'color' => '0000FF',
                    'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE,
                ),
                'table' => array(
                    // Note that applying a table style in PHPWord applies the relevant style to
                    // ALL the cells in the table. So, for example, the borderSize applied here
                    // applies to all the cells, and not just to the outer edges of the table:
                    'borderColor' => '000000',
                    'borderSize' => 10,
                ),
                'th' => array(
                    'borderColor' => '000000',
                    'borderSize' => 10,
                ),
                'td' => array(
                    'borderColor' => '000000',
                    'borderSize' => 10,
                ),
            );

        // Classes:
        // The keys of the classes array are valid CSS classes;
        // The array associated with each of these classes is a set
        // of PHPWord style definitions.
        // Classes will be applied in the order that they appear here if
        // more than one class appears on an element.
        $styles['classes'] =
            array(
                'underline' => array(
                    'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE,
                ),
                'purple' => array(
                    'color' => '901391',
                ),
                'green' => array(
                    'color' => '00A500',
                ),
            );

        // Inline style definitions, of the form:
        // array(css attribute-value - separated by a colon and a single space => array of
        // PHPWord attribute value pairs.
        $styles['inline'] =
            array(
                'text-decoration: underline' => array(
                    'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE,
                ),
                'vertical-align: left' => array(
                    'align' => 'left',
                ),
                'vertical-align: middle' => array(
                    'align' => 'center',
                ),
                'vertical-align: right' => array(
                    'align' => 'right',
                ),
            );

        return $styles;
    }


}
