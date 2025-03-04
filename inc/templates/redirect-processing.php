<?php if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

?>
<!doctype html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
	<style type="text/css" media="screen">
		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		body.wprus-page {
			font-size: 18px;
			font-family: sans-serif;
			line-height: 1.5;
			color: #6d6d6d;
		}

		#wpadminbar {
			display: none;
		}

		.wprus-inner {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 999999;
			background: #eeeeee;
			overflow-y: scroll;
		}

		.wprus-wrapper {
			width: 100%;
			max-width: 360px;
			margin: auto;
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			z-index: 99999;
		}

		.wprus-wait {
			background: #ffffff;
			max-width: 360px;
			min-width: 320px;
			width: 100%;
			padding: 45px 35px;
			text-align: center;
		}

		.wprus-wait-error {
			display: none;
		}

		@media screen and (max-width: 375px) {
			.wprus-wrapper {
				max-width: 100%;
			}

			.wprus-inner {
				background: #ffffff;
			}

			.wprus-wait {
				max-width: 100%;
				top: initial;
				bottom: initial;
				transform: none;
				left: initial;
				right: initial;
				box-shadow: none;
			}
		}
	</style>
</head>

<body class="wprus-page">
	<div class="wprus-inner">
		<div class="wprus-wrapper">
			<div id="wprus_wait" class="wprus-wait">
				<div class="wprus-wait-message"></div>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			var messageElement = document.querySelector('.wprus-wait-message');
			var dots = 0;
			setInterval(function() {
				var text = '<?php esc_html_e('Processing', 'wprus'); ?>';
				for (var i = 0; i < dots; i++) {
					text += '.';
				}
				messageElement.textContent = text;
				dots = (dots + 1) % 4; // Cycle through 0, 1, 2, 3
			}, 1000); // Change every second
		});
	</script>
</body>

</html>