<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\LessonsController;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\Settings\AcademySettingsController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\UserController as SettingsUserController;
use App\Http\Controllers\WeeklyScheduleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

/*
| Admin preset pages (v0.3).
| Loaded from routes/web.php via:
| require __DIR__.'/owl-admin-pages.php';
*/

Route::middleware(array_merge(
    AdminRouteMiddleware::stack(),
    ['role.assigned', 'role.access']
))->group(function () {
    Route::get('/workplace', [\App\Http\Controllers\WorkplaceController::class, 'index'])->name('workplace');

    Route::redirect('/app-settings', '/settings?tab=app');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/customers', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomersController::class, 'create'])->name('customers.create');
    Route::get('/customers/{customer}', [CustomersController::class, 'show'])->name('customers.show');

    Route::get('/teachers', [TeachersController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/create', [TeachersController::class, 'create'])->name('teachers.create');
    Route::get('/teachers/{teacher}', [TeachersController::class, 'show'])->name('teachers.show');

    Route::get('/courses-groups', [\App\Http\Controllers\CoursesGroupsController::class, 'index'])->name('courses-groups.index');

    Route::get('/lessons', [LessonsController::class, 'index'])->name('lessons.index');
    Route::redirect('/schedules', '/schedule-service');
    Route::redirect('/weekly-schedule', '/schedule-service');

    Route::get('/schedule-service', [WeeklyScheduleController::class, 'index'])->name('weekly-schedule.index');
    Route::get('/documents', fn () => Inertia::render('Placeholder/ComingSoon', ['section' => 'documents']))->name('placeholder.documents');
    Route::get('/communication', fn () => Inertia::render('Placeholder/ComingSoon', ['section' => 'communication']))->name('placeholder.communication');
    Route::get('/events', fn () => Inertia::render('Placeholder/ComingSoon', ['section' => 'events']))->name('placeholder.events');
    Route::get('/archive', fn () => Inertia::render('Placeholder/ComingSoon', ['section' => 'archive']))->name('placeholder.archive');
    Route::get('/costume-service', fn () => Inertia::render('Placeholder/ComingSoon', ['section' => 'costumeService']))->name('placeholder.costume-service');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/language', [SettingsController::class, 'updateLanguage'])->name('settings.language.update');

    Route::redirect('/ai-settings', '/settings?tab=ai')->name('ai-settings.index');

    Route::get('/statistics/logs', [ActivityLogController::class, 'index'])->name('statistics.logs');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::middleware('administrator')->group(function () {
        Route::redirect('/roles', '/settings?tab=roles')->name('roles.index');
    });

    Route::middleware('can.write')->group(function () {
        Route::middleware('administrator')->group(function () {
            Route::post('/roles', [\App\Http\Controllers\RolesController::class, 'store'])->name('roles.store');
            Route::patch('/roles/{role}', [\App\Http\Controllers\RolesController::class, 'update'])->name('roles.update');
        });

        Route::post('/customers', [CustomersController::class, 'store'])->name('customers.store');
        Route::patch('/customers/{customer}', [CustomersController::class, 'update'])->name('customers.update');

        Route::post('/teachers', [TeachersController::class, 'store'])->name('teachers.store');
        Route::patch('/teachers/{teacher}', [TeachersController::class, 'update'])->name('teachers.update');

        Route::post('/courses-groups/courses', [\App\Http\Controllers\CoursesGroupsController::class, 'storeCourse'])->name('courses-groups.courses.store');
        Route::patch('/courses-groups/courses/{course}', [\App\Http\Controllers\CoursesGroupsController::class, 'updateCourse'])->name('courses-groups.courses.update');
        Route::post('/courses-groups/courses/{course}/groups', [\App\Http\Controllers\CoursesGroupsController::class, 'storeGroup'])->name('courses-groups.groups.store');
        Route::patch('/courses-groups/groups/{courseGroup}', [\App\Http\Controllers\CoursesGroupsController::class, 'updateGroup'])->name('courses-groups.groups.update');
        Route::post('/courses-groups/groups/{courseGroup}/students', [\App\Http\Controllers\CoursesGroupsController::class, 'attachStudent'])->name('courses-groups.students.attach');
        Route::post('/courses-groups/groups/{courseGroup}/lessons', [\App\Http\Controllers\CoursesGroupsController::class, 'attachLesson'])->name('courses-groups.lessons.attach');
        Route::patch('/courses-groups/groups/{courseGroup}/lessons/{lesson}', [\App\Http\Controllers\CoursesGroupsController::class, 'updateGroupLesson'])->name('courses-groups.lessons.update');

        Route::post('/lessons', [LessonsController::class, 'store'])->name('lessons.store');
        Route::patch('/lessons/{lesson}', [LessonsController::class, 'update'])->name('lessons.update');

        Route::post('/settings/users', [SettingsUserController::class, 'store'])->name('settings.users.store');
        Route::patch('/settings/users/{user}', [SettingsUserController::class, 'update'])->name('settings.users.update');
        Route::post('/settings/academy/buildings', [AcademySettingsController::class, 'storeBuilding'])->name('settings.academy.buildings.store');
        Route::patch('/settings/academy/buildings/{academyBuilding}', [AcademySettingsController::class, 'updateBuilding'])->name('settings.academy.buildings.update');
        Route::post('/settings/academy/buildings/{academyBuilding}/rooms', [AcademySettingsController::class, 'storeRoom'])->name('settings.academy.rooms.store');
        Route::patch('/settings/academy/buildings/{academyBuilding}/rooms/{academyRoom}', [AcademySettingsController::class, 'updateRoom'])->name('settings.academy.rooms.update');

        Route::post('/ai-settings/{provider}/key', [AiSettingsController::class, 'saveKey'])->name('ai-settings.save-key');
        Route::post('/ai-settings/{provider}/check', [AiSettingsController::class, 'check'])->name('ai-settings.check');
        Route::post('/ai-settings/{provider}/activate', [AiSettingsController::class, 'activate'])->name('ai-settings.activate');
        Route::post('/ai-settings/deactivate', [AiSettingsController::class, 'deactivate'])->name('ai-settings.deactivate');

        Route::post('/schedule-service/weeks', [WeeklyScheduleController::class, 'storeWeek'])->name('weekly-schedule.weeks.store');
        Route::post('/schedule-service/{week}/lessons', [WeeklyScheduleController::class, 'storeLesson'])->name('weekly-schedule.lessons.store');
        Route::patch('/schedule-service/{week}/lessons/{lesson}', [WeeklyScheduleController::class, 'updateLesson'])->name('weekly-schedule.lessons.update');
        Route::delete('/schedule-service/{week}/lessons/{lesson}', [WeeklyScheduleController::class, 'destroyLesson'])->name('weekly-schedule.lessons.destroy');
        Route::post('/schedule-service/{week}/lessons/{lesson}/duplicate', [WeeklyScheduleController::class, 'duplicateLesson'])->name('weekly-schedule.lessons.duplicate');
        Route::post('/schedule-service/{week}/publish', [WeeklyScheduleController::class, 'publish'])->name('weekly-schedule.publish');
        Route::post('/schedule-service/{week}/copy-previous', [WeeklyScheduleController::class, 'copyPrevious'])->name('weekly-schedule.copy-previous');
        Route::post('/schedule-service/{week}/clear', [WeeklyScheduleController::class, 'clearTimeline'])->name('weekly-schedule.clear');
        Route::patch('/schedule-service/{week}/settings', [WeeklyScheduleController::class, 'updateSettings'])->name('weekly-schedule.settings.update');
        Route::post('/schedule-service/{week}/ai-schedule', [WeeklyScheduleController::class, 'aiSchedule'])->name('weekly-schedule.ai-schedule');
    });

    Route::get('/schedule-service/{week}/conflicts', [WeeklyScheduleController::class, 'conflicts'])->name('weekly-schedule.conflicts');

    Route::middleware('can.delete')->group(function () {
        Route::middleware('administrator')->group(function () {
            Route::delete('/roles/{role}', [\App\Http\Controllers\RolesController::class, 'destroy'])->name('roles.destroy');
        });

        Route::delete('/customers/{customer}', [CustomersController::class, 'destroy'])->name('customers.destroy');
        Route::delete('/teachers/{teacher}', [TeachersController::class, 'destroy'])->name('teachers.destroy');
        Route::delete('/courses-groups/courses/{course}', [\App\Http\Controllers\CoursesGroupsController::class, 'destroyCourse'])->name('courses-groups.courses.destroy');
        Route::delete('/courses-groups/groups/{courseGroup}', [\App\Http\Controllers\CoursesGroupsController::class, 'destroyGroup'])->name('courses-groups.groups.destroy');
        Route::delete('/courses-groups/groups/{courseGroup}/students/{customer}', [\App\Http\Controllers\CoursesGroupsController::class, 'detachStudent'])->name('courses-groups.students.detach');
        Route::delete('/courses-groups/groups/{courseGroup}/lessons/{lesson}', [\App\Http\Controllers\CoursesGroupsController::class, 'detachLesson'])->name('courses-groups.lessons.detach');
        Route::delete('/lessons/{lesson}', [LessonsController::class, 'destroy'])->name('lessons.destroy');
        Route::delete('/settings/users/{user}', [SettingsUserController::class, 'destroy'])->name('settings.users.destroy');
        Route::delete('/settings/academy/buildings/{academyBuilding}', [AcademySettingsController::class, 'destroyBuilding'])->name('settings.academy.buildings.destroy');
        Route::delete('/settings/academy/buildings/{academyBuilding}/rooms/{academyRoom}', [AcademySettingsController::class, 'destroyRoom'])->name('settings.academy.rooms.destroy');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});
