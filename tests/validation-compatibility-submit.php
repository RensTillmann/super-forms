<?php
/** Public submission-check compatibility seam; run by run-validation-compatibility.py. */
$bootstrap = getenv('SF_TEST_BOOTSTRAP');
if (!$bootstrap || !is_file($bootstrap)) { fwrite(STDERR,"SF_TEST_BOOTSTRAP must name an isolated real WordPress bootstrap\n"); exit(2); }
require $bootstrap;
foreach( array('SUPER_Forms'=>'super-forms.php','SUPER_Ajax'=>'includes/class-ajax.php','SUPER_Common'=>'includes/class-common.php') as $class=>$relative ) {
    if( realpath((new ReflectionClass($class))->getFileName())!==realpath(getenv('SF_PLUGIN_ROOT').'/'.$relative) ) {
        fwrite(STDERR,'Wrong loaded class: '.$class.' '.(new ReflectionClass($class))->getFileName()); exit(2);
    }
}
$case=json_decode(stream_get_contents(STDIN),true);
if (!is_array($case)) exit(2);
if( isset($case['operation']) && $case['operation']==='code-claim' ) {
    $settings=SUPER_Common::code_settings_from_atts($case['settings']);
    $preview=SUPER_Common::generate_random_code($settings,false);
    $first=SUPER_Common::claim_generated_code($settings,$preview);
    $second=SUPER_Common::claim_generated_code($settings,$preview);
    if($first) delete_option('_sf_unique_code_'.$preview);
    echo 'CHECKS_ACCEPTED:'.wp_json_encode(array('value'=>$preview,'first_claim'=>$first,'duplicate_claim'=>$second));
    exit;
}
$fid=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'synthetic-validation-'.uniqid()));
register_shutdown_function(function()use($fid){wp_delete_post($fid,true);});
$element=array('tag'=>$case['tag'],'group'=>'form_elements','data'=>array_merge(array('name'=>'probe','may_be_empty'=>'true'),$case['settings']));
update_post_meta($fid,'_super_elements',array($element));
update_post_meta($fid,'_super_form_settings',array('send'=>'no','confirm'=>'no','save_contact_entry'=>'no'));
$data=array(
    'probe'=>array('name'=>'probe','type'=>'var','value'=>$case['value']),
    'hidden_form_id'=>array('name'=>'hidden_form_id','type'=>'form_id','value'=>(string)$fid),
    'hidden_contact_entry_id'=>array('name'=>'hidden_contact_entry_id','type'=>'entry_id','value'=>''),
);
$_POST=array('form_id'=>(string)$fid,'action'=>'super_submit_form','i18n'=>'','data'=>wp_slash(wp_json_encode($data)));
$_REQUEST=$_POST;$_FILES=array();
$atts=SUPER_Ajax::submit_form_checks(false);
echo "CHECKS_ACCEPTED:".wp_json_encode($atts['data']['probe']);
