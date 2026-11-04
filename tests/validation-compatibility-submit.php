<?php
/** Public submission-check compatibility seam; run by run-validation-compatibility.py. */
$bootstrap = getenv('SF_TEST_BOOTSTRAP');
if (!$bootstrap || !is_file($bootstrap)) { fwrite(STDERR,"SF_TEST_BOOTSTRAP must name an isolated real WordPress bootstrap\n"); exit(2); }
require $bootstrap;
foreach( array('SUPER_Forms'=>'super-forms.php','SUPER_Ajax'=>'includes/class-ajax.php','SUPER_Common'=>'includes/class-common.php','SUPER_Shortcodes'=>'includes/class-shortcodes.php') as $class=>$relative ) {
    if( realpath((new ReflectionClass($class))->getFileName())!==realpath(getenv('SF_PLUGIN_ROOT').'/'.$relative) ) {
        fwrite(STDERR,'Wrong loaded class: '.$class.' '.(new ReflectionClass($class))->getFileName()); exit(2);
    }
}
require_once __DIR__.'/generated-pdf-signature-fixture.php';
$case=json_decode(stream_get_contents(STDIN),true);
if (!is_array($case)) exit(2);
if( isset($case['operation']) && $case['operation']==='code-claim' ) {
    $settings=SUPER_Common::code_settings_from_atts($case['settings']);
    $preview=SUPER_Common::generate_random_code($settings,false);
    $claim_key='_sf_unique_code-'.$preview;
    $first=false;
    $second=false;
    try {
        $first=SUPER_Common::claim_generated_code($settings,$preview);
        $second=SUPER_Common::claim_generated_code($settings,$preview);
    } finally {
        if( $first===true ) delete_option($claim_key);
    }
    $cleaned=(get_option($claim_key,false)===false);
    echo 'CHECKS_ACCEPTED:'.wp_json_encode(array('value'=>$preview,'first_claim'=>$first,'duplicate_claim'=>$second,'claim_cleaned'=>$cleaned));
    exit;
}
$fid=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'synthetic-validation-'.uniqid()));
register_shutdown_function(function()use($fid){wp_delete_post($fid,true);});
if( isset($case['operation']) && $case['operation']==='generated-pdf-three-signatures' ) {
    // A form a buyer builds: three Signature elements, a PDF page break, PDF generation on.
    // The browser hands the generated PDF back as a data URI, exactly as a real submission
    // does. The PDF is the plugin's own output for this form, not an uploaded file.
    $pdf_bytes=super_forms_test_build_signature_pdf(isset($case['pdf_bytes']) ? (int)$case['pdf_bytes'] : 8215751);
    $settings=array(
        'send'=>'no',
        'confirm'=>'no',
        'save_contact_entry'=>'no',
        '_pdf'=>array('generate'=>'true','filename'=>'form.pdf','emailLabel'=>'PDF file:'),
    );
    if( isset($case['pdf_filesize']) ) $settings['_pdf']['filesize']=(string)$case['pdf_filesize'];
    update_post_meta($fid,'_super_elements',super_forms_test_signature_elements());
    update_post_meta($fid,'_super_form_settings',$settings);
    $data=super_forms_test_signature_values()+super_forms_test_generated_pdf_carrier('data:application/pdf;base64,'.base64_encode($pdf_bytes));
    $data['hidden_form_id']=array('name'=>'hidden_form_id','type'=>'form_id','value'=>(string)$fid);
    $data['hidden_contact_entry_id']=array('name'=>'hidden_contact_entry_id','type'=>'entry_id','value'=>'');
    $_POST=array('form_id'=>(string)$fid,'action'=>'super_submit_form','i18n'=>'','data'=>wp_slash(wp_json_encode($data)));
    $_REQUEST=$_POST;$_FILES=array();
    $atts=SUPER_Ajax::submit_form_checks($settings,false);
    $file=$atts['data']['_generated_pdf_file']['files'][0];
    $attachment_id=isset($file['attachment']) ? absint($file['attachment']) : 0;
    register_shutdown_function(function()use($attachment_id){ if($attachment_id) wp_delete_attachment($attachment_id,true); });
    echo 'CHECKS_ACCEPTED:'.wp_json_encode(array(
        'pdf_bytes'=>isset($file['size']) ? (int)$file['size'] : 0,
        'pdf_mime'=>isset($file['type']) ? $file['type'] : '',
        'signature_fields'=>count(super_forms_test_signature_elements()),
        'data_uri_removed'=>!isset($file['datauristring']),
    ));
    exit;
}
if( isset($case['elements']) ) {
    // Multi-element form: the saved tree and the browser carriers are given verbatim.
    update_post_meta($fid,'_super_elements',$case['elements']);
    update_post_meta($fid,'_super_form_settings',array('send'=>'no','confirm'=>'no','save_contact_entry'=>'no'));
    $data=$case['payload']+array(
        'hidden_form_id'=>array('name'=>'hidden_form_id','type'=>'form_id','value'=>(string)$fid),
        'hidden_contact_entry_id'=>array('name'=>'hidden_contact_entry_id','type'=>'entry_id','value'=>''),
    );
    $_POST=array('form_id'=>(string)$fid,'action'=>'super_submit_form','i18n'=>'','data'=>wp_slash(wp_json_encode($data)));
    $_REQUEST=$_POST;$_FILES=array();
    $atts=SUPER_Ajax::submit_form_checks(false);
    echo 'CHECKS_ACCEPTED:'.wp_json_encode(array_intersect_key($atts['data'],$case['payload']));
    exit;
}
$element=array('tag'=>$case['tag'],'group'=>'form_elements','data'=>array_merge(array('name'=>'probe','may_be_empty'=>'true'),$case['settings']));
update_post_meta($fid,'_super_elements',array($element));
update_post_meta($fid,'_super_form_settings',array('send'=>'no','confirm'=>'no','save_contact_entry'=>'no'));
if( isset($case['operation']) && $case['operation']==='render-currency' ) {
    $html=SUPER_Shortcodes::output_element_html(array('tag'=>'currency','group'=>'form_elements','data'=>$element['data'],'shortcodes'=>SUPER_Shortcodes::shortcodes(),
        'settings'=>SUPER_Common::get_form_settings($fid),'i18n'=>isset($case['language']) ? $case['language'] : null));
    $doc=new DOMDocument();
    $doc->loadHTML('<html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',LIBXML_NOERROR|LIBXML_NOWARNING);
    $input=(new DOMXPath($doc))->query('//input[@name="probe"]')->item(0);
    if(!$input) throw new RuntimeException('Real currency renderer did not emit probe input');
    $attributes=array();
    foreach($input->attributes as $attr) {
        if(strpos($attr->name,'data-')===0) {
            $key=preg_replace_callback('/-([a-z])/',function($m){return strtoupper($m[1]);},substr($attr->name,5));
            $attributes[$key]=$attr->value;
        }
    }
    echo 'CHECKS_ACCEPTED:'.wp_json_encode(array('rendered_attributes'=>$attributes));
    exit;
}

$data=array(
    'probe'=>array('name'=>'probe','type'=>'var','value'=>$case['value']),
    'hidden_form_id'=>array('name'=>'hidden_form_id','type'=>'form_id','value'=>(string)$fid),
    'hidden_contact_entry_id'=>array('name'=>'hidden_contact_entry_id','type'=>'entry_id','value'=>''),
);
$_POST=array('form_id'=>(string)$fid,'action'=>'super_submit_form','i18n'=>isset($case['language']) ? $case['language'] : '','data'=>wp_slash(wp_json_encode($data)));
$_REQUEST=$_POST;$_FILES=array();
$atts=SUPER_Ajax::submit_form_checks(false);
echo "CHECKS_ACCEPTED:".wp_json_encode($atts['data']['probe']);
