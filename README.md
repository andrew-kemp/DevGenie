# DevGenie

**DevGenie** is a fun, modern portal for automated Dev account provisioning.

## Features

- User-friendly web form for Dev account requests
- Admin setup on first run
- Secure login for admins (future)
- Modern, responsive design

## Setup

1. Run the installer:

   ```bash
   bash installer.sh
   ```

2. Activate your Python virtual environment and install requirements:

   ```
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

3. Run the app:

   ```
   flask run
   ```

## Structure

- `/app`: Flask app files
- `/scripts`: Helper SQL/schema scripts
- `installer.sh`: Initial setup script

---

**DevGenie** — Granting wishes for Dev accounts, one request at a time! 🧞