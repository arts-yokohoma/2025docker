from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List
import logging
from app.infras.external.db.postgres_connection import postgre_connection
from app.entity.category_entity import (
    CategoryCreate,
    CategoryDeleteRequest,
    CategoryUpdate,
    CategoryOut,
)
from app.repository.category_repository import CategoryRepository
from app.utils.middleware import get_auth_dependency
from app.utils.response import response

router = APIRouter(tags=["Category"])
logger = logging.getLogger()


def get_category_repo(db: Session = Depends(postgre_connection)) -> CategoryRepository:
    return CategoryRepository(logger=logger, db=db)


@router.post("/create", response_model=CategoryOut)
def create_category(
    category_data: CategoryCreate,
    repo: CategoryRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        category = repo._create_category(category_data)
        return response(
            data=CategoryOut.from_orm(category).model_dump(mode="json"), code=201
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.get("/", response_model=List[CategoryOut])
def list_categories(
    repo: CategoryRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        result = repo._get_categories()
        categories = [
            CategoryOut.from_orm(cat).model_dump(mode="json") for cat in result
        ]
        return response(data=categories)
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.put("/update/{category_id}", response_model=CategoryOut)
def update_category(
    category_id: int,
    category_data: CategoryUpdate,
    repo: CategoryRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        category = repo._update_category(category_id, category_data)
        if not category:
            return response(message="Category not found", code=404)
        return response(
            data=CategoryOut.from_orm(category).model_dump(mode="json"), code=200
        )
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)


@router.post("/delete", response_model=CategoryOut)
def delete_category(
    req: CategoryDeleteRequest,
    repo: CategoryRepository = Depends(get_category_repo),
    payload=Depends(get_auth_dependency([1, 2])),
):
    try:
        if isinstance(payload, dict) and "code" in payload and payload["code"] in (401, 403):
            return payload
        if not req.ids:
            return response(message="不正リクエスト", code=400)
        repo._delete_category(req.ids)
        return response(data={"message": "success"})
    except Exception as e:
        logger.exception(e)
        return response(message="Internal Server Error", code=500)
