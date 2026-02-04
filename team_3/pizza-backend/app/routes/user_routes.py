from fastapi import APIRouter, Depends, HTTPException, Request
from sqlalchemy.orm import Session
from slowapi import Limiter
from app.infras.external.db.postgres_connection import postgre_connection
from app.repository.user_client_repository import UserRepository
import random
from app.entity.user_entity import (
    ChangeForgotPasswordSchema,
    ChangePasswordSchema,
    UserCreate,
    UserOut,
    SendCodeMail,
    VerifyCode,
)
from app.common.mail_template import FORGOT_PASS_TEMPLATE
from app.common.timer import Timer, timedelta
from app.entity.login_entity import LoginSchema, TokenResponse
from app.utils.response import response
from app.utils.middleware import get_auth_dependency
from app.utils.jwt_handler import (
    create_access_token,
    create_refresh_token,
    verify_token,
)
from app.infras.external.slow_api import SlowApiLimiter
from app.common.constant import PASSWORD_DEFAULT
import logging
from passlib.hash import argon2
from app.infras.external.mail_service.send_mail import SendMail

# import redis

router = APIRouter(tags=["User"])
logger = logging.getLogger()
limiter = SlowApiLimiter().get_limiter()

# r = redis.Redis(host="localhost", port=6379, db=0, decode_responses=True)


def get_user_repo(
    db: Session = Depends(postgre_connection),
) -> UserRepository:
    return UserRepository(logger=logger, db=db)


