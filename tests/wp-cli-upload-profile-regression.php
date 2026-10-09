<?php
if (!defined('SUPER_FORMS_LOCAL_UPLOAD_TEST') || SUPER_FORMS_LOCAL_UPLOAD_TEST !== true) throw new Exception('Owned real WP only');
require_once ABSPATH.'wp-admin/includes/image.php';
require_once WP_PLUGIN_DIR.'/super-forms/includes/class-ajax.php';
$green = !defined('EXPECT_AUTHORITY_RED');
$checks = array(); $cases = array();
$check = static function($name, $ok) use (&$checks) { $checks[] = array('name'=>$name, 'passed'=>$ok === true); };
$invoke = static function($method, $args) { $r = new ReflectionMethod('SUPER_Ajax', $method); $r->setAccessible(true); return $r->invokeArgs(null, $args); };
$mapper = new ReflectionMethod('SUPER_Register_Login', 'resolve_custom_meta_value'); $mapper->setAccessible(true);
$fixture = json_decode(file_get_contents('/artifacts/photo-form.json'), true)[0];
$user = wp_insert_user(array('user_login'=>'authority-'.wp_generate_uuid4(), 'user_pass'=>wp_generate_password(), 'user_email'=>wp_generate_uuid4().'@example.invalid', 'role'=>'subscriber'));
wp_set_current_user($user);
SUPER_Forms()->global_settings = SUPER_Common::get_global_settings(); SUPER_Forms()->global_settings['csrf_check'] = 'false';
if (!defined('CROSSFORM_ONLY')) {
if ($green) {
    $form=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'Owned profile media-name boundary'));
    update_post_meta($form,'_super_elements',$fixture['elements']); update_post_meta($form,'_super_form_settings',$fixture['settings']);
    $root=wp_upload_dir()['basedir'].'/superforms/profile-boundary-'.wp_generate_uuid4(); wp_mkdir_p($root); $original=$root.'/profile-original.jpg'; copy('/artifacts/iphone7-large.jpg',$original);
    $id=wp_insert_attachment(array('post_mime_type'=>'image/jpeg','post_status'=>'inherit'),$original,0);
    add_post_meta($id,'super-forms-form-upload-file',true); add_post_meta($id,'_super_forms_upload_form_id',$form); add_post_meta($id,'_super_forms_upload_field','file');
    wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$original)); $physical=get_attached_file($id);
    $physicalOwned=SUPER_Ajax::build_owned_upload($form,'file',$physical,'image/jpeg',wp_get_attachment_url($id),$id,$root,filesize($physical)); $owned=$physicalOwned;
    $owned['original']=array('file'=>$original,'basename'=>basename($original),'size'=>filesize($original));
    $record=$invoke('owned_upload_file_record',array($owned)); $physicalRecord=$invoke('owned_upload_file_record',array($physicalOwned));
    $data=array('file'=>array('type'=>'files','files'=>array($record)));
    $result=$mapper->invoke(null,'file',$data,$fixture['settings'],$form,array($owned));
    $control=$mapper->invoke(null,'file',array('file'=>array('type'=>'files','files'=>array($physicalRecord))),$fixture['settings'],$form,array($physicalOwned));
    $check('original and physical names map same verified attachment', $result===$id && $control===$id);
    foreach(array('value'=>'forged.jpg','size'=>filesize($original)+1,'type'=>'text/plain','url'=>wp_get_attachment_url($id).'?forged=1') as $key=>$bad) {
        $f=$record; $f[$key]=$bad;
        $check('reject original '.$key.' drift', is_wp_error($mapper->invoke(null,'file',array('file'=>array('type'=>'files','files'=>array($f))),$fixture['settings'],$form,array($owned))));
    }
    update_post_meta($id,'_super_forms_upload_form_id',$form+1);
    $check('reject foreign form original media', is_wp_error($mapper->invoke(null,'file',$data,$fixture['settings'],$form,array($owned))));
    update_post_meta($id,'_super_forms_upload_form_id',$form);
}
foreach (array('legacy-original','legacy-display','scaled-row','plain-row','scaled-nested','plain-nested') as $scenario) {
    $legacy = strpos($scenario,'legacy-') === 0; $nested = strpos($scenario,'nested') !== false; $plain = strpos($scenario,'plain') === 0;
    $route = $legacy ? 'file' : ($nested ? 'file[0]_2' : 'file_2');
    $elements = $fixture['elements'];
    if (!$legacy) $elements = array(array('tag'=>'column','data'=>array('duplicate'=>'enabled'),'inner'=>$elements));
    if ($nested) $elements = array(array('tag'=>'column','data'=>array('duplicate'=>'enabled'),'inner'=>$elements));
    $form = wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'Authority '.$scenario));
    $settings = $fixture['settings']; $settings['register_login_action']='update'; $settings['register_login_update_user_meta']=$route.'|sf_authority_'.$scenario;
    update_post_meta($form,'_super_elements',$elements); update_post_meta($form,'_super_form_settings',$settings);
    $settings = SUPER_Common::get_form_settings($form);
    $root = wp_upload_dir()['basedir'].'/superforms/authority-'.wp_generate_uuid4(); wp_mkdir_p($root);
    $original = $root.'/original.jpg'; copy('/artifacts/iphone7-large.jpg',$original);
    if ($plain) { $editor=wp_get_image_editor($original); if(is_wp_error($editor))throw new Exception('Image editor'); $editor->resize(640,480); $editor->save($original); }
    $id = wp_insert_attachment(array('post_mime_type'=>'image/jpeg','post_status'=>'inherit'),$original,0);
    add_post_meta($id,'super-forms-form-upload-file',true);
    if (!$legacy) { add_post_meta($id,'_super_forms_upload_form_id',$form); add_post_meta($id,'_super_forms_upload_field','file'); }
    wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$original)); $physical=get_attached_file($id);
    $check($scenario.' real scaling control', $plain ? $physical===$original : $physical!==$original);
    $entry=0;
    if ($legacy) {
        $entry=wp_insert_post(array('post_type'=>'super_contact_entry','post_status'=>'super_read','post_parent'=>$form,'post_author'=>$user));
        wp_update_post(array('ID'=>$id,'post_parent'=>$entry));
        $stored=array('value'=>$scenario==='legacy-display'?'Keep-Exact-Display.JPG':basename($original),'name'=>$route,'type'=>'image/jpeg','url'=>wp_get_attachment_url($id),'attachment'=>$id);
        $saved=array($route=>array('type'=>'files','files'=>array($stored))); SUPER_Data_Access::update_entry_data($entry,$saved);
        $client=$stored; $client['retention_token']='entry';
        $resolved=$invoke('resolve_submission_files',array(array($route=>array('type'=>'files','files'=>array($client))),$form,$elements,$entry));
        $owned=$resolved['retained_owned_files'];
        $check($scenario.' missing old metadata and size preserved', get_post_meta($id,'_super_forms_upload_form_id',true)==='' && get_post_meta($id,'_super_forms_upload_field',true)==='' && !isset(SUPER_Data_Access::get_entry_data($entry)[$route]['files'][0]['size']));
    } else {
        $o=SUPER_Ajax::build_owned_upload($form,'file',$physical,'image/jpeg',wp_get_attachment_url($id),$id,$root,filesize($physical));
        if (!$plain) $o['original']=array('file'=>$original,'basename'=>basename($original),'size'=>filesize($original));
        $o['route_name']=$route;
        $token=$invoke('issue_upload_receipt',array($o));
        $client=array('upload_token'=>$token);
        $input=array($route=>array('type'=>'files','field_name'=>'file','files'=>array($client)));
        $resolved=$invoke('resolve_submission_files',array($input,$form,$elements,0));
        $check($scenario.' receipt route substitution rejected', is_wp_error($invoke('resolve_submission_files',array(array($nested?'file[0]_3':'file_3'=>array('type'=>'files','field_name'=>'file','files'=>array($client))),$form,$elements,0))));
        $claims=$invoke('claim_upload_receipts',array($resolved['inspected'])); $owned=$invoke('consume_upload_receipt_claims',array($claims));
        $check($scenario.' receipt consumed and replay rejected', is_wp_error($invoke('resolve_submission_files',array($input,$form,$elements,0))));
    }
    $check($scenario.' real reconstruction', is_array($resolved) && count($owned)===1);
    $data=$resolved['data']; $record=reset($data[$route]['files']);
    $result=$mapper->invoke(null,$route,$data,$settings,$form,$owned);
    $expected = $green || $plain;
    $check($scenario.' mapper '.($expected?'accepts':'RED rejects'), $expected ? $result===$id : is_wp_error($result));
    // Exercise the actual deferred profile hooks in a real WP child: a rejected hook dies with real JSON.
    $childCode = 'if(!has_action(\'super_before_sending_email_hook\')||!has_action(\'super_before_email_success_msg_action\'))throw new Exception(\'Actual AJAX hooks absent\');wp_set_current_user('.$user.');$x=json_decode(base64_decode(\''.base64_encode(wp_json_encode(array('form_id'=>$form,'data'=>$data,'post'=>array('form_id'=>$form,'action'=>'super_submit_form'),'settings'=>$settings,'owned_files'=>$owned,'entry_id'=>$entry,'attachments'=>array()))).'\'),true);do_action(\'super_before_sending_email_hook\',$x);do_action(\'super_before_email_success_msg_action\',$x);echo wp_json_encode(array(\'hook_returned\'=>true));';
    $process = proc_open(array('/usr/local/bin/wp','--allow-root','--exec=define("DOING_AJAX",true);','eval','eval(stream_get_contents(STDIN));'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,ABSPATH);
    if (!is_resource($process)) throw new Exception('Real WP hook child did not start');
    fwrite($pipes[0],$childCode); fclose($pipes[0]); $hook=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $status=proc_close($process);
    $check($scenario.' hook child no PHP errors', $stderr==='');
    wp_cache_delete($user,'user_meta');
    $meta=get_user_meta($user,'sf_authority_'.$scenario,true);
    $check($scenario.' actual hook custom meta '.($expected?'written':'RED untouched'), $expected ? (string)$meta===(string)$id : $meta==='' && strpos($hook,'Invalid file upload')!==false);
    $cases[]=array('scenario'=>$scenario,'form'=>$form,'entry'=>$entry,'attachment'=>$id,'route'=>$route,'record'=>$record,'mapper'=>is_wp_error($result)?$result->get_error_code():$result,'hook_stderr'=>$stderr,'hook_exit'=>$status,'hook_output'=>$hook,'custom_meta'=>$meta,'physical'=>$physical,'original'=>$original);
    if ($green) {
        foreach (array('value'=>'forged.jpg','name'=>'foreign','size'=>$record['size']+1,'type'=>'text/plain','url'=>$record['url'].'?forged=1','path'=>$physical,'upload_token'=>'forged','retention_token'=>'entry','attachment'=>$id+10000,'_super_file_authority'=>'client') as $key=>$bad) {
            $changed=$record; $changed[$key]=$bad; $d=$data; $d[$route]['files']=array($changed);
            $check($scenario.' rejects record '.$key, is_wp_error($mapper->invoke(null,$route,$d,$settings,$form,$owned)));
        }
        $check($scenario.' rejects flag without server descriptor', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,array())));
        // Retained form access is authorized upstream; fresh uploads bind the submitting form here.
        $check($scenario.' rejects foreign form', $legacy
            ? $invoke('submission_entry_update_is_authorized',array($entry,'',$form+10000,$settings))===false
            : is_wp_error($mapper->invoke(null,$route,$data,$settings,$form+10000,$owned)));
        foreach (array('_super_forms_upload_form_id'=>$form+1,'_super_forms_upload_field'=>'foreign','super-forms-form-upload-file'=>'') as $key=>$bad) {
            $old=get_post_meta($id,$key,true); update_post_meta($id,$key,$bad);
            $check($scenario.' rejects ownership '.$key, is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)));
            if ($old==='') delete_post_meta($id,$key); else update_post_meta($id,$key,$old);
        }
        $metadata=wp_get_attachment_metadata($id); $bad=$metadata; $bad['original_image']='foreign.jpg'; wp_update_attachment_metadata($id,$bad);
        if (!$plain && !$legacy) $check($scenario.' metadata drift rejected', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)));
        wp_update_attachment_metadata($id,$metadata);
        if ($legacy) {
            $forged=$stored; $forged['value']='forged.jpg'; $forged['retention_token']='entry';
            $check($scenario.' forged retained selector rejected', is_wp_error($invoke('resolve_submission_files',array(array($route=>array('type'=>'files','files'=>array($forged))),$form,$elements,$entry))));
            $old=SUPER_Data_Access::get_entry_data($entry); $bad=$old; $bad[$route]['files'][0]['url'].='?drift'; SUPER_Data_Access::update_entry_data($entry,$bad);
            $check($scenario.' saved-record drift revoked', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)));
            SUPER_Data_Access::update_entry_data($entry,$old);
            wp_update_post(array('ID'=>$id,'post_parent'=>0));
            $check($scenario.' foreign parent rejected', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)));
            wp_update_post(array('ID'=>$id,'post_parent'=>$entry));
        }
        if (!$legacy) {
            wp_update_post(array('ID'=>$id,'post_parent'=>$form));
            $check($scenario.' foreign parent rejected', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)));
            wp_update_post(array('ID'=>$id,'post_parent'=>0));
        }
        wp_delete_attachment($id,true);
        $check($scenario.' deleted descriptor rejected', is_wp_error($mapper->invoke(null,$route,$data,$settings,$form,$owned)) && !is_file($physical) && !is_file($original));
    }
}
}
// Configured retrieve-last-entry is not a Listing: A submits, B owns the retained attachment.
$cross_red = defined('EXPECT_CROSSFORM_RED');
$source_form=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'Crossform source B'));
update_post_meta($source_form,'_super_elements',$fixture['elements']); update_post_meta($source_form,'_super_form_settings',$fixture['settings']);
$current_form=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'Crossform submitting A'));
$cross_settings=$fixture['settings']; $cross_settings['retrieve_last_entry_form']=(string)$source_form;
$cross_settings['register_login_action']='update'; $cross_settings['register_login_update_user_meta']='file|sf_crossform_profile';
update_post_meta($current_form,'_super_elements',$fixture['elements']); update_post_meta($current_form,'_super_form_settings',$cross_settings);
$foreign_form=wp_insert_post(array('post_type'=>'super_form','post_status'=>'publish','post_title'=>'Ungrantable C'));
update_post_meta($foreign_form,'_super_elements',$fixture['elements']); update_post_meta($foreign_form,'_super_form_settings',$cross_settings);
$cross_root=wp_upload_dir()['basedir'].'/superforms/crossform-'.wp_generate_uuid4(); wp_mkdir_p($cross_root);
$cross_path=$cross_root.'/retained.jpg'; copy('/artifacts/iphone7-large.jpg',$cross_path);
$editor=wp_get_image_editor($cross_path); if(is_wp_error($editor))throw new Exception('Image editor'); $editor->resize(640,480); $editor->save($cross_path);
$cross_id=wp_insert_attachment(array('post_mime_type'=>'image/jpeg','post_status'=>'inherit'),$cross_path,0);
add_post_meta($cross_id,'super-forms-form-upload-file',true); add_post_meta($cross_id,'_super_forms_upload_form_id',$source_form); add_post_meta($cross_id,'_super_forms_upload_field','file');
wp_update_attachment_metadata($cross_id,wp_generate_attachment_metadata($cross_id,$cross_path));
$cross_entry=wp_insert_post(array('post_type'=>'super_contact_entry','post_status'=>'super_read','post_parent'=>$source_form,'post_author'=>$user));
wp_update_post(array('ID'=>$cross_id,'post_parent'=>$cross_entry));
$cross_stored=array('value'=>basename($cross_path),'name'=>'file','type'=>'image/jpeg','url'=>wp_get_attachment_url($cross_id),'size'=>filesize($cross_path),'attachment'=>$cross_id);
SUPER_Data_Access::update_entry_data($cross_entry,array('file'=>array('type'=>'files','files'=>array($cross_stored))));
$other_actor=wp_insert_user(array('user_login'=>'foreign-'.wp_generate_uuid4(),'user_pass'=>wp_generate_password(),'user_email'=>wp_generate_uuid4().'@example.invalid','role'=>'subscriber'));
$other_entry=wp_insert_post(array('post_type'=>'super_contact_entry','post_status'=>'super_read','post_parent'=>$source_form,'post_author'=>$other_actor));
SUPER_Data_Access::update_entry_data($other_entry,array('file'=>array('type'=>'files','files'=>array($cross_stored))));
$check('crossform real unscaled attachment',get_attached_file($cross_id)===$cross_path && !isset(wp_get_attachment_metadata($cross_id)['original_image']));
$context=base64_encode(wp_json_encode(array('user'=>$user,'other_actor'=>$other_actor,'form'=>$current_form,'source'=>$source_form,'foreign'=>$foreign_form,'entry'=>$cross_entry,'other_entry'=>$other_entry,'attachment'=>$cross_id,'stored'=>$cross_stored)));
foreach(array('reconstruct','full-submit','ungranted','foreign-form','foreign-entry','foreign-actor','forged-selector','descriptor-less','forged-record','forged-descriptor','fresh-form-mismatch','actor-switch','request-drift','replay') as $scenario) {
    delete_user_meta($user,'sf_crossform_profile');
    $childCode='$c=json_decode(base64_decode(\''.$context.'\'),true);$mode=\''.$scenario.'\';require_once WP_PLUGIN_DIR.\'/super-forms/includes/class-ajax.php\';$_SERVER[\'HTTP_HOST\']=wp_parse_url(home_url(),PHP_URL_HOST).\':\'.wp_parse_url(home_url(),PHP_URL_PORT);$_SERVER[\'REQUEST_URI\']=\'/wp-admin/admin-ajax.php\';$_SERVER[\'REQUEST_METHOD\']=\'POST\';wp_set_current_user($c[\'user\']);$exp=time()+3600;$token=WP_Session_Tokens::get_instance($c[\'user\'])->create($exp);$_COOKIE[LOGGED_IN_COOKIE]=wp_generate_auth_cookie($c[\'user\'],$exp,\'logged_in\',$token);SUPER_Forms()->global_settings=SUPER_Common::get_global_settings();SUPER_Forms()->global_settings[\'csrf_check\']=\'false\';'
        .'if(!has_action(\'super_before_sending_email_hook\')||!has_action(\'super_before_email_success_msg_action\'))throw new Exception(\'Actual AJAX hooks absent\');'
        .'$html=SUPER_Shortcodes::super_form_func(array(\'id\'=>(string)$c[\'form\']));$grant=SUPER_Common::getClientData(\'update_contact_entry_\'.$c[\'form\'].\'_\'.$c[\'entry\']);'
        .'if(strpos($html,\'hidden_contact_entry_id\')===false||!is_array($grant)||$grant!==SUPER_Common::current_entry_update_grant_value())throw new Exception(\'Renderer grant absent\');'
        .'$client=$c[\'stored\'];$client[\'retention_token\']=\'entry\';$form=$c[\'form\'];$entry=$c[\'entry\'];'
        .'if($mode===\'ungranted\'){SUPER_Common::setClientData(array(\'name\'=>\'update_contact_entry_\'.$form.\'_\'.$entry,\'value\'=>false,\'force\'=>true));}'
        .'if($mode===\'foreign-form\')$form=$c[\'foreign\'];if($mode===\'foreign-entry\')$entry=$c[\'other_entry\'];if($mode===\'foreign-actor\')wp_set_current_user($c[\'other_actor\']);if($mode===\'forged-selector\')$client[\'value\']=\'forged.jpg\';'
        .'$_POST=array(\'action\'=>\'super_submit_form\',\'form_id\'=>$form,\'entry_id\'=>$entry,\'data\'=>wp_slash(wp_json_encode(array(\'hidden_form_id\'=>array(\'name\'=>\'hidden_form_id\',\'value\'=>(string)$form,\'type\'=>\'form_id\'),\'hidden_contact_entry_id\'=>array(\'name\'=>\'hidden_contact_entry_id\',\'value\'=>(string)$entry,\'type\'=>\'entry_id\'),\'file\'=>array(\'name\'=>\'file\',\'type\'=>\'files\',\'files\'=>array($client))))));$_REQUEST=$_POST;'
        .'if(in_array($mode,array(\'full-submit\',\'ungranted\',\'foreign-form\',\'foreign-entry\',\'foreign-actor\',\'forged-selector\'),true)){SUPER_Ajax::submit_form();exit;}'
        .'$a=SUPER_Ajax::submit_form_checks();$owned=$a[\'retained_owned_files\'];$data=$a[\'data\'];$mapper=new ReflectionMethod(\'SUPER_Register_Login\',\'resolve_custom_meta_value\');$mapper->setAccessible(true);'
        .'if($mode===\'reconstruct\'){$result=$mapper->invoke(null,\'file\',$data,$a[\'settings\'],$form,$owned,$entry);echo wp_json_encode(array(\'grant\'=>$grant,\'entry\'=>$a[\'entry_id\'],\'form\'=>$a[\'form_id\'],\'owned\'=>$owned,\'data\'=>$data,\'mapper\'=>is_wp_error($result)?$result->get_error_code():$result));exit;}'
        .'if($mode===\'descriptor-less\')$owned=array();if($mode===\'forged-record\')$data[\'file\'][\'files\'][0][\'url\'].=\'?forged\';if($mode===\'forged-descriptor\')$owned[0][\'form_id\']=$form;'
        .'if($mode===\'fresh-form-mismatch\'){$o=$owned[0];foreach(array(\'legacy_entry_id\',\'legacy_source_field\',\'legacy_source_key\',\'retained\') as $k)unset($o[$k]);$owned=array($o);$data[\'file\'][\'files\'][0][\'_super_file_authority\']=\'owned\';$r=$mapper->invoke(null,\'file\',$data,$a[\'settings\'],$form,$owned,$entry);echo wp_json_encode(array(\'fresh_rejected\'=>is_wp_error($r)));exit;}'
        .'$x=array(\'form_id\'=>$form,\'post\'=>$_POST,\'settings\'=>$a[\'settings\'],\'data\'=>$data,\'owned_files\'=>$owned,\'owned_upload_parent\'=>$entry,\'entry_id\'=>$entry,\'attachments\'=>array());do_action(\'super_before_sending_email_hook\',$x);'
        .'if($mode===\'actor-switch\')wp_set_current_user($c[\'other_actor\']);if($mode===\'request-drift\')$x[\'post\'][\'form_id\']=$c[\'foreign\'];'
        .'do_action(\'super_before_email_success_msg_action\',$x);if($mode===\'replay\'){$first=get_user_meta($c[\'user\'],\'sf_crossform_profile\',true);delete_user_meta($c[\'user\'],\'sf_crossform_profile\');do_action(\'super_before_email_success_msg_action\',$x);echo wp_json_encode(array(\'first_write\'=>$first,\'hook_returned\'=>true));}else echo wp_json_encode(array(\'hook_returned\'=>true));';
    $process=proc_open(array('/usr/local/bin/wp','--allow-root','--exec=define("DOING_AJAX",true);','eval','eval(stream_get_contents(STDIN));'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,ABSPATH);
    if(!is_resource($process))throw new Exception('Crossform real WP child absent');
    fwrite($pipes[0],$childCode);fclose($pipes[0]);$output=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$status=proc_close($process);
    wp_cache_delete($user,'user_meta');$meta=get_user_meta($user,'sf_crossform_profile',true);
    $diagnostics=method_exists('SUPER_Common','triggerEvent') ? preg_replace('/^\[[^\r\n]+ UTC\] triggerEvent\(sf\.(?:(?:before|after)\.submission|submission\.finalized)\)\r?\n/m','',$stderr) : $stderr;
    $check('crossform '.$scenario.' no PHP errors',$diagnostics==='');
    if($scenario==='reconstruct') {
        $r=json_decode($output,true);$o=isset($r['owned'][0])?$r['owned'][0]:array();
        $check('crossform actual renderer grant and canonical parent',$status===0 && isset($r['grant']['actor_id']) && $r['grant']['actor_id']===$user && (int)$r['entry']===$cross_entry && $r['form']===$current_form && $o['form_id']===$source_form && $o['legacy_entry_id']===$cross_entry && $o['legacy_source_field']==='file' && $o['legacy_source_key']===0 && $o['attachment']===$cross_id);
        $check('crossform stored and current reconstruction exact',$r['data']['file']['files'][0]['value']===$cross_stored['value'] && $r['data']['file']['files'][0]['size']===$cross_stored['size'] && $r['data']['file']['files'][0]['url']===$cross_stored['url'] && $r['data']['file']['files'][0]['_super_file_authority']==='retained');
        $check('crossform mapper '.($cross_red?'RED rejects':'accepts'),$cross_red?$r['mapper']==='super_forms_invalid_custom_meta_file':$r['mapper']===$cross_id);
    }elseif($scenario==='full-submit') {
        $check('crossform actual full-submit profile '.($cross_red?'RED untouched':'written'),$cross_red?$meta==='' && strpos($output,'Invalid file upload')!==false:(string)$meta===(string)$cross_id && strpos($output,'"error":false')!==false);
    }elseif($scenario==='fresh-form-mismatch') {
        $r=json_decode($output,true);$check('crossform fresh stays bound to submitting form',$status===0 && isset($r['fresh_rejected']) && $r['fresh_rejected']===true && $meta==='');
    }elseif($scenario==='replay') {
        $r=json_decode($output,true);
        $check($cross_red?'crossform replay setup RED rejected':'crossform one-time action cannot write twice',$cross_red ? $meta==='' && strpos($output,'Invalid file upload')!==false : $meta==='' && isset($r['first_write']) && (string)$r['first_write']===(string)$cross_id);
    }else {
        $permission=in_array($scenario,array('ungranted','foreign-form','foreign-entry','foreign-actor'),true);
        $action=in_array($scenario,array('actor-switch','request-drift'),true);
        $needle=$permission?'You do not have permission to edit this entry':($action?'Unable to authorize':'Invalid file upload');
        $check('crossform '.$scenario.' rejected without meta write',$meta==='' && strpos($output,$needle)!==false);
    }
    $cases[]=array('scenario'=>'crossform-'.$scenario,'output'=>$output,'stderr'=>$stderr,'exit'=>$status,'meta'=>$meta,'form'=>$current_form,'source_form'=>$source_form,'entry'=>$cross_entry,'attachment'=>$cross_id);
}
$failures=array_values(array_filter($checks,static function($c){return !$c['passed'];}));
echo wp_json_encode(array('mode'=>$green?'GREEN':'RED','real_wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'cases'=>$cases,'checks'=>$checks,'failures'=>$failures,'ajax_sha256'=>hash_file('sha256',WP_PLUGIN_DIR.'/super-forms/includes/class-ajax.php'),'mapper_sha256'=>hash_file('sha256',(new ReflectionClass('SUPER_Register_Login'))->getFileName())));
if ($failures) throw new Exception('Authority checks failed: '.wp_json_encode($failures));
