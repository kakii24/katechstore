@echo off
title KA Tech - Serveur Vite Localhost
cd /d "%~dp0site"

echo ========================================================
echo        KA TECH STORE - SERVEUR LOCAL VITE
echo ========================================================
echo.
echo Demarrage du serveur Vite sur http://localhost:5173...

start http://localhost:5173
npx vite
