from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
import logging

from app.infras.external.db.postgres_connection import postgre_connection
from app.entity.role_entity import RoleCreate, RoleUpdate, RoleOut
from app.utils.response import response
from app.utils.middleware import get_auth_dependency
from app.repository.role_repository import RoleRepository

router = APIRouter(tags=["Role"])
logger = logging.getLogger()


def get_role_repo(
    db: Session = Depends(postgre_connection),
) -> RoleRepository:
    return RoleRepository(logger=logger, db=db)


@router.post("/create")
async def create_role(
    role: RoleCreate,
    repo: RoleRepository = Depends(get_role_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        existing_role = repo._get_role_by_name(role.name, int(role.type))
        if existing_role:
            return response(code=400, message="Role already exists")

        new_role = repo._create_role(role)
        return response(
            data={"id": new_role.id, "name": new_role.name}, message="Role created"
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/")
async def list_roles(
    repo: RoleRepository = Depends(get_role_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        roles = repo._list_roles()
        roles_out = [{"id": r.id, "name": r.name} for r in roles]
        return response(data=roles_out)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/{role_id}")
async def get_role(
    role_id: int,
    repo: RoleRepository = Depends(get_role_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        role = repo._get_role_by_id(role_id)
        if not role:
            return response(message="Role not found", code=404)
        return response(data={"id": role.id, "name": role.name})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.put("/{role_id}")
async def update_role(
    role_id: int,
    role_data: RoleUpdate,
    repo: RoleRepository = Depends(get_role_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        updated_role = repo._update_role(role_id, role_data)
        if not updated_role:
            return response(message="Role not found", code=404)
        return response(
            data={"id": updated_role.id, "name": updated_role.name},
            message="Role updated",
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.delete("/{role_id}")
async def delete_role(
    role_id: int,
    repo: RoleRepository = Depends(get_role_repo),
    payload=Depends(get_auth_dependency([1])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        deleted = repo._delete_role(role_id)
        if not deleted:
            return response(message="Role not found", code=404)
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
