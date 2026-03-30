<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\UpdateAttachmentRequest;
use App\Models\Attachment;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentsController extends Controller
{
    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    public function index(Lesson $lesson): JsonResponse
    {
        $attachments = $this->attachmentService->getLessonAttachments($lesson);
        return response()->json($attachments);
    }

    public function store(StoreAttachmentRequest $request, Lesson $lesson): JsonResponse
    {
        $attachment = $this->attachmentService->createAttachment(
            $lesson,
            $request->validated()
        );

        return response()->json($attachment, 201);
    }

    public function show(Lesson $lesson, Attachment $attachment): JsonResponse
    {
        $this->attachmentService->validateAttachmentBelongsToLesson($lesson, $attachment);
        return response()->json($attachment);
    }

    public function update(
        UpdateAttachmentRequest $request,
        Lesson                  $lesson,
        Attachment              $attachment
    ): JsonResponse {
        $this->attachmentService->validateAttachmentBelongsToLesson($lesson, $attachment);

        $updatedAttachment = $this->attachmentService->updateAttachment(
            $attachment,
            $request->validated()
        );

        return response()->json($updatedAttachment);
    }

    public function destroy(Lesson $lesson, Attachment $attachment): JsonResponse
    {
        $this->attachmentService->validateAttachmentBelongsToLesson($lesson, $attachment);
        $this->attachmentService->deleteAttachment($attachment);

        return response()->json(null, 204);
    }
    public function destroyAttachment($id)
    {
        $attachment = LessonAttachment::find($id);

        if (!$attachment) {
            return response()->json([
                'message' => 'الملحق غير موجود'
            ], 404);
        }

        // حذف الملف من التخزين لو كان ملف/صورة/فيديو
        if (in_array($attachment->type, ['image', 'file', 'video']) && $attachment->url) {
            if (Storage::exists($attachment->url)) {
                Storage::delete($attachment->url);
            }
        }

        // حذف السجل من قاعدة البيانات
        $attachment->delete();

        return response()->json([
            'message' => 'تم حذف الملحق بنجاح'
        ]);
    }
}
