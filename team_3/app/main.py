from fastapi import FastAPI
import psycopg2
from psycopg2.extras import RealDictCursor
import os

app = FastAPI()

def get_db_connection():
    return psycopg2.connect(
        host="team_3_db",
        database="team_3_db",
        user="team_3",
        password="team3pass",
        cursor_factory=RealDictCursor
    )

@app.get("/")
async def root():
    return {"message": "Hello from Team 3 - FastAPI + PostgreSQL!"}

@app.get("/team_3/")
async def team_home():
    try:
        conn = get_db_connection()
        cur = conn.cursor()
        cur.execute("SELECT version();")
        db_version = cur.fetchone()
        cur.close()
        conn.close()
        return {
            "team": "Team 3", 
            "tech": "FastAPI + PostgreSQL", 
            "database_version": db_version['version']
        }
    except Exception as e:
        return {"error": str(e)}
