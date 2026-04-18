' SST Manager — Servidor PHP em segundo plano (sem janela)
' Detecta automaticamente o diretório onde este arquivo está localizado

Dim WshShell, sDir, sCmd

' WScript.ScriptFullName retorna o caminho completo deste .vbs
' InStrRev encontra a última barra invertida para extrair só o diretório
sDir = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\") - 1)
sCmd = "php artisan serve --host=127.0.0.1 --port=8000"

Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = sDir
' 0 = janela completamente oculta, False = não aguarda
WshShell.Run sCmd, 0, False
Set WshShell = Nothing
