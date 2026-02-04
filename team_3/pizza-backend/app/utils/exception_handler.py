from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from app.utils.response import response
import logging
from slowapi.errors import RateLimitExceeded

logger = logging.getLogger()


async def validation_exception_handler(request: Request, exc: RequestValidationError):
    logger.error(f"Bad request {exc.errors()}")
    return response(
        code=400,
        message="送信されたデータが無効です"
    )


async def rate_limit_handler(request: Request, exc: RateLimitExceeded):
    return response(
        code=429,
        message="リクエストが多すぎる"
    )