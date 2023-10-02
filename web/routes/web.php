<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LLMController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\DuelController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ManageController;
use BeyondCode\LaravelSSE\Facades\SSE;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\LLMs;
use App\Models\Chats;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('/');

Route::get('/api_auth', [ProfileController::class, 'api_auth']);
Route::get('/api_stream', [ProfileController::class, 'api_stream'])->name('api.stream');

# Admin routes, require admin permission
Route::middleware('auth', 'verified', AdminMiddleware::class . ':tab_Dashboard')->group(function () {
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', function () {
            return view('dashboard');
        })->name('dashboard.home');

        Route::group(['prefix' => 'LLMs'], function () {
            Route::get('/toggle/{llm_id}', [LLMController::class, 'toggle'])->name('dashboard.llms.toggle');
            Route::delete('/delete', [LLMController::class, 'delete'])->name('dashboard.llms.delete');
            Route::post('/create', [LLMController::class, 'create'])->name('dashboard.llms.create');
            Route::patch('/update', [LLMController::class, 'update'])->name('dashboard.llms.update');
        });
    });
});

# User routes, required email verified
Route::middleware('auth', 'verified')->group(function () {
    #---Profiles
    Route::middleware(AdminMiddleware::class . ':tab_Profile')
        ->prefix('profile')
        ->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');

            Route::middleware(AdminMiddleware::class . ':Profile_update_api_token')
                ->patch('/api', [ProfileController::class, 'renew'])
                ->name('profile.api.renew');
            Route::middleware(AdminMiddleware::class . ':Profile_update_openai_token')
                ->patch('/chatgpt/api', [ProfileController::class, 'chatgpt_update'])
                ->name('profile.chatgpt.api.update');

            Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
            Route::middleware(AdminMiddleware::class . ':Profile_delete_account')
                ->delete('/', [ProfileController::class, 'destroy'])
                ->name('profile.destroy');
        });

    #---Chats
    Route::middleware(AdminMiddleware::class . ':tab_Chat')
        ->prefix('chats')
        ->group(function () {
            Route::get('/', [ChatController::class, 'home'])->name('chat.home');

            Route::get('/new/{llm_id}', function ($llm_id) {
                if (!LLMs::findOrFail($llm_id)->exists()) {
                    return redirect()->route('chat');
                }
                return view('chat');
            })->name('chat.new');
            
            Route::get('/chain', [ChatController::class, 'update_chain'])->name('chat.chain');
            Route::get('/stream', [ChatController::class, 'SSE'])->name('chat.sse');
            Route::get('/{chat_id}', [ChatController::class, 'main'])->name('chat.chat');
            Route::post('/create', [ChatController::class, 'create'])->name('chat.create');
            Route::post('/request', [ChatController::class, 'request'])->name('chat.request');
            Route::post('/edit', [ChatController::class, 'edit'])->name('chat.edit');
            Route::delete('/delete', [ChatController::class, 'delete'])->name('chat.delete');
        })
        ->name('chat');

    #---Archives
    Route::middleware(AdminMiddleware::class . ':tab_Archive')
        ->prefix('archive')
        ->group(function () {
            Route::get('/', function () {
                return view('archive');
            })->name('archive.home');

            Route::get('/{chat_id}', [ArchiveController::class, 'main'])->name('archive.chat');
            Route::post('/edit', [ArchiveController::class, 'edit'])->name('archive.edit');
            Route::delete('/delete', [ArchiveController::class, 'delete'])->name('archive.delete');
        })
        ->name('archive');

    #---Duel
    Route::middleware(AdminMiddleware::class . ':tab_Duel')
        ->prefix('duel')
        ->group(function () {
            Route::get('/', [DuelController::class, 'main'])->name('duel.home');

            Route::post('/create', [DuelController::class, 'create'])->name('duel.create');
            Route::get('/{duel_id}', [DuelController::class, 'main'])->name('duel.chat');
            Route::post('/edit', [DuelController::class, 'edit'])->name('duel.edit');
            Route::delete('/delete', [DuelController::class, 'delete'])->name('duel.delete');
            Route::post('/request', [DuelController::class, 'request'])->name('duel.request');
        })
        ->name('duel');

    #---Play
    Route::middleware(AdminMiddleware::class . ':tab_Play')
        ->prefix('play')
        ->group(function () {
            Route::get('/', function () {
                return view('play');
            })->name('play.home');

            Route::prefix('ai_election')
                ->group(function () {
                    Route::get('/', [PlayController::class, 'play'])->name('play.ai_elections.home');
                    Route::patch('/update', [PlayController::class, 'update'])->name('play.ai_elections.update');
                })
                ->name('play.ai_elections');
        })
        ->name('play');

    #---Play
    Route::middleware(AdminMiddleware::class . ':tab_Manage')
        ->prefix('manage')
        ->group(function () {
            Route::get('/', function () {
                return view('manage.home');
            })->name('manage.home');

            Route::prefix('group')
                ->group(function () {
                    Route::post('/create', [ManageController::class, 'group_create'])->name('manage.group.create');
                    Route::patch('/update', [ManageController::class, 'group_update'])->name('manage.group.update');
                    Route::delete('/delete', [ManageController::class, 'group_delete'])->name('manage.group.delete');
                })
                ->name('manage.group');

            Route::prefix('user')
                ->group(function () {
                    Route::post('/create', [ManageController::class, 'user_create'])->name('manage.user.create');
                    Route::patch('/update', [ManageController::class, 'user_update'])->name('manage.user.update');
                    Route::delete('/delete', [ManageController::class, 'user_delete'])->name('manage.user.delete');
                    Route::post('/search', [ManageController::class, 'search_user'])->name('manage.user.search');
                })
                ->name('manage.user');

            Route::prefix('setting')
                ->group(function () {
                    Route::get('/resetRedis', [SystemController::class, 'ResetRedis'])->name('manage.setting.resetRedis');
                    Route::patch('/update', [SystemController::class, 'update'])->name('manage.setting.update');
                })
                ->name('manage.user');

                
            Route::post('/tab', [ManageController::class, 'tab'])->name('manage.tab');
        })
        ->name('play');
});

require __DIR__ . '/auth.php';
