import os
from flask import Flask, jsonify
from flask_cors import CORS

from routes.recommend import recommend_bp
from routes.playstyle import playstyle_bp
from routes.chatbot import chatbot_bp

app = Flask(__name__)
CORS(app)

app.register_blueprint(recommend_bp)
app.register_blueprint(playstyle_bp)
app.register_blueprint(chatbot_bp)


@app.route('/api/health')
def health():
    return jsonify({'status': 'ok'})


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port)
