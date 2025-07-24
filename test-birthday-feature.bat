@echo off
echo =======================================================
echo          BIRTHDAY FEATURE TEST SUITE
echo =======================================================
echo.

echo Running Unit Tests for BirthdayService...
echo -------------------------------------------------------
php artisan test tests/Unit/BirthdayServiceTest.php
echo.

echo Running Feature Tests for ProcessBirthdays Command...
echo -------------------------------------------------------
php artisan test tests/Feature/ProcessBirthdaysCommandTest.php
echo.

echo Running Feature Tests for Birthday Popup functionality...
echo -------------------------------------------------------
php artisan test tests/Feature/BirthdayPopupTest.php
echo.

echo Running Integration Tests for Birthday Feature...
echo -------------------------------------------------------
php artisan test tests/Feature/BirthdayIntegrationTest.php
echo.

echo =======================================================
echo          TEST SUITE COMPLETED
echo =======================================================
echo.

echo Testing Birthday Command Manually...
echo -------------------------------------------------------
php artisan birthdays:process
echo.

echo Checking Laravel Schedule Configuration...
echo -------------------------------------------------------
php artisan schedule:list | grep birthday
echo.

echo =======================================================
echo All tests completed! Check output above for results.
echo =======================================================
pause 