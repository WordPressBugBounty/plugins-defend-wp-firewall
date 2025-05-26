<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Access denied by DefendWP</title>
	<?php wp_head(); ?>
</head>

<body style="font-family: Arial, Helvetica, sans-serif;background-color: #D0D5DE;height: 100vh;padding: 50px 10px 10px;box-sizing: border-box;margin: 0;">
	<div class="website-name" style="text-align: center;font-size: 18px;padding: 50px 0 10px;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
	<div style="text-align: center;margin-bottom: 30px;"><a href="<?php echo esc_url( get_site_url() ); ?>" class="homepage-link" style="text-align: center;font-size: 12px;background: #bcc2cd;padding: 5px 9px 5px 7px;border-radius: 15px;text-decoration: none;color: #000;"><svg version="1.1"
				id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0"
				viewBox="0 0 24 24" xml:space="preserve" enable-background="new 0 0 24 24" height="10" width="10"
				style="margin-right:5px;">
				<path
					d="M4.5 12c0 -0.7 0.3 -1.3 0.8 -1.7L16.5 0.5c0.8 -0.7 1.9 -0.6 2.6 0.2 0.6 0.8 0.6 1.9 -0.2 2.5l-9.8 8.6c-0.1 0.1 -0.1 0.2 0 0.3l9.8 8.6c0.8 0.7 0.9 1.8 0.2 2.6s-1.8 0.9 -2.6 0.2l-0.1 -0.1 -11.1 -9.7c-0.5 -0.4 -0.8 -1.1 -0.8 -1.7z"
					fill="#000000" stroke-width="1"></path>
			</svg>Go to Homepage</a></div>
	<main style="background-color: #fff;max-width: 540px;border-radius: 10px;margin: 0 auto 10px;">
		<div style="padding: 20px;">
			<div class="error-msg" style="font-size: 26px;font-weight: bold;"><?php echo esc_html( $data['title'] ); ?></div>
			<div class="error-code" style="color: #888;margin-top: 5px;"><?php echo esc_html( $data['message'] ); ?></div>
			<hr style="border: 0;border-top: 1px solid #eee;margin: 15px 0;">
			<div class="dwp-message" style="font-size: 16px;line-height: 1.4em;">Your request has been blocked by the DefendWP Web Application Firewall. If you are a genuine user being blocked incorrectly, contact the website's administrator.</div>
		</div>
		</div>
	</main>
	<div class="footer" style="text-align: center;max-width: 580px;margin: 20px auto 0;font-size: 14px;">
		<div class="dwp-branding">This website is protected by<br><a href="https://defendwp.org/" target="_blank"
				class="link" style='background-image: url("<?php echo esc_url( DEFEND_WP_FIREWALL_PLUGIN_URL . 'assets/icon.svg' ); ?>");width: 120px;height: 16px;display: block;margin: 5px auto 0;'></a></div>
</body>

</html>
