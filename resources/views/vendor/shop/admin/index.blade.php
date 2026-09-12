<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>ASAAN administration</title>

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4/css/font-awesome.min.css">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css">

		<style type="text/css">
			body {
				background: #f5f2ec;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
			}

			.login-wrapper {
				position: absolute;
				left: 50%;
				top: 50%;
				width: 22rem;
				margin: -8rem -11rem;
			}

			.login-wrapper .brand {
				text-align: center;
				font-size: 1.75rem;
				font-weight: 700;
				letter-spacing: 0.15rem;
				color: #1c5b3a;
				margin-bottom: 1.5rem;
			}

			.login-wrapper .brand-logo {
				display: block;
				max-width: 8rem;
				margin: 0 auto 0.5rem;
			}

			.login-wrapper .login {
				background: #fff;
				border-radius: 0.5rem;
				padding: 1.5rem;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
			}

			form.login .input-group-addon {
				font-size: 1.25rem;
				min-width: 3.65rem;
			}
		</style>
	</head>
	<body>

		<div class="login-wrapper">
			<div class="brand">
				<img class="brand-logo" src="{{ asset('aimeos/asaan.png') }}" alt="ASAAN">
				<div>ASAAN</div>
			</div>

			<form class="login" method="POST" action="{{ url('login') }}" >
				{!! csrf_field() !!}
				<div class="form-group input-group input-group-lg">
					<span class="input-group-addon fa fa-at" id="email-addon"></span>
					<input class="form-control" type="email" name="email" required="required" placeholder="Email" value="{{ old('email') }}" aria-describedby="email-addon">
				</div>
				<div class="form-group input-group input-group-lg">
					<span class="input-group-addon fa fa-lock" id="password-addon"></span>
					<input class="form-control" type="password" name="password" required="required" placeholder="Password" aria-describedby="password-addon">
				</div>
				<hr>
				<button class="btn btn-block btn-lg btn-primary" type="submit">Login</button>
			</form>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/js/bootstrap.bundle.min.js"></script>
	</body>
</html>