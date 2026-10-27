@echo off
rem Lance Jimbe en local, rapide : OPcache active (PHP ne recompile plus tout Laravel a chaque page)
rem et plusieurs processus pour que le navigateur n'attende pas. Double-clic pour demarrer, Ctrl+C pour arreter.
rem Adresse : http://127.0.0.1:8000

cd /d "%~dp0public"
set PHP_CLI_SERVER_WORKERS=8
"C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" ^
  -d zend_extension=opcache ^
  -d opcache.enable_cli=1 ^
  -d opcache.memory_consumption=256 ^
  -d opcache.max_accelerated_files=20000 ^
  -d opcache.validate_timestamps=1 ^
  -d opcache.revalidate_freq=2 ^
  -d realpath_cache_size=4096K ^
  -S 127.0.0.1:8000 ^
  "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
