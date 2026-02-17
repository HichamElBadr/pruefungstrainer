from fastapi import FastAPI
from app.api.routes import router

app = FastAPI(title="Prüfungstrainer AI Gateway")
app.include_router(router)
