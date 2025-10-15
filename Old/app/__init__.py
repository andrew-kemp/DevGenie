from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_wtf import CSRFProtect
from flask_bcrypt import Bcrypt
import os

db = SQLAlchemy()
csrf = CSRFProtect()
bcrypt = Bcrypt()

def create_app():
    app = Flask(__name__)
    app.config.from_pyfile('../config.py')
    db.init_app(app)
    csrf.init_app(app)
    bcrypt.init_app(app)

    from . import setup, main, admin
    app.register_blueprint(setup.bp)
    app.register_blueprint(main.bp)
    app.register_blueprint(admin.bp)

    return app