<?php

namespace App\Repositories\User;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateRequest;
use Illuminate\Support\Facades\Request;

interface UserRepositoryInterface
{
    public function createUser(RegisterRequest $request);
    public function getUserByEmail($email);
    public function updateProfile(UpdateRequest $request, $id);
}
