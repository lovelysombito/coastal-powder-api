<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\ColourController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JobScheduleController;
use App\Http\Controllers\LineItemsController;
use App\Http\Controllers\QualityControlController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NonConformanceReportController;
use App\Http\Controllers\PackingSlipController;
use App\Http\Controllers\SearchFilterController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\NcrFailedOptionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => '/auth'], function () {
    Route::post('/login', [LoginController::class, 'login'])->middleware('stateful');
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('stateful');
    Route::post('/verify', [UserController::class, 'verify'])->middleware('stateful');

    Route::group(['middleware' => ['stateful', 'verified', 'auth:sanctum']], function () {

        Route::get('confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
            ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
            ->name('password.confirmation');

        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
            ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
            ->name('password.confirm');

        $twoFactorLimiter = config('fortify.limiters.two-factor');
        Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:' . config('fortify.guard'),
                $twoFactorLimiter ? 'throttle:' . $twoFactorLimiter : null,
            ]));

        $twoFactorMiddleware = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard'), 'password.confirm']
            : [config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')];

        Route::post('two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.enable');

        Route::post('confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.confirm');

        Route::delete('two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.disable');

        Route::get('two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.qr-code');

        // Route::get('two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show'])
        //     ->middleware($twoFactorMiddleware)
        //     ->name('two-factor.secret-key');

        Route::get('two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.recovery-codes');

        Route::post('two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])
            ->middleware($twoFactorMiddleware);
    });
});

