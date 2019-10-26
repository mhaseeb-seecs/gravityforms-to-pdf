<?php 

class GFPDFGenerator {

    private $pageSize = 'Letter';
    private $mPDF, $form, $entry, $filepath;
    private $filterPrefix = 'gfpdf_pdf_';

    public function __construct( $form , $entry, $path ) {
        $this->form = $form;
        $this->entry = $entry;
        $this->filepath = $path;
        $this->mPDF = new \Mpdf\Mpdf( [
            'format' => $this->pageSize,
            'setAutoTopMargin' => true,
            'setAutoBottomMargin' => true,
            'fontDir' => $this->addFontDir(),
            'fontdata' => $this->addFontData(),
            'default_font' => 'opensans'
            ] );
    }

    public function save() {
        
        $this->setHeader( $this->getSetting('header_text') );
        
        $this->setFooter( $this->getSetting('footer_text') );

        $this->addStyles();
        
        $this->setContent( $this->getSetting('pdf_content') );
        
        
        $this->mPDF->Output( $this->filepath , \Mpdf\Output\Destination::FILE );
    }

    private function setHeader( $html ) {
        $html = $this->maybeFormat( $html );
        $html = apply_filters( $this->filter_tag( 'header_html' ) , $html );

        $this->mPDF->SetHTMLHeader( $html );
    }

    private function setContent( $html ) {

        $html = $this->maybeFormat( $html );
        $html = apply_filters( $this->filter_tag( 'content_html' ) , $html );

        $this->mPDF->WriteHTML( $html );
    }

    private function setFooter( $html ) {
        $html = $this->maybeFormat( $html );
        $html = apply_filters( $this->filter_tag( 'footer_html' ) , $html );

        //Add Page Number in case page number is enabled.
        $page_number = $this->getSetting('page_number_enabled');
        
        if($page_number) {
            $this->mPDF->AliasNbPages('{PAGETOTAL}');
            $html .= '<p style="text-align: '. $this->getSetting('page_number_alignment', 'center') .'">Page {PAGENO} of {PAGETOTAL}</p>';
        }

        $this->mPDF->SetHTMLFooter( $html );
    }

    private function addStyles() {
        $styles = '<style media="all">';

        //WP Editor Styles 
        $styles .= ' .aligncenter { display: block; margin-left: auto; margin-right: auto; } .alignright { float:right; margin: 5px 0 20px 20px;} .alignleft { float: left; margin: 5px 20px 20px 0; }';

        //Document Background Color
        if( !empty( $this->getSetting('bg_color') ) )
            $styles .= ' body { background-color: '. $this->getSetting('bg_color') .'; }';

        //Body Text Color
        if( !empty( $this->getSetting('body_color') ) )
            $styles .= ' body, p { color: '. $this->getSetting('body_color') .'; }';

        //Headings Color
        if( !empty( $this->getSetting('heading_color') ) )
            $styles .= ' h1, h2, h3, h4, h5, h6 { color: '. $this->getSetting('heading_color') .'; }';
        
        //Body Font
        if( !empty( $this->getSetting('body_font') ) )
            $styles .= ' body, p { font-family: '. $this->getSetting('body_font') .'; }';
        
        //Headings Font
        if( !empty( $this->getSetting('heading_font') ) )
            $styles .= ' h1, h2, h3, h4, h5, h6 { font-family: '. $this->getSetting('heading_font') .'; }'; 
            

        $styles .= '</style>';
        
        $this->mPDF->WriteHTML( $styles );
    }

    private function addFontDir() {
        $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        return array_merge( $fontDirs,  [ GF_PDF_ADDON_PATH . 'fonts' ] );
    }

    private function addFontData() {
        $fonts = [
            "opensans" => [
                'R' => "OpenSans-Regular.ttf",
                'B' => "OpenSans-Bold.ttf",
                'I' => "OpenSans-Italic.ttf",
                'BI' => "OpenSans-BoldItalic.ttf",
            ],
            "ptserif" => [
                'R' => "PTSerif-Regular.ttf",
                'B' => "PTSerif-Bold.ttf",
                'I' => "PTSerif-Italic.ttf",
                'BI' => "PTSerif-BoldItalic.ttf",
            ],
        ];
        
        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        
        return $fontData + $fonts;
    }

    private function getSetting( $name, $default = false ) {
        if( isset( $this->form['gravityforms-to-pdf'][$name] ) )
            return $this->form['gravityforms-to-pdf'][$name];

        return $default;
    }

    private function filter_tag( $tag ) {
        return $this->filterPrefix . $tag;
    }

    private function maybeFormat( $html ) {
        $html = $this->merge_tags($html);
        return wpautop( $html );
    }

    private function customMergeTags() {
        return [
            'page_break' => '<pagebreak page-break-type="slice">'
        ];
    }

    private function merge_tags ( $html ) {
        preg_match_all('/{(\w+)}/', $html, $matches);

        $newHTML = $html;
        $customTags = $this->customMergeTags();
        
        foreach ( $matches[0] as $index => $tag ) {
            if ( isset( $this->entry[$matches[1][$index]] ) ) {
                $newHTML = str_replace( $tag, $this->entry[$matches[1][$index]] , $newHTML );
            } else if (isset( $customTags[$matches[1][$index]] )) {
                $newHTML = str_replace( $tag, $customTags[$matches[1][$index]] , $newHTML );
            }
        }
        return $newHTML;
    }

}