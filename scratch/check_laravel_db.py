"""Check Laravel DB connectivity from within the FastAPI container."""
import asyncio
import asyncpg


async def main():
    for host in ['platform-glpi-main-pgsql-1', 'pgsql']:
        try:
            conn = await asyncpg.connect(
                host=host, port=5432,
                user='sail', password='password',
                database='laravel',
            )
            rows = await conn.fetch(
                "SELECT id, email, name, role, glpi_user_id "
                "FROM users WHERE role IN ('admin', 'super_admin')"
            )
            for r in rows:
                print(dict(r))
            await conn.close()
            print(f'Connected via {host}')
            return
        except Exception as e:
            print(f'{host}: {e}')
    print('Could not connect to Laravel DB')


asyncio.run(main())
