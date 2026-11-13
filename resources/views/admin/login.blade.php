<!doctype html>
<html lang="fa" dir="rtl">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>ورود مدیر | {{ config('app.name', 'Bestoon') }}</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
		<link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face-FD.css" rel="stylesheet">
		<style>
			body { background-color: #f8f9fa; font-family: 'Vazir FD', 'Vazir', sans-serif; }
			.card { border-radius: .75rem; }
		</style>
	</head>
	<body>
		<div class="container py-5">
			<div class="row justify-content-center">
				<div class="col-12 col-md-6 col-lg-4">
					<div class="text-center mb-4">
						<h1 class="h4 fw-bold">پنل مدیریت</h1>
						<p class="text-muted mb-0">لطفاً وارد حساب کاربری خود شوید</p>
					</div>
					<div class="card shadow-sm">
						<div class="card-body p-4">
							@if ($errors->any())
								<div class="alert alert-danger">
									<ul class="mb-0">
										@foreach ($errors->all() as $error)
											<li>{{ $error }}</li>
										@endforeach
									</ul>
								</div>
							@endif
							<form method="POST" action="{{ route('admin.login.submit') }}">
								@csrf
								<div class="mb-3">
									<label for="email" class="form-label">ایمیل</label>
									<input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
								</div>
								<div class="mb-3">
									<label for="password" class="form-label">رمز عبور</label>
									<input type="password" class="form-control" id="password" name="password" required>
								</div>
								<div class="mb-3 form-check">
									<input type="checkbox" class="form-check-input" id="remember" name="remember">
									<label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
								</div>
								<button type="submit" class="btn btn-primary w-100">ورود</button>
							</form>
						</div>
					</div>
					<div class="text-center mt-3">
						<small class="text-muted">© {{ date('Y') }} {{ config('app.name', 'Bestoon') }}</small>
					</div>
				</div>
			</div>
		</div>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	</body>
</html>


