' SST Manager — Lançador Silencioso
' Executa o start-sst.bat sem abrir nenhuma janela
' Detecta automaticamente o diretório onde este arquivo está localizado

Dim WshShell, sDir, sBat

sDir = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\") - 1)
sBat = sDir & "\start-sst.bat"

Set WshShell = CreateObject("WScript.Shell")
' 0 = janela oculta, False = não aguarda
WshShell.Run "cmd /c """ & sBat & """", 0, False
Set WshShell = Nothing
