@echo off
setlocal

set MSECT=1
set MPATH=C:\cmder\Manpages

if "%1" == "" (
  echo Usage: man [-M path] [section] name
  goto end
)

if "%1" == "-M" (
  set MPATH=%2
  shift
  shift
)

if not "%2" == "" (
  set MSECT=%1
  shift
)

set MFILE=%MPATH%\man%MSECT%\%1.%MSECT%

if not exist %MFILE% (
  echo Man entry not found
  echo %MFILE%
  goto end
)

:: Make sure you have groff and less in your path.
groff -Tascii -pet -mandoc -P-c %MFILE% | less -irs

:end
endlocal