@echo off
setlocal EnableDelayedExpansion
echo Now in: %cd%
REM Extract the folder name from the input
for %%e in ("%cd%.") do set "current_folder=%%~nxe"

REM Define an array to store the model types and their names
set "names[1]=ChatGPT"
set "names[2]=Gemini Pro"
set "names[3]=GGUF Model"
set "names[4]=HuggingFace Model"
set "names[5]=Custom Module"

REM Define an array to store the model types and their names
set "models[1]=chatgpt"
set "models[2]=geminipro"
set "models[3]=llamacpp"
set "models[4]=huggingface"
set "models[5]=custom"

REM Check if the current folder matches any option
for %%a in (1 2 3 4) do (
	if "!models[%%a]!"=="!current_folder!" (
		echo Using predefined...
		echo EXECUTOR_TYPE=!models[%%a]!
		echo EXECUTOR_NAME=!names[%%a]!
		echo EXECUTOR_ACCESS_CODE=!models[%%a]!
		
		set "EXECUTOR_TYPE=!models[%%a]!"
		set "EXECUTOR_NAME=!names[%%a]!"
		set "EXECUTOR_ACCESS_CODE=!models[%%a]!"
		goto skip_selection
	)
)

REM Display the options
echo Select an option:

for %%a in (1 2 3 4 5) do (
	echo %%a - !names[%%a]!
)

REM Ask for user input
:input_option
set /p "option=Enter the option number (1-5): "
if not defined models[%option%] (
    echo Invalid option. Please try again.
    goto input_option
)

REM Set the model type based on the selected option
set "EXECUTOR_TYPE=!models[%option%]!"

if "!option!" == "5" (
    REM Ask for worker path (must-fill field)
    :input_worker_path
    set /p "worker_path=Enter the worker path: "
    if "!worker_path!"=="" (
        echo Worker path cannot be blank. Please try again.
        goto input_worker_path
    )
)

REM Ask for model name
:input_EXECUTOR_NAME
set /p "EXECUTOR_NAME=Enter the model name: "
if "!EXECUTOR_NAME!"=="" (
    echo Model name cannot be blank. Please try again.
    goto input_EXECUTOR_NAME
)

REM Ask for access code (must-fill field)
:input_EXECUTOR_ACCESS_CODE
set /p "EXECUTOR_ACCESS_CODE=Enter the access code: "
if "!EXECUTOR_ACCESS_CODE!"=="" (
    echo Access code cannot be blank. Please try again.
    goto input_EXECUTOR_ACCESS_CODE
)

:skip_selection

REM Ask for API key if the model type is geminipro or ChatGPT
if "!EXECUTOR_TYPE!"=="geminipro" (
    set "api_key="
    :input_api_key
    set /p "api_key=Enter the API key (press Enter to leave blank): "
    if "!api_key!"=="" goto continue
) else if "!EXECUTOR_TYPE!"=="chatgpt" (
    set "api_key="
    :input_api_key
    set /p "api_key=Enter the API key (press Enter to leave blank): "
    if "!api_key!"=="" goto continue
)

:continue

REM Ask for model path if the model type is llamacpp or Hugging Face
if "!EXECUTOR_TYPE!"=="llamacpp" (
	for /r %%i in (*.gguf) do (
		echo "using founded .gguf file"
		echo model_path=%%~fi
		set "model_path=%%~fi"
		goto skip_model_path
	)

    :input_model_path
    set /p "model_path=Enter the model path: "
    if "!model_path!"=="" (
        echo Model path cannot be blank. Please try again.
        goto input_model_path
    )
) else if "!EXECUTOR_TYPE!"=="huggingface" (
	for /r %%i in (*.model *.bin *.safetensor) do (
		echo "model folder detected, using current folder path"
		echo model_path=%%~dpi
		set "model_path=%%~dpi"
		goto skip_model_path
	)
    :input_model_path
    set /p "model_path=Enter the model path: "
    if "!model_path!"=="" (
        echo Model path cannot be blank. Please try again.
        goto input_model_path
    )
)

:skip_model_path

for /r %%i in (*.jpg *.jpeg *.png *.gif *.webp *.bmp *.ico *.svg *.tiff *.tif *.jp2 *.jxr *.wdp *.hdp) do (
    echo "Image detected, using founded image."
    echo image_path=%%~fi
    set "image_path=%%~fi"
    goto skip_image_path
)

:input_image_path
set /p "image_path=Enter the image path: (press Enter to leave blank)"

:skip_image_path
	
del env.bat
REM Save to env.bat
if defined EXECUTOR_TYPE (
	echo set "EXECUTOR_TYPE=!EXECUTOR_TYPE!" >> env.bat
) else (
	echo set /U EXECUTOR_TYPE >> env.bat
)
if defined EXECUTOR_NAME (
	echo set "EXECUTOR_NAME=!EXECUTOR_NAME!" >> env.bat
) else (
	echo set /U EXECUTOR_NAME >> env.bat
)
if defined api_key (
	echo set "api_key=!api_key!" >> env.bat
) else (
	echo set /U api_key >> env.bat
)
if defined EXECUTOR_ACCESS_CODE (
	echo set "EXECUTOR_ACCESS_CODE=!EXECUTOR_ACCESS_CODE!" >> env.bat
) else (
	echo set /U EXECUTOR_ACCESS_CODE >> env.bat
)
if defined model_path (
	echo set "model_path=!model_path!" >> env.bat
) else (
	echo set /U model_path >> env.bat
)
if defined worker_path (
	echo set "worker_path=!worker_path!" >> env.bat
) else (
	echo set /U worker_path >> env.bat
)
if defined image_path (
	echo set "image_path=!image_path!" >> env.bat
) else (
	echo set /U image_path >> env.bat
)

echo Configuration saved to env.bat
pause