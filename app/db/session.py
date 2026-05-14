"""
Async SQLAlchemy session factory and engine configuration.
"""

from sqlalchemy.ext.asyncio import (
    AsyncSession,
    async_sessionmaker,
    create_async_engine,
)

from app.core.config import get_settings

settings = get_settings()

engine = create_async_engine(
    settings.DATABASE_URL,
    echo=settings.DB_ECHO,
    pool_size=settings.DB_POOL_SIZE,
    max_overflow=settings.DB_MAX_OVERFLOW,
    future=True,
)

async_session_factory = async_sessionmaker(
    engine,
    class_=AsyncSession,
    expire_on_commit=False,
)

# Laravel platform database engine
laravel_engine = create_async_engine(
    settings.LARAVEL_DATABASE_URL,
    echo=False,
    pool_size=5,
    max_overflow=2,
)

laravel_session_factory = async_sessionmaker(
    laravel_engine,
    class_=AsyncSession,
    expire_on_commit=False,
)


async def get_db() -> AsyncSession:
    """FastAPI dependency — yields an async session, auto-closes."""
    async with async_session_factory() as session:
        try:
            yield session
            await session.commit()
        except Exception:
            await session.rollback()
            raise
        finally:
            await session.close()


async def get_laravel_db() -> AsyncSession:
    """FastAPI dependency — yields a read-only Laravel DB session."""
    async with laravel_session_factory() as session:
        try:
            yield session
        finally:
            await session.close()
