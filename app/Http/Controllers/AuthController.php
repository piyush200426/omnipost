<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /* -------------------------
        SHOW LOGIN PAGE
    --------------------------*/
    public function showLoginPage()
    {
        return view('auth');
    }

    /* -------------------------
        SHOW REGISTER PAGE
    --------------------------*/
    public function showRegisterPage()
    {
        return view('auth');
    }

    /* -------------------------
        HANDLE LOGIN
    --------------------------*/
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password'], 'login');
    }

    /* -------------------------
        HANDLE REGISTER
    --------------------------*/
 public function register(Request $request)
{
    $request->validate([
        'name'                  => 'required|string|max:255',
        'email'                 => 'required|email|unique:users,email',
        'password'              => 'required|min:6',
        'password_confirmation' => 'required|same:password'
    ], [
        'email.unique' => 'This email is already registered',
        'password_confirmation.same' => 'Passwords do not match'
    ]);

    // ✅ Create user
    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password)
    ]);

    // 🔥 VERY IMPORTANT: AUTO LOGIN
    Auth::login($user);

    // ✅ Now user is authenticated
    return redirect()->route('dashboard');
}


    /* -------------------------
        LOGOUT
    --------------------------*/
    public function logout()
    {
        Auth::logout();
        return redirect()->route('landing');
    }
}
