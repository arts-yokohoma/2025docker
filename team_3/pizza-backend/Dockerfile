FROM python:3.12-slim
WORKDIR /usr/src/app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

COPY .env.docker /app/.env

RUN chmod +x create_admin.sh

CMD ["sh", "-c", "./create_admin.sh && exec uvicorn app.main.app --host 0.0.0.0 --port 8000"]
