from fastapi import FastAPI, HTTPException, Depends, WebSocket, WebSocketDisconnect, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
import httpx
import asyncio
from dotenv import load_dotenv
import os

load_dotenv()

app = FastAPI(title="TMS FastAPI Service", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=os.getenv("ALLOWED_ORIGINS", "http://localhost:5173").split(","),
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

security = HTTPBearer()
LARAVEL_API_URL  = os.getenv("LARAVEL_API_URL", "http://localhost:8000/api/v1")
INTERNAL_API_KEY = os.getenv("INTERNAL_API_KEY", "")


# ── Auth Dependency ─────────────────────────────────────────────
async def verify_token(credentials: HTTPAuthorizationCredentials = Depends(security)):
    """Verifikasi Sanctum token lewat Laravel — single source of truth."""
    async with httpx.AsyncClient() as client:
        try:
            res = await client.get(
                f"{LARAVEL_API_URL}/auth/me",
                headers={"Authorization": f"Bearer {credentials.credentials}"},
                timeout=5.0,
            )
            if res.status_code != 200:
                raise HTTPException(status_code=401, detail="Token tidak valid.")
            return res.json()["data"]
        except httpx.RequestError:
            raise HTTPException(status_code=503, detail="Auth service tidak dapat dijangkau.")


def require_role(*roles: str):
    async def check(user=Depends(verify_token)):
        if user["role"] not in roles:
            raise HTTPException(status_code=403, detail=f"Role yang diizinkan: {', '.join(roles)}")
        return user
    return check


# ── WebSocket Manager ───────────────────────────────────────────
class ConnectionManager:
    def __init__(self):
        self.active: dict[int, list[WebSocket]] = {}
        self.driver_locations: dict[int, dict] = {}

    async def connect(self, ws: WebSocket, user_id: int):
        await ws.accept()
        self.active.setdefault(user_id, []).append(ws)

    def disconnect(self, ws: WebSocket, user_id: int):
        if user_id in self.active:
            self.active[user_id].remove(ws)
            if not self.active[user_id]:
                del self.active[user_id]

    async def send_to(self, user_id: int, msg: dict):
        for ws in self.active.get(user_id, []):
            await ws.send_json(msg)

    async def broadcast_role(self, role: str, msg: dict, users: dict):
        for uid, u in users.items():
            if u.get("role") == role:
                await self.send_to(uid, msg)

    async def broadcast_all(self, msg: dict):
        for conns in self.active.values():
            for ws in conns:
                await ws.send_json(msg)


manager = ConnectionManager()
connected_users: dict[int, dict] = {}


# ── HTTP Endpoints ──────────────────────────────────────────────
@app.get("/health")
async def health():
    return {"status": "ok", "connected": sum(len(v) for v in manager.active.values())}


@app.post("/api/gps/update")
async def update_gps(payload: dict, driver=Depends(require_role("driver"))):
    """Driver kirim koordinat → broadcast realtime ke admin & user."""
    loc = {
        "driver_id":   driver["id"],
        "driver_name": driver["name"],
        "lat":         payload.get("lat"),
        "lng":         payload.get("lng"),
        "speed":       payload.get("speed", 0),
        "heading":     payload.get("heading", 0),
        "timestamp":   payload.get("timestamp"),
    }
    manager.driver_locations[driver["id"]] = loc
    event = {"event": "gps.location_updated", "data": loc}
    await manager.broadcast_role("admin", event, connected_users)
    await manager.broadcast_role("user",  event, connected_users)
    asyncio.create_task(_persist_gps(driver["id"], loc))
    return {"success": True}


@app.get("/api/gps/drivers")
async def get_driver_locations(user=Depends(verify_token)):
    return {"success": True, "data": list(manager.driver_locations.values())}


@app.post("/api/internal/notify")
async def internal_notify(payload: dict, x_internal_key: str | None = None):
    """
    Dipanggil Laravel untuk push notifikasi realtime.
    Contoh: job order baru → notif ke semua driver dengan suara.
    """
    if x_internal_key != INTERNAL_API_KEY:
        raise HTTPException(status_code=403, detail="Invalid internal key")

    notif = {
        "event":     payload.get("event"),
        "data":      payload.get("data", {}),
        "sound":     payload.get("sound", False),
        "timestamp": payload.get("timestamp"),
    }

    uid  = payload.get("user_id")
    role = payload.get("role")

    if uid:
        await manager.send_to(uid, notif)
    elif role:
        await manager.broadcast_role(role, notif, connected_users)
    else:
        await manager.broadcast_all(notif)

    return {"success": True}


# ── WebSocket ───────────────────────────────────────────────────
@app.websocket("/ws")
async def ws_endpoint(websocket: WebSocket, token: str):
    """
    Vue connect: ws://localhost:8001/ws?token=<sanctum_token>

    Events yang dikirim ke client:
      gps.location_updated     → update posisi driver di map
      job_order.new            → ada job order baru (driver dapat notif + suara)
      carrier.assigned         → driver di-assign ke job
      dispatch.status_updated  → status truck berubah
      pod.submitted            → bukti pengiriman masuk
      notification.new         → notifikasi umum
    """
    user = None
    try:
        async with httpx.AsyncClient() as client:
            res = await client.get(
                f"{LARAVEL_API_URL}/auth/me",
                headers={"Authorization": f"Bearer {token}"},
                timeout=5.0,
            )
            if res.status_code == 200:
                user = res.json()["data"]
    except Exception:
        await websocket.close(code=1008, reason="Auth failed")
        return

    if not user:
        await websocket.close(code=1008, reason="Invalid token")
        return

    uid = user["id"]
    await manager.connect(websocket, uid)
    connected_users[uid] = user

    await websocket.send_json({
        "event": "connected",
        "data":  {"user_id": uid, "role": user["role"], "message": "Terhubung ke TMS Realtime"}
    })

    try:
        while True:
            data = await websocket.receive_json()
            if data.get("type") == "ping":
                await websocket.send_json({"type": "pong"})
    except WebSocketDisconnect:
        manager.disconnect(websocket, uid)
        connected_users.pop(uid, None)


async def _persist_gps(driver_id: int, loc: dict):
    try:
        async with httpx.AsyncClient() as client:
            await client.post(
                f"{LARAVEL_API_URL}/internal/gps/persist",
                json={"driver_id": driver_id, **loc},
                headers={"X-Internal-Key": INTERNAL_API_KEY},
                timeout=3.0,
            )
    except Exception:
        pass