Route::group(['middleware'=>['stateful', 'verified', 'auth:sanctum']], function() {

    Route::get('search', [SearchController::class, 'search']);

    Route::group(['prefix'=>'/user'] ,function () {
        Route::get('/', [UserController::class, 'getUser']);
        Route::get('/notification-options', [UserController::class, 'getNotificationOption']);
        Route::patch('/notification-options/{userId}', [UserController::class, 'updateNotificationOption']);
        Route::get('/2factor-auth', [UserController::class, 'get2faAuth']);
        Route::patch('/2factor-auth/{userId}', [UserController::class, 'update2faAuth']);
        Route::get('/password', [UserController::class, 'getUserPassword']);
        Route::patch('/password', [UserController::class, 'updateUserPassword']);

    });

    Route::group(['prefix'=>'/users'] ,function () {
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::post('/', [UserController::class, 'addUser']);
        Route::delete('{userId}', [UserController::class, 'deleteUser']);
        Route::put('/{userId}', [UserController::class, 'updateUser']);
        Route::patch('{userId}', [UserController::class, 'resendVerificationEmail']);
    });

    //only administrator
    Route::group(['middleware' => ['CheckRole:administrator']], function () {

        Route::group(['prefix' => '/users'], function () {
            Route::get('/', [UserController::class, 'getAllUsers']);
            Route::post('/', [UserController::class, 'addUser']);
            Route::delete('/{userId}', [UserController::class, 'deleteUser']);
            Route::put('/{userId}', [UserController::class, 'updateUser']);
            Route::patch('/{userId}', [UserController::class, 'resendVerificationEmail']);
        });

        Route::group(['prefix' => '/integrations'], function () {
            Route::get('/', [IntegrationController::class, 'getAllIntegration']);
            Route::patch('/integration/{integrationId}', [IntegrationController::class, 'updateIntegration']);
        });
        Route::group(['prefix' => 'jobs/scheduled/'], function () {
            Route::get('table/overview', [JobScheduleController::class, 'getJobs']);
        });

        Route::get('/jobs/schedule/date_kanban/overview', [JobScheduleController::class, 'getJobsOverviewDateKanban']);
        Route::get('/jobs/schedule/table/{bay}', [JobScheduleController::class, 'getJobByBay']);
        Route::get('/jobs/schedule/date_kanban/{bay}', [JobScheduleController::class, 'getJobByBayKanban']);
        // Route::get('jobs/dashboard/', [JobScheduleController::class, 'getJobsDashboard']);

        Route::group(['prefix' => '/comments'], function () {
            Route::post('/', [CommentController::class, 'addComment']);
            Route::patch('/{commentId}', [CommentController::class, 'updateComment']);
        });
        
        Route::get('/failed-options', [NcrFailedOptionController::class, 'getFailedOption']);
        Route::group(['prefix' => '/failed-option'], function () {
            Route::post('/', [NcrFailedOptionController::class, 'addFailedOption']);
            Route::patch('/{ncrId}', [NcrFailedOptionController::class, 'editFailedOption']);
            Route::delete('/{ncrId}', [NcrFailedOptionController::class, 'deleteFailedOption']);
        });
    });

    //administrator & supervisor
    // Route::group(['middleware' => ['CheckRole:administrator,supervisor']], function () {
        Route::group(['prefix' => '/products'], function () {
            Route::post('/', [ProductController::class, 'addProduct']);
            Route::get('/', [ProductController::class, 'getAllProducts']);
            Route::post('/product', [ProductController::class, 'addProduct']); // TODO https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/212
            Route::delete('/product/{productId}', [ProductController::class, 'deleteProduct']); // TODO https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/213
            Route::delete('{productId}', [ProductController::class, 'deleteProduct']);
            Route::patch('/product/{productId}', [ProductController::class, 'updateProduct']); // TODO https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/214
            Route::put('{productId}', [ProductController::class, 'updateProduct']);
            Route::post('/export', [ProductController::class, 'exportProducts']);
            Route::post('/import', [ProductController::class, 'importProducts']);
            Route::get('/search',[ProductController::class, 'productSearch']);
        });

        Route::get('/colours', [ColourController::class, 'getAllColours']);
        Route::group(['prefix' => '/colour'], function () {
            Route::post('/', [ColourController::class, 'addColour']);
            Route::patch('/{colourId}', [ColourController::class, 'editColour']);
            Route::delete('/{colourId}', [ColourController::class, 'deleteColour']);
            Route::patch('/change-weight/{colourId}', [ColourController::class, 'changeWeight']);
        });

        Route::group(['prefix' => '/location'], function () {
            Route::get('/', [LocationController::class, 'getAllLocations']);
            Route::post('/', [LocationController::class, 'addLocation']);
            Route::patch('/{locationId}', [LocationController::class, 'editLocation']);
            Route::delete('/{locationId}', [LocationController::class, 'deleteLocation']);
        });

        Route::patch('/job/{jobId}', [JobScheduleController::class, 'updateJobDetail']);
        Route::patch('/job/location/{jobId}', [JobScheduleController::class, 'updateJobLocation']);

       Route::get('/all_holidays', [HolidayController::class, 'getAllHolidays']);
        Route::group(['prefix' => '/holiday'], function () {
            Route::post('/', [HolidayController::class, 'addHoliday']);
            Route::patch('/{holidayId}', [HolidayController::class, 'editHoliday']);
            Route::delete('/{holidayId}', [HolidayController::class, 'deleteHoliday']);
        });
        
        Route::get('/materials', [MaterialController::class, 'getAllMaterial']);

        Route::get('/treatments', [TreatmentController::class, 'getAllTreatment']);
        Route::group(['prefix' => '/treatment'], function () {
            Route::post('/', [TreatmentController::class, 'addTreatment']);
            Route::patch('/{treatmentId}', [TreatmentController::class, 'editTreatment']);
            Route::delete('/{treatmentId}', [TreatmentController::class, 'deleteTreatment']);
        });

        Route::group(['prefix'=>'/integrations'] ,function () {
            Route::get('/', [IntegrationController::class, 'getAllIntegration']);
            Route::group(['prefix'=>'hubspot'], function() {
                Route::post('/callback', [IntegrationController::class, 'hubspotCallback']);
                Route::delete('/', [IntegrationController::class, 'removeHubSpotIntegration']);
            });

            Route::group(['prefix'=>'xero'], function() {
                Route::post('/callback', [IntegrationController::class, 'xeroCallback']);
                Route::delete('/', [IntegrationController::class, 'removeXeroIntegration']);
            });
        });

        Route::group(['prefix' => '/qc'], function () {
            Route::get('passed', [QualityControlController::class, 'getQCPassedJobs']);
            Route::get('pending', [QualityControlController::class, 'getQCPendingJobs']);

            Route::get('/', [QualityControlController::class, 'getAllQualityControl']);
            Route::post('/job/{jobId}', [QualityControlController::class, 'updateQCJobStatus']);
            Route::post('/line/{lineId}', [QualityControlController::class, 'updateQCLineStatus']);

        });

        /**Dispatch */
        Route::get('/jobs/dispatch', [DispatchController::class, 'getJobDispatched']);
        Route::post('/dispatch/job/{job}', [DispatchController::class, 'UpdateJobDispatchStatus']);
        Route::post('/dispatch/line/{line}', [DispatchController::class, 'UpdateLineDispatchStatus']);


        Route::post('/dispatch/bulk/line/', [DispatchController::class, 'updateBulkLineDispatchStatus']);

        Route::post('/dispatch/deal/{dealId}/packing-slip/print', [DispatchController::class, 'PrintPackingSlip']);
        Route::post('/dispatch/deal/{dealId}/packing-slip/email', [DispatchController::class, 'SendEmailPackingSlip']);
        Route::post('/dispatch/deal/{dealId}/dispatch-packing-slip/email', [DispatchController::class, 'SendDispatchEmailPackingSlip']);

        Route::post('/packing-slip/download', [PackingSlipController::class, 'downloadPackingSlip']);
        Route::post('/packing-slip/email', [PackingSlipController::class, 'emailPackingSlip']);

        Route::group(['prefix' => '/comments'], function () {
            Route::get('/', [CommentController::class, 'getComment']);
            Route::patch('/read/{commentId}', [CommentController::class, 'readComment']);
            Route::get('/notification', [CommentController::class, 'notification']);
            Route::get('/get-all-comments', [CommentController::class, 'getAllComments']);
        });

        Route::group(['prefix' => '/ncr'], function () {
            Route::get('/', [NonConformanceReportController::class, 'getNonConformanceReports']);
            Route::post('/{id}', [NonConformanceReportController::class, 'downloadImage']);
        });
    // });

    //administrator & supervisor & user
    Route::group(['middleware' => ['CheckRole:administrator,supervisor,user']], function () {
        Route::group(['prefix' => '/comments'], function () {
            Route::post('/', [CommentController::class, 'addComment']);
        });

        Route::group(['prefix' => '/jobs'], function () {
            Route::patch('/job/{jobId}', [JobScheduleController::class, 'updateJobStatus']);
            Route::patch('/job-priority/{jobId}', [JobScheduleController::class, 'updateJobPriority']);
            Route::patch('/job-date/{jobId}', [JobScheduleController::class, 'updateJobBayDate']);
            Route::patch('/job-status/{jobId}', [JobScheduleController::class, 'updateJobBayStatus']);

            Route::patch('/job/{jobId}/edit', [JobScheduleController::class, 'editJob']);

            Route::get('redirect/{jobId}', [JobScheduleController::class, 'handleJobQRCodeRedirection']);
            Route::get('/archive', [JobScheduleController::class, 'archiveJob']);
        });
        Route::patch('/override-qc/{jobId}', [JobScheduleController::class, 'overrideQc']);

        Route::patch('/line/{lineId}', [LineItemsController::class, 'updateLineStatus']);

        Route::group(['prefix' => 'jobs/scheduled/'], function () {
            Route::get('table/overview', [JobScheduleController::class, 'getJobs']);
        });

        Route::get('jobs/dashboard', [JobScheduleController::class, 'getJobsDashboard']);
        Route::get('jobs/overview', [JobScheduleController::class, 'getJobsOverview']);
        Route::get('jobs/bay', [JobScheduleController::class, 'getJobsBay']);
        Route::get('jobs-report/bay', [JobScheduleController::class, 'getJobsBayReport']);
        Route::get('jobs/generate-report', [ReportController::class, 'generateReport']);


        Route::get('/jobs/schedule/date_kanban/overview', [JobScheduleController::class, 'getJobsOverviewDateKanban']);
        Route::get('/jobs/schedule/table/{bay}', [JobScheduleController::class, 'getJobByBay']);
        Route::get('/jobs/schedule/date_kanban/{bay}', [JobScheduleController::class, 'getJobByBayKanban']);
        Route::get('/jobs/generate-qr-code-labels/{job_id}', [JobScheduleController::class, 'generateQrCodeLabels']);
        Route::get('jobs/schedule/overview/kanban', [JobScheduleController::class, 'getAllJobsOverviewKanban']);


        Route::get('packing-slip/{id}', [PackingSlipController::class, 'getPackingSlip']);

        Route::group(['prefix' => '/comments'], function () {
            Route::post('/', [CommentController::class, 'addComment']);
            Route::patch('/{commentId}', [CommentController::class, 'updateComment']);
        });

        Route::group(['prefix' => '/notifications'], function () {
            Route::get('get-comment-notifications/', [NotificationController::class, 'commentNotifications']);
            Route::get('get-comment-notification/{id}', [NotificationController::class, 'getCommentNotification']);
            Route::patch('patch-viewed-notification/{id}', [NotificationController::class, 'patchViewNotification']);            
        });

        Route::group(['prefix' => '/search-bay-filters'], function () {
            Route::post('/add-filter', [SearchFilterController::class, 'addFilter']);
            Route::post('/get-filter-by-table', [SearchFilterController::class, 'getFilterByTable']);
        });
    });
});

