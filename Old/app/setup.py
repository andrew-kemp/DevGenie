from flask import Blueprint, render_template, request, redirect, url_for, flash
from . import db, bcrypt
from .models import Admin
from sqlalchemy.exc import IntegrityError

bp = Blueprint('setup', __name__)

@bp.route('/setup', methods=['GET', 'POST'])
def setup():
    if Admin.query.first():
        return redirect(url_for('admin.login'))

    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        password = request.form['password']
        pw_hash = bcrypt.generate_password_hash(password).decode('utf-8')
        admin = Admin(username=username, email=email, password_hash=pw_hash)
        db.session.add(admin)
        try:
            db.session.commit()
            flash('Admin account created, please log in.', 'success')
            return redirect(url_for('admin.login'))
        except IntegrityError:
            db.session.rollback()
            flash('Username or email already exists.', 'danger')
    return render_template('setup.html')