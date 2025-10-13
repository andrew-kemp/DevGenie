# DevGenie App Initialization
from flask import Flask

def create_app():
    app = Flask(__name__)
    app.config.from_object('config.Config')
    # Import and register blueprints here if needed
    return app