<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return view('content.pages.table-user');
    }

    public function show(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        $query = User::select('id', 'name', 'username', 'email', 'jabatan');

        $recordsTotal = $query->count();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'LIKE', "%{$searchValue}%")
                  ->orWhere('name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('username', 'LIKE', "%{$searchValue}%")
                  ->orWhere('email', 'LIKE', "%{$searchValue}%")
                  ->orWhere('jabatan', 'LIKE', "%{$searchValue}%");
            });
        }

        $recordsFiltered = $query->count();

        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }
        User::create($data);
        return response()->json(['message' => 'User created successfully']);
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->id);
        $data = $request->only(['name', 'username', 'email', 'jabatan']);
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function detail(Request $request)
    {
        $user = User::findOrFail($request->id);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'jabatan' => $user->jabatan,
        ]);
    }
}
