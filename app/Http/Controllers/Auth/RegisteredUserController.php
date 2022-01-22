<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Traits\ApiService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    use ApiService;
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['status' => 'OK'], 200);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'project_name' => 'string|max:255'
        ]);

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        event(new Registered($user));

//        if (User::count() < 22) {
            $user->project()->create([
                'name' => $request->header('project_name')
            ]);
//        }

        Auth::login($user);
        $user->project_name = $request->project_name;

        return $this->responseJson(['user' => $user]);

//        return redirect(RouteServiceProvider::HOME);
    }

    public function logOut(Request $request)
    {
        Auth::logout();

        return $this->responseJson(['user' => null]);

//        return redirect(RouteServiceProvider::HOME);
    }
}
