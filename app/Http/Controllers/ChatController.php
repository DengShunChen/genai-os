<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ChatRequest;
use Illuminate\Http\Request;
use App\Models\Histories;
use App\Jobs\RequestChat;
use App\Models\Chats;
use App\Models\LLMs;
use App\Models\User;
use Predis\Client;

class ChatController extends Controller
{
    public function main(Request $request)
    {
        $chat = Chats::findOrFail($request->route('chat_id'));
        if ($chat->user_id != Auth::user()->id) {
            return redirect()->route('chat');
        } elseif (LLMs::findOrFail($chat->llm_id)->enabled == true) {
            return view('chat');
        }
        return redirect()->route('archives', $request->route('chat_id'));
    }

    public function create(ChatRequest $request): RedirectResponse
    {
        $input = $request->input('input');
        if ($input){
            $chat = new Chats();
            $chat->fill(['name' => $input, 'llm_id' => $request->input('llm_id'), 'user_id' => $request->user()->id]);
            $chat->save();
            $history = new Histories();
            $history->fill(['msg' => $input, 'chat_id' => $chat->id, 'isbot' => false]);
            $history->save();
            $llm = LLMs::findOrFail($request->input('llm_id'));
            Redis::rpush('usertask_' . Auth::user()->id, $history->id);
            RequestChat::dispatch($history->id, $input, $llm->access_code, Auth::user()->id);
        }
        return Redirect::route('chats', $chat->id);
    }

    public function request(Request $request): RedirectResponse
    {
        $chatId = $request->input('chat_id');
        if ($chatId){
            $history = new Histories();
            $history->fill(['msg' => $request->input('input'), 'chat_id' => $chatId, 'isbot' => false]);
            $history->save();
            $access_code = LLMs::findOrFail(Chats::findOrFail($chatId)->llm_id)->access_code;
            Redis::rpush('usertask_' . Auth::user()->id, $history->id);
            RequestChat::dispatch($history->id, $request->input('input'), $access_code, Auth::user()->id);
        }
        return Redirect::route('chats', $chatId);
    }

    public function delete(Request $request): RedirectResponse
    {
        try {
            $chat = Chats::findOrFail($request->input('id'));
        } catch (ModelNotFoundException $e) {
            // Handle the exception here, for example:
            return Redirect::route('chat');
        }

        $chat->delete();
        return Redirect::route('chat');
    }

    public function edit(Request $request): RedirectResponse
    {
        try {
            $chat = Chats::findOrFail($request->input('id'));
        } catch (ModelNotFoundException $e) {
            // Handle the exception here, for example:
            return response()->json(['error' => 'Chat not found'], 404);
        }
        $chat->fill(['name' => $request->input('new_name')]);
        $chat->save();
        return Redirect::route('chats', $request->input('id'));
    }

    public function ResetRedis(Request $request)
    {
        Redis::flushAll();
        return Redirect::route('dashboard');
    }

    public function SSE(Request $request)
    {
        $response = response()->stream(function () {
            $lengths = [];
            $listening = Redis::lrange('usertask_' . Auth::user()->id, 0, -1);
            $start_time = time();
            $timeouts = count($listening) + 30;
            set_time_limit($timeouts);
            foreach ($listening as $history_id) {
                $lengths[$history_id] = 0;
            }
            while (!empty($listening)) {
                $new_listening = Redis::lrange('usertask_' . Auth::user()->id, 0, -1);
                foreach ($listening as $history_id) {
                    $finished = false;
                    if (array_search($history_id, $new_listening) === false) {
                        $finished = true;
                    }
                    $result = Redis::get('msg' . $history_id);
                    # Validate and convert for the encoding of incoming message
                    $encoding = mb_detect_encoding($result, 'UTF-8, ISO-8859-1', true);
                    if ($encoding !== 'UTF-8') {
                        $result = mb_convert_encoding($result, 'UTF-8', $encoding);
                    }
                    $newData = mb_substr($result, $lengths[$history_id], null, 'utf-8');
                    $length = mb_strlen($newData, 'utf-8');
                    for ($i = 0; $i < $length; $i++) {
                        # Make sure the data is correctly encoded and output a character at a time
                        $char = mb_substr($newData, $i, 1, 'utf-8');
                        if (mb_check_encoding($char, 'utf-8')) {
                            $lengths[$history_id] += 1;
                            echo 'data: ' . $history_id . ',' . $char . "\n\n";
                            # each token should restore 30 seconds of timeout
                            set_time_limit(time() - $start_time + 30 + count($listening));
                            #Flush the buffer
                            ob_flush();
                            flush();
                        }
                    }
                    if ($finished) {
                        unset($lengths[$history_id]);
                        $key = array_search($history_id, $listening);
                        if ($key !== false) {
                            unset($listening[$key]);
                        }
                    }
                    usleep(1000000); #For each Request, wait 1 second
                }
            }
            echo "event: close\n\n";
            ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('charset', 'utf-8');
        $response->headers->set('Connection', 'close');
        return $response;
    }
}
