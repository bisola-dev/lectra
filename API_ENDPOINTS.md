# API Endpoints

## Authentication

### Login

- **URL**: `/api/auth/login`
- **Method**: `POST`
- **Description**: Authenticate a user and return a JWT token.
- **Request Body**:
  ```json
  {
    "email": "string",
    "password": "string"
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Login successful",
      "token": "string (JWT)",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string"
      }
    }
    ```
- **Error Responses**:
  - 400: Email and password are required
  - 401: Invalid email or password
  - 404: User not found

### Register

- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Description**: Register a new user. Requires admin authentication.
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **Request Body**:
  ```json
  {
    "name": "string",
    "email": "string",
    "password": "string",
    "role": "string (admin, lecturer, student)",
    "department_id": "integer (optional)"
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "User registered successfully",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string",
        "department_id": integer or null
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token required / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Name, email and password are required / Invalid role
  - 409: Email already exists

### Change Password

- **URL**: `/api/auth/change-password`
- **Method**: `POST`
- **Description**: Change the password for the authenticated user.
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>`
- **Request Body**:
  ```json
  {
    "old_password": "string",
    "new_password": "string"
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Password updated successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 400: Old and new password required
  - 404: User not found
  - 401: Old password is incorrect

### Update Profile

- **URL**: `/api/auth/update-profile`
- **Method**: `PUT`
- **Description**: Update profile information for the authenticated user.
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>`
- **Request Body** (all fields optional, but at least one must be provided):
  ```json
  {
    "name": "string (optional)",
    "email": "string (optional)",
    "role": "string (admin, lecturer, student) (optional)",
    "department_id": "integer (optional)"
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Profile updated successfully",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string",
        "department_id": integer or null
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 400: At least one field must be provided for update / Invalid role
  - 409: Email already exists
  - 405: Method not allowed (if not PUT)
  - 500: Failed to update profile

## Users Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Users / Create User

- **URL**: `/api/users`
- **Methods**:
  - `GET`: List all users
  - `POST`: Create a new user
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "name": "string",
          "email": "string",
          "role": "string",
          "department_id": integer or null,
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "name": "string",
    "email": "string",
    "password": "string",
    "role": "string (admin, lecturer, student)",
    "department_id": "integer (optional)"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "User created successfully",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string",
        "department_id": integer or null,
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing fields, invalid role, etc.)
  - 405: Method not allowed
  - 409: Email already exists
  - 500: Server error

### Get Specific User / Update User / Delete User

- **URL**: `/api/users/:id`
- **Methods**:
  - `GET`: Get a specific user by ID
  - `PUT`: Update a specific user by ID
  - `DELETE`: Delete a specific user by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = user ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string",
        "department_id": integer or null,
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body** (all fields optional, but at least one must be provided for update):
  ```json
  {
    "name": "string (optional)",
    "email": "string (optional)",
    "role": "string (admin, lecturer, student) (optional)",
    "department_id": "integer (optional)",
    "password": "string (optional)"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "User updated successfully",
      "user": {
        "id": integer,
        "name": "string",
        "email": "string",
        "role": "string",
        "department_id": integer or null,
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "User deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, invalid role, etc.) / Cannot delete own account
  - 404: User not found
  - 405: Method not allowed
  - 409: Email already exists (when updating)
  - 500: Server error

## Middleware

### Auth Middleware

- **URL**: `/api/middleware/auth.php` (internal use)
- **Method**: N/A (used by other endpoints via `require_once`)
- **Description**: Validates the JWT token from the Authorization header and returns the decoded token payload. Used to protect endpoints.
- **Note**: This is not a public endpoint but is included for completeness.

## Notes

- All endpoints return JSON with a `Content-Type: application/json` header.
- Error responses always include a `status` field set to `"error"` and a `message` field describing the error.
- Successful responses include a `status` field set to `"success"`.
- The JWT secret key is defined in `/usr/local/var/www/lectra/jwt.php` as `lectra_secret_2026`.

## Departments Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Departments / Create Department

- **URL**: `/api/departments`
- **Methods**:
  - `GET`: List all departments
  - `POST`: Create a new department
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "name": "string",
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "name": "string"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Department created successfully",
      "department": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing name, etc.)
  - 405: Method not allowed
  - 409: Department already exists
  - 500: Server error

### Get Specific Department / Update Department / Delete Department

- **URL**: `/api/departments/:id`
- **Methods**:
  - `GET`: Get a specific department by ID
  - `PUT`: Update a specific department by ID
  - `DELETE`: Delete a specific department by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = department ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "department": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body**:
  ```json
  {
    "name": "string"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Department updated successfully",
      "department": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Department deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, etc.)
  - 404: Department not found
  - 405: Method not allowed
  - 409: Department already exists (when updating)
  - 400: Cannot delete department - users or courses are assigned to it
  - 500: Server error

## Faculties Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Faculties / Create Faculty

- **URL**: `/api/faculties`
- **Methods**:
  - `GET`: List all faculties
  - `POST`: Create a new faculty
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "name": "string",
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "name": "string"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Faculty created successfully",
      "faculty": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing name, etc.)
  - 405: Method not allowed
  - 409: Faculty already exists
  - 500: Server error

### Get Specific Faculty / Update Faculty / Delete Faculty

- **URL**: `/api/faculties/:id`
- **Methods**:
  - `GET`: Get a specific faculty by ID
  - `PUT`: Update a specific faculty by ID
  - `DELETE`: Delete a specific faculty by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = faculty ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "faculty": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body**:
  ```json
  {
    "name": "string"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Faculty updated successfully",
      "faculty": {
        "id": integer,
        "name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Faculty deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, etc.)
  - 404: Faculty not found
  - 405: Method not allowed
  - 409: Faculty already exists (when updating)
  - 500: Server error

## Courses Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Courses / Create Course

- **URL**: `/api/courses`
- **Methods**:
  - `GET`: List all courses
  - `POST`: Create a new course
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "code": "string",
          "title": "string",
          "lecturer_id": integer or null,
          "department_id": integer,
          "lecturer_name": "string or null",
          "lecturer_email": "string or null",
          "department_name": "string",
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "code": "string",
    "title": "string",
    "lecturer_id": "integer (optional)",
    "department_id": "integer"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Course created successfully",
      "course": {
        "id": integer,
        "code": "string",
        "title": "string",
        "lecturer_id": integer or null,
        "department_id": integer,
        "lecturer_name": "string or null",
        "lecturer_email": "string or null",
        "department_name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing fields, invalid lecturer/department ID, etc.)
  - 405: Method not allowed
  - 409: Course code already exists
  - 500: Server error

### Get Specific Course / Update Course / Delete Course

- **URL**: `/api/courses/:id`
- **Methods**:
  - `GET`: Get a specific course by ID
  - `PUT`: Update a specific course by ID
  - `DELETE`: Delete a specific course by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = course ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "course": {
        "id": integer,
        "code": "string",
        "title": "string",
        "lecturer_id": integer or null,
        "department_id": integer,
        "lecturer_name": "string or null",
        "lecturer_email": "string or null",
        "department_name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body**:
  ```json
  {
    "code": "string (optional)",
    "title": "string (optional)",
    "lecturer_id": "integer (optional)",
    "department_id": "integer (optional)"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Course updated successfully",
      "course": {
        "id": integer,
        "code": "string",
        "title": "string",
        "lecturer_id": integer or null,
        "department_id": integer,
        "lecturer_name": "string or null",
        "lecturer_email": "string or null",
        "department_name": "string",
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Course deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, invalid lecturer/department ID, etc.)
  - 404: Course not found
  - 405: Method not allowed
  - 409: Course code already exists (when updating)
  - 400: Cannot delete course - timetable entries exist for this course
  - 500: Server error

## Timetable Management

- **Note**: All endpoints in this section require admin authentication.

### Get Timetable Entries (with filtering) / Create Timetable Entry

- **URL**: `/api/timetable`
- **Methods**:
  - `GET`: List timetable entries (with optional filtering)
  - `POST`: Create a new timetable entry
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Query Parameters**:
  - `department_id`: integer (optional) - Filter by department
  - `day`: string (optional) - Filter by day of week (Monday, Tuesday, etc.)
  - `week`: integer (optional) - Filter by week number
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "course_id": integer,
          "day": "string",
          "start_time": "string (HH:MM:SS)",
          "end_time": "string (HH:MM:SS)",
          "venue": "string",
          "week": integer,
          "created_at": "timestamp",
          "course_code": "string",
          "course_title": "string",
          "department_name": "string",
          "lecturer_name": "string or null"
        }
      ],
      "count": integer,
      "filters_applied": {
        "department_id": integer or null,
        "day": string or null,
        "week": integer or null
      }
    }
    ```
- **POST Request Body**:
  ```json
  {
    "course_id": "integer",
    "day": "string (Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday)",
    "start_time": "string (HH:MM:SS)",
    "end_time": "string (HH:MM:SS)",
    "venue": "string",
    "week": "integer (default: 1)"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Timetable entry created successfully",
      "timetable_entry": {
        "id": integer,
        "course_id": integer,
        "day": "string",
        "start_time": "string (HH:MM:SS)",
        "end_time": "string (HH:MM:SS)",
        "venue": "string",
        "week": integer,
        "created_at": "timestamp",
        "course_code": "string",
        "course_title": "string",
        "department_name": "string",
        "lecturer_name": "string or null"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing/invalid fields, invalid day format, etc.)
  - 405: Method not allowed
  - 409: Time conflict detected
  - 500: Server error

### Get Specific Timetable Entry / Update Timetable Entry / Delete Timetable Entry

- **URL**: `/api/timetable/:id`
- **Methods**:
  - `GET`: Get a specific timetable entry by ID
  - `PUT`: Update a specific timetable entry by ID
  - `DELETE`: Delete a specific timetable entry by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = timetable entry ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "timetable_entry": {
        "id": integer,
        "course_id": integer,
        "day": "string",
        "start_time": "string (HH:MM:SS)",
        "end_time": "string (HH:MM:SS)",
        "venue": "string",
        "week": integer,
        "created_at": "timestamp",
        "course_code": "string",
        "course_title": "string",
        "department_name": "string",
        "lecturer_name": "string or null"
      }
    }
    ```
- **PUT Request Body** (all fields optional, but must provide at least one for update):
  ```json
  {
    "course_id": "integer (optional)",
    "day": "string (optional)",
    "start_time": "string (optional)",
    "end_time": "string (optional)",
    "venue": "string (optional)",
    "week": "integer (optional)"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Timetable entry updated successfully",
      "timetable_entry": {
        "id": integer,
        "course_id": integer,
        "day": "string",
        "start_time": "string (HH:MM:SS)",
        "end_time": "string (HH:MM:SS)",
        "venue": "string",
        "week": integer,
        "created_at": "timestamp",
        "course_code": "string",
        "course_title": "string",
        "department_name": "string",
        "lecturer_name": "string or null"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Timetable entry deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, invalid fields, etc.)
  - 404: Timetable entry not found
  - 405: Method not allowed
  - 409: Time conflict detected (when updating)
  - 500: Server error

## Announcements Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Announcements / Create Announcement

- **URL**: `/api/announcements`
- **Methods**:
  - `GET`: List all announcements
  - `POST`: Create a new announcement
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "title": "string",
          "body": "string",
          "is_emergency": boolean,
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "title": "string",
    "body": "string",
    "is_emergency": "boolean (optional, default: false)"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Announcement created successfully",
      "announcement": {
        "id": integer,
        "title": "string",
        "body": "string",
        "is_emergency": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing title or body, etc.)
  - 405: Method not allowed
  - 500: Server error

### Get Specific Announcement / Update Announcement / Delete Announcement

- **URL**: `/api/announcements/:id`
- **Methods**:
  - `GET`: Get a specific announcement by ID
  - `PUT`: Update a specific announcement by ID
  - `DELETE`: Delete a specific announcement by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = announcement ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "announcement": {
        "id": integer,
        "title": "string",
        "body": "string",
        "is_emergency": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body**:
  ```json
  {
    "title": "string (optional)",
    "body": "string (optional)",
    "is_emergency": "boolean (optional)"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Announcement updated successfully",
      "announcement": {
        "id": integer,
        "title": "string",
        "body": "string",
        "is_emergency": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Announcement deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, etc.)
  - 404: Announcement not found
  - 405: Method not allowed
  - 500: Server error

## Notifications Management

- **Note**: All endpoints in this section require admin authentication.

### Get All Notifications / Create Notification

- **URL**: `/api/notifications`
- **Methods**:
  - `GET`: List all notifications
  - `POST`: Create a new notification
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "data": [
        {
          "id": integer,
          "title": "string",
          "message": "string",
          "type": "string (info, warning, alert)",
          "target_role": "string (admin, lecturer, student, all)",
          "is_active": boolean,
          "created_at": "timestamp"
        }
      ],
      "count": integer
    }
    ```
- **POST Request Body**:
  ```json
  {
    "title": "string",
    "message": "string",
    "type": "string (info, warning, alert, default: info)",
    "target_role": "string (admin, lecturer, student, all, default: all)"
  }
  ```
- **POST Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Notification created successfully",
      "notification": {
        "id": integer,
        "title": "string",
        "message": "string",
        "type": "string (info, warning, alert)",
        "target_role": "string (admin, lecturer, student, all)",
        "is_active": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing/invalid fields, etc.)
  - 405: Method not allowed
  - 500: Server error

### Get Specific Notification / Update Notification / Delete Notification

- **URL**: `/api/notifications/:id`
- **Methods**:
  - `GET`: Get a specific notification by ID
  - `PUT`: Update a specific notification by ID
  - `DELETE`: Delete a specific notification by ID
- **Headers**:
  - `Authorization: Bearer <JWT_TOKEN>` (token must be for an admin user)
- **URL Parameter**: `:id` = notification ID (integer)
- **GET Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "notification": {
        "id": integer,
        "title": "string",
        "message": "string",
        "type": "string (info, warning, alert)",
        "target_role": "string (admin, lecturer, student, all)",
        "is_active": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **PUT Request Body** (all fields optional, but must provide at least one for update):
  ```json
  {
    "title": "string (optional)",
    "message": "string (optional)",
    "type": "string (optional)",
    "target_role": "string (optional)",
    "is_active": "boolean (optional)"
  }
  ```
- **PUT Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Notification updated successfully",
      "notification": {
        "id": integer,
        "title": "string",
        "message": "string",
        "type": "string (info, warning, alert)",
        "target_role": "string (admin, lecturer, student, all)",
        "is_active": boolean,
        "created_at": "timestamp"
      }
    }
    ```
- **DELETE Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Notification deleted successfully"
    }
    ```
- **Error Responses**:
  - 401: Unauthorized - Token missing / Invalid token
  - 403: Forbidden - Admin access required
  - 400: Validation errors (missing ID, invalid fields, etc.)
  - 404: Notification not found
  - 405: Method not allowed
  - 500: Server error

## Middleware
