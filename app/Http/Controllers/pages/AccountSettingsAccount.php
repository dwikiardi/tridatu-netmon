<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AccountSettingsAccount extends Controller
{
  public function index()
  {
    $user = Auth::user();
    return view('content.pages.pages-account-settings-account', compact('user'));
  }

  public function update(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|email|max:255|unique:users,email,' . $user->id,
      'phone' => 'nullable|string|max:20',
      'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8000',
    ]);

    $updateData = [
      'name' => $request->name,
      'email' => $request->email,
      'phone' => $request->phone,
    ];

    // Handle photo upload
    if ($request->hasFile('photo')) {
      $photo = $request->file('photo');

      if ($photo->isValid()) {
        try {
          // Create directory if not exists
          $uploadPath = public_path('assets/img/avatars');
          if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
          }

          // Delete old photo if exists
          if ($user->photo && file_exists(public_path($user->photo))) {
            unlink(public_path($user->photo));
          }

          $photoName = time() . '_' . $user->id . '.' . $photo->getClientOriginalExtension();
          $destinationPath = $uploadPath . DIRECTORY_SEPARATOR . $photoName;

          // Move file to upload path
          if ($photo->move($uploadPath, $photoName)) {
            $updateData['photo'] = 'assets/img/avatars/' . $photoName;
            Log::info('Photo uploaded: ' . $updateData['photo']);
          } else {
            Log::error('Failed to move photo file');
            return response()->json([
              'message' => 'Failed to save photo file'
            ], 500);
          }

        } catch (\Exception $e) {
          Log::error('Photo upload error: ' . $e->getMessage());
          return response()->json([
            'message' => 'Failed to upload photo: ' . $e->getMessage()
          ], 500);
        }
      } else {
        return response()->json([
          'message' => 'Photo file is not valid'
        ], 422);
      }
    }

    // Update user data
    try {
      $user->update($updateData);
      Log::info('User updated: ' . json_encode($updateData));
    } catch (\Exception $e) {
      Log::error('Database update error: ' . $e->getMessage());
      return response()->json([
        'message' => 'Failed to update profile: ' . $e->getMessage()
      ], 500);
    }

    // Refresh user data from database
    $user->refresh();

    return response()->json([
      'message' => 'Profile updated successfully',
      'photo' => $user->photo ? asset($user->photo) : null,
      'photoPath' => $user->photo
    ]);
  }

  public function changePassword(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    $request->validate([
      'current_password' => 'required|string',
      'new_password' => 'required|string|min:8|confirmed',
    ], [
      'new_password.min' => 'Password must be at least 8 characters',
      'new_password.confirmed' => 'Password confirmation does not match',
    ]);

    // Check if current password is correct
    if (!Hash::check($request->current_password, $user->password)) {
      Log::warning('Failed password change attempt for user: ' . $user->id);
      return response()->json([
        'message' => 'Current password is incorrect'
      ], 401);
    }

    try {
      // Update password
      $user->update([
        'password' => Hash::make($request->new_password)
      ]);

      Log::info('Password changed for user: ' . $user->id);

      return response()->json([
        'message' => 'Password changed successfully'
      ]);
    } catch (\Exception $e) {
      Log::error('Password change error: ' . $e->getMessage());
      return response()->json([
        'message' => 'Failed to change password: ' . $e->getMessage()
      ], 500);
    }
  }
}
