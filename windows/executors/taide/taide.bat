@echo off
cd "%~dp0"

set userInput=y
set /p userInput=�n���z�۰ʤU�� Llama3-TAIDE-LX-8B-Chat-Alpha1.Q4_K_M �� GGUF �ҫ��� (�� 4.7GB)�H [Y/n] 

if /I "%userInput%"=="n" (
    echo �N���|�۰ʤU���Ӽҫ��A�z�i�H�b�U���n�Ӽҫ����J�Ӹ�Ƥ����A�éR�W��taide-8b-a.3-q4_k_m.gguf
    start .
     pause
) else (
     echo ���b�U���ҫ�...
     curl -L -o "taide-8b-a.3-q4_k_m.gguf" https://huggingface.co/ZoneTwelve/Llama3-TAIDE-LX-8B-Chat-Alpha1-GGUF/resolve/main/Llama3-TAIDE-LX-8B-Chat-Alpha1.Q4_K_M.gguf?download=true
     
)