from fastapi import Request, HTTPException
from fastapi.security import HTTPBearer
from app.utils.jwt_handler import verify_token
from app.utils.response import response

security = HTTPBearer()


async def check_verify_token(request):
    auth = await security(request)
    token = auth.credentials
    return verify_token(token)


async def auth_dependency(request: Request, allowed_roles: list[int] | None = None):
    payload = await check_verify_token(request)
    if not payload:
        return response(message="トークン期限", code=401)
    
    role_type = payload.get("role_type")
    if allowed_roles and role_type is not None and role_type not in allowed_roles:
        return response(message="権限が無い", code=403)

    return payload


def get_auth_dependency(allowed_roles: list[int] | None = None):
    async def dependency(request: Request):
        return await auth_dependency(request, allowed_roles=allowed_roles)

    return dependency

