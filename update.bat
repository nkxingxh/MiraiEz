@ECHO OFF
TITLE miraiez update

git clone git@github.com:nkxingxh/miraiez.git

@for /f "delims=" %%i in ('dir data_* /b') do (
move "%%i" miraiez
)

del /F /Q "*.php"

XCOPY /E /Y "miraiez" ".\"
RD /S /Q "miraiez"