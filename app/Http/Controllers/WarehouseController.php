<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Warehouse;
use DB;
use Illuminate\Http\Request;
use Validator;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::select('name', 'address', 'status', 'created_at');

        if ($request->user->level_id != 1) {
            $warehouses->where('id', $request->user->warehouse_id);
        }

        $warehouses = $warehouses->get();

        return $warehouses;
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'warehousename' => 'required|unique:warehouses,warehousename',
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
            $warehouse = new Warehouse();
            $warehouse->warehousename = $request->warehousename;
            $warehouse->name = $request->name;
            $warehouse->password = hash('sha256', $request->password);
            $warehouse->status = 1;

            if ($request->warehouse->isMarketing()) {
                $validation = Validator::make($request->all(), [
                    'role_id' => 'required|exists:roles,id|in:5,6',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $warehouse->level_id = 3;
                $warehouse->role_id = $request->role_id;
                $warehouse->warehouse_id = $request->warehouse->warehouse_id;
            } else if ($request->warehouse->isSuperAdmin()) {
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

                $warehouse->level_id = $request->level_id;
                $warehouse->role_id = $request->role_id;
                $warehouse->warehouse_id = $request->warehouse_id;
            } else {
                throw new ApiException('Forbidden', 403);
            }

            $warehouse->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($e instanceof ApiException) {
                throw $e;
            }

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Warehouse is successfully saved!'
        ]);
    }

    public function show(Request $request)
    {
        $warehouse = Warehouse::with('warehouse', 'role', 'level');

        if ($request->warehouse->level_id == 2) {
            $warehouse->where('warehouse_id', $request->warehouse->warehouse_id);
        } else if ($request->warehouse->level_id == 3) {
            throw new ApiException('Forbidden', 403);
        }

        $warehouse = $warehouse->find($request->id);

        if (!$warehouse) {
            throw new ApiException('Not found', 404);
        }

        return $warehouse;
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'warehousename' => 'required|unique:warehouses,warehousename,' . $request->warehousename,
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
            $warehouse = Warehouse::find($request->id);
            $warehouse->warehousename = $request->warehousename;
            $warehouse->name = $request->name;
            $warehouse->password = $request->password;
            $warehouse->status = 1;

            if ($request->warehouse->isMarketing()) {
                $validation = Validator::make($request->all(), [
                    'role_id' => 'required|exists:roles,id|in:5,6',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $warehouse->level_id = 3;
                $warehouse->role_id = $request->role_id;
                $warehouse->warehouse_id = $request->warehouse->warehouse_id;
                $warehouse->status = $request->status;
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

                $warehouse->level_id = $request->level_id;
                $warehouse->role_id = $request->role_id;
                $warehouse->warehouse_id = $request->warehouse_id;
            }

            $warehouse->save();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Warehouse is successfully updated!'
        ]);
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $warehouse = Warehouse::find($request->id);
            $warehouse->delete();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Warehouse is successfully deleted!'
        ]);
    }
}
