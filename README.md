# Facebook-like Web Application

A professional social media web application built with React.js, PHP, and MySQL.

## Project Structure

- `/frontend` - React.js frontend application
- `/backend` - PHP API and server-side logic
- `/database` - SQL database schema and setup scripts

## Features

- User authentication (register, login, logout)
- User profiles with profile pictures
- News feed with posts and comments
- Friend requests and connections
- Real-time notifications
- Messaging system
- Photo and media sharing

## Technology Stack

- **Frontend**: React.js, Redux, Material-UI
- **Backend**: PHP, MySQL
- **Authentication**: JWT (JSON Web Tokens)
- **Storage**: File system for media, MySQL for data

## Setup Instructions

### Database Setup
1. Create a MySQL database
2. Import the schema from `/database/schema.sql`
3. Configure database connection in `/backend/config/database.php`

### Backend Setup
1. Navigate to the backend directory: `cd backend`
2. Configure your web server (Apache/Nginx) to point to the backend directory
3. Ensure PHP is properly configured with MySQL extensions

### Frontend Setup
1. Navigate to the frontend directory: `cd frontend`
2. Install dependencies: `npm install`
3. Configure API endpoint in `.env` file
4. Start development server: `npm start`

## Development

This project follows a decoupled architecture where the frontend and backend communicate via RESTful API endpoints."# facebook-clone" 
