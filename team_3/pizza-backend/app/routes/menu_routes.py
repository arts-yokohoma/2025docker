# app/routes/menu_routes.py
from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List
import logging
from app.common.constant import DISCORD_BOT_TOKEN
from app.infras.external.db.postgres_connection import postgre_connection
from app.entity.menu_entity import MenuCreate, MenuDeleteRequest, MenuOut, MenuUpdate
from app.repository.menu_repository import MenuRepository
from app.utils.middleware import get_auth_dependency
from app.utils.response import response
import base64
import io
import requests
import httpx
router = APIRouter(tags=["Menu"])
logger = logging.getLogger()


def get_menu_repo(db: Session = Depends(postgre_connection)) -> MenuRepository:
    return MenuRepository(logger=logger, db=db)


@router.post("/create", response_model=MenuOut)
async def create_menu(
    req: MenuCreate,
    repo: MenuRepository = Depends(get_menu_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload

        image_url = None

        if req.image_url and req.image_url.startswith("data:image/"):
            base64_str = req.image_url.split(",")[1]
            image_bytes = base64.b64decode(base64_str)

            file_name = f"{req.name}.png"
            payload_json = {"content": "Upload image"}

            async with httpx.AsyncClient() as client:
                files = {"file": (file_name, image_bytes, "image/png")}
                res = await client.post(DISCORD_BOT_TOKEN, data=payload_json, files=files)
                res.raise_for_status()
                image_url = res.json()["attachments"][0]["url"]

        req.image_url = image_url
        menu = repo._create_menu(req)

        return response(
            data=MenuOut.from_orm(menu).model_dump(mode="json"),
            code=201
        )

    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/", response_model=List[MenuOut])
def list_menus(
    repo: MenuRepository = Depends(get_menu_repo),
    # payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        result = repo._get_menus()
        menus = [MenuOut.from_orm(user).model_dump(mode="json") for user in result]
        return response(data=menus)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.put("/update/{menu_id}", response_model=MenuUpdate)
def update_menu(
    menu_id: int,
    menu_data: MenuUpdate,
    repo: MenuRepository = Depends(get_menu_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        menu = repo._update_menu(menu_id, menu_data)
        if not menu:
            return response(message="メニュー存在が無い", code=404)
        return response(data=MenuOut.from_orm(menu).model_dump(mode="json"), code=200)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/delete")
def delete_menus(
    req: MenuDeleteRequest,
    repo: MenuRepository = Depends(get_menu_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        if not req.ids:
            return response(message="不正リクエスト", code=400)
        repo._delete_menus(req.ids)
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
