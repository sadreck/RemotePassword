<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/install', [\App\Http\Controllers\InstallationController::class, 'index'])->name('firstRun');
Route::post('/install/save', [\App\Http\Controllers\InstallationController::class, 'save'])->name('firstRunSave');

Auth::routes([
    'verify' => false,
    'reset' => false,
    'forgot' => false,
    'register' => env('ALLOW_REGISTRATIONS', true)
]);

Route::get('/register/activate/{token}/{email}', [\App\Http\Controllers\Auth\VerificationController::class, 'index'])
    ->name('activateAccount');
Route::post('/register/activate/{token}/{email}/2fa', [\App\Http\Controllers\Auth\VerificationController::class, 'activate'])
    ->name('activateAccount2FA');
Route::get('/register/activate/resend', [\App\Http\Controllers\Auth\VerificationController::class, 'resendIndex'])
    ->name('resendAccountActivation');
Route::post('/register/activate/resend', [\App\Http\Controllers\Auth\VerificationController::class, 'resendAction'])
    ->name('actionResendAcccountActivation');
Route::get('/login/forgot', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'index'])
    ->name('forgotPassword');
Route::post('/login/forgot', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'forgotAction'])
    ->name('actionForgotPassword');
Route::get('/login/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'index'])
    ->name('resetPassword');
Route::get('/login/reset/{token}/{email}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'index'])
    ->name('resetPasswordIndex');
Route::post('/login/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'actionReset'])
    ->name('actionPasswordReset');
Route::get('/login/unlock', [\App\Http\Controllers\Auth\LoginController::class, 'unlock'])
    ->name('unlockAccount');
Route::post('/login/unlock/send', [\App\Http\Controllers\Auth\LoginController::class, 'sendUnlockEmail'])
    ->name('actionSendUnlockEmail');
Route::get('/login/unlock/{token}/{email}', [\App\Http\Controllers\Auth\LoginController::class, 'unlockAccount'])
    ->name('actionUnlockAccount');

Route::get('/manage/keys', [\App\Http\Controllers\KeyController::class, 'index'])
    ->name('manageKeys');
Route::get('/manage/keys/{id}/edit', [\App\Http\Controllers\KeyController::class, 'editKey'])
    ->where('id', '[0-9]+')
    ->name('manageKeysEdit');
Route::post('/manage/keys/{id}/edit/save', [\App\Http\Controllers\KeyController::class, 'editKeySave'])
    ->where('id', '[0-9]+')
    ->name('manageKeysEditSave');
Route::post('/manage/keys/{id}/delete', [\App\Http\Controllers\KeyController::class, 'deleteKey'])
    ->where('id', '[0-9]+')
    ->name('manageKeysDelete');

Route::get('/manage/passwords', [\App\Http\Controllers\PasswordController::class, 'index'])
    ->name('managePasswords');
Route::get('/manage/passwords/export', [\App\Http\Controllers\PasswordController::class, 'export'])
    ->name('managePasswordsExport');
Route::post('/manage/passwords/export/run', [\App\Http\Controllers\PasswordController::class, 'exportRun'])
    ->name('managePasswordsExportRun');
Route::get('/manage/passwords/import', [\App\Http\Controllers\PasswordController::class, 'import'])
    ->name('managePasswordsImport');
Route::post('/manage/passwords/import/run', [\App\Http\Controllers\PasswordController::class, 'importRun'])
    ->name('managePasswordsImportRun');
Route::get('/manage/passwords/{id}/edit', [\App\Http\Controllers\PasswordController::class, 'editPassword'])
    ->where('id', '[0-9]+')
    ->name('managePasswordsEdit');
Route::post('/manage/passwords/{id}/edit/save', [\App\Http\Controllers\PasswordController::class, 'editPasswordSave'])
    ->where('id', '[0-9]+')
    ->name('managePasswordsEditSave');
Route::post('/manage/passwords/{id}/delete', [\App\Http\Controllers\PasswordController::class, 'deletePassword'])
    ->where('id', '[0-9]+')
    ->name('managePasswordsDelete');
Route::post('/manage/passwords/{id}/reset-count', [\App\Http\Controllers\PasswordController::class, 'resetUseCount'])
    ->where('id', '[0-9]+')
    ->name('managePasswordsResetCount');
Route::get('/manage/passwords/{id}/view/{view}', [\App\Http\Controllers\PasswordController::class, 'view'])
    ->where('id', '[0-9]+')
    ->where('view', '[a-z]+')
    ->name('managePassword');
Route::get('/manage/passwords/{id}/view/restrictions/{restrictionId}/edit', [\App\Http\Controllers\PasswordController::class, 'editRestriction'])
    ->where('id', '[0-9]+')
    ->where('restrictionId', '[0-9]+')
    ->name('managePasswordRestrictionEdit');
Route::post('/manage/passwords/{id}/view/restrictions/{restrictionId}/edit/save', [\App\Http\Controllers\PasswordController::class, 'saveRestrictions'])
    ->where('id', '[0-9]+')
    ->where('restrictionId', '[0-9]+')
    ->name('managePasswordRestrictionSave');
Route::post('/manage/passwords/{id}/view/restrictions/{restrictionId}/delete', [\App\Http\Controllers\PasswordController::class, 'deleteRestrictions'])
    ->where('id', '[0-9]+')
    ->where('restrictionId', '[0-9]+')
    ->name('managePasswordRestrictionDelete');
Route::post('/manage/passwords/{id}/view/notifications/save', [\App\Http\Controllers\PasswordController::class, 'saveNotifications'])
    ->where('id', '[0-9]+')
    ->name('managePasswordNotificationsSave');

Route::get('/password/{token1}/{token2}/{format?}', [\App\Http\Controllers\RetrievePasswordController::class, 'accessPasswordGet'])
    ->defaults('format', 'raw')
    ->name('accessPasswordGet');
Route::get('/password/', [\App\Http\Controllers\RetrievePasswordController::class, 'accessPasswordGetQuery'])
    ->name('accessPasswordGetQuery');
Route::post('/password', [\App\Http\Controllers\RetrievePasswordController::class, 'accessPasswordPost'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('accessPasswordPost');

Route::get('/user/{view}', [\App\Http\Controllers\UserController::class, 'view'])
    ->where('view', '[a-z]+')
    ->name('userAccount');
Route::post('/user/profile/save', [\App\Http\Controllers\UserController::class, 'saveProfile'])
    ->name('userAccountProfileSave');
Route::post('/user/password/save', [\App\Http\Controllers\UserController::class, 'changePassword'])
    ->name('userAccountChangePassword');
Route::post('/user/notifications/save', [\App\Http\Controllers\UserController::class, 'saveNotifications'])
    ->name('userAccountNotificationsSave');

Route::get('/logs', [\App\Http\Controllers\LogsController::class, 'index'])
    ->name('accessLogs');
Route::get('/logs/invalid', [\App\Http\Controllers\LogsController::class, 'invalid'])
    ->name('invalidAccessLogs')
    ->can('admin');
Route::get('/logs/errors', [\App\Http\Controllers\LogsController::class, 'errors'])
    ->name('errorLogs')
    ->can('admin');

Route::get('/site/settings/{view}', [\App\Http\Controllers\SiteSettingsController::class, 'view'])
    ->where('view', '[a-z]+')
    ->name('siteSettings')
    ->can('admin');
Route::post('/site/settings/{view}/save', [\App\Http\Controllers\SiteSettingsController::class, 'save'])
    ->where('view', '[a-z]+')
    ->name('siteSettingsSave')
    ->can('admin');
Route::post('/site/settings/email/test', [\App\Http\Controllers\SiteSettingsController::class, 'testEmail'])
    ->name('siteSettingsEmailTest')
    ->can('admin');
Route::get('/site/settings/users/{id}/edit', [\App\Http\Controllers\SiteSettingsController::class, 'editUser'])
    ->where('id', '[0-9]+')
    ->name('siteSettingsUserEdit')
    ->can('admin');
Route::post('/site/settings/users/{id}/edit', [\App\Http\Controllers\SiteSettingsController::class, 'editUserSave'])
    ->where('id', '[0-9]+')
    ->name('siteSettingsUserEditSave')
    ->can('admin');
