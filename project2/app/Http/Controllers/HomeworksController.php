<?php
// app/Http/Controllers/HomeworkController.php
namespace App\Http\Controllers;

use App\Http\Requests\CreateHomeworkRequest;
use App\Http\Requests\UpdateHomeworkRequest;
use App\Services\HomeworkService;
use App\Traits\ApiResponseTrait;

class HomeworksController extends Controller
{
    private HomeworkService $HomeworkService;
    use ApiResponseTrait;

    public function __construct(HomeworkService $HomeworkService)
    {
        $this->HomeworkService = $HomeworkService;
    }

    public function AddHomework(CreateHomeworkRequest $request)
    {
        $data = $this->HomeworkService->AddHomework($request, auth()->id());
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

    public function ShowHomework($id)
    {
        $data = $this->HomeworkService->ShowHomework($id);
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

    public function GetHomeworksByClass($classroomId)
    {
        $data = $this->HomeworkService->GetHomeworksByClass($classroomId);
        return $this->successResponse($data['data'], $data['message'], $data['code']);
    }

    public function UpdateHomework(UpdateHomeworkRequest $request, $id)
    {
        $data = $this->HomeworkService->UpdateHomework($request, $id, auth()->id());
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

    public function DeleteHomework($id)
    {
        $data = $this->HomeworkService->DeleteHomework($id, auth()->id());
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

    public function GetStudentHomeworks($enrollmentId)
    {
        $data = $this->HomeworkService->GetStudentHomeworks($enrollmentId);
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }
    public function GetTeacherHomeworks()
    {
        $data = $this->HomeworkService->GetTeacherHomeworks();
        return $data['code'] === 200
            ? $this->successResponse($data['data'], $data['message'], $data['code'])
            : $this->errorResponse($data['data'], $data['message'], $data['code']);
    }

}
