' SST Manager — Agendador Laravel em segundo plano (sem janela)
' Detecta automaticamente o diretório onde este arquivo está localizado

Dim WshShell, sDir, sCmd

sDir = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\") - 1)
sCmd = "php artisan schedule:work"

Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = sDir
' 0 = janela oculta, False = não aguarda
WshShell.Run sCmd, 0, False
Set WshShell = Nothing
