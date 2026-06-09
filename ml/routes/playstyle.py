import pandas as pd
from flask import Blueprint, jsonify

from utils.db import get_db

playstyle_bp = Blueprint('playstyle', __name__)


@playstyle_bp.route('/api/playstyle/<username>')
def playstyle(username):
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)

        cursor.execute(
            'SELECT id FROM users WHERE username = %s LIMIT 1',
            (username,)
        )
        user = cursor.fetchone()
        if not user:
            cursor.close(); db.close()
            return jsonify({'error': 'User not found'}), 404

        cursor.execute('''
            SELECT time_ms, moves, won, level_id
            FROM scores
            WHERE user_id = %s AND won = 1
        ''', (user['id'],))
        wins = cursor.fetchall()
        cursor.close(); db.close()

        if len(wins) < 5:
            return jsonify({
                'style': 'Newcomer',
                'description': 'Not enough wins yet to analyse your play style.',
            })

        df = pd.DataFrame(wins)
        avg_time  = df['time_ms'].mean() / 1000
        avg_moves = df['moves'].mean()

        time_fast = avg_time < 40
        moves_low = avg_moves < 60

        if time_fast and moves_low:
            style = 'Speed Runner'
            desc  = 'Fast and efficient — you take the shortest path and waste no time.'
        elif time_fast and not moves_low:
            style = 'Rusher'
            desc  = 'You move quickly but take a lot of steps — speed over precision.'
        elif not time_fast and moves_low:
            style = 'Strategist'
            desc  = 'Careful and methodical — you think before you move.'
        else:
            style = 'Explorer'
            desc  = 'You take your time and explore the maze thoroughly.'

        return jsonify({
            'style': style,
            'description': desc,
            'avg_win_time_s': round(avg_time, 2),
            'avg_moves': round(avg_moves, 1),
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500
