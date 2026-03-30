<?php

use App\Http\Controllers\Add_one_out;
use App\Http\Controllers\API\LessonAttachmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\DifferenceSpotController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExpertSystemController;
use App\Http\Controllers\HomeworksController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LessonAttachmentsController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MazeController;
use App\Http\Controllers\MemoryTestController;
use App\Http\Controllers\OrderShapesController;
use App\Http\Controllers\RavenController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SizeOrderController;
use App\Http\Controllers\SLCTController;
use App\Http\Controllers\SpeechController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\VoiceAnalysisController;
use App\Http\Controllers\UserTokenController;
use App\Http\Controllers\FirebaseNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(UserController::class)->group(function (){
    Route::post('/login','login')->name('user.login');

    Route::group(['middleware'=>['auth:sanctum']],function () {
        Route::get('logout', 'logout')->name('user.logout');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('class')->controller(ClassRoomController::class)->group(function () {
            Route::post('/add', 'AddClassRoom')->name('AddClassRoom');
            Route::post('/update/{id}', 'UpdateClassRoom')->name('UpdateClassRoom');
            Route::Get('/show/{id}', 'ShowClassRoom')->name('ShowClassRoom');
            Route::Get('/get', 'GetClassRoom')->name('GetClassRoom');
            Route::get('/getClassCount', 'ClassCount')->name('ClassCount');
            Route::get('/delete/{id}', 'DeleteClassRoom')->name('DeleteClassRoom');
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('Enrollment')->controller(EnrollmentController::class)->group(function () {
                Route::post('/add', 'enrollment')->name('enrollments.create');
                Route::get('/get', 'GetAllEnrollment')->name('GetAllEnrollment');
                Route::get('/getParentEnrollment', 'GetEnrollment')->name('GetEnrollment');
                Route::get('/show/{id}', 'ShowEnrollment')->name('ShowEnrollment');
                Route::post('/update/{id}', 'UpdateEnrollment')->name('UpdateEnrollment');
                Route::get('/delete/{id}', 'CanselEnrollment')->name('CanselEnrollment');
            });
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('Teacher')->controller(TeacherController::class)->group(function () {
                Route::post('/add', 'TeacherRegister')->name('TeacherRegister');
                Route::get('/get', 'GetTeacher')->name('GetTeacher');
                Route::get('/getTeacherCount', 'TeacherCount')->name('TeacherCount');
                Route::get('/getUnassignedTeachers', 'getUnassignedTeachers')->name('getUnassignedTeachers');// الذين ليس لديهم صف
                Route::get('/show/{id}', 'ShowTeacher')->name('ShowTeacher');
                Route::post('/update/{id}', 'UpdateTeacher')->name('UpdateTeacher');
                Route::get('/delete/{id}', 'DeleteTeacher')->name('DeleteTeacher');
            });
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('Student')->controller(StudentController::class)->group(function () {
                Route::post('/add', 'StudentRegister')->name('StudentRegister');
                Route::get('/get', 'GetStudent')->name('GetStudent');
                Route::get('/show/{id}', 'ShowStudent')->name('ShowStudent');
                Route::post('/update/{id}', 'UpdateStudent')->name('UpdateStudent');
                Route::get('/delete/{id}', 'DeleteStudent')->name('DeleteStudent');
                Route::get('/count', 'StudentCount')->name('StudentCount');
                Route::get('/by-level', 'getStudentsByLevel');
            });
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('lessons')->controller(LessonController::class)->group(function () {
                Route::post('/add', 'AddLesson')->name('AddLesson');
                Route::get('/get', 'GetLessons')->name('GetLessons');
                Route::get('/getLessonsByClassroom/{classroom_id}', 'getLessonsByClassroom')->name('getLessonsByClassroom');
                Route::get('/show/{id}', 'ShowLesson')->name('ShowLesson');
                Route::get('/today', 'GetTodayLessons')->name('GetTodayLessons');
                Route::get('/upcoming-week', 'GetUpcomingWeekLessons')->name('GetUpcomingWeekLessons');
                Route::get('/subject', 'GetLessonsBySubject')->name('GetLessonsBySubject');
                Route::post('/update/{id}', 'UpdateLesson')->name('UpdateLesson');
                Route::delete('/delete/{id}', 'DeleteLesson')->name('DeleteLesson');
                Route::get('/by-enrollment/{enrollment_id}', 'GetLessonsByEnrollment')->name('GetLessonsByEnrollment');
                Route::get('/by-enrollment/{enrollment_id}/today',  'GetTodayLessonsByEnrollment')->name('GetTodayLessonsByEnrollment');
                Route::get('/by-enrollment/{enrollment_id}/week','GetUpcomingWeekLessonsByEnrollment')->name('GetUpcomingWeekLessonsByEnrollment');


            });
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('schedule')->controller(ClassScheduleController::class)->group(function () {
                Route::get('/today/{enrollment_id}',  'getTodaySubjects')->name('getTodaySubjects');
                Route::get('/tomorrow/{enrollment_id}', 'getTomorrowSubjects')->name('getTomorrowSubjects');
                Route::post('/create', 'store')->name('store_schedule');
                Route::get('/getAll', 'getAll')->name('getAll_schedule');
                Route::get('/getClassroomByTeacher', 'getClassroomByTeacher')->name('getClassroomByTeacher');
                Route::get('/getSchedulesByClassroom/{classroom_id}', 'getSchedulesByClassroom')->name('show_schedule');
                Route::post('/update/{id}', 'update')->name('update_schedule');

            });
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('homeworks')->controller(HomeworksController::class)->group(function (){
            Route::post('/add',  'AddHomework');
            Route::get('/show/{id}',  'ShowHomework');
            Route::get('/GetByClass/{classroomId}', 'GetHomeworksByClass');
            Route::get('/GetTeacherHomeworks', 'GetTeacherHomeworks');
            Route::post('/update/{id}',  'UpdateHomework');
            Route::delete('/delete/{id}', 'DeleteHomework');

            // للطالب/ولي الأمر
            Route::get('/students/{enrollmentId}', 'GetStudentHomeworks');
        });
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/teacher/attendance', [AttendanceController::class, 'storeAttendance']);
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/teacher/students', [AttendanceController::class, 'teacherAttendances']);
        });
        Route::middleware('auth:sanctum')->group(function () {
            // استرجاع حضور طالب محدد
            Route::get('/attendance/student/{studentId}', [AttendanceController::class, 'studentAttendanceReport']);

            // استرجاع حضور صف المعلم
            Route::get('/attendance/classroom', [AttendanceController::class, 'classroomAttendance']);
            Route::get('/attendance/allDate', [AttendanceController::class, 'classroomAttendanceAlldate']);
            Route::get('/absencesByDate', [AttendanceController::class, 'absencesByDate']);
        });
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/size-order/start/{enrollment_id}', [SizeOrderController::class, 'generate']);
            Route::post('/size-order/submit/{enrollment_id}', [SizeOrderController::class, 'submit']);
        });

        Route::middleware('auth:sanctum')->group(function(){
            Route::get('/maze/start/{enrollment_id}',[MazeController::class,'generate']);
            Route::post('/maze/submit/{enrollment_id}',[MazeController::class,'submit']);
        });


    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/send-notification', [FirebaseNotificationController::class, 'sendNotification'])->name('sendNotification');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/recommend/{enrollmentId}', [RecommendationController::class, 'recommend'])->name('recommend');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/save-token', [UserTokenController::class, 'saveToken'])->name('saveToken');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/generate-numbers/{enrollment_id}', [MemoryTestController::class, 'generate']);
        Route::post('/submit-answer/{enrollment_id}', [MemoryTestController::class, 'checkAnswer']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/generate/{enrollment_id}', [ReportController::class, 'generate']);
    });
    Route::middleware('auth:sanctum')->prefix('slct')->group(function () {
        Route::get('generate/{enrollment_id}', [SLCTController::class, 'generate']);
        Route::post('evaluate/{enrollment_id}', [SLCTController::class, 'evaluate']);
    });
    Route::middleware('auth:sanctum')->prefix('raven')->group(function () {
        Route::get('/get-question/{enrollment_id}', [RavenController::class, 'generate']);
        Route::post('/send-answer/{enrollment_id}', [RavenController::class, 'submit']);
        Route::post('/analyze-results', [RavenController::class, 'analyze']);
    });
    Route::prefix('classify')->middleware('auth:sanctum')->group(function () {
        Route::get('generate/{enrollment_id}/', [Add_one_out::class, 'generate']);
        Route::post('evaluate/{enrollment_id}/', [Add_one_out::class, 'evaluate']);
    });
