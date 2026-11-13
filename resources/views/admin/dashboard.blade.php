<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>داشبورد مدیریت | بده بسون</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: Vazirmatn, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-container {
            padding: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .stat-card.primary .icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.success .icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stat-card.warning .icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .stat-card.info .icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: #2d3748;
        }
        .stat-card p {
            margin: 0;
            color: #718096;
            font-size: 0.95rem;
        }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .table-card h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #667eea !important;
        }
        .table {
            margin: 0;
        }
        .table thead th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            border: none;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .table tbody tr {
            transition: background 0.2s ease;
        }
        .table tbody tr:hover {
            background: #f7fafc;
        }
        .badge-income {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-expense {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .amount-positive {
            color: #38a169;
            font-weight: 600;
        }
        .amount-negative {
            color: #e53e3e;
            font-weight: 600;
        }
        .summary-box {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .summary-box h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
        .summary-item {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: #f7fafc;
        }
        .summary-item:last-child {
            margin-bottom: 0;
        }
        .summary-item .label {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .clickable-row {
            cursor: pointer;
        }
        .clickable-row:hover {
            background: #edf2f7 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2"></i> داشبورد مدیریت
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">{{ Auth::guard('admin')->user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-left"></i> خروج
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3>{{ number_format($usersCount) }}</h3>
                    <p>تعداد کاربران</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="icon">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <h3>{{ number_format($transactionsCount) }}</h3>
                    <p>تراکنش‌ها</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="icon">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                    <h3>{{ number_format($categoriesCount) }}</h3>
                    <p>دسته‌بندی‌ها</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3>{{ number_format($budgetsCount) }}</h3>
                    <p>بودجه‌ها</p>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="summary-box">
                    <h5><i class="bi bi-cash-stack text-success"></i> خلاصه مالی</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="summary-item">
                                <div class="label">کل درآمد</div>
                                <div class="value amount-positive">
                                    <i class="bi bi-arrow-up-circle-fill"></i>
                                    {{ number_format($totalIncome) }} تومان
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-item">
                                <div class="label">کل هزینه</div>
                                <div class="value amount-negative">
                                    <i class="bi bi-arrow-down-circle-fill"></i>
                                    {{ number_format($totalExpense) }} تومان
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-item" style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);">
                                <div class="label">خالص (درآمد - هزینه)</div>
                                <div class="value {{ ($totalIncome - $totalExpense) >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                    <i class="bi bi-{{ ($totalIncome - $totalExpense) >= 0 ? 'check-circle-fill' : 'exclamation-circle-fill' }}"></i>
                                    {{ number_format($totalIncome - $totalExpense) }} تومان
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="table-card">
                    <h5><i class="bi bi-people text-primary"></i> کاربران اخیر</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام</th>
                                    <th>ایمیل</th>
                                    <th>تلفن</th>
                                    <th>تاریخ عضویت</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentUsers as $index => $user)
                                <tr class="clickable-row" onclick="window.location='{{ route('admin.users.show', $user->id) }}'">
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td><small class="text-muted">{{ $user->email }}</small></td>
                                    <td><small class="text-muted">{{ $user->phone ?? '-' }}</small></td>
                                    <td><small class="text-muted">{{ $user->created_at->format('Y/m/d') }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        کاربری وجود ندارد
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="table-card">
                    <h5><i class="bi bi-clock-history text-success"></i> تراکنش‌های اخیر</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>عنوان</th>
                                    <th>کاربر</th>
                                    <th>نوع</th>
                                    <th>مبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $index => $transaction)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>
                                        <strong>{{ $transaction->title }}</strong>
                                        @if($transaction->category)
                                            <br><small class="text-muted">{{ $transaction->category->name }}</small>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $transaction->user->name ?? '-' }}</small></td>
                                    <td>
                                        <span class="badge-{{ $transaction->type === 'income' ? 'income' : 'expense' }}">
                                            {{ $transaction->type === 'income' ? 'درآمد' : 'هزینه' }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="{{ $transaction->type === 'income' ? 'amount-positive' : 'amount-negative' }}">
                                            {{ number_format($transaction->amount) }}
                                        </strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        تراکنشی وجود ندارد
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
