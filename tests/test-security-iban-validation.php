<?php
/** The stored IBAN validation rule must be enforced before public submission hooks. */
class Test_Security_Iban_Validation extends Super_Forms_Upload_Security_Test_Case {
    private function accepts_iban( $value ) {
        return $this->invoke_ajax_private(
            'submission_value_matches_validation',
            array( $value, array( 'validation' => 'iban' ) )
        );
    }

    public function test_server_iban_validation_matches_supported_browser_examples() {
        $this->assertTrue( $this->accepts_iban( 'NL91ABNA0417164300' ) );
        $this->assertTrue( $this->accepts_iban( 'de89 3704 0044 0532 0130 00' ) );
        $this->assertTrue( $this->accepts_iban( 'GB29NWBK60161331926819' ) );
        $this->assertFalse( $this->accepts_iban( 'NL91ABNA0417164301' ) );
        $this->assertFalse( $this->accepts_iban( 'NL91ABNA04171643' ) );
        $this->assertFalse( $this->accepts_iban( 'not-an-iban' ) );
    }

    public function test_server_accepts_all_bundled_iban_country_examples() {
        $examples = array(
            'AD' => 'AD1200012030200359100100',
            'AE' => 'AE070331234567890123456',
            'AL' => 'AL47212110090000000235698741',
            'AO' => 'AO69123456789012345678901',
            'AT' => 'AT611904300234573201',
            'AZ' => 'AZ21NABZ00000000137010001944',
            'BA' => 'BA391290079401028494',
            'BE' => 'BE68' . '539007547034',
            'BF' => 'BF2312345678901234567890123',
            'BG' => 'BG80BNBG96611020345678',
            'BH' => 'BH67BMAG00001299123456',
            'BI' => 'BI41123456789012',
            'BJ' => 'BJ39123456789012345678901234',
            'BR' => 'BR9700360305000010009795493P1',
            'CH' => 'CH9300762011623852957',
            'CI' => 'CI17A12345678901234567890123',
            'CM' => 'CM9012345678901234567890123',
            'CR' => 'CR72012300000171549015',
            'CV' => 'CV30123456789012345678901',
            'CY' => 'CY17002001280000001200527600',
            'CZ' => 'CZ6508000000192000145399',
            'DE' => 'DE89370400440532013000',
            'DK' => 'DK5000400440116243',
            'DO' => 'DO28BAGR00000001212453611324',
            'DZ' => 'DZ8612345678901234567890',
            'EE' => 'EE382200221020145685',
            'ES' => 'ES9121000418450200051332',
            'FI' => 'FI2112345600000785',
            'FO' => 'FO6264600001631634',
            'FR' => 'FR1420041010050500013M02606',
            'GB' => 'GB29NWBK60161331926819',
            'GE' => 'GE29NB0000000101904917',
            'GI' => 'GI75NWBK000000007099453',
            'GL' => 'GL8964710001000206',
            'GR' => 'GR1601101250000000012300695',
            'GT' => 'GT82TRAJ01020000001210029690',
            'HR' => 'HR1210010051863000160',
            'HU' => 'HU42117730161111101800000000',
            'IE' => 'IE29AIBK93115212345678',
            'IL' => 'IL620108000000099999999',
            'IR' => 'IR861234568790123456789012',
            'IS' => 'IS140159260076545510730339',
            'IT' => 'IT60X0542811101000000123456',
            'JO' => 'JO15AAAA1234567890123456789012',
            'KW' => 'KW81CBKU0000000000001234560101',
            'KZ' => 'KZ86125KZT5004100100',
            'LB' => 'LB62099900000001001901229114',
            'LC' => 'LC07HEMM000100010012001200013015',
            'LI' => 'LI21088100002324013AA',
            'LT' => 'LT121000011101001000',
            'LU' => 'LU280019400644750000',
            'LV' => 'LV80BANK0000435195001',
            'MC' => 'MC5811222000010123456789030',
            'MD' => 'MD24AG000225100013104168',
            'ME' => 'ME25505000012345678951',
            'MG' => 'MG1812345678901234567890123',
            'MK' => 'MK07250120000058984',
            'ML' => 'ML15A12345678901234567890123',
            'MR' => 'MR1300020001010000123456753',
            'MT' => 'MT84MALT011000012345MTLCAST001S',
            'MU' => 'MU17BOMM0101101030300200000MUR',
            'MZ' => 'MZ25123456789012345678901',
            'NL' => 'NL91ABNA0417164300',
            'NO' => 'NO9386011117947',
            'PK' => 'PK36SCBL0000001123456702',
            'PL' => 'PL61109010140000071219812874',
            'PS' => 'PS92PALS000000000400123456702',
            'PT' => 'PT50000201231234567890154',
            'QA' => 'QA30AAAA123456789012345678901',
            'RO' => 'RO49AAAA1B31007593840000',
            'RS' => 'RS35260005601001611379',
            'SA' => 'SA0380000000608010167519',
            'SE' => 'SE4550000000058398257466',
            'SI' => 'SI56263300012039086',
            'SK' => 'SK3112000000198742637541',
            'SM' => 'SM86U0322509800000000270100',
            'SN' => 'SN52A12345678901234567890123',
            'ST' => 'ST68000100010051845310112',
            'TL' => 'TL380080012345678910157',
            'TN' => 'TN5910006035183598478831',
            'TR' => 'TR330006100519786457841326',
            'UA' => 'UA511234567890123456789012345',
            'VG' => 'VG96VPVG0000012345678901',
            'XK' => 'XK051212012345678906',
        );
        foreach( $examples as $country => $value ) {
            $this->assertTrue( $this->accepts_iban($value), $country );
        }
    }

    public function test_public_submission_rejects_invalid_iban_before_processing() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array(
                'name' => 'bank_account', 'validation' => 'iban',
            ) ),
        ) );
        $this->set_request( $form_id, array(
            'bank_account' => array(
                'name' => 'bank_account', 'type' => 'var',
                'value' => 'NL91ABNA0417164301',
            ),
        ) );
        $this->assert_handler_rejected_with(
            array( 'SUPER_Ajax', 'submit_form_checks' ),
            'Invalid form data.'
        );
    }
}
