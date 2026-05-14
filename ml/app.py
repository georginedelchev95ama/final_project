import os
import pandas as pd
import numpy as np
from flask import Flask, jsonify
from flask_cors import CORS
import mysql.connector
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler

app = Flask(__name__)
CORS(app)


def get_db():
    return mysql.connector.connect(
        host=os.environ.get('DB_HOST', 'localhost'),
        database=os.environ.get('DB_NAME', ''),
        user=os.environ.get('DB_USER', ''),
        password=os.environ.get('DB_PASS', ''),
        connection_timeout=5,
    )


@app.route('/api/health')
def health():
    return jsonify({'status': 'ok'})


@app.route('/api/recommend/<username>')
def recommend(username):
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)

        cursor.execute('SELECT id, games_played FROM users WHERE username = %s LIMIT 1', (username,))
        user = cursor.fetchone()
        if not user:
            cursor.close(); db.close()
            return jsonify({'error': 'User not found'}), 404

        cursor.execute('''
            SELECT level_id, time_ms, moves, won, keys_collected
            FROM scores
            WHERE user_id = %s
        ''', (user['id'],))
        scores = cursor.fetchall()
        cursor.close(); db.close()

        if len(scores) < 3:
            return jsonify({
                'recommended_level': 1,
                'reason': 'Play more games to get personalised recommendations.',
                'confidence': 'low',
            })

        df = pd.DataFrame(scores)

        level_stats = df.groupby('level_id').agg(
            attempts=('won', 'count'),
            wins=('won', 'sum'),
            avg_time=('time_ms', 'mean'),
            avg_moves=('moves', 'mean'),
        ).reset_index()
        level_stats['win_rate'] = level_stats['wins'] / level_stats['attempts']

        qualified = level_stats[level_stats['attempts'] >= 2].copy()

        if qualified.empty:
            top = level_stats.sort_values('level_id').iloc[-1]
            return jsonify({
                'recommended_level': int(top['level_id']),
                'reason': f'Keep pushing — try Level {int(top["level_id"])} again.',
                'confidence': 'low',
            })

        if len(df) >= 10:
            X = df[['level_id', 'time_ms', 'moves', 'keys_collected']].values
            y = df['won'].values

            scaler = StandardScaler()
            X_scaled = scaler.fit_transform(X)

            model = LogisticRegression(max_iter=500)
            model.fit(X_scaled, y)

            all_levels = qualified['level_id'].tolist()
            predictions = []
            for lvl in all_levels:
                row = qualified[qualified['level_id'] == lvl].iloc[0]
                feat = scaler.transform([[lvl, row['avg_time'], row['avg_moves'], 0]])
                prob_win = model.predict_proba(feat)[0][1]
                predictions.append((lvl, prob_win))

            hardest_lvl, lowest_prob = min(predictions, key=lambda x: x[1])
            confidence = 'high' if len(df) >= 20 else 'medium'

            return jsonify({
                'recommended_level': int(hardest_lvl),
                'reason': (
                    f'Our model predicts you win Level {hardest_lvl} only '
                    f'{round(lowest_prob * 100)}% of the time — practice it.'
                ),
                'confidence': confidence,
            })

        worst = qualified.sort_values('win_rate').iloc[0]
        return jsonify({
            'recommended_level': int(worst['level_id']),
            'reason': (
                f'You win Level {int(worst["level_id"])} only '
                f'{round(worst["win_rate"] * 100)}% of the time — keep practising.'
            ),
            'confidence': 'medium',
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/playstyle/<username>')
def playstyle(username):
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)

        cursor.execute('SELECT id FROM users WHERE username = %s LIMIT 1', (username,))
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

        time_fast  = avg_time < 40
        moves_low  = avg_moves < 60

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


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port)
