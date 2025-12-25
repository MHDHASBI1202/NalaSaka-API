<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class FollowController extends Controller
{
    public function toggle(Request $request)
    {
        $request->validate([
            'target_user_id' => 'required|exists:users,id'
        ]);

        $me = $request->user();
        $targetId = $request->target_user_id;

        if ($me->id == $targetId) {
            return response()->json(['error' => true, 'message' => 'Tidak bisa mengikuti diri sendiri.'], 400);
        }

        $isFollowing = $me->following()->where('followed_id', $targetId)->exists();

        if ($isFollowing) {
            $me->following()->detach($targetId);
            $message = 'Berhenti mengikuti.';
            $status = false;
        } else {
            $me->following()->attach($targetId);
            $message = 'Mulai mengikuti!';
            $status = true;
        }

        return response()->json([
            'error' => false,
            'message' => $message,
            'isFollowing' => $status
        ]);
    }

    public function checkStatus($targetId, Request $request)
    {
        $me = $request->user();
        $isFollowing = $me->following()->where('followed_id', $targetId)->exists();

        return response()->json([
            'error' => false,
            'isFollowing' => $isFollowing
        ]);
    }
}