"""
Authentication routes — login, refresh, logout.
"""

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status, Request
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.enums import AuditAction
from app.api.deps import get_redis, get_current_user, RedisService
from app.schemas.auth import TokenPair, TokenRefreshRequest
from app.schemas.user import UserLogin, UserCreate, UserResponse
from app.schemas.common import MessageOut
from app.services.auth_service import AuthService
from app.services.settings_service import SettingsService
from app.services.user_service import UserService
from app.services.audit_service import AuditService
from app.db.models.user import User

router = APIRouter(prefix="/auth", tags=["Authentication"])


@router.post("/register", response_model=UserResponse, status_code=status.HTTP_201_CREATED)
async def register(
    payload: UserCreate,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
):
    """Register a new user account."""
    user_service = UserService(db)
    settings_service = SettingsService(db)
    settings = await settings_service.get_all_settings()

    if not settings["allow_registration"]:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail={
                "code": "registration_disabled",
                "message": "Registration is currently disabled by an administrator.",
            },
        )

    try:
        user_service.validate_password_policy(
            payload.password,
            min_password_length=int(settings["min_password_length"]),
            password_complexity=bool(settings["password_complexity"]),
        )
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

    try:
        user = await user_service.create_user(payload)
    except ValueError as e:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(e))

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.CREATE,
        resource_type="user",
        resource_id=str(user.id),
        user_id=user.id,
        description=f"User registered: {user.email}",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
        ip_address=request.client.host if request.client else None,
    )
    return user


@router.post("/login", response_model=TokenPair)
async def login(
    payload: UserLogin,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
):
    """Authenticate user and return JWT tokens."""
    auth_service = AuthService(db, redis)
    settings_service = SettingsService(db)
    user = await auth_service.authenticate(payload.email, payload.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid email or password",
        )
    app_settings = await settings_service.get_all_settings()
    tokens = auth_service.generate_tokens(
        user,
        access_token_minutes=int(app_settings["session_timeout"]),
    )

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.LOGIN,
        resource_type="user",
        resource_id=str(user.id),
        user_id=user.id,
        description=f"User logged in: {user.email}",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
        ip_address=request.client.host if request.client else None,
    )
    return tokens


@router.post("/refresh", response_model=TokenPair)
async def refresh_token(
    payload: TokenRefreshRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
):
    """Refresh an expired access token."""
    auth_service = AuthService(db, redis)
    settings_service = SettingsService(db)
    app_settings = await settings_service.get_all_settings()
    tokens = await auth_service.refresh_tokens(
        payload.refresh_token,
        access_token_minutes=int(app_settings["session_timeout"]),
    )
    if not tokens:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired refresh token",
        )
    return tokens


@router.post("/logout", response_model=MessageOut)
async def logout(
    request: Request,
    current_user: Annotated[User, Depends(get_current_user)],
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
):
    """Blacklist current tokens."""
    token = request.headers.get("Authorization", "").replace("Bearer ", "")
    auth_service = AuthService(db, redis)
    await auth_service.logout(access_token=token)

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.LOGOUT,
        resource_type="user",
        resource_id=str(current_user.id),
        user_id=current_user.id,
        description=f"User logged out: {current_user.email}",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
        ip_address=request.client.host if request.client else None,
    )
    return {"message": "Successfully logged out"}
