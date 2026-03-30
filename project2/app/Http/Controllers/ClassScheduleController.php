<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassScheduleRequest;
use App\Http\Requests\UpdateClassScheduleRequest;
use App\Models\ClassRoom;
use App\Services\ClassScheduleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;


class ClassScheduleController extends Controller
{
    private ClassScheduleService $ClassScheduleService ;
    use ApiResponseTrait ;

    public function __construct(ClassScheduleService $ClassScheduleService)
    {
        $this->ClassScheduleService = $ClassScheduleService ;
    }



    public function store(StoreClassScheduleRequest $request)
    {
        $data = $this->ClassScheduleService->createWeeklySchedule($request);

        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse(null, $data['message'], $data['code']);
    }

    public function update(UpdateClassScheduleRequest $request, $classroomId)
    {
        $data = $this->ClassScheduleService->updateWeeklySchedule($request, $classroomId);

        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse(null, $data['message'], $data['code']);
    }
    public function getTodaySubjects($enrollment_id)
    {
        $data = $this->ClassScheduleService->getTodaySubjectsByEnrollment($enrollment_id);

        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

    public function getTomorrowSubjects($enrollment_id)
    {
        $data = $this->ClassScheduleService->getTomorrowSubjectsByEnrollment($enrollment_id);

        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }
    public function getAll()
    {
        $data = $this->ClassScheduleService->getallSchedule();

        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }
//    public function show($id)
//    {
//        $data = $this->ClassScheduleService->show($id);
//
//        return $data['code'] === 200
//            ? $this->successResponse($data['data'], $data['message'], $data['code'])
//            : $this->errorResponse($data['data'], $data['message'], $data['code']);
//    }


    public function getClassroomByTeacher()
    {
        $teacher = Auth::user(); // المستخدم المصادق عليه
        $teacher_id = $teacher->id;

        $classroom = ClassRoom::with('schedule')->where('user_id', $teacher_id)->first();

        if (!$classroom) {
            return response()->json([
                'data' => null,
                'message' => 'No classroom found for this teacher',
                'code' => 404
            ]);
        }

        return response()->json([
            'data' => [
                'classroom' => [
                    'id' => $classroom->id,
                    'class_name' => $classroom->class_name,
                    'level' => $classroom->level,
                    'teacher_id' => $classroom->user_id,
                ],
                'schedules' => $classroom->schedule
            ],
            'message' => 'Classroom fetched successfully',
            'code' => 200
        ]);
    }
    public function getSchedulesByClassroom($classroom_id)
    {
        $classroom = ClassRoom::with('schedule')->find($classroom_id);

        if (!$classroom) {
            return response()->json([
                'data' => null,
                'message' => 'Classroom not found',
                'code' => 404
            ]);
        }

        return response()->json([
            'data' => [
                'classroom' => [
                    'id' => $classroom->id,
                    'class_name' => $classroom->class_name,
                    'level' => $classroom->level,
                    'teacher_id' => $classroom->user_id,
                ],
                'schedules' => $classroom->schedule
            ],
            'message' => 'Schedules fetched successfully',
            'code' => 200
        ]);
    }



}
