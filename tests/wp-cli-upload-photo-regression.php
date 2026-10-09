<?php
/** Run only in an owned real-WP site via wp eval-file; not a stubbed WP test. */
if( !defined('SUPER_FORMS_LOCAL_UPLOAD_TEST') || SUPER_FORMS_LOCAL_UPLOAD_TEST!==true ) {
    throw new Exception('Explicit disposable local test bootstrap required');
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once WP_PLUGIN_DIR . '/super-forms/includes/class-ajax.php';
$checks = array();
$check = static function($name, $ok) use (&$checks) {
    if( !$ok ) throw new Exception($name);
    $checks[] = $name;
};
$invoke = static function($name, $args) {
    $m = new ReflectionMethod('SUPER_Ajax', $name);
    $m->setAccessible(true);
    return $m->invokeArgs(null, $args);
};
$fixture = json_decode(file_get_contents('/artifacts/photo-form.json'), true)[0];
$form = wp_insert_post(array('post_type'=>'super_form', 'post_status'=>'publish', 'post_title'=>'Owned scaled-photo authority regression'));
update_post_meta($form, '_super_elements', $fixture['elements']);
update_post_meta($form, '_super_form_settings', $fixture['settings']);
$root = wp_upload_dir()['basedir'] . '/superforms/photo-authority-' . wp_generate_uuid4();
wp_mkdir_p($root);
$path = $root . '/physical-original.jpg';
copy('/artifacts/iphone7-large.jpg', $path);
$id = wp_insert_attachment(array('post_mime_type'=>'image/jpeg', 'post_status'=>'inherit'), $path, 0);
add_post_meta($id, 'super-forms-form-upload-file', true);
add_post_meta($id, '_super_forms_upload_form_id', $form);
add_post_meta($id, '_super_forms_upload_field', 'file');
wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $path));
$attached = get_attached_file($id);
$check('real WP scaling occurred', $attached!==$path && wp_get_original_image_path($id)===$path);
$owned = SUPER_Ajax::build_owned_upload($form, 'file', $attached, 'image/jpeg', wp_get_attachment_url($id), $id, $root, filesize($attached));
$owned['original'] = array('file'=>$path, 'basename'=>basename($path), 'size'=>filesize($path));
$current = static function($o, $parent) use ($invoke) { return $invoke('owned_upload_is_current', array($o, $parent)); };
$check('original and physical descriptor authority', $current($owned, 0)===true);
wp_set_current_user(0);
SUPER_Forms()->global_settings = SUPER_Common::get_global_settings();
SUPER_Forms()->global_settings['csrf_check'] = 'false'; // Process-local, explicitly sessionless receipt probe.
$token = $invoke('issue_upload_receipt', array($owned));
$check('server receipt issued', is_string($token));
$inspect = static function() use ($invoke, $token, $form) { return $invoke('inspect_upload_receipt', array($token, $form, 'file')); };
$check('receipt inspection binds original bytes', is_array($inspect()));
foreach(array('file'=>$attached, 'basename'=>'client-invented.jpg', 'size'=>filesize($path)+1) as $key=>$bad) {
    $changed=$owned; $changed['original'][$key]=$bad;
    $check('reject original '.$key.' drift', $current($changed, 0)===false);
}
$meta=wp_get_attachment_metadata($id);$changed=$meta;$changed['original_image']='unrelated.jpg';wp_update_attachment_metadata($id,$changed);
$check('metadata original-path drift revokes receipt', $inspect()===false);
wp_update_attachment_metadata($id,$meta);
$backup=$root.'/original-backup.jpg';rename($path,$backup);symlink($backup,$path);
$check('original symlink revokes receipt', $inspect()===false);
unlink($path);rename($backup,$path);
$descriptor=$inspect();$claims=$invoke('claim_upload_receipts',array(array($descriptor)));
$check('atomic receipt claim', is_array($claims) && count($claims)===1);
$check('duplicate claim rejected', $invoke('claim_upload_receipts',array(array($descriptor)))===false);
$check('exact claim consumption', count($invoke('consume_upload_receipt_claims',array($claims)))===1);
$check('consumed receipt cannot replay', $inspect()===false);
$entry=wp_insert_post(array('post_type'=>'super_contact_entry','post_status'=>'super_read','post_parent'=>$form));
wp_update_post(array('ID'=>$id,'post_parent'=>$entry));
$resolve = static function($client, &$authority) use ($invoke, $entry) {
    $args=array($client,$entry,'file','file',&$authority);
    return $invoke('resolve_retained_entry_file',$args);
};
// 2026-10-03 rule (t_44dd6674): a saved name that is not the verified backing file is retained under
// the on-disk name; the re-save is accepted, and the file is never deleted.
$stored=array('value'=>'Keep-Exact-Display.JPG','name'=>'file','type'=>'image/jpeg','url'=>wp_get_attachment_url($id),'size'=>filesize($path),'attachment'=>$id);
$data=array('file'=>array('type'=>'files','files'=>array($stored)));
update_post_meta($entry,'_super_contact_entry_data',$data);
foreach(array($stored,array('value'=>basename($attached),'url'=>wp_get_attachment_url($id))) as $alias) {
    $authority=false;$record=$resolve($alias,$authority);
    $check('mismatched saved name re-saves under the on-disk name '.count($checks), is_array($record) && $record['value']===basename($attached) && $record['size']===filesize($attached));
}
$authority=false;$record=$resolve($stored,$authority);
$check('mismatched saved name grants no cleanup authority', is_array($authority) && $authority['cleanup_authority']===false && $invoke('retained_owned_upload_is_current',array($authority))===false);
$check('mismatched saved name is never deleted', $invoke('delete_finalized_owned_uploads',array(array($authority),$entry,$form))===false && get_post($id)!==null && is_file($path) && is_file($attached));
$forged=$stored;$forged['value']='forged.jpg';$unused=false;
$check('forged selector rejected', $resolve($forged,$unused)===false);
$forged=$stored;$forged['url'].='?forged=1';
$check('forged URL rejected', $resolve($forged,$unused)===false);
$drift=$data;$drift['file']['files'][0]['size']++;update_post_meta($entry,'_super_contact_entry_data',$drift);
$authority=false;
$check('size drift on a mismatched name still re-saves without cleanup', is_array($resolve($drift['file']['files'][0],$authority)) && $authority['cleanup_authority']===false);
// The exact original name keeps its saved display and verified cleanup authority.
$stored['value']=basename($path);
$data=array('file'=>array('type'=>'files','files'=>array($stored)));
update_post_meta($entry,'_super_contact_entry_data',$data);
$authority=false;$record=$resolve($stored,$authority);
$check('exact original name keeps its saved display', is_array($record) && $record['value']===$stored['value'] && $record['size']===$stored['size']);
$check('cleanup revalidates the exact saved record', $authority['cleanup_authority']===true && $invoke('retained_owned_upload_is_current',array($authority))===true);
$duplicate=$data;$duplicate['file']['files'][]=$stored;update_post_meta($entry,'_super_contact_entry_data',$duplicate);
$check('duplicate selector rejected', $resolve($stored,$unused)===false);
update_post_meta($entry,'_super_contact_entry_data',$data);
foreach(array('_super_forms_upload_form_id'=>$form+1,'_super_forms_upload_field'=>'foreign') as $key=>$bad) {
    $old=get_post_meta($id,$key,true);update_post_meta($id,$key,$bad);
    $check('foreign ownership marker '.$key, $resolve($stored,$unused)===false);
    update_post_meta($id,$key,$old);
}
delete_post_meta($id,'super-forms-form-upload-file');
$check('missing upload marker rejected', $resolve($stored,$unused)===false);
add_post_meta($id,'super-forms-form-upload-file',true);
$drift=$data;$drift['file']['files'][0]['size']++;update_post_meta($entry,'_super_contact_entry_data',$drift);
$check('stored original size drift rejected', $resolve($stored,$unused)===false);
$check('cleanup rejects changed stored record', $invoke('retained_owned_upload_is_current',array($authority))===false);
update_post_meta($entry,'_super_contact_entry_data',$data);
$originalHash=hash_file('sha256',$path);$derivativeHash=hash_file('sha256',$attached);
$check('cleanup-off path preserves originals and derivatives', $invoke('cleanup_owned_uploads',array(array($authority)))===true && hash_file('sha256',$path)===$originalHash && hash_file('sha256',$attached)===$derivativeHash);
$check('retained exact configured deletion works', $invoke('delete_finalized_owned_uploads',array(array($authority),$entry,$form))===true);
$check('WP deletes only owned attachment original and derivatives', get_post($id)===null && !is_file($path) && !is_file($attached));
echo wp_json_encode(array('real_wordpress'=>get_bloginfo('version'),'checks'=>$checks,'passed'=>count($checks),'ajax_sha256'=>hash_file('sha256',WP_PLUGIN_DIR.'/super-forms/includes/class-ajax.php')));
