<?php
/**
 * Shared fixture for the generated-PDF regressions.
 *
 * A form with Signature elements produces a PDF that embeds every signature as an
 * image, so the file grows with each signature and page. The builder below writes a
 * structurally valid PDF of an exact byte size with a set number of embedded images,
 * which is what the client sends back as
 *   _generated_pdf_file[files][0][datauristring] = data:application/pdf;base64,...
 *
 * The embedded image streams are byte patterns, not decodable JPEGs on purpose: the
 * server never decodes them, it only measures and stores the PDF byte stream.
 */

if( !function_exists( 'super_forms_test_pdf_image_stream' ) ) {

    function super_forms_test_pdf_image_stream( $length=4096, $seed=1 ) {
        $stream = '';
        $value = $seed;
        for( $i=0; $i<$length; $i++ ) {
            $value = ( ( $value * 1103515245 ) + 12345 ) & 0x7fffffff;
            $stream .= chr( $value % 256 );
        }
        return $stream;
    }

    /**
     * Build a valid, exact-size PDF with $image_count embedded images over $page_count
     * pages, the way jsPDF lays out a submission with Signature elements.
     */
    function super_forms_test_build_signature_pdf( $target_bytes=8215751, $image_count=7, $page_count=3 ) {
        $image_stream = super_forms_test_pdf_image_stream( 4096, 7 );

        $build = static function( $pad ) use ( $image_count, $page_count, $image_stream ) {
            $page_objects = array();
            $image_objects = array();
            $next = 5;
            for( $page=0; $page<$page_count; $page++ ) {
                $page_objects[] = $next++;
            }
            for( $image=0; $image<$image_count; $image++ ) {
                $image_objects[] = $next++;
            }

            $bodies = array();
            $bodies[1] = "<< /Type /Catalog /Pages 2 0 R >>";
            $kids = array();
            foreach( $page_objects as $page_object ) {
                $kids[] = $page_object . ' 0 R';
            }
            $bodies[2] = "<< /Type /Pages /Kids [ " . implode( ' ', $kids ) . " ] /Count " . $page_count . " >>";
            $bodies[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
            foreach( $page_objects as $index => $page_object ) {
                $xobjects = '';
                foreach( $image_objects as $image_index => $image_object ) {
                    if( ( $image_index % $page_count ) !== $index ) {
                        continue;
                    }
                    $xobjects .= '/Im' . $image_index . ' ' . $image_object . ' 0 R ';
                }
                $bodies[$page_object] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
                    . " /Resources << /Font << /F1 3 0 R >> /XObject << " . $xobjects . ">> >> /Contents [] >>";
            }
            foreach( $image_objects as $image_object ) {
                $bodies[$image_object] = "<< /Type /XObject /Subtype /Image /Width 1200 /Height 480"
                    . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length "
                    . strlen( $image_stream ) . " >>\nstream\n" . $image_stream . "\nendstream";
            }
            ksort( $bodies );

            $pdf = "%PDF-1.4\n";
            $offsets = array();
            foreach( $bodies as $number => $body ) {
                $offsets[$number] = strlen( $pdf );
                $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
            }
            if( $pad > 0 ) {
                $pdf .= '%' . str_repeat( 'P', max( 0, $pad - 1 ) ) . "\n";
            }

            $size = count( $offsets ) + 1;
            $xref_offset = strlen( $pdf );
            $xref = "xref\n0 " . $size . "\n0000000000 65535 f \n";
            foreach( $offsets as $offset ) {
                $xref .= sprintf( "%010d 00000 n \n", $offset );
            }
            $xref .= "trailer\n<< /Size " . $size . " /Root 1 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF\n";
            return $pdf . $xref;
        };

        $pad = 0;
        $pdf = $build( $pad );
        for( $attempt=0; $attempt<8; $attempt++ ) {
            $delta = $target_bytes - strlen( $pdf );
            if( $delta===0 ) {
                return $pdf;
            }
            $pad = max( 0, $pad + $delta );
            $pdf = $build( $pad );
        }
        if( strlen( $pdf )!==$target_bytes ) {
            throw new RuntimeException( 'Unable to build a ' . $target_bytes . ' byte PDF fixture, got ' . strlen( $pdf ) );
        }
        return $pdf;
    }

    /** One drawn signature, exactly as the client posts it back to the server. */
    function super_forms_test_signature_data_uri( $seed=1, $length=2048 ) {
        return 'data:image/png;base64,' . base64_encode( super_forms_test_pdf_image_stream( $length, $seed ) );
    }

    /** The three Signature elements of a membership form, with their drawn values. */
    function super_forms_test_signature_elements() {
        $elements = array();
        foreach( array( 'firma', 'firma_fotoenewsletter', 'FIRMA_PRIVACY' ) as $name ) {
            $elements[] = array(
                'tag' => 'signature',
                'group' => 'form_elements',
                'data' => array(
                    'name' => $name,
                    'validation' => 'none',
                    'thickness' => '2',
                    'width' => '0',
                    'height' => '100',
                ),
            );
        }
        return $elements;
    }

    function super_forms_test_signature_values() {
        $values = array();
        $seed = 1;
        foreach( array( 'firma', 'firma_fotoenewsletter', 'FIRMA_PRIVACY' ) as $name ) {
            $values[$name] = array(
                'name' => $name,
                'type' => 'var',
                'value' => super_forms_test_signature_data_uri( $seed++ ),
            );
        }
        return $values;
    }

    /** The generated-PDF carrier the client posts with the submission. */
    function super_forms_test_generated_pdf_carrier( $data_uri ) {
        return array(
            '_generated_pdf_file' => array(
                'name' => '_generated_pdf_file',
                'type' => 'files',
                'files' => array( array(
                    'label' => 'PDF file:',
                    'name' => 'form.pdf',
                    'value' => 'form.pdf',
                    'datauristring' => $data_uri,
                ) ),
            ),
        );
    }
}
