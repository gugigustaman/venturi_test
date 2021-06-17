<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Token;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('username', $request->username)
            ->where('password', hash('sha256', $request->password))
            ->first();

        if (!$user) {
            throw new ApiException('Unauthorized', 401);
        }

        $token = new Token();
        $token->user_id = $user->id;
        $token->token = hash('sha256', 'VENTURI' . Carbon::now()->format('u'));
        $token->expired_at = Carbon::now()->addMinutes(config('app.token_expiry'));

        $token->save();

        return response()->json([
            'message' => 'Successfully logged in!',
            'user' => $user,
            'token' => $token->token
        ]);
    }
}
