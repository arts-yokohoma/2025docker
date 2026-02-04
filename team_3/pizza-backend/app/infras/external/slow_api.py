from slowapi import Limiter
from slowapi.util import get_remote_address
import json

class SlowApiLimiter:
    _instance = None

    def __new__(cls, key_func=None):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance.limiter = Limiter(key_func=key_func or get_remote_address)
            cls._instance.key_func = key_func or get_remote_address
        return cls._instance

    def get_limiter(self):
        return self.limiter

    @staticmethod
    def user_body_key_func(request):
        try:
            data = json.loads(request.state.body.decode())
            return data.get("user_name") or data.get("email") or get_remote_address(request)
        except Exception:
            return get_remote_address(request)
