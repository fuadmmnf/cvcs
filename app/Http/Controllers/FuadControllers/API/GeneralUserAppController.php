<?php

namespace App\Http\Controllers\FuadControllers\API;

use App\Album;
use App\Branch;
use App\Notice;
use App\Payment;
use App\Position;
use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class GeneralUserAppController
{
    public function getPaginatedNotices(){
        $notices = Notice::orderBy('id', 'DESC')->paginate(4);
        return response()->json($notices);
    }

    public function getPaginatedGallery(){
        $albums = Album::orderBy('id', 'DESC')->paginate(4);
        return response()->json($albums);
    }

    public function getDesignations(){
        $positions = Position::where('id', '>', 0)->get();
        return response()->json($positions);
    }

    public function getBranches(){
        $branches = Branch::where('id', '>', 0)->get();
        return response()->json($branches);
    }

    public function getUserProfile($unique_key){
        $member = User::where('unique_key', $unique_key)->first();

        if(!$member){
            return response()->json("user with unique key does not exist", 404);
        }

        $pendingfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $approvedfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $pendingcountdashboard = Payment::where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();
        $approvedcountdashboard = Payment::where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();


        return response()->json([
            'user' => $member,
            'pendingfordashboard' => $pendingfordashboard,
            'approvedfordashboard' => $approvedfordashboard,
            'pendingcountdashboard' => $pendingcountdashboard,
            'approvedcountdashboard' => $approvedcountdashboard
        ], 200);
    }
}
