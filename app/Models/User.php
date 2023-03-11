<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'birthdate',
        'country',
        'phone'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_name'=>'string',
        'last_name'=>'string',
        'email'=>'string',
        'password'=>'string',
        'birthdate'=>'timestamp',
        'country'=>'string',
        'phone'=>'string'
    ];

    public function role()
    {
        return $this->belongsToMany(Role::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Lecture::class, 'favorites',
            'user_id', 'lecture_id');
    }

    public function isFavorite($lectureId)
    {
        return $this->favorites()->where('id', $lectureId)->exists();
    }

    public function isAdmin()
    {
        return $this->role()->where('name','admin')->exists();
    }

    public function isListener()
    {
        return $this->role()->where('name','listener')->exists();
    }

    public function isAnnouncer()
    {
        return $this->role()->where('name','announcer')->exists();
    }
}
