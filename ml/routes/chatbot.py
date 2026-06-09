import os
import json
import requests
from flask import Blueprint, jsonify, request

chatbot_bp = Blueprint('chatbot', __name__)

SYSTEM_PROMPT = """You are a support assistant for Maze Escape, a browser-based maze puzzle game.
You help players with gameplay questions only. If asked anything unrelated to Maze Escape, respond with exactly:
'I can only help with questions about Maze Escape. Ask me anything about how to play!'

GAME FACTS — only use these, do not make anything up:

CONTROLS:
- Move using Arrow Keys or WASD (keyboard only, no mouse movement)
- W or Arrow Up = move up, S or Arrow Down = move down, A or Arrow Left = move left, D or Arrow Right = move right

OBJECTIVE:
- Navigate through the maze to reach the exit
- Collect keys scattered around the maze before you can exit
- Avoid enemies — if caught you lose the run
- Finish as fast as possible with as few moves as possible for a higher score

SCORING:
- Points are awarded based on time taken, moves made, and keys collected
- Winning earns more points than losing
- Your total points accumulate across all runs and affect your leaderboard rank

LEVELS:
- There are 8 levels of increasing difficulty
- Each level has a different maze layout, more keys, and more enemies as difficulty increases

ACHIEVEMENTS (these are the only real achievements, do not invent others):
- First Escape: Complete your first successful run
- Getting Started: Play 10 games in total
- Consistent Runner: Win 5 games
- Deep Runner: Beat Level 5 or higher
- Beginner Explorer: Reach 1000 total points
- Maze Hunter: Reach 2500 total points
- Escape Master: Reach 7000 total points
- Shadow Sprinter: Reach 15000 total points
- Maze Legend: Reach 20000 total points
- Key Collector: Collect 25 keys across all runs
- Rapid Exit: Finish any level in under 40 seconds
- No More Training Wheels: Beat Level 8

FEATURES:
- Leaderboard showing top players ranked by points
- Player profiles showing stats, recent runs and achievements
- Compare your stats side by side with another player
- Messaging system to chat with other players
- Practice mode to replay levels
- AI tip on your profile suggesting which level to practise based on your history

Keep answers concise and friendly."""


@chatbot_bp.route('/api/chatbot', methods=['POST'])
def chatbot():
    api_key = os.environ.get('GEMINI_API_KEY', '')
    if not api_key:
        return jsonify({'error': 'Chatbot is not configured yet.'}), 503

    data = request.get_json(silent=True) or {}
    user_message = (data.get('message') or '').strip()
    history = data.get('history') or []  # list of {role, text} from the browser

    if not user_message:
        return jsonify({'error': 'No message provided.'}), 400

    # Build Gemini contents array (include conversation history for context)
    contents = []
    for turn in history[-10:]:  # cap at last 10 turns to keep payload small
        role = 'user' if turn.get('role') == 'user' else 'model'
        contents.append({'role': role, 'parts': [{'text': turn.get('text', '')}]})
    contents.append({'role': 'user', 'parts': [{'text': user_message}]})

    payload = {
        'system_instruction': {'parts': [{'text': SYSTEM_PROMPT}]},
        'contents': contents,
        'generationConfig': {
            'temperature': 0.4,
            'maxOutputTokens': 300,
        },
    }

    url = (
        'https://generativelanguage.googleapis.com/v1beta/'
        f'models/gemini-2.5-flash:generateContent?key={api_key}'
    )

    import time
    for attempt in range(2):
        try:
            resp = requests.post(url, json=payload, timeout=15)
            if resp.status_code == 429 and attempt == 0:
                time.sleep(5)
                continue
            resp.raise_for_status()
            result = resp.json()
            reply = result['candidates'][0]['content']['parts'][0]['text']
            return jsonify({'reply': reply.strip()})
        except requests.exceptions.Timeout:
            return jsonify({'error': 'The assistant took too long to respond. Try again.'}), 504
        except requests.exceptions.HTTPError as e:
            status = e.response.status_code if e.response is not None else 500
            body = e.response.text if e.response is not None else ''
            if status == 429:
                return jsonify({'error': 'Too many requests — please wait a moment and try again.', 'detail': body}), 429
            return jsonify({'error': 'Gemini API error. Please try again later.', 'status': status, 'detail': body}), 502
        except Exception as e:
            return jsonify({'error': 'Something went wrong. Please try again.', 'detail': str(e)}), 500
