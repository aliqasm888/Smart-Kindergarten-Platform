<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateLessonRequest;
use App\Http\Requests\SubjectFilterRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\ClassRoom;
use App\Services\LessonServices;
use App\Traits\ApiResponseTrait;

class LessonController extends Controller
{
    private LessonServices $lessonServices;
    use ApiResponseTrait;

    public function __construct(LessonServices $lessonServices)
    {
        $this->lessonServices = $lessonServices;
    }

    public function AddLesson(CreateLessonRequest $request)
    {
        try {

            $data = $this->lessonServices->AddLesson($request);
            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }

    public function ShowLesson($id)
    {
        try {
            $data = $this->lessonServices->ShowLesson($id);
            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }

    public function GetLessons()
    {
        try {
            $data = $this->lessonServices->GetLessons();
            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }

    public function UpdateLesson(UpdateLessonRequest $request, $id)
    {
        try {
            $data = $this->lessonServices->UpdateLesson($request, $id);
            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }

    public function DeleteLesson($id)
    {
        try {
            $data = $this->lessonServices->DeleteLesson($id);
            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function GetTodayLessons()
    {
        try {
            $data = $this->lessonServices->GetTodayLessons();

            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function GetUpcomingWeekLessons()
    {
        try {
            $data = $this->lessonServices->GetUpcomingWeekLessons();

            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function GetLessonsBySubject(SubjectFilterRequest $request)
    {
        try {
            $data = $this->lessonServices->GetLessonsBySubject($request);

            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function GetLessonsByEnrollment($enrollment_id)
    {
        try {
            $data = $this->lessonServices->GetLessonsByEnrollment($enrollment_id);

            if ($data['code'] === 200) {
                return $this->successResponse($data['data'], $data['message'], $data['code']);
            } else {
                return $this->errorResponse($data['data'], $data['message'], $data['code']);
            }

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function GetTodayLessonsByEnrollment($enrollment_id)
    {
        try {
            $data = $this->lessonServices->GetTodayLessonsByEnrollment(
                $enrollment_id,
            );

            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }

    public function GetUpcomingWeekLessonsByEnrollment( $enrollment_id)
    {
        try {
            $data = $this->lessonServices->GetUpcomingWeekLessonsByEnrollment(
                $enrollment_id,
            );

            return $data['code'] === 200
                ? $this->successResponse($data['data'], $data['message'], $data['code'])
                : $this->errorResponse($data['data'], $data['message'], $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse(null, $e->getMessage());
        }
    }
    public function getLessonsByClassroom($classroom_id)
    {
        $classroom = ClassRoom::with(['lessons', 'user'])->find($classroom_id);

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
                    'teacher' => [
                        'id' => $classroom->user->id,
                        'name' => $classroom->user->name,
                        'email' => $classroom->user->email,
                    ],
                ],
                'lessons' => $classroom->lessons
            ],
            'message' => 'Lessons fetched successfully',
            'code' => 200
        ]);
    }





}
