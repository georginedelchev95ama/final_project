import os
import json
import requests
from flask import Blueprint, jsonify, request

chatbot_bp = Blueprint('chatbot', __name__)

SYSTEM_PROMPT = """You are a support assistant for Maze Escape, a browser-based maze puzzle game.
You help players with gameplay questions only: how to play, controls, levels, scoring, keys,
time bonuses, achievements, leaderboard, and tips for improving.
If the user asks about anything not related to Maze Escape, respond with exactly:
'I can only help with questions about Maze Escape. Ask me anything about how to play!'
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
        f'models/gemini-1.5-flash:generateContent?key={api_key}'
    )

    try:
        resp = requests.post(url, json=payload, timeout=15)
        resp.raise_for_status()
        result = resp.json()
        reply = result['candidates'][0]['content']['parts'][0]['text']
        return jsonify({'reply': reply.strip()})
    except requests.exceptions.Timeout:
        return jsonify({'error': 'The assistant took too long to respond. Try again.'}), 504
    except requests.exceptions.HTTPError as e:
        status = e.response.status_code if e.response is not None else 500
        if status == 429:
            return jsonify({'error': 'Too many requests — please wait a moment and try again.'}), 429
        return jsonify({'error': 'Gemini API error. Please try again later.'}), 502
    except Exception:
        return jsonify({'error': 'Something went wrong. Please try again.'}), 500
