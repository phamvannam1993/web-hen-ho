<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="robots" content="noodp,noindex,nofollow" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>404 Page Not Found</title>
	<style>
		.error_main {
			display: flex;
			max-height: 100vh;
			align-items: center;
		}

		.error404 {
			align-items: center;
			display: flex;
			max-width: 90%;
			padding: 100px 0;
			margin: 0 auto;
			text-align: center;
		}

		.img_error img {
			width: 480px;
		}

		.content_error h3 {
			color: #30425B;
			font-size: 48px;
			font-weight: 500;
			line-height: 57px;
			max-width: 460px;
		}

		.list_contact {
			display: flex;
			justify-content: center;
		}

		.item_contact {
			margin: 0 10px;
		}

		.item_contact p {
			color: #666;
			font-size: 12px;
			line-height: 17px;
			margin-bottom: 5px;
		}

		.item_contact a {
			background: #2a5b84;
			font-size: 16px;
			font-weight: bold;
			padding: 5px 0;
			border-radius: 20px;
			justify-content: center;
			text-align: center;
			display: flex;
			align-items: center;
			width: 205px;
			text-decoration: none;
			color: #fff;
			height: 30px;
		}

		.item_contact img {
			width: 20px;
			background: #fff;
			border-radius: 50%;
		}

		.text_back {
			color: #fff !important;
			padding: 0 !important;
			margin: 0 0 0 5px !important;
			font-size: 16px !important;
			font-weight: bold;
		}

		@media only screen and (max-width: 768px) {
			.error404 {
				flex-direction: column;
				padding: 40px 20px 30px;
				text-align: center;
			}

			.img_error img {
				width: 280px;
			}

			.content_error h3 {
				color: #30425B;
				font-size: 20px;
				font-weight: 500;
				line-height: 24px;
				margin: 20px auto;
				max-width: 250px;
			}
		}

		@media only screen and (max-width: 540px) {
			.error_main {
				min-height: auto;
			}

			.error404 {
				padding: 40px 0 30px;
			}

			.item_contact a {
				width: 140px;
				font-size: 12px;
				padding: 2px 5px;
			}

			.text_back {
				font-size: 12px !important;
			}
		}
	</style>
</head>

<body>
	<div class="error_main">
		<div class="error404">
			<div class="img_error">
				<img src="/images/404.png" alt="image 404">
			</div>
			<div class="content_error">
				<h3>Xin lỗi, chúng tôi không tìm thấy trang bạn cần!</h3>
				<div class="list_contact">
					<div class="item_contact">
						<p>Trở về trang chủ<br>Vnesports.vn</p>
						<a href="/">
							<img src="/images/favicon.png" alt="logo">
							<p class="text_back">Vnesports.vn</p>
						</a>
					</div>
					<div class="item_contact">
						<p>Liên hệ hỗ trợ miễn phí<br>(7h30 - 22h)</p>
						<a href="#">vnesports@gmail.com</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

</html>