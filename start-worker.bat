@echo off
echo Starting Laravel Queue Worker...
cd /d C:\New_Orders_System
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
pause
