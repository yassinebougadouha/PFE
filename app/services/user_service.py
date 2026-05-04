"""
User service â€” CRUD + business logic for user management.
"""

import re
import uuid
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from fastapi import UploadFile

from app.core.security import hash_password, verify_password
from app.core.config import settings
from app.db.models.enums import UserRole, UserStatus
from app.db.models.user import User
from app.schemas.user import UserCreate, UserPasswordChangeRequest, UserProfileUpdate, UserUpdate

PASSWORD_UPPER_RE = re.compile(r"[A-Z]")
PASSWORD_LOWER_RE = re.compile(r"[a-z]")
PASSWORD_DIGIT_RE = re.compile(r"\d")
PASSWORD_SPECIAL_RE = re.compile(r"[^A-Za-z0-9]")


class UserService:

    def __init__(self, db: AsyncSession):
        self.db = db

    @staticmethod
    def _compute_profile_completed(role: UserRole, phone_number: str | None, teams_email: str | None) -> bool:
        if role != UserRole.ADMIN:
            return True
        return bool((phone_number or "").strip() and (teams_email or "").strip())

    @staticmethod
    def password_meets_complexity(password: str) -> bool:
        return bool(
            PASSWORD_UPPER_RE.search(password)
            and PASSWORD_LOWER_RE.search(password)
            and PASSWORD_DIGIT_RE.search(password)
            and PASSWORD_SPECIAL_RE.search(password)
        )

    def validate_password_policy(
        self,
        password: str,
        *,
        min_password_length: int,
        password_complexity: bool,
    ) -> None:
        if len(password) < int(min_password_length):
            raise ValueError(f"Password must be at least {min_password_length} characters long.")
        if password_complexity and not self.password_meets_complexity(password):
            raise ValueError(
                "Password must include upper-case, lower-case, numeric, and special characters."
            )

    async def create_user(
        self,
        payload: UserCreate,
        *,
        must_change_password: bool = False,
    ) -> User:
        """Register a new user."""
        existing = await self.get_by_email(payload.email)
        if existing:
            raise ValueError("A user with this email already exists.")

        if payload.phone_number:
            phone_exists = await self.get_by_phone(payload.phone_number)
            if phone_exists:
                raise ValueError("A user with this phone number already exists.")

        user = User(
            email=payload.email,
            hashed_password=hash_password(payload.password),
            full_name=payload.full_name,
            role=payload.role,
            phone_number=payload.phone_number,
            must_change_password=must_change_password,
            profile_completed=self._compute_profile_completed(payload.role, payload.phone_number, None),
        )
        self.db.add(user)
        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def get_by_id(self, user_id: uuid.UUID) -> Optional[User]:
        result = await self.db.execute(
            select(User).where(User.id == user_id, User.is_deleted == False)
        )
        return result.scalar_one_or_none()

    async def get_by_email(self, email: str) -> Optional[User]:
        result = await self.db.execute(
            select(User).where(User.email == email, User.is_deleted == False)
        )
        return result.scalar_one_or_none()

    async def get_by_phone(self, phone_number: str) -> Optional[User]:
        """Look up a user by phone number."""
        result = await self.db.execute(
            select(User).where(
                User.phone_number == phone_number,
                User.is_deleted == False,
            )
        )
        return result.scalar_one_or_none()

    async def list_users(
        self,
        role: Optional[UserRole] = None,
        status: Optional[UserStatus] = None,
        skip: int = 0,
        limit: int = 50,
    ) -> tuple[list[User], int]:
        query = select(User).where(User.is_deleted == False)
        count_query = select(func.count(User.id)).where(User.is_deleted == False)

        if role:
            query = query.where(User.role == role)
            count_query = count_query.where(User.role == role)
        if status:
            query = query.where(User.status == status)
            count_query = count_query.where(User.status == status)

        total = (await self.db.execute(count_query)).scalar() or 0
        result = await self.db.execute(query.offset(skip).limit(limit).order_by(User.created_at.desc()))
        return list(result.scalars().all()), total

    async def update_user(self, user_id: uuid.UUID, payload: UserUpdate) -> Optional[User]:
        user = await self.get_by_id(user_id)
        if not user:
            return None

        update_data = payload.model_dump(exclude_unset=True)
        next_phone = update_data.get("phone_number")
        if next_phone and next_phone != user.phone_number:
            phone_exists = await self.get_by_phone(next_phone)
            if phone_exists and phone_exists.id != user.id:
                raise ValueError("A user with this phone number already exists.")

        for field, value in update_data.items():
            setattr(user, field, value)

        user.profile_completed = self._compute_profile_completed(
            user.role,
            user.phone_number,
            user.teams_email,
        )

        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def update_profile(self, user_id: uuid.UUID, payload: UserProfileUpdate) -> Optional[User]:
        user = await self.get_by_id(user_id)
        if not user:
            return None

        update_data = payload.model_dump(exclude_unset=True)
        next_phone = update_data.get("phone_number")
        if next_phone and next_phone != user.phone_number:
            phone_exists = await self.get_by_phone(next_phone)
            if phone_exists and phone_exists.id != user.id:
                raise ValueError("A user with this phone number already exists.")

        for field, value in update_data.items():
            setattr(user, field, value)

        user.profile_completed = self._compute_profile_completed(
            user.role,
            user.phone_number,
            user.teams_email,
        )

        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def change_password(
        self,
        user_id: uuid.UUID,
        payload: UserPasswordChangeRequest,
        *,
        min_password_length: int,
        password_complexity: bool,
    ) -> Optional[User]:
        user = await self.get_by_id(user_id)
        if not user:
            return None

        if not verify_password(payload.current_password, user.hashed_password):
            raise ValueError("Current password is incorrect.")

        self.validate_password_policy(
            payload.new_password,
            min_password_length=min_password_length,
            password_complexity=password_complexity,
        )

        user.hashed_password = hash_password(payload.new_password)
        user.must_change_password = False

        await self.db.flush()
        await self.db.refresh(user)
        return user

    async def soft_delete(self, user_id: uuid.UUID) -> bool:
        user = await self.get_by_id(user_id)
        if not user:
            return False
        user.is_deleted = True
        user.deleted_at = datetime.now(timezone.utc)
        await self.db.flush()
        return True

    async def upload_profile_picture(self, user_id: uuid.UUID, file: UploadFile) -> Optional[User]:
        """Upload and save a profile picture for a user."""
        user = await self.get_by_id(user_id)
        if not user:
            return None

        # Validate file type
        allowed_types = {"image/jpeg", "image/png", "image/webp", "image/gif"}
        if file.content_type not in allowed_types:
            raise ValueError("Only image files (JPEG, PNG, WebP, GIF) are allowed.")

        # Validate file size (max 5MB)
        max_size = 5 * 1024 * 1024
        content = await file.read()
        if len(content) > max_size:
            raise ValueError("File size must not exceed 5MB.")

        # Create profile pictures directory
        profile_pics_dir = Path(settings.UPLOADS_DIR) / "profile_pictures"
        profile_pics_dir.mkdir(parents=True, exist_ok=True)

        # Generate filename
        ext = Path(file.filename or "image.jpg").suffix
        filename = f"{user_id}{ext}"
        file_path = profile_pics_dir / filename

        # Save file
        with open(file_path, "wb") as f:
            f.write(content)

        # Update user profile picture URL (full URL accessible from browser)
        user.profile_picture_url = f"{settings.BACKEND_API_URL}/uploads/profile_pictures/{filename}"

        await self.db.flush()
        await self.db.refresh(user)
        return user

