from . import db

class Admin(db.Model):
    __tablename__ = 'devgenie_admins'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(100), unique=True, nullable=False)
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    created_at = db.Column(db.DateTime, server_default=db.func.now())
    is_active = db.Column(db.Boolean, default=True)

class Request(db.Model):
    __tablename__ = 'devgenie_requests'
    id = db.Column(db.Integer, primary_key=True)
    requester_email = db.Column(db.String(255), nullable=False)
    target_email = db.Column(db.String(255), nullable=False)
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    display_name = db.Column(db.String(200), nullable=False)
    justification = db.Column(db.Text, nullable=False)
    status = db.Column(db.Enum('pending', 'approved', 'denied'), default='pending')
    created_at = db.Column(db.DateTime, server_default=db.func.now())
    updated_at = db.Column(db.DateTime, nullable=True)