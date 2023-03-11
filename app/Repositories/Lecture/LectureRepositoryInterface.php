<?php

namespace App\Repositories\Lecture;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\LectureRequest;
use App\Models\Lecture;
use App\Traits\ZoomJWT;
use Illuminate\Http\Request;

interface LectureRepositoryInterface
{
    public function getMeetingLectures($id);
    public function getLecture($id);
    public function getLecturesByFilters(Request $request);
    public function searchLectures(Request $request);
    public function createLecture(LectureRequest $request);
    public function deleteLecture($id);
    public function updateLecture(LectureRequest $request, $id);
    public function getSlots($id);
    public function deleteSlot($meetingId, $slotId);
    public function insertSlot($meetingId, $slotId);
    public function getMeetingUserLecture($id);
    public function sendDeleteLectureEmail($lecture);
    public function sendNewLectureEmail($lecture);
    public function sendUpdateLectureEmail($lecture, $slot);
    public function downloadPresentation($presentation);
}
