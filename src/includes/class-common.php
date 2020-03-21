<?php
/**
 * Super Forms Common Class.
 *
 * @author      feeling4design
 * @category    Class
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Common
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Common' ) ) :

/**
 * SUPER_Common
 */
class SUPER_Common {
    
    // @since 4.7.7 - US states (currently used by dropdown element only)
    public static function us_states(){
        return array('Alabama'=>'AL','Alaska'=>'AK','Arizona'=>'AZ','Arkansas'=>'AR','California'=>'CA','Colorado'=>'CO','Connecticut'=>'CT','Delaware'=>'DE','District of Columbia'=>'DC','Florida'=>'FL','Georgia'=>'GA','Hawaii'=>'HI','Idaho'=>'ID','Illinois'=>'IL','Indiana'=>'IN','Iowa'=>'IA','Kansas'=>'KS','Kentucky'=>'KY','Louisiana'=>'LA','Maine'=>'ME','Montana'=>'MT','Nebraska'=>'NE','Nevada'=>'NV','New Hampshire'=>'NH','New Jersey'=>'NJ','New Mexico'=>'NM','New York'=>'NY','North Carolina'=>'NC','North Dakota'=>'ND','Ohio'=>'OH','Oklahoma'=>'OK','Oregon'=>'OR','Maryland'=>'MD','Massachusetts'=>'MA','Michigan'=>'MI','Minnesota'=>'MN','Mississippi'=>'MS','Missouri'=>'MO','Pennsylvania'=>'PA','Rhode Island'=>'RI','South Carolina'=>'SC','South Dakota'=>'SD','Tennessee'=>'TN','Texas'=>'TX','Utah'=>'UT','Vermont'=>'VT','Virginia'=>'VA','Washington'=>'WA','West Virginia'=>'WV','Wisconsin'=>'WI','Wyoming'=>'WY');
    }
    // @since 4.7.7 - Countries (currently used by dropdown element only)
    public static function countries(){
        return array(
            array('shortname'=>'Afghanistan','official'=>'the Islamic Republic of Afghanistan','iso3'=>'AFG','iso2'=>'AF'), array('shortname'=>'Albania','official'=>'the Republic of Albania','iso3'=>'ALB','iso2'=>'AL'), array('shortname'=>'Algeria','official'=>'the People\'s Democratic Republic of Algeria','iso3'=>'DZA','iso2'=>'DZ'), array('shortname'=>'Andorra','official'=>'the Principality of Andorra','iso3'=>'AND','iso2'=>'AD'), array('shortname'=>'Angola','official'=>'the Republic of Angola','iso3'=>'AGO','iso2'=>'AO'), array('shortname'=>'Antigua and Barbuda','official'=>'Antigua and Barbuda','iso3'=>'ATG','iso2'=>'AG'), array('shortname'=>'Argentina','official'=>'the Argentine Republic','iso3'=>'ARG','iso2'=>'AR'), array('shortname'=>'Armenia','official'=>'the Republic of Armenia','iso3'=>'ARM','iso2'=>'AM'), array('shortname'=>'Australia','official'=>'Australia','iso3'=>'AUS','iso2'=>'AU'), array('shortname'=>'Austria','official'=>'the Republic of Austria','iso3'=>'AUT','iso2'=>'AT'), array('shortname'=>'Azerbaijan','official'=>'the Republic of Azerbaijan','iso3'=>'AZE','iso2'=>'AZ'), array('shortname'=>'Bahamas','official'=>'the Commonwealth of the Bahamas','iso3'=>'BHS','iso2'=>'BS'), array('shortname'=>'Bahrain','official'=>'the Kingdom of Bahrain','iso3'=>'BHR','iso2'=>'BH'), array('shortname'=>'Bangladesh','official'=>'the People\'s Republic of Bangladesh','iso3'=>'BGD','iso2'=>'BD'), array('shortname'=>'Barbados','official'=>'Barbados','iso3'=>'BRB','iso2'=>'BB'), array('shortname'=>'Belarus','official'=>'the Republic of Belarus','iso3'=>'BLR','iso2'=>'BY'), array('shortname'=>'Belgium','official'=>'the Kingdom of Belgium','iso3'=>'BEL','iso2'=>'BE'), array('shortname'=>'Belize','official'=>'Belize','iso3'=>'BLZ','iso2'=>'BZ'), array('shortname'=>'Benin','official'=>'the Republic of Benin','iso3'=>'BEN','iso2'=>'BJ'), array('shortname'=>'Bhutan','official'=>'the Kingdom of Bhutan','iso3'=>'BTN','iso2'=>'BT'), array('shortname'=>'Bolivia (Plurinational State of)','official'=>'the Plurinational State of Bolivia','iso3'=>'BOL','iso2'=>'BO'), array('shortname'=>'Bosnia and Herzegovina','official'=>'Bosnia and Herzegovina','iso3'=>'BIH','iso2'=>'BA'), array('shortname'=>'Botswana','official'=>'the Republic of Botswana','iso3'=>'BWA','iso2'=>'BW'), array('shortname'=>'Brazil','official'=>'the Federative Republic of Brazil','iso3'=>'BRA','iso2'=>'BR'), array('shortname'=>'Brunei Darussalam','official'=>'Brunei Darussalam','iso3'=>'BRN','iso2'=>'BN'), array('shortname'=>'Bulgaria','official'=>'the Republic of Bulgaria','iso3'=>'BGR','iso2'=>'BG'), 
            array('shortname'=>'Burkina Faso','official'=>'Burkina Faso','iso3'=>'BFA','iso2'=>'BF'), array('shortname'=>'Burundi','official'=>'the Republic of Burundi','iso3'=>'BDI','iso2'=>'BI'), array('shortname'=>'Cabo Verde','official'=>'Republic of Cabo Verde','iso3'=>'CPV','iso2'=>'CV'), array('shortname'=>'Cambodia','official'=>'the Kingdom of Cambodia','iso3'=>'KHM','iso2'=>'KH'), array('shortname'=>'Cameroon','official'=>'the Republic of Cameroon','iso3'=>'CMR','iso2'=>'CM'), array('shortname'=>'Canada','official'=>'Canada','iso3'=>'CAN','iso2'=>'CA'), array('shortname'=>'Central African Republic','official'=>'the Central African Republic','iso3'=>'CAF','iso2'=>'CF'), array('shortname'=>'Chad','official'=>'the Republic of Chad','iso3'=>'TCD','iso2'=>'TD'), array('shortname'=>'Chile','official'=>'the Republic of Chile','iso3'=>'CHL','iso2'=>'CL'), array('shortname'=>'China','official'=>'the People\'s Republic of China','iso3'=>'CHN','iso2'=>'CN'), array('shortname'=>'Colombia','official'=>'the Republic of Colombia','iso3'=>'COL','iso2'=>'CO'), array('shortname'=>'Comoros','official'=>'the Union of the Comoros','iso3'=>'COM','iso2'=>'KM'), array('shortname'=>'Congo','official'=>'the Republic of the Congo','iso3'=>'COG','iso2'=>'CG'), array('shortname'=>'Cook Islands','official'=>'the Cook Islands','iso3'=>'COK','iso2'=>'CK'), array('shortname'=>'Costa Rica','official'=>'the Republic of Costa Rica','iso3'=>'CRI','iso2'=>'CR'), array('shortname'=>'Croatia','official'=>'the Republic of Croatia','iso3'=>'HRV','iso2'=>'HR'), array('shortname'=>'Cuba','official'=>'the Republic of Cuba','iso3'=>'CUB','iso2'=>'CU'), array('shortname'=>'Cyprus','official'=>'the Republic of Cyprus','iso3'=>'CYP','iso2'=>'CY'), array('shortname'=>'Czechia','official'=>'the Czech Republic','iso3'=>'CZE','iso2'=>'CZ'), array('shortname'=>'Côte d\'Ivoire','official'=>'the Republic of Côte d\'Ivoire','iso3'=>'CIV','iso2'=>'CI'), array('shortname'=>'Democratic People\'s Republic of Korea','official'=>'the Democratic People\'s Republic of Korea','iso3'=>'PRK','iso2'=>'KP'), array('shortname'=>'Democratic Republic of the Congo','official'=>'the Democratic Republic of the Congo','iso3'=>'COD','iso2'=>'CD'), array('shortname'=>'Denmark','official'=>'the Kingdom of Denmark','iso3'=>'DNK','iso2'=>'DK'), array('shortname'=>'Djibouti','official'=>'the Republic of Djibouti','iso3'=>'DJI','iso2'=>'DJ'), array('shortname'=>'Dominica','official'=>'the Commonwealth of Dominica','iso3'=>'DMA','iso2'=>'DM'), array('shortname'=>'Dominican Republic','official'=>'the Dominican Republic','iso3'=>'DOM','iso2'=>'DO'), array('shortname'=>'Ecuador','official'=>'the Republic of Ecuador','iso3'=>'ECU','iso2'=>'EC'), array('shortname'=>'Egypt','official'=>'the Arab Republic of Egypt','iso3'=>'EGY','iso2'=>'EG'), array('shortname'=>'El Salvador','official'=>'the Republic of El Salvador','iso3'=>'SLV','iso2'=>'SV'), array('shortname'=>'Equatorial Guinea','official'=>'the Republic of Equatorial Guinea','iso3'=>'GNQ','iso2'=>'GQ'), array('shortname'=>'Eritrea','official'=>'the State of Eritrea','iso3'=>'ERI','iso2'=>'ER'), array('shortname'=>'Estonia','official'=>'the Republic of Estonia','iso3'=>'EST','iso2'=>'EE'), array('shortname'=>'Eswatini','official'=>'the Kingdom of Eswatini ','iso3'=>'SWZ','iso2'=>'SZ'), array('shortname'=>'Ethiopia','official'=>'the Federal Democratic Republic of Ethiopia','iso3'=>'ETH','iso2'=>'ET'), array('shortname'=>'Faroe Islands ','official'=>'Faroe Islands','iso3'=>'FRO','iso2'=>'FO'), array('shortname'=>'Fiji','official'=>'the Republic of Fiji','iso3'=>'FJI','iso2'=>'FJ'), array('shortname'=>'Finland','official'=>'the Republic of Finland','iso3'=>'FIN','iso2'=>'FI'), array('shortname'=>'France','official'=>'the French Republic','iso3'=>'FRA','iso2'=>'FR'), array('shortname'=>'Gabon','official'=>'the Gabonese Republic','iso3'=>'GAB','iso2'=>'GA'), array('shortname'=>'Gambia','official'=>'the Republic of the Gambia','iso3'=>'GMB','iso2'=>'GM'), array('shortname'=>'Georgia','official'=>'Georgia','iso3'=>'GEO','iso2'=>'GE'), array('shortname'=>'Germany','official'=>'the Federal Republic of Germany','iso3'=>'DEU','iso2'=>'DE'), array('shortname'=>'Ghana','official'=>'the Republic of Ghana','iso3'=>'GHA','iso2'=>'GH'), array('shortname'=>'Greece','official'=>'the Hellenic Republic','iso3'=>'GRC','iso2'=>'GR'), array('shortname'=>'Grenada','official'=>'Grenada','iso3'=>'GRD','iso2'=>'GD'), array('shortname'=>'Guatemala','official'=>'the Republic of Guatemala','iso3'=>'GTM','iso2'=>'GT'), array('shortname'=>'Guinea','official'=>'the Republic of Guinea','iso3'=>'GIN','iso2'=>'GN'), array('shortname'=>'Guinea-Bissau','official'=>'the Republic of Guinea-Bissau','iso3'=>'GNB','iso2'=>'GW'), array('shortname'=>'Guyana','official'=>'the Co-operative Republic of Guyana','iso3'=>'GUY','iso2'=>'GY'), array('shortname'=>'Haiti','official'=>'the Republic of Haiti','iso3'=>'HTI','iso2'=>'HT'), array('shortname'=>'Honduras','official'=>'the Republic of Honduras','iso3'=>'HND','iso2'=>'HN'), array('shortname'=>'Hungary','official'=>'Hungary','iso3'=>'HUN','iso2'=>'HU'), array('shortname'=>'Iceland','official'=>'the Republic of Iceland','iso3'=>'ISL','iso2'=>'IS'), 
            array('shortname'=>'India','official'=>'the Republic of India','iso3'=>'IND','iso2'=>'IN'), array('shortname'=>'Indonesia','official'=>'the Republic of Indonesia','iso3'=>'IDN','iso2'=>'ID'), array('shortname'=>'Iran (Islamic Republic of)','official'=>'the Islamic Republic of Iran','iso3'=>'IRN','iso2'=>'IR'), array('shortname'=>'Iraq','official'=>'the Republic of Iraq','iso3'=>'IRQ','iso2'=>'IQ'), array('shortname'=>'Ireland','official'=>'Ireland','iso3'=>'IRL','iso2'=>'IE'), array('shortname'=>'Israel','official'=>'the State of Israel','iso3'=>'ISR','iso2'=>'IL'), array('shortname'=>'Italy','official'=>'the Republic of Italy','iso3'=>'ITA','iso2'=>'IT'), array('shortname'=>'Jamaica','official'=>'Jamaica','iso3'=>'JAM','iso2'=>'JM'), array('shortname'=>'Japan','official'=>'Japan','iso3'=>'JPN','iso2'=>'JP'), array('shortname'=>'Jordan','official'=>'the Hashemite Kingdom of Jordan','iso3'=>'JOR','iso2'=>'JO'), array('shortname'=>'Kazakhstan','official'=>'the Republic of Kazakhstan','iso3'=>'KAZ','iso2'=>'KZ'), array('shortname'=>'Kenya','official'=>'the Republic of Kenya','iso3'=>'KEN','iso2'=>'KE'), array('shortname'=>'Kiribati','official'=>'the Republic of Kiribati','iso3'=>'KIR','iso2'=>'KI'), array('shortname'=>'Kuwait','official'=>'the State of Kuwait','iso3'=>'KWT','iso2'=>'KW'), array('shortname'=>'Kyrgyzstan','official'=>'the Kyrgyz Republic','iso3'=>'KGZ','iso2'=>'KG'), array('shortname'=>'Lao People\'s Democratic Republic','official'=>'the Lao People\'s Democratic Republic','iso3'=>'LAO','iso2'=>'LA'), array('shortname'=>'Latvia','official'=>'the Republic of Latvia','iso3'=>'LVA','iso2'=>'LV'), array('shortname'=>'Lebanon','official'=>'the Lebanese Republic','iso3'=>'LBN','iso2'=>'LB'), array('shortname'=>'Lesotho','official'=>'the Kingdom of Lesotho','iso3'=>'LSO','iso2'=>'LS'), array('shortname'=>'Liberia','official'=>'the Republic of Liberia','iso3'=>'LBR','iso2'=>'LR'), array('shortname'=>'Libya','official'=>'State of Libya','iso3'=>'LBY','iso2'=>'LY'), array('shortname'=>'Lithuania','official'=>'the Republic of Lithuania','iso3'=>'LTU','iso2'=>'LT'), array('shortname'=>'Luxembourg','official'=>'the Grand Duchy of Luxembourg','iso3'=>'LUX','iso2'=>'LU'), array('shortname'=>'Madagascar','official'=>'the Republic of Madagascar','iso3'=>'MDG','iso2'=>'MG'), array('shortname'=>'Malawi','official'=>'the Republic of Malawi','iso3'=>'MWI','iso2'=>'MW'), array('shortname'=>'Malaysia','official'=>'Malaysia','iso3'=>'MYS','iso2'=>'MY'), array('shortname'=>'Maldives','official'=>'the Republic of Maldives','iso3'=>'MDV','iso2'=>'MV'), 
            array('shortname'=>'Mali','official'=>'the Republic of Mali','iso3'=>'MLI','iso2'=>'ML'), array('shortname'=>'Malta','official'=>'the Republic of Malta','iso3'=>'MLT','iso2'=>'MT'), array('shortname'=>'Marshall Islands','official'=>'the Republic of the Marshall Islands','iso3'=>'MHL','iso2'=>'MH'), array('shortname'=>'Mauritania','official'=>'the Islamic Republic of Mauritania','iso3'=>'MRT','iso2'=>'MR'), array('shortname'=>'Mauritius','official'=>'the Republic of Mauritius','iso3'=>'MUS','iso2'=>'MU'), array('shortname'=>'Mexico','official'=>'the United Mexican States','iso3'=>'MEX','iso2'=>'MX'), array('shortname'=>'Micronesia (Federated States of)','official'=>'the Federated States of Micronesia','iso3'=>'FSM','iso2'=>'FM'), array('shortname'=>'Monaco','official'=>'the Principality of Monaco','iso3'=>'MCO','iso2'=>'MC'), array('shortname'=>'Mongolia','official'=>'Mongolia','iso3'=>'MNG','iso2'=>'MN'), array('shortname'=>'Montenegro','official'=>'Montenegro','iso3'=>'MNE','iso2'=>'ME'), array('shortname'=>'Morocco','official'=>'the Kingdom of Morocco','iso3'=>'MAR','iso2'=>'MA'), array('shortname'=>'Mozambique','official'=>'the Republic of Mozambique','iso3'=>'MOZ','iso2'=>'MZ'), array('shortname'=>'Myanmar','official'=>'the Republic of the Union of Myanmar','iso3'=>'MMR','iso2'=>'MM'), array('shortname'=>'Namibia','official'=>'the Republic of Namibia','iso3'=>'NAM','iso2'=>'NA'), array('shortname'=>'Nauru','official'=>'the Republic of Nauru','iso3'=>'NRU','iso2'=>'NR'), array('shortname'=>'Nepal','official'=>'the Federal Democratic Republic of Nepal','iso3'=>'NPL','iso2'=>'NP'), array('shortname'=>'Netherlands','official'=>'the Kingdom of the Netherlands','iso3'=>'NLD','iso2'=>'NL'), array('shortname'=>'New Zealand','official'=>'New Zealand','iso3'=>'NZL','iso2'=>'NZ'), array('shortname'=>'Nicaragua','official'=>'the Republic of Nicaragua','iso3'=>'NIC','iso2'=>'NI'), array('shortname'=>'Niger','official'=>'the Republic of the Niger','iso3'=>'NER','iso2'=>'NE'), array('shortname'=>'Nigeria','official'=>'the Federal Republic of Nigeria','iso3'=>'NGA','iso2'=>'NG'), array('shortname'=>'Niue','official'=>'Niue','iso3'=>'NIU','iso2'=>'NU'), array('shortname'=>'North Macedonia','official'=>'the Republic of North Macedonia ','iso3'=>'MKD','iso2'=>'MK'), array('shortname'=>'Norway','official'=>'the Kingdom of Norway','iso3'=>'NOR','iso2'=>'NO'), array('shortname'=>'Oman','official'=>'the Sultanate of Oman','iso3'=>'OMN','iso2'=>'OM'), array('shortname'=>'Pakistan','official'=>'the Islamic Republic of Pakistan','iso3'=>'PAK','iso2'=>'PK'), 
            array('shortname'=>'Palau','official'=>'the Republic of Palau','iso3'=>'PLW','iso2'=>'PW'), array('shortname'=>'Panama','official'=>'the Republic of Panama','iso3'=>'PAN','iso2'=>'PA'), array('shortname'=>'Papua New Guinea','official'=>'Independent State of Papua New Guinea','iso3'=>'PNG','iso2'=>'PG'), array('shortname'=>'Paraguay','official'=>'the Republic of Paraguay','iso3'=>'PRY','iso2'=>'PY'), array('shortname'=>'Peru','official'=>'the Republic of Peru','iso3'=>'PER','iso2'=>'PE'), array('shortname'=>'Philippines','official'=>'the Republic of the Philippines','iso3'=>'PHL','iso2'=>'PH'), array('shortname'=>'Poland','official'=>'the Republic of Poland','iso3'=>'POL','iso2'=>'PL'), array('shortname'=>'Portugal','official'=>'the Portuguese Republic','iso3'=>'PRT','iso2'=>'PT'), array('shortname'=>'Qatar','official'=>'the State of Qatar','iso3'=>'QAT','iso2'=>'QA'), array('shortname'=>'Republic of Korea','official'=>'the Republic of Korea','iso3'=>'KOR','iso2'=>'KR'), array('shortname'=>'Republic of Moldova','official'=>'the Republic of Moldova','iso3'=>'MDA','iso2'=>'MD'), array('shortname'=>'Romania','official'=>'Romania','iso3'=>'ROU','iso2'=>'RO'), array('shortname'=>'Russian Federation','official'=>'the Russian Federation','iso3'=>'RUS','iso2'=>'RU'), array('shortname'=>'Rwanda','official'=>'the Republic of Rwanda','iso3'=>'RWA','iso2'=>'RW'), array('shortname'=>'Saint Kitts and Nevis','official'=>'Saint Kitts and Nevis','iso3'=>'KNA','iso2'=>'KN'), array('shortname'=>'Saint Lucia','official'=>'Saint Lucia','iso3'=>'LCA','iso2'=>'LC'), array('shortname'=>'Saint Vincent and the Grenadines','official'=>'Saint Vincent and the Grenadines','iso3'=>'VCT','iso2'=>'VC'), array('shortname'=>'Samoa','official'=>'the Independent State of Samoa','iso3'=>'WSM','iso2'=>'WS'), array('shortname'=>'San Marino','official'=>'the Republic of San Marino','iso3'=>'SMR','iso2'=>'SM'), array('shortname'=>'Sao Tome and Principe','official'=>'the Democratic Republic of Sao Tome and Principe','iso3'=>'STP','iso2'=>'ST'), array('shortname'=>'Saudi Arabia','official'=>'the Kingdom of Saudi Arabia','iso3'=>'SAU','iso2'=>'SA'), array('shortname'=>'Senegal','official'=>'the Republic of Senegal','iso3'=>'SEN','iso2'=>'SN'), array('shortname'=>'Serbia','official'=>'the Republic of Serbia','iso3'=>'SRB','iso2'=>'RS'), array('shortname'=>'Seychelles','official'=>'the Republic of Seychelles','iso3'=>'SYC','iso2'=>'SC'), array('shortname'=>'Sierra Leone','official'=>'the Republic of Sierra Leone','iso3'=>'SLE','iso2'=>'SL'), array('shortname'=>'Singapore','official'=>'the Republic of Singapore','iso3'=>'SGP','iso2'=>'SG'), 
            array('shortname'=>'Slovakia','official'=>'the Slovak Republic','iso3'=>'SVK','iso2'=>'SK'), array('shortname'=>'Slovenia','official'=>'the Republic of Slovenia','iso3'=>'SVN','iso2'=>'SI'), array('shortname'=>'Solomon Islands','official'=>'Solomon Islands','iso3'=>'SLB','iso2'=>'SB'), array('shortname'=>'Somalia','official'=>'the Federal Republic of Somalia','iso3'=>'SOM','iso2'=>'SO'), array('shortname'=>'South Africa','official'=>'the Republic of South Africa','iso3'=>'ZAF','iso2'=>'ZA'), array('shortname'=>'South Sudan','official'=>'the Republic of South Sudan','iso3'=>'SSD','iso2'=>'SS'), array('shortname'=>'Spain','official'=>'the Kingdom of Spain','iso3'=>'ESP','iso2'=>'ES'), array('shortname'=>'Sri Lanka','official'=>'the Democratic Socialist Republic of Sri Lanka','iso3'=>'LKA','iso2'=>'LK'), array('shortname'=>'Sudan','official'=>'the Republic of the Sudan','iso3'=>'SDN','iso2'=>'SD'), array('shortname'=>'Suriname','official'=>'the Republic of Suriname','iso3'=>'SUR','iso2'=>'SR'), array('shortname'=>'Sweden','official'=>'the Kingdom of Sweden','iso3'=>'SWE','iso2'=>'SE'), array('shortname'=>'Switzerland','official'=>'the Swiss Confederation','iso3'=>'CHE','iso2'=>'CH'), array('shortname'=>'Syrian Arab Republic','official'=>'the Syrian Arab Republic','iso3'=>'SYR','iso2'=>'SY'), array('shortname'=>'Tajikistan','official'=>'the Republic of Tajikistan','iso3'=>'TJK','iso2'=>'TJ'), array('shortname'=>'Thailand','official'=>'the Kingdom of Thailand','iso3'=>'THA','iso2'=>'TH'), array('shortname'=>'Timor-Leste','official'=>'the Democratic Republic of Timor-Leste','iso3'=>'TLS','iso2'=>'TL'), array('shortname'=>'Togo','official'=>'the Togolese Republic','iso3'=>'TGO','iso2'=>'TG'), array('shortname'=>'Tokelau ','official'=>'Tokelau','iso3'=>'TKL','iso2'=>'TK'), array('shortname'=>'Tonga','official'=>'the Kingdom of Tonga','iso3'=>'TON','iso2'=>'TO'), array('shortname'=>'Trinidad and Tobago','official'=>'the Republic of Trinidad and Tobago','iso3'=>'TTO','iso2'=>'TT'), array('shortname'=>'Tunisia','official'=>'the Republic of Tunisia','iso3'=>'TUN','iso2'=>'TN'), array('shortname'=>'Turkey','official'=>'the Republic of Turkey','iso3'=>'TUR','iso2'=>'TR'), array('shortname'=>'Turkmenistan','official'=>'Turkmenistan','iso3'=>'TKM','iso2'=>'TM'), array('shortname'=>'Tuvalu','official'=>'Tuvalu','iso3'=>'TUV','iso2'=>'TV'), array('shortname'=>'Uganda','official'=>'the Republic of Uganda','iso3'=>'UGA','iso2'=>'UG'), array('shortname'=>'Ukraine','official'=>'Ukraine','iso3'=>'UKR','iso2'=>'UA'), array('shortname'=>'United Arab Emirates','official'=>'the United Arab Emirates','iso3'=>'ARE','iso2'=>'AE'), 
            array('shortname'=>'United Kingdom of Great Britain and Northern Ireland','official'=>'the United Kingdom of Great Britain and Northern Ireland','iso3'=>'GBR','iso2'=>'GB'), array('shortname'=>'United Republic of Tanzania','official'=>'the United Republic of Tanzania','iso3'=>'TZA','iso2'=>'TZ'), array('shortname'=>'United States of America','official'=>'the United States of America','iso3'=>'USA','iso2'=>'US'), array('shortname'=>'Uruguay','official'=>'the Eastern Republic of Uruguay','iso3'=>'URY','iso2'=>'UY'), array('shortname'=>'Uzbekistan','official'=>'the Republic of Uzbekistan','iso3'=>'UZB','iso2'=>'UZ'), array('shortname'=>'Vanuatu','official'=>'the Republic of Vanuatu','iso3'=>'VUT','iso2'=>'VU'), array('shortname'=>'Venezuela (Bolivarian Republic of)','official'=>'the Bolivarian Republic of Venezuela','iso3'=>'VEN','iso2'=>'VE'), array('shortname'=>'Viet Nam','official'=>'the Socialist Republic of Viet Nam','iso3'=>'VNM','iso2'=>'VN'), array('shortname'=>'Yemen','official'=>'the Republic of Yemen','iso3'=>'YEM','iso2'=>'YE'), array('shortname'=>'Zambia','official'=>'the Republic of Zambia','iso3'=>'ZMB','iso2'=>'ZM'), array('shortname'=>'Zimbabwe','official'=>'the Republic of Zimbabwe','iso3'=>'ZWE','iso2'=>'ZW')
        );
    }
    public static function get_dropdown_items($type){
        if($type=='states') $data = self::us_states();
        if($type=='countries_iso2' || $type=='countries_normal' || $type=='countries_full') $data = self::countries();
        $items = array();
        foreach($data as $label => $value){
            if($type=='countries_iso2'){
                $label = $value['shortname'];
                $value = $value['iso2'];
            }elseif($type=='countries_normal'){
                $label = $value['shortname'];
                $value = $value['shortname'];
            }elseif($type=='countries_full'){
                $label = $value['shortname'] . ' (' . $value['iso2'] . ')';
                $value = $value['shortname'].';'.$value['iso2'].';'.$value['iso3'].';'.$value['official'];
            }
            $items[] = array(
                'checked' => false,
                'label' => $label,
                'value' => $value
            );
        }
        return $items;
    }
    // Function to replace tags inside dynamic columns for conditional logic and conditional variable logic
    public static function replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, $settingName){
        if(isset($v['data'][$settingName])){
            foreach($v['data'][$settingName] as $ck => $cv){
                // Replace {tags}
                // `field`
                if(!empty($cv['field'])){
                    $str = $cv['field'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $str = str_replace($mv[0], '{'.$new_tag.'}', $str);
                        }
                    }
                    $v['data'][$settingName][$ck]['field'] = $str;
                }
                // `field_and`
                if(!empty($cv['field_and'])){
                    $str = $cv['field_and'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $str = str_replace($mv[0], '{'.$new_tag.'}', $str);
                        }
                    }
                    $v['data'][$settingName][$ck]['field_and'] = $str;
                }
                // `value`
                if(!empty($cv['value'])){
                    $str = $cv['value'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value']);
                        }
                    }
                    $v['data'][$settingName][$ck]['value'] = $cv['value'];
                }
                // `value_and`
                if(!empty($cv['value_and'])){
                    $str = $cv['value_and'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['value_and'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['value_and']);
                        }
                    }
                    $v['data'][$settingName][$ck]['value_and'] = $cv['value_and'];
                }
                // `new_value`
                if(!empty($cv['new_value'])){
                    $str = $cv['new_value'];
                    if($str=='{}') continue; // We must skip accidentaly empty tags or it might result in JS error
                    // If field name doesn't contain any curly braces, then append and prepend them and continue;
                    if ( strpos( $str, '{') === false ) $str = '{'.$str.'}';
                    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                    foreach($matches as $mk => $mv){
                        // In case advanced tag is used explode it
                        $values = explode(";", $mv[1]);
                        if(in_array($values[0], $dynamic_field_names)){
                            $new_name = $values[0].'_'.$i;
                            $values[0] = $new_name;
                            $new_tag = implode(";", $values);
                            $cv['new_value'] = str_replace($mv[0], '{'.$new_tag.'}', $cv['new_value']);
                        }
                    }
                    $v['data'][$settingName][$ck]['new_value'] = $cv['new_value'];
                }
            }
        }
        return $v;
    }
    // Function used for dynamic columns to replace {tags} in conditional logics with correct updated field names
    public static function replace_tags_dynamic_columns($v, $re, $i, $dynamic_field_names, $inner_field_names, $dv=array()){
        // Rename Email Label and Field name accordingly 
        if(!empty($v['data']['name'])){
            $current_name = $v['data']['name'];
            if(isset($inner_field_names[$current_name])){
                if($i>1){
                    $v['data']['name'] = $inner_field_names[$current_name]['name'] . '_' . $i;
                }else{
                    $v['data']['name'] = $inner_field_names[$current_name]['name'];
                }
                $v['data']['email'] = SUPER_Common::convert_field_email_label($inner_field_names[$current_name]['email'], $i);
            }
            if( !empty($dv[$current_name]) ) {
                if(!empty($dv[$current_name]['value'])) {
                    // Now override the "Default value" with the actual Entry data
                    $v['data']['value'] = $dv[$current_name]['value'];
                }
            }
        }
        if($i>1){
            // If inside dynamic column, and not the first dynamic column we need to replace all the {tags} accordingly
            // Rename conditional logics accordingly
            $v = SUPER_Common::replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, 'conditional_items');
            $v = SUPER_Common::replace_tags_dynamic_column_conditional_items($v, $dynamic_field_names, 'conditional_variable_items');
            // Replace HTML {tags}
            if($v['tag']=='html') {
                $str = $v['data']['html'];
                preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                foreach($matches as $mk => $mv){
                    // In case advanced tag is used explode it
                    $values = explode(";", $mv[1]);
                    if(in_array($values[0], $dynamic_field_names)){
                        $new_name = $values[0].'_'.$i;
                        $values[0] = $new_name;
                        $new_tag = implode(";", $values);
                        $v['data']['html'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['html']);
                    }
                }
            }
            // Replace calculator math with correct {tags}
            if(isset($v['data']['math'])){
                $str = $v['data']['math'];
                preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
                foreach($matches as $mk => $mv){
                    // In case advanced tag is used explode it
                    $values = explode(";", $mv[1]);
                    if(in_array($values[0], $dynamic_field_names)){
                        $new_name = $values[0].'_'.$i;
                        $values[0] = $new_name;
                        $new_tag = implode(";", $values);
                        $v['data']['math'] = str_replace($mv[0], '{'.$new_tag.'}', $v['data']['math']);
                    }
                }
            }
        }
        return $v;
    }


    // @since 4.7.7 - get the absolute default value of an element
    // this function is used specifically for dynamic column system
    public static function get_absolute_default_value($element, $shortcodes=false){
        $tag = $element['tag'];
        // Check if element belongs to one of those with `multi-items`, if not just grab the `value` setting
        $multi_item_elements = array('radio', 'checkbox', 'dropdown');
        if(in_array($tag, $multi_item_elements)){
            // Let's loop over the items and grab the ones that are selected by default
            if($tag=='radio'){
                if(!empty($element['data']['radio_items'])){
                    $items = $element['data']['radio_items'];
                    foreach($items as $v){
                        if($v['checked']==='1' || $v['checked']==='true'){
                            // Since radio buttons only can have one selected item return instantly
                            return $v['value'];
                        }
                    }
                }
                return '';
            }else{
                $values = array();
                if($tag=='checkbox'){
                    if(!empty($element['data']['checkbox_items'])){
                        $items = $element['data']['checkbox_items'];
                        foreach($items as $v){
                            if($v['checked']==='1' || $v['checked']==='true'){
                                $values[] = $v['value'];
                            }
                        }
                        return implode(',', $values);
                    }
                    return '';
                }
                if($tag=='dropdown'){
                    if(!empty($element['data']['dropdown_items'])){
                        $items = $element['data']['dropdown_items'];
                        foreach($items as $v){
                            if($v['checked']==='1' || $v['checked']==='true'){
                                $values[] = $v['value'];
                            }
                        }
                        return implode(',', $values);
                    }
                    return '';
                }
            }
        }else{
            // Not an element with `multi-items` let's return the `value` instead
            if(isset($element['data']['value'])){
                return $element['data']['value'];
            }else{
                // If no such data exists, check for element default setting
                $default_value = self::get_default_element_setting_value($shortcodes, $element['group'], $tag, 'general', 'value');
                // If no such data exists it will return an empty string
                return $default_value;
            }
        }
        return '';
    }


    /**
     * This function grabs any "Email label" setting value from a field, and converts it to the correct Email label if needed.
     * When a field is inside a dynamic column, it should for instance append the correct counter
     * Users can define where the counter itself should be placed by defining a %d inside the Email label
     * e.g: `Product %d quantity:` will be converted to `Product 4 quantity:`
     * We will also returned a trimmed version to remove any whitespaces at the start or end of the label
     */
    public static function convert_field_email_label($email_label, $counter, $clean=false){
        // Remove whitespaces from start and end
        $email_label = trim($email_label);
        if($counter<2){
            if($clean){
                return str_replace('%d', '', str_replace('%d ', '', $email_label));
            }else{
                return $email_label;
            }
        }
        $pos = strpos($email_label, '%d');
        if ($pos === false) {
            // Not found, just return with counter appended at the end
            return $email_label . ' ' . $counter;
        } else {
            // Found, return with counter replaced at correct position
            return str_replace('%d', $counter, $email_label);
        }
    }

    /**
     * This function takes the last comma or dot (if any) to make a clean float, ignoring thousand separator, currency or any other letter :
     */
    public static function tofloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
       
        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        } 

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
        );
    }


    /**
     * Country Flags
     */
    public static function get_flags($key=null){
        $flags = array(
            // Africa
            'dz' => 'Algeria','ao' => 'Angola','bj' => 'Benin','bw' => 'Botswana','bf' => 'Burkina Faso','bi' => 'Burundi','cm' => 'Cameroon','cv' => 'Cape Verde','cf' => 'Central African Republic','td' => 'Chad','km' => 'Comoros','cg' => 'Congo','cd' => 'Congo, The Democratic Republic of the','ci' => 'Cote d\'Ivoire','dj' => 'Djibouti','eg' => 'Egypt','gq' => 'Equatorial Guinea','er' => 'Eritrea','et' => 'Ethiopia','ga' => 'Gabon','gm' => 'Gambia','gh' => 'Ghana','gn' => 'Guinea','gw' => 'Guinea-Bissau','ke' => 'Kenya','ls' => 'Lesotho','lr' => 'Liberia','ly' => 'Libya','mg' => 'Madagascar','mw' => 'Malawi','ml' => 'Mali','mr' => 'Mauritania','mu' => 'Mauritius','yt' => 'Mayotte','ma' => 'Morocco','mz' => 'Mozambique','na' => 'Namibia','ne' => 'Niger','ng' => 'Nigeria','re' => 'Reunion','rw' => 'Rwanda','sh' => 'Saint Helena','st' => 'Sao Tome and Principe','sn' => 'Senegal','sc' => 'Seychelles','sl' => 'Sierra Leone','so' => 'Somalia','za' => 'South Africa','ss' => 'South Sudan','sd' => 'Sudan','sz' => 'Swaziland','tz' => 'Tanzania','tg' => 'Togo','tn' => 'Tunisia','ug' => 'Uganda','eh' => 'Western Sahara','zm' => 'Zambia','zw' => 'Zimbabwe',

            // America
            'ai' => 'Anguilla','ag' => 'Antigua and Barbuda','ar' => 'Argentina','aw' => 'Aruba','bs' => 'Bahamas','bb' => 'Barbados','bz' => 'Belize','bm' => 'Bermuda','bo' => 'Bolivia, Plurinational State of','br' => 'Brazil','ca' => 'Canada','ky' => 'Cayman Islands','cl' => 'Chile','co' => 'Colombia','cr' => 'Costa Rica','cu' => 'Cuba','cw' => 'Curacao','dm' => 'Dominica','do' => 'Dominican Republic','ec' => 'Ecuador','sv' => 'El Salvador','fk' => 'Falkland Islands (Malvinas)','gf' => 'French Guiana','gl' => 'Greenland','gd' => 'Grenada','gp' => 'Guadeloupe','gt' => 'Guatemala','gy' => 'Guyana','ht' => 'Haiti','hn' => 'Honduras','jm' => 'Jamaica','mq' => 'Martinique','mx' => 'Mexico','ms' => 'Montserrat','an' => 'Netherlands Antilles','ni' => 'Nicaragua','pa' => 'Panama','py' => 'Paraguay','pe' => 'Peru','pr' => 'Puerto Rico','kn' => 'Saint Kitts and Nevis','lc' => 'Saint Lucia','pm' => 'Saint Pierre and Miquelon','vc' => 'Saint Vincent and the Grenadines','sx' => 'Sint Maarten','sr' => 'Suriname','tt' => 'Trinidad and Tobago','tc' => 'Turks and Caicos Islands','us' => 'United States','uy' => 'Uruguay','ve' => 'Venezuela, Bolivarian Republic of','vg' => 'Virgin Islands, British','vi' => 'Virgin Islands, U.S.',

            // Asia
            'af' => 'Afghanistan','am' => 'Armenia','az' => 'Azerbaijan','bh' => 'Bahrain','bd' => 'Bangladesh','bt' => 'Bhutan','bn' => 'Brunei Darussalam','kh' => 'Cambodia','cn' => 'China','cy' => 'Cyprus','ge' => 'Georgia','hk' => 'Hong Kong','in' => 'India','id' => 'Indonesia','ir' => 'Iran, Islamic Republic of','iq' => 'Iraq','il' => 'Israel','jp' => 'Japan','jo' => 'Jordan','kz' => 'Kazakhstan','kp' => 'Korea, Democratic People\'s Republic of','kr' => 'Korea, Republic of','kw' => 'Kuwait','kg' => 'Kyrgyzstan','la' => 'Lao People\'s Democratic Republic','lb' => 'Lebanon','mo' => 'Macao','my' => 'Malaysia','mv' => 'Maldives','mn' => 'Mongolia','mm' => 'Myanmar','np' => 'Nepal','om' => 'Oman','pk' => 'Pakistan','ps' => 'Palestinian Territory, Occupied','ph' => 'Philippines','qa' => 'Qatar','sa' => 'Saudi Arabia','sg' => 'Singapore','lk' => 'Sri Lanka','sy' => 'Syrian Arab Republic','tw' => 'Taiwan, Province of China','tj' => 'Tajikistan','th' => 'Thailand','tl' => 'Timor-Leste','tr' => 'Turkey','tm' => 'Turkmenistan','ae' => 'United Arab Emirates','uz' => 'Uzbekistan','vn' => 'Viet Nam','ye' => 'Yemen',

            // Europe
            'ax' => 'Aland Islands','al' => 'Albania','ad' => 'Andorra','at' => 'Austria','by' => 'Belarus','be' => 'Belgium','ba' => 'Bosnia and Herzegovina','bg' => 'Bulgaria','hr' => 'Croatia','cz' => 'Czech Republic','dk' => 'Denmark','ee' => 'Estonia','fo' => 'Faroe Islands','fi' => 'Finland','fr' => 'France','de' => 'Germany','gi' => 'Gibraltar','gr' => 'Greece','gg' => 'Guernsey','va' => 'Holy See (Vatican City State)','hu' => 'Hungary','is' => 'Iceland','ie' => 'Ireland','im' => 'Isle of Man','it' => 'Italy','je' => 'Jersey','xk' => 'Kosovo','lv' => 'Latvia','li' => 'Liechtenstein','lt' => 'Lithuania','lu' => 'Luxembourg','mk' => 'Macedonia, The Former Yugoslav Republic of','mt' => 'Malta','md' => 'Moldova, Republic of','mc' => 'Monaco','me' => 'Montenegro','nl' => 'Netherlands','no' => 'Norway','pl' => 'Poland','pt' => 'Portugal','ro' => 'Romania','ru' => 'Russian Federation','sm' => 'San Marino','rs' => 'Serbia','sk' => 'Slovakia','si' => 'Slovenia','es' => 'Spain','sj' => 'Svalbard and Jan Mayen','se' => 'Sweden','ch' => 'Switzerland','ua' => 'Ukraine','gb' => 'United Kingdom',

            // Australia and Oceania
            'as' => 'American Samoa','au' => 'Australia','ck' => 'Cook Islands','fj' => 'Fiji','pf' => 'French Polynesia','gu' => 'Guam','ki' => 'Kiribati','mh' => 'Marshall Islands','fm' => 'Micronesia, Federated States of','nr' => 'Nauru','nc' => 'New Caledonia','nz' => 'New Zealand','nu' => 'Niue','nf' => 'Norfolk Island','mp' => 'Northern Mariana Islands','pw' => 'Palau','pg' => 'Papua New Guinea','pn' => 'Pitcairn','ws' => 'Samoa','sb' => 'Solomon Islands','tk' => 'Tokelau','to' => 'Tonga','tv' => 'Tuvalu','vu' => 'Vanuatu','wf' => 'Wallis and Futuna',

            // Other areas
            'bv' => 'Bouvet Island','io' => 'British Indian Ocean Territory','ic' => 'Canary Islands','catalonia' => 'Catalonia','england' => 'England','eu' => 'European Union','tf' => 'French Southern Territories','hm' => 'Heard Island and McDonald Islands','kurdistan' => 'Kurdistan','scotland' => 'Scotland','somaliland' => 'Somaliland','gs' => 'South Georgia and the South Sandwich Islands','tibet' => 'Tibet','um' => 'United States Minor Outlying Islands','wales' => 'Wales','zanzibar' => 'Zanzibar'
        );
        if(!empty($key)){
            return $flags[$key];
        }
        return $flags;
    }


    /**
     * Get Form Translations
     */
    public static function get_form_translations($form_id){
        $translations = get_post_meta( $form_id, '_super_translations', true );
        return $translations;
    }


    /**
     * Font Awesome 5 Free backwards compatibility
     */
    public static function fontawesome_bwc($icon){
        $old_to_new = array(
            'address-book-o' => 'address-book', 'address-card-o' => 'address-card', 'area-chart' => 'chart-area', 'arrow-circle-o-down' => 'arrow-alt-circle-down', 'arrow-circle-o-left' => 'arrow-alt-circle-left', 'arrow-circle-o-right' => 'arrow-alt-circle-right', 'arrow-circle-o-up' => 'arrow-alt-circle-up', 'arrows' => 'arrows-alt', 'arrows-alt' => 'expand-arrows-alt', 'arrows-h' => 'arrows-alt-h', 'arrows-v' => 'arrows-alt-v', 'asl-interpreting' => 'american-sign-language-interpreting', 'automobile' => 'car', 'bank' => 'university', 'bar-chart' => 'chart-bar', 'bar-chart-o' => 'chart-bar', 'bathtub' => 'bath', 'battery' => 'battery-full', 'battery-0' => 'battery-empty', 'battery-1' => 'battery-quarter', 'battery-2' => 'battery-half', 'battery-3' => 'battery-three-quarters', 'battery-4' => 'battery-full', 'bell-o' => 'bell', 'bell-slash-o' => 'bell-slash', 'bitbucket-square' => 'bitbucket', 'bitcoin' => 'btc', 'bookmark-o' => 'bookmark', 'building-o' => 'building', 'cab' => 'taxi', 'calendar' => 'calendar-alt', 'calendar-check-o' => 'calendar-check', 'calendar-minus-o' => 'calendar-minus', 'calendar-o' => 'calendar', 'calendar-plus-o' => 'calendar-plus', 'calendar-times-o' => 'calendar-times', 'caret-square-o-down' => 'caret-square-down', 'caret-square-o-left' => 'caret-square-left', 'caret-square-o-right' => 'caret-square-right', 'caret-square-o-up' => 'caret-square-up', 'cc' => 'closed-captioning', 'chain' => 'link', 'chain-broken' => 'unlink', 'check-circle-o' => 'check-circle', 'check-square-o' => 'check-square', 'circle-o' => 'circle', 'circle-o-notch' => 'circle-notch', 'circle-thin' => 'circle', 'clock-o' => 'clock', 'close' => 'times', 'cloud-download' => 'cloud-download-alt', 'cloud-upload' => 'cloud-upload-alt', 'cny' => 'yen-sign', 'code-fork' => 'code-branch', 'comment-o' => 'comment', 'commenting' => 'comment-dots', 'commenting-o' => 'comment-dots', 'comments-o' => 'comments', 'credit-card-alt' => 'credit-card', 'cutlery' => 'utensils', 'dashboard' => 'tachometer-alt', 'deafness' => 'deaf', 'dedent' => 'outdent', 'diamond' => 'gem', 'dollar' => 'dollar-sign', 'dot-circle-o' => 'dot-circle', 'drivers-license' => 'id-card', 'drivers-license-o' => 'id-card', 'eercast' => 'sellcast', 'envelope-o' => 'envelope', 'envelope-open-o' => 'envelope-open', 'eur' => 'euro-sign', 'euro' => 'euro-sign', 'exchange' => 'exchange-alt', 'external-link' => 'external-link-alt', 'external-link-square' => 'external-link-square-alt', 'eyedropper' => 'eye-dropper', 'fa' => 'font-awesome', 'facebook' => 'facebook-f', 'facebook-official' => 'facebook', 'feed' => 'rss', 'file-archive-o' => 'file-archive', 'file-audio-o' => 'file-audio', 'file-code-o' => 'file-code', 'file-excel-o' => 'file-excel', 'file-image-o' => 'file-image', 'file-movie-o' => 'file-video', 'file-o' => 'file', 'file-pdf-o' => 'file-pdf', 'file-photo-o' => 'file-image', 'file-picture-o' => 'file-image', 'file-powerpoint-o' => 'file-powerpoint', 'file-sound-o' => 'file-audio', 'file-text' => 'file-alt', 'file-text-o' => 'file-alt', 'file-video-o' => 'file-video', 'file-word-o' => 'file-word', 'file-zip-o' => 'file-archive', 'files-o' => 'copy', 'flag-o' => 'flag', 'flash' => 'bolt', 'floppy-o' => 'save', 'folder-o' => 'folder', 'folder-open-o' => 'folder-open', 'frown-o' => 'frown', 'futbol-o' => 'futbol', 'gbp' => 'pound-sign', 'ge' => 'empire', 'gear' => 'cog', 'gears' => 'cogs', 'gittip' => 'gratipay', 'glass' => 'glass-martini', 'google-plus' => 'google-plus-g', 'google-plus-circle' => 'google-plus', 'google-plus-official' => 'google-plus', 'group' => 'users', 'hand-grab-o' => 'hand-rock', 'hand-lizard-o' => 'hand-lizard', 'hand-o-down' => 'hand-point-down', 'hand-o-left' => 'hand-point-left', 'hand-o-right' => 'hand-point-right', 'hand-o-up' => 'hand-point-up', 'hand-paper-o' => 'hand-paper', 'hand-peace-o' => 'hand-peace', 'hand-pointer-o' => 'hand-pointer', 'hand-rock-o' => 'hand-rock', 'hand-scissors-o' => 'hand-scissors', 'hand-spock-o' => 'hand-spock', 'hand-stop-o' => 'hand-paper', 'handshake-o' => 'handshake', 'hard-of-hearing' => 'deaf', 'hdd-o' => 'hdd', 'header' => 'heading', 'heart-o' => 'heart', 'hospital-o' => 'hospital', 'hotel' => 'bed', 'hourglass-1' => 'hourglass-start', 'hourglass-2' => 'hourglass-half', 'hourglass-3' => 'hourglass-end', 'hourglass-o' => 'hourglass', 'id-card-o' => 'id-card', 'ils' => 'shekel-sign', 'inr' => 'rupee-sign', 'institution' => 'university', 'intersex' => 'transgender', 'jpy' => 'yen-sign', 'keyboard-o' => 'keyboard', 'krw' => 'won-sign', 'legal' => 'gavel', 'lemon-o' => 'lemon', 'level-down' => 'level-down-alt', 'level-up' => 'level-up-alt', 'life-bouy' => 'life-ring', 'life-buoy' => 'life-ring', 'life-saver' => 'life-ring', 'lightbulb-o' => 'lightbulb', 'line-chart' => 'chart-line', 'linkedin' => 'linkedin-in', 'linkedin-square' => 'linkedin', 'long-arrow-down' => 'long-arrow-alt-down', 'long-arrow-left' => 'long-arrow-alt-left', 'long-arrow-right' => 'long-arrow-alt-right', 'long-arrow-up' => 'long-arrow-alt-up', 'mail-forward' => 'share', 'mail-reply' => 'reply', 'mail-reply-all' => 'reply-all', 'map-marker' => 'map-marker-alt', 'map-o' => 'map', 'meanpath' => 'font-awesome', 'meh-o' => 'meh', 'minus-square-o' => 'minus-square', 'mobile' => 'mobile-alt', 'mobile-phone' => 'mobile-alt', 'money' => 'money-bill-alt', 'moon-o' => 'moon', 'mortar-board' => 'graduation-cap', 'navicon' => 'bars', 'newspaper-o' => 'newspaper', 'paper-plane-o' => 'paper-plane', 'paste' => 'clipboard', 'pause-circle-o' => 'pause-circle', 'pencil' => 'pencil-alt', 'pencil-square' => 'pen-square', 'pencil-square-o' => 'edit', 'photo' => 'image', 'picture-o' => 'image', 'pie-chart' => 'chart-pie', 'play-circle-o' => 'play-circle', 'plus-square-o' => 'plus-square', 'question-circle-o' => 'question-circle', 'ra' => 'rebel', 'refresh' => 'sync', 'remove' => 'times', 'reorder' => 'bars', 'repeat' => 'redo', 'resistance' => 'rebel', 'rmb' => 'yen-sign', 'rotate-left' => 'undo', 'rotate-right' => 'redo', 'rouble' => 'ruble-sign', 'rub' => 'ruble-sign', 'ruble' => 'ruble-sign', 'rupee' => 'rupee-sign', 's15' => 'bath', 'scissors' => 'cut', 'send' => 'paper-plane', 'send-o' => 'paper-plane', 'share-square-o' => 'share-square', 'shekel' => 'shekel-sign', 'sheqel' => 'shekel-sign', 'shield' => 'shield-alt', 'sign-in' => 'sign-in-alt', 'sign-out' => 'sign-out-alt', 'signing' => 'sign-language', 'sliders' => 'sliders-h', 'smile-o' => 'smile', 'snowflake-o' => 'snowflake', 'soccer-ball-o' => 'futbol', 'sort-alpha-asc' => 'sort-alpha-down', 'sort-alpha-desc' => 'sort-alpha-up', 'sort-amount-asc' => 'sort-amount-down', 'sort-amount-desc' => 'sort-amount-up', 'sort-asc' => 'sort-up', 'sort-desc' => 'sort-down', 'sort-numeric-asc' => 'sort-numeric-down', 'sort-numeric-desc' => 'sort-numeric-up', 'spoon' => 'utensil-spoon', 'square-o' => 'square', 'star-half-empty' => 'star-half', 'star-half-full' => 'star-half', 'star-half-o' => 'star-half', 'star-o' => 'star', 'sticky-note-o' => 'sticky-note', 'stop-circle-o' => 'stop-circle', 'sun-o' => 'sun', 'support' => 'life-ring', 'tablet' => 'tablet-alt', 'tachometer' => 'tachometer-alt', 'television' => 'tv', 'thermometer' => 'thermometer-full', 'thermometer-0' => 'thermometer-empty', 'thermometer-1' => 'thermometer-quarter', 'thermometer-2' => 'thermometer-half', 'thermometer-3' => 'thermometer-three-quarters', 'thermometer-4' => 'thermometer-full', 'thumb-tack' => 'thumbtack', 'thumbs-o-down' => 'thumbs-down', 'thumbs-o-up' => 'thumbs-up', 'ticket' => 'ticket-alt', 'times-circle-o' => 'times-circle', 'times-rectangle' => 'window-close', 'times-rectangle-o' => 'window-close', 'toggle-down' => 'caret-square-down', 'toggle-left' => 'caret-square-left', 'toggle-right' => 'caret-square-right', 'toggle-up' => 'caret-square-up', 'trash' => 'trash-alt', 'trash-o' => 'trash-alt', 'try' => 'lira-sign', 'turkish-lira' => 'lira-sign', 'unsorted' => 'sort', 'usd' => 'dollar-sign', 'user-circle-o' => 'user-circle', 'user-o' => 'user', 'vcard' => 'address-card', 'vcard-o' => 'address-card', 'video-camera' => 'video', 'vimeo' => 'vimeo-v', 'volume-control-phone' => 'phone-volume', 'warning' => 'exclamation-triangle', 'wechat' => 'weixin', 'wheelchair-alt' => 'accessible-icon', 'window-close-o' => 'window-close', 'won' => 'won-sign', 'y-combinator-square' => 'hacker-news', 'yc' => 'y-combinator', 'yc-square' => 'hacker-news', 'yen' => 'yen-sign', 'youtube-play' => 'youtube',
        );
        // Return new if found
        if(isset($old_to_new[$icon])){
            return $old_to_new[$icon];
        }    
        // Otherwise just return original
        return $icon;
    }

    /**
     * Filter if() statements
     */
    public static function filter_if_statements($html=''){
        // If does not contain 'endif;' we can just return the `$html` without doing anything
        if(!strpos($html, 'endif;')) return $html;
        $re = '/\s*[\'|"]?(.*?)[\'|"]?\s*(==|!=|>=|<=|>|<)\s*[\'|"]?(.*?)[\'|"]?\s*$/';
        $array = str_split($html);
        $if_index = 0;
        $skip_up_to = 0;
        $capture_elseifcontent = false;
        $capture_conditions = false;
        $capture_suffix = false;
        $statements = array();
        $prefix = '';
        $first_if_found = false;
        $depth = 0;
        foreach($array as $k => $v){
            if($skip_up_to!=0 && $skip_up_to > $k){
                continue;
            }
            if( !self::if_match($array, $k) && $first_if_found==false ) {
                $prefix .= $v;
            }else{
                $first_if_found = true;
                if($capture_conditions){
                    if( (isset($array[$k]) && $array[$k]===')') && 
                        (isset($array[$k+1]) && $array[$k+1]===':') ) {
                        $capture_elseifcontent = false;
                        $capture_suffix = false;
                        $capture_conditions = false;
                        $skip_up_to = $k+2;
                        continue;
                    }
                    if(!isset($statements[$if_index]['conditions'])){
                        $statements[$if_index]['conditions'] = '';
                    }
                    $statements[$if_index]['conditions'] .= $v;
                    continue;
                }
                if($depth==0){
                    if(self::if_match($array, $k)){
                        $if_index++;
                        $depth++;
                        $capture_elseifcontent = false;
                        $capture_suffix = false;
                        $capture_conditions = true;
                        $skip_up_to = $k+3;
                        continue;
                    }
                }else{
                    if(self::if_match($array, $k)){
                        $depth++;
                    }
                }
                if( (isset($array[$k]) && $array[$k]==='e') && 
                    (isset($array[$k+1]) && $array[$k+1]==='n') && 
                    (isset($array[$k+2]) && $array[$k+2]==='d') && 
                    (isset($array[$k+3]) && $array[$k+3]==='i') && 
                    (isset($array[$k+4]) && $array[$k+4]==='f') && 
                    (isset($array[$k+5]) && $array[$k+5]===';') ) {
                    $depth--;
                    if($depth==0){
                        $capture_elseifcontent = false;
                        $capture_conditions = false;
                        $capture_suffix = true;
                        $skip_up_to = $k+6;
                        continue;
                    }
                }
                if($depth==1){
                    if( (isset($array[$k]) && $array[$k]==='e') && 
                        (isset($array[$k+1]) && $array[$k+1]==='l') &&
                        (isset($array[$k+2]) && $array[$k+2]==='s') &&
                        (isset($array[$k+3]) && $array[$k+3]==='e') &&
                        (isset($array[$k+4]) && $array[$k+4]==='i') &&
                        (isset($array[$k+5]) && $array[$k+5]==='f') &&
                        (isset($array[$k+6]) && $array[$k+6]===':') ) {
                        $capture_elseifcontent = true;
                        $capture_suffix = false;
                        $capture_conditions = false;
                        $skip_up_to = $k+7;
                        continue;
                    }
                }
                if($depth==0){
                    if($capture_suffix){
                        if(!isset($statements[$if_index]['suffix'])) $statements[$if_index]['suffix'] = ''; 
                        $statements[$if_index]['suffix'] .= $v;
                        continue;
                    }
                }
                if($depth>=1){
                    if($capture_elseifcontent){
                        if(!isset($statements[$if_index]['elseif_content'])) $statements[$if_index]['elseif_content'] = '';
                        $statements[$if_index]['elseif_content'] .= $v;
                        continue;
                    }
                }
                if($depth>=1){
                    // Capture everything that is inside the statement
                    if(!isset($statements[$if_index]['inner_content'])) $statements[$if_index]['inner_content'] = '';
                    $statements[$if_index]['inner_content'] .= $v;
                    continue;
                }
            }
        }
        $result = '';
        foreach($statements as $k => $v){
            $show_counter = 0;
            $conditions = explode('&&', $v['conditions']);
            $method = '&&';
            if(count($conditions)==1){
                $conditions = explode('||', $v['conditions']);
                $method = '||';
            }
            foreach($conditions as $ck => $cv){
                preg_match($re, $cv, $matches);
                $v1 = $matches[1];
                $operator = $matches[2];
                $v2 = $matches[3];
                $show = false;
                if($operator==='==' && $v1==$v2) $show = true;
                if($operator==='!=' && $v1!=$v2) $show = true;
                if($operator==='>=' && $v1>=$v2) $show = true;
                if($operator==='<=' && $v1<=$v2) $show = true;
                if($operator==='>' && $v1>$v2) $show = true;
                if($operator==='<' && $v1<$v2) $show = true;
                if($show){
                    $show_counter++;
                }
            }
            if($method=='||' && $show_counter>0){
                $result .= SUPER_Common::filter_if_statements($v['inner_content']);
            }else{
                if(count($conditions)===$show_counter){
                    $result .= SUPER_Common::filter_if_statements($v['inner_content']);
                }else{
                    if(!empty($v['elseif_content'])) $result .= SUPER_Common::filter_if_statements($v['elseif_content']);
                }
            }
            if(!empty($v['suffix'])) $result .= $v['suffix'];
        }
        return $prefix.$result;
    }


    /**
     * Find if() match
     */
    public static function if_match($array=array(), $k=0){
        if( (isset($array[$k]) && $array[$k]==='i') && 
            (isset($array[$k+1]) && $array[$k+1]==='f') && 
            (isset($array[$k+2]) && $array[$k+2]==='(') ) {
            return true;
        }
        return false;       
    }


    /**
     * Get data-fields attribute based on value that contains tags e.g: {option;2}_{color;3} would convert to [option][color]
     */
    public static function get_data_fields_attribute($field_names=array(), $str, $bwc=false){
        if($bwc){
            // If field name doesn't contain any curly braces, then append and prepend them and continue;
            if ( strpos( $str, '{') === false ) {
                $str = '{'.$str.'}';   
            } 
        }
        $re = '/\{(.*?)\}/';
        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
        foreach($matches as $mk => $mv){
            $values = explode(";", $mv[1]);
            $field_names[$values[0]] = $values[0];
        }
        return $field_names;
    }

    /**
     * Get global settings
     *
     * @since 4.6.0
     */
    public static function get_global_settings(){
        if(!isset(SUPER_Forms()->global_settings)){
            SUPER_Forms()->global_settings = get_option( 'super_settings', array() );
            if(is_array(SUPER_Forms()->global_settings)){
                SUPER_Forms()->global_settings = array_map('stripslashes_deep', SUPER_Forms()->global_settings);
            }else{
                SUPER_Forms()->global_settings = stripslashes(SUPER_Forms()->global_settings);
            }
        }
        return SUPER_Forms()->global_settings;
    }

    /**
     * Get form settings
     *
     * @since 3.8.0
     */
    public static function get_form_settings($form_id) {
        if( !class_exists( 'SUPER_Settings' ) )  require_once( 'class-settings.php' ); 
        $form_settings = get_post_meta( absint($form_id), '_super_form_settings', true );
        if(!$form_settings) $form_settings = array();
        $global_settings = self::get_global_settings();
        $defaults = SUPER_Settings::get_defaults($global_settings, 0);
        $global_settings = array_merge( $defaults, $global_settings );
        $settings = array_merge( $global_settings, $form_settings );
        return apply_filters( 'super_form_settings_filter', $settings, array( 'id'=>$form_id ) );
    }


    /**
     * Generate array with default values for each settings of a specific element 
     *
     * @since 3.8.0
     */
    public static function generate_array_default_element_settings($shortcodes=false, $group, $tag) {
        $defaults = array();
        if($shortcodes==false) $shortcodes = SUPER_Shortcodes::shortcodes();
        foreach($shortcodes[$group]['shortcodes'][$tag]['atts'] as $k => $v){
            if( !empty( $v['fields'] ) ) {
                foreach( $v['fields'] as $fk => $fv ) {
                    if( (isset($fv['type'])) && ($fv['type']=='multicolor') ) {
                        foreach( $fv['colors'] as $ck => $cv ) {
                            if( isset($fv['default']) ) $defaults[$ck] = $cv['default'];
                        }
                    }else{
                        if( isset($fv['default']) ) $defaults[$fk] = $fv['default'];
                    }
                }
            }
        }
        return $defaults;
    }


    /**
     * Get the entry data based on a WC order ID
     *
     * @since 3.8.0
     */
    public static function get_entry_data_by_wc_order_id($order_id, $skip){
        global $wpdb;
        $contact_entry_id = $wpdb->get_var("
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_super_contact_entry_wc_order_id' 
            AND meta_value = '" . absint($order_id) . "'"
        );
        $data = get_post_meta( absint($contact_entry_id), '_super_contact_entry_data', true );
        if(!empty($data)){
            unset($data['hidden_form_id']);
            if(!empty($skip)){
                $skip_fields = explode( "|", $skip );
                foreach($skip_fields as $field_name){
                    if( isset($data[$field_name]) ) {
                        unset($data[$field_name]);
                    }
                }
            }
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => $contact_entry_id,
                'type' => 'entry_id'
            );
        }
        return $data;
    }


    /**
     * Get the default value of a specific element setting
     *
     * @since 3.8.0
     */
    public static function get_default_element_setting_value($shortcodes=false, $group, $tag, $tab, $name) {
        if($shortcodes==false) $shortcodes = SUPER_Shortcodes::shortcodes();
        if(isset($shortcodes[$group]['shortcodes'][$tag]['atts'][$tab]['fields'][$name]['default'])){
            return $shortcodes[$group]['shortcodes'][$tag]['atts'][$tab]['fields'][$name]['default'];
        }else{
            return '';
        }
    }


    /**
     * Get the absolute default field setting value based on group ($parent) and field tag ($name)
     *
     * @since 3.4.0
     */
    public static function get_default_setting_value( $parent, $name ) {
        $fields = SUPER_Settings::fields();
        return $fields[$parent]['fields'][$name]['default'];
    }


    /**
     * Return the dynamic functions (used to hook into javascript)
     *
     * @since 1.1.3
     */
    public static function get_dynamic_functions() {
        return apply_filters(
            'super_common_js_dynamic_functions_filter', 
            array(
                // @since 1.0.0
                'before_validating_form_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_validating_form_hook' => array(),

                'after_initializing_forms_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_dropdown_change_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'calculate_distance' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_field_change_blur_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_radio_change_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),
                'after_checkbox_change_hook' => array(
                    array( 'name' => 'conditional_logic' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'init_replace_html_tags' ),
                    array( 'name' => 'init_replace_post_url_tags' )
                ),

                // @since 1.2.8
                'after_email_send_hook' => array(),

                // @since 1.3
                'after_responsive_form_hook' => array(),
                'after_form_data_collected_hook' => array(),
                'after_duplicate_column_fields_hook' => array(),
 
                // @since 1.9
                'before_submit_button_click_hook' => array(),
                'after_preview_loaded_hook' => array(),

                // @since 2.0.0
                'after_form_cleared_hook' => array(),
                
                // @since 2.1.0
                'before_scrolling_to_error_hook' => array(),
                'before_scrolling_to_message_hook' => array(),
                
                // @since 2.4.0
                'after_duplicating_column_hook' => array(),

                // @since 3.3.0
                'after_appending_duplicated_column_hook' => array(),

                // @since 4.7.0
                'before_submit_hook' => array(),

                // @since 4.9.0
                'after_init_common_fields' => array(
                    array( 'name' => 'init_dropdowns' ),
                    array( 'name' => 'init_distance_calculators' ),
                    array( 'name' => 'init_color_pickers' ),
                    array( 'name' => 'init_carouseljs' ),
                    array( 'name' => 'init_tooltips' ),
                    array( 'name' => 'init_datepicker' ),
                    array( 'name' => 'init_masked_input' ),
                    array( 'name' => 'init_currency_input' ),
                    array( 'name' => 'init_colorpicker' ),
                    array( 'name' => 'init_slider_field' ),
                    array( 'name' => 'init_button_colors' ),
                    array( 'name' => 'init_text_editors' ),
                    array( 'name' => 'init_fileupload_fields' ),
                    array( 'name' => 'google_maps_init' ),
                    array( 'name' => 'set_keyword_tags_width' ),
                    array( 'name' => 'rating' ),
                    array( 'name' => 'init_signature' ) // This should actually be called by the signature add-on, but it has been like this since the start.
                ),
            )
        );
    }


    /**
     * Returns error and success messages
     *
     *  @param  boolean  $error
     *  @param  varchar  $msg
     *  @param  varchar  $redirect
     *  @param  array    $fields
     *  @param  boolean  $display  @since 3.4.0
     *  @param  boolean  $loading  @since 3.4.0
     *  @param  boolean  $json  @since 4.8.0 
     *  @param  boolean  $response_data  @since 4.9.0 
     *
     * @since 1.0.6
     * @deprecated 4.9.0 Use output_message()
     * @see output_message()
     */
    public static function output_error( $error=true, $msg='Missing required parameter $msg!', $redirect=null, $fields=array(), $display=true, $loading=false, $json=true, $response_data=array() ) {        
        _deprecated_function( __FUNCTION__, '4.9.0', 'output_message()' );
        self::output_message( $error, $msg, $redirect, $fields, $display, $loading, $json, $response_data );
    }
    public static function output_message( $error=true, $msg='Missing required parameter $msg!', $redirect=null, $fields=array(), $display=true, $loading=false, $json=true, $response_data=array() ) {        
        if($json!=true){
            // We will want to return the error/success message HTML instantly
            echo $msg;
        }else{
            // We will want to return a JSON string with the error/success message data
            $result = array(
                'error' => $error,
                'msg' => $msg,
            );
            if( $redirect!=null ) {
                $result['redirect']= $redirect;
            }
            $result['fields'] = $fields;
            $result['display'] = $display; // @since 3.4.0 - option to hide the message
            $result['loading'] = $loading; // @since 3.4.0 - option to keep the form at a loading state, when enabled, it will keep submit button at loading state and will not hide the form and prevents to scroll to top of page
            $result['response_data'] = $response_data; // @since 4.9.0 - holds the contact entry ID (if one was created, and the form ID), might be used in the future for other data.
            echo json_encode( $result );
        }
        die();
    }

    /**
     * Output the form elements on the backend (create form page) to allow to edit the elements
     *
     *  @param  integer  $id
     *
     * @since 1.0.0
     */
    public static function generate_backend_elements( $id=null, $shortcodes=null, $elements=null ) {
        
        // @since 1.0.6 - Make sure that we have all settings even if this form hasn't saved it yet when new settings where added by a add-on
        require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
        $settings = SUPER_Common::get_form_settings($id);
        $html = '';
        if( $elements!=false ) {
            if( $elements==null ) {
                $elements = get_post_meta( $id, '_super_elements', true );
            }
            // If elements are saved as JSON in database, convert to array
            if( !is_array( $elements) ) {
                $shortcode = json_decode(stripslashes($elements), true);
                if( $shortcode==null ) {
                    $shortcode = json_decode($elements, true);
                }
                // @since 4.3.0 - required to make sure any backslashes used in custom regex is escaped properly
                $elements = wp_slash($shortcode);
            }
            if( is_array( $elements) ) {
                foreach( $elements as $k => $v ) {
                    if( empty($v['data']) ) $v['data'] = null;
                    if( empty($v['inner']) ) $v['inner'] = null;
                    $html .= SUPER_Shortcodes::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
                }
            }         
        }
        
        return $html;
    }

    /**
     * Return list with all posts filtered by specific post type
     *
     *  @param  string  $type
     *
     * @since 1.0.0
     */
    public static function list_posts_by_type_array( $type ) {
        $list = array();
        $list[''] = '- Select a '.$type.' -';
        $args = array();
        $args['sort_order'] = 'ASC';
        $args['sort_column'] = 'post_title';
        $args['post_type'] = $type;
        $args['post_status'] = 'publish';
        $pages = get_pages($args); 
        if($pages!=false){
            foreach($pages as $page){
                $list[$page->ID] = $page->post_title;
            }
        }
        return $list;
    }
    
    /**
     * Check if specific time can be found between a time range
     *
     * @since 1.0.0
    */
    public static function check_time($t1, $t2, $tn, $opposite=false) {
        $t1 = +str_replace(":", "", $t1);
        $t2 = +str_replace(":", "", $t2);
        $tn = +str_replace(":", "", $tn);       
        if ($t2 >= $t1) {
            if($opposite==true){
                return $t1 < $tn && $tn < $t2;
            }else{
                return $t1 <= $tn && $tn < $t2;
            }
        } else {
            if($opposite==true){
                return ! ($t2 < $tn && $tn < $t1);
            }else{
                return ! ($t2 <= $tn && $tn < $t1);
            }
        }
    }
 

    /**
     * Generate random code
     *
     * @since 2.2.0
    */
    public static function generate_random_code($length, $characters, $prefix, $invoice, $invoice_padding, $suffix, $uppercase, $lowercase) {
        $char  = '';
        if( ($characters=='1') || ($characters=='2') || ($characters=='3') ) {
            $char .= '0123456789';
        }
        if( ($characters=='1') || ($characters=='2') || ($characters=='4') ) {
            if($uppercase=='true') $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            if($lowercase=='true') $char .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if($characters=='2') {
            $char .= '!@#$%^&*()';
        }
        $charactersLength = strlen($char);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $char[rand(0, $charactersLength - 1)];
        }

        // @since 2.8.0 - invoice numbers
        $code_without_invoice_number = $prefix.$code.$suffix;
        if( $invoice=='true' ) {
            if ( ctype_digit( (string)$invoice_padding ) ) {
                $number = get_option('_super_form_invoice_number', 0);
                $number = $number+1;
                $code .= sprintf('%0'.$invoice_padding.'d', $number );
            }
        }
        $code = $prefix.$code.$suffix;

        // Now we have generated the code check if it already exists
        global $wpdb;
        $table = $wpdb->prefix . 'postmeta';
        $transient = '_super_contact_entry_code-' . $code_without_invoice_number;
        if( get_transient($transient)!=false) {
            return $code;
        }
        if( (get_transient($transient)==false) && (get_option($transient)==false) ) {
            
            // For backwards compatiblity we will also check for old generated codes
            $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE meta_key = '_super_contact_entry_code' AND meta_value = '$code_without_invoice_number'");
            if( $exists==0 ) {
                // Set expiration to 12 hours
                $result = set_transient( $transient, $code_without_invoice_number, 12 * HOUR_IN_SECONDS );
                return $code;
            }
        }
        return self::generate_random_code($length, $characters, $prefix, $invoice, $invoice_padding, $suffix, $uppercase, $lowercase);
    }


    /**
     * Generate random folder number
     *
     * @since 1.0.0
    */
    public static function generate_random_folder( $folder ) {
        $number = rand( 100000000, 999999999 );
        $new_folder = $folder . '/' . $number;
        if( file_exists( $new_folder ) ) {
            self::generate_random_folder( $folder );
        }else{
            if( !file_exists( $new_folder ) ) {
                mkdir( $new_folder, 0755, true );
                return $new_folder;
            }else{
                return $new_folder;
            }
        }
    }


    /**
     * Get the IP address of the user that submitted the form
     *
     * @since 1.0.0
    */
    public static function real_ip() {
        foreach (array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ) as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }


    /**
     * Decodes the values of the submitted data
     *
     * @since 1.0.0
    */
    public static function decode_textarea( $value ) {
        if( empty( $value ) ) return $value;
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            return nl2br( urldecode( stripslashes( $value ) ) );
        }
    }
    public static function decode( $value ) {
        if( empty( $value ) ) return $value;
        if( is_string( $value ) ) {
            // @since 3.9.0 - do not decode base64 images (signature add-on)
            if ( strpos( $value, 'data:image/png;base64,') !== false ) {
                return $value;
            }else{
                return urldecode( strip_tags( stripslashes( $value ), '<br>' ) );
            }
        }
        // @since 1.4 - also return integers
        return absint( $value );
    }
    public static function decode_email_header( $value ) {
        if( empty( $value ) ) return $value;
        if( ( !empty( $value ) ) && ( is_string ( $value ) ) ) {
            $emails = array();
            $value = explode( ",", $value );
            foreach($value as $v){
                if(sanitize_email( $v )){
                    $emails[] = sanitize_email( $v );
                }
            }
            return implode(',', $emails);
        }
    }


    /**
     * Create an array with tags that can be used in emails, this function also replaced tags when $value and $data are set
     *
     * @since 1.0.6
    */
    public static function email_tags( $value=null, $data=null, $settings=null, $user=null, $skip=true ) {
        if( ($value==='') && ($skip==true) ) return '';
        $current_author = null;
        $current_user = wp_get_current_user();

        // @since 4.9.402 - check if switching from language, if so grab initial tag values from session
        if( (isset($_POST['action'])) && ($_POST['action']=='super_language_switcher') ) {
            // Try to get data from session
            $tags = SUPER_Forms()->session->get( 'super_tags_values' );
        }else{
            // @since 4.0.0 - retrieve author id if on profile page
            // First check if we are on the author profile page, and see if we can find author based on slug
            //get_current_user_id()
            $page_url = ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $author_name = basename($page_url);
            $current_author = ( isset($_GET['author']) ? get_user_by('id', absint($_GET['author'])) : get_user_by('slug', $author_name) );
            if( $current_author ) {
                // This is an author profile page
                $author_id = $current_author->ID;
                $user_info = get_userdata($author_id);
                if($user_info!=false){
                    $author_email = $user_info->user_email;
                }
            }
            global $post;
            if( !isset( $post ) ) {
                if( isset( $_REQUEST['post_id'] ) ) {
                    $post_title = get_the_title( absint( $_REQUEST['post_id'] ) );
                    $post_id = (string)$_REQUEST['post_id'];
                    if ( class_exists( 'WooCommerce' ) ) {
                        $product = wc_get_product( $post_id );
                        if($product){
                            $product_regular_price = $product->get_regular_price();
                            $product_sale_price = $product->get_sale_price();
                            $product_price = $product->get_price();
                        }
                    }
                }
            }else{
                $post_title = get_the_title($post->ID);
                $post_permalink = get_permalink($post->ID);
                $post_id = (string)$post->ID;
                if(!isset($author_id)) $author_id = $post->post_author;
                $user_info = get_userdata($author_id);
                $current_author = $user_info;
                if($user_info!=false){
                    if(!isset($author_email)) $author_email = $user_info->user_email;
                }
                if ( class_exists( 'WooCommerce' ) ) {
                    $product = wc_get_product( $post_id );
                    if($product){
                        $product_regular_price = $product->get_regular_price();
                        $product_sale_price = $product->get_sale_price();
                        $product_price = $product->get_price();
                   }
               }
            }
            
            // Make sure all variables are set
            if(!isset($post_id)) $post_id = '';
            if(!isset($post_title)) $post_title = '';
            if(!isset($post_permalink)) $post_permalink = '';
            if(!isset($author_id)) $author_id = '';
            if(!isset($author_email)) $author_email = '';
    
            if(!isset($product_regular_price)) $product_regular_price = 0;
            if(!isset($product_sale_price)) $product_sale_price = 0;
            if(!isset($product_price)) $product_price = 0;
            
            $user_roles = implode(',', $current_user->roles); // @since 3.2.0
    
            // @since 3.3.0 - save http_referrer into a session
            $http_referrer = SUPER_Forms()->session->get( 'super_server_http_referrer' );
            if( $http_referrer==false ) {
                $http_referrer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
            }
            SUPER_Forms()->session->set( 'super_server_http_referrer', $http_referrer );
            
            // @since 3.4.0 - Retrieve latest contact entry based on form ID
            // @since 3.4.0 - retrieve the lock count
            $last_entry_id = 0;
            $last_entry_status = '';
            $user_last_entry_status = '';
            $form_submission_count = '';
            if(!isset($settings['id'])) {
                $form_id = 0;
            }else{
                $form_id = $settings['id'];
            }
            if($form_id!=0){
                global $wpdb;
                $table = $wpdb->prefix . 'posts';
                $entry = $wpdb->get_results("
                SELECT  ID 
                FROM    $table 
                WHERE   post_parent = $form_id AND
                        post_status IN ('publish','super_unread','super_read') AND 
                        post_type = 'super_contact_entry'
                ORDER BY ID DESC
                LIMIT 1");
                if( isset($entry[0])) {
                    $last_entry_id = absint($entry[0]->ID);
                    $last_entry_status = get_post_meta( $entry[0]->ID, '_super_contact_entry_status', true );
                }
                $form_submission_count = absint(get_post_meta( $form_id, '_super_submission_count', true ));
            }
    
            // @since 4.7.7     - Get last entry status bas on currently logged in user
            $user_last_entry_id = 0;
            $user_last_entry_status = '';
            if( (isset($current_user)) && ($current_user->ID>0) ) {
                global $wpdb;
                $entry = $wpdb->get_results(
                    $wpdb->prepare("
                        SELECT  ID
                        FROM    $wpdb->posts
                        WHERE   post_parent = %d AND 
                                post_author = %d AND
                                post_status IN ('publish','super_unread','super_read') AND 
                                post_type = 'super_contact_entry'
                        ORDER BY ID DESC
                        LIMIT 1",
                        array( $form_id, $current_user->ID )
                    )
                );
                if( isset($entry[0])) {
                    $user_last_entry_id = absint($entry[0]->ID);
                    $user_last_entry_status = get_post_meta( $entry[0]->ID, '_super_contact_entry_status', true );
                }
            }
    
            $_SERVER_HTTP_REFERER = '';
            if( isset($_SERVER['HTTP_REFERER']) ) {
                $_SERVER_HTTP_REFERER = $_SERVER['HTTP_REFERER'];
            }
    
            $tags = array(
                'field_*****' => array(
                    esc_html__( 'Any field value submitted by the user', 'super-forms' ),
                    ''
                ),
                'field_label_*****' => array(
                    esc_html__( 'Any field value submitted by the user', 'super-forms' ),
                    ''
                ),
    
                // @since 4.4.0 - option to retrieve setting values from the form settings
                'form_setting_*****' => array(
                    esc_html__( 'Any setting value used for the form', 'super-forms' ),
                    ''
                ),
                
                'option_admin_email' => array(
                    esc_html__( 'E-mail address of blog administrator', 'super-forms' ),
                    get_option('admin_email')
                ),
                'option_blogname' => array(
                    esc_html__( 'Weblog title; set in General Options', 'super-forms' ),
                    get_option('blogname')
                ),
                'option_blogdescription' => array(
                    esc_html__( 'Tagline for your blog; set in General Options', 'super-forms' ),
                    get_option('blogdescription')
                ),
                'option_blog_charset' => array(
                    esc_html__( 'Blog Charset', 'super-forms' ),
                    get_option('blog_charset')
                ),
                'option_date_format' => array(
                    esc_html__( 'Date Format', 'super-forms' ),
                    get_option('date_format')
                ),            
                'option_default_category' => array(
                    esc_html__( 'Default post category; set in Writing Options', 'super-forms' ),
                    get_option('default_category')
                ),
                'option_home' => array(
                    esc_html__( 'The blog\'s home web address; set in General Options', 'super-forms' ),
                    home_url()
                ),
                'option_siteurl' => array(
                    esc_html__( 'WordPress web address; set in General Options', 'super-forms' ),
                    get_option('siteurl')
                ),
                'option_template' => array(
                    esc_html__( 'The current theme\'s name; set in Presentation', 'super-forms' ),
                    get_option('template')
                ),
                'option_start_of_week' => array(
                    esc_html__( 'Start of the week', 'super-forms' ),
                    get_option('start_of_week')
                ),
                'option_upload_path' => array(
                    esc_html__( 'Default upload location; set in Miscellaneous Options', 'super-forms' ),
                    get_option('upload_path')
                ),
                'option_posts_per_page' => array(
                    esc_html__( 'Posts per page', 'super-forms' ),
                    get_option('posts_per_page')
                ),
                'option_posts_per_rss' => array(
                    esc_html__( 'Posts per RSS feed', 'super-forms' ),
                    get_option('posts_per_rss')
                ),
                'real_ip' => array(
                    esc_html__( 'Retrieves the submitter\'s IP address', 'super-forms' ),
                    self::real_ip()
                ),
                'loop_label' => array(
                    esc_html__( 'Retrieves the field label for the field loop {loop_fields}', 'super-forms' ),
                ),
                'loop_value' => array(
                    esc_html__( 'Retrieves the field value for the field loop {loop_fields}', 'super-forms' ),
                ),
                'loop_fields' => array(
                    esc_html__( 'Retrieves the loop anywhere in your email', 'super-forms' ),
                ),
                'post_title' => array(
                    esc_html__( 'Retrieves the current page or post title', 'super-forms' ),
                    $post_title
                ),
                'post_id' => array(
                    esc_html__( 'Retrieves the current page or post ID', 'super-forms' ),
                    $post_id
                ),
    
                // @since 4.0.0 - return profile author ID and E-mail with tag
                'author_id' => array(
                    esc_html__( 'Retrieves the current author ID', 'super-forms' ),
                    $author_id
                ),
                'author_email' => array(
                    esc_html__( 'Retrieves the current author email', 'super-forms' ),
                    $author_email
                ),
    
                // @since 2.9.0 - return post author ID and E-mail with tag
                'post_author_id' => array(
                    esc_html__( 'Retrieves the current page or post author ID', 'super-forms' ),
                    $author_id
                ),
                'post_author_email' => array(
                    esc_html__( 'Retrieves the current page or post author email', 'super-forms' ),
                    $author_email
                ),
    
                // @since 3.0.0 - return post URL (permalink) with tag
                'post_permalink' => array(
                    esc_html__( 'Retrieves the current page URL', 'super-forms' ),
                    $post_permalink
                ),
    
    
                // @since 1.1.6
                'user_login' => array(
                    esc_html__( 'Retrieves the current logged in user login (username)', 'super-forms' ),
                    $current_user->user_login
                ),
                'user_email' => array(
                    esc_html__( 'Retrieves the current logged in user email', 'super-forms' ),
                    $current_user->user_email
                ),
                'user_firstname' => array(
                    esc_html__( 'Retrieves the current logged in user first name', 'super-forms' ),
                    $current_user->user_firstname
                ),
                'user_lastname' => array(
                    esc_html__( 'Retrieves the current logged in user last name', 'super-forms' ),
                    $current_user->user_lastname
                ),
                'user_display' => array(
                    esc_html__( 'Retrieves the current logged in user display name', 'super-forms' ),
                    $current_user->display_name
                ),
                'user_id' => array(
                    esc_html__( 'Retrieves the current logged in user ID', 'super-forms' ),
                    $current_user->ID
                ),
                'user_roles' => array(
                    esc_html__( 'Retrieves the current logged in user roles', 'super-forms' ),
                    $user_roles
                ),
    
                // @since 3.3.0 - tags to retrieve http_referrer (users previous location), and timestamp and date values
                'server_http_referrer' => array(
                    esc_html__( 'Retrieves the location where user came from (if exists any) before loading the page with the form', 'super-forms' ),
                    $_SERVER_HTTP_REFERER
                ),
                'server_http_referrer_session' => array(
                    esc_html__( 'Retrieves the location where user came from from a session (if exists any) before loading the page with the form', 'super-forms' ),
                    $http_referrer
                ),
                'server_timestamp_gmt' => array(
                    esc_html__( 'Retrieves the server timestamp (UTC/GMT)', 'super-forms' ),
                    strtotime(date_i18n('Y-m-d H:i:s', false, 'gmt'))
                ),
                'server_day_gmt' => array(
                    esc_html__( 'Retrieves the current day of the month (UTC/GMT)', 'super-forms' ),
                    date_i18n('d', false, 'gmt')
                ),
                'server_month_gmt' => array(
                    esc_html__( 'Retrieves the current month of the year (UTC/GMT)', 'super-forms' ),
                    date_i18n('m', false, 'gmt')
                ),
                'server_year_gmt' => array(
                    esc_html__( 'Retrieves the current year of time (UTC/GMT)', 'super-forms' ),
                    date_i18n('Y', false, 'gmt')
                ),
                'server_hour_gmt' => array(
                    esc_html__( 'Retrieves the current hour of the day (UTC/GMT)', 'super-forms' ),
                    date_i18n('H', false, 'gmt')
                ),
                'server_minute_gmt' => array(
                    esc_html__( 'Retrieves the current minute of the hour (UTC/GMT)', 'super-forms' ),
                    date_i18n('i', false, 'gmt')
                ),
                'server_seconds_gmt' => array(
                    esc_html__( 'Retrieves the current second of the minute (UTC/GMT)', 'super-forms' ),
                    date_i18n('s', false, 'gmt')
                ),
    
                // @since 3.4.0 - tags to return local times
                'server_timestamp' => array(
                    esc_html__( 'Retrieves the server timestamp (Local time)', 'super-forms' ),
                    strtotime(date_i18n('Y-m-d H:i:s', false, false))
                ),
                'server_day' => array(
                    esc_html__( 'Retrieves the current day of the month (Local time)', 'super-forms' ),
                    date_i18n('d', false, false)
                ),
                'server_month' => array(
                    esc_html__( 'Retrieves the current month of the year (Local time)', 'super-forms' ),
                    date_i18n('m', false, false)
                ),
                'server_year' => array(
                    esc_html__( 'Retrieves the current year of time (Local time)', 'super-forms' ),
                    date_i18n('Y', false, false)
                ),
                'server_hour' => array(
                    esc_html__( 'Retrieves the current hour of the day (Local time)', 'super-forms' ),
                    date_i18n('H', false, false)
                ),
                'server_minute' => array(
                    esc_html__( 'Retrieves the current minute of the hour (Local time)', 'super-forms' ),
                    date_i18n('i', false, false)
                ),
                'server_seconds' => array(
                    esc_html__( 'Retrieves the current second of the minute (Local time)', 'super-forms' ),
                    date_i18n('s', false, false)
                ),
    
                // @since 3.4.0 - retrieve the lock
                'submission_count' => array(
                    esc_html__( 'Retrieves the total submission count (if form locker is used)', 'super-forms' ),
                    $form_submission_count
                ),
    
                // @since 3.4.0 - retrieve the last entry status
                'last_entry_status' => array(
                    esc_html__( 'Retrieves the latest Contact Entry status', 'super-forms' ),
                    $last_entry_status
                ),
                // @since 4.7.7 - retrieve the last entry ID
                'last_entry_id' => array(
                    esc_html__( 'Retrieves the latest Contact Entry ID', 'super-forms' ),
                    $last_entry_id
                ),
                
                // @since 4.7.7 - retrieve last entry status and ID of the logged in user
                'user_last_entry_status' => array(
                    esc_html__( 'Retrieves the latest Contact Entry status of the logged in user', 'super-forms' ),
                    $user_last_entry_status
                ),
                'user_last_entry_id' => array(
                    esc_html__( 'Retrieves the latest Contact Entry ID of the logged in user', 'super-forms' ),
                    $user_last_entry_id
                ),
    
            );
            
            // Make sure to replace tags with correct user data
            if( $user!=null ) {
                $user_tags = array(
                    'user_id' => array(
                        esc_html__( 'User ID', 'super-forms' ),
                        $user->ID
                    ),
                    'user_login' => array(
                        esc_html__( 'User username', 'super-forms' ),
                        $user->user_login
                    ),
                    'display_name' => array(
                        esc_html__( 'User display name', 'super-forms' ),
                        $user->user_nicename
                    ),
                    'user_nicename' => array(
                        esc_html__( 'User nicename', 'super-forms' ),
                        $user->user_nicename
                    ),
                    'user_email' => array(
                        esc_html__( 'User email', 'super-forms' ),
                        $user->user_email
                    ),
                    'user_url' => array(
                        esc_html__( 'User URL (website)', 'super-forms' ),
                        $user->user_url
                    ),
                    'user_registered' => array(
                        esc_html__( 'User Registered (registration date)', 'super-forms' ),
                        $user->user_registered
                    )
                );
                $tags = array_merge( $tags, $user_tags );
            }
    
    
            // @since 3.6.0 - tags to retrieve cart information
            if ( class_exists( 'WooCommerce' ) ) {
                global $woocommerce;
                if($woocommerce->cart!=null){
                    $items = $woocommerce->cart->get_cart();
                    $cart_total = $woocommerce->cart->get_cart_total();
                    $cart_total_float = $woocommerce->cart->total;
                    $cart_items = '';
                    $cart_items_price = '';
                    foreach($items as $item => $values) { 
                        $product =  wc_get_product( $values['data']->get_id() ); 
                        $cart_items .= absint($values['quantity']) . 'x - ' . $product->get_title() . '<br />'; 
                        $cart_items_price .= absint($values['quantity']) . 'x - ' . $product->get_title() . ' (' . wc_price(get_post_meta($values['product_id'], '_price', true)) . ')<br />'; 
                    }
                }else{
                    $cart_total = 0;
                    $cart_total_float = 0;
                    $cart_items = '';
                    $cart_items_price = '';
                }
                $wc_tags = array(
                    'wc_cart_total' => array(
                        esc_html__( 'WC Cart Total', 'super-forms' ),
                        $cart_total
                    ),
                    'wc_cart_total_float' => array(
                        esc_html__( 'WC Cart Total (float format)', 'super-forms' ),
                        $cart_total_float
                    ),
                    'wc_cart_items' => array(
                        esc_html__( 'WC Cart Items', 'super-forms' ),
                        $cart_items
                    ),
                    'wc_cart_items_price' => array(
                        esc_html__( 'WC Cart Items + Price', 'super-forms' ),
                        $cart_items_price
                    ),
                    'product_regular_price' => array(
                        esc_html__( 'Product Regular Price', 'super-forms' ),
                        $product_regular_price
                    ),
                    'product_sale_price' => array(
                        esc_html__( 'Product Sale Price', 'super-forms' ),
                        $product_sale_price
                    ),
                    'product_price' => array(
                        esc_html__( 'Product Price', 'super-forms' ),
                        $product_price
                    )
    
                );
                $tags = array_merge( $tags, $wc_tags );
            }
            $tags = apply_filters( 'super_email_tags_filter', $tags );
            SUPER_Forms()->session->set( 'super_tags_values', $tags );
        }
        
        // Return the new value with tags replaced for data
        if( $value!=null ) {

            // First loop through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( isset( $v['name'] ) ) {
                        if( (isset($v['type'])) && ($v['type']=='text') ) {
                            $v['value'] = nl2br( $v['value'] );
                        }
                        if( isset( $v['timestamp'] ) ) {
                            $value = str_replace( '{' . $v['name'] . ';timestamp}', self::decode( $v['timestamp'] ), $value );
                        }
                        if( isset( $v['label'] ) ) {
                            $value = str_replace( '{field_label_' . $v['name'] . '}', self::decode( $v['label'] ), $value );
                        }
                        if( isset( $v['option_label'] ) ) {
                            if( !empty($v['replace_commas']) ) {
                                $v['option_label'] = str_replace( ',', $v['replace_commas'], $v['option_label'] );
                            }
                            $value = str_replace( '{' . $v['name'] . ';label}', self::decode( $v['option_label'] ), $value );
                        }
                        if( isset( $v['value'] ) ) {
                            if( !empty($v['replace_commas']) ) {
                                $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            }
                            $value = str_replace( '{field_' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                        }
                    }
                }
            }

            // Now loop again through all the data (submitted by the user)
            if( $data!=null ) {
                foreach( $data as $k => $v ) {
                    if( isset( $v['name'] ) ) {
                        if( isset( $v['value'] ) ) {
                            if( (isset($v['type'])) && ($v['type']=='text') ) {
                                $v['value'] = nl2br( $v['value'] );
                            }
                            if( !empty($v['replace_commas']) ) {
                                $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            }
                            $value = str_replace( '{' . $v['name'] . '}', self::decode( $v['value'] ), $value );
                        }
                    }
                }
            }

            // Now replace all the tags inside the value with the correct data
            foreach( $tags as $k => $v ) {
                if( isset( $v[1] ) ) {
                    $value = str_replace( '{'. $k .'}', self::decode( $v[1] ), $value );
                }
            }

            // @since 4.4.0 - Loop through form settings
            // After replacing the settings {tag} with data, make sure to once more replace any possible {tags} 
            // (but only once, so we will skip this next time)
            if( is_array( $settings ) ) {
                foreach( $settings as $k => $v ) {
                    $value = str_replace( '{form_setting_' . $k . '}', self::decode( $v ), $value, $count );
                    // After replacing the settings {tag} with data, make sure to once more replace any possible {tags}
                    // Only execute if replacing took place
                    if ($count > 0) {
                        $value = self::email_tags( $value, $data, $settings, $user, $skip );
                    }
                }
            }
            
            // @since 4.0.1 - Let's try to replace author meta data
            if( $current_author!=null ) {
                // We possibly are looking for custom author meta data
                if ( strpos( $value, '{author_meta') !== false ) {
                    $meta_key = str_replace('{author_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_user_meta( $current_author->ID, $meta_key, true ); 
                    if( $value=='' ) {
                        // Whenever no meta was found mostly we try to retrieve default values like user_login etc. (which is not meta data)
                        // first convert object to array then try retrieve the value by key
                        $value = $current_author->{$meta_key};
                    }
                    return $value;
                }
            }

            // @since 4.0.0 - Let's try to replace user meta data
            if( $current_user!=null ) {
                // We possibly are looking for custom user meta data
                if ( strpos( $value, '{user_meta') !== false ) {
                    $meta_key = str_replace('{user_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_user_meta( $current_user->ID, $meta_key, true ); 
                    return $value;
                }
            }

            // @since 4.0.0 - Let's try to replace custom post meta data
            if( isset( $post ) ) {
                // We possibly are looking for custom user meta data
                if ( strpos( $value, '{post_meta') !== false ) {
                    $meta_key = str_replace('{post_meta_', '', $value);
                    $meta_key = str_replace('}', '', $meta_key);
                    $value = get_post_meta( $post->ID, $meta_key, true ); 
                    return $value;
                }
            }
            
            // Let's try to retrieve product attributes
            if ( class_exists( 'WooCommerce' ) ) {
                if( isset( $post ) ) {
                    if ( strpos( $value, '{product_attributes_') !== false ) {
                        global $product;
                        $meta_key = str_replace('{product_attributes_', '', $value);
                        $meta_key = str_replace('}', '', $meta_key);
                        $value = $product->get_attribute( $meta_key );
                        return $value;
                    }
                }
            }

            // Now return the final output
            return $value;

        }
        return $tags;
    }

    /**
     * Remove directory and it's contents
     *
     * @since 1.1.8
    */
    public static function delete_dir($dir) {
        if ( (is_dir( $dir )) && (ABSPATH!=$dir) ) {
            if ( substr( $dir, strlen( $dir ) - 1, 1 ) != '/' ) {
                $dir .= '/';
            }
            $files = glob( $dir . '*', GLOB_MARK );
            foreach ( $files as $file ) {
                if ( is_dir( $file ) ) {
                    self::delete_dir( $file );
                } else {
                    unlink( $file );
                }
            }
            rmdir($dir);
        }
    }


    /**
     * Remove file
     *
     * @since 1.1.9
    */
    public static function delete_file($file) {
        if ( !is_dir( $file ) ) {
            unlink( $file );
        }
    }


    /**
     * Replaces the tags with the according user data
     *
     * @since 1.0.0
     * @deprecated since version 1.0.6
     *
     * public static function replace_tag( $value, $data )
    */


    /**
     * Function to send email over SMTP
     *
     * authSendEmail()
     *
     * @since 1.0.0
     * @deprecated since version 1.0.6
    */


    /**
     * Convert HEX color to RGB color format
     *
     * @since 1.3
    */
    public static function hex2rgb( $hex, $opacity=1 ) {
        $hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        $rgb = array($r, $g, $b, $opacity);
        return 'rgba(' . (implode(",", $rgb)) . ')'; // returns the rgb values separated by commas
        //return $rgb; // returns an array with the rgb values
    }

    /**
     * Adjust the brightness of any given color (used for our focus and hover colors)
     *
     * @since 1.0.0
    */
    public static function adjust_brightness( $hex, $steps ) {
        
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Format the hex color string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }

        // Get decimal values
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));

        // Adjust number of steps and keep it inside 0 to 255
        $r = max(0,min(255,$r + $steps));
        $g = max(0,min(255,$g + $steps));  
        $b = max(0,min(255,$b + $steps));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#'.$r_hex.$g_hex.$b_hex;
    }


    /**
     * Send emails
     *
     * @since 1.0.6
    */
    public static function email( $to, $from, $from_name, $custom_reply=false, $reply, $reply_name, $cc, $bcc, $subject, $body, $settings, $attachments=array(), $string_attachments=array() ) {

        $from = trim($from);
        $from_name = trim(preg_replace('/[\r\n]+/', '', $from_name)); //Strip breaks and trim
        $to = explode( ",", $to );

        $global_settings = SUPER_Common::get_global_settings();
        if( !isset( $global_settings['smtp_enabled'] ) ) {
            $global_settings['smtp_enabled'] = 'disabled';
        }
        if( $global_settings['smtp_enabled']=='disabled' ) {
            $wpmail_attachments = array();
            foreach( $attachments as $k => $v ) {
                $v = str_replace(content_url(), '', $v);
                $wpmail_attachments[] = WP_CONTENT_DIR . $v;
            }

            SUPER_Forms()->session->set( 'super_string_attachments', $string_attachments );

            $headers = array();
            if(!empty($settings['header_additional'])){
                $headers = array_filter( explode( "\n", $settings['header_additional'] ) );
            } 
            $headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"";
            
            // Set From: header
            if( empty( $from_name ) ) {
                $from_header = $from;
            }else{
                $from_header = $from_name . ' <' . $from . '>';
            }
            $headers[] = 'From: ' . $from_header;
            
            // Set Reply-To: header
            if( $custom_reply!=false ) {
                if( empty( $reply_name ) ) {
                    $reply_header = $reply;
                }else{
                    $reply_header = $reply_name . ' <' . $reply . '>';
                }
                $headers[] = 'Reply-To: ' . $reply_header;
            }else{
                $headers[] = 'Reply-To: ' . $from_header;
            }
            
            // Add CC
            if( !empty( $cc ) ) {
                $cc = explode( ",", $cc );
                foreach( $cc as $value ) {
                    $headers[] = 'Cc: ' . trim($value);
                }
            }
            // Add BCC
            if( !empty( $bcc ) ) {
                $bcc = explode( ",", $bcc );
                foreach( $bcc as $value ) {
                    $headers[] = 'Bcc: ' . trim($value);
                }
            }
            $result = wp_mail( $to, $subject, $body, $headers, $wpmail_attachments );
            $error = '';
            if($result==false){
                $error = 'Email could not be send through wp_mail()';
            }
            // Return
            return array( 'result'=>$result, 'error'=>$error, 'mail'=>null );
        }else{
            if ( !class_exists( 'PHPMailer' ) ) {
                require_once( 'phpmailer/class.phpmailer.php' );
                if( $global_settings['smtp_enabled']=='enabled' ) {
                    require_once( 'phpmailer/class.smtp.php' );
                }
            }
            $mail = new PHPMailer;

            // Set mailer to use SMTP
            $mail->isSMTP();

            // Specify main and backup SMTP servers
            $mail->Host = $global_settings['smtp_host'];
            
            // Enable SMTP authentication
            if( $global_settings['smtp_auth']=='enabled' ) {
                $mail->SMTPAuth = true;
            }

            // SMTP username
            $mail->Username = $global_settings['smtp_username'];

            // SMTP password
            $mail->Password = $global_settings['smtp_password'];  

            // Enable TLS encryption
            if( $global_settings['smtp_secure']!='' ) {
                $mail->SMTPSecure = $global_settings['smtp_secure']; 
            }

            // TCP port to connect to
            $mail->Port = $global_settings['smtp_port'];

            // Set Timeout
            $mail->Timeout = $global_settings['smtp_timeout'];

            // Set keep alive
            if( $global_settings['smtp_keep_alive']=='enabled' ) {
                $mail->SMTPKeepAlive = true;
            }

            // Set debug
            if( $global_settings['smtp_debug'] != 0 ) {
                $mail->SMTPDebug = $global_settings['smtp_debug'];
                $mail->Debugoutput = $global_settings['smtp_debug_output_mode'];

            }
        
            // Set From: header
            $mail->setFrom($from, $from_name);

            // Add a recipient
            foreach( $to as $value ) {
                $mail->addAddress($value); // Name 'Joe User' is optional
            }

            // Set Reply-To: header
            if( $custom_reply!=false ) {
                $mail->addReplyTo($reply, $reply_name);
            }else{
                $mail->addReplyTo($from, $from_name);
            }

            // Add CC
            if( !empty( $cc ) ) {
                $cc = explode( ",", $cc );
                foreach( $cc as $value ) {
                    $mail->addCC($value);
                }
            }

            // Add BCC
            if( !empty( $bcc ) ) {
                $bcc = explode( ",", $bcc );
                foreach( $bcc as $value ) {
                    $mail->addBCC($value);
                }
            }

            // Custom headers
            if( !empty( $settings['header_additional'] ) ) {
                $headers = explode( "\n", $settings['header_additional'] );
                foreach( $headers as $k => $v ) {
                    $mail->addCustomHeader($v);
                }
            }

            // Add attachment(s)
            foreach( $attachments as $k => $v ) {
                $v = str_replace(content_url(), '', $v);
                $mail->addAttachment( WP_CONTENT_DIR . $v );
            }

            // Add string attachment(s)
            foreach( $string_attachments as $v ) {
                if( $v['encoding']=='base64' && $v['type']=='image/png' ) {
                    $v['data'] = substr( $v['data'], strpos( $v['data'], "," ) );
                    $v['data'] = base64_decode( $v['data'] );
                }
                $mail->AddStringAttachment( $v['data'], $v['filename'], $v['encoding'], $v['type'] );
            }

            // Set email format to HTML
            if( !isset( $settings['header_content_type'] ) ) $settings['header_content_type'] = 'html';
            if( $settings['header_content_type'] == 'html' ) {
                $mail->isHTML(true);
            }else{
                $mail->isHTML(false);
            }

            // CharSet
            if( !isset( $settings['header_charset'] ) ) $settings['header_charset'] = 'UTF-8';
            $mail->CharSet = $settings['header_charset'];

            // Content-Type
            //$mail->ContentType = 'multipart/mixed';

            // Content-Transfer-Encoding
            // Options: "8bit", "7bit", "binary", "base64", and "quoted-printable".
            //$mail->Encoding = 'base64';

            // Subject
            $mail->Subject = $subject;

            // Body
            $mail->Body = $body;

            // Send the email
            $result = $mail->send();

            // Explicit call to smtpClose() when keep alive is enabled
            if( $mail->SMTPKeepAlive==true ) {
                $mail->SmtpClose();
            }
            
            // Return
            return array( 'result'=>$result, 'error'=>$mail->ErrorInfo, 'mail'=>$mail );

        }
    }
}
endif;
