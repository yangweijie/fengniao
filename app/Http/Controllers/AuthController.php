<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        if ($username === 'test' && $password === 'test') {
            Session::put('user', $username);
            return redirect()->route('test-page');
        }
        return back()->withErrors(['账号或密码错误']);
    }

    public function logout()
    {
        Session::forget('user');
        return redirect()->route('login');
    }
}
