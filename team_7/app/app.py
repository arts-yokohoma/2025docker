from flask import Flask, jsonify
import psycopg2
from psycopg2.extras import RealDictCursor
import os

app = Flask(__name__)

def get_db_connection():
    return psycopg2.connect(
        host="team_7_db",
        database="team_7_db",
        user="team_7",
        password="team7pass",
        cursor_factory=RealDictCursor
    )

@app.route('/')
def hello():
    return 'Hello from Team 7 - Flask + PostgreSQL!'

@app.route('/team_7/')
def team_home():
    try:
        conn = get_db_connection()
        cur = conn.cursor()
        cur.execute("SELECT version();")
        db_version = cur.fetchone()
        cur.close()
        conn.close()
        return jsonify({
            'team': 'Team 7', 
            'tech': 'Flask + PostgreSQL', 
            'database_version': db_version['version']
        })
    except Exception as e:
        return jsonify({'error': str(e)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
