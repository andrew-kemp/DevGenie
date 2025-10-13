from flask import Blueprint, render_template, request, redirect, url_for, flash
from . import db
from .models import Request

bp = Blueprint('main', __name__)

@bp.route('/', methods=['GET'])
def home():
    return render_template('index.html')

@bp.route('/request', methods=['GET', 'POST'])
def user_request():
    if request.method == 'POST':
        data = request.form
        req = Request(
            requester_email=data['requester_email'],
            target_email=data['target_email'],
            first_name=data['first_name'],
            last_name=data['last_name'],
            display_name=data['display_name'],
            justification=data['justification']
        )
        db.session.add(req)
        db.session.commit()
        flash('Request submitted!', 'success')
        return redirect(url_for('main.home'))
    return render_template('request_form.html')