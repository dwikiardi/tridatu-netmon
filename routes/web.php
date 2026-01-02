<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\cards\CardBasic;
use App\Http\Controllers\user_interface\Accordion;
use App\Http\Controllers\user_interface\Alerts;
use App\Http\Controllers\user_interface\Badges;
use App\Http\Controllers\user_interface\Buttons;
use App\Http\Controllers\user_interface\Carousel;
use App\Http\Controllers\user_interface\Collapse;
use App\Http\Controllers\user_interface\Dropdowns;
use App\Http\Controllers\user_interface\Footer;
use App\Http\Controllers\user_interface\ListGroups;
use App\Http\Controllers\user_interface\Modals;
use App\Http\Controllers\user_interface\Navbar;
use App\Http\Controllers\user_interface\Offcanvas;
use App\Http\Controllers\user_interface\PaginationBreadcrumbs;
use App\Http\Controllers\user_interface\Progress;
use App\Http\Controllers\user_interface\Spinners;
use App\Http\Controllers\user_interface\TabsPills;
use App\Http\Controllers\user_interface\Toasts;
use App\Http\Controllers\user_interface\TooltipsPopovers;
use App\Http\Controllers\user_interface\Typography;
use App\Http\Controllers\extended_ui\PerfectScrollbar;
use App\Http\Controllers\extended_ui\TextDivider;
use App\Http\Controllers\icons\Boxicons;
use App\Http\Controllers\form_elements\BasicInput;
use App\Http\Controllers\form_elements\InputGroups;
use App\Http\Controllers\form_layouts\VerticalForm;
use App\Http\Controllers\form_layouts\HorizontalForm;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\oltmonitor\Olt as TablesOlt;
use App\Http\Controllers\datacust\CustomerController as TablesCustomer;
use App\Http\Controllers\report\CustomerLogController;
use App\Http\Controllers\ticketing\TicketController;
use App\Http\Controllers\report\ReportController;
use App\Http\Controllers\authentications\Login as Login;
use App\Http\Controllers\UserController;

// // Menampilkan halaman login
// Route::get('/login', [Auth::class, 'showLogin'])->name('login');
// Proses login
Route::group(['middleware' => 'guest'], function () {
  Route::post('/auth/process', [Login::class, 'processLogin'])->name('login.process');
  Route::get('/auth/login', [Login::class, 'index'])->name('login');
});


