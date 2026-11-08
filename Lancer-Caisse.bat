@echo off
title KA Tech - Caisse & Registre Boutique
cd /d "%~dp0"

echo ========================================================
echo        KA TECH STORE - REGISTRE & CAISSE BOUTIQUE
echo ========================================================
echo.
echo Demarrage du serveur de synchronisation en temps reel...

netstat -ano | findstr :3456 >nul
if %errorlevel% neq 0 (
    start "" /b node server.js
    timeout /t 2 >nul
)

echo Ouverture de l'application dans votre navigateur...
start http://localhost:3456

echo.
echo Application en cours d'execution sur http://localhost:3456
echo Synchronise en direct avec: https://katechstore.net
echo.
echo Vous pouvez reduire cette fenetre.
