<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Models\SystemSetting;

class PlayController extends Controller
{
    function home(Request $request){
        if (SystemSetting::where('key', 'ai_election_enabled')->first()->value == 'true'){
            return view('ai_election.home');
        }
        return Redirect::route('play.home');
    }

    function update(Request $request){
        $result = "play_setting_saved";
        $model = SystemSetting::where("key", "ai_election_enabled")->first();
        $model->value = $request->input("ai_election_enabled") == "allow" ? "true" : "false";
        $model->save();

        return Redirect::route('dashboard.home')->with('status', $result);
    }
}
