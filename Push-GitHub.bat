@echo off
title Push KA Tech Store to GitHub
cd /d "C:\Users\ka tech\Desktop\ka tech"
echo ====================================================
echo  KA TECH STORE — PUSH GITHUB (kakii24)
echo ====================================================
echo.
echo Verification du depot local...
git status
echo.
echo Envoi des 12 commits vers https://github.com/kakii24/katechstore.git ...
git push -u origin main
echo.
if %ERRORLEVEL% EQU 0 (
    echo ====================================================
    echo  SUCCES : Le projet katechstore a ete pousse avec succes !
    echo ====================================================
) else (
    echo ====================================================
    echo  NOTE : Si le depot n'existe pas encore sur GitHub,
    echo  veuillez le creer sur https://github.com/new avec le nom 'katechstore'
    echo ====================================================
)
pause
