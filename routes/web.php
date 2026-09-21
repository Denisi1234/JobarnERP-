<?php

use Illuminate\Support\Facades\Route;
use App\Filament\App\Pages\Auth\Register;

Route::group(['middleware' => 'redirect.if.not.installed'], function () {
    Route::get('register', Register::class)
        ->name('filament.app.auth.register')
        ->middleware('signed');
});

// Health check endpoint — HARDENED no disclosure
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Throwable $e) {
        $dbStatus = 'unavailable';
    }
    return response()->json([
        'status' => 'healthy',
        'database' => $dbStatus,
        'timestamp' => now()->toIso8601String(),
        'app' => config('app.name', 'FrontDesk'),
    ]);
});

// Dedicated login POST route (handles native HTML form submissions & fallback)
Route::post('/login', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email') ?? $request->input('data.email');
    $password = $request->input('password') ?? $request->input('data.password');
    $remember = (bool) ($request->input('remember') ?? $request->input('data.remember', false));
    $selectedRole = $request->input('role') ?? $request->input('data.role');

    if (!$email || !$password) {
        return back()->withErrors(['email' => 'Please enter both email and password.'])->withInput();
    }

    if (\Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
        $request->session()->regenerate();
        $user = \Illuminate\Support\Facades\Auth::user();

        // A user's assigned role is authoritative.  The legacy is_admin flag
        // must never move an IT, Sales, or Reception user into the admin panel.
        $targetRole = $user->role ?? 'reception';
        return match ($targetRole) {
            'reception' => redirect()->route('reception.dashboard'),
            'it' => redirect()->route('it.index'),
            'sales' => redirect()->route('sales.index'),
            'manager' => redirect()->route('manager.index'),
            default => redirect()->route('reception.dashboard'),
        };
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->withInput($request->only('email', 'data.email'));
})->name('login.post');

// Dedicated logout route
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('filament.app.auth.logout');
Route::post('/app/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware(['auth'])->group(function () {
    // Keep old bookmarks working, but always send users to the logbook inside
    // their own portal rather than a shared, context-free URL.
    Route::get('/work-reports', function (\Illuminate\Http\Request $request) {
        return match ($request->user()->role ?? 'reception') {
            'it' => redirect()->route('it.logbook'),
            'sales' => redirect()->route('sales.logbook'),
            'manager' => redirect()->route('manager.logbooks'),
            default => redirect()->route('reception.logbook'),
        };
    })->name('work-reports.index');
    Route::get('/reception/logbook', [\App\Http\Controllers\WorkReportController::class, 'index'])->name('reception.logbook');
    Route::get('/it/logbook', [\App\Http\Controllers\WorkReportController::class, 'index'])->name('it.logbook');
    Route::get('/sales/logbook', [\App\Http\Controllers\WorkReportController::class, 'index'])->name('sales.logbook');
    Route::get('/manager/logbooks', [\App\Http\Controllers\WorkReportController::class, 'index'])->name('manager.logbooks');
    Route::post('/work-reports', [\App\Http\Controllers\WorkReportController::class, 'store'])->name('work-reports.store');
    Route::post('/work-reports/{workReport}/review', [\App\Http\Controllers\WorkReportController::class, 'review'])->name('work-reports.review');
});

