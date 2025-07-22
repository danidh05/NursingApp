@echo off
echo 🚀 Running Slider ^& Popup Tests...
echo ==================================

REM Run the specific tests
php artisan test tests/Unit/SliderServiceTest.php tests/Unit/PopupServiceTest.php tests/Feature/SliderControllerTest.php tests/Feature/PopupControllerTest.php

echo.
echo ✅ Test Summary:
echo - Unit Tests: Business logic validation
echo - Feature Tests: End-to-end API testing
echo - Database: SQLite in-memory (isolated)
echo - Firebase: Mocked (no external dependencies)
echo.
echo 🎯 If all tests pass, your features are production-ready!
pause 