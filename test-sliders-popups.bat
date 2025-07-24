@echo off
echo Testing Sliders and Popups Endpoints
echo =====================================

echo.
echo 1. Testing GET /api/sliders (should require authentication)
curl -X GET "http://localhost:8000/api/sliders" -H "Accept: application/json"

echo.
echo.
echo 2. Testing GET /api/popups (should require authentication)
curl -X GET "http://localhost:8000/api/popups" -H "Accept: application/json"

echo.
echo.
echo 3. Testing POST /api/admin/sliders (should require authentication and admin role)
curl -X POST "http://localhost:8000/api/admin/sliders" -H "Accept: application/json" -H "Content-Type: application/json"

echo.
echo.
echo 4. Testing POST /api/admin/popups (should require authentication and admin role)
curl -X POST "http://localhost:8000/api/admin/popups" -H "Accept: application/json" -H "Content-Type: application/json"

echo.
echo.
echo Tests completed! 