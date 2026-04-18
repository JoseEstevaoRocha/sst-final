' SST Manager — Sincronização CAEPI em segundo plano
' Detecta automaticamente o diretório onde este arquivo está localizado
' Mostra janela minimizada com progresso (visível na barra de tarefas)

Dim WshShell, sDir, sCmd, sLog

sDir = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\") - 1)
sLog = sDir & "\storage\logs\caepi.log"
sCmd = "cmd /k ""cd /d """ & sDir & """ && php artisan caepi:sincronizar & echo. & echo Sincronizacao CAEPI concluida. Esta janela pode ser fechada. & pause"""

Set WshShell = CreateObject("WScript.Shell")
' 2 = janela minimizada (aparece na barra de tarefas), False = não aguarda
WshShell.Run sCmd, 2, False
Set WshShell = Nothing
