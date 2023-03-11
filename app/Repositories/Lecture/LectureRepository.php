<?php

namespace App\Repositories\Lecture;

use App\Events\AddLecture;
use App\Events\DeleteLecture;
use App\Events\UpdateLecture;
use App\Http\Requests\LectureRequest;
use App\Http\Requests\ZoomRequest;
use App\Models\Lecture;
use App\Models\Meeting;
use App\Models\Slot;
use App\Models\User;
use App\Traits\ZoomJWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LectureRepository implements LectureRepositoryInterface
{
    use ZoomJWT;

    public function getMeetingLectures($id)
    {
        $lectures =  Lecture::where('meeting_id',$id)->get();
        $user = auth('sanctum')->user();
        $date = Meeting::select('date')->whereId($id)->first();
        $date = date('Y-m-d', $date->date);

        foreach ($lectures as $lecture) {
            $slot = Slot::findOrFail($lecture->slot_id);
            $start_datetime = new \DateTime($date . ' ' .$slot->start);
            $end_datetime = new \DateTime($date . ' ' .$slot->end);
            $now = new \DateTime('now');

            if($now > $start_datetime && $now < $end_datetime) {
                $lecture->status = 'Started';
            } elseif ($now > $end_datetime) {
                $lecture->status = 'Ended';
            } else {
                $lecture->status = 'Waiting';
            }

            $lecture->start = $slot->start;
            $lecture->end = $slot->end;
            $lecture->is_favorite = $user->isFavorite($lecture->id);
        }

        return $lectures;
    }

    public function getLecture($id)
    {
        $lecture = Lecture::findOrFail($id);
        $slot = Slot::findOrFail($lecture->slot_id);
        $meeting = Meeting::findOrFail($lecture->meeting_id);

        if($lecture->zoom_id){
            $zoom = $this->getZoom($lecture->zoom_id);
            $lecture->start_time = $zoom['data']['start_time'];
            $lecture->zoom_link = $zoom['data']['join_url'];
        }

        $lecture->start = $slot->start;
        $lecture->end = $slot->end;
        $lecture->category = $lecture->category;
        $lecture->is_joined = $meeting->isJoined();

        return $lecture;
    }

    public function getLecturesByFilters(Request $request)
    {
        $query = Lecture::query()->where('meeting_id',$request['id']);

        if($request['start_time'])
            $query = $query->startTimeFilter($request['start_time']);
        if($request['end_time'])
            $query = $query->endTimeFilter($request['end_time']);
        if($request['category'])
            $query = $query->categoryFilter($request['category']);

        $lectures = $query->get();

        $user = auth('sanctum')->user();
        foreach ($lectures as $lecture) {
            $slot = Slot::findOrFail($lecture->slot_id);
            $lecture->start = $slot->start;
            $lecture->end = $slot->end;
            $lecture->is_favorite = $user->isFavorite($lecture->id);
        }

        return $lectures;
    }

    public function searchLectures(Request $request)
    {
        $query = Lecture::query()->select('id', 'theme');

        if($request->filled('title'))
            $query = $query->search($request['title']);

        return $query->get();
    }

    public function createLecture(LectureRequest $request)
    {
        $request['user_id'] = auth('sanctum')->id();

        $data = $request->only([
            'user_id',
            'meeting_id',
            'slot_id',
            'theme',
            'description'
        ]);

        $fileName = time().'.'.$request['presentation']->getClientOriginalExtension();
        Storage::disk('local')->putFileAs('presentations', $request['presentation'],$fileName);
        $data['presentation'] = $fileName;


        if($request->filled('zoom')){
            $date = Meeting::select('date')->whereId($data['meeting_id'])->first();
            $date = date('Y-m-d', $date->date);
            $time = Slot::select('start')->whereId($data['slot_id'])->first();
            $start_time = $date . 'T' . $time->start;

            $zoom = $this->createZoom(new ZoomRequest(['topic'=>$data['theme'], 'start_time'=>$start_time]));
            $data['zoom_id']=$zoom['data']['id'];
            Cache::forget('zoom');
        }

        $this->deleteSlot($request['meeting_id'],$request['slot_id']);
        $lecture = Lecture::create($data);
        $lecture->category()->attach($request['category']);

        $this->sendNewLectureEmail($lecture);

        return $lecture;
    }

    public function deleteLecture($id)
    {
        $lecture = Lecture::findOrFail($id);

        if($lecture->zoom_id){
            $this->deleteZoom($lecture->zoom_id);
            Cache::forget('zoom');
        }

        $this->sendDeleteLectureEmail($lecture);

        Storage::disk('local')->delete('presentations/'.$lecture->presentation);
        $this->insertSlot($lecture->meeting_id, $lecture->slot_id);

        return Lecture::destroy($id);
    }

    public function updateLecture(LectureRequest $request, $id)
    {
        $data = $request->only([
            'user_id',
            'slot_id',
            'meeting_id',
            'theme',
            'description',
        ]);

        $lecture = Lecture::findOrFail($id);
        if($request['presentation']!=null){
            Storage::disk('local')->delete('presentations/'.$lecture->presentation);
            $fileName = time().'.'.$request['presentation']->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('presentations', $request['presentation'],$fileName);
            $data['presentation'] = $fileName;
        }

        if($lecture->slot_id != $data['slot_id']){
            $this->insertSlot($lecture->meeting_id,$lecture->slot_id);
            $this->deleteSlot($lecture->meeting_id,$data['slot_id']);

            $this->sendUpdateLectureEmail($lecture, $data['slot_id']);
        }

        if($lecture->zoom_id){
            $date = Meeting::select('date')->whereId($data['meeting_id'])->first();
            $date = date('Y-m-d', $date->date);
            $time = Slot::select('start')->whereId($data['slot_id'])->first();
            $start_time = $date . 'T' . $time->start;
            Cache::forget('zoom');

            $this->updateZoom(new ZoomRequest(['topic'=>$data['theme'], 'start_time'=>$start_time]), $lecture->zoom_id);
        }

        $lecture->category()->detach($lecture->category);
        $lecture->category()->attach($request['category']);

        return Lecture::where('id',$id)->update($data);
    }

    public function getSlots($id)
    {
        $meeting = Meeting::findOrFail($id);
        return $meeting->slots;
    }

    public function deleteSlot($meetingId, $slotId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        return $meeting->slots()->detach($slotId);
    }

    public function insertSlot($meetingId, $slotId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        return $meeting->slots()->attach($slotId);
    }

    public function getMeetingUserLecture($id)
    {
        $lecture = Lecture::where('meeting_id',$id)->where('user_id', auth('sanctum')->id())->get();
        return $lecture[0]->id;
    }

    public function sendDeleteLectureEmail($lecture)
    {
        $meeting = Meeting::findOrFail($lecture->meeting_id);
        $user = User::findOrFail($lecture->user_id);

        event(new DeleteLecture($meeting,$user->email));
    }

    public function sendNewLectureEmail($lecture)
    {
        $meeting = Meeting::findOrFail($lecture->meeting_id);
        $user = User::findOrFail($lecture->user_id);

        $listeners = array_column($meeting->subscribers->toArray(),'id');
        $recipient = User::query()->select('email')->whereIn('id',$listeners)
            ->whereHas('role', function ($q) {
            $q->where('name','listener');})->get();

        event(new AddLecture($meeting, $user, $lecture, $recipient));
    }

    public function sendUpdateLectureEmail($lecture, $slot)
    {
        $meeting = Meeting::findOrFail($lecture->meeting_id);
        $user = User::findOrFail($lecture->user_id);
        $time = Slot::findOrFail($slot);

        $listeners = array_column($meeting->subscribers->toArray(),'id');
        $recipient = User::query()->select('email')->whereIn('id',$listeners)
            ->whereHas('role', function ($q) {
                $q->where('name','listener');})->get();

        event(new UpdateLecture($meeting, $lecture, $user, $time, $recipient));
    }

    public function downloadPresentation($presentation)
    {
        return Storage::disk('local')->download('presentations/'.$presentation);
    }
}
