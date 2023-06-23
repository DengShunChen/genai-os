<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use App\Events\RequestStatus;
use App\Models\Histories;
use GuzzleHttp\Client;
use Carbon\Carbon;

class RequestChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $input, $access_code, $msgtime, $history_id, $user_id, $chat_id, $chatgpt_apitoken;
    public $tries = 100; # Wait 1000 seconds in total
    public $timeout = 1200; # For the 100th try, 200 seconds limit is given
    /**
     * Create a new job instance.
     */
    public function __construct($chat_id, $input, $access_code, $user_id, $history_id, $chatgpt_apitoken)
    {
        $this->input = $input;
        $this->msgtime = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 second'));
        $this->access_code = $access_code;
        $this->user_id = $user_id;
        $this->chat_id = $chat_id;
        $this->history_id = $history_id;
        if ($chatgpt_apitoken == null)$chatgpt_apitoken = "";
        $this->chatgpt_apitoken = $chatgpt_apitoken;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (Histories::findOrFail($this->history_id)->msg != "* ...thinking... *") {
            Log::Debug("Hmmm");
            return;
        }
        Log::channel("analyze")->Info("In:" . $this->access_code . "|" . $this->user_id . "|" . $this->history_id . "|" . strlen(trim($this->input)) . "|" . trim($this->input));
        $start = microtime(true); 
        $tmp = '';
        try {
            $agent_location = \App\Models\SystemSetting::where('key', 'agent_location')->first()->value;
            $client = new Client(['timeout' => 300]);
            $response = $client->post($agent_location . 'status', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => [
                    'name' => $this->access_code,
                    'history_id' => $this->history_id,
                ],
                'stream' => true,
            ]);
            if ($response->getBody()->getContents() == 'BUSY') {
                $this->release(10);
            } else {
                try {
                    $response = $client->post($agent_location, [
                        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                        'form_params' => [
                            'input' => $this->input,
                            'name' => $this->access_code,
                            'history_id' => $this->history_id,
                            "chatgpt_apitoken" => $this->chatgpt_apitoken
                        ],
                        'stream' => true,
                    ]);

                    $stream = $response->getBody();
                    $buffer = '';
                    while (!$stream->eof()) {
                        $chunk = $stream->read(1);
                        $buffer .= $chunk;
                        $bufferLength = mb_strlen($buffer, 'UTF-8');
                        $messageLength = null;
                        for ($i = 1; $i <= $bufferLength; $i++) {
                            if (ord($buffer[$i - 1]) < 128 || $i == $bufferLength) {
                                $messageLength = $i;
                                break;
                            }
                        }
                        if ($messageLength !== null) {
                            $message = mb_substr($buffer, 0, $messageLength, 'UTF-8');
                            if (mb_check_encoding($message, 'UTF-8')) {
                                $tmp .= $message;
                                Redis::publish($this->history_id, 'New ' . $tmp);
                                $buffer = mb_substr($buffer, $messageLength, null, 'UTF-8');
                            }
                        }
                        if (mb_strlen($tmp) > 1100) {
                            break;
                        }
                    }
                    if (trim($tmp) == '') {
                        Redis::publish($this->history_id, 'New [Oops, seems like LLM given empty message as output, Please try again!]');
                    } else {
                        Redis::publish($this->history_id, 'New ' . trim($tmp));
                    }
                } catch (Exception $e) {
                    Redis::publish($this->history_id, 'New ' . $tmp . "\n[Sorry, something is broken!]");
                    Log::channel("analyze")->Debug("failJob " . $this->history_id);
                } finally {
                    try {
                        $history = Histories::findOrFail($this->history_id);
                        $history->fill(['msg' => trim($tmp)]);
                        $history->save();
                    } catch (Exception $e) {
                    }
                    Redis::publish($this->history_id, 'Ended Ended');
                    Redis::lrem('usertask_' . $this->user_id, 0, $this->history_id);
                    $end = microtime(true); // Record end time
                    $elapsed = $end - $start; // Calculate elapsed time
                    Log::channel("analyze")->Info("Out:" . $this->access_code . "|" . $this->user_id . "|" . $this->history_id . "|" . $elapsed . "|" . strlen(trim($tmp)) . "|" . Carbon::createFromFormat('Y-m-d H:i:s', $this->msgtime)->diffInSeconds(Carbon::now()) . "|" . trim(str_replace("\n", "[NEWLINEPLACEHOLDERUWU]", $tmp)));
                }
            }
        } catch (Exception $e) {
        }
    }
}
