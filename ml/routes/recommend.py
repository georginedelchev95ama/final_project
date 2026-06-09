import pandas as pd
from flask import Blueprint, jsonify
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler

from utils.db import get_db

recommend_bp = Blueprint('recommend', __name__)


@recommend_bp.route('/api/recommend/<username>')
def recommend(username):
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)

        cursor.execute(
            'SELECT id, games_played FROM users WHERE username = %s LIMIT 1',
            (username,)
        )
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

            pct = round(lowest_prob * 100, 1)
            pct_str = 'less than 1' if pct < 1 else str(pct).rstrip('0').rstrip('.')
            return jsonify({
                'recommended_level': int(hardest_lvl),
                'reason': (
                    f'Our model predicts you win Level {hardest_lvl} only '
                    f'{pct_str}% of the time — practice it.'
                ),
                'confidence': confidence,
            })

        worst = qualified.sort_values('win_rate').iloc[0]
        pct = round(worst['win_rate'] * 100, 1)
        pct_str = 'less than 1' if pct < 1 else str(pct).rstrip('0').rstrip('.')
        return jsonify({
            'recommended_level': int(worst['level_id']),
            'reason': (
                f'You win Level {int(worst["level_id"])} only '
                f'{pct_str}% of the time — keep practising.'
            ),
            'confidence': 'medium',
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500
