# Mobile API Docs

## Base URL
All API calls start with:
http://localhost/lectra

## How Responses Work
Every API call returns JSON in one of two formats.

When things work:
{
  "success": true,
  "data": [ ... your data here ... ]
}

When something goes wrong:
{
  "success": false,
  "message": "What went wrong"
}

## Available Endpoints

### 1. Get Timetable
GET /api/mobile/timetable?department_id=<id>&week=<week>
Use this to get a student's class schedule for their department and a specific week.

What you need to send:
- department_id (needed): The department's ID number
- week (needed): Which week you want (like 1 for first week)

What you get back:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "day": "Monday",
      "start_time": "09:00:00",
      "end_time": "11:00:00",
      "venue": "Room 101",
      "week": 1,
      "created_at": "2026-05-28 10:00:00",
      "course_code": "CS101",
      "course_title": "Introduction to Programming",
      "department_name": "Computer Science",
      "lecturer_name": "Dr. Jane Smith"
    }
  ]
}

### 2. Get Notifications
GET /api/mobile/notifications?role=<role>&last_checked=<timestamp>
Checks for new notifications since the app last looked.

What you need to send:
- role (needed): Who is asking (admin, lecturer, or student)
- last_checked (needed): The last time the app checked (as a MySQL datetime)

What you get back:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "System Update",
      "message": "The system will undergo maintenance this weekend",
      "type": "info",
      "target_role": "all",
      "is_active": 1,
      "created_at": "2026-05-28 10:00:00"
    }
  ]
}

### 3. Get Announcements
GET /api/mobile/announcements
Gets any active emergency alerts or important notices.

No extra info needed.

What you get back:
{
  "success": true,
  "data": [
    {
      "id": 3,
      "title": "Campus Closure",
      "body": "Due to severe weather, campus will be closed tomorrow",
      "is_emergency": 1,
      "created_at": "2026-05-28 10:00:00"
    }
  ]
}

### 4. update  Device Token
PUT /api/mobile/device-token
After logging in, the app confirms your role then go ahead to update your device token.

What you send:
{
  "device_token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
}

What you get back:
{
  "success": true,
  "data": []
}

### 5. Record Attendance
POST /api/mobile/attendance
When a student taps "I'm here" in class, this records their attendance.

What you send:
{
  "timetable_id": 1
}

What you get back if it worked:
{
  "success": true,
  "data": []
}

If they already checked in:
{
  "success": false,
  "message": "Attendance already recorded for this timetable"
}

## Logging In
Every API call needs this header:
Authorization: Bearer <your_jwt_token_here>
Get the token when you log in through the regular login system.

## About CORS
All API endpoints allow requests from any website (for development). They include:
- Access-Control-Allow-Origin: *
- Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
- Access-Control-Allow-Headers: Content-Type, Authorization

The system automatically handles OPTIONS preflight requests.
