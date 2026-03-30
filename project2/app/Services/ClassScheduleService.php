<?php

namespace App\Services;
use App\Models\ClassRoom;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use Carbon\Carbon;

class ClassScheduleService
{

    public function getTodaySubjects($classroom_id)
    {
        $today = Carbon::now()->locale('en')->dayName; // eg. Sunday
        return $this->getSubjectsByDay($classroom_id, $today);
    }
    public function getallSchedule(){
        $allSchedule =ClassSchedule::query()->get();
        return [
            'code' => 200,
            'message' => "Schedule get successfully",
            'data' => $allSchedule

        ];
    }
    public function show($id)
    {
        $showSchedule =ClassRoom::with('classroom')->find($id);
        return [
            'code' => 200,
            'message' => "Schedule get successfully",
            'data' => $showSchedule

        ];
    }

    public function getTomorrowSubjects($classroom_id)
    {
        $tomorrow = Carbon::now()->addDay()->locale('en')->dayName;

        // إذا كان غدًا سبت أو جمعة نتجاوزه إلى أول يوم من الأسبوع (الأحد)
        if (in_array($tomorrow, ['Friday', 'Saturday'])) {
            $tomorrow = 'Sunday';
        }

        return $this->getSubjectsByDay($classroom_id, $tomorrow);
    }

    private function getSubjectsByDay($classroom_id, $day)
    {
        $schedule = ClassSchedule::where('classroom_id', $classroom_id)
            ->where('day', $day)
            ->first();

        if (!$schedule) {
            return [
                'code' => 404,
                'message' => "No schedule found for $day",
                'data' => null
            ];
        }

        return [
            'code' => 200,
            'message' => "Schedule for $day",
            'data' => [
                'day' => $day,
                'subjects' => [
                    'period_1' => $schedule->period_1,
                    'period_2' => $schedule->period_2,
                    'period_3' => $schedule->period_3,
                ]
            ]
        ];
    }
    public function getTodaySubjectsByEnrollment($enrollment_id)
    {
        $today = Carbon::now()->locale('en')->dayName;
        return $this->getSubjectsByEnrollmentAndDay($enrollment_id, $today);
    }

    public function getTomorrowSubjectsByEnrollment($enrollment_id)
    {
        $tomorrow = Carbon::now()->addDay()->locale('en')->dayName;

        if (in_array($tomorrow, ['Friday', 'Saturday'])) {
            $tomorrow = 'Sunday';
        }

        return $this->getSubjectsByEnrollmentAndDay($enrollment_id, $tomorrow);
    }

    private function getSubjectsByEnrollmentAndDay($enrollment_id, $day)
    {
        $enrollment = Enrollment::with('classRoom')->find($enrollment_id);

        if (!$enrollment || !$enrollment->classRoom) {
            return [
                'code' => 404,
                'message' => 'Enrollment not found or has no classroom',
                'data' => null
            ];
        }

        $classroom_id = $enrollment->classRoom->id;

        $schedule = ClassSchedule::where('classroom_id', $classroom_id)
            ->where('day', $day)
            ->first();

        if (!$schedule) {
            return [
                'code' => 404,
                'message' => "No schedule found for $day",
                'data' => null
            ];
        }

        return [
            'code' => 200,
            'message' => "Schedule for $day",
            'data' => [
                'day' => $day,
                'subjects' => [
                    'period_1' => $schedule->period_1,
                    'period_2' => $schedule->period_2,
                    'period_3' => $schedule->period_3,
                ]
            ]
        ];
    }    public function createWeeklySchedule($request)
    {
        $classroomId = $request->classroom_id;

        foreach ($request->schedules as $schedule) {
            ClassSchedule::create([
                'classroom_id' => $classroomId,
                'day' => $schedule['day'],
                'period_1' => $schedule['period_1'],
                'period_2' => $schedule['period_2'],
                'period_3' => $schedule['period_3'],
            ]);
        }

        return [
            'code' => 200,
            'message' => 'Weekly schedule created successfully',
            'data' => ClassSchedule::where('classroom_id', $classroomId)->get()
        ];
    }
    public function updateWeeklySchedule($request, $classroomId)
    {
        // حذف القديم
        ClassSchedule::where('classroom_id', $classroomId)->delete();

        foreach ($request->schedules as $schedule) {
            ClassSchedule::create([
                'classroom_id' => $classroomId,
                'day' => $schedule['day'],
                'period_1' => $schedule['period_1'],
                'period_2' => $schedule['period_2'],
                'period_3' => $schedule['period_3'],
            ]);
        }

        return [
            'code' => 200,
            'message' => 'Weekly schedule updated successfully',
            'data' => ClassSchedule::where('classroom_id', $classroomId)->get()
        ];
    }



}
