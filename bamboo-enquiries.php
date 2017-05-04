<?php
/************************************************************************************************************/
/*
Plugin Name: Bamboo Enquiries
Plugin URI:  https://www.bamboomanchester.uk/wordpress/bamboo-enquiries
Author:      Bamboo
Author URI:  https://www.bamboomanchester.uk
Version:     1.9.2
Description: Turn any web form into a flexible enquiry form, enabling you to have multiple enquiry forms throughout your website.
*/
/************************************************************************************************************/

	if( isset( $_POST['bamboo_enquiry_form_to_address'] ) ) {
		bamboo_enquiries_send_enquiry();
	}

/************************************************************************************************************/

	function bamboo_enquiries_enqueue_scripts() {

		$path = plugins_url( '', __FILE__ );

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script(
			'bamboo-scrollTo',
			$path . '/jquery.scrollTo.min.js',
			'jquery',
			null,
			true
		);

		wp_enqueue_script(
			'bamboo-easing',
			$path . '/jquery.easing.min.js',
			'jquery',
			null,
			true
		);

    	wp_enqueue_script(
			'bamboo-enquiries',
			$path . '/bamboo-enquiries.min.js',
			null,
			null,
			true
		);

		wp_enqueue_style(
			'bamboo-enquiries',
			$path . '/bamboo-enquiries.css',
			array(),
			null
		);

	}
	add_action( 'wp_enqueue_scripts', 'bamboo_enquiries_enqueue_scripts' );

/************************************************************************************************************/

	function bamboo_enquiries_do_shortcode( $atts, $content=null ) {

		$from = ( isset( $atts['from'] ) ) ? $atts['from'] : '';
		$to = ( isset( $atts['to'] ) ) ? $atts['to']   : '';
		$auto_labels = ( isset( $atts['auto_labels'] ) ) ? $atts['auto_labels']   : 'off';
		$honeypot = ( isset( $atts['honeypot'] ) ) ? $atts['honeypot']   : 'off';

		do_action( 'before_bamboo_enquiry' );

		$html = "<form enctype=\"multipart/form-data\" class=\"bamboo_enquiry";
		if ( "on" == $auto_labels ) $html.= " auto_labels ";
		$html.= "\" method=\"post\" action=\"\">";
		$html.= "<input type=\"hidden\" name=\"bamboo_enquiry_form_to_address\" value=\"$to\"/>";
		$html.= "<input type=\"hidden\" name=\"bamboo_enquiry_form_from_address\" value=\"$from\"/>";
		$html.= "<input type=\"hidden\" name=\"bamboo_enquiry_form_honeypot\" value=\"$honeypot\"/>";
		if ( "on" == $honeypot ) {
			$html.= "<input type=\"hidden\" name=\"email\"/>";
		}
		$html.= do_shortcode($content);
		$html.= "</form>";

		do_action( 'after_bamboo_enquiry' );

		return $html;

	}
	add_shortcode( 'bamboo-enquiry', 'bamboo_enquiries_do_shortcode' );

