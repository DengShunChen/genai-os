@php
    $allowedCIDRs = array_filter(explode(',', env('ALLOWED_IPS', '')), 'strlen');
    $ip_allowed = !$allowedCIDRs || App\Http\Controllers\ProfileController::isIPInCIDRList(request()->ip(), $allowedCIDRs);
@endphp


<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased scrollbar">
    <div
        class="relative z-9999 min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">
        @if (Route::has('login'))
            <div class="p-6 text-right">
                @if ($ip_allowed)
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            @if (Auth::user()->hasPerm('tab_Dashboard'))
                                <a href="{{ url('/dashboard') }}"
                                    class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('dashboard.route') }}</a>
                            @endif
                            @if (Auth::user()->hasPerm('tab_Room'))
                                <a href="{{ route('room.home') }}"
                                    class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('room.route') }}</a>
                            @endif
                            @if (Auth::user()->hasPerm('tab_Chat'))
                                <a href="{{ route('chat.home') }}"
                                    class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('chat.route') }}</a>
                            @endif
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                                class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('login.button.sign_out') }}</a>
                            <a class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                href="{{ route('lang') }}">{{ __('profile.button.change_lang') }}</a>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                            class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('login.button.sign_in') }}</a>

                        @if (Route::has('register') &&
                                \App\Models\SystemSetting::where('key', 'allowRegister')->where('value', 'true')->exists())
                            <a href="{{ route('register') }}"
                                class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('login.button.sign_up') }}</a>
                        @endif

                        
                        <a class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            href="{{ route('lang') }}">{{ __('profile.button.change_lang') }}</a>
                    @endauth
                @else
                    @env('nuk')
                    <a
                        class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('welcome.service_campus_only') }}</a>
                @else
                    <a
                        class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">{{ __('welcome.service_internal_only') }}</a>
                    @endenv
                    <a class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        href="{{ route('lang') }}">{{ __('profile.button.change_lang') }}</a>
                @endif
            </div>
        @endif


        <div class="max-w-7xl mx-auto px-6 pt-6 lg:px-8 lg:pt-8 pb-3">
            <x-Logo />
            <div class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    <div
                        class="scale-100 justify-center p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div class="flex flex-col w-full">
                            <h2 class="text-xl font-semibold text-center text-gray-900 dark:text-white">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                {{ __('welcome.comparative') }}
                            @else
                                {{ __('welcome.translate') }}
                                @endenv
                            </h2>
                            <div id="chatroom"
                                class="flex-1 p-4 justify-center overflow-hidden flex flex-col scrollbar rounded-lg">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                <div class="flex w-full mt-2 space-x-3 ml-auto justify-end">
                                    <div>
                                        <div class="p-3 bg-blue-600 text-white rounded-l-lg rounded-br-lg">
                                            <p class="text-sm">你好</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        User
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/TAIDE.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">
                                                您好！我是TAIDE，一個來自台灣的AI助理，樂於以台灣人的立場幫助您，使用繁體中文來回答您的問題。請您隨時提出問題，我將盡我所能給予協助。
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/taibun.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">你好！有啥物我會使幫助你的？</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/hakka.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">你好！當歡喜看著你。有麼个𠊎做得𢯭手个無？</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/meta.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">Hello! 😊 I'm here to help you with any questions or
                                                concerns you may have. Please feel free to...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/chatglm.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">你好👋！我是人工智能助手 ChatGLM3-6B，很高兴见到你，欢迎问我任何问题。</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex w-full mt-2 space-x-3 ml-auto justify-end">
                                    <div>
                                        <div class="p-3 bg-blue-600 text-white rounded-l-lg rounded-br-lg">
                                            <p class="text-sm">請翻譯成繁體中文：The International Federation of the Phonographic
                                                Industry has announced it's latest Global Artist Chart, which features a
                                                Taiwanese artist in the top 10. ...</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        User
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/TAIDE.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">
                                                國際唱片業協會公佈了最新的全球藝術家排行榜，其中一位臺灣藝術家上升至前十名。...</p>
                                        </div>
                                    </div>
                                </div>
                                @endenv
                            </div>
                        </div>
                    </div>
                    <div
                        class="scale-100 justify-center p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div class="flex flex-col w-full">
                            <h2 class="text-xl font-semibold text-center text-gray-900 dark:text-white">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                {{ __('welcome.deployment') }}
                            @else
                                {{ __('welcome.composition') }}
                                @endenv
                            </h2>
                            <div id="chatroom"
                                class="flex-1 p-4 justify-center overflow-hidden flex flex-col scrollbar rounded-lg">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                <div class="flex justify-center items-center">
                                    <img class="w-auto dark:hidden" src="{{ asset('images/deployment_light.png') }}">
                                    <img class="w-auto hidden dark:block"
                                        src="{{ asset('images/deployment_dark.png') }}">
                                </div>
                            @else
                                <div class="flex w-full mt-2 space-x-3 ml-auto justify-end">
                                    <div>
                                        <div class="p-3 bg-blue-600 text-white rounded-l-lg rounded-br-lg">
                                            <p class="text-sm">
                                                寫一篇文章關於你曾收到的一份特別的禮物。描述你在何種情況下收到這份禮物，以及禮物的特別之處。題目：《一份特別的禮物》</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        User
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/TAIDE.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">
                                                我曾經收到一份特別珍貴的禮物，那是我的朋友送我的。當時我們正在一個朋友的生日派對上，他突然走到我身旁，告訴我他有一個禮物想給我。我很驚訝，因為...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endenv
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    <div
                        class="scale-100 justify-center p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div class="flex flex-col w-full">
                            <h2 class="text-xl font-semibold text-center text-gray-900 dark:text-white">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                {{ __('welcome.export_and_import') }}
                            @else
                                {{ __('welcome.communication') }}
                                @endenv
                            </h2>
                            <div id="chatroom"
                                class="flex-1 p-4 justify-center overflow-hidden flex flex-col scrollbar rounded-lg">

                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                <div class="flex justify-center items-center">
                                    <img class="w-auto dark:hidden" src="{{ asset('images/feedback.png') }}">
                                    <img class="w-auto hidden dark:block" src="{{ asset('images/feedback.png') }}">
                                </div>
                            @else
                                <div class="flex w-full mt-2 space-x-3 ml-auto justify-end">
                                    <div>
                                        <div class="p-3 bg-blue-600 text-white rounded-l-lg rounded-br-lg">
                                            <p class="text-sm">
                                                給一位朋友寫一封信，推薦他們去臺灣旅遊的五個必去景點以及各自的特色，並說明為什麼這些地方值得一遊。</p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        User
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/TAIDE.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">親愛的朋友，<br>
                                                在這封信中，我將向您推薦五個台灣必去的景點，以及每個地方的特色。這些景點都值得您一遊，以下是我對它們的詳細介紹：<br>
                                                1.台北101：台北101曾經是世界上最高的摩天大樓，...</p>
                                        </div>
                                    </div>
                                </div>
                                @endenv
                            </div>
                        </div>
                    </div>

                    <div
                        class="scale-100 justify-center p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                        <div class="flex flex-col w-full">
                            <h2 class="text-xl font-semibold text-center text-gray-900 dark:text-white">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                {{ __('welcome.application') }}
                            @else
                                {{ __('welcome.summarization') }}
                                @endenv
                            </h2>
                            <div id="chatroom"
                                class="flex-1 p-4 justify-center overflow-hidden flex flex-col scrollbar rounded-lg">
                                @env(['kuwa', 'arena', 'csie', 'chipllm', 'icdesign'])
                                <div class="flex justify-center items-center">
                                    <img class="w-auto dark:hidden" src="{{ asset('images/rag_light.png') }}">
                                    <img class="w-auto hidden dark:block" src="{{ asset('images/rag_dark.png') }}">
                                </div>
                            @else
                                <div class="flex w-full mt-2 space-x-3 ml-auto justify-end">
                                    <div>
                                        <div class="p-3 bg-blue-600 text-white rounded-l-lg rounded-br-lg">
                                            <p class="text-sm">
                                                請提供這篇文章的要點概述。DEEPMIND近日又開發出新一代AI棋類高手ALPHAZERO，只靠增強式學習演算法和遊戲規則，從隨機下棋開始歷經數百萬回自我博弈，來自學圍棋，西洋棋和日本象棋，並打敗這些棋賽的電腦程式冠軍，甚至在圍棋對弈中贏過ALPHAGO。...
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        User
                                    </div>
                                </div>
                                <div class="flex w-full mt-2 space-x-3 ">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                        <img src="{{ asset('images/TAIDE.png') }}">
                                    </div>
                                    <div>
                                        <div class="p-3 bg-gray-300 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">本文概述如下：<br>
                                                1.開發出一代新的AI棋高手AlphaZero<br>
                                                ...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endenv
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-center mt-4 px-0 sm:items-center sm:justify-between">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400 sm:text-left">
                    <div class="flex items-center gap-4">
                        @env(['kuwa', 'arena', 'nuk', 'csie', 'chipllm', 'icdesign'])
                        <a href="https://www.gai.tw/" target="_blank"
                            class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">由國立高雄大學
                            資訊工程學系<br>開發與維護的語言模型平台</a>
                    @else
                        <a href="https://www.nuk.edu.tw/" target="_blank"
                            class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                            {!! __('welcome.develope_by') !!}
                        </a>
                        @endenv
                    </div>
                </div>

                <div class="ml-4 text-center text-sm text-gray-500 dark:text-gray-400 sm:text-right sm:ml-0">
                    @env(['kuwa', 'arena', 'nuk', 'csie', 'chipllm', 'icdesign'])
                    @env(['nuk', 'csie'])
                    <a class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        href="https://www.nuk.edu.tw/" target="_blank">國立高雄大學</a>
                @else
                    <a class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        href="https://www.gai.tw/" target="_blank">Kuwa</a>
                    @endenv
                @else
                    <a class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        href="https://www.twcc.ai/" target="_blank">{{ __('welcome.powered_by') }}</a>
                    @endenv
                    <span class="text-black dark:text-white flex justify-end text-sm">{{ __('welcome.version') }}
                        0.1.0.1</span>
                </div>
            </div>

        </div>
    </div>
</body>

</html>
