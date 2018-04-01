<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 12.3.2017
 * Time: 1:06
 */
class CCTVWidget extends WP_Widget {
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'CCTVWidget',
			'description' => 'Widget for displaying CCTV footage',
		);
		
		parent::__construct( 'CCTVWidget', 'Kamerový záznam', $widget_ops );
	}
	
	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
	    $camera_url = $instance['camera_url'];
	    $ip_filter = $instance['ip_filter'];

	    if ($ip_filter && $_SERVER['REMOTE_ADDR'] != $ip_filter) {
            die();
        }

	    $name = basename($camera_url);
	    if (strpos($name, '.jpg') !== false && strpos($name, '.png') !== false) {
	        $name .= '.png';
        }
		$ch = curl_init($camera_url);
		$image = wp_upload_dir()['basedir'] . "/camera/{$name}";
		$fp = fopen($image, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
        
		
		$image_url = wp_upload_dir()['baseurl'] . "/camera/{$name}";
	    ?>

        <style type="text/css">
            body {
                background-image: url(<?php echo $image_url ?>);
                background-size:cover;
            }
            .site-footer {
                display: none;
            }
        </style>
		
		<?php
	}
	
	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		
	    //Retrieve URL from old instance
		if ( isset( $instance['camera_url'] ) ) {
			$camera_url = $instance['camera_url'];
        }
		else {
		    $camera_url = "";
        }

        if ( isset( $instance['ip_filter'] ) ) {
            $ip_filter = $instance['ip_filter'];
        }
        else {
            $ip_filter = "";
        }
        
		?>
        
		<input type="text" name="<?php echo $this->get_field_name('camera_url'); ?>" value="<?php echo $camera_url ?>">
		<label for="ip_filter">Zabezpečiť prístup len z IP:</label>
        <input type="text" name="<?php echo $this->get_field_name('ip_filter'); ?>" value="<?php echo $ip_filter ?>" />
		<?php
	}
	
	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['camera_url'] = $new_instance['camera_url'];
		$instance['ip_filter'] = $new_instance['ip_filter'];

		return $instance;
	}
}