Route::middleware(['auth'])->group(function () {
    // Main Page Route
    Route::get('/', [Analytics::class, 'index'])->name('dashboard-analytics');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    // layout oltmonitor
    Route::get('/oltmonitor/view', [TablesOlt::class, 'index'])->name('view-olt');
    Route::get('/oltmonitor/data', [TablesOlt::class, 'dataOnt'])->name('table-olt');
    Route::post('/oltmonitor/signal', [TablesOlt::class, 'signalOnt'])->name('signal-ont');
    // layput datacust
     Route::get('/datacust/view', [TablesCustomer::class, 'index'])->name('view-customer');
     Route::get('/datacust/data', [TablesCustomer::class, 'show'])->name('show-customer');
     Route::get('/datacust/detail', [TablesCustomer::class, 'detail'])->name('detail-customer');
     Route::post('/datacust/store', [TablesCustomer::class, 'store'])->name('store-customer');
     Route::put('/datacust/update', [TablesCustomer::class, 'update'])->name('update-customer');
     Route::delete('/datacust/delete', [TablesCustomer::class, 'destroy'])->name('delete-customer');
     Route::get('/datacust/pops', [TablesCustomer::class, 'getPops'])->name('get-pops');
    // customer log report
     Route::get('/report/customer-log', [CustomerLogController::class, 'index'])->name('view-customer-log');
     Route::get('/report/customer-log/show', [CustomerLogController::class, 'show'])->name('show-customer-log');
     Route::get('/report/customer-log/customers', [CustomerLogController::class, 'getCustomers'])->name('get-customers');
    // ticketing
     Route::get('/ticketing/view', [TicketController::class, 'index'])->name('view-ticketing');
     Route::get('/ticketing/data', [TicketController::class, 'show'])->name('show-ticketing');
     Route::get('/ticketing/detail', [TicketController::class, 'detail'])->name('detail-ticketing');
     Route::get('/ticketing/api/detail', [TicketController::class, 'getTicketDetail'])->name('get-ticket-detail');
     Route::get('/ticketing/api/replies', [TicketController::class, 'getReplies'])->name('get-ticket-replies');
     Route::post('/ticketing/api/update-rfo', [TicketController::class, 'updateRfo'])->name('update-rfo');
     Route::get('/ticketing/export-rfo/{id}', [TicketController::class, 'exportSingleRfo'])->name('export-rfo-single');
     Route::post('/ticketing/api/reply', [TicketController::class, 'storeReply'])->name('store-ticket-reply');
     Route::post('/ticketing/store', [TicketController::class, 'store'])->name('store-ticketing');
     Route::put('/ticketing/update', [TicketController::class, 'update'])->name('update-ticketing');
     Route::delete('/ticketing/delete', [TicketController::class, 'destroy'])->name('delete-ticketing');
     Route::get('/ticketing/customers', [TicketController::class, 'getCustomers'])->name('get-ticketing-customers');
     Route::get('/tickets/survey-projects', [TicketController::class, 'getSurveyProjects'])->name('get-survey-projects');
     Route::get('/existing-customers-with-survey', [TicketController::class, 'getExistingCustomersWithSurvey'])->name('get-existing-customers-with-survey');
     Route::get('/ticketing/teknisi', [TicketController::class, 'getTeknisi'])->name('get-ticketing-teknisi');
     Route::get('/ticketing/sales', [TicketController::class, 'getSales'])->name('get-ticketing-sales');
     Route::get('/calon-customers', [TicketController::class, 'getCalonCustomers'])->name('get-calon-customers');
     Route::get('/ticketing/{ticketId}', [TicketController::class, 'showDetailPage'])->name('show-ticket-detail-page');
     // Debug route - hapus setelah selesai debugging
     Route::get('/debug/customers', function() {
         $customers = \App\Models\Customer::orderBy('created_at', 'desc')->limit(5)->get();
         return response()->json($customers);
     });
    // report
     Route::get('/report/view', [ReportController::class, 'index'])->name('view-report');
     Route::get('/report/customer/data', [ReportController::class, 'getReportCustomer'])->name('report.customer.data');
     Route::get('/report/maintenance/data', [ReportController::class, 'getReportMaintenance'])->name('report.maintenance.data');
     Route::get('/report/customer/summary', [ReportController::class, 'getSummaryCustomer'])->name('report.customer.summary');
     Route::get('/report/maintenance/summary', [ReportController::class, 'getSummaryMaintenance'])->name('report.maintenance.summary');
     Route::get('/report/sales/users', [ReportController::class, 'getSalesUsers'])->name('report.sales.users');
     Route::post('/report/filter/save', [ReportController::class, 'saveFilterPreference'])->name('report.filter.save');
     Route::get('/report/filters/{type}', [ReportController::class, 'getSavedFilters'])->name('report.filters.get');
     Route::delete('/report/filter/{id}', [ReportController::class, 'deleteFilter'])->name('report.filter.delete');
     Route::get('/report/export/excel', [ReportController::class, 'exportExcel'])->name('report.export.excel');
     Route::get('/report/export/pdf', [ReportController::class, 'exportPdf'])->name('report.export.pdf');
     Route::get('/report/export/rfo', [ReportController::class, 'exportRFO'])->name('report.export.rfo');
    // user management
     Route::get('/user/view', [UserController::class, 'index'])->name('view-user');
     Route::get('/user/data', [UserController::class, 'show'])->name('show-user');
     Route::get('/user/detail', [UserController::class, 'detail'])->name('detail-user');
     Route::post('/user/store', [UserController::class, 'store'])->name('store-user');
     Route::put('/user/update', [UserController::class, 'update'])->name('update-user');
     Route::delete('/user/delete', [UserController::class, 'destroy'])->name('delete-user');
    //logout
    Route::post('/auth/logout', [Login::class, 'logout'])->name('logout');
});
// layout
Route::get('/layouts/without-menu', [WithoutMenu::class, 'index'])->name('layouts-without-menu');
Route::get('/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('layouts-without-navbar');
Route::get('/layouts/fluid', [Fluid::class, 'index'])->name('layouts-fluid');
Route::get('/layouts/container', [Container::class, 'index'])->name('layouts-container');
Route::get('/layouts/blank', [Blank::class, 'index'])->name('layouts-blank');

// pages
Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('pages-account-settings-account');
Route::post('/pages/account-settings-account/update', [AccountSettingsAccount::class, 'update'])->name('pages-account-settings-account-update');
Route::post('/pages/account-settings-account/change-password', [AccountSettingsAccount::class, 'changePassword'])->name('pages-account-settings-account-change-password');
Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('pages-account-settings-notifications');
Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('pages-account-settings-connections');
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');
Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('pages-misc-under-maintenance');

// authentication
// Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('auth-login-basic');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');
Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');

// cards
Route::get('/cards/basic', [CardBasic::class, 'index'])->name('cards-basic');

// User Interface
Route::get('/ui/accordion', [Accordion::class, 'index'])->name('ui-accordion');
Route::get('/ui/alerts', [Alerts::class, 'index'])->name('ui-alerts');
Route::get('/ui/badges', [Badges::class, 'index'])->name('ui-badges');
Route::get('/ui/buttons', [Buttons::class, 'index'])->name('ui-buttons');
Route::get('/ui/carousel', [Carousel::class, 'index'])->name('ui-carousel');
Route::get('/ui/collapse', [Collapse::class, 'index'])->name('ui-collapse');
Route::get('/ui/dropdowns', [Dropdowns::class, 'index'])->name('ui-dropdowns');
Route::get('/ui/footer', [Footer::class, 'index'])->name('ui-footer');
Route::get('/ui/list-groups', [ListGroups::class, 'index'])->name('ui-list-groups');
Route::get('/ui/modals', [Modals::class, 'index'])->name('ui-modals');
Route::get('/ui/navbar', [Navbar::class, 'index'])->name('ui-navbar');
Route::get('/ui/offcanvas', [Offcanvas::class, 'index'])->name('ui-offcanvas');
Route::get('/ui/pagination-breadcrumbs', [PaginationBreadcrumbs::class, 'index'])->name('ui-pagination-breadcrumbs');
Route::get('/ui/progress', [Progress::class, 'index'])->name('ui-progress');
Route::get('/ui/spinners', [Spinners::class, 'index'])->name('ui-spinners');
Route::get('/ui/tabs-pills', [TabsPills::class, 'index'])->name('ui-tabs-pills');
Route::get('/ui/toasts', [Toasts::class, 'index'])->name('ui-toasts');
Route::get('/ui/tooltips-popovers', [TooltipsPopovers::class, 'index'])->name('ui-tooltips-popovers');
Route::get('/ui/typography', [Typography::class, 'index'])->name('ui-typography');

// extended ui
Route::get('/extended/ui-perfect-scrollbar', [PerfectScrollbar::class, 'index'])->name('extended-ui-perfect-scrollbar');
Route::get('/extended/ui-text-divider', [TextDivider::class, 'index'])->name('extended-ui-text-divider');

// icons
Route::get('/icons/boxicons', [Boxicons::class, 'index'])->name('icons-boxicons');

// form elements
Route::get('/forms/basic-inputs', [BasicInput::class, 'index'])->name('forms-basic-inputs');
Route::get('/forms/input-groups', [InputGroups::class, 'index'])->name('forms-input-groups');

// form layouts
Route::get('/form/layouts-vertical', [VerticalForm::class, 'index'])->name('form-layouts-vertical');
Route::get('/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('form-layouts-horizontal');

// tables
Route::get('/tables/basic', [TablesBasic::class, 'index'])->name('tables-basic');

// Auth::routes();
