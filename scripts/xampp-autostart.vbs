' Lanza el arranque de XAMPP sin mostrar ventana de consola.
' Se ejecuta desde el acceso directo de la carpeta de Inicio.

Set fso = CreateObject("Scripting.FileSystemObject")
Set sh = CreateObject("WScript.Shell")

dir = fso.GetParentFolderName(WScript.ScriptFullName)
script = dir & "\xampp-start.ps1"

If Not fso.FileExists(script) Then
    WScript.Quit 1
End If

comando = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & script & """"
sh.Run comando, 0, False
