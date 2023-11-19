<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Jobs\RequestChat;
use App\Models\DuelChat;
use App\Models\Histories;
use App\Models\Chats;
use App\Models\LLMs;
use DB;
use Session;

class DuelController extends Controller
{
    public function main(Request $request)
    {
        $duel_id = $request->route('duel_id');
        if ($duel_id) {
            $chat = DuelChat::findOrFail($duel_id);
            if ($chat->user_id != Auth::user()->id) {
                return redirect()->route('duel.home');
            } elseif (true) {
                #LLMs::findOrFail($chat->llm_id)->enabled == true) {
                return view('duel');
            }
        }
        return view('duel');
        #return redirect()->route('archives', $request->route('chat_id'));
    }

    public function create(Request $request): RedirectResponse
    {
        $llms = $request->input('llm');
        $selectedLLMs = $request->input('chatsTo');
        if (count($selectedLLMs) > 0 && count($llms) > 1) {
            $result = DB::table(function ($query) {
                $query
                    ->select(DB::raw('substring(name, 7) as model_id'), 'perm_id')
                    ->from('group_permissions')
                    ->join('permissions', 'perm_id', '=', 'permissions.id')
                    ->where('group_id', Auth()->user()->group_id)
                    ->where('name', 'like', 'model_%')
                    ->get();
            }, 'tmp')
                ->join('llms', 'llms.id', '=', DB::raw('CAST(tmp.model_id AS BIGINT)'))
                ->select('llms.id')
                ->where('llms.enabled', true)
                ->get()
                ->pluck('id')
                ->toarray();

            foreach ($llms as $i) {
                if (!in_array($i, $result)) {
                    return back();
                }
            }
            # model permission auth done
            foreach ($selectedLLMs as $id) {
                if (!in_array($id, $llms)) {
                    return back();
                }
            }
            $input = $request->input('input');
            $Duel = new DuelChat();
            $Duel->fill(['name' => $input, 'user_id' => Auth::user()->id]);
            $Duel->save();
            foreach ($llms as $llm) {
                $chat = new Chats();
                $chat->fill(['name' => 'Duel Chat', 'llm_id' => $llm, 'user_id' => Auth::user()->id, 'dcID' => $Duel->id]);
                $chat->save();
                if (in_array($llm, $selectedLLMs)) {

                    $history = new Histories();
                    $history->fill(['msg' => $input, 'chat_id' => $chat->id, 'isbot' => false]);
                    $history->save();
                    $history = new Histories();
                    $history->fill(['msg' => '* ...thinking... *', 'chat_id' => $chat->id, 'isbot' => true, 'created_at' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 second'))]);
                    $history->save();
                    RequestChat::dispatch(json_encode([['msg' => $input, 'isbot' => false]]), LLMs::findOrFail($chat->llm_id)->access_code, Auth::user()->id, $history->id, Auth::user()->openai_token);
                    Redis::rpush('usertask_' . Auth::user()->id, $history->id);
                }
            }
        }
        return redirect()->to(route('duel.chat', $Duel->id) . ($request->input('limit') ? '?limit=' . $request->input('limit') : ''))->with("selLLMs",$selectedLLMs);
    }

    public function new(Request $request): RedirectResponse
    {
        $llms = $request->input('llm');
        if (count($llms) > 1) {
            $result = DB::table(function ($query) {
                $query
                    ->select(DB::raw('substring(name, 7) as model_id'), 'perm_id')
                    ->from('group_permissions')
                    ->join('permissions', 'perm_id', '=', 'permissions.id')
                    ->where('group_id', Auth()->user()->group_id)
                    ->where('name', 'like', 'model_%')
                    ->get();
            }, 'tmp')
                ->join('llms', 'llms.id', '=', DB::raw('CAST(tmp.model_id AS BIGINT)'))
                ->select('llms.id')
                ->where('llms.enabled', true)
                ->get()
                ->pluck('id')
                ->toarray();

            foreach ($llms as $i) {
                if (!in_array($i, $result)) {
                    return back();
                }
            }

            return redirect()
                ->route('duel.home')
                ->with('llms', $llms);
        }

        return redirect()->route('duel.home');
    }

    public function delete(Request $request): RedirectResponse
    {
        try {
            $ids = [];
            $chats = DuelChat::findOrFail($request->input('id'));
            foreach (Chats::where('dcID', '=', $chats->id)->get() as $chat) {
                $ids[] = $chat->llm_id;
                Histories::where('chat_id', '=', $chat->id)->delete();
            }
            Chats::where('dcID', '=', $chats->id)->delete();
            $chats->delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Chat not found: ' . $request->input('id'));
        }
        return redirect()
            ->to(route('duel.home') . ($request->input('limit') ? '?limit=' . $request->input('limit') : ''))
            ->with('llms', $ids);
    }

    public function edit(Request $request): RedirectResponse
    {
        try {
            $chat = DuelChat::findOrFail($request->input('id'));
            $chat->fill(['name' => $request->input('new_name')]);
            $chat->save();
        } catch (ModelNotFoundException $e) {
            Log::error('Chat not found: ' . $request->input('id'));
        }
        return redirect()->to(route('duel.chat', $request->input('id')) . ($request->input('limit') ? '?limit=' . $request->input('limit') : ''));
    }

    public function request(Request $request): RedirectResponse
    {
        $duelId = $request->input('duel_id');
        $selectedLLMs = $request->input('chatsTo');
        $input = $request->input('input');
        $chained = Session::get('chained') == 'true';
        if (count($selectedLLMs) > 0 && $duelId && $input) {
            $chats = Chats::where('dcID', $request->input('duel_id'))->get();
            if (
                Chats::join('llms', 'llms.id', '=', 'llm_id')
                    ->where('dcID', $request->input('duel_id'))
                    ->get()
                    ->where('enabled', false)
                    ->count() == 0
            ) {
                $result = DB::table(function ($query) {
                    $query
                        ->select(DB::raw('substring(name, 7) as model_id'), 'perm_id')
                        ->from('group_permissions')
                        ->join('permissions', 'perm_id', '=', 'permissions.id')
                        ->where('group_id', Auth()->user()->group_id)
                        ->where('name', 'like', 'model_%')
                        ->get();
                }, 'tmp')
                    ->join('llms', 'llms.id', '=', DB::raw('CAST(tmp.model_id AS BIGINT)'))
                    ->select('llms.id')
                    ->where('llms.enabled', true)
                    ->get()
                    ->pluck('id')
                    ->toarray();

                foreach ($chats->pluck('llm_id')->toarray() as $i) {
                    if (!in_array($i, $result)) {
                        return back();
                    }
                }
                foreach ($selectedLLMs as $id) {
                    if (!in_array($id, $chats->pluck('llm_id')->toarray())) {
                        return back();
                    }
                }
                #Model permission checked
                foreach ($chats as $chat) {
                    if (in_array($chat->llm_id, $selectedLLMs)) {
                        $history = new Histories();
                        $history->fill(['msg' => $input, 'chat_id' => $chat->id, 'isbot' => false]);
                        $history->save();
                        if (in_array(LLMs::find($chat->llm_id)->access_code, ['doc_qa', 'web_qa', 'doc_qa_b5', 'web_qa_b5']) && !$chained) {
                            $tmp = json_encode([
                                [
                                    'msg' => Histories::where('chat_id', '=', $chat->id)
                                        ->select('msg')
                                        ->orderby('created_at')
                                        ->orderby('id', 'desc')
                                        ->get()
                                        ->first()->msg,
                                    'isbot' => false,
                                ],
                                ['msg' => $request->input('input'), 'isbot' => false],
                            ]);
                        } elseif ($chained) {
                            $tmp = Histories::where('chat_id', '=', $chat->id)
                                ->select('msg', 'isbot')
                                ->orderby('created_at')
                                ->orderby('id', 'desc')
                                ->get()
                                ->toJson();
                        } else {
                            $tmp = json_encode([['msg' => $input, 'isbot' => false]]);
                        }

                        $history = new Histories();
                        $history->fill(['msg' => '* ...thinking... *', 'chat_id' => $chat->id, 'isbot' => true, 'created_at' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 second'))]);
                        $history->save();
                        RequestChat::dispatch($tmp, LLMs::findOrFail($chat->llm_id)->access_code, Auth::user()->id, $history->id, Auth::user()->openai_token);
                        Redis::rpush('usertask_' . Auth::user()->id, $history->id);
                    }
                }
            }
        }
        return redirect()->to(route('duel.chat', $duelId) . ($request->input('limit') ? '?limit=' . $request->input('limit') : ''))->with("selLLMs",$selectedLLMs);
    }
}
