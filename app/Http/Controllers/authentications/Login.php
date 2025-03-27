<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class Login extends Controller
{
  public function index()
  {
    return view('content.authentications.login');
  }

  public function processLogin(Request $request)
  {
      $credentials = [
        'name' => $request->username,
        'password' => $request->password,
      ];

      if (Auth::attempt($credentials)) {
          $request->session()->regenerate();

          Log::info('User logged in: ' . auth()->user()->username); // Log ID user

          return redirect()->intended('/');
      }

      Log::warning('Login failed for email: ' . $request->email); // Log jika gagal

      return back()->withErrors([
          'email' => 'Email atau password salah.',
      ]);
  }

  public function logout(Request $request)
  {
    Auth::logout();

    // Hapus semua data sesi
    session()->flush();

    // Regenerate token agar sesi baru aman
    $request->session()->regenerateToken();

    File::delete(storage_path('framework/sessions/' . session()->getId()));

    return redirect('/auth/login');
  }
}
