<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>جزئیات کاربر: {{ $user->name }} | بده بسون</title>
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
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .stat-card.primary .icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.success .icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .stat-card.danger .icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stat-card.warning .icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            color: #2d3748;
        }
        .stat-card p {
            margin: 0;
            color: #718096;
            font-size: 0.9rem;
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
        .user-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: bold;
        }
        .pagination {
            margin: 0;
        }
        .category-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            margin: 0.25rem;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-arrow-right-circle"></i> بازگشت به داشبورد
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
        <!-- User Header -->
        <div class="user-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="user-avatar">
                        {{ mb_substr($user->name, 0, 2) }}
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-2">{{ $user->name }}</h2>
                    <p class="mb-1 text-muted"><i class="bi bi-envelope"></i> {{ $user->email }}</p>
                    @if($user->phone)
                        <p class="mb-1 text-muted"><i class="bi bi-phone"></i> {{ $user->phone }}</p>
                    @endif
                    <p class="mb-0 text-muted"><i class="bi bi-calendar"></i> عضو از {{ $user->created_at->format('Y/m/d') }}</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="icon">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <h3>{{ number_format($transactionsCount) }}</h3>
                    <p>تراکنش‌ها</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="icon">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <h3>{{ number_format($totalIncome) }}</h3>
                    <p>کل درآمد (تومان)</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card danger">
                    <div class="icon">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <h3>{{ number_format($totalExpense) }}</h3>
                    <p>کل هزینه (تومان)</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h3>{{ number_format($totalIncome - $totalExpense) }}</h3>
                    <p>خالص (تومان)</p>
                </div>
            </div>
        </div>

        <!-- Categories and Budgets -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="table-card">
                    <h5><i class="bi bi-tag-fill text-primary"></i> دسته‌بندی‌ها ({{ $categoriesCount }})</h5>
                    @if($categories->count() > 0)
                        <div>
                            @foreach($categories as $category)
                                <span class="category-badge">
                                    <i class="bi bi-tag"></i> {{ $category->name }}
                                    <span class="badge bg-{{ $category->type === 'income' ? 'success' : 'danger' }} ms-1">
                                        {{ $category->type === 'income' ? 'درآمد' : 'هزینه' }}
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            دسته‌بندی وجود ندارد
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <div class="table-card">
                    <h5><i class="bi bi-wallet text-warning"></i> بودجه‌ها ({{ $budgetsCount }})</h5>
                    @if($budgets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>نام</th>
                                        <th>مبلغ</th>
                                        <th>هزینه شده</th>
                                        <th>باقی‌مانده</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgets as $budget)
                                    <tr>
                                        <td><strong>{{ $budget->name }}</strong></td>
                                        <td><small>{{ number_format($budget->amount) }}</small></td>
                                        <td><small class="text-danger">{{ number_format($budget->spent) }}</small></td>
                                        <td><small class="text-success">{{ number_format($budget->amount - $budget->spent) }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            بودجه‌ای وجود ندارد
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        @if($categoryBreakdown->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-card">
                    <h5><i class="bi bi-pie-chart text-info"></i> تحلیل هزینه‌ها بر اساس دسته‌بندی</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>دسته‌بندی</th>
                                    <th>تعداد تراکنش</th>
                                    <th>مجموع هزینه</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryBreakdown as $index => $item)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td><i class="bi bi-tag"></i> <strong>{{ $item['name'] }}</strong></td>
                                    <td><span class="badge bg-info">{{ $item['count'] }} تراکنش</span></td>
                                    <td><strong class="amount-negative">{{ number_format($item['total']) }} تومان</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Transactions Table -->
        <div class="row">
            <div class="col-12">
                <div class="table-card">
                    <h5><i class="bi bi-list-ul text-success"></i> تراکنش‌ها</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>عنوان</th>
                                    <th>دسته‌بندی</th>
                                    <th>نوع</th>
                                    <th>مبلغ</th>
                                    <th>تاریخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $transaction)
                                <tr>
                                    <td><strong>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $index + 1 }}</strong></td>
                                    <td>
                                        <strong>{{ $transaction->title }}</strong>
                                        @if($transaction->description)
                                            <br><small class="text-muted">{{ Str::limit($transaction->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->category)
                                            <span class="badge bg-secondary">{{ $transaction->category->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
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
                                    <td><small class="text-muted">{{ $transaction->date->format('Y/m/d') }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        تراکنشی وجود ندارد
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($transactions->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

