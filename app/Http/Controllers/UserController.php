<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::select('username', 'name', 'status', 'created_at');

        if ($request->user->level_id == 2) {
            $users->where('warehouse_id', $request->user->warehouse_id)
                ->where('level_id', 3);
        } else if ($request->user->level_id == 3) {
            throw new ApiException('Forbidden', 403);
        }

        $users = $users->get();

        return $users;
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'username' => 'required|unique:users,username',
            'name' => 'required|string',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            throw new ApiException('Bad request', 400, [
                'errors' => $validation->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $user = new User();
            $user->username = $request->username;
            $user->name = $request->name;
            $user->password = hash('sha256', $request->password);
            $user->status = 1;

            if ($request->user->isMarketing()) {
                $validation = Validator::make($request->all(), [
                    'role_id' => 'required|exists:roles,id|in:5,6',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $user->level_id = 3;
                $user->role_id = $request->role_id;
                $user->warehouse_id = $request->user->warehouse_id;
            } else if ($request->user->isSuperAdmin()) {
                $validation = Validator::make($request->all(), [
                    'level_id' => 'required|exists:levels,id',
                    'role_id' => 'required|exists:roles,id',
                    'warehouse_id' => 'required|exists:warehouses,id',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $user->level_id = $request->level_id;
                $user->role_id = $request->role_id;
                $user->warehouse_id = $request->warehouse_id;
            } else {
                throw new ApiException('Forbidden', 403);
            }

            $user->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($e instanceof ApiException) {
                throw $e;
            }

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'User is successfully saved!'
        ]);
    }

    public function show(Request $request)
    {
        $user = User::with('warehouse', 'role', 'level');

        if ($request->user->level_id == 2) {
            $user->where('warehouse_id', $request->user->warehouse_id);
        } else if ($request->user->level_id == 3) {
            throw new ApiException('Forbidden', 403);
        }

        $user = $user->find($request->id);

        if (!$user) {
            throw new ApiException('Not found', 404);
        }

        return $user;
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'username' => 'required|unique:users,username,' . $request->username,
            'name' => 'required|string',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            throw new ApiException('Bad request', 400, [
                'errors' => $validation->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $user = User::find($request->id);
            $user->username = $request->username;
            $user->name = $request->name;
            $user->password = $request->password;
            $user->status = 1;

            if ($request->user->isMarketing()) {
                $validation = Validator::make($request->all(), [
                    'role_id' => 'required|exists:roles,id|in:5,6',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $user->level_id = 3;
                $user->role_id = $request->role_id;
                $user->warehouse_id = $request->user->warehouse_id;
                $user->status = $request->status;
            } else {
                $validation = Validator::make($request->all(), [
                    'level_id' => 'required|exists:levels,id',
                    'role_id' => 'required|exists:roles,id',
                    'warehouse_id' => 'required|exists:warehouses,id',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $user->level_id = $request->level_id;
                $user->role_id = $request->role_id;
                $user->warehouse_id = $request->warehouse_id;
            }

            $user->save();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'User is successfully updated!'
        ]);
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = User::find($request->id);
            $user->delete();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'User is successfully deleted!'
        ]);
    }
}
