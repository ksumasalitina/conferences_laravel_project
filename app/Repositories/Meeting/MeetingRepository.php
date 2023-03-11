<?php

namespace App\Repositories\Meeting;
use App\Events\DeleteMeeting;
use App\Events\JoinListener;
use App\Http\Requests\MeetingRequest;
use App\Models\Country;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;

class MeetingRepository implements MeetingRepositoryInterface
{
    public function getAllMeetings()
    {
        $meetings = Meeting::select(['id','title','date'])->latest()->paginate(15);

        foreach ($meetings as $meeting) {
            $meeting->is_joined = $meeting->isJoined();
        }

        return $meetings;
    }

    public function getMeetingsByFilter(Request $request)
    {
        $query = Meeting::query()->select(['id','title','date'])->where('date','>=', date('Y-m-d'));
        if($request->filled('category'))
            $query = $query->categoryFilter($request['category']);
        if($request->filled('first_date'))
            $query = $query->firstDateFilter($request['first_date']);
        if ($request->filled('last_date'))
            $query = $query->lastDateFilter($request['last_date']);
        if ($request->filled('lectures'))
            $query = $query->lecturesFilter($request['lectures']);

        $meetings = $query->paginate(15);
        foreach ($meetings as $meeting) {
            $meeting->is_joined = $meeting->isJoined();
        }
        return $meetings;
    }

    public function searchMeeting(Request $request)
    {
        $query = Meeting::query()->select('id','title')->where('date','>=', date('Y-m-d'));
        if($request->filled('title'))
            $query = $query->search($request['title']);

        return $query->get();
    }

    public function getMeetingById($id)
    {
        $meeting = Meeting::findOrFail($id);
        $meeting->category = $meeting->category;
        return $meeting;
    }

    public function deleteMeeting($id)
    {
        $meeting = Meeting::findOrFail($id);

        $this->sendDeleteMeetingEmail($meeting);

        return Meeting::destroy($id);
    }

    public function createMeeting(MeetingRequest $meetingRequest)
    {
        $meetingRequest['date'] = date('Y/m/d H:i', strtotime($meetingRequest['date']));
        $meeting = Meeting::create($meetingRequest->all());
        $meeting->category()->attach($meetingRequest['category']);

        for($j=1;$j<=10;$j++) {
            $meeting->slots()->attach($j);
        }

        return $meeting;
    }

    public function updateMeeting($id, MeetingRequest $meetingRequest)
    {
        $meetingRequest['date'] = date('Y/m/d H:i', strtotime($meetingRequest['date']));

        $meeting = Meeting::findOrFail($id);
        $meeting->category()->detach($meeting->category);
        $meeting->category()->attach($meetingRequest['category']);

        $data = $meetingRequest->only([
            'title',
            'country',
            'date',
            'latitude',
            'longitude'
        ]);

        return Meeting::whereId($id)->update($data);
    }

    public function getCountries()
    {
        return Country::query()->select('name')->get();
    }

    public function sendDeleteMeetingEmail($meeting)
    {
        $users = array_column($meeting->subscribers->toArray(),'email');

        event(new DeleteMeeting($meeting, $users));
    }

    public function sendNewListenerEmail($meeting, $user)
    {
        $listener = User::findOrFail($user);
        $announcers = array_column($meeting->subscribers->toArray(),'id');
        $recipient = User::query()->select('email')->whereIn('id',$announcers)
            ->whereHas('role', function ($q) {
                $q->where('name','announcer');})->get();

        event(new JoinListener($meeting, $listener, $recipient));
    }

    public function join($id)
    {
        $meeting = Meeting::findOrFail($id);
        $this->sendNewListenerEmail($meeting, auth()->user()->id);

        return $meeting->subscribers()->attach(auth()->user()->id);
    }

    public function cancel($id)
    {
        $meeting = Meeting::findOrFail($id);

        return $meeting->subscribers()->detach(auth()->user()->id);
    }
}
