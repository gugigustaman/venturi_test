<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Item;
use DB;
use Illuminate\Http\Request;
use Validator;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::select(
            'name',
            'qty',
            'unit',
            'description',
            'user_id',
            'warehouse_id',
            'approved_at',
            'created_at',
        );

        if ($request->user->isCustomer()) {
            $items->where('user_id', $request->user->id);
        } else if ($request->user->isMarketing()) {
            $items->whereHas('user', function ($q) use ($request) {
                $q->where('warehouse_id', $request->user->warehouse_id);
            });

            if ($request->approved == 'no') {
                $items->whereNull('approved_at');
            }
        }

        $items = $items->get();

        return $items;
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'qty' => 'required',
            'unit' => 'required',
            'description' => 'required',
            'warehouse_id' => 'required'
        ]);

        if ($validation->fails()) {
            throw new ApiException('Bad request', 400, [
                'errors' => $validation->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $item = new Item();
            $item->name = $request->name;
            $item->qty = $request->qty;
            $item->unit = $request->unit;
            $item->description = $request->description;
            $item->user_id = $request->user->id;
            $item->warehouse_id = $request->warehouse_id;

            $item->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($e instanceof ApiException) {
                throw $e;
            }

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Item store is successfully requested! Waiting approval...'
        ]);
    }

    public function show(Request $request)
    {
        $item = Item::with('warehouse', 'role', 'level');

        if ($request->item->level_id == 2) {
            $item->where('warehouse_id', $request->item->warehouse_id);
        } else if ($request->item->level_id == 3) {
            throw new ApiException('Forbidden', 403);
        }

        $item = $item->find($request->id);

        if (!$item) {
            throw new ApiException('Not found', 404);
        }

        return $item;
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'itemname' => 'required|unique:items,itemname,' . $request->itemname,
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
            $item = Item::find($request->id);
            $item->itemname = $request->itemname;
            $item->name = $request->name;
            $item->password = $request->password;
            $item->status = 1;

            if ($request->item->isMarketing()) {
                $validation = Validator::make($request->all(), [
                    'role_id' => 'required|exists:roles,id|in:5,6',
                ]);

                if ($validation->fails()) {
                    throw new ApiException('Bad request', 400, [
                        'errors' => $validation->errors()->first()
                    ]);
                }

                $item->level_id = 3;
                $item->role_id = $request->role_id;
                $item->warehouse_id = $request->item->warehouse_id;
                $item->status = $request->status;
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

                $item->level_id = $request->level_id;
                $item->role_id = $request->role_id;
                $item->warehouse_id = $request->warehouse_id;
            }

            $item->save();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Item is successfully updated!'
        ]);
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $item = Item::find($request->id);
            $item->delete();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            throw new ApiException('Internal Server Error', 500);
        }

        return response([
            'message' => 'Item is successfully deleted!'
        ]);
    }
}
