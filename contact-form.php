<!doctype html>
<html lang="es">
	<head>
		<meta charset="utf-8" />
		<title>Contact Form</title>
		<meta name="description" content="" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="apple-touch-icon" href="apple-touch-icon.png" />
		<link
		rel="shortcut icon"
		type="image/x-icon"
		href="assets/images/favicon.png"
		/>
		<link rel="stylesheet" type="text/css" href="style.css" />
		<script type="text/javascript" src="contact-form.js" defer></script>
	</head>
	<body>

		<form
		id="contact-form"
		method="post"
		action="sendmail.php"
		>
			<fieldset>

				<input
				class="from-control"
				type="text"
				id="name"
				name="name"
				placeholder="Nombre"
				required=""
				/>
				
				<input
				class="from-control"
				type="text"
				id="email"
				name="email"
				placeholder="E-Mail"
				required=""
				/>

				<input
				class="from-control"
				type="text"
				id="phone"
				name="phone"
				placeholder="Teléfono"
				required=""
				/>

				<input
				class="from-control"
				type="text"
				id="mobile"
				name="mobile"
				placeholder="Celular"
				required=""
				/>

				<textarea
				class="from-control"
				id="message"
				name="message"
				placeholder="Su consulta"
				required=""
				></textarea>

				<input
				type="hidden"
				name="token"
				value="<?php echo time(); ?>"
				/>

				<div
				class="cf-turnstile"
				data-sitekey="PUBLIC_TOKEN"
				data-theme="light"
				data-language="es"
				></div>
				<input
						class="submit"
						type="submit"
						value="Enviar"
						/>
			</fieldset>
		</form>
	</body>
</html>