// 1. Reception Portal — FrontDesk OS — HARDENED role+throttle
Route::prefix('reception')->name('reception.')->middleware(['auth','role:reception'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Reception\ReceptionController::class, 'dashboard'])->name('dashboard');
    Route::get('/visitors', [\App\Http\Controllers\Reception\ReceptionController::class, 'visitors'])->name('visitors');
    Route::get('/visitors/export', [\App\Http\Controllers\Reception\ReceptionController::class, 'exportVisitors'])->name('visitors.export');
    
    // Visitor check-in/out API — HARDENED throttle + role via group
    Route::post('/api/checkin', [\App\Http\Controllers\Reception\VisitorCheckinController::class, 'store'])->middleware('throttle:30,1')->name('api.checkin');
    Route::post('/api/tickets', [\App\Http\Controllers\Reception\VisitorCheckinController::class, 'storeTicket'])->middleware('throttle:30,1')->name('api.tickets.store');
    Route::post('/api/checkout', [\App\Http\Controllers\Reception\VisitorCheckinController::class, 'checkout'])->middleware('throttle:30,1')->name('api.checkout');
    Route::post('/api/sms/send', [\App\Http\Controllers\Reception\VisitorCheckinController::class, 'sendCustomSms'])->middleware('throttle:10,1')->name('api.sms.send');
    Route::post('/api/checkin/test', [\App\Http\Controllers\Reception\VisitorCheckinController::class, 'testSms'])->middleware('throttle:10,1')->name('api.checkin.test');
    Route::get('/api/search', [\App\Http\Controllers\Reception\GlobalSearchController::class, 'index'])->middleware('throttle:60,1')->name('api.search');
    
    Route::get('/directory', function () { 
        $employees = \App\Models\Employee::with(['department', 'designation'])->get();
        $departments = \App\Models\Department::all();
        return view('reception.directory.index', [
            'title' => 'Staff Directory & Extensions',
            'employees' => $employees,
            'departments' => $departments,
        ]); 
    })->name('directory');
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages');
    Route::get('/tasks', [\App\Http\Controllers\Reception\ReceptionController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/export', [\App\Http\Controllers\Reception\ReceptionController::class, 'exportTasks'])->name('tasks.export');
    Route::post('/tasks', [\App\Http\Controllers\Reception\ReceptionController::class, 'storeTask'])->name('tasks.store');
    Route::get('/tasks/{id}', [\App\Http\Controllers\Reception\ReceptionController::class, 'showTask'])->name('tasks.show');
    Route::post('/tasks/{id}/status', [\App\Http\Controllers\Reception\ReceptionController::class, 'updateTaskStatus'])->name('tasks.status');
    Route::post('/tasks/{id}/verify', [\App\Http\Controllers\Reception\ReceptionController::class, 'verifyTask'])->name('tasks.verify');
    Route::delete('/tasks/{id}', [\App\Http\Controllers\Reception\ReceptionController::class, 'deleteTask'])->name('tasks.destroy');
    Route::get('/deliveries', function () { return view('reception.deliveries.index', ['title' => 'Deliveries & Packages']); })->name('deliveries');
    Route::get('/settings', function () { return view('reception.settings.index', ['title' => __('reception.settings_title')]); })->name('settings');
    Route::get('/language/{locale}', function ($locale) {
        if (in_array($locale, ['en', 'sw'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }
        return redirect()->back();
    })->name('language.switch')->withoutMiddleware(['auth']);
    Route::post('/language', function (\Illuminate\Http\Request $request) {
        $locale = $request->input('locale', 'en');
        if (in_array($locale, ['en', 'sw'])) {
            session(['locale' => $locale]);
        }
        return redirect()->back()->with('success', __('reception.saved'));
    })->name('language.update')->withoutMiddleware(['auth']);
});

// 2. IT Service Portal — HARDENED
Route::prefix('it')->name('it.')->middleware(['auth','role:it'])->group(function () {
    Route::get('/settings', function () { return view('reception.settings.index', ['title' => __('reception.settings_title')]); })->name('settings');
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages');
    Route::get('/', [\App\Http\Controllers\ItPortalController::class, 'index'])->name('index');
    Route::get('/tickets/export', [\App\Http\Controllers\ItPortalController::class, 'exportTickets'])->name('tickets.export');
    Route::get('/tickets/{id}', [\App\Http\Controllers\ItPortalController::class, 'show'])->name('tickets.show');
    Route::post('/tickets', [\App\Http\Controllers\ItPortalController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{id}/accept', [\App\Http\Controllers\ItPortalController::class, 'accept'])->name('tickets.accept');
    Route::post('/tickets/{id}/start', [\App\Http\Controllers\ItPortalController::class, 'start'])->name('tickets.start');
    Route::post('/tickets/{id}/update', [\App\Http\Controllers\ItPortalController::class, 'updateWorkspace'])->name('tickets.update');
    Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\ItPortalController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{id}/return', [\App\Http\Controllers\ItPortalController::class, 'returnTicket'])->name('tickets.return');
    Route::post('/tickets/{id}/assign', [\App\Http\Controllers\ItPortalController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{id}/reassign', [\App\Http\Controllers\ItPortalController::class, 'reassign'])->name('tickets.reassign');
});

Route::prefix('sales')->name('sales.')->middleware(['auth','role:sales'])->group(function () {
    Route::get('/settings', function () { return view('reception.settings.index', ['title' => __('reception.settings_title')]); })->name('settings');
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages');
    Route::get('/', [\App\Http\Controllers\SalesPortalController::class, 'index'])->name('index');
    Route::get('/history', [\App\Http\Controllers\SalesPortalController::class, 'history'])->name('history');
    Route::get('/inventory', [\App\Http\Controllers\SalesPortalController::class, 'inventory'])->name('inventory');
    Route::get('/inventory/export', [\App\Http\Controllers\SalesPortalController::class, 'exportInventory'])->name('inventory.export');
    Route::get('/products/create', [\App\Http\Controllers\SalesPortalController::class, 'createProduct'])->name('products.create');
    Route::get('/products/{id}', [\App\Http\Controllers\SalesPortalController::class, 'showProduct'])->name('products.show');
    Route::get('/products/{id}/edit', [\App\Http\Controllers\SalesPortalController::class, 'editProduct'])->name('products.edit');
    Route::get('/suppliers', [\App\Http\Controllers\SalesPortalController::class, 'suppliers'])->name('suppliers.index');
    Route::post('/suppliers', [\App\Http\Controllers\SalesPortalController::class, 'storeSupplier'])->name('suppliers.store');
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroySupplier'])->name('suppliers.destroy');
    Route::post('/suppliers/{id}/message', [\App\Http\Controllers\SalesPortalController::class, 'sendSupplierMessage'])->middleware('throttle:10,1')->name('suppliers.message');
    Route::delete('/suppliers/messages/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroySupplierMessage'])->name('suppliers.messages.destroy');
    Route::get('/debits', [\App\Http\Controllers\SalesPortalController::class, 'debits'])->name('debits.index');
    Route::post('/debits', [\App\Http\Controllers\SalesPortalController::class, 'storeDebit'])->name('debits.store');
    Route::post('/debits/{id}/pay', [\App\Http\Controllers\SalesPortalController::class, 'payDebit'])->name('debits.pay');
    Route::delete('/debits/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroyDebit'])->name('debits.destroy');
    Route::post('/debits/{id}/message', [\App\Http\Controllers\SalesPortalController::class, 'sendDebitMessage'])->middleware('throttle:10,1')->name('debits.message');
    Route::delete('/debits/messages/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroyDebitMessage'])->name('debits.messages.destroy');
    Route::get('/tasks', [\App\Http\Controllers\Reception\ReceptionController::class, 'tasks'])->name('tasks.index');
    Route::post('/tasks', [\App\Http\Controllers\Reception\ReceptionController::class, 'storeTask'])->name('tasks.store');
    Route::get('/tasks/{id}', [\App\Http\Controllers\Reception\ReceptionController::class, 'showTask'])->name('tasks.show');
    Route::post('/tasks/{id}/status', [\App\Http\Controllers\Reception\ReceptionController::class, 'updateTaskStatus'])->name('tasks.status');
    Route::post('/tasks/{id}/verify', [\App\Http\Controllers\Reception\ReceptionController::class, 'verifyTask'])->name('tasks.verify');
    Route::delete('/tasks/{id}', [\App\Http\Controllers\Reception\ReceptionController::class, 'deleteTask'])->name('tasks.destroy');
    Route::get('/tasks/export', [\App\Http\Controllers\Reception\ReceptionController::class, 'exportTasks'])->name('tasks.export');
    Route::post('/checkout', [\App\Http\Controllers\SalesPortalController::class, 'checkout'])->name('checkout');
    Route::post('/invoices/{id}/pay', [\App\Http\Controllers\SalesPortalController::class, 'pay'])->name('invoices.pay');
    Route::get('/invoices/{id}/receipt', [\App\Http\Controllers\SalesPortalController::class, 'receipt'])->name('invoices.receipt');
    Route::get('/invoices/export', [\App\Http\Controllers\SalesPortalController::class, 'exportInvoices'])->name('invoices.export');
    Route::post('/it-tickets/{id}/charge', [\App\Http\Controllers\SalesPortalController::class, 'chargeItTicket'])->name('it.charge');
    Route::post('/products', [\App\Http\Controllers\SalesPortalController::class, 'storeProduct'])->name('products.store');
    Route::post('/products/{id}/stock', [\App\Http\Controllers\SalesPortalController::class, 'updateStock'])->name('products.stock');
    Route::post('/products/{id}/adjust', [\App\Http\Controllers\SalesPortalController::class, 'adjustStock'])->name('products.adjust');
    Route::delete('/products/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroyProduct'])->name('products.destroy');
    Route::post('/leads', [\App\Http\Controllers\SalesPortalController::class, 'storeLead'])->name('leads.store');
    Route::post('/leads/{id}/status', [\App\Http\Controllers\SalesPortalController::class, 'updateLeadStatus'])->name('leads.status');
    Route::post('/leads/{id}/convert', [\App\Http\Controllers\SalesPortalController::class, 'convertLead'])->name('leads.convert');
    Route::post('/quotations', [\App\Http\Controllers\SalesPortalController::class, 'storeQuotation'])->name('quotations.store');
    Route::post('/quotations/{id}/duplicate', [\App\Http\Controllers\SalesPortalController::class, 'duplicateQuotation'])->name('quotations.duplicate');
    Route::get('/quotations/{id}', [\App\Http\Controllers\SalesPortalController::class, 'showQuotation'])->name('quotations.show');
    Route::get('/quotations/{id}/print', [\App\Http\Controllers\SalesPortalController::class, 'printQuotation'])->name('quotations.print');
    Route::get('/invoices/{id}/print', [\App\Http\Controllers\SalesPortalController::class, 'printInvoice'])->name('invoices.print');
    Route::post('/quotations/{id}/accept', [\App\Http\Controllers\SalesPortalController::class, 'acceptQuotation'])->name('quotations.accept');
    Route::post('/quotations/{id}/to-proforma', [\App\Http\Controllers\SalesPortalController::class, 'convertToProforma'])->name('quotations.to_proforma');
    Route::post('/quotations/{id}/to-order', [\App\Http\Controllers\SalesPortalController::class, 'convertToSalesOrder'])->name('quotations.to_order');
    Route::post('/quotations/{id}/to-invoice', [\App\Http\Controllers\SalesPortalController::class, 'convertToInvoice'])->name('quotations.to_invoice');
    Route::post('/quotations/{id}/status', [\App\Http\Controllers\SalesPortalController::class, 'updateQuotationStatus'])->name('quotations.status');
    Route::delete('/quotations/{id}', [\App\Http\Controllers\SalesPortalController::class, 'destroyQuotation'])->name('quotations.destroy');
    Route::get('/api/customer-credit', [\App\Http\Controllers\SalesPortalController::class, 'getCustomerCredit'])->name('api.customer_credit');
    Route::get('/api/live-stats', [\App\Http\Controllers\SalesPortalController::class, 'liveStats'])->name('api.live_stats');
});

// 4. Executive Manager Portal — HARDENED
Route::prefix('manager')->name('manager.')->middleware(['auth','role:manager'])->group(function () {
    Route::get('/settings', function () { return view('reception.settings.index', ['title' => __('reception.settings_title')]); })->name('settings');
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages');
    Route::get('/', [\App\Http\Controllers\ManagerPortalController::class, 'index'])->name('index');
    Route::get('/export', [\App\Http\Controllers\ManagerPortalController::class, 'exportReport'])->name('export');
    Route::get('/approvals', [\App\Http\Controllers\ManagerPortalController::class, 'approvals'])->name('approvals');
    Route::post('/approvals', [\App\Http\Controllers\ManagerPortalController::class, 'storeApproval'])->name('approvals.store');
    Route::post('/approvals/{approval}/decision', [\App\Http\Controllers\ManagerPortalController::class, 'decideApproval'])->name('approvals.decision');
    Route::get('/staff', [\App\Http\Controllers\ManagerPortalController::class, 'staff'])->name('staff');
    Route::post('/staff', [\App\Http\Controllers\ManagerPortalController::class, 'storeStaff'])->name('staff.store');
    Route::post('/staff/link-account', [\App\Http\Controllers\ManagerPortalController::class, 'linkStaffAccount'])->name('staff.link-account');

    Route::get('/audit', [\App\Http\Controllers\ManagerPortalController::class, 'audit'])->name('audit');
    Route::get('/tasks', [\App\Http\Controllers\ManagerPortalController::class, 'tasks'])->name('tasks');
    Route::post('/tasks/bulk', [\App\Http\Controllers\ManagerPortalController::class, 'bulkStore'])->name('tasks.bulk');
    Route::get('/tasks/{id}', [\App\Http\Controllers\Reception\ReceptionController::class, 'showTask'])->name('tasks.show');
    Route::post('/tasks/{id}/verify', [\App\Http\Controllers\Reception\ReceptionController::class, 'verifyTask'])->name('tasks.verify');
    Route::post('/task-templates', [\App\Http\Controllers\ManagerPortalController::class, 'storeTemplate'])->name('task-templates.store');
    Route::post('/task-templates/{id}/use', [\App\Http\Controllers\ManagerPortalController::class, 'useTemplate'])->name('task-templates.use');
    Route::delete('/task-templates/{id}', [\App\Http\Controllers\ManagerPortalController::class, 'destroyTemplate'])->name('task-templates.destroy');
});

// Shared task workspace actions (checklist, subtasks, dependencies, details, documents)
Route::prefix('tasks')->name('tasks.')->middleware(['auth'])->group(function () {
    Route::post('/{id}/checklist', [\App\Http\Controllers\Reception\ReceptionController::class, 'storeChecklist'])->name('checklist.store');
    Route::post('/{id}/checklist/{item}/toggle', [\App\Http\Controllers\Reception\ReceptionController::class, 'toggleChecklist'])->name('checklist.toggle');
    Route::delete('/{id}/checklist/{item}', [\App\Http\Controllers\Reception\ReceptionController::class, 'destroyChecklist'])->name('checklist.destroy');
    Route::post('/{id}/subtasks', [\App\Http\Controllers\Reception\ReceptionController::class, 'storeSubtask'])->name('subtasks.store');
    Route::post('/{id}/dependencies', [\App\Http\Controllers\Reception\ReceptionController::class, 'storeDependency'])->name('dependencies.store');
    Route::delete('/{id}/dependencies/{depId}', [\App\Http\Controllers\Reception\ReceptionController::class, 'destroyDependency'])->name('dependencies.destroy');
    Route::put('/{id}/details', [\App\Http\Controllers\Reception\ReceptionController::class, 'updateDetails'])->name('details.update');
    Route::post('/{id}/attachments', [\App\Http\Controllers\Reception\ReceptionController::class, 'uploadTaskAttachments'])->name('attachments.store');
    Route::delete('/{id}/attachments/{index}', [\App\Http\Controllers\Reception\ReceptionController::class, 'deleteTaskAttachment'])->name('attachments.destroy');
});

// Messages — 4-portal direct + department (many customers via manager) — HARDENED: throttle, auth
Route::middleware(['auth','throttle:60,1'])->group(function () {
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::get('/api/messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('messages.unread');
    Route::post('/api/messages', [\App\Http\Controllers\MessageController::class, 'store'])->middleware('throttle:30,1')->name('messages.store');
    Route::post('/messages/{id}/read', [\App\Http\Controllers\MessageController::class, 'markRead'])->name('messages.read');
    Route::post('/messages/read-all', [\App\Http\Controllers\MessageController::class, 'markAllRead'])->name('messages.readAll');
});

// Notifications — HARDENED POST only + throttle
Route::middleware(['auth','throttle:60,1'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications/live', [\App\Http\Controllers\NotificationController::class, 'liveHeader'])->middleware('throttle:30,1')->name('notifications.live');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// Settings — password change for all 4 portals
Route::post('/settings/password', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'current_password' => ['required','current_password'],
        'password' => ['required','string','min:8','confirmed'],
    ]);
    $request->user()->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
    return back()->with('success', 'Password changed successfully');
})->middleware(['auth','throttle:10,1'])->name('settings.password.update');

// 5. Live Handoff — HARDENED throttle
Route::prefix('api/live')->middleware(['auth','throttle:60,1'])->group(function () {
    Route::get('/state', [\App\Http\Controllers\LiveHandoffController::class, 'state'])->middleware('throttle:30,1')->name('live.state');
    Route::post('/visits', [\App\Http\Controllers\LiveHandoffController::class, 'storeVisit'])->middleware('throttle:30,1')->name('live.visits.store');
    Route::post('/visits/{id}/services', [\App\Http\Controllers\LiveHandoffController::class, 'addService'])->middleware('throttle:30,1')->name('live.visits.services.store');
    Route::post('/services/{id}/accept', [\App\Http\Controllers\LiveHandoffController::class, 'acceptService'])->middleware('throttle:30,1')->name('live.services.accept');
    Route::post('/services/{id}/complete', [\App\Http\Controllers\LiveHandoffController::class, 'completeService'])->middleware('throttle:30,1')->name('live.services.complete');
    Route::post('/visits/{id}/checkout', [\App\Http\Controllers\LiveHandoffController::class, 'checkout'])->middleware('throttle:30,1')->name('live.visits.checkout');
    Route::get('/visits/{id}/timeline', [\App\Http\Controllers\LiveHandoffController::class, 'timeline'])->middleware('throttle:60,1')->name('live.visits.timeline');
});

// Root Intelligent Gateway: Route authenticated users to their portal, else login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return match ($user->role ?? 'reception') {
            'reception' => redirect()->route('reception.dashboard'),
            'it' => redirect()->route('it.index'),
            'sales' => redirect()->route('sales.index'),
            'manager' => redirect()->route('manager.index'),
            default => redirect()->route('reception.dashboard'),
        };
    }
    return redirect('/login');
})->name('filament.app.pages.dashboard');