//    Route::middleware('auth:sanctum')->group(function () {
//        Route::get('/images/{enrollment_id}', [ImageController::class, 'getImages']);
//        Route::get('/image/{filename}', [ImageController::class, 'serveImage']);
//        Route::post('/analyze-audio/{enrollment_id}', [ImageController::class, 'analyzeAudio']);
//        Route::post('/order_shapes/{enrollment_id}', [OrderShapesController::class, 'evaluateOrder']);
//        Route::get('/difference', [DifferenceSpotController::class, 'getPair']);
//        Route::post('/difference/evaluate/{enrollment_id}', [DifferenceSpotController::class, 'evaluateDifferenceSpot']);
//    });

});

Route::get('/images/{enrollment_id}', [ImageController::class, 'getImages']);
Route::get('/image/{filename}', [ImageController::class, 'serveImage']);
Route::post('/analyze-audio/{enrollment_id}', [ImageController::class, 'analyzeAudio']);
Route::post('/order_shapes/{enrollment_id}', [OrderShapesController::class, 'evaluateOrder']);
Route::get('/difference', [DifferenceSpotController::class, 'getPair']);
Route::post('/difference/evaluate/{enrollment_id}', [DifferenceSpotController::class, 'evaluateDifferenceSpot']);
Route::get('/expert/start', [ExpertSystemController::class, 'startSession']);
Route::post('/expert/answer', [ExpertSystemController::class, 'sendAnswer']);
Route::delete('/attachments/{id}', [LessonAttachmentsController::class, 'destroyAttachment']);
//
//Route::post('/analyze-voice', [VoiceAnalysisController::class, 'analyze']);
//
//Route::post('/upload-audio', [SpeechController::class, 'uploadAudio']);
//Route::get('/analyze-audio', [SpeechController::class, 'analyze']);
