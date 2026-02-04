from fastapi.responses import JSONResponse

def response(data=None, message="success", code=200):
    return JSONResponse(
        status_code=200,
        content={"message": message, "data": data, "code":code},
    )