/************************************************************************************************************/

	function bamboo_enquiries_send_enquiry() {

		$to_address    	= $_POST["bamboo_enquiry_form_to_address"];    	// ADDRESS TO SEND ENQUIRIES TO
		$from_address  	= $_POST["bamboo_enquiry_form_from_address"];	// ADDRESS TO SEND ENQUIRIES FROM
		$honeypot		= $_POST["bamboo_enquiry_form_honeypot"];		// HONEYPOT INDICATOR ("ON" OR "OFF")
		$reply_address	= $from_address;                               	// DEFAULT REPLY ADDRESS IF ONE IS NOT SUPPLEID
		$subject       	= 'Website Enquiry';                           	// START OF THE EMAIL SUBJECT
		$intro         	= '<p>There has been an enquiry sent from your website, the details are below:</p>'; // INTRO TO THE EMAIL

		// ESTABLISH IF THE FORM IS BLANK
		$all_blank = true;
		foreach ( $_POST as $key => $value ) {
			if ( ( substr( $key, 0, 20) != "bamboo_enquiry_form_" && $key != "undefined" ) && ( $value != '' ) ) {
				$all_blank = false;
			}
		}

		// ESTABLISH IF THE HONEYPOT HAS BEEN TRIGGERED
		$honeypot_triggered = false;
		if( "on"==$honeypot ) {
			$honeypot_value = '';
			if( isset( $_POST["email"] ) ) {
				$honeypot_value = $_POST["email"];
			}
			if( ''!= $honeypot_value ) {
				$honeypot_triggered = true;
			}
		}

		// IF THE FORM ISN'T BLANK AND THE HONEYPOT HASN'T BEEN TRIGGERED WE CAN SEND THE ENQUIRY
		if( ! $all_blank && ! $honeypot_triggered) {

			// GENERATE A RANDOM MIME CONTENT BOUNDARY
			$mime_boundary = uniqid('noodle-enquiries');

			// CONSTRUCT THE HEADERS
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-Type: multipart/mixed;boundary=\"$mime_boundary\"\r\n";
			$headers .= "From: $from_address" . "\r\n";
			$headers .= "Reply-To: $reply_address" . "\r\n";

			// CONSTRUCT THE FORM CONTENT
			$content = '';
			foreach ( $_POST as $key => $value ) {
				if( substr( $key, 0, 20 ) != "bamboo_enquiry_form_" && $key != "undefined" ) {
					if( is_array( $value ) ) {
						$text = '';
						foreach( $value as $val ) {
							if( ''!=$text ) {
								$text.=', ';
							}
							$text.= $val;
						}
					} else {
						$text = $value;
					}
					$content .= "<p><strong>" . str_replace( "_", " ", $key ) . ":</strong>&nbsp;" . $text . "</p>";
				}
			}

			$log_entry = '';
			foreach ( $_POST as $key => $value ) {
				if( substr( $key, 0, 20 ) != "bamboo_enquiry_form_" && $key != "undefined" ) {
					if( is_array( $value ) ) {
						$text = '';
						foreach( $value as $val ) {
							if( ''!=$text ) {
								$text.='; ';
							}
							$text.= $val;
						}
					} else {
						$text = $value;
					}
					$log_entry .= ',' . str_replace(',',';',$text);
				}
			}
			bamboo_enquiries_log_enquiry( $log_entry );

			$file_attached = false;
			foreach ( $_FILES as $key => $value ) {
				if($_FILES[$key]["size"]>0) {
					$file_attached = true;
				}
			}
			if( true==$file_attached ) {
				$content .= "<p><strong>File Attached</strong></p>";
			}

			// WRAP CONTENT IN CONTAINER TAGS
			$content = "<html><head><title>$subject</title></head><body>$intro" . $content;
			$content .= "</body></html>";

			// CONSTRUCT THE MESSAGE
			$message  = "This is a MIME encoded message.";
			$message .= "\r\n\r\n--" . $mime_boundary . "\r\n";
			$message .= "Content-Type: text/html;charset=utf-8\r\n\r\n";
			$message .= $content;
			$message .= "\r\n\r\n--" . $mime_boundary . "\r\n";

			// ADD ANY SUBMITTED FILES
			foreach ( $_FILES as $key => $value ) {
				if($_FILES[$key]["size"]>0) {
					$message .= "Content-Type: {" . $_FILES[$key]["type"] . "}; name=\"" . $_FILES[$key]["name"] . "\"\r\n";
					$message .= "Content-Transfer-Encoding: base64\r\n";
					$message .= "Content-Disposition: attachment;\r\n; filename=\"" . $_FILES[$key]["name"] . "\"\r\n\r\n";
					$message .= chunk_split(base64_encode(file_get_contents($_FILES[$key]["tmp_name"])));
					$message .= "\r\n\r\n--" . $mime_boundary . "\r\n";
				}
			}
echo 'Sending mail...';
			// SEND THE MESSAGE
			mail( $to_address, $subject, $message, $headers );
echo 'Sent';
die();
		}

		// RELOAD THE PAGE
		$url = $_SERVER['HTTP_REFERER'];
		if( 0< strpos( $url, '?' ) ) {
			$url.= '&bamboo_enquiry_sent';
		} else {
			$url.='?bamboo_enquiry_sent';
		}

		echo '<script type="text/javascript">';
       	echo 'window.location = "' . $url . '"';
  		echo '</script>';

	}

/************************************************************************************************************/

	function bamboo_enquiries_log_enquiry( $entry ) {

		$filepath = WP_CONTENT_DIR . '/enquiry_log/';
		if( !file_exists( $filepath ) ) {
			mkdir( $filepath );
		}

		$access_file = $filepath . ".htaccess";
		if( !file_exists( $access_file ) ) {
			file_put_contents( $access_file, "deny from all\n" );
		}

		$filename = get_option( 'bamboo_enquiries_filename' );
		if( !$filename ) {
			$filename = bamboo_enquiries_generate_filename();
			update_option( 'bamboo_enquiries_filename', $filename );
		}

		$page = str_replace( site_url(), '', $_SERVER['HTTP_REFERER'] );
		$entry = $page . $entry;

		$timestamp = date( 'j/n/Y H:i:s' );
		$entry = $timestamp . "," . $entry;

 		$file = fopen( $filepath . $filename, "a");
 		fwrite( $file, $entry . "\r\n");
 		fclose( $file );

		return true;

	}

/************************************************************************************************************/

	function bamboo_enquiries_generate_filename() {

		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$length = 20;

		$filename = '';
		for( $i=1; $i<=$length; $i++ ) {

			$rand = rand( 0, 61 );
			$char = substr( $characters, $rand, 1 );
			$filename .= $char;
		}

		return $filename . '.csv';

	}

/************************************************************************************************************/
?>
