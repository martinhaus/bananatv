<?php
	$args = array(
			'public'   => true,
			'_builtin' => false
		);
	$post_types = get_post_types($args);
	array_push($post_types,'post');
//Save Form value
$msg = '';
if ( count($_POST) > 0 && isset($_POST['template_settings'])){
		
		if(!empty($_POST['post_type_name'])){
			$impVal=implode(',', $_POST['post_type_name']);
			delete_option ( 'wp_custom_post_template');
			add_option ( 'wp_custom_post_template', $impVal );
			$msg = '<div id="message" class="updated below-h2 msgText"><p>Setting Saved.</p></div>';
		}else{
			delete_option ( 'wp_custom_post_template');
			add_option ( 'wp_custom_post_template', 'post' );
			$msg = '<div id="message" class="error msgText"><p>Please select atleast one post type.</p></div>';
		}
		
}
if(isset($_REQUEST['template_reset']) && $_REQUEST['template_reset']=='reset'){
		
		$impVal =$_POST['post_type_name']='';
		delete_option ( 'wp_custom_post_template');
		add_option ( 'wp_custom_post_template','post');	
		
		$msg = '<div id="message" class="updated below-h2 msgText"><p>Default Setting Saved.</p></div>';
}		

?>
<div class="templateFormType" style="width:70%; float:left;">
<?php echo $msg; ?>
<fieldset class="FormTypeSetting"><legend class="FormTyp_setting"><strong >General Settings</strong></legend>
<form action="" method="post" enctype="multipart/form-data">
<div class="type_chkbox_main">
<?php foreach($post_types as $type){ 
		$counter++;
		 $obj = get_post_type_object( $type );
		 $post_types_name = $obj->labels->singular_name; 
		
		if(get_option('wp_custom_post_template') != ''){
			$postType_title = get_option('wp_custom_post_template');
			$postType_chkd = explode(',',$postType_title);
			$chd = '';
			if(in_array($type, $postType_chkd)){
				 $chd = 'checked="checked"';
			}
		}
		
?>
<div class="type_chkbox"><input type="checkbox" name="post_type_name[]" value="<?php echo $type; ?>" id="<?php echo $type; ?>" <?php echo $chd; ?> class="chkBox" /><label for="<?php echo $type; ?>"><?php echo $post_types_name; ?></label> </div>

<?php } ?>
</div>
<div class="type_submit">
<input type="submit" name="submit" value="Save" class="butt-trmp" />
<input type="hidden" name="template_settings" value="save" style="display:none;" />
</div>
</form>
<p class="note_summry"><?php _e('Note: Select one or more custom post type where you need to enable custom post template selection.');?></p>
</fieldset>	
</div>

<div class="defaultFormType" style="width:70%; float:left;">
	<fieldset class="FormTypeSetting"><legend class="FormTyp_setting"><strong >Default Settings</strong></legend>
	<form action="" method="post" enctype="multipart/form-data">	
		<input type="submit" name="Submit" value="Default Setting" class="butt-trmp" />
		<input type="hidden" name="template_reset" value="reset" style="display:none;" />
	</form>	
	<p class="note_summry"><?php _e('Note: If you are using default setting then post template will show only on default post.'); ?></p>
	</fieldset>
</div>