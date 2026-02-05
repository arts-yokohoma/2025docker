from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from app.routes import user_admin_routes, user_routes, role_routes, menu_routes, category_routes, schedule_routes, order_routes, holiday_routes
from app.infras.external.db.postgres_connection import Base, engine
from app.common.constant import CORS_ENV
from fastapi.exceptions import RequestValidationError
from app.utils.exception_handler import validation_exception_handler, rate_limit_handler
from app.infras.external.slow_api import SlowApiLimiter
from slowapi.middleware import SlowAPIMiddleware
from slowapi.errors import RateLimitExceeded

Base.metadata.create_all(bind=engine)

app = FastAPI(title="PizzaLALALA")

limiter_singleton = SlowApiLimiter()
limiter = limiter_singleton.get_limiter()
app.state.limiter = limiter
app.add_middleware(SlowAPIMiddleware)

@app.middleware("http")
async def store_body(request: Request, call_next):
    body = await request.body()
    request.state.body = body
    response = await call_next(request)
    return response

origins = [origin.strip() for origin in CORS_ENV.split(",") if origin]
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)



app.add_exception_handler(RequestValidationError, validation_exception_handler)
app.add_exception_handler(RateLimitExceeded, rate_limit_handler)

api_prefix = "/api/v1"

app.include_router(user_admin_routes.router, prefix=f"{api_prefix}/user-admin")
app.include_router(user_routes.router, prefix=f"{api_prefix}/user")
app.include_router(role_routes.router, prefix=f"{api_prefix}/role")
app.include_router(menu_routes.router, prefix=f"{api_prefix}/menu")
app.include_router(category_routes.router, prefix=f"{api_prefix}/category")
app.include_router(schedule_routes.router, prefix=f"{api_prefix}/schedule")
app.include_router(order_routes.router, prefix=f"{api_prefix}/order")
app.include_router(holiday_routes.router, prefix=f"{api_prefix}/holiday")

@app.get("/")
def root():
    return {"message": "FastAPI project ready 🚀"}
