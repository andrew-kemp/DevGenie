# DevGenie

**DevGenie** is a fun, modern portal for automated Dev account provisioning using Microsoft Entra ID (Azure AD), Python (Flask), MySQL, and Bootstrap.

## Features

- User-friendly web form for Dev account requests
- Admin dashboard for approvals
- Secure login with Entra ID (Azure AD)
- Automated provisioning via Microsoft Graph
- Modern, responsive design

## Setup (Development)

1. Clone this repo and enter the directory  
2. Create and activate a Python virtualenv  
3. Install dependencies: `pip install -r requirements.txt`  
4. Set up your `.env` file (see below)  
5. Set up MySQL/MariaDB database  
6. Run `python run.py` to start the app

## Configuration

Set the following environment variables in a `.env` file:

```
SECRET_KEY=your-secret-key
DATABASE_URL=mysql://user:password@localhost/devgenie
# Add your Entra ID / MS Graph secrets here
```

## Structure

See the `/app` directory for Flask app files, `/scripts` for setup helpers, and `/app/templates` for HTML.

---

**DevGenie**—Granting wishes for Dev accounts, one request at a time! 🧞