Route::group(['prefix' => 'webhooks'], function () {
    Route::post('/hubspot', [WebhookController::class, 'handleHubspotWebhook'])->middleware('hubspot-webhook');
    Route::post('/xero', [WebhookController::class, 'handleXeroWebhook'])->middleware('xero-webhook');
});

Route::group(['prefix' => 'hubspot'], function() {
    Route::group(['prefix' => 'crm-cards'], function() {
        Route::group(['middleware' => ['hubspot-webhook', 'hubspot-crm-card']], function() {
            Route::get('/deal/line-item-editor', [IntegrationController::class, 'getDealLineItemEditorCrmCard']);
            Route::get('/ticket/job-status', [IntegrationController::class, 'getTicketJobStatusCrmCard']);

            Route::get('/company/xero-invoices', [IntegrationController::class, 'getCompanyXeroInvoicesCrmCard']);
            Route::get('/deal-ticket/xero-invoice', [IntegrationController::class, 'getDealTicketXeroInvoiceCrmCard']);
        });

        Route::group(['middleware' => 'hubspot-crm-card-request'], function() {
            Route::get('/deals/{dealId}/line-items', [IntegrationController::class, 'getDealLineItems']);
            Route::post('/deals/{dealId}/line-items', [IntegrationController::class, 'updateDealLineItems']);
            Route::get('/colours', [IntegrationController::class, 'getColours']);
            Route::get('/products', [IntegrationController::class, 'getProducts']);
        });
    });
});
