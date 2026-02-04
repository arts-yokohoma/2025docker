import os
from dotenv import load_dotenv

load_dotenv()

CONFIRM_ORDER_PRICE = 20000
ORDER_STATUS = {
    1: "未確認",
    2: "確認済み",
    3: "完成",
    4: "キャンセル",
}

SECRET_KEY = os.getenv("JWT_SECRET")
ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")

ACCESS_EXPIRES_MINUTES = int(os.getenv("ACCESS_EXPIRES_MINUTES", 300))
REFRESH_EXPIRES_DAYS = int(os.getenv("REFRESH_EXPIRES_DAYS", 7))
CORS_ENV = os.getenv("CORS", "")
PASSWORD_DEFAULT = os.getenv("PASSWORD_DEFAULT", "")
RESEND_API_KEY = os.getenv("RESEND_API_KEY", "")
DISCORD_BOT_TOKEN = os.getenv("DISCORD_BOT_TOKEN", "")


TIMEFORMAT_YYYY_MM_DD_STR = "%Y-%m-%d"
TIMEFORMAT_YYYYMMDD_STR = "%Y%m%d"
TIMEFORMAT_YYYYMMDDHHMMSS_STR = "%Y-%m-%dT%H:%M:%S"
TIMEFORMAT_YYYYMMDDHHMMSSF_STR = "%Y-%m-%dT%H:%M:%S.%f"