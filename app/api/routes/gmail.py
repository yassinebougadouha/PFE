"""
Gmail integration routes — OAuth2 flow, sync, status, disconnect.
"""

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, Request, status
from fastapi.responses import RedirectResponse
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import AuditAction
from app.api.deps import get_current_user, require_admin
from app.schemas.gmail import GmailAuthURL, GmailStatusResponse, GmailSyncResult
from app.schemas.common import MessageOut
from app.services.gmail_service import (
    GmailService,
    get_authorization_url,
    exchange_code_for_tokens,
)
from app.services.audit_service import AuditService

router = APIRouter(prefix="/gmail", tags=["Gmail Integration"])


@router.get("/authorize", response_model=GmailAuthURL)
async def authorize_gmail(
    current_user: Annotated[User, Depends(get_current_user)],
):
    """
    Generate a Google OAuth2 authorization URL.
    The user must visit this URL to grant Gmail access.
    """
    url, state = get_authorization_url()
    # State is embedded in the URL; Google sends it back in the callback
    return {"authorization_url": url}


@router.get("/callback")
async def gmail_oauth_callback(
    code: str = Query(...),
    state: str = Query(...),
    db: AsyncSession = Depends(get_db),
):
    """
    Google redirects here after the user grants access.
    Exchanges the auth code for tokens and stores them.

    Note: In production, you'd associate state with a user session.
    For now, this endpoint requires the user to be logged in via a
    separate mechanism or we store using the first admin user.
    """
    try:
        credentials = exchange_code_for_tokens(code, state)
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Failed to exchange authorization code: {str(e)}",
        )

    # Get the Gmail address from the token
    from googleapiclient.discovery import build
    service = build("gmail", "v1", credentials=credentials)
    profile = service.users().getProfile(userId="me").execute()
    gmail_address = profile.get("emailAddress", "unknown@gmail.com")

    # Find the user by gmail address, or store under first admin
    # In production, state would encode user_id
    from app.services.user_service import UserService
    user_svc = UserService(db)

    # Try to find a user whose email matches the Gmail address
    user = await user_svc.get_by_email(gmail_address)
    if not user:
        # Fallback: get first active admin
        from sqlalchemy import select
        from app.db.models.enums import UserRole, UserStatus
        result = await db.execute(
            select(User).where(
                User.role == UserRole.ADMIN,
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
            ).limit(1)
        )
        user = result.scalar_one_or_none()

    if not user:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No matching user found. Please register first.",
        )

    gmail_svc = GmailService(db)
    await gmail_svc.save_credential(user.id, gmail_address, credentials)

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.CREATE,
        resource_type="gmail_credential",
        resource_id=gmail_address,
        user_id=user.id,
        description=f"Gmail connected: {gmail_address}",
    )

    return {
        "message": "Gmail connected successfully",
        "gmail_address": gmail_address,
        "user_id": str(user.id),
    }


@router.get("/status", response_model=GmailStatusResponse)
async def gmail_status(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Check whether Gmail is connected for the current user."""
    gmail_svc = GmailService(db)
    cred = await gmail_svc.get_credential(current_user.id)

    if not cred:
        return {"connected": False, "is_active": False}

    return {
        "connected": True,
        "gmail_address": cred.gmail_address,
        "is_active": cred.is_active,
        "last_synced": cred.updated_at,
    }


@router.post("/sync", response_model=GmailSyncResult)
async def manual_sync(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """
    Manually trigger a Gmail sync for the current user.
    Dispatches the task to Celery and returns immediately.
    """
    gmail_svc = GmailService(db)
    cred = await gmail_svc.get_credential(current_user.id)

    if not cred or not cred.is_active:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Gmail is not connected. Please authorize first.",
        )

    # Fire Celery task
    from app.workers.tasks import sync_gmail_for_user_task
    sync_gmail_for_user_task.delay(str(current_user.id))

    return {
        "emails_fetched": 0,
        "emails_ingested": 0,
        "errors": 0,
    }


@router.delete("/disconnect", response_model=MessageOut)
async def disconnect_gmail(
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Deactivate Gmail integration for the current user."""
    gmail_svc = GmailService(db)
    disconnected = await gmail_svc.disconnect(current_user.id)

    if not disconnected:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="No Gmail connection found.",
        )

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.DELETE,
        resource_type="gmail_credential",
        user_id=current_user.id,
        description="Gmail disconnected",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
    )

    return {"message": "Gmail disconnected successfully"}