@router.post("/create", response_model=UserOut)
async def create_user(
    user: UserCreate,
    repo: UserRepository = Depends(get_user_repo),
    # token_payload=Depends(get_auth_dependency()),
):
    try:
        is_exsit = repo._get_by_username(user.user_name, email=user.email)
        if is_exsit:
            if is_exsit.is_deleted:
                hashed_pw = argon2.hash(PASSWORD_DEFAULT)
                repo._create_update_user(is_exsit, user, hashed_pw)
                return response(code=201, data={"message": "success"})
            else:
                return response(code=400, message="ユーザー存在している")
        hashed_pw = argon2.hash(user.password)
        # repo._create_user(user)
        repo._create_user(user, hashed_pw)
        return response(code=201, data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server", code=500)


# @router.get("/", response_model=UserOut)
# async def list_users(
#     repo: UserRepository = Depends(get_user_repo),
#     token_payload=Depends(get_auth_dependency([1])),
# ):
#     try:
#         if (
#             isinstance(token_payload, dict)
#             and "code" in token_payload
#             and token_payload["code"] in (401, 403)
#         ):
#             return token_payload
#         result = repo._get_users()
#         users_list = [
#             UserOut.from_orm(user).model_dump(mode="json") for user in result
#         ]
#         return response(data=users_list)
#     except Exception as e:
#         logger.exception(e)
#         return response(message="Internal Server", code=500)


@router.post("/login", response_model=TokenResponse)
def login(user: LoginSchema, repo: UserRepository = Depends(get_user_repo)):
    try:
        if not user.username:
            return response(code=400, message="Bad request")

        tmp_user = repo._get_by_username(user.username)
        if not tmp_user or tmp_user.is_deleted:
            return response(code=400, message="Bad request")

        if not argon2.verify(user.password, tmp_user.password):
            return response(code=400, message="Bad request")

        token_data = {"user_id": tmp_user.id, "name": tmp_user.name}
        token = create_access_token(token_data)
        refresh_token = create_refresh_token(token_data)
        result = {
            "access_token": token,
            "refresh_token": refresh_token,
            "token_type": "bearer",
        }
        return response(data=result)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server", code=500)


@router.get("/infor", response_model=None)
async def get_user_info(
    request: Request,
    repo: UserRepository = Depends(get_user_repo),
    payload=Depends(get_auth_dependency()),
):
    try:
        if (
            isinstance(payload, dict)
            and "code" in payload
            and payload["code"] in (401, 403)
        ):
            return payload
        user_id = payload.get("user_id")
        if not user_id:
            return response(message="Invalid token", code=401)

        user = repo._get_user_by_id(user_id)

        if not user or user.is_deleted:
            return response(message="ユーザー存在ない", code=404)

        user_out = UserOut.from_orm(user).model_dump(mode="json")
        return response(data=user_out)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/refresh-token")
async def refresh_token(request: Request):
    try:
        body = await request.json()
        refresh_token = body.get("refresh_token")

        if not refresh_token:
            return response(message="Missing refresh token", code=400)

        payload = verify_token(refresh_token)
        if not payload or payload.get("type") != "refresh":
            return response(message="Invalid or expired refresh token", code=401)

        token_data = {
            "user_id": payload.get("user_id"),
            "name": payload.get("name"),
        }
        new_access_token = create_access_token(token_data)

        return response(data={"access_token": new_access_token, "token_type": "bearer"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/change-password")
async def change_password_api(
    user: ChangePasswordSchema,
    repo: UserRepository = Depends(get_user_repo),
    token_payload=Depends(get_auth_dependency()),
):
    try:
        if (
            isinstance(token_payload, dict)
            and "code" in token_payload
            and token_payload["code"] in (401, 403)
        ):
            return token_payload
        user_id = token_payload.get("user_id")
        tmp_user = repo._get_user_by_id(user_id)
        if not tmp_user:
            logger.error("user not found")
            return response(code=400, message="Bad request")

        is_check_password = argon2.verify(user.old_password, tmp_user.password)
        if not is_check_password:
            logger.error("password invalid")
            return response(code=400, message="Bad request")

        tmp_new_password = argon2.hash(user.new_password)
        repo._change_password(tmp_user, tmp_new_password)
        logger.info("Successfully")
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


# @router.post("/delete/{user_id}")
# async def delete_user_api(
#     user_id: int,
#     repo: UserRepository = Depends(get_user_repo),
#     token_payload=Depends(get_auth_dependency([1])),
# ):
#     try:
#         if (
#             isinstance(token_payload, dict)
#             and "code" in token_payload
#             and token_payload["code"] in (401, 403)
#         ):
#             return token_payload

#         tmp_user = repo._get_user_by_id(user_id=user_id)
#         if not tmp_user:
#             return response(message="ユーザー存在ない", code=400)
#         repo._delete_user(user=tmp_user)
#         return response(data={"message": "User deleted successfully"})

#     except Exception as e:
#         logger.exception(e)
#         return response(message="Internal Server Error", code=500)


# @router.post("/sendcode/")
# def forgot_password_send_code(username):
#     try:
#         filter_conditions = {}
#         if username:
#             filter_conditions["username"] = username
#         user: User = User.query.filter_by(**filter_conditions).first()
#         if not user:
#             return {"msg": "User not found"}, 400
#         if user.reset_code_sent_at:
#             if user.reset_code_sent_at + timedelta(minutes=1) > datetime.utcnow():
#                 return {"msg": "1分以内に新しいコードをリクエストできます"}, 429

#         reset_code = ''.join(random.choices('0123456789', k=4))
#         user.reset_code_sent_at = datetime.utcnow()
#         user.reset_code = reset_code
#         resend.api_key = RESEND_API_KEY
#         params = {
#             "from": "Acme <onboarding@resend.dev>",
#             "to": [f"{username}"],
#             "subject": "忘れたパスワードのコード",
#             "html": f"<strong>{reset_code}</strong>"
#         }
#         db.session.commit()
#         resend.Emails.send(params)
#         return {"msg": "Success"}, 200
#     except Exception as e:
#         logging.error(f"Error sending forgot password code: {e}")
#         return {"msg": "Failed to send email"}, 500


# @router.post("/sendcode/")
# def forgot_password_send_code_redis(
#     req: SendCodeMail,
#     repo: AdminUserRepository = Depends(get_admin_user_repo),
# ):
#     try:
#         redis_key = f"forgot_code:{req.user_name}"
#         if r.exists(redis_key):
#             return response(
#                 message="1分以内に新しいコードをリクエストできます", code=429
#             )

#         user = repo._get_by_username(req.user_name, req.email)
#         if not user:
#             return response(message="ユーザー存在ない", code=400)
#         jst_time_now = Timer.strf_datetime_to_yyyymmddhhmmss(
#             Timer.get_jst_now_from_utc()
#         )

#         reset_code = "".join(random.choices("0123456789", k=4))
#         repo.set_reset_code(user, reset_code, jst_time_now)

#         # Lưu Redis key với TTL 1 phút
#         r.setex(redis_key, timedelta(minutes=1), "1")
#         resend.api_key = RESEND_API_KEY
#         # Gửi email
#         resend.Emails.send(
#             {
#                 "from": "Acme <onboarding@resend.dev>",
#                 "to": [user.user_name],
#                 "subject": "忘れたパスワードのコード",
#                 "html": f"<strong>{reset_code}</strong>",
#             }
#         )

#         return response(data={"message": "Success"})
#     except Exception as e:
#         logging.exception(e)
#         return response(message="Internal Server Error", code=500)


@router.post("/sendcode/")
@limiter.limit("3/minute", SlowApiLimiter.user_body_key_func)
async def forgot_password_send_code_slowapi(
    request: Request,
    req: SendCodeMail,
    repo: UserRepository = Depends(get_user_repo),
):
    try:
        user = repo._get_by_username(req.user_name, req.email)
        if not user:
            return response(message="ユーザー存在ない", code=400)

        jst_time_now = Timer.strf_datetime_to_yyyymmddhhmmss(
            Timer.get_jst_now_from_utc()
        )
        reset_code = "".join(random.choices("0123456789", k=4))
        repo.set_reset_code(user, reset_code, jst_time_now)
        mailer = SendMail()
        mailer._send(to_mail=req.email, mail_content=FORGOT_PASS_TEMPLATE, reset_code=reset_code)
        return response(data={"message": "Success"})
    except Exception as e:
        logging.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/verify-code/")
@limiter.limit("5/minute", SlowApiLimiter.user_body_key_func)
async def forgot_password_send_code_slowapi(
    request: Request,
    req: VerifyCode,
    repo: UserRepository = Depends(get_user_repo),
):
    try:
        user = repo._get_by_username(req.user_name, req.email)
        if not user:
            return response(message="ユーザー存在ない", code=400)
        
        jst_time_now = Timer.get_jst_now_from_utc()
        if user.reset_code != str(req.reset_code) or user.reset_code_sent_at + timedelta(minutes=5) < jst_time_now:
            return response(message="コード期限", code=400)
        return response(data={"message": "Success"})
    except Exception as e:
        logging.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/change-forgot-password")
async def change_password_api(
    req: ChangeForgotPasswordSchema,
    repo: UserRepository = Depends(get_user_repo),
    # token_payload=Depends(get_auth_dependency()),
):
    try:
        tmp_user = repo._get_by_username(req.user_name, req.email)
        if not req:
            return response(message="ユーザー存在ない", code=400)

        jst_time_now = Timer.get_jst_now_from_utc()
        if tmp_user.reset_code != str(req.reset_code) or tmp_user.reset_code_sent_at + timedelta(minutes=5) < jst_time_now:
            return response(message="コード期限", code=400)
        
        tmp_new_password = argon2.hash(req.new_password)
        repo._change_password(tmp_user, tmp_new_password)
        logger.info("Successfully")
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)